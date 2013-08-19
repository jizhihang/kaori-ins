<?php

/**
 * 		@file 	ksc-ProcessOneRun-Filter4DPM.php
 * 		@brief 	Ranking and Filtering for DPM
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 18 Aug 2013.
 */


require_once "ksc-AppConfig.php";
require_once "ksc-Tool-EvalMAP.php";

// based on ksc-ProcessOneRun-Rank.php

////////////////// START //////////////////


$nTVYear = 2013;
$arVideoPathLUT[2012] = "tv2012/subtest2012-new";
$arVideoPathLUT[2013] = "tv2013/test2013-new";
$arMaxShotsLUT = array(2012 => 10000, 2013 => 50000);
$arQueryIDStartLUT= array(2012=>9048, 2013=>9068);

$nQueryIDStart = 9069;
$nQueryIDEnd = 9098;
if($argc!=4)
{
    printf("Usage: %s <Year> <QueryIDStart> <QueryIDEnd>\n", $argv[0]);
    printf("Usage: %s %s %s %s\n", $argv[0], $nTVYear, $nQueryIDStart, $nQueryIDEnd);
    exit();
}

$nTVYear = intval($argv[1]);
$nQueryIDStart = intval($argv[2]);
$nQueryIDEnd = intval($argv[3]);

$szTVYear = sprintf("tv%d", $nTVYear);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);

// ins.topics.2013.xml 
$szFPInputFN = sprintf("%s/ins.topics.%d.xml", $szMetaDataDir, $nTVYear);
$arQueryList = loadQueryDesc($szFPInputFN);

$szVideoPath = $arVideoPathLUT[$nTVYear];
$nMaxShots = $arMaxShotsLUT[$nTVYear];

$szResultDir = sprintf("%s/result", $gszRootBenchmarkDir);

$arDirList = collectDirsInOneDir($szResultDir);
sort($arDirList);
print_r($arDirList);

$szFPNISTResultFN = sprintf("%s/ins.search.qrels.%s", $szMetaDataDir, $szTVYear);

if(file_exists($szFPNISTResultFN))
{
	$arNISTList = parseNISTResult($szFPNISTResultFN);
}

$nMaxDocs = 1000; 
$nTVYearz = sprintf("%s", $nTVYear);
foreach($arDirList as $szRunID)
{
    if((!strstr($szRunID, "run_")) || (!strstr($szRunID, $nTVYearz)) || (strstr($szRunID, "dpm")))
    {
        printf("### Skipping [%s] ...\n", $szRunID);
        continue;        
    }

     printf("### Processing [%s] ...\n", $szRunID);
    $nNumQueries = sizeof($arQueryList) + $arQueryIDStartLUT[$nTVYear];
    if($nQueryIDEnd > $nNumQueries)
    {
        $nQueryIDEnd = $nNumQueries;
    }
    
    $arQueryKeys = array_keys($arQueryList);
    //print_r($arQueryKeys);
    //printf("%d - %d\n", $nQueryIDStart, $nQueryIDEnd);exit();
    for($nQueryID=$nQueryIDStart; $nQueryID<$nQueryIDEnd; $nQueryID++){
        
        $nIndex = $nQueryID - $arQueryIDStartLUT[$nTVYear];
        $szQueryID = $arQueryKeys[$nIndex];
        printf("Processing query [%s]\n", $szQueryID);

        $szQueryResultDir1 = sprintf("%s/%s/%s", $szResultDir, $szRunID, $szVideoPath);
    	$szQueryResultDir = sprintf("%s/%s/%s/%s", $szResultDir, $szRunID, $szVideoPath, $szQueryID);
    
        $arDPMList = loadRankedList($szQueryResultDir, $nTVYear, $nMaxShots);

        $szFPOutputFN = sprintf("%s/%s.dpm.lst", $szQueryResultDir1, $szQueryID);
        saveDataFromMem2File($arDPMList, $szFPOutputFN);
    }
}

//////////////////////////////// FUNCTIONS ///////////////////////////////////


/**
 <videoInstanceTopic
 text="George W. Bush"
 num="9001"
 type="PERSON">
 */
function loadQueryDesc($szFPInputFN="ins.topics.2011.xml")
{
	$nNumRows = loadListFile($arRawList, $szFPInputFN);

	$arOutput = array();
	for($i=0; $i<$nNumRows; $i++)
	{
		$szLine = trim($arRawList[$i]);
		if($szLine == "<videoInstanceTopic")
		{
			$szQueryText = trim($arRawList[$i+1]);
			$szQueryText = str_replace("text=", "", $szQueryText);
			$szQueryText = trim($szQueryText, "\"");

			$szQueryID = trim($arRawList[$i+2]);
			$szQueryID = str_replace("num=", "", $szQueryID);
			$szQueryID = trim($szQueryID, "\"");

			$szQueryType = trim($arRawList[$i+3]);
			$szQueryType = str_replace(">", "", $szQueryType);
			$szQueryType = str_replace("type=", "", $szQueryType);
			$szQueryType = trim($szQueryType, "\"");

			$szOutput = sprintf("%s - %s - %s", $szQueryID, $szQueryType, $szQueryText);
			$arOutput[$szQueryID] = $szOutput;
		}
	}

	return $arOutput;
}

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



function loadRankedList($szResultDir, $nTVYear, $nMaxShots=50000)
{
    $arFileList = collectFilesInOneDir($szResultDir, "", ".res");
    //print_r($arFileList);
    $arRankList = array();
    
    $arShotKeyFrameList = array();
    $nCount = 0;
    foreach($arFileList as $szInputName)
    {
        $szFPScoreListFN = sprintf("%s/%s.res", $szResultDir, $szInputName);
    	loadListFile($arScoreList, $szFPScoreListFN);
        foreach($arScoreList as $szLine)
    	{
            $arTmp = explode("#$#", $szLine);
        	$szTestKeyFrameID = trim($arTmp[0]);
        	$szQueryKeyFrameID = trim($arTmp[1]);
        	$fScore = floatval($arTmp[2]);
        	 
            $arTmp1 = explode("_", $szTestKeyFrameID);
        	if($nTVYear != 2013)
        	{
                $szShotID = trim($arTmp1[0]);
        	}
        	else 
        	{
                $szShotID = sprintf("%s_%s", trim($arTmp1[0]), trim($arTmp1[1]));
        	}

        	if(($nTVYear == 2013) &&(strstr($szShotID, "shot0_")))
        	{
        	    continue; // skip shot0_
        	}
        	 
        	// ShotID-KeyFrameID-QueryID.n --> unique
        	if(isset($arShotKeyFrameList[$szShotID][$szTestKeyFrameID]))
        	{
        	    if($arShotKeyFrameList[$szShotID][$szTestKeyFrameID] < $fScore)
        	    {
        	        $arShotKeyFrameList[$szShotID][$szTestKeyFrameID]= $fScore;
        	    }
        	}
        	else 
        	{
                $arShotKeyFrameList[$szShotID][$szTestKeyFrameID]= $fScore;
        	}

            if(isset($arRankList[$szShotID]))
            {
                if($arRankList[$szShotID] < $fScore)
                {
                    $arRankList[$szShotID] = $fScore;
    			}
    		}
    		else
    		{
    			$arRankList[$szShotID] = $fScore;
    		}
    	}
    }
    
    arsort($arRankList);
    
    $arDPMList = array();
    $nShotCount = 0;
    foreach($arRankList as $szShotID=>$fScore){
        
        $arLocalShotKFList = $arShotKeyFrameList[$szShotID];
        
        arsort($arLocalShotKFList);
        
        $nKFCount = 0;
        foreach($arLocalShotKFList as $szKeyFrameID => $fLocalScore)
        {
            if(($nKFCount == 0) && ($fLocalScore != $fScore))
            {
                printf("Serious error!\n"); 
                print_r($arRankList);
                print_r($arLocalShotKFList);
                exit();
            }
            $arDPMList[] = sprintf("%s #$# %s #$# %f", $szKeyFrameID, $szShotID, $fLocalScore);
            $nKFCount++;
            if($nKFCount >= 2) // max 2KF/shot
            {
                break;
            }
        }
        $nShotCount++;
        
        if($nShotCount>=$nMaxShots)
        {
            break;
        }
    }

    return ($arDPMList);
}


?>