clear; clc;

%% scripts to build codebook from sampled SIFT
cfg.datadir = './data';
cfg.year = 'tv2011';    % = 'tv2011' / 'tv2012' / 'tv2013'
cfg.type = 'test';      % = 'test' / 'query'
cfg.feattype = 'heslap'; % = 'heslap'
cfg.cbdir = './data';
cfg.nsample = 1000000;
K = 1000;

vlfsetup = '/net/per900a/raid0/ndthanh/myprojects/tools/vlfeat/toolbox/vl_setup.m';
run(vlfsetup);

% load sampled SIFT(s)
datafile = [cfg.datadir '/' cfg.year '.' num2str(cfg.nsample) '.sampledSIFT.' cfg.feattype '.mat'];
load(datafile,'featmat');

% clustering by vl_feat
[cb,asgn] = vl_ikmeans(featmat,K,'verbose','method','elkan');

% save codebook
cbfile = [cfg.cbdir '/' cfg.year '.cb.' num2str(K) '.' cfg.feattype '.mat'];
save(cbfile,'cb');

% oyasumi :)
disp('-- DONE --');
