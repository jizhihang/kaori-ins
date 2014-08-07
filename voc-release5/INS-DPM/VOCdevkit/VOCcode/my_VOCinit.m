clear VOCopts

% get current directory with forward slashes

cwd=cd;
cwd(cwd=='\')='/';

% change this path to point to your copy of the PASCAL VOC data
% this dir is used in pascal_data_m (line neg(numneg).im     = [VOCopts.datadir rec.imgname];)
% rec.imgname is declared in Annotations/neg-img-name.txt
VOCopts.datadir=fullfile('/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/'); % use the same the dir in voc_config_9069.m (check identical --> if BUGGY)

% change this path to a writable directory for your results
VOCopts.resdir=[cwd '/results/'];

% change this path to a writable local directory for the example code
VOCopts.localdir=[cwd '/local/'];

% initialize the test set

%VOCopts.testset='val'; % use validation data for development test set
VOCopts.testset='test'; % use test set for final challenge

% initialize paths

%VOCopts.imgsetpath=[VOCopts.datadir 'comp1/ImageSets/%s.txt'];
if isempty(conf.pascal.VOCopts.imgsetpath)
    VOCopts.imgsetpath=[VOCopts.datadir 'ImageSets/%s.txt'];
else % always  use this because conf.pascal.VOCopts.imgsetpath is set in voc_config_9069.m
    VOCopts.imgsetpath = [conf.pascal.VOCopts.imgsetpath]; % we use absolute path in voc_config_9069.m
end

VOCopts.clsimgsetpath=[VOCopts.datadir 'comp1/ImageSets/%s_%s.txt'];
%VOCopts.annopath=[VOCopts.datadir 'comp1/Annotations/%s.txt'];
if isempty(conf.pascal.VOCopts.annopath)
    VOCopts.annopath=[VOCopts.datadir 'Annotations/%s.txt'];
else
    VOCopts.annopath = [conf.pascal.VOCopts.annopath]; % we use absolute path in voc_config_9069.m
end
%VOCopts.imgpath=[VOCopts.datadir 'comp1/Images/%s.png'];
if isempty(conf.pascal.VOCopts.annopath)
    VOCopts.imgpath=[VOCopts.datadir 'Images/%s.png'];
else
    VOCopts.imgpath = [conf.pascal.VOCopts.imgpath]; % we use absolute path in voc_config_9069.m
end
VOCopts.clsrespath=[VOCopts.resdir '%s_cls_' VOCopts.testset '_%s.txt'];
VOCopts.detrespath=[VOCopts.resdir '%s_det_' VOCopts.testset '_%s.txt'];

% initialize the VOC challenge options
VOCopts.classes={'inriaperson'}; %???
VOCopts.nclasses=length(VOCopts.classes);	

VOCopts.minoverlap=0.5;

% initialize example options

VOCopts.exfdpath=[VOCopts.localdir '%s_fd.mat'];
