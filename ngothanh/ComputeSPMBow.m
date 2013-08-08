function [bow] = ComputeSPMBow(feats,frames,cb,imh,imw)
    cbsize = size(cb,2);
    g = zeros(cbsize,1);
    t1 = zeros(cbsize,1); 
    t2 = zeros(cbsize,1); 
    t3 = zeros(cbsize,1);
    y = frames(2,:);
    
    % quatization
    code = vl_ikmeanspush(feats,cb);  
    
    % BOW of tile1
    y1 = 1; y2 = min(floor(imh/3)+1,imh);    
    code1 = code(y>y1 & y<y2);
    t1 = vl_ikmeanshist(cbsize,code1);
    norm = sum(t1);
    if (norm > 0)
        t1 = t1/norm/3;    
    end    
    
    % BOW of tile2
    y1 = min(floor(imh/3),imh); y2 = min(floor(imh*2/3)+1,imh);    
    code2 = code(y>y1 & y<y2);
    t2 = vl_ikmeanshist(cbsize,code2);
    norm = sum(t2);
    if (norm > 0)
        t2 = t2/norm/3;
    end    
    
    % BOW of tile3
    y1 = min(floor(imh*2/3),imh); y2 = imh;    
    code3 = code(y>y1 & y<y2);
    t3 = vl_ikmeanshist(cbsize,code3);
    norm = sum(t3);
    if (norm > 0)
        t3 = t3/norm/3;
    end    
    
    g = t1 + t2 + t3;
    bow = cat(1,g,t1,t2,t3);    
end

