<?php

/**
 * 		@file 	ksc-ProcessOneRun-Rank.php
 * 		@brief 	Ranking and Evaluation
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 18 Aug 2013.
 */


require_once "ksc-AppConfig.php";
require_once "ksc-Tool-EvalMAP.php";


////////////////// START //////////////////


$nTVYear = 2012;
$arVideoPathLUT[2012] = "tv2012/subtest2012-new";
$arVideoPathLUT[2013] = "tv2013/test2013-new";

if($argc!=2)
{
    printf("Usage: %s <Year>\n", $argv[0]);
    printf("Usage: %s %s\n", $argv[0], $nTVYear);
    exit();
}
$nTVYear = intval($argv[1]);
$szTVYear = sprintf("tv%d", $nTVYear);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);

// ins.topics.2013.xml 
$szFPInputFN = sprintf("%s/ins.topics.%d.xml", $szMetaDataDir, $nTVYear);
$arQueryList = loadQueryDesc($szFPInputFN);

$szVideoPath = $arVideoPathLUT[$nTVYear];

$szResultDir = sprintf("%s/result", $gszRootBenchmarkDir);

$arDirList = collectDirsInOneDir($szResultDir);
sort($arDirList);

$szFPNISTResultFN = sprintf("%s/ins.search.qrels.%s", $szMetaDataDir, $szTVYear);

if(file_exists($szFPNISTResultFN))
{
	$arNISTList = parseNISTResult($szFPNISTResultFN);
}

$nMaxDocs = 1000; 
$nTVYearz = sprintf("%s", $nTVYear);
foreach($arDirList as $szRunID)
{
    if((!strstr($szRunID, "run_")) || (!strstr($szRunID, $nTVYearz)))
    {
        printf("### Skipping [%s] ...\n", $szRunID);
        continue;        
    }

    $szQueryResultDir1 = sprintf("%s/%s/%s", $szResultDir, $szRunID, $szVideoPath);
    $szFPOutputFN = sprintf("%s/%s.rank", $szQueryResultDir1, $szRunID);
    if(file_exists($szFPOutputFN))
    {
        continue; // skip existing file
    }
    
    $arTVQRELOutput = array(); // for using trec_eval
    $arKSCMap = array();
    $fMeanAP = 0;
    $nCountAP = 0;
    foreach($arQueryList as $szQueryID => $szTmp)
    {
    	printf("Path:$szVideoPath <BR>\n");
    	$szQueryResultDir1 = sprintf("%s/%s/%s", $szResultDir, $szRunID, $szVideoPath);
    	$szQueryResultDir = sprintf("%s/%s/%s/%s", $szResultDir, $szRunID, $szVideoPath, $szQueryID);
    
        $arRawListz = loadRankedList($szQueryResultDir, $nTVYear);
        $arRawList = array();
        $nCount = 0;
        $nRank = 1;
        $arScoreList = array();
        foreach($arRawListz as $szShotID => $fScore)
        {
            if(($nTVYear == 2013) &&(strstr($szShotID, "shot0_")))
            {
                continue; // skip shot0_
            }
            
            $arRawList[] = sprintf("%s#$#%0.10f", $szShotID, $fScore);
            if($nRank <= $nMaxDocs)
        	{        		
                $arScoreList[$szShotID] = $fScore;
                
                $arTVQRELOutput[] = sprintf("%s 0 %s %s %s %s", 
                    $szQueryID, $szShotID, $nRank, $fScore, $szRunID);
        	}
            $nRank++;
        }
        
        $szFPOutputFN = sprintf("%s/%s.rank", $szQueryResultDir1, $szQueryID);
        saveDataFromMem2File($arRawList, $szFPOutputFN);
        
        $arAnnList = array();
        foreach($arNISTList[$szQueryID] as $szShotID)
        {
        	$arAnnList[$szShotID] = 1;
        }
        
        $arTmpzzz = computeTVAveragePrecision($arAnnList, $arScoreList, $nMaxDocs);
        $fMAP = $arTmpzzz['ap'];
        
        //print_r($arTmpzzz); print_r($arAnnList); print_r($arScoreList); exit();
        
        $arKSCMap[$szQueryID] = sprintf("%s %0.2f", $szQueryID, $fMAP);
        $fMeanAP += $fMAP;
        $nCountAP++;
    }

    $szFPOutputFN = sprintf("%s/%s.rank", $szQueryResultDir1, $szRunID);
    $szFPEvalFN = sprintf("%s/%s.eval", $szQueryResultDir1, $szRunID);
    saveDataFromMem2File($arTVQRELOutput, $szFPOutputFN);
    $szTRECEvalApp = "/net/per900b/raid0/ledduy/bin/trec_eval_video.8.1/trec_eval";
    $szCmdLine = sprintf("%s -q -a -c %s %s %s > %s", $szTRECEvalApp,
    		$szFPNISTResultFN, $szFPOutputFN, $nMaxDocs, $szFPEvalFN);
    execSysCmd($szCmdLine);

    loadListFile($arRawList, $szFPEvalFN);
    $arOutput = array();
    foreach($arRawList as $szLine)
    {
    	if(strstr($szLine, "infAP") && !in_array($szLine, $arOutput))
    	{
    		$arOutput[] = $szLine;
    	}
    }
    $szFPEval2FN = sprintf("%s.csv", $szFPEvalFN);
    saveDataFromMem2File($arOutput, $szFPEval2FN);
    print_r($arOutput);
    
    $szFPEval2FN = sprintf("%s.ksc.csv", $szFPEvalFN);
    $arKSCMap[MeanAP] = $fMeanAP/$nCountAP;
    saveDataFromMem2File($arKSCMap, $szFPEval2FN);
    
  //  break; // debug
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



function loadRankedList($szResultDir, $nTVYear)
{
    $arFileList = collectFilesInOneDir($szResultDir, "", ".res");
    //print_r($arFileList);
    $arRankList = array();
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

    return ($arRankList);
}


?>