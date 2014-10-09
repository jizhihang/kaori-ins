% config_file: all info for a run
% data_name: tv2014
% query_pat: query2014
% test_pat: test2014 
% path: tv2014/test2014 or tv2014/query2014
% topK: number of shots to be returned. E.g 1,000 or 10,000 (default)
config_file = '../run_configs/tv2013.surrey.hard.soft.latefusion.asym.cfg';
data_name = 'tv2014';
test_pat = 'test2014';
query_pat = 'vistek2014';
topK = 1000;
addpath('..');
% Read configure file
file_config = fopen(config_file, 'r');
re = '(.*):(.*)';
while ~feof(file_config)
	line = fgetl(file_config);
	[rematch, retok] = regexp(line, re, 'match', 'tokens');
	switch strtrim(retok{1}{1})
	case 'query_obj'
		database.comp_sim.query_obj = retok{1}{2};
	case 'feat_detr'
		database.comp_sim.feat_detr = retok{1}{2};
	case 'feat_desc'
		database.comp_sim.feat_desc = retok{1}{2};
	case 'clustering'
		database.comp_sim.clustering = retok{1}{2};
	case 'K'
		database.comp_sim.K = str2double(retok{1}{2});
	case 'num_samps'
		database.comp_sim.num_samps = str2double(retok{1}{2});
	case 'iter'
		database.comp_sim.iter = str2double(retok{1}{2});
	case 'video_sampling'
		database.comp_sim.video_sampling = str2double(retok{1}{2});
	case 'frame_sampling'
		database.comp_sim.frame_sampling = str2double(retok{1}{2});
	case 'knn'
		database.comp_sim.knn = str2double(retok{1}{2});
	case 'delta_sqr'
		database.comp_sim.delta_sqr = str2double(retok{1}{2});
	case 'db_agg'
		database.comp_sim.db_agg = retok{1}{2};
	case 'vocab'
		database.comp_sim.vocab = retok{1}{2};
	case 'trim'
		database.comp_sim.trim = retok{1}{2};
	case 'freq'
		database.comp_sim.freq = retok{1}{2};
	case 'weight'
		database.comp_sim.weight = retok{1}{2};
	case 'norm'
		database.comp_sim.norm = retok{1}{2};
	case 'query_knn'
		database.comp_sim.query_knn = str2double(retok{1}{2});
	case 'query_delta_sqr'
		database.comp_sim.query_delta_sqr = str2double(retok{1}{2});
	case 'query_num'
		database.comp_sim.query_num = str2double(retok{1}{2});
	case 'query_agg'
		database.comp_sim.query_agg = retok{1}{2};
	case 'dist'
		database.comp_sim.dist = retok{1}{2};
	case 'run_prefix'
		run_prefix = retok{1}{2};
	end
end

database.comp_sim.build_params = struct('algorithm', 'kdtree','trees', 8, 'checks', 800, 'cores', 10);

fclose(file_config);

% Add libraries and environmental variable
run('/net/per610a/export/das11f/ledduy/plsang/nvtiep/libs/vlfeat-0.9.18/toolbox/vl_setup.m');
addpath(genpath('/net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/code/funcs'));
addpath(genpath('/net/per610a/export/das11f/ledduy/plsang/nvtiep/funcs'));

% parameter settings
clobber = false;
eval_topN = topK;

root_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014';
work_dir = fullfile(root_dir, 'result', data_name, test_pat); % result/tv2014/test2014
if ~exist(work_dir,'dir')
	mkdir(work_dir);
	fileattrib(work_dir,'+w','a');
end

database.db_frame_dir = fullfile(root_dir, 'keyframe-5', data_name, test_pat); % keyframe-5/tv2014/test2014
database.query_dir = fullfile(root_dir, 'keyframe-5', data_name, query_pat); % keyframe-5/tv2014/query2014
database.query_mask_dir = fullfile(database.query_dir); % already put all in one dir

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
if database.comp_sim.query_knn>1 && database.comp_sim.query_delta_sqr~=-1
	if ~isempty(strfind(database.comp_sim.feat_desc, 'root'))
		database.comp_sim.query_delta_sqr=database.comp_sim.query_delta_sqr/5e5;
	elseif ~isempty(strfind(database.comp_sim.feat_desc, 'color'))
		database.comp_sim.query_delta_sqr=database.comp_sim.query_delta_sqr*2;
	elseif ~isempty(strfind(database.comp_sim.feat_desc, 'mom'))
		database.comp_sim.query_delta_sqr=database.comp_sim.query_delta_sqr/1e3;
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

test_feature_dir = fullfile(root_dir, 'feature/keyframe-5', data_name, test_pat);
database.db_mat_dir = fullfile(test_feature_dir,[feature_name '_mat']); % XXX

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
database.cluster_dir = fullfile(test_feature_dir,[feature_name,'_cluster'],clustering_name);
assert(exist(database.cluster_dir,'dir') == 7);
cluster_filename = dir(fullfile(database.cluster_dir,sprintf('Clustering_l2_%d_%d*.hdf5',database.comp_sim.K,database.comp_sim.num_samps)));
assert(length(cluster_filename) == 1);
database.cluster_filename = cluster_filename(1).name;
database.build_dir = fullfile(database.cluster_dir,build_name);
database.bow_dir = fullfile(database.build_dir,db_quantize_name);

% query_frame_dir
query_feature_dir = fullfile(root_dir, 'feature/keyframe-5', data_name, query_pat);
database.query_frame_dir = fullfile(database.query_dir); % already put all in one dir
database.query_feat_dir = fullfile(query_feature_dir, ['raw.'  strrep(feature_name,'rootsift','sift')]); % raw feature

% query_quant_dirname: place contains bow of query
query_quant_dirname = sprintf('db_%s_qr_%s_%s_%s_%s', quantize_name, query_feature_name,clustering_name,build_name,query_quantize_name);
database.query_bow_dir = fullfile(query_feature_dir,['bow.' query_quant_dirname]); % bow feature
if ~exist(database.query_bow_dir,'dir')
	mkdir(database.query_bow_dir);
	fileattrib(database.query_bow_dir,'+w','a');
end

%dist_name
dist_name = database.comp_sim.dist;

% res_name
[cfg_path, cfg_name, cfg_ext] = fileparts(config_file);
res_name = sprintf('%s_%s_%s_%s_%s_%s_%s_%s_%s_%s_%s',run_prefix,cfg_name,query_feature_name,clustering_name,...
	build_name,db_quantize_name,db_agg_name,bow_making_name,query_quantize_name,query_agg_name,dist_name);
	
result_dir = fullfile(work_dir,res_name); % result/tv2014/test2014/runID (runID = res_name)	
if ~exist(result_dir,'dir')
	mkdir(result_dir);
	fileattrib(result_dir,'+w','a');
end

knn_txt_dir = fullfile(result_dir, 'txt'); % result/tv2014/test2014/runID/txt (runID = res_name)
if ~exist(knn_txt_dir,'dir')
	mkdir(knn_txt_dir);
	fileattrib(knn_txt_dir,'+w','a');
end

database.query_mat_dir	= fullfile(result_dir, 'mat'); %runID/mat: store tmp .mat file
if ~exist(database.query_mat_dir,'dir')
    mkdir(database.query_mat_dir);
	fileattrib(database.query_mat_dir,'+w','a');	
end

res_filename = fullfile(database.query_mat_dir,['res_' res_name, '.mat']);

% Load codebook file
clustering_file = fullfile(database.cluster_dir, database.cluster_filename);
if ~exist('prev_clustering_file','var') || ~strcmp(clustering_file,prev_clustering_file)
	if ~exist(clustering_file,'file')
		fprintf('centroid file (%s) doesnot exist!\n',clustering_file)
		return;
	end
	%time('centers=hdf5read(clustering_file,''/clusters'');');
	disp('Loading codebook...')
	centers=hdf5read(clustering_file,'/clusters');
	[feat_len,hist_len]=size(centers);
	prev_clustering_file = clustering_file;
end
% database bow_file
if strcmp(db_agg_name,'avg_pooling')
	bow_file = fullfile(database.bow_dir,sprintf('bow_clip_%s_%s.mat', bow_making_name, db_agg_name));
end
if ~exist('prev_bow_file','var') || ~strcmp(bow_file,prev_bow_file)
	if exist(bow_file,'file') && ~clobber
		%time('load(bow_file)','Loading weighted and normalized database bow file...');
		disp('Loading weighted and normalized database bow file...')
		load(bow_file);
	else
		%% Compute database bow at the beginning
		% Load raw bag of word of the database
		raw_bow_file = fullfile(database.bow_dir,[database.comp_sim.db_agg, '_raw_bow.mat']);
		raw_bow_file = fullfile(database.bow_dir,'raw_bow.mat');
		if ~exist(raw_bow_file,'file')
			fprintf('raw bow file (%s) doesnot exist!\n',raw_bow_file)
			return;
		end;
		%time('load(raw_bow_file);','Loading raw database frame bow...');
		disp('Loading raw database frame bow...')
		load(raw_bow_file);
		
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
		%time('load(big_bow_info_file);','load raw bow info ...');
		disp('Loading raw bow info ...')
		load(big_bow_info_file);
		
		% computing tf 
		term_freq = list_term_freq.(database.comp_sim.freq);
		db_lut=list_id2clip_lut;
		%% CLEAR redundant data
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
		%time('save(bow_file,''db_bow'',''db_lut'',''weight'',''clip_frame_num'',''-v7.3'')',...
		%	 'saving weighted and normalized database bow file ...');
		disp('Saving weighted and normalized database bow file ...')
		save(bow_file,'db_bow','db_lut','weight','clip_frame_num','-v7.3');
	end
	% Build inverted file
	ivf = [];
	if ~isempty(strfind(database.comp_sim.dist,'ivf'))
		%time('ivf = BuildInvFile([],db_bow,0,false);','Building inverted file...');
		disp('Building inverted file...')
		ivf = BuildInvFile([],db_bow,0,false);
		db_bow = [];
	end
	prev_bow_file = bow_file;
end
