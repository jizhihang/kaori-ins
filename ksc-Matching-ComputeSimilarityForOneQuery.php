<?php 
/**
 * 		@file 	ksc-Matching-ComputeSimilarityForOneQuery.php
 * 		@brief 	Compute similarity between one query and keyframes in the database
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 17 Aug 2013.
 */

require_once "ksc-AppConfig.php";

/*
http://stackoverflow.com/questions/178328/in-php-5-0-is-passing-by-reference-fasterConclusions --> CONFIRMED by me

Pass the parameter by value is always faster

If the function change the value of the variable passed, for practical purposes is the same as pass by reference than by value
*/

// L1NormL1Dist --> improve dense6mul.sift from 7.91 to 12.51 (MAP)

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);
$szRootResultDir =  sprintf("%s/result", $szRootDir); //TO DO

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////
$szQueryID =  "9048";
$szQueryPatName = "queryext2012-new";
$szTestPatName = "subtest2012-new";
$szFeatureExt = "nsc.bow.dense6mul.sift.Soft-1000.subtest2012-new.norm1x1";
$nStartVideoID = 0;
$nEndVideoID = 1;

$nL1NormL1Dist = 1;

if($argc != 7)
{
    printf("Usage: %s <QueryID> <QueryPatName> <TestPatName> <FeatureExt> <StartVideoID> <EndVideoID>\n", $argv[0]);
    printf("Usage: %s %s %s %s %s %d %d\n", $argv[0], $szQueryID, $szQueryPatName, $szTestPatName, $szFeatureExt, $nStartVideoID, $nEndVideoID);
    exit();
}

$szQueryID = $argv[1];
$szQueryPatName = $argv[2];
$szTestPatName = $argv[3];
$szFeatureExt = $argv[4];
$nStartVideoID = intval($argv[5]);
$nEndVideoID = intval($argv[6]);

$szRunID = sprintf("run_%s_%s_%s_l1norm%d", $szQueryPatName, $szTestPatName, $szFeatureExt, $nL1NormL1Dist);

//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);
$szRootFeatureInputDir = $szRootFeatureDir;

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");

$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig

$szTmpDir = sprintf("%s/%s/%s/%s-%s-%s-%d-%d-norm%d", $szLocalTmpDir,  $szScriptBaseName,
		$szQueryPatName, $szTestPatName, $szQueryID, $szFeatureExt, $nStartVideoID, $nEndVideoID, $nL1NormL1Dist);
makeDir($szTmpDir);

$szFPTestVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szTestPatName);
$szFPQueryVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szQueryPatName);

computeSimilarityForOneQueryOnePat($szTmpDir, 
    $szRootMetaDataDir, $szRootFeatureInputDir, $szRootResultDir,
    $szRunID, $szQueryID, $szFeatureExt,
    $szFPTestVideoListFN, $szFPQueryVideoListFN, $nStartVideoID, $nEndVideoID);

///////////////////////////////// FUNCTIONS /////////////////////////////////

function computeSimilarityForOneQueryOnePat($szLocalDir, 
    $szRootMetaDataDir, $szRootFeatureInputDir, $szRootResultDir,
    $szRunID, $szQueryID, $szFeatureExt,
    $szFPTestVideoListFN, $szFPQueryVideoListFN, $nStartVideoID, $nEndVideoID)
{
	
    $arVideoPathList = array();
    
	loadListFile($arRawList, $szFPTestVideoListFN);

	foreach($arRawList as $szLine)
	{
		$arTmp = explode("#$#", $szLine);
		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);
		$arVideoPathList[$szVideoID] = $szVideoPath;
	}

	$nNumVideos = sizeof($arVideoPathList);
	if($nStartVideoID < 0)
	{
		$nStartVideoID = 0;
	}

	if($nEndVideoID <0 || $nEndVideoID>$nNumVideos)
	{
		$nEndVideoID = $nNumVideos;
	}

	$arVideoList = array_keys($arVideoPathList);

	for($i=$nStartVideoID; $i<$nEndVideoID; $i++)
	{
		$szVideoID = $arVideoList[$i];
		printf("###%d. Processing video [%s] ...\n", $i, $szVideoID);

		if(!isset($arVideoPathList[$szVideoID]))
		{
			continue;
		}
		$szVideoPath = $arVideoPathList[$szVideoID];
		

		// !!! IMPORTANT !!!
		$szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootMetaDataDir, $szVideoPath, $szVideoID);

		// specific for one video program
		$szLocalDir2 = sprintf("%s/%s", $szLocalDir, $szVideoID);
		makeDir($szLocalDir2);
		
		$szResultDir = sprintf("%s/%s/%s/%s", $szRootResultDir, $szRunID, $szVideoPath, $szQueryID);
		makeDir($szResultDir);
		$szFPOutputFN = sprintf("%s/%s.res", $szResultDir, $szVideoID);
		
		if(file_exists($szFPOutputFN))
		{
		    printf("### Skipping since the file exists ...\n");
		    continue;
		}
		computeSimilarityForOneQueryOneVideoProgram($szLocalDir2, 
		$szRootMetaDataDir, $szRootFeatureInputDir,
		$szVideoPath, $szVideoID, $szQueryID, $szFeatureExt,  
		$szFPKeyFrameListFN, $szFPQueryVideoListFN, $szFPOutputFN
		);
		
		// clean up
		$szCmdLine = sprintf("rm -rf %s", $szLocalDir2);
		execSysCmd($szCmdLine);
	}
}

function computeSimilarityForOneQueryOneVideoProgram($szLocalDir,
    $szRootMetaDataDir, $szRootFeatureInputDir,
	$szVideoPath, $szVideoID, $szQueryID, $szFeatureExt, 
    $szFPKeyFrameListFN, $szFPQueryVideoListFN, $szFPOutputFN
)
{
    $time_start = microtime(true);
    
    loadListFile($arRawList, $szFPQueryVideoListFN);
    $szQueryVideoPath = "";
    foreach($arRawList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        $szQueryIDz = trim($arTmp[0]);
        $szQueryVideoPathz = trim($arTmp[2]);
        
        if($szQueryIDz == $szQueryID)
        {
        	$szQueryVideoPath = $szQueryVideoPathz;
        	break;
        }
    }
    
    if($szQueryVideoPath == "")
    {
        exit("Error in QueryPath\n");    
    }
    
    $szFPQueryKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootMetaDataDir, $szQueryVideoPath, $szQueryID);
    loadListFile($arQueryKFList, $szFPQueryKeyFrameListFN);
    
    loadListFile($arTestKFList, $szFPKeyFrameListFN);

    // features of all keyframes in one video are packed into ONE file
    $szQueryFeatureCoreName = sprintf("%s.%s", $szQueryID, $szFeatureExt);
    $szFPQueryFeatureFN = sprintf("%s/%s/%s/%s.tar.gz", $szRootFeatureInputDir, $szFeatureExt,
    		$szQueryVideoPath, $szQueryFeatureCoreName);
    
    $szTestFeatureCoreName = sprintf("%s.%s", $szVideoID, $szFeatureExt);
    $szFPTestFeatureFN = sprintf("%s/%s/%s/%s.tar.gz", $szRootFeatureInputDir, $szFeatureExt, 
                $szVideoPath, $szTestFeatureCoreName);
        
    $szCmdLine = sprintf("tar -xvf %s -C %s", $szFPQueryFeatureFN, $szLocalDir);
    execSysCmd($szCmdLine);
        
    $szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTestFeatureFN, $szLocalDir);
    execSysCmd($szCmdLine);

    $szFPLocalQueryFeatureFN = sprintf("%s/%s", $szLocalDir, $szQueryFeatureCoreName);
    $szFPLocalTestFeatureFN = sprintf("%s/%s", $szLocalDir, $szTestFeatureCoreName);
    
    $arQueryFeature = loadOneSvfFeatureFile($szFPLocalQueryFeatureFN, $nKFIndex=2);
    $arTestFeature = loadOneSvfFeatureFile($szFPLocalTestFeatureFN, $nKFIndex=2);

    doL1Norm($arQueryFeature);
    doL1Norm($arTestFeature);
    
    deleteFile($szFPLocalQueryFeatureFN);
    deleteFile($szFPLocalTestFeatureFN);
    
    global $nL1NormL1Dist; 
    
    $nCount = 0;
    $nNumKeyFrames = sizeof($arTestKFList);
    printf("Performing matching for [%d] KF...[", $nNumKeyFrames);
    $arFinalOutput = array();
    foreach($arTestKFList as $szTestKeyFrameID)
    {
        foreach($arQueryKFList as $szQueryKeyFrameID)
        {
            if(!isset($arTestFeature[$szTestKeyFrameID]) || !isset($arQueryFeature[$szQueryKeyFrameID]))
            {
                continue; // IMPORTANT --> some cases, feature vector is not available
            }
            if($nL1NormL1Dist)
            {
                $fScore = computeL1NormL1Dist($arQueryFeature[$szQueryKeyFrameID], $arTestFeature[$szTestKeyFrameID]);
            }
            else
            {    
                $fScore = computeL2Dist($arQueryFeature[$szQueryKeyFrameID], $arTestFeature[$szTestKeyFrameID]);
            }
            //exit("Score - $fScore");
            
            $arFinalOutput[] = sprintf("%s #$# %s #$# %f", $szTestKeyFrameID, $szQueryKeyFrameID, $fScore);
            
            
        }
        
        if(($nCount % 100) == 0)
        {
            $time_end = microtime(true);
    
            printf("###Processing time [%s]: %0.2f. \n", $szVideoID, $time_end-$time_start);

            printf(".");
            
            $time_start = microtime(true);
        }
        $nCount++;
        
        
    }
    printf("]. Finish!\n");
    $time_end = microtime(true);
    
    printf("###Processing time [%s]: %0.2f. \n", $szVideoID, $time_end-$time_start);
    
    saveDataFromMem2File($arFinalOutput, $szFPOutputFN);
}

// passing by ref
function computeL2Dist($arFV1, $arFV2)
{
    $fScore = 0;
    $arKeys1 = array_keys($arFV1);
    $arKeys2 = array_keys($arFV2);
    $arKeys = array_unique(array_merge($arKeys1, $arKeys2));

//    printf("%d - %d - %d\n", sizeof($arFV1), sizeof($arFV2), sizeof($arKeys));

    $fScore = 0;
    foreach($arKeys as $szKey)
    {
        if(isset($arFV1[$szKey]))
        {
            if(isset($arFV2[$szKey]))
            {
                $fScore += pow($arFV1[$szKey]-$arFV2[$szKey], 2);
            }
            else 
            {
                $fScore += pow($arFV1[$szKey], 2);
            }
        }
        else 
        {
            if(isset($arFV2[$szKey]))
            {
            	$fScore += pow($arFV2[$szKey], 2);
            }           
        }
    }
    
    return -sqrt($fScore);
}


// data is already L1Norm
function computeL1NormL1Dist($arFV1, $arFV2)
{
    $fScore = 0;
    $arKeys1 = array_keys($arFV1);
    $arKeys2 = array_keys($arFV2);
    $arKeys = array_unique(array_merge($arKeys1, $arKeys2));

    //    printf("%d - %d - %d\n", sizeof($arFV1), sizeof($arFV2), sizeof($arKeys));

    $fScore = 0;
    foreach($arKeys as $szKey)
    {
        if(isset($arFV1[$szKey]))
        {
            if(isset($arFV2[$szKey]))
            {
                $fScore += abs($arFV1[$szKey]-$arFV2[$szKey]);
            }
            else
            {
                $fScore += $arFV1[$szKey];
            }
        }
        else
        {
            if(isset($arFV2[$szKey]))
            {
                $fScore += $arFV2[$szKey];
            }
        }
    }

    return -$fScore;
}

function doL1Norm(&$arFeatureList)
{
    foreach($arFeatureList as $szKeyFrameID => $arFV)
    {
        $arFeatureList[$szKeyFrameID] = doL1Norm1FV($arFV);
    }
}

function doL1Norm1FV($arFV)
{
    $arNormFV = array();
    
    $fSum = 0;
    foreach($arFV as $szKey => $fVal)
    {
        $fSum += $fVal;
    }

    foreach($arFV as $szKey => $fVal)
    {
        $arNormFV[$szKey] = $fVal/$fSum;
    }

    return $arNormFV;
}

?>