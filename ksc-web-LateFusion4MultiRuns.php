<?php

/**
 * 		@file 	ksc-web-LateFusion4MultiRuns.php
 * 		@brief 	Perform late fusion online
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 17 Sep 2014.
 */

// Update Sep 17, 2014
// Fix bug of the case of lacking score of one shot in other list.
// --> Previous Aug 08, 2014: assume R1 is the biggest list
// --> case 1: (if one shot S2 in R2 is not in R1, then ignore score of S2 --> no use weight f2)
// --> case 2: (if one shot S1 in R1 is not in R2, then no use weight f2) --> Found BUG (Sep 17, 2014)
// --> Now Sep 17, 2014 --> if case 2, use f2*fMinScore of R2

// Update Jul 11, 2014
/**
 * 1.
 * Adding more normalization method - sigmoid function (default) and z-score
 * 2. Auto adding fusion config to runID
 * 3. Fusion method: first, compute shot score (= max score of keyframes), then normalize score, and fuse
 * 4. OutputRunID - suffix will be added (R1, R2, weights, normalization method)
 * 5. OutputRun config file is saved.
 */
require_once "ksc-AppConfig.php";
require_once "ksc-Tool-EvalMAP.php";

// //////////////// START //////////////////

$arNormMethodDesc = array(
    // 0 => "Simple Sigmoid Function (1/(1+exp(-t)))",
    1 => "Z-Score by Using Mean and Std"
); // shown better perf compared to sigmoid

$nAction = 0;
if (isset($_REQUEST['vAction'])) {
    $nAction = $_REQUEST['vAction'];
}

if ($nAction == 0) {
    printf("<P><H1>Late Fusion</H1>\n");
    printf("<P><H1>Select TVYear</H1>\n");
    printf("<FORM TARGET='_blank'>\n");
    printf("<P>TVYear<BR>\n");
    printf("<SELECT NAME='vTVYear'>\n");
    printf("<OPTION VALUE='2014'>2014</OPTION>\n");
    printf("<OPTION VALUE='2013'>2013</OPTION>\n");
    printf("<OPTION VALUE='2012'>2012</OPTION>\n");
    printf("<OPTION VALUE='2011'>2011</OPTION>\n");
    printf("</SELECT>\n");
    
    printf("<P>Num Runs for Fusion<BR>\n");
    // load xml file
    printf("<SELECT NAME='vNumRuns'>\n");
    printf("<OPTION VALUE='2'>2</OPTION>\n");
    printf("<OPTION VALUE='3'>3</OPTION>\n");
    printf("<OPTION VALUE='4'>4</OPTION>\n");
    printf("<OPTION VALUE='5'>5</OPTION>\n");
    printf("</SELECT>\n");
    
    printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1'>\n");
    printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
    printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
    printf("</FORM>\n");
    exit();
}

$arVideoPathLUT[2012] = "tv2012/subtest2012-new";
$arVideoPathLUT[2013] = "tv2013/test2013-new";

$nTVYear = $_REQUEST['vTVYear'];
$szTVYear = sprintf("tv%d", $nTVYear);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);

$szPatName4KFDir = sprintf("test%s", $nTVYear);

$nNumRuns = $_REQUEST['vNumRuns'];

if ($nNumRuns == 0) {
    $nNumRuns = 2;
}

$szResultDir = sprintf("%s/result/%s/%s", $gszRootBenchmarkDir, $szTVYear, $szPatName4KFDir);
$arDirList = collectDirsInOneDir($szResultDir);
sort($arDirList);

// print_r($arQueryListCount);
// show form
if ($nAction == 1) {
    printf("<P><H1>Late Fusion</H1>\n");
    printf("<FORM TARGET='_blank'>\n");
    
    for ($iz = 0; $iz < $nNumRuns; $iz ++) {
        $nRunCount = $iz + 1;
        printf("<P>RunID%d<BR>\n", $nRunCount);
        printf("<SELECT NAME='vRunID[]'>\n");
        foreach ($arDirList as $szRunID) {
            printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szRunID, $szRunID);
        }
        printf("</SELECT>\n");
        
        printf("<P>RunID%d-Weight<BR>\n", $nRunCount);
        printf("<INPUT TYPE='TEXT' NAME='vWeight[]' VALUE='1'>\n");
    }
    
    $arQueryStartID = array(
        2013 => 9069,
        2014 => 9099
    );
    printf("<P>From<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vFrom' VALUE='%s'>\n", $arQueryStartID[$nTVYear]);
    
    $arQueryEndID = array(
        2013 => 9098,
        2014 => 9128
    );
    printf("<P>To<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vTo' VALUE='%s'>\n", $arQueryEndID[$nTVYear]);
    
    printf("<P>Normalization Method for Scores<BR>\n");
    printf("<SELECT NAME='vNormMethod'>\n");
    printf("<OPTION VALUE='1'>%s</OPTION>\n", $arNormMethodDesc[1]);
    printf("</SELECT>\n");
    
    printf("<P>Output Run - Suffix will be added, e.g XXX[R1=R2.1xw1=1.0-R2=R2.2xw2=1.0-Norm=0]<BR>\n");
    printf("<INPUT TYPE='TEXT' NAME='vOutRunID' VALUE='R1_%s.fusion-' SIZE='100'>\n", $szTVYear);
    
    printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='2'>\n");
    printf("<P><INPUT TYPE='HIDDEN' NAME='vTVYear' VALUE='%s'>\n", $nTVYear);
    printf("<P><INPUT TYPE='HIDDEN' NAME='vNumRuns' VALUE='%s'>\n", $nNumRuns);
    printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
    printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
    printf("</FORM>\n");
    exit();
}

print_r($_REQUEST);

$arRunList = $_REQUEST['vRunID'];
$arWeightList = $_REQUEST['vWeight'];
$nQueryIDStart = intval($_REQUEST['vFrom']);
$nQueryIDEnd = intval($_REQUEST['vTo']);

$szCoreOutRunID = $_REQUEST['vOutRunID'];

// adding Jul 11, 2014
$nNormMethod = $_REQUEST['vNormMethod'];

$szOutputRunSuffix = sprintf('[%s-', $arWeightList[0]);
for ($i = 1; $i < $nNumRuns - 1; $i ++) {
    $szOutputRunSuffix = $szOutputRunSuffix . $arWeightList[$i] . '-';
}
$szOutputRunSuffix = $szOutputRunSuffix . $arWeightList[$i] . ']';
$szOutRunID = sprintf("%s%s", $szCoreOutRunID, $szOutputRunSuffix);

print($szOutputRunID);
$arLog = array();
$arLog[] = sprintf("Fusion run config");
$arLog[] = sprintf("NormScoreMethod: %s", $arNormMethodDesc[$nNormMethod]);
$arLog[] = sprintf("TVYear: %s", $szTVYear);
$arLog[] = sprintf("Output Name: %s", $szOutRunID);
for ($i = 0; $i < $nNumRuns; $i ++) {
    $arLog[] = sprintf("R%d: %s - Weight: %0.2f", $i, $arRunList[$i], $arWeightList[$i]);
}

foreach ($arLog as $szLine) {
    printf("%s<BR>\n", $szLine);
}

for ($nQueryID = $nQueryIDStart; $nQueryID <= $nQueryIDEnd; $nQueryID ++) {
    $szQueryIDz = sprintf("%s", $nQueryID);
    $szQueryResultDir1 = sprintf("%s/%s", $szResultDir, $szOutRunID);
    $szQueryResultDir = sprintf("%s/%s/%s", $szResultDir, $szOutRunID, $szQueryIDz);
    
    makeDir($szQueryResultDir1);
    $szCmdLine = sprintf("chmod 777 %s", $szQueryResultDir1);
    execSysCmd($szCmdLine);
    makeDir($szQueryResultDir);
    $szCmdLine = sprintf("chmod 777 %s", $szQueryResultDir);
    execSysCmd($szCmdLine);
    
    $szFPOutputFN = sprintf("%s/%s.rank", $szQueryResultDir1, $szQueryIDz);
    // printf("<P>****** %s", $szFPOutputFN);
    // exit("<P>******** DEBUG *******");
    $arRawListz = fuseRankedList($szQueryIDz, $nNormMethod, $arRunList, $arWeightList, $szResultDir, $nTVYear);
    $arRawList = array();
    $nCount = 0;
    
    $arScoreOutput = array();
    foreach ($arRawListz as $szShotID => $fScore) {
        $arRawList[] = sprintf("%s #$# %f", $szShotID, $fScore);
        $arScoreOutput[] = sprintf("%s #$# %s #$# %f", $szShotID, $szQueryIDz, $fScore);
        $nCount ++;
        
        // if ($nCount > 10000)
        // break;
    }
    saveDataFromMem2File($arRawList, $szFPOutputFN);
    $szCmdLine = sprintf("chmod 777 %s", $szFPOutputFN);
    execSysCmd($szCmdLine);
    
    $szFPScoreOutputFN = sprintf("%s/%s.res", $szQueryResultDir, $szQueryIDz);
    saveDataFromMem2File($arScoreOutput, $szFPScoreOutputFN);
    $szCmdLine = sprintf("chmod 777 %s", $szFPScoreOutputFN);
    execSysCmd($szCmdLine);
}

// update Jul 11, 2014
$szFPOutputFN = sprintf("%s/%s.log", $szQueryResultDir1, $szOutRunID);
saveDataFromMem2File($arLog, $szFPOutputFN);
exit();

exit();

// ////////////////////////////// FUNCTIONS ///////////////////////////////////

/**
 * <videoInstanceTopic
 * text="George W.
 * Bush"
 * num="9001"
 * type="PERSON">
 */
function loadQueryDesc($szFPInputFN = "ins.topics.2011.xml")
{
    $nNumRows = loadListFile($arRawList, $szFPInputFN);
    
    $arOutput = array();
    for ($i = 0; $i < $nNumRows; $i ++) {
        $szLine = trim($arRawList[$i]);
        if ($szLine == "<videoInstanceTopic") {
            $szQueryText = trim($arRawList[$i + 1]);
            $szQueryText = str_replace("text=", "", $szQueryText);
            $szQueryText = trim($szQueryText, "\"");
            
            $szQueryID = trim($arRawList[$i + 2]);
            $szQueryID = str_replace("num=", "", $szQueryID);
            $szQueryID = trim($szQueryID, "\"");
            
            $szQueryType = trim($arRawList[$i + 3]);
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
    foreach ($arRawList as $szLine) {
        // 9001 0 shot300_101 0
        $arTmp = explode(" ", $szLine);
        $szQueryID = trim($arTmp[0]);
        $szShotID = trim($arTmp[2]);
        $nLabel = intval($arTmp[3]);
        
        if ($nLabel) {
            $arOutput[$szQueryID][] = $szShotID;
        }
    }
    
    return $arOutput;
}

// update Jul 11, 2014
// $nTVYear --> to get the shotID
function fuseRankedList($szQueryIDz, $nNormMethod, $arRunList, $arWeightList, $szResultDir, $nTVYear)
{
    global $nQueryID;
    global $arLog;
    
    $arResultDirList = array();
    foreach ($arRunList as $szRunID) {
        $arResultDirList[] = sprintf("%s/%s/%s", $szResultDir, $szRunID, $szQueryIDz);
    }
    $arResultRankList = array();
    $nRound = 1;
    
    // print_r($arResultDirList);
    // exit();
    // first - compute shot score = max scores of keyframes
    $arTmpList = array();
    foreach ($arResultDirList as $szResultDir) {
        $arFileList = collectFilesInOneDir($szResultDir, "", ".res");
        // print_r($arFileList);
        $arRankList = array();
        $nCount = 0;
        foreach ($arFileList as $szInputName) {
            $szFPScoreListFN = sprintf("%s/%s.res", $szResultDir, $szInputName);
            loadListFile($arScoreList, $szFPScoreListFN);
            foreach ($arScoreList as $szLine) {
                $arTmp = explode("#$#", $szLine);
                $szTestKeyFrameID = trim($arTmp[0]);
                $szQueryKeyFrameID = trim($arTmp[1]);
                $fScore = floatval($arTmp[2]);
                
                $arTmp1 = explode("_", $szTestKeyFrameID);
                if ($nTVYear < 2013) {
                    $szShotID = trim($arTmp1[0]);
                } else {
                    $szShotID = sprintf("%s_%s", trim($arTmp1[0]), trim($arTmp1[1]));
                }
                if (isset($arRankList[$szShotID])) {
                    if ($arRankList[$szShotID] < $fScore) {
                        $arRankList[$szShotID] = $fScore;
                    }
                } else {
                    $arRankList[$szShotID] = $fScore;
                }
            }
        }
        
        // compute statistics such as mean and std
        $nCount = 0;
        $fSum = 0;
        $fSumSq = 0;
        
        $fMin = 1E10;
        $fMax = - 1E10;
        foreach ($arRankList as $szShotID => $fScore) {
            $fSum += $fScore;
            $fSumSq += $fScore * $fScore;
            $nCount ++;
            
            $fMin = min($fMin, $fScore);
            $fMax = max($fMax, $fScore);
        }
        $fMean = $fSum / $nCount;
        $fStd = $fSumSq / $nCount - $fMean * $fMean;
        $fStd = sqrt($fStd);
        $arLog[] = sprintf("<P>QueryID = %s - Mean = %0.4f - Std = %0.4f - Path: %s<BR>\n", $nQueryID, $fMean, $fStd, $szResultDir);
        
        // assign scores of the first ranked list
        
        if ($nRound == 1) {
            foreach ($arRankList as $szShotID => $fScore) {
                if ($nNormMethod == 0) {
                    $arResultRankList[$szShotID]["score"] = $arWeightList[$nRound - 1] * normScoreSigmoid($fScore);
                }
                
                if ($nNormMethod == 1) {
                    $arResultRankList[$szShotID]["score"] = $arWeightList[$nRound - 1] * normScoreZMethod($fScore, $fMean, $fStd);
                }
                
                $arResultRankList[$szShotID]["weight"] = $arWeightList[$nRound - 1];
            }
/*            
            $arKeys = array_keys($arResultRankList);
            
            for ($k = 0; $k < 10; $k ++) {
                printf("<BR>[%d][%d].%s - %0.4f - %d\n", $nRound, $k, $arKeys[$k], $arResultRankList[$arKeys[$k]]["score"], $arResultRankList[$arKeys[$k]]["weight"]);
            }
*/            
        } else {
            // iterate over $arResultRankList
            $arKeys = array_keys($arResultRankList);
            $nRankz = 1;
            $nDocCount = sizeof($arKeys);
            foreach ($arKeys as $szShotID) {
                if (isset($arRankList[$szShotID])) { // check whether it occurs in ranked list 2, 3, etc
                    $fScore = $arRankList[$szShotID];
                } else {
                    //$fScore = 0; // --> worse than $fScore = $fMin
                    $fScore = $fMin; // AD-HOC - ah
                    // $fScore = $fMin*(1.0 - 1.0*$nRankz/$nDocCount); // estimate true score using fMin and nRankz --> worst than $fScore = $fMin
                }
                if ($nNormMethod == 0) {
                    $arResultRankList[$szShotID]["score"] += $arWeightList[$nRound - 1] * normScoreSigmoid($fScore);
                }
                if ($nNormMethod == 1) {
                    $arResultRankList[$szShotID]["score"] += $arWeightList[$nRound - 1] * normScoreZMethod($fScore, $fMean, $fStd);
                }
                $arResultRankList[$szShotID]["weight"] += $arWeightList[$nRound - 1];
                
                $nRankz++;
            }
/*            
            for ($k = 0; $k < 10; $k ++) {
                printf("<BR>[%d][%d].%s - %0.4f - %d\n", $nRound, $k, $arKeys[$k], $arResultRankList[$arKeys[$k]]["score"], $arResultRankList[$arKeys[$k]]["weight"]);
            }
*/            
        }
        $nRound ++;
    }
    
    // printf((sizeof($arTmpList)));exit();
    
    $arResultRankList2 = array();
    foreach ($arResultRankList as $szShotID => $arTmp) {
        $arResultRankList2[$szShotID] = $arTmp["score"] / $arTmp["weight"];
    }
    arsort($arResultRankList2);
    
    return ($arResultRankList2);
}

function normScoreSigmoid($fScore)
{
    $fReturn = 1 / (1 + exp(- $fScore));
    
    return $fReturn;
}

function normScoreZMethod($fScore, $fMean, $fStd)
{
    $fReturn = ($fScore - $fMean) / ($fStd + 1); // $fStd+1 to avoid error of dividing to zero
                                                 
    // printf("<P>Score = %0.4f - Norm Score = %0.4f <BR>\n", $fScore, $fReturn);exit();
    
    return $fReturn;
}

?>
