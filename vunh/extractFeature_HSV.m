%% Extract HSV feature for image
% param     Struct for parameters of MSCR feature extraction
%   normedSize
%   onMask
%   nBin
% img       matrix of image

% Un-implemented functions and variables
% xFirst, xLast, yFirst, yLast
% pers

function personHist = extractFeature_HSV (param, img, box)
xFirst = box(1, 1);
yFirst = box(1, 2);
xLast = box(1, 3);
yLast = box(1, 4);
img = img(yFirst:yLast, xFirst:xLast);
img = imresize(img, param.normedSize);


img_hsv  = rgb2hsv(img);
tmp      = img_hsv(:,:,3);
tmp      = histeq(tmp); % Color Equalization
img_hsv  = cat(3,img_hsv(:,:,1),img_hsv(:,:,2),tmp); % eq. HSV

personHist = [];

    
if (param.onMask == 0)
    clippedMask = ones(size(img));
else
    clippedMask = zeros(size(img));     % Not
end
    
tempHist = [];
for ch = 1:3
    clippedImg = img_hsv(:, :, ch);
    tempHist = [tempHist; whistcY(clippedImg(:), clippedMask(:), [0:1/(param.nBin(ch)-1):1])];
end
personHist = [personHist tempHist'];


end