<?php

/**
 * 		@file 	ksc-Tool-GenerateMetaData-SGE.php
 * 		@brief 	Organize images of devel partition and test partition into subdirs.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 11 Aug 2013.
 */

//*** Update Aug 11
// This is for optimization of using grid

// JOBS
// Create .prg file  --> list of keyframes of one video program
// Create test.lst file --> list of video programs
// One video program ~ 100 images ==> devel.lst --> 150 videos

/////////////////////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

$szRootDir = $gszRootBenchmarkDir; // "/net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012";

///////////////////////////// SGE JOBS ///////////////////////////////////

$szProjectCodeName = "kaori-secode-ins13"; // *** CHANGED ***
$szCoreScriptName = "ksc-Tool-GenerateMetaData";  // *** CHANGED ***

$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);


//////////////////////////////////////// START ///////////////////////////////

if($argc != 2)
{
    printf("Usage: %s <nYear>\n", $argv[0]);
    printf("Usage: %s 2011\n", $argv[0]);
        exit();
}
$nYear = intval($argv[1]);

// map from src to dest
$arPatList = array(
		"test"
); 

$nMaxVideoPerDestPatList = 1000;

$arMaxHostsPerPatList = array(
		"test" => 100,
		);

foreach($arPatList as $szSrcPatName)
{
	// for running on grid, one job --> one block
	$szFPLogFN = "/dev/null";
	$arSGECmdLineList = array();
	// 	printf("Usage: %s <SrcPatName> <StartBlockID> <EndBlockID>\n", $argv[0]);

	$nMaxBlocks = $nMaxVideoPerDestPatList;
	$nNumBlocksPerJob = intval($nMaxBlocks/$arMaxHostsPerPatList[$szSrcPatName]);

	for($nBlockID=0; $nBlockID<$nMaxBlocks; $nBlockID+=$nNumBlocksPerJob)
	{
		$nStartBlockID = $nBlockID;
		$nEndBlockID = $nStartBlockID + $nNumBlocksPerJob;
		$szParam = sprintf("%s %s %s %s", $nYear, 
		    $szSrcPatName, $nStartBlockID, $nEndBlockID);
		$szSGECmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
		$arSGECmdLineList[] = $szSGECmdLine;
		$arSGECmdLineList[] = "sleep 10s";
	}

	$szFPOutputFN = sprintf("%s/runme.%s.tv%s.%s.sh", $szRootScriptOutputDir, $szCoreScriptName, $nYear, $szSrcPatName);
	saveDataFromMem2File($arSGECmdLineList, $szFPOutputFN);
}

?>