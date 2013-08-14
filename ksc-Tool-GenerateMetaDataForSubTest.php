<?php

/**
 * 		@file 	ksc-Tool-GenerateMetaDataForSubTest.php
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
// distribute into VideoID
$nMaxShotsPerVideoID = 50;

$arOrigPatList = array(2011 => "test2011-new", 2012 => "test2012-new", 2013 => "test2013-new");
$arSamplingRateList = array(2011 => 10, 2012 => 20, 2013 => 20);
if($argc !=2)
{
	printf("Usage: %s <Year>\n", $arg[0]);
	printf("Usage: %s 2011 \n", $arg[0]);
	exit();
}
$nTVYear = $argv[1];
$szTVYear = sprintf("tv%d", $nTVYear);
$szPatName = $arOrigPatList[$nTVYear];
$szSubPatName = sprintf("sub%s", $szPatName);

$nSamplingRate = $arSamplingRateList[$nTVYear];

if($nTVYear == 2013) // still keep the old one for Duc's experiments
{
    $szRootMetaDataDir = sprintf("%s/metadata-bak/keyframe-5", $gszRootBenchmarkDir);
}
else
{
    $szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
}

$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);
$szRootKeyFrameDir = sprintf("%s/keyframe-5", $gszRootBenchmarkDir);
$szInputKeyFrameDir = sprintf("%s/%s/%s", $szRootKeyFrameDir, $szTVYear, $szPatName);
$szOutputKeyFrameDir = sprintf("%s/%s/%s", $szRootKeyFrameDir, $szTVYear, $szSubPatName);
makeDir($szKeyFrameDir);

$szNewVideoPath = sprintf("%s/%s", $szTVYear, $szSubPatName);

// first VideoID consists of relevant shots 
$szFPNISTResultFN = sprintf("%s/ins.search.qrels.%s", $szMetaDataDir, $szTVYear);


if(file_exists($szFPNISTResultFN))
{
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
    
    $szFPInputFN = sprintf("%s/%s.lst", $szMetaDataDir, $szPatName);

    $nNumVideos = loadListFile($arVideoList, $szFPInputFN);
    shuffle($arVideoList);
    
    $nCount = 0;
    $arFinalOutput = array();
    // aggregate all shots of test partition
    $arVideoShotLUT = array(); 
    foreach($arVideoList as $szLine)
    {
        $arTmpzz = explode("#$#", $szLine);
        $szVideoID = trim($arTmpzz[0]);
        $szFPInputFN = sprintf("%s/%s/%s.prg", $szMetaDataDir, $szPatName, $szVideoID);
        
        loadListFile($arShotList, $szFPInputFN);
        foreach($arShotList as $szLine)
        {
            if($nTVYear == 2012 || $nTVYear == 2011)
            {
                //FL000076643_0001
                $arTmpzz = explode("_", $szLine);
                $szShotID = trim($arTmpzz[0]);
                $szKeyFrameID = trim($szLine);
            }
            else 
            {
                exit("parser does not support");    
            }            

            $arFinalOutput[$szShotID][] = $szKeyFrameID;
            $arVideoShotLUT[$szShotID] = $szVideoID;
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
     
    printf("Num selected shots: %d\n", sizeof($arSubTestOutput));
    //exit();
    $nIndex = 0;
    $nVideoID = 0;
    $arVideoList = array();
    $szPrefix = sprintf("TRECVIDz%d", $nTVYear); // TRECVID2011_1 --> videoID
    
    $arDPMList = array(); // list of keyframes to run DPM
    
    $arVideoOutputList = array();
    
    foreach($arSubTestOutput as $szShotID => $arKeyFrameList)
    {
        $szOrigVideoID = $arVideoShotLUT[$szShotID];
        $szInputKeyFrameDir2 = sprintf("%s/%s", $szInputKeyFrameDir, $szOrigVideoID);
        if(($nIndex % $nMaxShotsPerVideoID) == 0)
        {
        	$szVideoID = sprintf("%s_%d", $szPrefix, $nVideoID);
        	$szOutputKeyFrameDir2 = sprintf("%s/%s", $szOutputKeyFrameDir, $szVideoID);
        	makeDir($szOutputKeyFrameDir2);
        	$nVideoID++;
        	
        	// VideoID #$# VideoName #$# VideoPath
        	$arVideoOutputList[$szVideoID] = sprintf("%s#$#%s#$#%s", $szVideoID, $szVideoID, $szNewVideoPath);
        	 
        }
        $nIndex++;
        
        // make soft link
        $szCmdLine = sprintf("cp %s/%s.tar %s", $szInputKeyFrameDir2, $szShotID, $szOutputKeyFrameDir2);
        execSysCmd($szCmdLine);
        
        $nDPMCount = 0;
        $nNumKeyFrames = sizeof($arKeyFrameList);
        $nMiddle = intval(0.5*$nNumKeyFrames); // only pick ONE keyframe per shot
        foreach($arKeyFrameList as $szKeyFrameID)
        {
        	$arVideoList[$szVideoID][] = sprintf("%s", $szKeyFrameID);
        	
        	if($nDPMCount == $nMiddle)
        	{
        	   $arDPMList[] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
        	}
        	$nDPMCount++;
        }
    }
    
    // new partition called subtest --> a subset of test partition
    $szOutputDir = sprintf("%s/%s/%s", $szRootMetaDataDir, $szTVYear, $szSubPatName);
    makeDir($szOutputDir);
    $szFPOutputFN = sprintf("%s/%s/%s.lst", $szRootMetaDataDir, $szTVYear, $szSubPatName); 
    saveDataFromMem2File($arVideoOutputList, $szFPOutputFN);
    foreach($arVideoList as $szVideoID => $arKeyFrameList)
    {
    	$szFPOutputFN = sprintf("%s/%s.prg", $szOutputDir, $szVideoID); // tv2011.lst
    	saveDataFromMem2File($arKeyFrameList, $szFPOutputFN);
    }
    
    $szFPOutputFN = sprintf("%s/%s/%s.dpm.lst", $szRootMetaDataDir, $szTVYear, $szSubPatName); 
    saveDataFromMem2File($arDPMList, $szFPOutputFN);
    
    printf("Num shots of subtest: %d - Num all shots: %d - Sampling rate: %d", sizeof($arSubTestOutput), sizeof($arFinalOutput), $nSamplingRate);
        
    exit();
}

///////////////// INS 2013 //////////////////

// other VideoID consists of distracting shots
$szPatName = "test";
$szFPInputFN = sprintf("%s/%s.%s.lst", $szMetaDataDir, $szTVYear, $szPatName);
$nNumVideos = loadListFile($arVideoList, $szFPInputFN);
shuffle($arVideoList);

$nCount = 0;
$arFinalOutput = array();
// aggregate all shots of test partition
foreach($arVideoList as $szVideoID)
{
	$szFPInputFN = sprintf("%s/%s/%s.prg", $szMetaDataDir, $szPatName, $szVideoID);
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
$nSamplingRate = 10; // pick all shots
foreach($arFinalOutput as $szShotID => $arKeyFrameList)
{
	if(($nCount % $nSamplingRate) == 0)
	{
		$arSubTestOutput[$szShotID] = $arKeyFrameList;
	}
	$nCount++;
}

// distribute into VideoID
$nMaxShotsPerVideoID = 500;

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
		 
	    // !!! IMPORTANT --> DPM list = subtest
		if($nDPMCount == $nMiddle)
		{
			$arDPMList[] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
		  $arVideoList[$szVideoID][] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
		}
		$nDPMCount++;
	}
}

// new partition called subtest --> a subset of test partition
$szOutputDir = sprintf("%s/%s/subtest10", $szRootMetaDataDir, $szTVYear);
makeDir($szOutputDir);
$szFPOutputFN = sprintf("%s/%s/%s.subtest10.lst", $szRootMetaDataDir, $szTVYear, $szTVYear); // tv2011.lst
saveDataFromMem2File(array_keys($arVideoList), $szFPOutputFN);
foreach($arVideoList as $szVideoID => $arKeyFrameList)
{
	$szFPOutputFN = sprintf("%s/%s.prg", $szOutputDir, $szVideoID); // tv2011.lst
	saveDataFromMem2File($arKeyFrameList, $szFPOutputFN);
}

$szFPOutputFN = sprintf("%s/%s/%s.subtest10.dpm.lst", $szRootMetaDataDir, $szTVYear, $szTVYear); // tv2011.lst
saveDataFromMem2File($arDPMList, $szFPOutputFN);

printf("Num shots of subtest: %d - Num all shots: %d - Sampling rate: %d", sizeof($arSubTestOutput), sizeof($arFinalOutput), $nSamplingRate);
exit();
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