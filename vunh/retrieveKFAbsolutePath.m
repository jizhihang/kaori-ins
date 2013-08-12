% This function parse a frameID to the absolute directory
%   absoluteDir     keyframe path

function absoluteDir = retrieveKFAbsolutePath(line, trecVersion)
keyframeDir = fullfile('/net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5/'...
                        ,trecVersion,'test');
                    
raw = textscan(line, '%s');
comps = raw{1};

kfID = comps{1};
shotID = comps{2};

absoluteDir = fullfile(keyframeDir, shotID, [kfID '.jpg']);
end