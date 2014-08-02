% query bow file
query_bow_file = fullfile(database.query_bow_dir,['bow_' query_bow_making_name, '.mat']);
if exist(query_bow_file,'file') && ~clobber
	time('load(query_bow_file)','load weighted and normalized query bow file ...');
else
	% raw query bow
	query_raw_bow_file = fullfile(database.query_bow_dir,'raw_bow.mat');
	if exist(query_raw_bow_file,'file') && ~clobber
		time('load(query_raw_bow_file)','load raw query bow file ...');
	else
		if strcmp(database.comp_sim.build_params.algorithm,'kdtree')
			kdtree_filename = fullfile(database.build_dir,'flann_kdtree.bin');
			kdsearch_filename = fullfile(database.build_dir,'flann_kdtree_search.mat');
			assert(exist(kdtree_filename,'file')~=0);
			disp('Loading kdtree ...');
			kdtree = flann_load_index(kdtree_filename,single(centers));
			load(kdsearch_filename);
			search_params.cores = database.comp_sim.build_params.cores;
		end

		query_dir = dir(database.query_frame_dir);
		query_dir = {query_dir(:).name};
		valid_ids = cellfun(@(x) ~strcmp(x(1),'.'), query_dir,'UniformOutput',false);
		query_dir = query_dir(cell2mat(valid_ids));
		query_num = length(query_dir);
		topic_bows = cell(1, query_num);
		frame_quant_info = cell(1, query_num);
		query_filenames = cell(1,query_num);
		disp('extract query feature and quantization...');
		for qid = 1:query_num
			tic;
			fprintf('\r%2d(1-%d) ',qid,query_num);
			query_pathname = fullfile(database.query_frame_dir,query_dir{qid});
			query_imgs = dir([query_pathname, '/*.png']);
			query_imgs = {query_imgs(:).name};
			query_filenames{qid} = cellfun(@(x) fullfile(query_pathname,x), query_imgs,'UniformOutput',false);
			num_query_imgs = length(query_imgs);
			if num_query_imgs == 0
				error('query images do not exist in %s!\n',query_pathname);
			end;
	
			sift_dir = fullfile(database.query_feat_dir,query_dir{qid});
			if ~exist(sift_dir,'dir')
				mkdir(sift_dir);
			end
			
			%topic_bows{qid}=zeros(length(vocab_range),num_query_imgs);
			topic_bows{qid}=zeros(hist_len,num_query_imgs);
			frame_quant_info{qid}.fg_index = cell(1, num_query_imgs);
			frame_quant_info{qid}.bg_index = cell(1, num_query_imgs);
			frame_quant_info{qid}.query_kp = cell(1, num_query_imgs);
			frame_quant_info{qid}.query_desc = cell(1, num_query_imgs);
			frame_quant_info{qid}.valid_bins = cell(1, num_query_imgs);
			frame_quant_info{qid}.valid_sqrdists = cell(1, num_query_imgs);
	
			% Tiep: total fg+bg feats
			total_fg_feat = 0;
			total_bg_feat = 0;
			for i=1:num_query_imgs
				clear desc kp
				% query sift extraction
				%disp('query sift extraction ...');
				if ~isempty(strfind(feature_name, 'perdoch'))
					[kp,desc] = mxhesaff(query_filenames{qid}{i},~isempty(strfind(feature_name,'root')),false);
				else
					query_sift_filename=fullfile(sift_dir,strrep(query_imgs{i},'png','txt'));
					if ~isempty(strfind(strrep(feature_config,'root',''),'-sift'))
						if ~exist(query_sift_filename,'file')
							exe = '/net/per610a/export/das11f/ledduy/plsang/nvtiep/INS/code/funcs/compute_descriptors_64bit.ln';
							unix(sprintf('%s %s -i %s -o1 %s>/dev/null 2>&1', exe,...
							strrep(feature_config,'rootsift','sift'), query_filenames{qid}{i}, query_sift_filename));
							if ~exist(query_sift_filename,'file')
								%delete(query_filenames{qid}{i});
								fprintf('query has no sift been detected in query image %s!\n',...
								fullfile(query_pathname, query_imgs{i}));
								continue;
							end
						end
						[kp,desc] = vl_ubcread(query_sift_filename, 'format', 'oxford');
					else
						if ~exist(query_sift_filename,'file')
							if ~isempty(strfind(strrep(feature_config,'root',''),'-sc'))
								feature_id = 'sc';
								exe = './funcs/compute_descriptors.ln';
							elseif ~isempty(strfind(strrep(feature_config,'root',''),'-mom'))
								feature_id = 'mom';
								exe = './funcs/compute_descriptors_linux64';
							end
							pt_file = strrep(query_sift_filename,feature_id,'sift');
							unix(sprintf('%s -p1 %s %s -i %s -o1 %s>/dev/null 2>&1', exe, pt_file, ...
							strrep(feature_desc,'rootsift','sift'), query_filenames{qid}{i}, query_sift_filename));
							if ~exist(query_sift_filename,'file')
								%delete(query_filenames{qid}{i});
								fprintf('query has no %s been detected in query image %s!\n',...
								feature_id, fullfile(query_pathname, query_imgs{i}));
								continue;
							end
						end
						[kp,desc] = ubcread_float(query_sift_filename);
					end
					
					if ~isempty(strfind(feature_name,'rootsift'))
						root_sift = zeros(feat_len,size(desc,2));
						for k = 1:size(desc,2)
							sift = double(desc(1:feat_len,k));
							root_sift(:,k) = sift ./ norm(sift,1);
						end
						desc = sqrt(root_sift);
					end
				end
				
				if ~strcmp(database.comp_sim.query_obj,'crop_fg')  %'crop_fg' is a obsolete option
					query_mask_filename=fullfile(database.query_mask_dir,strrep(query_imgs{i},'src','mask'));
					if exist(query_mask_filename,'file')
						mask = imread(query_mask_filename);
						mask = mask(:,:,1)>128;
						SE = strel('square', 5);
						mask=imdilate(mask,SE);
						% Tiep: blur fg and bg
						%G = fspecial('gaussian',[31 31],2);
						%mask = rgb2gray(mask);
						%mask_blur = imfilter(mask,G,'same');
						xy = floor(kp(1:2,:));
						fg_index=find(mask(sub2ind(size(mask),xy(2,:),xy(1,:))));
						%fg_weis =mask_blur(xy(2,fg_index), xy(1,fg_index));
						bg_index=find(mask(sub2ind(size(mask),xy(2,:),xy(1,:)))==0);
						total_fg_feat = total_fg_feat+length(fg_index);
						total_bg_feat = total_bg_feat+length(bg_index);
					else
						disp('Mask files doesnot exist, please check!');
					end
				end
				
				if strcmp(database.comp_sim.query_obj,'fg_bg') ||...
						strcmp(database.comp_sim.query_obj,'crop_fg') ||...
						~isempty(strfind(database.comp_sim.query_obj,'fg+bg')) ||...
						~exist('mask','var')
					query_kp=kp;
					query_desc=desc;
				elseif strcmp(database.comp_sim.query_obj,'fg')
					query_kp=kp(:,fg_index);
					query_desc=desc(:,fg_index);
				elseif strcmp(database.comp_sim.query_obj,'bg')
					query_kp=kp(:,bg_index);
					query_desc=desc(:,bg_index);
				end        
				
				% quantize query sift
				% disp('quantize query sift ...');
				if strcmp(database.comp_sim.build_params.algorithm,'kdtree')
					[bins,dist_sqr] = flann_search(kdtree,single(query_desc),database.comp_sim.query_knn, search_params);
					% fg+bg_0.1: weight 0.1 for background and 1 for foreground
					bg_index=find(mask(sub2ind(size(mask),xy(2,:),xy(1,:)))==0);
					pos=strfind(database.comp_sim.query_obj,'prop');
					if isempty(pos)
						fgbg_base=1;
					else
						fgbg_base=length(fg_index)/length(bg_index);
					end
					pos=strfind(database.comp_sim.query_obj,'_');
					if isempty(pos(end))
						fgbg_rate=1;
					else
						fgbg_rate=str2double(database.comp_sim.query_obj(pos(end)+1:end));
					end
					re_bins = reshape(bins(:,fg_index),1,[]);
					if database.comp_sim.query_delta_sqr ~= -1 
						weis = exp(-dist_sqr(:,fg_index)./(2*database.comp_sim.query_delta_sqr));
						weis = weis./repmat(sum(weis,1),size(weis,1),1);  % philbin, Lost in Quantization
						weis = reshape(weis,1,[]);
					else
						weis = 1;
					end
					% soft assignment on fg region 
					topic_bows{qid}(:,i) = vl_binsum(topic_bows{qid}(:,i),double(weis),double(re_bins));

					pos=strfind(database.comp_sim.query_obj,'bgsoft');
					if isempty(pos)
						weis = fgbg_rate*fgbg_base;
						% hard assignment on bg region
						topic_bows{qid}(:,i) = vl_binsum(topic_bows{qid}(:,i),double(weis),double(bins(1,bg_index)));
					else
						re_bins = reshape(bins(:,bg_index),1,[]);
						if database.comp_sim.query_knn>1 && database.comp_sim.query_delta_sqr ~= -1
							weis = exp(-dist_sqr(:,bg_index)./(2*database.comp_sim.query_delta_sqr));
							weis = weis./repmat(sum(weis,1),size(weis,1),1);  % philbin, Lost in Quantization
							weis = reshape(weis,1,[]);
						else
							weis = 1;
						end
						weis = weis*fgbg_rate*fgbg_base;
						% hard assignment on bg region
						topic_bows{qid}(:,i) = vl_binsum(topic_bows{qid}(:,i),double(weis),double(re_bins));
					end

					frame_quant_info{qid}.fg_index{i} = fg_index;
					frame_quant_info{qid}.bg_index{i} = bg_index;
					frame_quant_info{qid}.query_kp{i} = query_kp;
					frame_quant_info{qid}.query_desc{i} = query_desc;
					frame_quant_info{qid}.valid_bins{i} = bins;
					frame_quant_info{qid}.valid_sqrdists{i} = dist_sqr;
				end
			end
			% Tiep: save #feature of fr and bg
			nfeat_filename=fullfile(database.query_feat_dir,'num.feat.txt');
			fid = fopen(nfeat_filename, 'a');
			fprintf(fid,'%s %d %d\n', query_dir{qid}, total_fg_feat, total_bg_feat);
			fclose(fid);
			
			topic_bows{qid} = sparse(topic_bows{qid});
			fprintf(' %.0f',toc);
		end
		fprintf('\n');
		time('save(query_raw_bow_file,''topic_bows'',''frame_quant_info'',''query_filenames'',''-v7.3'')',...
			'save raw query bow file ...');
	end

	disp('Normalizing raw query bow ...');
	query_num = length(query_filenames);
	for qid = 1:query_num
		tic;
		fprintf('\r%d(1-%d)',qid,query_num);
		nn_set = 1:size(topic_bows{qid},2);
		kk = database.comp_sim.query_num;
		if kk==-1
			kk=10000;
		end
		if length(nn_set)>kk
			query_subsets = nchoosek(nn_set,kk);
		else
			query_subsets = nn_set;
		end
		new_quant_info.fg_index=cell(1,size(query_subsets,1));
		new_quant_info.query_kp=cell(1,size(query_subsets,1));
		new_quant_info.query_desc=cell(1,size(query_subsets,1));
		new_quant_info.quant_bins=cell(1,size(query_subsets,1));
		new_topic_bow = cell(1,size(query_subsets,1));
		new_query_comb = cell(1,size(query_subsets,1));
		query_feat_corr_bridges{qid} = cell(1,size(query_subsets,1));
		for i=1:size(query_subsets,1)
			new_query_comb{i} = query_filenames{qid}(query_subsets(i,:));
			query_bows = topic_bows{qid}(:,query_subsets(i,:));
			new_quant_info.fg_index{i} = frame_quant_info{qid}.fg_index(query_subsets(i,:));
			new_quant_info.quant_bins{i} = frame_quant_info{qid}.valid_bins(query_subsets(i,:));
			new_quant_info.query_kp{i} = frame_quant_info{qid}.query_kp(query_subsets(i,:));
			new_quant_info.query_desc{i} = frame_quant_info{qid}.query_desc(query_subsets(i,:));
			if ~isempty(strfind(database.comp_sim.query_agg,'auto_'))
				pos = strfind(database.comp_sim.query_agg,'_');
				com_word_thre = str2double(database.comp_sim.query_agg(pos(end-1)+1:pos(end)-1));   
				com_word_add_wei = str2double(database.comp_sim.query_agg(pos(end)+1:end));                             
				com_word_num = get_com_word_num(query_bows);
				adj_mat = com_word_num>=com_word_thre | (eye(size(com_word_num))>0);
				com_words = get_com_word_id(query_bows);
				com_word_add_weis = zeros(hist_len,1);
				for m=1:length(com_words)
					for n=m+1:length(com_words)
						if adj_mat(m,n)
							adj_mat(:,m) = adj_mat(:,m) | adj_mat(:,n);
							adj_mat(:,n) = adj_mat(:,m) | adj_mat(:,n);
						end
						lia_m=ismember(com_words{m,n}, new_quant_info.quant_bins{i}{m}(:,new_quant_info.fg_index{i}{m}));
						lia_n=ismember(com_words{m,n}, new_quant_info.quant_bins{i}{n}(:,new_quant_info.fg_index{i}{n}));
						com_word_add_weis(com_words{m,n}(lia_m & lia_n)) = com_word_add_weis(com_words{m,n}(lia_m & lia_n))+com_word_add_wei;
					end
				end   
				if com_word_add_wei ~= 0
					query_bows = query_bows+query_bows.*repmat(com_word_add_weis,1,size(query_bows,2));
				end
				% for this moment only avg_pooling
				full_adj_mat = unique(adj_mat,'rows');  
				new_query_bows = zeros(size(query_bows,1),size(full_adj_mat,1));
				for j = 1:size(full_adj_mat,1)
					new_query_bows(:,j) = mean(query_bows(:,full_adj_mat(j,:)),2);
				end
				query_bows = new_query_bows;  % now the query_bows is shrinked and not concide with query_filenames, may cause problems afterwards.
			end

			if database.comp_sim.query_num ~= 1
				if ~isempty(strfind(database.comp_sim.query_agg,'max_pooling'))
					query_bows = max(query_bows,[],2);
				elseif ~isempty(strfind(database.comp_sim.query_agg,'avg_pooling'))
					query_bows = mean(query_bows,2);
				end
			end
			new_topic_bow{i} = zeros(size(topic_bows{qid},1),size(query_bows,2));
			for k=1:size(query_bows,2)
				% trim bow
				query_bow = trim_bow(query_bows(:,k),database.comp_sim.trim);

				% apply weighting
				query_bow = wei_bow(query_bow,weight);

				% normalize bow
				query_bow = norm_bow(query_bow,database.comp_sim.norm,...
					length(query_filenames{qid}));
				new_topic_bow{i}(:,k) = query_bow;
			end
			new_topic_bow{i}=sparse(new_topic_bow{i});
		end
		topic_bows{qid} = new_topic_bow;
		query_filenames{qid} = new_query_comb;
		frame_quant_info{qid} = new_quant_info;
		fprintf(' %.0f',toc);
	end
	fprintf('\n');
	if isfield(database.comp_sim, 'query_feat_corr') && database.comp_sim.query_feat_corr.bridge_vq
		time('save(query_bow_file,''topic_bows'',''frame_quant_info'',''query_filenames'',''query_feat_corr_bridges'',''-v7.3'')',...
			'save normalized query bow ...');
	else
		time('save(query_bow_file,''topic_bows'',''frame_quant_info'',''query_filenames'',''-v7.3'')',...
			'save normalized query bow ...');
	end
end