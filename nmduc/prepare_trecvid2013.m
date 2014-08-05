% path to src images
src_img_dir = 'E:\Workspace\NII\Desktop\trecvid\TRECVID_INS2013\queries\frames\';
% path to mask images
mask_img_path = 'E:\Workspace\NII\Desktop\trecvid\TRECVID_INS2013\queries\masks\';
% path to output ROI images
anotation_dir = 'E:\Workspace\NII\Desktop\trecvid\TRECVID_INS2013\queries\new_training_data\annotation\';
output_img_dir = 'E:\Workspace\NII\Desktop\trecvid\TRECVID_INS2013\queries\new_training_data\images\';
config_file_dir = 'E:\Workspace\NII\Desktop\trecvid\TRECVID_INS2013\queries\new_training_data\configs\';
trainval_dir = 'E:\Workspace\NII\Desktop\trecvid\TRECVID_INS2013\queries\new_training_data\ImageSets\';

SEPERATE_REGION = 1;
MEAN_ROI = 2.9374e+004;
MAX_SCALE_FACTOR = 2.0;

BASE_DIR = 'VOC2007/Images/';
DATABASE = 'trecvid_query';

query_folders = dir(src_img_dir);
for i=1:length(query_folders)
    folder = query_folders(i).name;
    if strcmp(folder,'.') || strcmp(folder, '..')
        continue;
    end
    files = dir([src_img_dir folder]);
    n_discarded = 0;
    query_rects = [];
    query_scales = [];
    query_images = [];
    query_img_names = [];
    valid_imgname = [];
    counter = 0;
    counter2 = 0;
    for j=1:length(files)
        img_path = [src_img_dir folder '\' files(j).name]
        if strcmp(files(j).name,'.') || strcmp(files(j).name, '..')
            continue;
        end
        
        src_img = imread(img_path);
        mask_img_name = strrep(files(j).name, 'src', 'mask');
        mask = imread([mask_img_path mask_img_name]);
        % extract roi image(s): not write annotation, not separate region
        [rects scale] = validate_mask(src_img, mask, SEPERATE_REGION);
        if isempty(rects)
            n_discarded = n_discarded + 1;
        end
        counter = counter + 1;
        query_rects{counter} = rects;
        query_scales(counter) = scale;
        query_images{counter} = src_img;
        query_img_names{counter} = files(j).name;
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
        valid_imgname{counter2} = strrep(query_img_names{j},'.jpg', '');
        % resize image
        if scale ~= 1
            src_img = imresize(src_img, scale, 'lanczos3');
            for k=1:length(rects)
                rects{k} = rects{k} * scale;
            end
        end
        [HEIGHT WIDTH C] = size(src_img);
        
        % write annotation 
        % print initial info to annotation file
        anno_filename = [anotation_dir '\' strrep(strrep(query_img_names{j},'png','txt'),'jpg','txt')];
        fout = fopen(anno_filename, 'w');
        % basic info
        fprintf(fout, '# PASCAL Annotation Version 1.00\n\n');
        img_name = strrep(query_img_names{j},'png','jpg');
        fprintf(fout, 'Image filename : "%s%s"\n', BASE_DIR, img_name);
        fprintf(fout, 'Image size (X x Y x C) : %d x %d x %d\n', WIDTH, HEIGHT, 3);
        fprintf(fout, 'Database : "%s"\n', DATABASE);

        label = ['PASquery_' query_img_names{j}(1:4)];
        origional_label = ['Query_' query_img_names{j}(1:4)];
        object_labels = sprintf('"%s"', label);
        obj_counter = 0;
        for k=1:length(rects)
            if ~ isempty(rects{k})
                obj_counter = obj_counter + 1;
            end
        end
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
        imwrite(src_img, [output_img_dir query_img_names{j}], 'jpg');
    end
    % write .cfg file
    config_fname = [config_file_dir folder '.cfg'];
    fout = fopen(config_fname,'w');
    fprintf(fout, 'Scale : %f\n', scale);
    fprintf(fout, 'Number of sample images: %d\n', counter); 
    fprintf(fout, 'Number of discarded images: %d\n', n_discarded); 
    fclose(fout);
    
    % write trainval file
    if n_discarded < counter
        trainval_fname = [trainval_dir 'trainval_' folder '.txt'];
        fout = fopen(trainval_fname, 'w');
        for j=1:counter2
            fprintf(fout, '%s\n', valid_imgname{j});
        end
        fclose(fout);
    end
end

