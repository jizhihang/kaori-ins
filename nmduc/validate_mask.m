function [rects, scale] = validate_mask(src, mask, separateRegion)
% Return values (more details are below)
%   ROIs      Cell array of ROI images
%
% Arguments
%   src             source image
%   mask            mask image
%   extPercent      percentage of extended part according to the region 
%                   (to capture context information)
%   separateRegion  crop each region as a ROI or not (boolean)
% Output	
%   scale is estimated by min_size of bounding boxex
%	bounding boxes
    
	% Parameter configuration
    BW_THRESHOLD = 0.5;
    MIN_REGION_SIZE = 80;
    AREA_THRESHOLD = 40*40.0;
    DIM_THRESHOLD = 50.0; % minimum size of bounding box
    min_wh = Inf;
    scale = 1;
    min_area = Inf;
    
    WIDTH = 768; HEIGHT = 576; % image size of TV2013 videos (BBCEastEnders)
    RATIO = 20;
    MIN_X = WIDTH/RATIO;
    MAX_X = WIDTH - MIN_X;
    MIN_Y = HEIGHT/RATIO;
    MAX_Y = HEIGHT - MIN_Y;

    AREA = HEIGHT * WIDTH;
    objects = uint32([]);
    rects = [];
    roi_areas = [];
    obj_counter = 0;
    minx = Inf; maxx = -Inf; miny = Inf; maxy = -Inf;
    % convert mask image to binary
    bwmask = im2bw(mask, BW_THRESHOLD);
    
    % process non-zero regions
    [B L] = bwboundaries(bwmask);
    flag = 0;

    for i=1:length(B)
        [r c] = find(L==i);
        x1 = min(c);
        y1 = min(r);
        x2 = max(c);
        y2 = max(r);
        old_area = (x2-x1) * (y2-y1);
        flag = 0;
        if x1 < MIN_X
            x1 = MIN_X;
            flag = 1;
        end
        if x2 > MAX_X
            x2 = MAX_X;
            flag = 1;
        end
        if y1 < MIN_Y
            y1 = MIN_Y;
            flag = 1;
        end
        if y2 > MAX_Y
            y2 = MAX_Y;
            flag = 1;
        end
        % discard sample that is too close to the boundary
        if (x2-x1) * (y2-y1) / old_area < 0.6
            continue;
        end
        
        if separateRegion
        % extract each region as a ROI
            if length(find(L==i)) < MIN_REGION_SIZE
                % discard regions which are too small
                continue;
            end
            obj_counter = obj_counter + 1;
            roi_areas(obj_counter) = length(find(L==i));
            objects(obj_counter,1) = x1;
            objects(obj_counter,2) = y1;
            objects(obj_counter,3) = x2;
            objects(obj_counter,4) = y2;
        else
            % extract only the max region 
            if x1 < minx
                minx = x1;
            end
            if x2 > maxx
                maxx = x2;
            end
            if y1 < miny
                miny = y1;
            end
            if y2 > maxy
                maxy = y2;
            end
        end
    end
    if ~separateRegion
        obj_counter = 1;
        objects(obj_counter,1) = minx;
        objects(obj_counter,2) = miny;
        objects(obj_counter,3) = maxx;
        objects(obj_counter,4) = maxy;
    end
    
    % extract ROIs
    for i=1:obj_counter
        rect = [];
        rect(1:4) = objects(i, 1:4);
        w = objects(i,3) - objects(i,1);
        h= objects(i,4) - objects(i,2);
        rect_area = double(w * h);
        
        % discard too large roi
        if roi_areas(i) > 0.9*AREA 
%             imshow(mask);
%             rectangle('Position', [x1,y1,x2-x1,y2-y1], 'EdgeColor', 'r');
%             pause();
            continue;
        end
        if rect_area < MIN_REGION_SIZE
%             imshow(mask);
%             rectangle('Position', [x1,y1,x2-x1,y2-y1], 'EdgeColor', 'r');
%             pause();
            continue;
        end
        % discard too small roi/rect ratio
        if roi_areas(i)/rect_area < 0.1
%             imshow(mask);
%             rectangle('Position', [x1,y1,x2-x1,y2-y1], 'EdgeColor', 'r');
%             pause();
            continue;
        end
        if rect_area < min_area
            min_area = rect_area;
        end
        if min(w,h) < min_wh
            min_wh = min(w,h);
        end
        rects{i} = rect;
        
    end
    % deal with small roi
%     if min_area < AREA_THRESHOLD
%         scale = sqrt(AREA_THRESHOLD/min_area);
%     end
    if min_wh < DIM_THRESHOLD
        scale = DIM_THRESHOLD/double(min_wh); % scale is estimated by min_size of bounding boxex
    end
end


