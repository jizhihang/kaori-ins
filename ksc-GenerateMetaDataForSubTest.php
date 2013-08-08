<?php

/**
 * 		@file 	ksc-GenerateMetaDataForSubTest.php
 * 		@brief 	Generate metadata for a subset of database for developing stage of INS task.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 04 Aug 2013.
 */

/**
 * IMPORTANT
 * Use ln -s to make symlink for subtest to point to test in both metadata and keyframe-5 dirs
 */
require_once "ksc-AppConfig.php";

$nSamplingRate = 5; // 1/5 -20% of total shots

if($argc !=2)
{
	printf("Usage: %s <Year>\n", $arg[0]);
	printf("Usage: %s 2011 \n", $arg[0]);
	exit();
}
$nTVYear = $argv[1];
$szTVYear = sprintf("tv%d", $nTVYear);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);

$szSubTestMetaDataDir = sprintf("%s/%s/subtest", $szRootMetaDataDir, $szTVYear);

// first VideoID consists of relevant shots 
$szFPNISTResultFN = sprintf("%s/ins.search.qrels.%s", $szMetaDataDir, $szTVYear);
$arNISTList = parseNISTResult($szFPNISTResultFN);

$arAllShotList = array();
$arRelShotList = array();
foreach($arNISTList as $szQueryIDx => $arShotList)
{
    foreach($arShotList as $szShotID)
    {
        $arRelShotList[$szShotID] = 1;
    }
}

// other VideoID consists of distracting shots
$szFPInputFN = sprintf("%s/%s.test.lst", $szMetaDataDir, $szTVYear);
$nNumVideos = loadListFile($arVideoList, $szFPInputFN);
shuffle($arVideoList);

$nCount = 0;
$arFinalOutput = array();
// aggregate all shots of test partition
foreach($arVideoList as $szVideoID)
{
    $szFPInputFN = sprintf("%s/test/%s.prg", $szMetaDataDir, $szVideoID);
    loadListFile($arShotList, $szFPInputFN);
    foreach($arShotList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        $szShotID = trim($arTmp[0]);
        $szKeyFrameID = trim($arTmp[1]);
        
        $arFinalOutput[$szShotID][] = $szKeyFrameID;
    }
}

// select a subset of shots
$arSubTestOutput = array();
$nCount = 0;
foreach($arFinalOutput as $szShotID => $arKeyFrameList)
{
    if(isset($arRelShotList[$szShotID]))
    {
        $arSubTestOutput[$szShotID] = $arKeyFrameList;
    }
    else 
    {
        if(($nCount % $nSamplingRate) == 0)
        {
            $arSubTestOutput[$szShotID] = $arKeyFrameList;
        }
        $nCount++;
    }
}

// distribute into VideoID
$nMaxShotsPerVideoID = 50;

$nIndex = 0;
$nVideoID = 0;
$arVideoList = array();
$szPrefix = sprintf("TRECVIDz%d", $nTVYear); // TRECVID2011_1 --> videoID

$arDPMList = array(); // list of keyframes to run DPM

foreach($arSubTestOutput as $szShotID => $arKeyFrameList)
{
    if(($nIndex % $nMaxShotsPerVideoID) == 0)
    {
    	$szVideoID = sprintf("%s_%d", $szPrefix, $nVideoID);
    	$nVideoID++;
    }
    $nIndex++;
    
    $nDPMCount = 0;
    $nNumKeyFrames = sizeof($arKeyFrameList);
    $nMiddle = intval(0.5*$nNumKeyFrames); // only pick ONE keyframe per shot
    foreach($arKeyFrameList as $szKeyFrameID)
    {
    	$arVideoList[$szVideoID][] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
    	
    	if($nDPMCount == $nMiddle)
    	{
    	   $arDPMList[] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
    	}
    	$nDPMCount++;
    }
}

// new partition called subtest --> a subset of test partition
$szOutputDir = sprintf("%s/%s/subtest", $szRootMetaDataDir, $szTVYear);
makeDir($szOutputDir);
$szFPOutputFN = sprintf("%s/%s/%s.subtest.lst", $szRootMetaDataDir, $szTVYear, $szTVYear); // tv2011.lst
saveDataFromMem2File(array_keys($arVideoList), $szFPOutputFN);
foreach($arVideoList as $szVideoID => $arKeyFrameList)
{
	$szFPOutputFN = sprintf("%s/%s.prg", $szOutputDir, $szVideoID); // tv2011.lst
	saveDataFromMem2File($arKeyFrameList, $szFPOutputFN);
}

$szFPOutputFN = sprintf("%s/%s/%s.subtest.dpm.lst", $szRootMetaDataDir, $szTVYear, $szTVYear); // tv2011.lst
saveDataFromMem2File($arDPMList, $szFPOutputFN);

printf("%d - %d - %d", sizeof($arSubTestOutput), sizeof($arFinalOutput), $nSamplingRate);exit();

///////////////////////////// FUNCTIONS /////////////////////////

function parseNISTResult($szFPInputFN)
{
	loadListFile($arRawList, $szFPInputFN);

	$arOutput = array();
	foreach($arRawList as $szLine)
	{
		// 9001 0 shot300_101 0
		$arTmp = explode(" ", $szLine);
		$szQueryID = trim($arTmp[0]);
		$szShotID = trim($arTmp[2]);
		$nLabel = intval($arTmp[3]);

		if($nLabel)
		{
			$arOutput[$szQueryID][] = $szShotID;
		}
	}

	return $arOutput;
}
?>