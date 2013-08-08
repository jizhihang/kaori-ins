function ins13BowEncoding(year,pstart,pend)
cfg.year = year;        % = 'tv2011' / 'tv2012' / 'tv2013'
cfg.type = 'test';      % = 'test' / 'query'
cfg.maxsize = 500;      % max image size
istart = pstart;
iend = pend;
cfg.kfdir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5';    % keyframe dir
cfg.meta = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/metadata/keyframe-5';
cfg.rawfeatdir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/rawfeature/keyframe-5';
cfg.shotfeatdir = ['/net/per610a/export/das11f/ledduy/trecvid-ins-2013/feature-missing-run-on-per/keyframe-5/feature-ext/' cfg.year '/' cfg.type];
cfg.cbfile = '/net/per610a/export/das11f/ledduy/ndthanh/InstanceSearch2013/code/matlab/data/tv2011.cb1k.dense.mat';
cfg.localdir = ['./thanhtmp_encoding_' cfg.year '_' num2str(istart) '_' num2str(iend)];

% intialize vlfeat
vlfsetup = '/net/per900a/raid0/ndthanh/myprojects/tools/vlfeat/toolbox/vl_setup.m';
run(vlfsetup);

% create temp dir: to temporarily save resized images
if (~exist(cfg.localdir,'dir'))
    mkdir(cfg.localdir);
end

% create output dir
if (~exist(cfg.shotfeatdir,'dir'))
    mkdir(cfg.shotfeatdir);
end    

% check feat dir
outrawfeatdir = [cfg.rawfeatdir '/' cfg.year '/' cfg.type];
if (~exist(outrawfeatdir,'dir'))
	disp('Empty raw-feature dir !');
	quit;
end

% load code-book
cb ='';
if (exist(cfg.cbfile,'file'))
    ws = load(cfg.cbfile);
    cb = ws.cb;
    cbsize = size(cb,2);
else
    disp('Un-available codebook file !');
    quit;
end
% load rpg list
prgfile = [cfg.meta '/' cfg.year '/' cfg.year '.' cfg.type '.lst'];
fprg = fopen(prgfile,'r');
prg = textscan(fprg,'%s');
prg = prg{1};
fclose(fprg);

% process each prg/vid from istart --> iend
tic;
totalframe = 0;
totalshot = 0;
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
    [u,ia,ic] = unique(shotid);	
    
    % file to export
    ofilename = [cfg.localdir '/' char(curprg) '.dense-heslap-sift-spm-cb' num2str(cbsize)];    
    % for each  shot
    for j=1:length(u)        
        
        tarfile = [outrawfeatdir '/' char(u(j)) '.tar.gz'];
        if (exist(tarfile,'file'))            
		totalshot = totalshot + 1;
            udir = [cfg.localdir '/' char(u(j))];            
            if (~exist(udir,'dir'))
                mkdir(udir);
            end
            % untar
            
            system(['tar -C ' udir ' -zxvf ' tarfile],'-echo');
            
            % compute BoW
            
            frames = dir(fullfile(udir,'*.dense.sift'));            
            if (~isempty(frames))       
                disp(['Computing histogram for shot: ' char(u(j))]);
                shotBow = zeros(8*cbsize,1);
                nframes = 0;
                
                %process each frame
                for k=1:length(frames)
                    name = frames(k).name(1:length(frames(k).name)-11); % remove '.dense.sift'
                    
                    denfile = [udir '/' name '.dense.sift'];
                    hesfile = [udir '/' name '.heslap.sift'];
                    
                    % check raw-feat file
                    if ((~exist(denfile,'file')) || (~exist(hesfile,'file')))
                        disp('Missing raw-feature file ...');
                        disp(denfile);
                        disp(hesfile);
                        deldircmd = ['rm -rf ' udir];			
                        system(deldircmd,'-echo');
                        continue;
                    end    
                    
                    % read raw-feature
                    [denfeats,denframes] = LoadO1SIFTFeature(denfile);
                    [hesfeats,hesframes] = LoadO1SIFTFeature(hesfile);
                    denfeats = uint8(denfeats);
                    hesfeats = uint8(hesfeats);
                    
                    % get image size
                    imfile = [cfg.kfdir '/' cfg.year '/' cfg.type '/' char(u(j)) '/' name '.jpg'];                    
                    [imh,imw] = GetImageSize(imfile,cfg.maxsize);
                    
                    denseBow = ComputeSPMBow(denfeats,denframes,cb,imh,imw);
                    hesBow = ComputeSPMBow(hesfeats,hesframes,cb,imh,imw);
                    concatBow = cat(1,denseBow,hesBow);
                    shotBow = shotBow + concatBow;
                    nframes = nframes + 1;
                end 
                if (nframes > 0)
                    shotBow = shotBow/nframes;
			totalframe = totalframe + nframes;
                end                   
                
                % export to file
                fo = fopen(ofilename,'a');
                if (fo == -1)
                    disp('Can not open file to write !');
                    disp(ofilname);
                    quit;
                end    
                fprintf(fo,'%d%%%s%%',length(shotBow),char(u(j)));
                for k=1:length(shotBow)
                    fprintf(fo,' %.5f',shotBow(k));
                end    
                fprintf(fo,'\n');
                fclose(fo);
            else
                deldircmd = ['rm -rf ' udir];			
                system(deldircmd,'-echo');
                continue;
            end
            
            deldircmd = ['rm -rf ' udir];
			system(deldircmd,'-echo');
        end    
    end    
    cpcmd = ['cp ' ofilename ' ' cfg.shotfeatdir];
    system(cpcmd,'-echo');
    
    delcmd = ['rm ' ofilename];			
	system(delcmd,'-echo');
end   
system(['rm -rf ' cfg.localdir],'-echo');
end