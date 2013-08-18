<?php

/**
 * 		@file 	ksc-Matching-ComputeSimilarityForOneQuery-SGE.php
 * 		@brief 	Compute similarity.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 16 Aug 2013.
 */

////////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

$szProjectCodeName = "kaori-secode-ins13"; // *** CHANGED ***
$szCoreScriptName = "ksc-Matching-ComputeSimilarityForOneQuery"; // *** CHANGED ***

//$szSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-TVSIN11";
$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$arQueryPatList = array(
    "query2012-new",
    "queryext502012-new");

$arTestPatList = array(
		"subtest2012-new",
);


$arFeatureList = array();
$nMaxHostsPerPat = 100;
//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);

$arFeatureList = collectDirsInOneDir($szRootFeatureDir, "norm");
foreach($arQueryPatList as $szQueryPatName)
{
    foreach($arTestPatList as $szTestPatName)
    {

        foreach($arFeatureList as $szFeatureExt)
        {
    		$nMaxVideosPerPat = $arMaxVideosPerPatList[$szTestPatName];
    		$nNumVideosPerHost = max(1, intval($nMaxVideosPerPat/$nMaxHostsPerPat)); // Oct 19

			$szScriptOutputDir = sprintf("%s/%s/%s",
					$szRootScriptOutputDir, $szQueryPatName, $szTestPatName);
			makeDir($szScriptOutputDir);

			$arCmdLineList =  array();

			for($j=0; $j<$nMaxVideosPerPat; $j+=$nNumVideosPerHost)
			{
				$nStart = $j;
				$nEnd = $nStart+$nNumVideosPerHost;

				// override if no use log file
				$szFPLogFN = "/dev/null";

				for($nQueryID=9048; $nQueryID<=9068; $nQueryID++)
				{
    				$szParam = sprintf("%s %s %s %s %s %s",
    						$nQueryID, $szQueryPatName, $szTestPatName, $szFeatureExt, $nStart, $nEnd);
    
    				$szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
    
    				$arCmdLineList[] = $szCmdLine;
				}				
				
			}
			$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.%s.%s.sh",
					$szScriptOutputDir, $szCoreScriptName, $szQueryPatName, $szTestPatName, $szFeatureExt); // specific for one set of data
			if(sizeof($arCmdLineList) > 0 )
			{
				saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
				$arRunFileList[] = $szFPOutputFN;
			}
		}
    }
}
?>