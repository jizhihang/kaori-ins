<?php

/**
 * 		@file 	ksc-GenerateMetaData.php
 * 		@brief 	Generate metadata for INS task.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 04 Aug 2013.
 */

/** ############## DATASETS ##############
4. Datasets
- TV2011: 
+ 3 keyframes/sec
+ 100 keyframes/clip
+ Keyframe size; 352x288
+ 20,982 clips --> 2.1M keyframes 
+ 25 topics (9023-9047)
+ All videos were chopped into 20 to 10s clips using ffmpeg
+ ~ 100 hours, BBC rushes
+ Submission format: <item seqNum="1" shotId="8123"/>

- TV2012
+ 3 keyframes/sec
+ 74,958 clips --> 
+ 21 topics (9048-9068)
+ Keyframe size: 640x480
+ Flickr video
+ Submission format: <item seqNum="1" shotId="FL000000001"/>

- TV2013: 
+ 5 keyframes/sec
+ Keyframe size: 768x576
+ 471,526 shots --> 2.5M keyframes  
+ 30 topics (9069-9098)
+ BBC EastEnders, approximately 244 video files (totally 300 GB, 464 h)
+ Submission format: <item seqNum="1" shotId="shot4324_2" />
 */

/** 
 * --> One dir contains keyframes of one clip/shot (see submission format)
 * --> tv2011.lst --> list of videoID
 * --> videoID.lst (eg. TRECVID2011_1.lst) --> list of keyframes
 */

require_once "ksc-AppConfig.php";

// /net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5
$szRootKeyFrameDir = sprintf("%s/keyframe-5", $gszRootBenchmarkDir); 
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
makeDir($szRootMetaDataDir);

$arYearList = array(2011, 2012, 2013);

// clips/shots will be organized into videos, max videos per year ~ 2,000
$arMaxClipsPerVideo = array(2011 => 2, 2012 => 35, 2013 => 230);

if($argc !=2)
{
	printf("Usage: %s <Year>\n", $arg[0]);
	printf("Usage: %s 2011\n", $arg[0]);
	exit();
}
$nTargetYear = $argv[1];

foreach($arYearList as $nYear)
{
	if($nTargetYear != $nYear)
	{
		continue;
	}
	$szTVYear = sprintf("tv%d", nYear);
	$szInputKeyFrameDir = sprintf("%s/%s/test", $szRootKeyFrameDir, $szTVYear);
	
	$arDirList = collectDirsInOneDir($szInputKeyFrameDir);
	$nMaxClipsPerVideo = $arMaxClipsPerVideo[$nYear];
	
	// each dir --> one clip/shot
	$nIndex = 0;
	$nVideoID = 0;
	$arVideoList = array();
	$szPrefix = sprintf("TRECVID%d", $nYear); // TRECVID2011_1 --> videoID
	foreach($arDirList as $szDirName)
	{
		if(($nIndex % $nMaxClipsPerVideo) == 0)
		{
			$szVideoID = sprintf("%s_%d", $szPrefix, $nVideoID);
			$nVideoID++;
		}
		$nIndex++;
		$szSubDirName = sprintf("%s/%s", $szInputKeyFrameDir, $szDirName);
		$arKeyFrameList = collectFilesInOneDir($szSubDirName, "", ".jpg");
		sort($arKeyFrameList);
		foreach($arKeyFrameList as $szKeyFrameID)
		{
			$szShotID = $szDirName; 
			$arVideoList[$szVideoID][] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
		}
	}
	
	$szOutputDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);
	$szFPOutputFN = sprintf("%s/%s.lst", $szOutputDir, $szTVYear); // tv2011.lst
	saveDataFromMem2File(array_keys($arVideoList), $szFPOutputFN);
	foreach($arVideoList as $szVideoID => $arKeyFrameList)
	{
		$szFPOutputFN = sprintf("%s/%s.lst", $szOutputDir, $szVideoID); // tv2011.lst
		saveDataFromMem2File($arKeyFrameList, $szFPOutputFN);
	}
}

?>