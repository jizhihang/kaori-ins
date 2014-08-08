clear VOCopts

% DuyLe - look for CHANGED if you want to modify this code for your own use
% VOCopts is changed partially in voc_config_90xx --> REM to avoid override

% voc_config_9090.m
% conf.pascal.year = '9090';
% conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9090/';
% conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
% conf.pascal.VOCopts.annopath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9090/Annotations/%s.txt';
% conf.pascal.VOCopts.imgsetpath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9090/ImageSets/%s.txt';
% conf.pascal.VOCopts.imgpath = '/net/per610a/export/das11f/ledduy/trecvid-ins-2014/model/ins-dpm/tv2013/query2013/9090/Images/%s.txt';

% dataset
%
% Note for experienced users: the VOC2008-11 test sets are subsets
% of the VOC2012 test set. You don't need to do anything special
% to submit results for VOC2008-11.

% Used for determining training data (images, annotation) - VOCopts.annopath=[VOCopts.datadir VOCopts.dataset '/Annotations/%s.xml'];
% these info is overriden in voc_config_90xx --> do not change anything here
VOCopts.dataset='VOC2012'; 

% get devkit directory with forward slashes
devkitroot=strrep(fileparts(fileparts(mfilename('fullpath'))),'\','/'); % CHANGED (REM ALL)

% change this path to point to your copy of the PASCAL VOC data
% override in voc_config_90xx
% VOCopts.datadir=[devkitroot '/']; % CHANGED (REM ALL)

% change this path to point to your copy of the PASCAL VOC data
% this dir is used in pascal_data_m (line neg(numneg).im     = [VOCopts.datadir rec.imgname];)
% rec.imgname is declared in Annotations/neg-img-name.txt

if isempty(conf.pascal.VOCopts.datadir)
	VOCopts.datadir=[devkitroot '/'];
else
    VOCopts.datadir = [conf.pascal.VOCopts.datadir]; % training data is located at the same dir with model-dir
end

% change this path to a writable directory for your results
VOCopts.resdir=[devkitroot '/results/' VOCopts.dataset '/']; % CHANGED (REM ALL)

% change this path to a writable local directory for the example code
VOCopts.localdir=[devkitroot '/local/' VOCopts.dataset '/']; % CHANGED (REM ALL)

% initialize the training set

 VOCopts.trainset='train'; % use train for development
% VOCopts.trainset='trainval'; % use train+val for final challenge

% initialize the test set

VOCopts.testset='val'; % use validation data for development test set
% VOCopts.testset='test'; % use test set for final challenge

% initialize main challenge paths

% these info is overriden in voc_config_90xx --> do not change anything here (REM ALL)
% VOCopts.annopath=[VOCopts.datadir VOCopts.dataset '/Annotations/%s.xml']; % CHANGED
% VOCopts.imgpath=[VOCopts.datadir VOCopts.dataset '/JPEGImages/%s.jpg']; % CHANGED
% VOCopts.imgsetpath=[VOCopts.datadir VOCopts.dataset '/ImageSets/Main/%s.txt']; % CHANGED
% VOCopts.clsimgsetpath=[VOCopts.datadir VOCopts.dataset '/ImageSets/Main/%s_%s.txt']; % CHANGED
% VOCopts.clsrespath=[VOCopts.resdir 'Main/%s_cls_' VOCopts.testset '_%s.txt']; % CHANGED
% VOCopts.detrespath=[VOCopts.resdir 'Main/%s_det_' VOCopts.testset '_%s.txt']; % CHANGED

if isempty(conf.pascal.VOCopts.imgsetpath)
    VOCopts.imgsetpath=[VOCopts.datadir VOCopts.dataset '/ImageSets/Main/%s.txt'];
else % always  use this because conf.pascal.VOCopts.imgsetpath is set in voc_config_9069.m
    VOCopts.imgsetpath = [conf.pascal.VOCopts.imgsetpath]; % we use absolute path in voc_config_9069.m
end

if isempty(conf.pascal.VOCopts.annopath)
    VOCopts.annopath=[VOCopts.datadir VOCopts.dataset '/Annotations/%s.txt'];
else
    VOCopts.annopath = [conf.pascal.VOCopts.annopath]; % we use absolute path in voc_config_9069.m
end

if isempty(conf.pascal.VOCopts.annopath)
    VOCopts.imgpath=[VOCopts.datadir VOCopts.dataset '/JPEGImages/%s.jpg'];
else
    VOCopts.imgpath = [conf.pascal.VOCopts.imgpath]; % we use absolute path in voc_config_9069.m
end


% initialize the VOC challenge options

% classes

VOCopts.classes={'trecvid_ins'};

VOCopts.nclasses=length(VOCopts.classes);	

% overlap threshold

VOCopts.minoverlap=0.5;

% annotation cache for evaluation

VOCopts.annocachepath=[VOCopts.localdir '%s_anno.mat'];

% options for example implementations

VOCopts.exfdpath=[VOCopts.localdir '%s_fd.mat'];


