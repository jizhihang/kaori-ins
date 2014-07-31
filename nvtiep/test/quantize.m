%function quantize(run_id,num_multi_run,INS)
%if nargin<3
%    INS = 'ins2011';
%end
%if nargin<2
%    num_multi_run = 1;
%end
%if nargin<1
%    run_id = 1;
%end
clc
INS = 'ins2013';
num_multi_run = 1;
run_id = 1;
%% set path
version='vlfeat-0.9.13-sse2-datacontrol-chi-randbugfixedfixed';
run(sprintf('/net/per610a/export/das11g/caizhizhu/common/funcs/%s/toolbox/vl_setup',version));
addpath(genpath('/net/per610a/export/das11g/caizhizhu/common/funcs'));

%% parameter setting
build_quant = false;
build_bow = false;
create_big_bow = true;
frame_sampling = 1;  %1-using all frames per video (best performance), n-using 1/n frames per video
video_sampling = 50;  %1-using all videos, n-sampling every n video as the database  NOTE: only using for parameter tuning to save time
save_raw_bow = true;
save_max_avg_bow = false;
idf_l1_norm = false;
feature_detr = '-hesaff';
feature_desc = '-rootsift -noangle';
feature_config = sprintf('%s %s',feature_detr,feature_desc);
feature_name = strrep(feature_config, '-', '');
feature_name = strrep(feature_name, ' ', '_');
quant_struct = struct('quantize','kdtree','build_params',struct('algorithm', 'kdtree','trees', 8, 'checks', 800, 'cores', 15),'knn',3,'delta_sqr',6250);%'kdtree';  % 'hiertree',6250:
%quant_struct = struct('quantize','hiertree');%'kdtree';  % 'hiertree'
if quant_struct.knn>1 && quant_struct.delta_sqr~=-1
    if ~isempty(strfind(feature_name, 'root'))
        quant_struct.delta_sqr=quant_struct.delta_sqr/5e5;
    elseif ~isempty(strfind(feature_name, 'color'))
        quant_struct.delta_sqr=quant_struct.delta_sqr*2;
    end
end

quantize_name = sprintf('v%d_f%d_%d', video_sampling,frame_sampling,quant_struct.knn);
bow_name = quantize_name;
if ~strcmp(quant_struct.quantize,'kdtree') 
    build_name = sprintf('%s',quant_struct.quantize); 
else
   % build_name = sprintf('%s_%d_%.1f',quant_struct.build_params.algorithm,quant_struct.knn,...
   %     quant_struct.build_params.target_precision); 
    build_name = sprintf('%s_%d_%d',quant_struct.build_params.algorithm,quant_struct.build_params.trees,quant_struct.build_params.checks); 
    if quant_struct.knn>1
        bow_name = sprintf('%s_%g', quantize_name,quant_struct.delta_sqr);   
    end
end
quantize_name = sprintf('%s_sub_quant', quantize_name);

% directory setup
work_dir = fullfile('/home/caizhizhu/per610a/ins',INS);
database.feat_mat_dir = sprintf('%s/%s_mat',work_dir,feature_name);
%database.feat_mat_dir = sprintf('%s/%s_mat',work_dir,strrep(feature_name,'_sift','_sift_color'));
database.cluster_dir = sprintf('%s/%s_cluster/akmeans_1000000_100000000_50/',work_dir,feature_name);
%database.cluster_dir = sprintf('%s/%s_cluster/akmeans_1000000_100000000_50/',work_dir, strrep(feature_name,'_color',''));
%database.cluster_dir = sprintf('%s/%s_cluster/hikmeans_100_1000000_100000000_100/',work_dir,feature_name);
database.build_dir = fullfile(database.cluster_dir,build_name);
database.bow_dir = fullfile(database.build_dir,bow_name);
database.subbow_dir = fullfile(database.bow_dir,'sub_bow');
if ~exist(database.subbow_dir,'dir'),
%    mkdir(database.subbow_dir);
end

% check BIG existing quant results
if false%num_multi_run == 1 
    quant_dirnames = dir(fullfile(database.build_dir, '*_sub_quant'));
    quant_dirnames = {quant_dirnames(:).name};
    for i=1:length(quant_dirnames)
        quant_dirname = quant_dirnames{i};
        pos = strfind(quant_dirname,'_');
        v_val = str2double(quant_dirname(2:pos(1)-1));
        f_val = str2double(quant_dirname(pos(1)+2:pos(2)-1));
        k_val = str2double(quant_dirname(pos(2)+1:pos(3)-1));
        if (v_val==video_sampling || v_val==1) && (f_val==frame_sampling || f_val == 1) && k_val>=quant_struct.knn
            fprintf('found quant file %s(>%s)',quant_dirname, quantize_name);
            build_quant = false;
            break;
        end
    end
else
    if strcmp(INS, 'ins2013')
        quant_dirname = 'v1_f1_10_sub_quant';
    else
        quant_dirname = 'v1_f1_3_sub_quant';
        quant_dirname = 'v1_f1_10_sub_quant';
    end
end

if build_quant
    database.quant_dir = fullfile(database.build_dir,quantize_name);
    if ~exist(database.quant_dir,'dir'),
        mkdir(database.quant_dir);
    end
else
    database.quant_dir = fullfile(database.build_dir, quant_dirname);
end
database.log_filename = sprintf('%s/log/quantize_%s_%s_%s.log',work_dir,feature_name,build_name,quantize_name); %just to record which runs have been finished.

if false
    clip_feat_mat = dir([database.feat_mat_dir '/*.mat']);
    clip_feat_mat = {clip_feat_mat(:).name};
    
    % sort number id in ascending order
    clip_ids = cellfun(@(x) str2double(x(1:end-4)), clip_feat_mat, 'UniformOutput',false);
    [~,idx] = sort(cell2mat(clip_ids));
    clip_feat_mat = clip_feat_mat(idx);
    %unfinished_id = cellfun(@(x) ~exist(fullfile(mat_dir,sprintf('%s.mat', x))), clip_feat_mat, 'UniformOutput', false);
    %clip_feat_mat = clip_feat_mat(cell2mat(unfinished_id));
else
    switch INS
    case 'ins2011'
        min_clip_id = 1;
        max_clip_id = 20982;
        clip_feat_mat = cell(max_clip_id-min_clip_id+1,1);
        for i=min_clip_id:max_clip_id
            clip_feat_mat{i} = sprintf('%d',i);
        end
        gt_filename = fullfile(work_dir,'tv11.ins.truth');
    case 'ins2012'
        min_clip_id = 1;
        max_clip_id = 76751;
        clip_feat_mat = cell(max_clip_id-min_clip_id+1,1);
        for i=min_clip_id:max_clip_id
            clip_feat_mat{i} = sprintf('FL%09d',i);
        end
        gt_filename = fullfile(work_dir,'ins.search.qrels.tv12.revised');
    case 'ins2013'
        clip_list = fullfile(work_dir,'active/bbc.eastenders.master.shot.reference/eastenders.masterShotReferenceTable');
        fclip = fopen(clip_list);
        clip_dir = textscan(fclip, '%*d %s %*s %*s');
        fclose(fclip);
        clip_feat_mat = clip_dir{1}; 

        %filter out those sample video
        test_ids = cellfun(@(x) isempty(strfind(x, 'shot0_')), clip_feat_mat, 'UniformOutput', false);
        clip_feat_mat = clip_feat_mat(cell2mat(test_ids));
        gt_filename = fullfile(work_dir,'ins.search.qrels.tv13');
    otherwise
        error(['bad INS dataset: ' INS]);
    end
end

num_clip = length(clip_feat_mat);
if video_sampling>1
    fgt = fopen(gt_filename);
    gt_info = textscan(fgt, '%*d %*d %s %d');
    fclose(fgt);
    rel_clip_names = unique(gt_info{1}(logical(gt_info{2})));
    rel_num = length(rel_clip_names);
    desire_num = round(num_clip/video_sampling);
    if rel_num>desire_num
        clip_feat_mat = rel_clip_names;
    else
        irrel_clip_names = setdiff(clip_feat_mat,rel_clip_names);
        clip_feat_mat = sort([rel_clip_names;irrel_clip_names(1:desire_num-rel_num)]);
    end
    num_clip = length(clip_feat_mat);
end
clip_feat_mat = cellfun(@(x) sprintf('%s.mat',x), clip_feat_mat, 'UniformOutput',false);

if strcmp(quant_struct.quantize, 'kdtree') 
    tic;
    disp('load cluster centers file ...');
    cluster_filename = dir(fullfile(database.cluster_dir,'Cluster*.hdf5'));
    assert(length(cluster_filename) == 1);
    database.cluster_filename = cluster_filename(1).name;
    avg_big_bow_file = fullfile(database.bow_dir,'avg_pooling_raw_bow.mat');
    max_big_bow_file = fullfile(database.bow_dir,'max_pooling_raw_bow.mat');
    raw_bow_file = fullfile(database.bow_dir,'raw_bow.mat');
    big_bow_info_file = fullfile(database.bow_dir,'raw_bow_info.mat');
    if exist(big_bow_info_file,'file')~=0
        disp('big bow files exist');
        %return;
    end
    centers = hdf5read(fullfile(database.cluster_dir, database.cluster_filename),'/clusters'); 
    dataset = single(centers);
    [feat_len,hist_len] = size(dataset);
    fprintf('Deduced cluster center info %d %d...\n', feat_len, hist_len);
    % treat all_zero centers as invalid,just neglect them while building kdtree

elseif strcmp(quant_struct.quantize, 'hiertree')
   if K>1000
       fprintf('WARNING: using hiertree while K=%d could be very slow!',K);
    end
    cluster_filename = dir(fullfile(database.cluster_dir,'Cluster*.mat'));
    assert(length(cluster_filename) == 1);
    database.cluster_filename = cluster_filename(1).name;
    big_bow_file = fullfile(database.bow_dir,'raw_bow.mat');
    if exist(big_bow_file,'file')==2,
        return;
    end
    
    disp('load built kdtree file ...');
    load(fullfile(database.cluster_dir, database.cluster_filename),'tree');

    % parse directory name to deduce parameters
    D=tree.depth;
    K = tree.K;
    nleaves=K^D;
    if D>0
        hist_len = (K^(D+1) - 1) / (K - 1);
    else
        hist_len = size(tree.centers,2);
    end
    feat_len=size(tree.centers, 1);

    fprintf('Deduced tree info %d %d %d %d %d...\n', K, D, nleaves, feat_len, hist_len);
end
database

run_scope = linspace(1,num_clip,num_multi_run+1);
%j = 1;  
%if num_multi_run>1
%    if false
%        if num_multi_run > 1
%            for j=1:length(run_scope) 
%                i = floor(run_scope(j));        
%                quant_file = fullfile(database.quant_dir,clip_feat_mat{i});
%                if ~exist(quant_file, 'file')
%                    break;
%                end
%            end
%            if j>length(run_scope)
%                return;
%            end
%        end     
%    else
%        j = input(sprintf('Input slot id (1~%d):\t',num_multi_run));
%        assert(j>=1 && j<=num_multi_run);
%    end
%end
quantizing_time = 0;
sid = floor(run_scope(run_id));
eid = floor(run_scope(min(run_id+1,end)));
if run_id > 1
    sid = sid + 1;
end

if ~isempty(strfind(quant_struct.quantize, 'kdtree')) && build_quant
    kdtree_filename = fullfile(database.build_dir,'flann_kdtree.bin');
    kdsearch_filename = fullfile(database.build_dir,'flann_kdtree_search.mat');
    if exist(kdtree_filename,'file')
        fprintf('load kdtree ...');
        tic;
        kdtree = flann_load_index(kdtree_filename,dataset);
        load(kdsearch_filename);
        search_params.cores = quant_struct.build_params.cores;
        kdtree_time.load = toc;
    else
        tic;
        fprintf('build kdtree ...');
        [kdtree,search_params,speedup] = flann_build_index(dataset,quant_struct.build_params); 
        kdtree_time.speedup = speedup;
        kdtree_time.build = toc;
        fprintf('%.0f \n',kdtree_time.build);
        fprintf('save kdtree ...');
        tic;
        flann_save_index(kdtree,kdtree_filename);
        save(kdsearch_filename,'search_params');
        kdtree_time.save = toc;
    end

    total_time = 0;
    disp('build quant files separately');
    for clip_id = sid:eid
        tic;
        fprintf('\r%d(%d~%d) ', clip_id,sid,eid);
        quant_file = fullfile(database.quant_dir,clip_feat_mat{clip_id});
        if exist(quant_file,'file')==2,
            try
                load(quant_file);
                fprintf('\r%d/%d(%.0fs)  ', clip_id,num_clip,toc);
                continue;        
            catch ME
                unix(['rm ' quant_file]);
            end
        end;
        clip_feat_file = fullfile(database.feat_mat_dir,clip_feat_mat{clip_id});
        if ~exist(clip_feat_file,'file')
            disp([clip_feat_file ' does not exist!']);
            continue;
        end
        %clip_bow = [];
        %save(quant_file, 'clip_bow');  % to occupy a place

        %load feature
        load(clip_feat_file, 'clip_desc');  
        readmat_time = toc;

        %quantize feature
        tic;
        num_frame = length(clip_desc);
        selected_frame_id = 1:frame_sampling:num_frame;
        selected_frame_num = length(selected_frame_id);
        bins = cell(1,num_frame);
        sqrdists = cell(1,num_frame);
        for id = 1:selected_frame_num
            frame_id = selected_frame_id(id);
            if isempty(clip_desc{frame_id})
                continue;
            end
            frame_desc = clip_desc{frame_id}(1:feat_len,:);
            [bins{frame_id},sqrdists{frame_id}] = flann_search(kdtree,single(frame_desc),quant_struct.knn, search_params);
            %fprintf('\r%d(%.0fs)  ', size(frame_desc,2),toc);
        end
        computing_time = toc;
        tic;
        save(quant_file, 'bins','sqrdists');
        savemat_time = toc;
        round_time = readmat_time+computing_time+savemat_time;
        total_time = total_time + round_time;
        rem_time = total_time*(eid-clip_id)/(clip_id-sid)/3600;
        fprintf('rem:%.0fh,elap:%.0fs(readmat:%.3f; compute:%.3f; savemat:%.3f)          ',rem_time,total_time,readmat_time/round_time,computing_time/round_time,savemat_time/round_time);
    end;
    fprintf('\nfinished\n');   
end

if build_bow
    disp('build bow files separately');
    total_time = 0;
    for clip_id = sid:eid
        tic;
        fprintf('\r%d(%d~%d) ', clip_id,sid,eid);
        subbow_file = fullfile(database.subbow_dir,clip_feat_mat{clip_id});
        if exist(subbow_file,'file')==2,
            try
                load(subbow_file);
                fprintf('\r%d/%d(%.0fs)  ', clip_id,num_clip,toc);
                continue;        
            catch ME
                unix(['rm ' subbow_file]);
            end
        end;
        if ~isempty(strfind(quant_struct.quantize, 'kdtree'))
            quant_file = fullfile(database.quant_dir,clip_feat_mat{clip_id});
            if ~exist(quant_file,'file')
                disp([quant_file ' does not exist!']);
                continue;
            end
            %clip_bow = [];
            %save(subbow_file, 'clip_bow');  % to occupy a place

            %load feature
            load(quant_file);  
            readmat_time = toc;

            %quantize feature
            tic
            num_frame = length(bins);
            selected_frame_id = 1:frame_sampling:num_frame;
            selected_frame_num = length(selected_frame_id);
            frame_bow = zeros(hist_len,selected_frame_num);
            if quant_struct.knn>1 && quant_struct.delta_sqr ~= -1 
                frame_freq = zeros(hist_len,selected_frame_num);
            end
            for id = 1:selected_frame_num
                frame_id = selected_frame_id(id);
                if isempty(bins{frame_id})
                    continue;
                end
                bin = reshape(bins{frame_id}(1:quant_struct.knn,:),1,[]);
                if quant_struct.knn>1 && quant_struct.delta_sqr ~= -1 
                    sqrdist = sqrdists{frame_id}(1:quant_struct.knn,:);
                    weis = exp(-sqrdist./(2*quant_struct.delta_sqr));
                    weis = weis./repmat(sum(weis,1),size(weis,1),1);  % philbin, Lost in Quantization
                    weis = reshape(weis,1,[]);
                    frame_freq(:,id) = vl_binsum(frame_freq(:,id),double(ones(size(bin))),double(bin)); 
                else
                    weis = ones(size(bin));
                end
                frame_bow(:,id) = vl_binsum(frame_bow(:,id),double(weis),double(bin)); 
            end 
        else
            clip_feat_file = fullfile(database.feat_mat_dir,clip_feat_mat{clip_id});
            if ~exist(clip_feat_file,'file')
                disp([clip_feat_file ' does not exist!']);
                continue;
            end
            %clip_bow = [];
            %save(subbow_file, 'clip_bow');  % to occupy a place

            %load feature
            load(clip_feat_file, 'clip_desc');  
            readmat_time = toc;

            %quantize feature
            tic;
            num_frame = length(clip_desc);
            selected_frame_id = 1:frame_sampling:num_frame;
            selected_frame_num = length(selected_frame_id);
            frame_bow = zeros(hist_len,selected_frame_num);
            for id = 1:selected_frame_num
                frame_id = selected_frame_id(id);
                frame_desc = clip_desc{frame_id}(1:feat_len,:);
                frame_path = vl_hikmeanspush(tree,frame_desc); 
                frame_bow(:,id) = vl_hikmeanshist(tree,frame_path);
            end
        end
        frame_bow = sparse(frame_bow);
        computing_time = toc;
        tic;
        if ~exist('frame_freq','var')
            save(subbow_file, 'frame_bow');
        else
            frame_freq = sparse(frame_freq);
            save(subbow_file, 'frame_bow','frame_freq');
        end
        savemat_time = toc;
        round_time = readmat_time+computing_time+savemat_time;
        total_time = total_time + round_time;
        rem_time = total_time*(eid-clip_id)/(clip_id-sid)/3600;
        fprintf('rem:%.0fh,elap:%.0fs(readmat:%.3f; compute:%.3f; savemat:%.3f)            ',rem_time,total_time,readmat_time/round_time,computing_time/round_time,savemat_time/round_time);
    end;
    fprintf('\n');    
    disp('finished');

    if num_multi_run > 1
        flog = fopen(database.log_filename,'a+');
        fprintf(flog,'%d\n',run_id);
        frewind(flog);
        finished_ids = textscan(flog,'%d');
        if ~isempty(find(finished_ids{1}==-1))  
            disp('Some predecessor is merging, I finish my part and quit.');
            fclose(flog);
            exit;
        else
            if ~isempty(find(ismember(1:num_multi_run,finished_ids{1})==0,1))  % some runs are not finished, then quit to avoid conflict and memory consumption.
                disp('Leave the successor to merge, I finish my part and quit.');
                fclose(flog);
                exit;
            end
            fseek(flog, 0, 'eof');
            fprintf(flog,'%d\n',-1);
            fclose(flog);
            if exist(avg_big_bow_file,'file')==2
                disp('Wierd: I just finished my part, why the big one exist?, I quit and you need to check.');
                exit;
            end
            %dummy = [];
            %save(big_bow_file, 'dummy');  % to occupy a place
        end
    end
end

if create_big_bow
    if exist(big_bow_info_file)
        disp('big bow files exist!');
        %return
    end
    disp('load bow files into a big one');
    list_term_freq=struct('occu',zeros(hist_len+1,1),'frame',zeros(hist_len+1,1),'clip',zeros(hist_len+1,1));
    list_clip_frame_num = zeros(num_clip,1);
    list_id2clip_lut = cellfun(@(x) x(1:end-4), clip_feat_mat, 'UniformOutput',false);
    if save_max_avg_bow
        clear list_max_pooling_bow
        list_max_pooling_bow = sparse(hist_len,eid-sid+1);
        clear list_avg_pooling_bow
        list_avg_pooling_bow = sparse(hist_len,eid-sid+1);
    end
    if save_raw_bow
        clear list_frame_bow
        list_frame_bow = cell(1,eid-sid+1);
    end
    total_time = 0;
    sid = 1;
    eid = num_clip;
    for clip_id = sid:eid,
        tic;
        clip_feat_file = fullfile(database.feat_mat_dir,clip_feat_mat{clip_id});
        if ~exist(clip_feat_file,'file')
            if save_raw_bow
                list_frame_bow{clip_id} = sparse(hist_len,1);
            end
            continue;
        end
        subbow_file = fullfile(database.subbow_dir,clip_feat_mat{clip_id});
        load (subbow_file);
        load_time = toc;
        tic;
        num_frame = size(frame_bow,2);
        if ~exist('frame_freq','var')
            clip_freq = sum(frame_bow,2);
        else
            clip_freq = sum(frame_freq,2);
        end
        if save_max_avg_bow
            list_max_pooling_bow(:,clip_id-sid+1) = max(frame_bow, [], 2);
            if ~exist('frame_freq','var')
                list_avg_pooling_bow(:,clip_id-sid+1)=clip_freq./num_frame;
            else
                list_avg_pooling_bow(:,clip_id-sid+1)=mean(frame_bow,2);
            end
        end
        if save_raw_bow
            list_frame_bow{clip_id-sid+1} = frame_bow;
        end
        list_clip_frame_num(clip_id) = num_frame;
        list_term_freq.occu = list_term_freq.occu+[clip_freq;sum(clip_freq)];
        list_term_freq.clip = list_term_freq.clip+[double(clip_freq>0);1];
        list_term_freq.frame = list_term_freq.frame+[sum(frame_bow>0,2);size(frame_bow,2)];
        compute_time = toc;
        round_time = load_time + compute_time;
        total_time = total_time + round_time;
        rem_time = total_time*(eid-clip_id)/(clip_id-sid)/3600;
        fprintf('\r%d/(%d~%d), rem:%.0fh,elap:%.0fs(load:%.3f,compute:%.3f)       ', clip_id,sid,eid,rem_time,total_time,load_time/round_time,compute_time/round_time);
    end;
    % make sure your memory can handle
    tic;
    fprintf('\n save big bow info');
    save(big_bow_info_file, 'list_term_freq','list_clip_frame_num','list_id2clip_lut','-v6');   
    fprintf('%.0fs\n', toc);
    if save_raw_bow
        tic;
        fprintf('\n save raw bow');
        save(raw_bow_file, 'list_frame_bow','-v7.3');   
        fprintf('%.0fs\n', toc);
    end
    if save_max_avg_bow
        tic;
        fprintf('\n save big max bow');
        save(max_big_bow_file, 'list_max_pooling_bow','-v7.3');   
        fprintf('%.0fs\n', toc);
        tic;
        fprintf('\n save big avg bow ..');
        save(avg_big_bow_file, 'list_avg_pooling_bow','-v7.3');   
        fprintf('%.0fs\n', toc);
        if idf_l1_norm 
            load(big_bow_info_file);   
            term_freq = list_term_freq.clip;
            weight = get_wei(term_freq,'idf');
            db_bow = list_avg_pooling_bow;
            db_lut=list_id2clip_lut;
            clip_frame_num = list_clip_frame_num;
            clear term_freq list_clip_frame_num list_avg_pooling_bow list_term_freq
            
            wei_nonorm_bow_file = fullfile(database.bow_dir,'bow_clip_full_notrim_clip_idf_nonorm_avg_pooling.mat');
            wei_norm_bow_file = fullfile(database.bow_dir,'bow_clip_full_notrim_clip_idf_l1_avg_pooling.mat');
            fprintf('\n weighting ...');
            tic;
            for i=1:size(db_bow,2)
                db_bow(:,i) = db_bow(:,i).*weight;
            end
            fprintf('%.0fs\n', toc);
            save(wei_nonorm_bow_file,'db_bow','db_lut','weight','clip_frame_num','-v7.3');
            fprintf('\n Normalizing ...');
            tic;
            for i=1:size(db_bow,2)
                bow_norm = sum(db_bow(:,i))+eps;
                db_bow(:,i) = db_bow(:,i)./bow_norm;
            end 
            fprintf('%.0fs\n', toc);
            save(wei_norm_bow_file,'db_bow','db_lut','weight','clip_frame_num','-v7.3');

            clear list_avg_pooling_bow
            db_bow = list_max_pooling_bow;
            
            wei_nonorm_bow_file = fullfile(database.bow_dir,'bow_clip_full_notrim_clip_idf_nonorm_max_pooling.mat');
            wei_norm_bow_file = fullfile(database.bow_dir,'bow_clip_full_notrim_clip_idf_l1_max_pooling.mat');
            for i=1:size(db_bow,2)
                db_bow(:,i) = db_bow(:,i).*weight;
            end
            save(wei_nonorm_bow_file,'db_bow','db_lut','weight','clip_frame_num','-v7.3');
            for i=1:size(db_bow,2)
                bow_norm = sum(db_bow(:,i))+eps;
                db_bow(:,i) = db_bow(:,i)./bow_norm;
            end
            save(wei_norm_bow_file,'db_bow','db_lut','weight','clip_frame_num','-v7.3');
        end
    end
end
disp('finished');
