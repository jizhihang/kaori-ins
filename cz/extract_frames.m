function extract_frames(run_id,num_multi_run,INS)
if nargin<3
    INS = 'ins2013';
end
if nargin<2
    num_multi_run = 1;
end
if nargin<1
    run_id = 1;
end
%INS = 'ins2013';
%num_multi_run = 300000;
%run_id = 1;
work_dir = fullfile('/home/caizhizhu/per610a/ins', INS);
clip_dir='/net/per610a/export/das11f/ledduy/new-trecvid-archive/tv2013/trecvid-bbc/eastenders/videos';
frame_parent_dir=fullfile(work_dir,'frames_png');
%%% redo for the newer directory
%newer_file = '/home/caizhizhu/bigspace/news/frames_new/2012_06_13_11_102587_0000ntv~2012_06_13_11_107898_0000nt*';
%newer_file_info = dir(newer_file);
%assert(length(newer_file_info) == 1);
%newer_time = datenum(newer_file_info(1).date);
%%% redo for the newer directory
collection_xml = fullfile(work_dir,'active/bbc.eastenders.master.shot.reference/eastenders.collection.xml');
%xmlStru = parseXML(collection_xml);
xmlStru = xmltools(collection_xml);
num_clip = length(xmlStru.child(2).child);
clip_ids = zeros(num_clip,1);
clip_names = cell(num_clip,1);
for i=1:num_clip
    clip_ids(i)=str2double(xmltools(xmlStru.child(2).child(i),'get','id','value'));
    clip_names{i}=xmltools(xmlStru.child(2).child(i),'get','filename','value');
    %fprintf('%d %s\n',clip_ids(i),clip_names{i});
end
masterShotReferenceTable = fullfile(work_dir,'active/bbc.eastenders.master.shot.reference/eastenders.masterShotReferenceTable');
ms_fid=fopen(masterShotReferenceTable,'r');
pack = textscan(ms_fid,'%d %s %s %s');
fclose(ms_fid);
sampling_hz = int32(5);    % frame per second
%frame_rate = 25.000; % constant depends on the video

run_scope = linspace(1,length(pack{1}),num_multi_run+1);
sid = floor(run_scope(run_id));
eid = floor(run_scope(min(run_id+1,end)));
if run_id < num_multi_run
    eid = eid - 1;
end

total_time = 0;
total_duration = 0;
total_frame_num = 0;
total_ext_frame_num = 0;

log_file = fullfile(work_dir,sprintf('log/extract_frame_%d_%d.log',run_id,num_multi_run));
log_fid=fopen(log_file,'w');
tip = 'shot_id shot_name frame_num ext_frame_num time\n';
fprintf(tip);
fprintf(log_fid,tip);
for i=sid:eid
    tic;
    clip_id = pack{1}(i);
    clip_name = clip_names{clip_ids==clip_id};
    clip_filename = fullfile(clip_dir,clip_name);
    shot_name = pack{2}{i};
    %if ~strcmp(shot_name,'shot44_1704')
    %    continue;
    %end
    [start_time, frame_rate]=convertTime(pack{3}{i});
    end_time = convertTime(pack{4}{i});

    frame_dir = fullfile(frame_parent_dir,shot_name);
    duration = etime(datevec(end_time),datevec(start_time))+1/frame_rate;
    frame_num = duration*frame_rate; 
    ext_frame_num = ceil(duration * sampling_hz);
    
    % in case failed in last time
    need_ext_flag = true;
    if exist(frame_dir,'dir')
        shot_frames= dir(fullfile(frame_dir,'*.png'));
        if ext_frame_num == length({shot_frames(:).name})
            need_ext_flag = false;
        end
    else
        mkdir(frame_dir);
    end
    
    if need_ext_flag
        cmd_line = sprintf('ffmpeg -ss %s -i %s -vframes %d -r %d %s/%s',...
            start_time, clip_filename, ext_frame_num, sampling_hz,...
            frame_dir, [start_time '_%06d.png']);
       % disp(cmd_line);
       % unix(cmd_line);
        unix([cmd_line,'>/dev/null 2>&1']);
        
        list_file = fullfile(frame_dir,'frames.txt');
        list_fid=fopen(list_file,'w');
        for j=1:ext_frame_num
            assert(exist(sprintf('%s/%s_%06d.png',frame_dir,start_time,j),'file')~=0);
            fprintf(list_fid,'%s_%06d.png\n',start_time,j);
        end
        fclose(list_fid);
    end
    used_time = toc;
    tip = sprintf('%7d %9s %9.1f %13d %.0f\n', i, shot_name, frame_num, ext_frame_num, used_time);
    
    fprintf(tip);
    fprintf(log_fid,tip);
    total_frame_num = total_frame_num + frame_num;
    total_ext_frame_num = ext_frame_num + total_ext_frame_num;
    total_time=total_time+used_time;
    total_duration = total_duration + duration;
end
fprintf(log_fid,'total_ext_frame_num %d total_frame_num %d total_duration %d total_time %.0fsec', ...
    total_ext_frame_num, total_frame_num,total_duration, total_time);
fclose(log_fid);

function [matTimeStr,frame_rate] = convertTime(mpg7TimeStr)
sps = strfind(mpg7TimeStr,'T');
mps = strfind(mpg7TimeStr,':');
mps = mps(end);
eps = strfind(mpg7TimeStr,'F');
frame_rate = str2double(mpg7TimeStr(eps+1:end));
fract = str2double(mpg7TimeStr(mps+1:eps-1))/frame_rate;
if fract == 0
    fract = '0.0';
else
    fract = num2str(fract);
end
matTimeStr = [mpg7TimeStr(sps+1:mps-1) fract(2:end)];


%function theStruct = parseXML(filename)
%% PARSEXML Convert XML file to a MATLAB structure.
%try
%   tree = xmlread(filename);
%catch
%   error('Failed to read XML file %s.',filename);
%end
%
%% Recurse over child nodes. This could run into problems 
%% with very deeply nested trees.
%try
%   theStruct = parseChildNodes(tree);
%catch
%   error('Unable to parse XML file %s.',filename);
%end
%
%function removeIndentNodes( childNodes )
%
%numNodes = childNodes.getLength;
%remList = [];
%for i = numNodes:-1:1
%   theChild = childNodes.item(i-1);
%   if (theChild.hasChildNodes)
%      removeIndentNodes(theChild.getChildNodes);
%   else
%      if ( theChild.getNodeType == theChild.TEXT_NODE && ...
%           ~isempty(char(theChild.getData()))         && ...
%           all(isspace(char(theChild.getData()))))
%         remList(end+1) = i-1; % java indexing
%      end
%   end
%end
%for i = 1:length(remList)
%   childNodes.removeChild(childNodes.item(remList(i)));
%end
%
%% ----- Local function PARSECHILDNODES -----
%function children = parseChildNodes(theNode)
%% Recurse over node children.
%children = [];
%if theNode.hasChildNodes
%   childNodes = theNode.getChildNodes;
%   %removeIndentNodes(childNodes);
%   numChildNodes = childNodes.getLength;
%   allocCell = cell(1, numChildNodes);
%
%   children = struct(             ...
%      'Name', allocCell, 'Attributes', allocCell,    ...
%      'Data', allocCell, 'Children', allocCell);
%
%    for count = 1:numChildNodes
%        theChild = childNodes.item(count-1);
%        children(count) = makeStructFromNode(theChild);
%    end
%end
%
%% ----- Local function MAKESTRUCTFROMNODE -----
%function nodeStruct = makeStructFromNode(theNode)
%% Create structure of node info.
%
%nodeStruct = struct(                        ...
%   'Name', char(theNode.getNodeName),       ...
%   'Attributes', parseAttributes(theNode),  ...
%   'Data', '',                              ...
%   'Children', parseChildNodes(theNode));
%
%if any(strcmp(methods(theNode), 'getData'))
%   nodeStruct.Data = char(theNode.getData); 
%else
%   nodeStruct.Data = '';
%end
%
%% ----- Local function PARSEATTRIBUTES -----
%function attributes = parseAttributes(theNode)
%% Create attributes structure.
%
%attributes = [];
%if theNode.hasAttributes
%   theAttributes = theNode.getAttributes;
%   numAttributes = theAttributes.getLength;
%   allocCell = cell(1, numAttributes);
%   attributes = struct('Name', allocCell, 'Value', ...
%                       allocCell);
%
%   for count = 1:numAttributes
%      attrib = theAttributes.item(count-1);
%      attributes(count).Name = char(attrib.getName);
%      attributes(count).Value = char(attrib.getValue);
%   end
%end
