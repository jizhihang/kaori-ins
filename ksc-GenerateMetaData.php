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
$arMaxClipsPerVideo = array(2011 => 10, 2012 => 35, 2013 => 250);

if($argc !=3)
{
	printf("Usage: %s <Year> <Pat>\n", $arg[0]);
	printf("Usage: %s 2011 test|query\n", $arg[0]);
	exit();
}
$nTargetYear = $argv[1];
$szPat = $argv[2];

foreach($arYearList as $nYear)
{
	if($nTargetYear != $nYear)
	{
		continue;
	}
	$szTVYear = sprintf("tv%d", $nYear);
	
	// for query
	if($szPat == "query")
	{
	    $szInputKeyFrameDir = sprintf("%s/%s/query", $szRootKeyFrameDir, $szTVYear);
	    $arDirList = collectDirsInOneDir($szInputKeyFrameDir);
	    sort($arDirList);
	    $arVideoList = array();
	    foreach($arDirList as $szDirName)
	    {
    		$szVideoID = sprintf("Q_%d", $szDirName);
	    	$szSubDirName = sprintf("%s/%s", $szInputKeyFrameDir, $szDirName);
	    	$arKeyFrameList = collectFilesInOneDir($szSubDirName, "", ".jpg");
	    	sort($arKeyFrameList);
	    	$szShotID = $szDirName;
	    
    		foreach($arKeyFrameList as $szKeyFrameID)
    		{
    			$arVideoList[$szVideoID][] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
    			$nTotalKeyFrames++;
    		}
	    }
	    $szOutputDir = sprintf("%s/%s/query", $szRootMetaDataDir, $szTVYear);
	    makeDir($szOutputDir);
	    $szFPOutputFN = sprintf("%s/%s/%s.query.lst", $szRootMetaDataDir, $szTVYear, $szTVYear); // tv2011.lst
	    saveDataFromMem2File(array_keys($arVideoList), $szFPOutputFN);
	    foreach($arVideoList as $szVideoID => $arKeyFrameList)
	    {
	    	$szFPOutputFN = sprintf("%s/%s.prg", $szOutputDir, $szVideoID); // tv2011.lst
	    	saveDataFromMem2File($arKeyFrameList, $szFPOutputFN);
	    }	     
	}
	
	// for test database
	if($szPat == "test")
	{
    	$szInputKeyFrameDir = sprintf("%s/%s/test", $szRootKeyFrameDir, $szTVYear);
    	
        $nMaxClipsPerVideo = $arMaxClipsPerVideo[$nYear];
    	if($nYear == 2013)
    	{
            $szFPInputFN = sprintf("%s/%s/clips.txt", $szRootMetaDataDir, $szTVYear);
    	    loadListFile($arDirList, $szFPInputFN);    
    	}
    	else 
    	{
    	   $arDirList = collectDirsInOneDir($szInputKeyFrameDir);
    	   sort($arDirList);
    	}
    	
    	// each dir --> one clip/shot
    	$nIndex = 0;
    	$nVideoID = 0;
    	$arVideoList = array();
    	$szPrefix = sprintf("TRECVID%d", $nYear); // TRECVID2011_1 --> videoID
    	$nTotalKeyFrames = 0;
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
    		$nNumKeyFrames = sizeof($arKeyFrameList);
    		$szShotID = $szDirName;
    		
    		if($nYear == 2013)
    		{
    			$nMaxKFPerShot = 5;
    			if($nNumKeyFrames <= $nMaxKFPerShot)
    			{
    			    foreach($arKeyFrameList as $szKeyFrameID)
    			    {
    			    	$arVideoList[$szVideoID][] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
    			    	$nTotalKeyFrames++;
    			    }			     
    			}
    			else
    			{
    			    $arList = array();
    			    $nMiddle1 = intval($nNumKeyFrames*0.1);
    			    $nMiddle2 = intval($nNumKeyFrames*0.3);			    
    			    $nMiddle3 = intval($nNumKeyFrames*0.5);
    			    $nMiddle4 = intval($nNumKeyFrames*0.7);
    			    $nMiddle5 = intval($nNumKeyFrames*0.9);
    			    $arList[$nMiddle1] = 1;			    
    			    $arList[$nMiddle2] = 1;			    
    			    $arList[$nMiddle3] = 1;			    
    			    $arList[$nMiddle4] = 1;			    
    			    $arList[$nMiddle5] = 1;
    			    foreach($arList as $nMiddle => $nTmp)
    			    {
    			        if(isset($arKeyFrameList[$nMiddle]))
    			        {
                            $szKeyFrameID = $arKeyFrameList[$nMiddle];
    			            $arVideoList[$szVideoID][] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
    			            $nTotalKeyFrames++;
    			        }
    			        else 
    			        {
    			            printf("### Warning [%d][%s]\n", $nNumKeyFrames, $nMiddle);
    			            exit();
    			        }			         
    			    }			    
    			}
    		}
    		else 
    		{
    			foreach($arKeyFrameList as $szKeyFrameID)
    			{
    				$arVideoList[$szVideoID][] = sprintf("%s#$#%s", $szShotID, $szKeyFrameID);
    				$nTotalKeyFrames++;				
    			}
    		}
    		printf("###[%s] - [%s]\n", $szVideoID, $nTotalKeyFrames);
    	}
    	
    	$szOutputDir = sprintf("%s/%s/test", $szRootMetaDataDir, $szTVYear);
    	makeDir($szOutputDir);
    	$szFPOutputFN = sprintf("%s/%s/%s.test.lst", $szRootMetaDataDir, $szTVYear, $szTVYear); // tv2011.lst
    	saveDataFromMem2File(array_keys($arVideoList), $szFPOutputFN);
    	foreach($arVideoList as $szVideoID => $arKeyFrameList)
    	{
    		$szFPOutputFN = sprintf("%s/%s.prg", $szOutputDir, $szVideoID); // tv2011.lst
    		saveDataFromMem2File($arKeyFrameList, $szFPOutputFN);
    	}
    	printf("Total keyframes: %s\n", $nTotalKeyFrames);
	}
}

?>