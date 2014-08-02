clear; clc;
% Add libraries and environmental variable
run('/net/per610a/export/das11f/ledduy/plsang/nvtiep/libs/vlfeat-0.9.18/toolbox/vl_setup.m');
addpath(genpath('/net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/code/funcs'));
addpath(genpath('/net/per610a/export/das11f/ledduy/plsang/nvtiep/funcs'));

% parameter settings
exp_purpose = 'best_config';
DB = 'INS2013';
disp(exp_purpose)
disp(DB)
eval_topN = 10000;
renew = false;

work_dir = fullfile('/net/per610a/export/das11f/ledduy/plsang/nvtiep/INS', DB);
gt_filename = fullfile(work_dir, 'ins.search.qrels.tv13');

%database.comp_sim= struct('query_obj','fg+bg_0.1','feat_detr','-hesaff', 'feat_desc', '-rootsift -noangle',...
%  'clustering','akmeans','K',1000000,'num_samps',100000000,'iter',50,...
%  'build_params',struct('algorithm', 'kdtree','trees', 8, 'checks', 800, 'cores', 10),...
%  'video_sampling',1,'frame_sampling',1,'knn',1,'delta_sqr',6250,'db_agg','avg_pooling',...
%  'vocab','full','trim','notrim','freq','clip','weight','idf','norm','l1',...
%  'query_knn',3,'query_delta_sqr',6250,'query_num',-1,'query_agg','avg_pooling','dist','l2asym_ivf');
%database.comp_sim= struct('query_obj','fg+bg_0.1','feat_detr','-perdoch -hesaff', 'feat_desc', '-rootsift',...
%    'clustering','akmeans','K',1000000,'num_samps',100000000,'iter',50,...
%    'build_params',struct('algorithm', 'kdtree','trees', 8, 'checks', 800, 'cores', 10),...
%    'video_sampling',1,'frame_sampling',1,'knn',3,'delta_sqr',6250,'db_agg','avg_pooling',...
%    'vocab','full','trim','notrim','freq','clip','weight','idf','norm','nonorm',...
%    'query_knn',3,'query_delta_sqr',6250,'query_num',-1,'query_agg','dist_avg','dist','autoasym_ivf_0.7');
	
database.comp_sim= struct('query_obj','fg+bg_0.1','feat_detr','-hesaff', 'feat_desc', '-rootsift -noangle',...
    'clustering','akmeans','K',1000000,'num_samps',100000000,'iter',50,...
    'build_params',struct('algorithm', 'kdtree','trees', 8, 'checks', 800, 'cores', 10),...
    'video_sampling',1,'frame_sampling',1,'knn',1,'delta_sqr',6250,'db_agg','avg_pooling',...
    'vocab','full','trim','notrim','freq','clip','weight','idf','norm','nonorm',...
    'query_knn',3,'query_delta_sqr',6250,'query_num',-1,'query_agg','avg_pooling','dist','l2_ivf');

database.db_frame_dir = '/net/per610a/export/das11g/caizhizhu/ins/ins2013/frames_png'; 
database.query_dir = fullfile(work_dir,'query');
database.query_mask_dir = fullfile(database.query_dir,'masks');
% Place to save ranked list and performance
database.query_mat_dir = fullfile(database.query_dir,'result',exp_purpose,'mat');
database.query_txt_dir = fullfile(database.query_dir,'result',exp_purpose,'txt');
database.query_perf_dir = fullfile(database.query_dir,'result',exp_purpose,'perf');

% Prepare dirs
if ~exist(database.query_mat_dir,'dir')
    mkdir(database.query_mat_dir);
end
if ~exist(database.query_txt_dir,'dir')
    mkdir(database.query_txt_dir);
end
if ~exist(database.query_perf_dir,'dir')
    mkdir(database.query_perf_dir);
end

% set proper norm according to the dist and agg options
if strcmp(database.comp_sim.dist, 'l1_ivf')
	database.comp_sim.norm='l1';
	database.comp_sim.delta_sqr=6250;
	database.comp_sim.query_delta_sqr=6250;
elseif strcmp(database.comp_sim.dist, 'l2_ivf') 
	database.comp_sim.norm='l2';
	database.comp_sim.delta_sqr=6250;
	database.comp_sim.query_delta_sqr=6250;
elseif ~isempty(strfind(database.comp_sim.dist, 'asym'))
	database.comp_sim.norm='nonorm';
	database.comp_sim.delta_sqr=6250;
	database.comp_sim.query_delta_sqr=6250;
end

% rescale delta_sqr
if database.comp_sim.knn>1 && database.comp_sim.delta_sqr~=-1
	if ~isempty(strfind(database.comp_sim.feat_desc, 'root'))
		database.comp_sim.delta_sqr=database.comp_sim.delta_sqr/5e5;
	elseif ~isempty(strfind(database.comp_sim.feat_desc, 'color'))
		database.comp_sim.delta_sqr=database.comp_sim.delta_sqr*2;
	elseif ~isempty(strfind(database.comp_sim.feat_desc, 'mom'))
		database.comp_sim.delta_sqr=database.comp_sim.delta_sqr/1e3;
	end
end
% If soft-assignment then using delta_sqr = 0.0125 for INS2013 data and rootsift feature
if database.comp_sim.query_knn>1 && database.comp_sim.query_delta_sqr~=-1
	if ~isempty(strfind(database.comp_sim.feat_desc, 'root'))
		database.comp_sim.query_delta_sqr=database.comp_sim.query_delta_sqr/5e5;
	end
end
	
%%  RUN RANKING PROGRAM
disp(database.comp_sim);
feature_detr = database.comp_sim.feat_detr;
feature_desc = database.comp_sim.feat_desc;
feature_config = sprintf('%s %s', feature_detr, feature_desc);
feature_name = strrep(feature_config, '-','');
feature_name = strrep(feature_name, ' ','_');
query_feature_name = sprintf('%s_%s',database.comp_sim.query_obj,feature_name);

database.db_mat_dir = fullfile(work_dir,[feature_name '_mat']);

% clustering_name
if ~isempty(strfind(database.comp_sim.clustering,'akmeans'))
	clustering_name = sprintf('%s_%d_%d_%d',database.comp_sim.clustering,...
		database.comp_sim.K,database.comp_sim.num_samps,database.comp_sim.iter); 
end

%build_name
if strcmp(database.comp_sim.build_params.algorithm,'kdtree')
	build_name = sprintf('kdtree_%d_%d',database.comp_sim.build_params.trees,...
		database.comp_sim.build_params.checks);
	quantize_name = sprintf('%d', database.comp_sim.knn);
	if database.comp_sim.knn>1
		quantize_name = sprintf('%s_%g', quantize_name,database.comp_sim.delta_sqr);
	end
end

db_quantize_name = sprintf('v%d_f%d_%s', database.comp_sim.video_sampling,database.comp_sim.frame_sampling,quantize_name);
db_agg_name = database.comp_sim.db_agg;
%bow_making_name
bow_making_name = sprintf('%s_%s_%s_%s_%s',database.comp_sim.vocab,...
	database.comp_sim.trim,database.comp_sim.freq,...
	database.comp_sim.weight,database.comp_sim.norm);
	   
%query_quantize_name
if strcmp(database.comp_sim.build_params.algorithm,'kdtree')
	query_quantize_name = sprintf('kdtree_%d', database.comp_sim.query_knn);
	if database.comp_sim.query_knn>1
		query_quantize_name = sprintf('%s_%g', query_quantize_name,database.comp_sim.query_delta_sqr);
	end
end
% query_agg_name
query_agg_name = sprintf('%d',database.comp_sim.query_num);
query_bow_making_name = sprintf('%s_%d',bow_making_name,database.comp_sim.query_num);
if database.comp_sim.query_num ~= 1
	query_agg_name = sprintf('%d_%s',database.comp_sim.query_num, database.comp_sim.query_agg);
	if ~isempty(strfind(database.comp_sim.query_agg,'avg_pooling'))
		query_bow_making_name = sprintf('%s_%s',bow_making_name,query_agg_name);
	end
end

% cluster_dir
database.cluster_dir = fullfile(work_dir,[feature_name,'_cluster'],clustering_name);
assert(exist(database.cluster_dir,'dir') == 7);
cluster_filename = dir(fullfile(database.cluster_dir,sprintf('Clustering_l2_%d_%d*.hdf5',database.comp_sim.K,database.comp_sim.num_samps)));
assert(length(cluster_filename) == 1);
database.cluster_filename = cluster_filename(1).name;
database.build_dir = fullfile(database.cluster_dir,build_name);
database.bow_dir = fullfile(database.build_dir,db_quantize_name);

% query_frame_dir
database.query_frame_dir = fullfile(database.query_dir,'frames_png');
database.query_feat_dir = fullfile(database.query_dir,'feature', strrep(feature_name,'rootsift','sift'));

% query_quant_dirname: place contains bow of query
query_quant_dirname = sprintf('%s_%s_%s_%s',query_feature_name,clustering_name,build_name,query_quantize_name);
database.query_bow_dir = fullfile(database.query_dir,'bow',query_quant_dirname);
if ~exist(database.query_bow_dir,'dir')
	mkdir(database.query_bow_dir);
end

%dist_name
dist_name = database.comp_sim.dist;

% res_name
res_name = sprintf('%s_%s_%s_%s_%s_%s_%s_%s_%s',query_feature_name,clustering_name,...
	build_name,db_quantize_name,db_agg_name,bow_making_name,query_quantize_name,query_agg_name,dist_name);
res_filename = fullfile(database.query_mat_dir,['res_' res_name, '.mat']);

% If result file already existed --> load and skip ranking process
if exist(res_filename, 'file')
	disp('Loading result file...')
	load(res_filename);
else
	% Load codebook file
	clustering_file = fullfile(database.cluster_dir, database.cluster_filename);
	if ~exist('prev_clustering_file','var') || ~strcmp(clustering_file,prev_clustering_file)
		if ~exist(clustering_file,'file')
			fprintf('centroid file (%s) doesnot exist!\n',clustering_file)
			return;
		end
		time('centers=hdf5read(clustering_file,''/clusters'');');
		[feat_len,hist_len]=size(centers);
		prev_clustering_file = clustering_file;
	end
	% Load database bow_file
	if strcmp(db_agg_name,'avg_pooling')
		bow_file = fullfile(database.bow_dir,sprintf('bow_clip_%s_%s.mat', bow_making_name, db_agg_name));
	end
	% If databe bag-of-word already load from previous runs
	if ~exist('prev_bow_file','var') || ~strcmp(bow_file,prev_bow_file)
		% Load database bag-of-word
		if exist(bow_file,'file') && ~renew
			time('load(bow_file)','Loading weighted and normalized database bow file...');
		else
			%% Compute database bow at the beginning
			% Load raw bag of word of the database
			raw_bow_file = fullfile(database.bow_dir,[database.comp_sim.db_agg, '_raw_bow.mat']);
			raw_bow_file = fullfile(database.bow_dir,'raw_bow.mat');
			if ~exist(raw_bow_file,'file')
				fprintf('raw bow file (%s) doesnot exist!\n',raw_bow_file)
				return;
			end;
			time('load(raw_bow_file);','Loading raw database frame bow...');
			
			assert(exist('list_frame_bow', 'var') ~= 0);
			if database.comp_sim.frame_sampling > 1
				disp('sampling database frames');
				list_frame_bow = cellfun(@(x) x(:,1:database.comp_sim.frame_sampling:end), list_frame_bow,'UniformOutput', false);
			end
			
			% Pooling
			switch database.comp_sim.db_agg
			case 'max_pooling'
				list_clip_bow = cellfun(@(x) max(x,[],2), list_frame_bow, 'uniformoutput', false);
				clip_frame_num = cellfun( @(x) size(x,2), list_frame_bow,'uniformoutput',false);
				clip_frame_num = cell2mat(clip_frame_num);
				db_bow = sparse([list_clip_bow{:}]);
			case 'avg_pooling'
				list_clip_bow = cellfun(@(x) mean(x,2), list_frame_bow, 'uniformoutput', false);
				clip_frame_num = cellfun( @(x) size(x,2), list_frame_bow,'uniformoutput',false);
				clip_frame_num = cell2mat(clip_frame_num);
				db_bow = sparse([list_clip_bow{:}]);
			otherwise
				disp('error db_agg option!');
				return;
			end
			big_bow_info_file = fullfile(database.bow_dir,'raw_bow_info.mat');
			time('load(big_bow_info_file);','load raw bow info ...');
			
			% computing tf 
			term_freq = list_term_freq.(database.comp_sim.freq);
			db_lut=list_id2clip_lut;
			%%%%%%%%% CLEAR redundant data
			clear list_term_freq list_clip_bow list_frame_bow list_avg_pooling_bow list_max_pooling_bow list_id2clip_lut;
			
			% compute weighting
			weight = get_wei(term_freq,database.comp_sim.weight);
			% trim bow
			if ~strcmp(database.comp_sim.trim,'notrim')
				db_bow = trim_bow(db_bow,database.comp_sim.trim);
			end
			
			disp('weighting and normalizing');
			% matlabpool(8)
			% apply weighting
			% normalize bow
			if ~strcmp(database.comp_sim.weight,'nowei')...
				&& ~strcmp(database.comp_sim.norm,'nonorm')
				norm_id=str2double(database.comp_sim.norm(end));
				assert(norm_id == 1 || norm_id == 2);
				for i=1:size(db_bow,2)
					db_bow(:,i) = db_bow(:,i).*weight;
					bow_norm = norm(db_bow(:,i),norm_id)+eps;
					db_bow(:,i) = db_bow(:,i)./bow_norm;
				end
			elseif ~strcmp(database.comp_sim.weight,'nowei')
				for i=1:size(db_bow,2)
					db_bow(:,i) = db_bow(:,i).*weight;
				end
			elseif ~strcmp(database.comp_sim.norm,'nonorm')
				norm_id=str2double(database.comp_sim.norm(end));
				assert(norm_id == 1 || norm_id == 2);
				for i=1:size(db_bow,2)
					bow_norm = norm(db_bow(:,i),norm_id)+eps;
					db_bow(:,i) = db_bow(:,i)./bow_norm;
				end
			end
			%matlabpool close
			time('save(bow_file,''db_bow'',''db_lut'',''weight'',''clip_frame_num'',''-v7.3'')',...
				 'saving weighted and normalized database bow file ...');
		end
		% Build inverted file
		ivf = [];
		if ~isempty(strfind(database.comp_sim.dist,'ivf'))
			time('ivf = BuildInvFile([],db_bow,0,false);','Building inverted file...');
			db_bow = [];
		end
		prev_bow_file = bow_file;
	end
	
	build_query_bow;
	
	%% Compute distance and ranking
	query_num = length(query_filenames);
	dists = cell(1,query_num);
	disp('Computing distance ...');
	tic;
	for qid = 1:query_num
		fprintf('\r%2d/%d ',qid,query_num);
		subset_num = length(topic_bows{qid});
		dists{qid} = cell(1,subset_num);
		for sid = 1:subset_num
			% make sure comp_dist output all zero distance with all
			% zero queries
			%topic_bows{qid}{sid} = mean(topic_bows{qid}{sid},2);
			dists{qid}{sid} = comp_dist(ivf,topic_bows{qid}{sid},db_bow,database.comp_sim.dist,false);
		end
	end
	fprintf('\n %.4f\n',toc);
	
	if database.comp_sim.query_num ~= 1 && isempty(strfind(database.comp_sim.query_agg,'max_pooling'))...
			&& isempty(strfind(database.comp_sim.query_agg,'avg_pooling'))
		disp('query aggregation ...');
		for qid = 1:query_num
			tic;
			fprintf('\r%d(1-%d)',qid,query_num);
			subset_num = length(dists{qid});
			for sid = 1:subset_num
				good_id = find(sum(topic_bows{qid}{sid},1));
				num_good = length(good_id);
				if num_good == 0
					dists{qid}{sid} = dists{qid}{sid}(:,1);
					continue;
				elseif num_good == 1
					dists{qid}{sid} = dists{qid}{sid}(:,good_id);
					continue;
				end
				dist = dists{qid}{sid}(:,good_id);
				if ~isempty(strfind(database.comp_sim.query_agg,'rank'))
					[~,idx]  = sort(dist,1,'ascend');
					[~,dist]  = sort(idx,1,'ascend');
				end
				
				if ~isempty(strfind(database.comp_sim.query_agg,'min'))
					[dist,idx] = min(dist,[],2);
					%print out stat to check if mins are equally
					fprintf('min distribution:');
					true_idx = find(dist~=max(dist));
					idx=idx(true_idx);
					for n = 1:num_good
						fprintf(' %d(%.0f%%);',n,length(find(idx==n))*100/length(idx));
					end
					fprintf('\n');
				elseif ~isempty(strfind(database.comp_sim.query_agg,'avg'))
					if ~isempty(strfind(database.comp_sim.query_agg,'fgwei'))
						fg_pt_num = cell2mat(cellfun(@(x)length(x),frame_quant_info{qid}.fg_index{sid},'UniformOutput', false));
						dist_weight = fg_pt_num/sum(fg_pt_num);
						dist = mean(dist.*repmat(full(dist_weight),size(dist,1),1),2);
					elseif ~isempty(strfind(database.comp_sim.query_agg,'wei'))
						pt_num = cell2mat(cellfun(@(x)size(x,2),frame_quant_info{qid}.quant_bins{sid},'UniformOutput', false));
						dist_weight = pt_num/sum(pt_num);
						dist = mean(dist.*repmat(full(dist_weight),size(dist,1),1),2);
					else
						dist = mean(dist,2);
					end
				elseif ~isempty(strfind(database.comp_sim.query_agg,'max'))
					[dist,idx] = max(dist,[],2);
					%print out stat to check if maxs are equally
					fprintf('max distribution:');
					true_idx = find(dist~=min(dist));
					idx=idx(true_idx);
					for n = 1:num_good
						fprintf(' %d(%.0f%%);',n,length(find(idx==n))*100/length(idx));
					end
					fprintf('\n');
				end
				dists{qid}{sid} = dist;
			end
			fprintf(' %.0f',toc);
		end
		fprintf('\n');
	end
	
	% Sort result
	tic;
	fprintf('sorting ...');
	score = cell(1,query_num);
	ranks = cell(1,query_num);
	for qid = 1:query_num
		subset_num = length(topic_bows{qid});
		score{qid} = cell(1,subset_num);
		ranks{qid} = cell(1,subset_num);
		for sid = 1:subset_num
			[score{qid}{sid},ranks{qid}{sid}]=sort(dists{qid}{sid},1);
		end
	end
	fprintf(' %.0f\n',toc);

	% Save result
	time('save(res_filename,''db_lut'',''score'',''ranks'',''query_filenames'',''-v7.3'')');
end

tic;
disp('Write result of current run ...');
knn_txt_dir = fullfile(database.query_txt_dir,res_name);
if ~exist(knn_txt_dir,'dir')
	mkdir(knn_txt_dir)
end
num_shown_frames = 4;
%write_knn(query_filenames, db_lut, score, ranks, database.db_frame_dir, ...
%	knn_txt_dir, 1000, 2, false);
write_knn(query_filenames, db_lut, score, ranks, database.db_frame_dir, ...
    knn_txt_dir, eval_topN, num_shown_frames, false);
fprintf(' %.0fs\n',toc);

if strcmp(DB, 'INS2013')
	% Save trec performance
	res_names = dir(res_filename);
	run_name = res_names(1).name(5:end-4);
	trecvid_res_dir = fullfile(database.query_perf_dir, run_name);
	if ~exist(trecvid_res_dir, 'dir')
		mkdir(trecvid_res_dir);
	end

	ex_filename = fullfile(work_dir,'dropped.example.image.shots');
	fid = fopen(ex_filename,'r');
	ex_list = textscan(fid,'%s');
	ex_list = ex_list{1};
	fclose(fid);

	save_trec_perf_list(query_filenames,score,ranks,db_lut,run_name,trecvid_res_dir,eval_topN, ex_list);

	% Evaluation
	%[gt,ngt] = read_gt_file(gt_filename);
	fgt = fopen(gt_filename,'r');
	gt_cell = textscan(fgt,'%d %*d %s %d');
	fclose(fgt);
	[qid, qid_ind,~]=unique(gt_cell{1});
	for i=1:length(qid)
		if i==1
			nz_ids = find(gt_cell{3}(1:qid_ind(i)));
			z_ids = find(gt_cell{3}(1:qid_ind(i))==0);
			check_list = gt_cell{2}(1:qid_ind(i));
		else
			nz_ids = find(gt_cell{3}(qid_ind(i-1)+1:qid_ind(i)));
			z_ids = find(gt_cell{3}(qid_ind(i-1)+1:qid_ind(i))==0);
			check_list = gt_cell{2}(qid_ind(i-1)+1:qid_ind(i));
		end
		gt.(['id_' num2str(qid(i))]) = check_list(nz_ids);
		ngt.(['id_' num2str(qid(i))]) = check_list(z_ids);
	end

	%[gt,ngt] = read_gt_file(gt_filename);
	%trecvid_res_dir = '/net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/INS2013/query/result/best_config/perf/fg+bg_0.1_hesaff_rootsift_noangle_akmeans_1000000_100000000_50_kdtree_8_800_v1_f1_3_0.0125_avg_pooling_full_notrim_clip_idf_nonorm_kdtree_3_0.0125_-1_dist_avg_autoasym_ivf_0.5';
	%eval_topN = 1000;
	perf = compute_trec_performance(query_filenames,gt_filename,trecvid_res_dir, eval_topN, gt);
	fprintf('Performance of the system is: %f', mean([perf{:}]));
end