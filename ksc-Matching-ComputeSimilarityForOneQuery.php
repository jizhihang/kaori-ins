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

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);
$szRootResultDir =  sprintf("%s/result", $szRootDir); //TO DO

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////
$szRunID = "run_bl";
$szQueryID =  "9048";
$szQueryPatName = "queryext2012-new";
$szTestPatName = "subtest2012-new";
$szFeatureExt = "nsc.bow.dense6mul.sift.Soft-1000.subtest2012-new.norm1x1";
$nStartVideoID = 0;
$nEndVideoID = 1;

if($argc != 8)
{
    printf("Usage: %s <RunID> <QueryID> <QueryPatName> <TestPatName> <FeatureExt> <StartVideoID> <EndVideoID>\n", $argv[0]);
    printf("Usage: %s %s %s %s %s %s %d %d\n", $argv[0], $szRunID, $szQueryID, $szQueryPatName, $szTestPatName, $szFeatureExt, $nStartVideoID, $nEndVideoID);
    exit();
}

$szRunID = $argv[1];
$szQueryID = $argv[2];
$szQueryPatName = $argv[3];
$szTestPatName = $argv[4];
$szFeatureExt = $argv[5];
$nStartVideoID = intval($argv[6]);
$nEndVideoID = intval($argv[7]);

//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);
$szRootFeatureInputDir = $szRootFeatureDir;

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");

$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig

$szTmpDir = sprintf("%s/%s/%s/%s-%s-%s-%d-%d", $szLocalTmpDir,  $szScriptBaseName,
		$szQueryPatName, $szTestPatName, $szQueryID, $szFeatureExt, $nStartVideoID, $nEndVideoID);
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

	if($nEndVideoID <0 || $nEndID>$nNumVideos)
	{
		$nEndVideoID = $nNumVideos;
	}

	$arVideoList = array_keys($arVideoPathList);

	for($i=$nStartVideoID; $i<$nEndVideoID; $i++)
	{
		$szVideoID = $arVideoList[$i];
		printf("###%d. Processing video [%s] ...\n", $i, $szVideoID);

		$szVideoPath = $arVideoPathList[$szVideoID];

		// !!! IMPORTANT !!!
		$szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootMetaDataDir, $szVideoPath, $szVideoID);

		// specific for one video program
		$szLocalDir2 = sprintf("%s/%s", $szLocalDir, $szVideoID);
		makeDir($szLocalDir2);
		
		$szResultDir = sprintf("%s/%s/%s", $szRootResultDir, $szRunID, $szVideoPath);
		makeDir($szResultDir);
		$szFPOutputFN = sprintf("%s/%s.res", $szResultDir, $szVideoID);
		
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
    system($szCmdLine);
    
    $szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTestFeatureFN, $szLocalDir);
    system($szCmdLine);

    $szFPLocalQueryFeatureFN = sprintf("%s/%s", $szLocalDir, $szQueryFeatureCoreName);
    $szFPLocalTestFeatureFN = sprintf("%s/%s", $szLocalDir, $szTestFeatureCoreName);
    
    $arQueryFeature = loadOneSvfFeatureFile($szFPLocalQueryFeatureFN, $nKFIndex=2);
    $arTestFeature = loadOneSvfFeatureFile($szFPLocalTestFeatureFN, $nKFIndex=2);

    deleteFile($szFPLocalQueryFeatureFN);
    deleteFile($szFPLocalTestFeatureFN);
    
    $arFinalOutput = array();
    $time_start = microtime(true);
    foreach($arTestKFList as $szTestKeyFrameID)
    {
        foreach($arQueryKFList as $szQueryKeyFrameID)
        {
            
            $fScore = computeL2Dist($arQueryFeature[$szQueryKeyFrameID], $arTestFeature[$szTestKeyFrameID]);
            //exit("Score - $fScore");
            
            $arFinalOutput[] = sprintf("%s #$# %s #$# %f", $szTestKeyFrameID, $szQueryKeyFrameID, $fScore);
            
            
        }
    }
    $time_end = microtime(true);
    
    printf("###Processing time [%s]: %0.2f. \n", $szVideoID, $time_end-$time_start);
    
    saveDataFromMem2File($arFinalOutput, $szFPOutputFN);
}

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

?>