<?php

/**
 * 		@file 	ksc-BOW-GetKeyFrameSize-SGE.php
 * 		@brief 	Generate jobs for SGE to get keyframe size.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Jul 2013.
 */


// Update Aug 01
// Customize for tv2011

//////////////////////////////////////
// Update Jun 17
// Copied from nsc-ExtractKeyFrame-SGE.php

/////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";


//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

$szProjectCodeName = "kaori-secode-ins13";
$szCoreScriptName = "ksc-BOW-GetKeyFrameSize";

//$szSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-TVSIN11";
$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////
$arPatList = array(
		"subtest2012-new" => 100,
		"test2013-new" => 1000,
);

$arVideoPathList = array(
	"subtest2012-new" => "tv2012/subtest2012-new", 
	"test2013-new" => "tv2013/test2013-new", 
);

$nMaxHostsPerPat = 20;

$szFPLogFN = "/dev/null";

foreach($arPatList as $szPatName => $nMaxVideosPerPat)
{
	$szVideoPath = $arVideoPathList[$szPatName];

	$nNumVideosPerHost = intval($nMaxVideosPerPat/$nMaxHostsPerPat );

	$szScriptOutputDir = sprintf("%s/%s",
			$szRootScriptOutputDir, $szPatName);
	makeDir($szScriptOutputDir);
	
	$arCmdLine = array();
	for($j=0; $j<$nMaxVideosPerPat; $j+=$nNumVideosPerHost)
	{
		$nStart = $j;
		$nEnd = $nStart+$nNumVideosPerHost;

		$szParam = sprintf("%s %s %s %s", $szPatName, $szVideoPath, $nStart, $nEnd);

		$szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
		//execSysCmd($szCmdLine);
		$arCmdLine[] = $szCmdLine; 
		sleep(1);
	}
	$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh",
			$szScriptOutputDir, $szCoreScriptName, $szPatName); // specific for one set of data
	saveDataFromMem2File($arCmdLine, $szFPOutputFN);
	
}

?>
