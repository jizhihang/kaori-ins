clear; clc;

%% scripts to extract feature from frames of Instance Search datasets
cfg.bin = '/net/per610a/export/das11f/ledduy/ndthanh/InstanceSearch2013/bin/compute_descriptors_64bit.ln';
cfg.kfdir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5';    % keyframe dir
cfg.meta = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/metadata/keyframe-5';
cfg.rawfeatdir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/rawfeature/keyframe-5';
cfg.year = 'tv2011';    % = 'tv2011' / 'tv2012' / 'tv2013'
cfg.type = 'test';      % = 'test' / 'query'
cfg.maxsize = 500;      % max image size
istart = 1;
iend = 100;

% create temp dir: to temporarily save resized images
if (~exist('./thanhtmp','dir'))
    mkdir('./thanhtmp');
end    

% load rpg list
prgfile = [cfg.meta '/' cfg.year '/' cfg.year '.' cfg.type '.lst'];
fprg = fopen(prgfile,'r');
prg = textscan(fprg,'%s');
prg = prg{1};
fclose(fprg);

% process each prg/vid from istart --> iend
for i=istart:iend
    curprg = prg(i);
    curprgfile = [cfg.meta '/' cfg.year '/' cfg.type '/' char(curprg) '.prg'];
    
    % read the prg file
    f = fopen(curprgfile,'r');
    fcontent = textscan(f,'%d#$#%s');
    shotid = fcontent{1};
    frameid = fcontent{2};
    fclose(f);
    
    % make output dir
    u = unique(shotid);
    for j=1:length(u)
        udir = [cfg.rawfeatdir '/' cfg.year '/' cfg.type '/' num2str(u(j))];
        if (~exist(udir,'dir'))
            mkdir(udir);
        end    
    end    
    % process each key-frame
    for j=1:length(frameid)    
        imfile = [cfg.kfdir '/' cfg.year '/' cfg.type '/' num2str(shotid(j)) '/' char(frameid(j)) '.jpg'];
        im = imread(imfile);
        h = size(im,1); w = size(im,2);
        
        % resize image
        if ((h > cfg.maxsize) || (w>cfg.maxsize))
            if (w>h)
                im = imresize(im,[(h/w*cfg.maxsize) cfg.maxsize]);
            else
                im = imresize(im,[cfg.maxsize (w/h*cfg.maxsize)]);
            end    
        end    
        
        % save to temp dir
        tmpfile = ['./thanhtmp/' char(curprg) '_' num2str(shotid(j)) '_' char(frameid(j)) '.jpg'];
        imwrite(im, tmpfile);
        outfile1 = [cfg.rawfeatdir '/' cfg.year '/' cfg.type '/' num2str(shotid(j)) '/' char(frameid(j)) '.heslap.sift'];
        outfile2 = [cfg.rawfeatdir '/' cfg.year '/' cfg.type '/' num2str(shotid(j)) '/' char(frameid(j)) '.dense.sift'];
        % extract feature        
        cmd = [cfg.bin ' -heslap -sift -i ' char(tmpfile) ' -o1 ' outfile1];
        system(cmd,'-echo');        
        cmd = [cfg.bin ' -dense 6 6 -sift -i ' char(tmpfile) ' -o1 ' outfile2];
        system(cmd,'-echo');         
        system(['rm ./thanhtmp/' char(curprg) '_' num2str(shotid(j)) '_' char(frameid(j)) '.jpg'],'-echo');
    end    
end    
