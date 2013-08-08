function ins13BuildCodebook(sampledSIFTfile,cbfile,iK)
%% scripts to build codebook from sampled SIFT
K = iK;

vlfsetup = '/net/per900a/raid0/ndthanh/myprojects/tools/vlfeat/toolbox/vl_setup.m';
run(vlfsetup);

% load sampled SIFT(s)
disp('load SIFT ...');
load(sampledSIFTfile,'featmat');

% clustering by vl_feat
disp(['clustering into ' num2str(K) ' clusters ...']);
[cb,asgn] = vl_ikmeans(featmat,K,'verbose','method','elkan');

% save codebook
save(cbfile,'cb');

% oyasumi :)
disp('-- DONE --');
end

