<?php

/**
 * 		@file 	ksc-Tool-GenerateMetaData.php
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

/**
 *  // Update Aug 11
 *  1. For each shot/clip of caizhi data 
 *      --> generate one tar file to pack the keyframes of that shot/clip
 *      --> and copy to the new dir test-new
 *  2. Metadata is generated for test-new only.
 *  3. Special treatment for tv2013
 *      - New file name: shotID_keyframeID
 *      - Only pick 5KF/shot --> copy to local dir
 */
require_once "ksc-AppConfig.php";

// /net/per610a/export/das11f/ledduy/trecvid-ins-2013/keyframe-5
$szRootKeyFrameDir = sprintf("%s/keyframe-5", $gszRootBenchmarkDir); 
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
makeDir($szRootMetaDataDir);

$arYearList = array(2011, 2012, 2013);

// clips/shots will be organized into videos, max videos per year ~ 1,000
$nMaxVideoPerDestPatList = 1000;

if($argc !=5)
{
	printf("Usage: %s <Year> <Pat> <StartVideoID> <EndVideoID>\n", $arg[0]);
	printf("Usage: %s 2011 test|query 0 1\n", $arg[0]);
	exit();
}
$nTargetYear = $argv[1];
$szPat = $argv[2];
$nStartBlockID = intval($argv[3]);
$nEndBlockID = intval($argv[4]);


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
	    $szVideoPath = sprintf("%s/%s", $szTVYear, $szPat);
	    $szInputKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szVideoPath);
	    
	    // collect dirs --> keyframes must be organized in advance
	    $arDirList = collectDirsInOneDir($szInputKeyFrameDir);
	    sort($arDirList);
	    
	    $arVideoList = array();
	    $arVideoOutputList = array();
	    foreach($arDirList as $szDirName)
	    {
    		$szVideoID = sprintf("%d", $szDirName);
	    	$szSubDirName = sprintf("%s/%s", $szInputKeyFrameDir, $szDirName);
	    	$arKeyFrameList = collectFilesInOneDir($szSubDirName, "", ".jpg");
	    	sort($arKeyFrameList);
	    	$szShotID = $szDirName;
	    
	    	// VideoID #$# VideoName #$# VideoPath
	    	$arVideoOutputList[$szVideoID] = sprintf("%s#$#%s#$#%s", $szVideoID, $szVideoID, $szVideoPath);
    		foreach($arKeyFrameList as $szKeyFrameID)
    		{
    			$arVideoList[$szVideoID][] = sprintf("%s", $szKeyFrameID);
    			$nTotalKeyFrames++;
    		}
	    }
	    $szOutputDir = sprintf("%s/%s", $szRootMetaDataDir, $szVideoPath);
	    makeDir($szOutputDir);
	    $szFPOutputFN = sprintf("%s/%s/%s.%s.lst", $szRootMetaDataDir, $szTVYear, $szTVYear, $szPat); // tv2011.lst
	    saveDataFromMem2File(array_keys($arVideoOutputList), $szFPOutputFN);
	    foreach($arVideoList as $szVideoID => $arKeyFrameList)
	    {
	    	$szFPOutputFN = sprintf("%s/%s.prg", $szOutputDir, $szVideoID); // tv2011.lst
	    	saveDataFromMem2File($arKeyFrameList, $szFPOutputFN);
	    }	     
	}
	
	// for test database
	if($szPat == "test")
	{
	    $szVideoPath = sprintf("%s/%s", $szTVYear, $szPat);
	    $szInputKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szVideoPath);

	    // generate metadata for xxx-new pat
	    $szNewPat = sprintf("%s%d-new", $szPat, $nYear);
	    $szNewVideoPath = sprintf("%s/%s", $szTVYear, $szNewPat);
	    $szNewOutputKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szNewVideoPath);
	    makeDir($szNewOutputKeyFrameDir);
	     
        $szFPInputFN = sprintf("%s/%s/clips.txt", $szRootMetaDataDir, $szTVYear);
        if(file_exists($szFPInputFN))
        {
    	   $nNumClips = loadListFile($arDirList, $szFPInputFN);
    	}
    	else 
    	{
    	   $arDirList = collectDirsInOneDir($szInputKeyFrameDir);
    	   sort($arDirList);
    	   $nNumClips = sizeof($arDirList);
    	   saveDataFromMem2File($arDirList, $szFPInputFN);
    	}
    	
        //$nMaxClipsPerVideo = $arMaxClipsPerVideo[$nYear];

        $nMaxClipsPerVideo = intval($nNumClips/$nMaxVideoPerDestPatList) + 1;
                
    	
    	$arVideoList = array();
    	$arVideoOutputList = array();
    	 
    	$szPrefix = sprintf("TRECVID%d", $nYear); // TRECVID2011_1 --> videoID
    	$nTotalKeyFrames = 0;
    	
    	for($nBlockID=$nStartBlockID; $nBlockID<$nEndBlockID; $nBlockID++)
    	{
            $nVideoID = $nBlockID+1;    	    
    	    $szVideoID = sprintf("%s_%d", $szPrefix, $nVideoID);

    		// VideoID #$# VideoName #$# VideoPath
    		$arVideoOutputList[$szVideoID] = sprintf("%s#$#%s#$#%s", $szVideoID, $szVideoID, $szNewVideoPath);
    		
    		for($j=0; $j<$nMaxClipsPerVideo; $j++)
            {
                $nIndex = $nBlockID*$nMaxClipsPerVideo+$j;

        		if($nIndex>=$nNumClips)
                {
                    break;
                }    		
                $szDirName = $arDirList[$nIndex];
        		$szSubDirName = sprintf("%s/%s", $szInputKeyFrameDir, $szDirName);
        		$arKeyFrameList = collectFilesInOneDir($szSubDirName, "", ".jpg");
        		sort($arKeyFrameList);
        		$nNumKeyFrames = sizeof($arKeyFrameList);
        		$szShotID = $szDirName;
        		
        		if($nYear == 2013)
        		{
        		    // copy 5KF to local dir and rename
        		    $szHostLocalDir = "/local/ledduy";
        		    if(file_exists($szHostLocalDir))
        		    {
                        $szLocalDir = sprintf("/net/per610a/export/das11f/ledduy/tmp/tv%s/%s", $nYear, $szShotID);
        		    }
        		    else
        		    {
        		        $szLocalDir = sprintf("%s/tv%s/%s", $szHostLocalDir, $nYear, $szShotID);
        		    }
        		    makeDir($szLocalDir);
        		    $szFPSrcDir = $szLocalDir;
        		    
        		    $nMaxKFPerShot = 5;
        			if($nNumKeyFrames <= $nMaxKFPerShot)
        			{
        			    foreach($arKeyFrameList as $szKeyFrameID)
        			    {
                                // adding shotID as prefix
                            $szNewKeyFrameID = sprintf("%s_KSC%s", $szShotID, $szKeyFrameID);
                                
                            $szCmdLine = sprintf("cp %s/%s.jpg %s/%s.jpg", $szSubDirName, $szKeyFrameID,
                                    $szLocalDir, $szNewKeyFrameID);
                            execSysCmd($szCmdLine);
        			        
                            $arVideoList[$szVideoID][] = sprintf("%s", $szNewKeyFrameID);
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
                                
                                // adding shotID as prefix
                                $szNewKeyFrameID = sprintf("%s_KSC%s", $szShotID, $szKeyFrameID);
                                
                                $szCmdLine = sprintf("cp %s/%s.jpg %s/%s.jpg", $szSubDirName, $szKeyFrameID,
                                    $szLocalDir, $szNewKeyFrameID);
                                execSysCmd($szCmdLine);
        			            $arVideoList[$szVideoID][] = sprintf("%s", $szNewKeyFrameID);
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
                    $szFPSrcDir = $szSubDirName; 
        		    foreach($arKeyFrameList as $szKeyFrameID)
        			{
        				$arVideoList[$szVideoID][] = sprintf("%s", $szKeyFrameID);
        				$nTotalKeyFrames++;				
        			}
        		}
        		printf("###[%s] - [%s]\n", $szVideoID, $nTotalKeyFrames);
        		
        		// dest dir contains .tar files, each .tar file --> pack of keyframes of a shotID
        		// in feature extraction, all .tar files will be downloaded to tmp dir and extracted.
        		$szFPDestDir = sprintf("%s/%s", $szNewOutputKeyFrameDir, $szVideoID);
        		makeDir($szFPDestDir);
        		$szFPTarFN = sprintf("%s/%s.tar", $szFPDestDir, $szShotID);
        		 
        		// Use -C and . for excluding the path
        		$szCmdLine = sprintf("tar -cvf %s -C %s .", $szFPTarFN, $szFPSrcDir);
        		execSysCmd($szCmdLine);
        		
        		if($nYear == 2013)
        		{
        		    $szCmdLine = sprintf("rm -rf %s", $szFPSrcDir);
        		    execSysCmd($szCmdLine);
        		}
        		//break; // Debug only
        		
        	}
        	$szOutputDir = sprintf("%s/%s", $szRootMetaDataDir, $szNewVideoPath);
        	makeDir($szOutputDir);
        	$szFPOutputFN = sprintf("%s/%s/%s.lst", $szRootMetaDataDir, $szTVYear, $szNewPat); // tv2011.lst

        	// BE careful --> a+t mode (due to running on SGE)
        	saveDataFromMem2File(array_keys($arVideoOutputList), $szFPOutputFN, "a+t");
    
        	foreach($arVideoList as $szVideoID => $arKeyFrameList)
        	{
        		$szFPOutputFN = sprintf("%s/%s.prg", $szOutputDir, $szVideoID); // tv2011.lst
        		saveDataFromMem2File($arKeyFrameList, $szFPOutputFN);
        	}
        	printf("Total keyframes: %s\n", $nTotalKeyFrames);
    	}
	}
}

?>