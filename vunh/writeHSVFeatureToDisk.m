% This function write a bunch of hsv feature to disk

function writeHSVFeatureToDisk (hsvFeatures, maxFrameInDir, dirParam, curFolderID, keyframeIDs)
% Constant parameters
KFListFileName = 'KFList.dat';
HSVFileName = 'HSV.dat';

curListFileID = [];
curHSVPath;
curHSVMatrix = [];
nWrittenFrame = 0;

if (curFolderID == 0)
    % Create new folder
    curFolderID = curFolderID + 1;
    newFolderPath = fullfile(dirParam.baseResultDir, ['F' num2str(curFolderID)]);
    mkdir(newFolderPath);
end
    
for iFrame = 1:nFrame
    if (isempty(curListFileID))
        % Open file of kf list
        fileListPath = fullfile(dirParam.baseResultDir, ['F' num2str(curFolderID)], KFListFileName);
        nWrittenFrame = countLines(fileListPath);
        
        curListFileID = fopen(fileListPath, 'a');
        
        % Load existing HSV features
        curHSVMatrix = [];
        curHSVPath = fullfile(dirParam.baseResultDir, ['F' num2str(curFolderID)], HSVFileName);
        if (exist(curHSVPath) ~= 0)
            curHSVMatrix = readMatrixFromFile(curHSVPath);
        end
    end
    
    if (nWrittenFrame == maxFrameInDir)
        % close and save openning files 
        if (~isempty(curListFileID))
            fclose(curListFileID);
            curListFileID = [];
        end
        if (~isempty(curHSVMatrix))
            save(curHSVPath, 'curHSVMatrix');
            curHSVMatrix = [];
        end
        
        % Create new folder
        curFolderID = curFolderID + 1;
        newFolderPath = fullfile(dirParam.baseResultDir, ['F' num2str(curFolderID)]);
        mkdir(newFolderPath);
        
        
    end
    
    
    % Go to the end of the file
    % Not
    
    % Add new frame IDs
    fprintf(curListFileID, [keyframeIDs{iFrame} '\n']);
    
    % Add new extracted features
    curHSVMatrix = [curHSVMatrix; hsvFeatures(iFrame, :)];
    
    nWrittenFrame = nWrittenFrame + 1;
    
end
% close openning files
% Not

end



function res = readMatrixFromFile(fileName)
% Variable name in the file
% is x
c = load(fileName);
res = c.curHSVMatrix;
end