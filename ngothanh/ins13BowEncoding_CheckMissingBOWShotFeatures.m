function ins13BowEncoding_CheckMissingBOWShotFeatures(year)

%% scripts to check missing files of shot features
cfg.year = year;        % = 'tv2011' / 'tv2012' / 'tv2013'
cfg.type = 'test';      % = 'test' / 'query'
cfg.maxsize = 500;      % max image size
cfg.meta = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/metadata/keyframe-5';
cfg.shotfeatdir = ['/net/per610a/export/das11f/ledduy/trecvid-ins-2013/feature/keyframe-5/feature-ext/' cfg.year '/' cfg.type];
cfg.cbfile = '/net/per610a/export/das11f/ledduy/ndthanh/InstanceSearch2013/code/matlab/data/tv2011.cb1k.dense.mat';

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

flog = fopen(['./checkmissingBOWshotfeatures_' cfg.year '.log'],'w');
for i=1:length(prg)
    curprg = prg(i);
    
    % BOW shot feature file
    shotBowfile = [cfg.shotfeatdir '/' char(curprg) '.dense-heslap-sift-spm-cb' num2str(cbsize)];
    
    % check exist
    if (~exist(shotBowfile,'file'))
        disp(shotBowfile);
        fprintf(flog,'%d %s\n',i,char(curprg));
    end    
end    
fclose(flog);