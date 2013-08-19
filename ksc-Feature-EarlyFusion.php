<?php
/**
 * 		@file 	ksc-Feature-EarlyFusion.php
 * 		@brief 	Early fusion of features
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 17 Aug 2013.
 */
require_once "ksc-AppConfig.php";

// !!! IMPORTANT !!!
$nMaxDimsForOneFeature = 10000;

// ////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

// $szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir); // TO DO
                                                                  
// ////////////////// END FOR CUSTOMIZATION ////////////////////
                                                                  
// /////////////////////////// MAIN ////////////////////////////////
$szFPFusionConfigFN = "harlap3x1+1x1.cfg";
$szPatName = "subtest2012-new";
$nStartVideoID = 0;
$nEndVideoID = 1;

if ($argc != 5) {
    printf("Usage: %s <FPFusionConfigFN> <PatName> <StartVideoID> <EndVideoID>\n", $argv[0]);
    printf("Usage: %s %s %s %d %d\n", $argv[0], $szFPFusionConfigFN, $szPatName, $nStartVideoID, $nEndVideoID);
    exit();
}

$szFPFusionConfigFN = $argv[1];
$szPatName = $argv[2];
$nStartVideoID = intval($argv[3]);
$nEndVideoID = intval($argv[4]);

// *** CHANGED *** !!! Modified Jul 06, 2012
$szRootFeatureInputDir = $szRootFeatureDir;

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");

$szLocalTmpDir = $gszTmpDir; // defined in ksc-AppConfig

$arOutput = loadFusionConfig($szFPFusionConfigFN);
$szDestFeatureExt = $arOutput["OutputFeature"];
$arFeatureList = $arOutput["InputFeature"];
$szDestVideoPath = sprintf("%s/%s", $arOutput["VideoPath"], $szPatName);

$szTmpDir = sprintf("%s/%s/%s/%s-%d-%d", 
    $szLocalTmpDir, $szScriptBaseName, $szPatName, $szDestFeatureExt, $nStartVideoID, $nEndVideoID);
makeDir($szTmpDir);
$szLocalDir = $szTmpDir;

$szFPVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szPatName);

$arVideoPathList = array();

loadListFile($arRawList, $szFPVideoListFN);

foreach ($arRawList as $szLine) {
    $arTmp = explode("#$#", $szLine);
    $szVideoID = trim($arTmp[0]);
    $szVideoPath = trim($arTmp[2]);
    $arVideoPathList[$szVideoID] = $szVideoPath;
}

$nNumVideos = sizeof($arVideoPathList);
if ($nStartVideoID < 0) {
    $nStartVideoID = 0;
}

if ($nEndVideoID < 0 || $nEndVideoID > $nNumVideos) {
    
    $nEndVideoID = $nNumVideos;
}

$arVideoList = array_keys($arVideoPathList);

for ($i = $nStartVideoID; $i < $nEndVideoID; $i ++) {
    
    $szVideoID = $arVideoList[$i];
    printf("###%d. Processing video [%s] ...\n", $i, $szVideoID);
    
    if (! isset($arVideoPathList[$szVideoID])) {
        continue;
    }
    
    $szVideoPath = $arVideoPathList[$szVideoID];
    
    // specific for one video program
    $szLocalDir2 = sprintf("%s/%s", $szLocalDir, $szVideoID);
    makeDir($szLocalDir2);
    
    $arOutputFeature = array();
    $nIndex = 0;
    foreach($arFeatureList as $szInputFeatureExt)
    {
        $szCoreName = sprintf("%s.%s", $szVideoID, $szInputFeatureExt);
        $szInputFeatureDir = sprintf("%s/%s", $szRootFeatureDir, $szInputFeatureExt);
        $szFPFeatureFN = sprintf("%s/%s/%s.tar.gz", $szInputFeatureDir, $szVideoPath, $szCoreName);
        $szCmdLine = sprintf("tar -xvf %s -C %s", $szFPFeatureFN, $szLocalDir2);
        execSysCmd($szCmdLine);
        $szFPLocalFeatureFN = sprintf("%s/%s", $szLocalDir2, $szCoreName);
        $arFeatureTmp = loadOneSvfFeatureFile($szFPLocalFeatureFN);
        
        // doEarlyFusion ==> just concatenate ==> re-index
        foreach($arFeatureTmp as $szKeyFrameID => $arFV)
        {
            $arFusedFV = array();
            foreach($arFV as $nKey => $szVal)
            {
                $nNewKey = $nKey + $nIndex*$nMaxDimsForOneFeature;
                $arFusedFV[$nNewKey] = $szVal;
            }
            
            if(!isset($arOutputFeature[$szKeyFrameID]))
            {
                $arOutputFeature[$szKeyFrameID] = $arFusedFV;
            }
            else
            {
                $arOutputFeature[$szKeyFrameID] = $arOutputFeature[$szKeyFrameID] + $arFusedFV;
                //print_r($arOutputFeature[$szKeyFrameID]);   exit();
            }
        }
        
        $nIndex ++;
    }
    printf("Generating output file ...\n");
    $arDestOutput = array();
    foreach($arOutputFeature as $szKeyFrameID => $arFV)
    {
        $szFeature = convertFeatureVector2SvfFormat($arFV);
        $arDestOutput[] = sprintf("%s %% %s %s %s", $szFeature, $szVideoID, $szVideoID, $szKeyFrameID);   
    }
    $szCoreOutputName = sprintf("%s.%s", $szVideoID, $szDestFeatureExt);
    $szFPLocalOutputFN = sprintf("%s/%s", $szLocalDir2, $szCoreOutputName);
    saveDataFromMem2File($arDestOutput, $szFPLocalOutputFN);
    
    $szOutputFeatureDir = sprintf("%s/%s/%s", $szRootFeatureDir, $szDestFeatureExt, $szDestVideoPath);
    makeDir($szOutputFeatureDir);
    
    $szFPOutputFN = sprintf("%s/%s.tar.gz", $szOutputFeatureDir, $szCoreOutputName);
    
    $szCmdLine = sprintf("tar -cvzf %s -C %s %s", $szFPOutputFN, $szLocalDir2, $szCoreOutputName);
    execSysCmd($szCmdLine);
    
    // clean up
    $szCmdLine = sprintf("rm -rf %s", $szLocalDir2);
    execSysCmd($szCmdLine);
}


// /////////////////////////////// FUNCTIONS /////////////////////////////////

/**
 * OutputFeature#$#nsc.bow.harlap.norm3x1+1x1.sift
 * InputFeature#$#
 * InputFeature#$#
 */
function loadFusionConfig($szFPFusionConfigFN)
{
    loadListFile($arRawList, $szFPFusionConfigFN);
    
    $arOutput = array();
    foreach ($arRawList as $szLine) {
        $arTmp = explode("#$#", $szLine);
        
        $szKey = trim($arTmp[0]);
        $szVal = trim($arTmp[1]);
        
        if ($szKey == "InputFeature") {
            $arOutput["InputFeature"][] = $szVal;
        }
        
        if ($szKey == "OutputFeature") {
            $arOutput["OutputFeature"] = $szVal;
        }
        
        if($szKey == "VideoPath")
        {
            $arOutput["VideoPath"] = $szVal;
        }
    }
    
    return $arOutput;
}
?>