%% Extract MSCR feature for image
% param     Struct for parameters of MSCR feature extraction
%   normedSize
%   onMask
%   nBin
%   parMSCR
% img       matrix of image

% Un-implemented functions
% 

function [mvec, pvec] = extractFeature_MSCR(param, img, box)
xFirst = box(1, 1);
yFirst = box(1, 2);
xLast = box(1, 3);
yLast = box(1, 4);

% Crop and resize the image
img = img(yFirst:yLast, xFirst:xLast);
img = imresize(img, param.normedSize);

% Illumination normalization
[Ia] = illuminant_normalization(img);

% masking
B = ones(size(img));
Ia = double(Ia).*cat(3,B,B,B); % mask application

% Equalization
[Ha,S,V] = rgb2hsv(uint8(Ia));
Ve = histeq(V(B==1)); Veq = V; Veq(B == 1) = Ve;
Ia = cat(3, Ha,S,Veq);
Ia = hsv2rgb(Ia);



           
% [ma pa] = detection_DPM(Ia, B, (y1:y2), (x1:x2), param.parMSCR);
[ma pa] = detection_DPM(Ia, B, (1:size(Ia,1)), (1:size(Ia,2)), param.parMSCR);
            
%{
mab(3,:) = mab(3,:)+ph(i);
mal(3,:) = mal(3,:)+pb(i);
????
%}
        
mvec = {ma};
pvec = {pa};
        
% mvec = [mvec; {ma}];
% pvec = [pvec; {pa}];
    
end