function [h,w] = GetImageSize(imfile,maxsize)
    h = 0;
    w = 0;
    try
        im = imread(imfile);
    catch
        return;
    end    
    h = size(im,1); w = size(im,2);
    % resize image
    if ((h > maxsize) || (w>maxsize))
        if (w>h)
            w = maxsize;
            h = floor(h/w*maxsize);
        else
            w = floor(w/h*maxsize);
            h = maxsize;
        end    
    end    
end

