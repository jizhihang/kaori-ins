<?php

/**
 * 		@file 	ksc-BOW-Quantization-SelectKeyPointsForClustering.php
 * 		@brief 	Selecting keypoints for clustering.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 14 Aug 2013.
 */

//*** Update Aug 14, 2013
// --> just copied from kaori-sin13
// --> changed: $arPatList = array("subtest2012-new");

//////////////// HOW TO CUSTOMIZE ////////////////

// Fixed param:
//$nMaxKeyPoints = intval(1500000.0);  // 1.5 M - max keypoints for clustering
//$nAveKeyPointsPerKF = 1000; // average number of keypoints per key frame
//$fKeyPointSamplingRate = 0.70; // percentage of keypoints of one image will be selected
//$fVideoSamplingRate = 1.0; // to ensure all videos are used for selection
//$fKeyFrameSamplingRate = 0.000001; // to ensure only 1 KF/shot

//--> Only ONE param: $fShotSamplingRate
// Input: Number of videos, Number of shots per video - If no shot case, it is the Number of KFs
// Estimation
// Max KeyFrames = 1.5M / (1000 * 0.7) = 2K KF
// Number of videos = 200 --> Number of KF per video ($fVideoSamplingRate = 1.0) = 2K / 200 = 10 (QUOTA)
// Number of shots per video (by parsing .RKF) ~ 400K (of devel set 2012) /200 (videos - new organization) = 2K
// Number of KF per shot ~ 1KF
// --> if ($fShotSamplingRate = 0.01) --> 2K * 0.01 = 20 (10 (QUOTA))

//////////////////////////////////////////////////

/////////////// IMPORTANT PARAMS ////////////////////

// FIX Max Keypoints Per Frame is 1,000

/*
$nMaxKeyPoints = intval(1500000.0);  // 1.5 M - max keypoints for clustering

$nAveKeyPointsPerKF = 1000; // average number of keypoints per key frame
$fKeyPointSamplingRate = 0.70; // percentage of keypoints of one image will be selected
$nMaxKeyFrames = intval($nMaxKeyPoints/($nAveKeyPointsPerKF*$fKeyPointSamplingRate)); // max keyframes to be selected for picking keypoints

$fVideoSamplingRate = 0.50; // percentage of videos of the set will be selected, for ImageNet, this value should be 1.0
$fShotSamplingRate = 0.2; // lower this value if we want more videos, percentage of shots of one video will be selected
$fKeyFrameSamplingRate = 0.0001; // i.e. 1KF/shot - $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFsPerShot));

$nMaxBlocksPerChunk=1; // only one chunk
$nMaxSamplesPerBlock=2000000; // larger than maxKP to ensure all keypoints in 1 chunk-block
*/
///////////////////////////////////////////////////////


/************* STEPS FOR BOW MODEL ***************
 * 	===> STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV10.php --> select keypoints from devel pat
* 	STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV10.php --> do clustering using VLFEAT vl_kmeans, L2 distance
* 	STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
* 	STEP 4: nsc-ComputeAssignmentSash-TV10/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
* 	STEP 5: nsc-ComputeSoftBOW-Grid-TV10/-SGE.php --> compute soft assignment for grid image
*/

/////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

///////////////////////////// THIS PART FOR CUSTOMIZATION /////////////////

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

// training pat
$arPatList = array("subtest2012-new"); //*** CHANGED ***

$arFeatureList = array("nsc.raw.harhes.sift",
		"nsc.raw.harlap.sift",
		"nsc.raw.heslap.sift",
		//						"nsc.raw.hesaff.sift",
		"nsc.raw.haraff.sift",
		"nsc.raw.dense4.sift",
		"nsc.raw.dense6.sift",
		"nsc.raw.dense8.sift",
		//						"nsc.raw.dense10.sift",
		"nsc.raw.phow6.sift",
		"nsc.raw.phow8.sift",
		"nsc.raw.phow10.sift",
		"nsc.raw.phow12.sift",
		//						"nsc.raw.phow14.sift",
		"nsc.raw.dense6mul.oppsift",
		"nsc.raw.dense6mul.sift",
		"nsc.raw.dense6mul.rgsift",
		"nsc.raw.dense6mul.rgbsift",
		"nsc.raw.dense6mul.csift",

		"nsc.raw.dense4mul.oppsift",
		"nsc.raw.dense4mul.sift",
		"nsc.raw.dense4mul.rgsift",
		"nsc.raw.dense4mul.rgbsift",
		"nsc.raw.dense4mul.csift",
);

$szDevPatName = "subtest2012-new";  // must be a member of $arPatList
$szTargetFeatureExt = "nsc.raw.dense4.sift";

/// !!! IMPORTANT
$nMaxKeyPoints = intval(1500000.0);  // 1.5 M - max keypoints for clustering

// average number of keypoints per key frame --> used in function loadOneRawSIFTFile
$nAveKeyPointsPerKF = 1000; 
$fKeyPointSamplingRate = 0.70; // percentage of keypoints of one image will be selected

// max keyframes to be selected for picking keypoints
// use weight = 1.5 to pick more number of keyframes to ensure min selected KP = $nMaxKeyPoints
// some keyframes --> no keypoints (ie. blank/black frames)
$nMaxKeyFrames = intval(1.5 * $nMaxKeyPoints/($nAveKeyPointsPerKF*$fKeyPointSamplingRate))+1; 

// shot information can not be inferred from keyframeID --> one shot = one keyframes
$fVideoSamplingRate = 1.0; // percentage of videos of the set will be selected
$fKeyFrameSamplingRate = 0.00001; // i.e. 1KF/shot - $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFsPerShot));

// *** CHANGED ***
$fShotSamplingRate = 1.0; // lower this value if we want more videos, percentage of shots of one video will be selected

$nMaxBlocksPerChunk=1; // only one chunk
$nMaxSamplesPerBlock= $nMaxKeyPoints*2; // larger than maxKP to ensure all keypoints in 1 chunk-block

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////

if($argc != 3)
{
	printf("Usage: %s <DevPatName> <RawFeatureExt>\n", $argv[0]);
	printf("Usage: %s %s %s\n", $argv[0], $szDevPatName, $szTargetFeatureExt);
	exit();
}

$szDevPatName = $argv[1];
$szTargetFeatureExt = $argv[2];

// Re-calculate $fShotSamplingRate
$nMaxVideos = $arMaxVideosPerPatList[$szDevPatName];
$nMaxKFPerVideo = intval($nMaxKeyFrames/$nMaxVideos)+1;
// if we set 1KF/shot --> $nMaxKFPerVideo = $nMaxShotPerVideo
$fShotSamplingRate = $nMaxKFPerVideo/$nAveShotPerVideo; 
printf("### Shot sampling rate: %f\n", $fShotSamplingRate);

$szFPLogFN = sprintf("ksc-BOW-Quantization-SelectKeypointsForClustering-%s.log", $szTargetFeatureExt); // *** CHANGED ***

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]",
		$szStartTime,
		$argv[1], $argv[2]);

$arLog[] = sprintf("###Max KeyFrames to Select: [%s] - Max Videos: [%s]- Max KF Per Video: [%s] - Shot Sampling Rate: [%s]",
		$nMaxKeyFrames , $nMaxVideos, $nMaxKFPerVideo, $fShotSamplingRate);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

/// !!! IMPORTANT
//$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szTargetFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);

// Update Nov 25, 2011
$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig
$szTmpDir = sprintf("%s/SelectKeyPointForClustering/%s", $szLocalTmpDir, $szTargetFeatureExt);
makeDir($szTmpDir);

foreach($arPatList as $szPatName)
{
	if($szDevPatName != $szPatName)
	{
		printf("Skipping [%s] ...\n", $szPatName);
		continue;
	}
	$szFPVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szPatName);

	foreach($arFeatureList as $szFeatureExt)
	{
		if($szTargetFeatureExt != $szFeatureExt)
		{
			printf("Skipping [%s] ...\n", $szFeatureExt);
			continue;
		}
		$szOutputDir = sprintf("%s/bow.codebook.%s.%s/%s/data", $szRootFeatureDir, $szTrialName, $szPatName, $szFeatureExt);
		makeDir($szOutputDir);
		$szDataPrefix = sprintf("%s.%s.%s", $szTrialName, $szPatName, $szFeatureExt);
		$szDataExt = "dvf";

		$arAllKeyFrameList = selectKeyFrames($nMaxKeyFrames,
				$fVideoSamplingRate, $fShotSamplingRate,
				$fKeyFrameSamplingRate,
				$szFPVideoListFN, $szRootMetaDataDir, $szFeatureExt);
			
		$szLocalTmpDir = sprintf("%s/%s", $szTmpDir, $szPatName);
		makeDir($szLocalTmpDir);

		$szFPInputListFN = sprintf("%s/BoW.SelKeyFrame.%s.%s.%s.lst", $szOutputDir, $szTrialName, $szPatName, $szFeatureExt);
//		saveDataFromMem2File(array_keys($arAllKeyFrameList), $szFPInputListFN);

		// if not use shuffle_assoc, keyframes in the bottom list might not be selected due to limit of max keypoints
		shuffle_assoc($arAllKeyFrameList);
		saveDataFromMem2File($arAllKeyFrameList, $szFPInputListFN);

		// print stats
		global $arStatVideoList;
		$nCountzz = 1;
		ksort($arStatVideoList);
		$arOutput = array();
		foreach($arStatVideoList as $szVideoID => $arKFList)
		{
			$arOutput[] = sprintf("###%d. %s, %s", $nCountzz, $szVideoID, sizeof($arKFList));
			$nCountzz++;
		}
		$szFPOutputStatFN = sprintf("%s.csv", $szFPInputListFN);
		saveDataFromMem2File($arOutput, $szFPOutputStatFN);
		
		
		selectKeyPointsFromKeyFrameList($szOutputDir, $szDataPrefix, $szDataExt,
				$szFPInputListFN, $szFPVideoListFN,
				$szFeatureExt, $szRootFeatureDir, $szLocalTmpDir, $fKeyPointSamplingRate, $nMaxKeyPoints,
				$nMaxBlocksPerChunk, $nMaxSamplesPerBlock);
	}
}

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]",
		$szStartTime, $szFinishTime,
		$argv[1], $argv[2]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");


////////////////////////////////////// FUNCTIONS //////////////////////////
/**
 * 	Select keypoints from a set of images for clustering (to form codebook).
 *
 * 	Selection params:
 * 		+ nMaxKeyFrames (default 2,000):
 * 		+ fVideoSamplingRate (default 1.0): percentage of videos of the set will be selected
 * 		+ fShotSamplingRate (default 1.0): percentage of shots of one video will be selected
 * 			==> if no shot (such as imageclef, imagenet) --> one shot = one keyframe
 * 			==> if KeyFrameID does not have .RKF --> consider as no shot.
 * 		+ $fKeyFrameSamplingRate (default 1/50): percentage of keyframes per shot
 * 			==> if no shot --> only 1 KF/shot is picked no matter what $fKeyFrameSamplingRate
 * 		+ fKeyPointSamplingRate (default: 0.75): percentage of keypoints of one image will be selected
 */

// szFPVideoListFN --> arVideoList[videoID] = videoPath
// RootMetaData + videoPath + /videoID.prg
// RootFeatureDir + FeatureExt + videoPath + /videoID.featureExt (.tar.gz)
function selectKeyFrames($nMaxKeyFrames,
		$fVideoSamplingRate=1.0, $fShotSamplingRate=1.0,
		$fKeyFrameSamplingRate,
		$szFPVideoListFN, $szRootMetaDataDir, $szFeatureExt)
{
	global $arStatVideoList;
	$arStatVideoList = array(); // for statistics 
	
	// load video list
	loadListFile($arRawList, $szFPVideoListFN);
	$arVideoList = array();
	foreach($arRawList as $szLine)
	{
		// TRECVID2005_141 #$# 20041030_133100_MSNBC_MSNBCNEWS13_ENG #$# tv2005/devel
		$arTmp = explode("#$#", $szLine);
		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);
		$arVideoList[$szVideoID] = $szVideoPath;
	}

	$nTotalVideos = sizeof($arVideoList);
	$nNumSelVideos = intval(max(1, $fVideoSamplingRate*$nTotalVideos));

	$arAllKeyFrameList = array();

	$arSelVideoList = array();
	if($nNumSelVideos < 2)
	{
		$arSelVideoList[] = array_rand($arVideoList, $nNumSelVideos);
	}
	else
	{
		$arSelVideoList = array_rand($arVideoList, $nNumSelVideos);
	}

	shuffle($arSelVideoList);
	print_r($arSelVideoList);
	$nFinish = 0;
	foreach($arSelVideoList as $szVideoID)
	{
		$szVideoPath = $arVideoList[$szVideoID];

		$szFPKeyFrameListFN = sprintf("%s/%s/%s.prg",
				$szRootMetaDataDir, $szVideoPath, $szVideoID);

		if(!file_exists($szFPKeyFrameListFN))
		{
			printf("#@@@# File [%s] not found!", $szFPKeyFrameListFN);
			continue;
		}

		loadListFile($arKFRawList, $szFPKeyFrameListFN);

		$arShotList = array();
		foreach($arKFRawList as $szKeyFrameID)
		{
			$arTmp = explode(".RKF", $szKeyFrameID);
			$szShotID = trim($arTmp[0]);

			// If there is no .RKF --> $szShotID = $szKeyFrameID
			$arShotList[$szShotID][$szKeyFrameID] = 1;
		}
		$nNumShots = sizeof($arShotList);
		$nNumSelShots = intval(max(1, $fShotSamplingRate*$nNumShots));

		$arSelShotList = array();
		if($nNumSelShots<2)
		{
			$arSelShotList[] = array_rand($arShotList, $nNumSelShots);
		}
		else
		{
			$arSelShotList = array_rand($arShotList, $nNumSelShots);
		}

		shuffle($arSelShotList);
		print_r($arSelShotList);

		foreach($arSelShotList as $szShotID)
		{
			$arKeyFrameList = $arShotList[$szShotID];
			$nNumKFs = sizeof($arKeyFrameList);

			$nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFs));
			$arSelKeyFrameList = array();
			if($nNumSelKFs < 2)
			{
				$arSelKeyFrameList[] = array_rand($arKeyFrameList, $nNumSelKFs);
			}
			else
			{
				$arSelKeyFrameList = array_rand($arKeyFrameList, $nNumSelKFs);
			}
				
			shuffle($arSelKeyFrameList);
			// print_r($arSelKeyFrameList); exit();
			foreach($arSelKeyFrameList as $szKeyFrameID)
			{
				// printf("###. %s\n", $szKeyFrameID);
			// $arAllKeyFrameList[$szKeyFrameID] = 1;
				
				//*** Changed for IMAGENET
				$arAllKeyFrameList[$szKeyFrameID] = 
					sprintf("%s #$# %s #$# %s", $szKeyFrameID, $szVideoID, $szShotID);
				//*** Changed for IMAGENET
				$arStatVideoList[$szVideoID][$szKeyFrameID] = 1;
		
				if(sizeof($arAllKeyFrameList) >= $nMaxKeyFrames )
				{
					$nFinish = 1;
					break; // keyframe selection
				}
			}
			if($nFinish)
			{
				break; // shot selection
			}
		}

		if($nFinish)
		{
			break; // video selection
		}
	}
	
	// print_r($arAllKeyFrameList); exit();
	return $arAllKeyFrameList;
}


function loadOneRawSIFTFile($szFPSIFTDataFN, $fKPSamplingRate=0.5, $szAnnPrefix = "")
{
	global $nAveKeyPointsPerKF;
	$nMaxKeyPointsPerKF = $nAveKeyPointsPerKF;  // 1000

	loadListFile($arRawList, $szFPSIFTDataFN);

	$nCount = 0;
	// print_r($arRawList);
	$arOutput = array();
	foreach($arRawList as $szLine)
	{
		// printf("%s\n", $szLine);
		// first row - numDims 128
		if($nCount == 0)
		{
			$nNumDims = intval($szLine);
			$nCount++;
			continue;
		}

		// second row  - numKPs
		if($nCount == 1)
		{
			$nNumKeyPoints = intval($szLine);

			$nNumSelKeyPoints = min($nMaxKeyPointsPerKF, intval($fKPSamplingRate*$nNumKeyPoints));
				
			$arIndexList = range(0, $nNumKeyPoints-1);
			$arSelIndexList = array_rand($arIndexList, $nNumSelKeyPoints);

			//if($nNumKeyPoints+2 != sizeof($arRawList))
			if($nNumKeyPoints+2 < sizeof($arRawList))
			{
				printf("Error in SIFT data file. Size different [%d KPs - %d Rows]\n", $nNumKeyPoints, sizeof($arRawList)-2);
				exit();
			}

			$nCount++;
			continue;
		}

		if(!in_array($nCount, $arSelIndexList))
		{
			$nCount++;
			continue;
		}
		$arTmp = explode(" ", $szLine);
		// 5 first values - x y a b c
		if(sizeof($arTmp) != $nNumDims + 5)
		{
			printf("Error in SIFT data file. Feature value different [%d Dims - %d Vals]\n", $nNumDims, sizeof($arTmp)-5);
			print_r($arTmp);
			exit();
		}

		$szOutput = sprintf("%s", $nNumDims);
		for($i=0; $i<$nNumDims; $i++)
		{
			$nIndex = $i+5;

			$szOutput = $szOutput . " " . trim($arTmp[$nIndex]);

		}
		$szAnn = sprintf("%s-KP-%06d", $szAnnPrefix, $nCount-2);
		$arOutput [] = $szOutput . " % " . $szAnn;
		$nCount++;
	}

	return $arOutput;
}


// NEW VERSION --> split samples into chunks and blocks in dvf format
// New params: DataExt (dvf), DataPrefix and OutputDir
function selectKeyPointsFromKeyFrameList($szOutputDir, $szDataPrefix, $szDataExt,
		$szFPInputListFN, $szFPVideoListFN,
		$szFeatureExt, $szRootFeatureDir, $szLocalDir, $fKPSamplingRate=0.7, $nMaxKeyPoints=1500000,
		$nMaxBlocksPerChunk=1, $nMaxSamplesPerBlock=2000000)
{
	// load video list
	loadListFile($arRawList, $szFPVideoListFN);
	$arVideoList = array();
	foreach($arRawList as $szLine)
	{
		// TRECVID2005_141 #$# 20041030_133100_MSNBC_MSNBCNEWS13_ENG #$# tv2005/devel
		$arTmp = explode("#$#", $szLine);
		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);
		$arVideoList[$szVideoID] = $szVideoPath;
	}
	//print_r($arVideoList); exit();
	
	loadListFile($arKeyFrameList, $szFPInputListFN);

	$nBlockID = 0;
	$nChunkID = 0;

	$szFPDataOutputFN = sprintf("%s/%s-c%d-b%d.%s", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID, $szDataExt);
	$szFPAnnOutputFN = sprintf("%s/%s-c%d-b%d.ann", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID);

	$arKeyPointFeatureList = array();
	$arKeyPointFeatureList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
	$arKeyPointFeatureList[1] = sprintf("%s", $nMaxSamplesPerBlock); //  estimated number of samples

	$arAnnList = array();
	$arAnnList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
	$arAnnList[1] = sprintf("%s", $nMaxSamplesPerBlock); //  estimated number of samples

	$nNumKPs = 0;
	//foreach($arKeyFrameList as $szKeyFrameID)

	//*** Changed for IMAGENET
	foreach($arKeyFrameList as $szLine)
	//*** Changed for IMAGENET
	{
		//*** Changed for IMAGENET
		$arTmp = explode("#$#", $szLine);
		$szKeyFrameID = trim($arTmp[0]);
		$szVideoID = trim($arTmp[1]);
		$szVideoPath = $arVideoList[$szVideoID];
		//*** Changed for IMAGENET
		$szInputDir = sprintf("%s/%s/%s/%s",
				$szRootFeatureDir, $szFeatureExt, $szVideoPath, $szVideoID);
		$szCoreName = sprintf("%s.%s", $szKeyFrameID, $szFeatureExt);
		$szFPTarKeyPointFN = sprintf("%s/%s.tar.gz",
				$szInputDir, $szCoreName);
		if(file_exists($szFPTarKeyPointFN))
		{
			//printf("[%s]. OK\n", $szFPTarKeyPointFN);

			$szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTarKeyPointFN, $szLocalDir);
			execSysCmd($szCmdLine);

			$szFPSIFTDataFN = sprintf("%s/%s", $szLocalDir, $szCoreName);

			$szAnnPrefix = sprintf("NA %s %s", $szKeyFrameID, $szKeyFrameID);
			$arOutput = loadOneRawSIFTFile($szFPSIFTDataFN, $fKPSamplingRate, $szAnnPrefix);

			//		print_r($arOutput);
			//		break;

			//$arKeyPointFeatureList = array_merge($arKeyPointFeatureList, $arOutput);
				
			// split to feature and ann
			foreach($arOutput as $szLine)
			{
				$arTmpzzz = explode("%", $szLine);

				$arKeyPointFeatureList[] = trim($arTmpzzz[0]);
				$arAnnList[] = trim($arTmpzzz[1]);
			}

			$nNumSelKPs = sizeof($arOutput);
			$nNumKPs += $nNumSelKPs;
			printf("### Total keypoints [%s] collected after adding [%s] keypoints\n", $nNumKPs, sizeof($arOutput));
				
			// log
			global $szFPLogFN;
			$arLog = array();
			$arLog[] = sprintf("###[%s] - NumKF: %s. Total: %s", $szKeyFrameID, $nNumSelKPs, $nNumKPs);
			
			saveDataFromMem2File($arLog, $szFPLogFN, "a+t");
				
			$arOutput = array();
			deleteFile($szFPSIFTDataFN);
				
			if($nNumKPs >= $nMaxKeyPoints)
			{
				printf("### Reach the limit [%s]. Break\n", $nMaxKeyPoints);
				break;
			}

			// -2 because 2 rows are for comment line and number of samples
			$nNumSamplesInBlock = sizeof($arKeyPointFeatureList)-2;
			if($nNumSamplesInBlock >= $nMaxSamplesPerBlock)
			{
				printf("@@@Writing output ...\n");
				$arKeyPointFeatureList[1] = sprintf("%s", $nNumSamplesInBlock); // update the number of samples of the block
				saveDataFromMem2File($arKeyPointFeatureList, $szFPDataOutputFN, "wt");

				$arAnnList[1] = sprintf("%s", $nNumSamplesInBlock);
				saveDataFromMem2File($arAnnList, $szFPAnnOutputFN, "wt");

				// prepare for the new chunk-block
				$nBlockID++;
				if($nBlockID >= $nMaxBlocksPerChunk)
				{
					// new chunk
					$nBlockID = 0;
					$nChunkID++;
				}

				$szFPDataOutputFN = sprintf("%s/%s-c%d-b%d.%s", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID, $szDataExt);
				$szFPAnnOutputFN = sprintf("%s/%s-c%d-b%d.ann", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID);

				$arKeyPointFeatureList = array();
				$arKeyPointFeatureList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
				$arKeyPointFeatureList[1] = sprintf("%s", $nMaxSamplesPerBlock); // estimated number of samples

				$arAnnList = array();
				$arAnnList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
				$arAnnList[1] = sprintf("%s", $nMaxSamplesPerBlock); //  estimated number of samples

			}
		}
		else
		{
			printf("[%s]. NO OK\n", $szFPTarKeyPointFN);
		}
	}

	$nNumSamplesInBlock = sizeof($arKeyPointFeatureList)-2;
	if($nNumSamplesInBlock)
	{
		$arKeyPointFeatureList[1] = sprintf("%s", $nNumSamplesInBlock); // update the number of samples of the block
		saveDataFromMem2File($arKeyPointFeatureList, $szFPDataOutputFN, "wt");

		$arAnnList[1] = sprintf("%s", $nNumSamplesInBlock);
		saveDataFromMem2File($arAnnList, $szFPAnnOutputFN, "wt");
	}
}


?>