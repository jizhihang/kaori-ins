%% This function extracts features of images in the output ranked file

% Un-implemented functions
% parseFrameDir
% retrieveKFAbsolutePath
% nextFeatureFolder
% writeParamFile

function extractFeatureForHSV ()
% Constant parameters
paramFileName = 'param.xml';
featureListFileName = 'featureList.dat';
KFListFileName = 'KFList.dat';
HSVFileName = 'HSV.mat';
MSCRFileName = 'MSCR.mat';
FeatureFolderPrefix = 'F';


% Directory parameters
keyframeDir = '';
rankedDir = '';
baseResultDir = '';
processedKFFile = '';
RankedFilePrefix = 'TRECVID2012_';

% Configuration parameters
maxFrameInDir = 1000;           % Each feature folder could store features for at most 1000 frames
nBufferFrame = 1000;            % After every nBufferFrame frames are processed, they are write to disk and begin with other nBufferFrame frames
nDPMComponent = 1;

% HSV hist parameters 
NBINs   = [16,16,4]; % hsv quantization

% MSCR parameters
parMSCR.min_margin	= 0.003; %  0.0015;  % Set margin parameter
parMSCR.ainc		= 1.05;
parMSCR.min_size	= 15;
parMSCR.filter_size	= 3;
parMSCR.verbosefl	= 0;  % Uncomment this line to suppress text output

% Form parameters for features extraction
hsvParam;
    hsvParam.onMask = 0;
    hsvParam.nBin = NBINs;
mscrParam;
    mscrParam.onMask = 0;
    mscrParam.parMSCR = parMSCR;
    
    
%% Check existance of pre-built feature files
featureListFilePath = fullfile(baseResultDir, featureListFileName);
if (exist(featureListFilePath) == 0)        % There is no featureList.prg file
    % Create parameter file
    paramPath = fullfile(baseResultDir, paramFileName);
    writeParamFile(paramPath);
    
    % Write featureList.prg file
    featureListFileID = fopen(featureListFilePath, 'w');
    fclose(featureListFileID);
    
end

%% Load processed keyframes --> Save to variable 'processedKF'
processedKF = containers.Map;
iProcessedKF = 1;
featureListFileID = fopen(featureListFilePath);
line1 = fscanf(featureListFileID, '%s', [1]);
nFeatureFolder = 0;                                 % Number of feature folders Eg. F1, F2, F3 --> 3
while (~isempty(line1))
    nFeatureFolder = nFeatureFolder + 1;
    KFListFileID = fopen(fullfile(baseResultDir, line1, KFListFileName));
    
    line2 = fscanf(KFListFileID, '%s', [1]);
    while (~isempty(line2))
        processedKF(line2) = iProcessedKF;
        iProcessedKF = iProcessedKF + 1;
        line2 = fscanf(KFListFileID, '%s', [1]);
    end
    
    fclose(KFListFileID);
    
    line1 = fscanf(featureListFileID, '%s', [1]);
end
fclose(featureListFileID);


%% Read ranked file and extract feature
iRankedFile = 1;
rankedFilePath = fullfile(rankedDir, [RankedFilePrefix num2str(iRankedFile)]);
keyframeIDs = {};
boundingBoxes = [];
while (exist(rankedFilePath) ~= 0)
    rankedFileID = fopen(rankedFilePath);
    line = fscanf(rankedFileID, '%s', [1]);
    
    while (~isempty(line))
        % Parse frame dir
        %frameID = parseFrameDir(line);
        [frameID, x1, y1, x2, y2] = parseRankedFileLine(line);

        if (~processedKF.isKey(frameID))
            % read img from frame dir if it is not processed yet
            keyframeIDs = [keyframeIDs; {frameID}];
            boundingBoxes = [boundingBoxes; [x1, y1, x2, y2]];
        end

        line = fscanf(rankedFileID, '%s', [1]);
    end
    fclose(rankedFileID);
    
    iRankedFile = iRankedFile + 1
    rankedFilePath = fullfile(rankedDir, [RankedFilePrefix num2str(iRankedFile)]);
end

%% Extract features for frames
nFrame = size(keyframeIDs, 1);

for iFrame = 1:nBufferFrame:nFrame

    
    upperFrameBound = min(nFrame, iFrame + nBufferFrame - 1);
    nBufferFrame = upperFrameBound - iFrame + 1;
    hsvFeatures = cell(upperFrameBound - iFrame + 1, 1);
    %mscrFeatures = cell(nFrame, 1);
    parfor iSubFrame = 1:nBufferFrame
        absoluteIndex = iSubFrame + iFrame - 1;
        frameDir = retrieveKFAbsolutePath (keyframeIDs{absoluteIndex});
        img = imread(frameDir);
        box = boundingBoxes(absoluteIndex,:);

        hsvFea = extractFeature_HSV(hsvParam, img, box);       % Not
        %mscrFea = extractFeature_MSCR(mscrParam, img);     % Not
        hsvFeatures{iSubFrame} = hsvFea;
        %mscrFeatures{iFeature} = mscrFea;
    end
    
    % When there are enough nBufferFrame frames processed, write to disk
    writeHSVFeatureToDisk (hsvFeatures);
    %writeMSCRFeatureToDisk (mscrFeatures);
end


end

