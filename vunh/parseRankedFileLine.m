% This function parse a line in the ranked file to
%   frameID  keyframe path
%   x1
%   y1
%   x2
%   y2

function [frameID, x1, y1, x2, y2] = parseRankedFileLine(line)


raw = textscan(line, '%s');
comps = raw{1};

kfID = comps{1};
shotID = comps{2};
frameID = [kfID '#$#' shotID];

x1 = comps{3};
y1 = comps{4};
x2 = comps{5};
y2 = comps{6};
end