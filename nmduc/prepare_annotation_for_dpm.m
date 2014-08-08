function prepare_annotation_for_dpm(data_name, query_pat)
% prepare_annotation_for_dpm('tv2013', 'query2013')

% 07 Aug 2014 - adding comments and modify codes for INS2013 experiments
% !!! IMPORTANT !!!
% Input: .src.png - query image and .mask.png - mask image --> located at the same dir (e.g. tv2013/query2013), format .png
% Neg-images are located in keyframe-5/tv2013/query2013-neg-images --> format .jpg

work_dir = fullfile ('/net/per610a/export/das11f/ledduy/trecvid-ins-2014/keyframe-5', data_name, query_pat) ; % tv2013/query2013
neg_img_dir = [work_dir '-neg-images'];
neg_files = dir([neg_img_dir '/*.jpg']);
for i=1:length(neg_files)
	neg_files(i).name = strrep(neg_files(i).name, '.jpg', '');
end

model_dir = fullfile ('/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm', data_name, query_pat);
if ~exist(model_dir,'dir')
	mkdir(model_dir);
	fileattrib(model_dir,'+w','a');
end

% path to query images
src_img_dir = work_dir;

% path to mask images
mask_img_path = src_img_dir; % all .src and .mask file are in the same folder, e.g. 9069

SEPERATE_REGION = 1; % 1: each ROI = 1 bounding box, 0: all ROI = 1 bounding box (merge all ROI into one)
MEAN_ROI = 2.9374e+004; % UNUSED

% to deal with small object
MAX_SCALE_FACTOR = 2.0; % maximum scale --> larger scale requires high computational cost, so should not set larger than 2.0

BASE_DIR = 'VOC2007/Images/'; %???
DATABASE = 'trecvid_query'; % ????

query_folders = dir(src_img_dir);
for i=1:length(query_folders) 
	i
    query_id = query_folders(i).name; % each dir --> 1 query, e.g 9069
    if strcmp(query_id,'.') || strcmp(query_id, '..')
        continue;
    end
	
	% path to output ROI images
	config_file_dir = fullfile(model_dir, query_id);  
	if ~exist(config_file_dir,'dir')
		mkdir(config_file_dir);
		fileattrib(config_file_dir,'+w','a');
	end

	anotation_dir = fullfile(model_dir, query_id, 'Annotations'); 
	if ~exist(anotation_dir,'dir')
		mkdir(anotation_dir);
		fileattrib(anotation_dir,'+w','a');
	end

	output_img_dir = fullfile(model_dir, query_id, 'Images'); 
	if ~exist(output_img_dir,'dir')
		mkdir(output_img_dir);
		fileattrib(output_img_dir,'+w','a');
	end
	
	trainval_dir = fullfile(model_dir, query_id, 'ImageSets');  % dir for pos images
	train_dir = fullfile(model_dir, query_id, 'ImageSets');  % dir for neg images (same as dir for pos images)
	if ~exist(trainval_dir,'dir')
		mkdir(trainval_dir);
		fileattrib(trainval_dir,'+w','a');
	end
	
	query_img_dirz = fullfile(src_img_dir, query_id);
    files = dir(query_img_dirz); % list all files in query dir
    n_discarded = 0;
    query_rects = [];
    query_scales = [];
    query_images = [];
    query_img_names = [];
    valid_imgname = [];
    counter = 0;
    counter2 = 0;


    for j=1:length(files)
        img_path = fullfile(query_img_dirz, files(j).name);
        if strcmp(files(j).name,'.') || strcmp(files(j).name, '..')
            continue;
        end
		
        src_img = imread(img_path); % read image
		if isempty(strfind(img_path, 'src.png'))
			continue; % skip
		end
        mask_img_name = strrep(files(j).name, 'src', 'mask'); % mask.png
        mask = imread(fullfile(query_img_dirz, mask_img_name));
        % extract roi image(s): not write annotation, not separate region
        [rects scale] = validate_mask(src_img, mask, SEPERATE_REGION);
        if isempty(rects) % remove images with small rects
            n_discarded = n_discarded + 1;
        end
        counter = counter + 1;
        query_rects{counter} = rects;
        query_scales(counter) = scale;
        query_images{counter} = src_img;
        query_img_names{counter} = files(j).name; % 9069.src.png
    end
    
    scale = max(query_scales);
    if scale > MAX_SCALE_FACTOR
        scale = MAX_SCALE_FACTOR;
    end
    for j=1:counter
        src_img = query_images{j};
        rects = query_rects{j};
        if isempty(rects)
            continue;
        end
        counter2 = counter2 + 1;
        valid_imgname{counter2} = strrep(query_img_names{j},'.png', ''); % set of images used in training set (some images of orig set will be discarded if having small mask size)
        % resize image if scale factor > 1
        if scale ~= 1
            src_img = imresize(src_img, scale, 'lanczos3'); % this resized image will be saved in dir 9069/Images/xxx.src.png
            for k=1:length(rects)
                rects{k} = rects{k} * scale;
            end
        end
        [HEIGHT WIDTH C] = size(src_img); % get new size (H, W) - used for annotation
        
        % write annotation 
        % print initial info to annotation file
        anno_filename = fullfile(anotation_dir, strrep(query_img_names{j},'png','txt')); % annotation name - replace .png = .txt, eg. 9069/Annotations/xxx.src.txt
        fout = fopen(anno_filename, 'w');
        % basic info
        fprintf(fout, '# PASCAL Annotation Version 1.00\n\n');
        img_name = strrep(query_img_names{j},'png','jpg'); % output image source use .jpg format
        fprintf(fout, 'Image filename : "%s/Images/%s"\n', query_id, img_name); % replace BASE_DIR by output_img_dir, SHITTTTTTTT in pascal_data.m:  pos(numpos).im      = [VOCopts.datadir rec.imgname];
        fprintf(fout, 'Image size (X x Y x C) : %d x %d x %d\n', WIDTH, HEIGHT, 3);
        fprintf(fout, 'Database : "%s"\n', DATABASE); %???

		label = ['PASquery_' query_id]; % --> PAS_query_9069
		origional_label = ['query_' query_id]; % query_9069 is the input of the train model function --> MUST BE IN LOWERCASE + train_model('query_9069', 1) SHHHHHHHHHHH
        object_labels = sprintf('"%s"', label);
        obj_counter = 0; % number of objects in the input image
        for k=1:length(rects)
            if ~ isempty(rects{k})
                obj_counter = obj_counter + 1;
            end
        end
		
		% special treatment if the number of objects > 1, eg. Objects with ground truth : 4 { "PASquery_9090" "PASquery_9090" "PASquery_9090" "PASquery_9090" }
        for k = 2:obj_counter 
            object_labels = sprintf('%s "%s"', object_labels, label);
        end
        fprintf(fout, 'Objects with ground truth : %d { %s }\n\n', obj_counter, object_labels);
         % some unnecessary info
        fprintf(fout, '# Note that there might be other objects in the image\n');
        fprintf(fout, '# for which ground truth data has not been provided.\n\n');
        fprintf(fout, '# Top left pixel co-ordinates : (1, 1)\n\n');

        for k = 1:obj_counter
            rect = uint32(rects{k});
            if isempty(rect)
                continue;
            end
            fprintf(fout, '# Details for object %d ("%s")\n', k, label);
            fprintf(fout, 'Original label for object %d "%s" : "%s"\n', k, label, origional_label);
            fprintf(fout, 'Bounding box for object %d "%s" (Xmin, Ymin) - (Xmax, Ymax) : (%d, %d) - (%d, %d)\n\n', k, label, ...
                rect(1), rect(2), rect(3), rect(4));
        end
        fclose(fout);
        
        % write image
        imwrite(src_img, strrep(fullfile(output_img_dir, query_img_names{j}), '.src.png', '.src.jpg'), 'jpg');
    end
	% copy neg-images to output_img_dir
	cmdLinux = ['cp ' neg_img_dir '/*.jpg ' output_img_dir];
	unix(cmdLinux);
	
    % write .cfg file
    config_fname = fullfile(config_file_dir, [query_id, '.cfg']); % 9069.cfg - used for visualization too
    fout = fopen(config_fname,'w');
    fprintf(fout, 'Scale : %f\n', scale);
    fprintf(fout, 'Number of sample images: %d\n', counter); 
    fprintf(fout, 'Number of discarded images: %d\n', n_discarded); 
    fclose(fout);
    
    % write trainval file
	% trainval_txt --> pos images
	% train.txt -->  neg images
    if n_discarded < counter
        trainval_fname = fullfile(trainval_dir, ['trainval_' query_id '.txt']);
        fout = fopen(trainval_fname, 'w');
        for j=1:counter2
            fprintf(fout, '%s\n', valid_imgname{j});
        end
        fclose(fout);
    end
	
    train_fname = fullfile(train_dir, ['train_' query_id '.txt']);
	fout = fopen(train_fname, 'w');
    for i=1:length(neg_files)
		fprintf(fout, '%s\n', neg_files(i).name);
		
		% create a new file in annotation dir, eg neg_1.txt
		negzz_fname = fullfile(anotation_dir, [neg_files(i).name '.txt']);
		fout2 = fopen(negzz_fname, 'w');
		% relative path to model/ins-dpm/tv2013/query2013 --> set in my_VOCinit (VOCopts.datadir=fullfile('/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/'); )
		fprintf(fout2, 'Image filename : "%s/Images/%s.jpg"\n', query_id, neg_files(i).name); % SHITTTTT in pascal_data.m: neg(numneg).im     = [VOCopts.datadir rec.imgname];
		fclose(fout2);
		
	end
	fclose(fout);
	
	voc_config_fname = fullfile(config_file_dir, ['voc_config_' query_id, '.m']); % voc_config_9069.m
	fout = fopen(voc_config_fname, 'w');

	fprintf(fout, 'function conf = voc_config_%s()\n', query_id);
	fprintf(fout, 'conf.pascal.year = ''%s'';\n', query_id);
	%conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9098/';
	fprintf(fout, 'conf.paths.model_dir = ''%s/'';\n', config_file_dir);
	%conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	fprintf(fout, 'conf.training.log = @(x) sprintf([conf.paths.model_dir ''%%s.log''], x);\n');
	%conf.pascal.VOCopts.annopath = '9098/Annotations/%s.txt';
	fprintf(fout, 'conf.pascal.VOCopts.annopath = ''%s/Annotations/%%s.txt'';\n', config_file_dir);
	%conf.pascal.VOCopts.imgsetpath = '9098/ImageSets/%s.txt';
	fprintf(fout, 'conf.pascal.VOCopts.imgsetpath = ''%s/ImageSets/%%s.txt'';\n', config_file_dir);
	%conf.pascal.VOCopts.imgpath = '9098/Images/%s.txt';
	fprintf(fout, 'conf.pascal.VOCopts.imgpath = ''%s/Images/%%s.txt'';\n', config_file_dir);
	%end
	fprintf(fout, 'end\n');
	fclose(fout);
	
	
end

% 9090.cfg - config file
% Scale : 1.282051
% Number of sample images: 4
% Number of discarded images: 0


% 9090.1.src.txt - annotation file
% # PASCAL Annotation Version 1.00

% Image filename : "9090/Images/9090.1.src.jpg"
% Image size (X x Y x C) : 985 x 739 x 3
% Database : "trecvid_query"
% Objects with ground truth : 4 { "PASquery_9090" "PASquery_9090" "PASquery_9090" "PASquery_9090" }

% # Note that there might be other objects in the image
% # for which ground truth data has not been provided.

% # Top left pixel co-ordinates : (1, 1)

% # Details for object 1 ("PASquery_9090")
% Original label for object 1 "PASquery_9090" : "Query_9090"
% Bounding box for object 1 "PASquery_9090" (Xmin, Ymin) - (Xmax, Ymax) : (133, 238) - (371, 701)

% # Details for object 2 ("PASquery_9090")
% Original label for object 2 "PASquery_9090" : "Query_9090"
% Bounding box for object 2 "PASquery_9090" (Xmin, Ymin) - (Xmax, Ymax) : (522, 286) - (600, 468)

% # Details for object 3 ("PASquery_9090")
% Original label for object 3 "PASquery_9090" : "Query_9090"
% Bounding box for object 3 "PASquery_9090" (Xmin, Ymin) - (Xmax, Ymax) : (555, 574) - (605, 629)

% # Details for object 4 ("PASquery_9090")
% Original label for object 4 "PASquery_9090" : "Query_9090"
% Bounding box for object 4 "PASquery_9090" (Xmin, Ymin) - (Xmax, Ymax) : (686, 304) - (845, 701)

% trainval_9090.txt
% 9090.1.src
% 9090.2.src
% 9090.3.src
% 9090.4.src
