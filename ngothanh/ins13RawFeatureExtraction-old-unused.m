function ins13RawFeatureExtraction(year,pstart,pend)
cfg.bin = '/net/per610a/export/das11f/ledduy/ndthanh/InstanceSearch2013/bin/compute_descriptors_64bit.ln';
cfg.kfdir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5';    % keyframe dir
cfg.meta = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/metadata/keyframe-5';
cfg.rawfeatdir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/rawfeature/keyframe-5';
cfg.localdir = ['/local/ledduy/thanhtmp_' year '_' num2str(pstart) '_' num2str(pend)];
cfg.year = year;    % = 'tv2011' / 'tv2012' / 'tv2013'
cfg.type = 'test';      % = 'test' / 'query'
cfg.maxsize = 500;      % max image size
istart = pstart;
iend = pend;

% create temp dir: to temporarily save resized images
if (~exist(cfg.localdir,'dir'))
    mkdir(cfg.localdir);
end    

% create raw feat dir
outrawfeatdir = [cfg.rawfeatdir '/' cfg.year '/' cfg.type];
if (~exist(outrawfeatdir,'dir'))
	disp(outrawfeatdir);
	mkdir(outrawfeatdir);
end

% load rpg list
prgfile = [cfg.meta '/' cfg.year '/' cfg.year '.' cfg.type '.lst'];
fprg = fopen(prgfile,'r');
prg = textscan(fprg,'%s');
prg = prg{1};
fclose(fprg);

% process each prg/vid from istart --> iend
for i=max(1,istart):min(iend,length(prg))
    curprg = prg(i);
    curprgfile = [cfg.meta '/' cfg.year '/' cfg.type '/' char(curprg) '.prg'];
    
    % read the prg file
    f = fopen(curprgfile,'r');
    fcontent = textscan(f,'%s');
    fcontent = fcontent{1};    
    fclose(f);
    shotid = cell(length(fcontent),1);
    frameid = cell(length(fcontent),1);
    for j=1:length(fcontent)
        curstr = char(fcontent{j});
        [sid,rem] = strtok(curstr,'#$#');
        shotid{j} = sid;
        frameid{j} = rem(4:length(rem));
    end
    % make output dir
    [u,ia,ic] = unique(shotid);
	mark = zeros(1,length(shotid));
    for j=1:length(u)        
        ctarfile = [outrawfeatdir '/' char(u(j)) '.tar.gz'];
        if (exist(ctarfile,'file'))
            mark(ic == j) = 1;
        else
            udir = [cfg.localdir '/' char(u(j))];
            disp(ctarfile);
            if (~exist(udir,'dir'))
                mkdir(udir);
            end    	
        end
    end    
    % process each key-frame
    for j=1:length(frameid)    
		if (mark(j) == 1)
			continue;
		end
        imfile = [cfg.kfdir '/' cfg.year '/' cfg.type '/' char(shotid(j)) '/' char(frameid(j)) '.jpg'];
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
        tmpfile = [cfg.localdir '/' char(shotid(j)) '/' char(frameid(j)) '.jpg'];
        imwrite(im, tmpfile);
        outfile1 = [cfg.localdir '/' char(shotid(j)) '/' char(frameid(j)) '.heslap.sift'];
        outfile2 = [cfg.localdir '/' char(shotid(j)) '/' char(frameid(j)) '.dense.sift'];
        % extract feature        
        cmd = [cfg.bin ' -heslap -sift -i ' char(tmpfile) ' -o1 ' outfile1];
        system(cmd,'-echo');           
        
        cmd = [cfg.bin ' -dense 6 6 -sift -i ' char(tmpfile) ' -o1 ' outfile2];
        system(cmd,'-echo');  
        system(['rm ' tmpfile],'-echo');
    end    
	% tar-ing
	for j=1:length(u)
		udir = [cfg.localdir '/' char(u(j))];
		if (exist(udir,'dir'))
			disp('tar-ing ...');
			tarcmd = ['tar -zcvf ' udir '.tar.gz -C ' udir ' .'];
			disp(tarcmd);
			system(tarcmd,'-echo');
			cpcmd = ['cp ' udir '.tar.gz ' outrawfeatdir];
			system(cpcmd,'-echo');
			delcmd = ['rm -rf ' udir];			
			system(delcmd,'-echo');
		end
	end	
end    
system(['rm -rf ' cfg.localdir],'-echo');
end

