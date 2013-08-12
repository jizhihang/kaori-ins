function [mvec, pvec, bimg] = detection_DPM(img, mask, regionHeight, regionWidth,p)
    img=im2double(img); img = img(regionHeight,regionWidth,:);
    mask = mask(regionHeight, regionWidth);
    
    [rows,cols,ndim]=size(img);
    
    [mvec,pvec, arate,elist2]=detect_mscr_masked(img,mask,p);
    pvec = pvec/256;
    
    bkgr=[1 0 0]'; % Paint on black background
    [mvec, pvec] = eliminate_equivalentblobs(mvec, pvec);
    bimg=draw_blobs(mvec,pvec,rows,cols,bkgr);
end