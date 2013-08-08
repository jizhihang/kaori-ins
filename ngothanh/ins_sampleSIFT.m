clear; clc;

%% scripts to sampling extracted SIFT(s)
cfg.rawfeatdir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/rawfeature/keyframe-5';
cfg.untardir = './untartmp';
cfg.datadir = './data';
cfg.year = 'tv2011';    % = 'tv2011' / 'tv2012' / 'tv2013'
cfg.type = 'test';      % = 'test' / 'query'
cfg.feattype = 'heslap'; % = 'heslap'
cfg.nsample = 1000000;

cfg.untardir = [cfg.untardir '_' cfg.feattype];
% create temp dir: to temporarily save resized images
if (~exist(cfg.untardir,'dir'))
    mkdir(cfg.untardir);
end    

% create data dir
if (~exist(cfg.datadir,'dir'))
    mkdir(cfg.datadir);
end    

tardir = [cfg.rawfeatdir '/' cfg.year '/' cfg.type];
disp('scanning tar files ...');
tar = dir(fullfile(tardir,'*.tar.gz'));
ntar = length(tar);

disp('sampling raw feature files ...');
randidx = randperm(ntar);
nseltar = floor(0.5*ntar);
seltar = randidx(1:nseltar);

% for each tar file/ video prg, randomly pick some points
featmat = zeros(128,cfg.nsample,'uint16');
nfeat = 0;
for i=1:nseltar
    disp([num2str(nfeat) ' ...']);    
    tarfile = [tardir '/' tar(seltar(i)).name];
    %disp(tarfile);
    if (~exist(tarfile,'file'))
        disp('error detected');
        break;
    end    
    %untar(tarfile,cfg.untardir);
    system(['tar -C ' cfg.untardir ' -zxvf ' tarfile],'-echo');
    utarfile = dir(fullfile(cfg.untardir,['*.' cfg.feattype '.sift']));
    nutar = length(utarfile);
    nselfeat = floor(0.1*length(utarfile));
    randfeatidx = randperm(nutar);
    selfeat = randfeatidx(1:nselfeat);
    enough = 0;
    for j=1:nselfeat
        name = utarfile(selfeat(j)).name;
        featfile = [cfg.untardir '/' name];
        [feats, frames] = LoadO1SIFTFeature(featfile);
        feats = feats(:,randperm(floor(0.1*size(feats,2))));
        if (nfeat + size(feats,2) <= cfg.nsample)
            featmat(:,nfeat+1:nfeat+size(feats,2)) = feats(:,:);
            nfeat = nfeat + size(feats,2);
        else
            enough = 1; break;
        end    
    end        
    system(['rm ' cfg.untardir '/*.sift'],'-echo');
    if (enough == 1) 
        break; 
    end
end
system(['rm -rf ' cfg.untardir],'-echo');
featmat = featmat(:,1:nfeat);
save([cfg.datadir '/' cfg.year '.' num2str(cfg.nsample) '.sampledSIFT.' cfg.feattype '.mat'],'featmat');
disp('-- DONE --');

