function ins13RawFeatureExtraction_CheckMissingTarFiles(year,pstart,pend)
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
tic;
% load rpg list
prgfile = [cfg.meta '/' cfg.year '/' cfg.year '.' cfg.type '.lst'];
fprg = fopen(prgfile,'r');
prg = textscan(fprg,'%s');
prg = prg{1};
fclose(fprg);

% process each prg/vid from istart --> iend
flog = fopen('./checkmissingtar.log','wt');
count = 0;
for i=max(1,istart):min(iend,length(prg))
	disp(i);
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
	u = unique(shotid);
	for j=1:length(u)
		ctarfile = [cfg.rawfeatdir '/' cfg.year '/' cfg.type '/' char(u(j)) '.tar.gz'];
		if (~exist(ctarfile,'file'))
			disp(ctarfile);
			count = count + 1;
			fprintf(flog,'%d %s\n',i,char(u(j)));
		end
	end
end    
fclose(flog);
disp(count);
toc;
end

