<?php

/**
 * 		@file 	ksc-web-ViewMatch.php
 * 		@brief 	View matches between query and shot.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Aug 2014.
 */

// 06 Aug 2014
// Modify code because the dir structure is changed
// Before: runID/tv2013/test2013
// Current: tv2013/test2013/runID
// Do not use szPatName

//  13 Jul 2014
// Copied from ksc-web-ViewResult.php

require_once "ksc-AppConfig.php";
require_once "ksc-Tool-EvalMAP.php";
//ob_start("ob_gzhandler"); 

$arBoundingBoxList = array(); // used for displaying bounding box of DPM result
$thumbWidth = 200;
$nNumShownKFPerShot = 5;
$fConfigScale = 1; // scale factor of DPM model

// added on Jul 09, 2014
$arImgFormatLUT  = array(
2013 => "png",
2012 => "jpg",
2011 => "jpg"
);

$szImgFormat = "jpg";


////////////////// START //////////////////

$nTVYear = $_REQUEST['vTVYear'];
$szTVYear = sprintf("tv%d", $nTVYear);

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $gszRootBenchmarkDir);
$szKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szTVYear);

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);
$szQueryPat= sprintf("query%d", $nTVYear);
$szTestPat= sprintf("test%d", $nTVYear);
$szTmpDirz1 = sprintf("%s/tmp", $gszRootBenchmarkDir);

$szPatName4KFDir = sprintf("test%s", $nTVYear); //duplicate
$szPatName4ModelDir = sprintf("query%s", $nTVYear);

$szRootModelDir = sprintf("%s/model/ins-dpm/%s/%s", $gszRootBenchmarkDir, $szTVYear, $szPatName4ModelDir);

// ins.topics.2013.xml 
$szFPInputFN = sprintf("%s/ins.topics.%d.xml", $szMetaDataDir, $nTVYear);
$arQueryList = loadQueryDesc($szFPInputFN);

$szFPInputFN = sprintf("%s/ins.search.qrels.%s.csv", $szMetaDataDir, $szTVYear);
$arQueryListCount = array();
if(file_exists($szFPInputFN))
{
    loadListFile($arList, $szFPInputFN);
    foreach($arList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        $szQueryIDx = trim($arTmp[0]);
        $nCount = intval($arTmp[1]);
        $arQueryListCount[$szQueryIDx] = $nCount;
    }
}


$szResultDir = sprintf("%s/result", $gszRootBenchmarkDir);
$arDirList = collectDirsInOneDir($szResultDir);
sort($arDirList);

$szImgFormat = $arImgFormatLUT[$nTVYear];

// view query images
$szQueryIDz = $_REQUEST['vQueryID'];
$arTmp = explode("#", $szQueryIDz);
$szQueryID = trim($arTmp[0]);
$szText = trim($arTmp[1]);

$szRunID = $_REQUEST['vRunID'];

// include both jpg and png file
$szQueryPatName = sprintf("query%s", $nTVYear);
$szQueryKeyFrameDir = sprintf("%s/%s/%s", $szKeyFrameDir, $szQueryPatName, $szQueryID);
$arQueryImgList = collectFilesInOneDir($szQueryKeyFrameDir, ".src.", ".png");
//print_r($arQueryImgList);exit();
//ins.search.qrels.tv2011
$szFPNISTResultFN = sprintf("%s/ins.search.qrels.%s", $szMetaDataDir, $szTVYear);

if(file_exists($szFPNISTResultFN))
{
	$arNISTList = parseNISTResult($szFPNISTResultFN);
}

// for computing MAP online
$nTotalHits = sizeof($arNISTList[$szQueryID]);
$arAnnList = array();
foreach($arNISTList[$szQueryID] as $szShotID)
{
    $arAnnList[$szShotID] = 1;    
}

$fConfigScale = -1; // meaning [N/A]
$szModelDir = sprintf("%s/%s", $szRootModelDir, $szQueryID);
$szFPModelConfigFN = sprintf("%s/%s.cfg", $szModelDir, $szQueryID);
if(file_exists($szFPModelConfigFN))
{
    loadListFile($arRawListz, $szFPModelConfigFN);
    // Scale : 2.000000
    $arTmp1 = explode(":", $arRawListz[0]);
    //print_r($arTmp1);
    $fConfigScale = floatval($arTmp1[1]);    
}
else
{
    printf("Model config file [%s] not found\n", $szFPModelConfigFN);
}

$szFPOutputFN = sprintf("%s/ins.search.qrels.%s.csv", $szMetaDataDir, $szTVYear);
if(!file_exists($szFPOutputFN) || !filesize($szFPOutputFN))
{
    $arTmpOutput = array();
    foreach($arNISTList as $szQueryIDx => $arTmp)
    {
    	printf("<P>Query [%s] - Count [%d]\n", $szQueryIDx, sizeof($arTmp));
    	$arTmpOutput[] = sprintf("%s#$#%s", $szQueryIDx, sizeof($arTmp));
    }
    
    saveDataFromMem2File($arTmpOutput, $szFPOutputFN);
}

////////////////// SHOW QUERY ///////////////////
$szRootKeyFrameDir = sprintf("%s/keyframe-5", $gszRootBenchmarkDir);
$szKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szTVYear);
$arOutput = array();
$arOutput[] = sprintf("<P><H1>RunID: %s</H1>\n", $szRunID);
$arOutput[] = sprintf("<P><H1>Query - %s</H1>\n", $szText);
$arOutput[] = sprintf("<P><H1>Scale factor (to scale up the test image using DPM model) - %0.4f</H1><BR>\n", $fConfigScale);
foreach($arQueryImgList as  $szQueryImg)
{
		$szURLImg = sprintf("%s/%s/%s/%s.%s", $szKeyFrameDir, $szQueryPatName, $szQueryID, $szQueryImg, "png");
		if(!file_exists($szURLImg))
		{
            printf("<!-- File not found [%s] -->\n", $szURLImg);		  
		}
		$szRetURL = $szURLImg;
		
		// TIEP: Em co duoc ds cac QueryImg tu cho nay

		$imgzz = imagecreatefrompng($szRetURL);
		$widthzz = imagesx($imgzz);
		$heightzz = imagesy($imgzz);

		// calculate thumbnail size
		$new_width = $thumbWidth;  // to reduce loading time
		$new_height = floor($heightzz*($thumbWidth/$widthzz));
		
		// create a new temporary image
		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		// copy and resize old image into new image
		// imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);

		// better quality compared with imagecopyresized
		imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
		//output to buffer
		ob_start();
		imagejpeg($tmp_img);
		$szImgContent = base64_encode(ob_get_clean());
		$arOutput[] = sprintf("<IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64,". $szImgContent ."' />", $szQueryImg, $fScore);

		imagedestroy($imgzz);
		imagedestroy($tmp_img);
		//		$arOutput[] = sprintf("<IMG SRC='%s' WIDTH='100' TITLE='%s'/> \n", $szURLImg, $szQueryImg);
}
$arOutput[] = sprintf("<P><BR>\n");



$arOutput[] = sprintf("<BR>\n");
$szShotID = $_REQUEST["vShotID"];
$szShotKFDir = sprintf("%s/test/%s", $szKeyFrameDir, $szShotID);
	
$arImgList = collectFilesInOneDir($szShotKFDir, "", "." . $szImgFormat);

// TIEP: Tu cho nay em co the co duoc ds cac image cua shot	
$arOutput[] = sprintf("<P><H1>ShotID - %s</H1>\n", $szShotID);
	$nCountz = 0;
	$nSampling = 0;
	$nNumKFzz = sizeof($arImgList);
	$nSamplingRate = intval($nNumKFzz/$nNumShownKFPerShot);
	
	$arSelList = array();

	foreach($arImgList as $szImg)
	{
		$nSampling++;
		if(($nSampling % $nSamplingRate) != 0)
		{
			continue;
		}

		$szURLImg = sprintf("%s/test/%s/%s.%s",
				$szKeyFrameDir, $szShotID, $szImg, $szImgFormat);			
		///
		// generate thumbnail image
		$szRetURL = $szURLImg;
		
		if($szImgFormat == "png")
		{
			$imgzz = imagecreatefrompng($szRetURL);
		}
		else
		{
			$imgzz = imagecreatefromjpeg($szRetURL);
		}
		
		if(!$imgzz)
		{
			printf("<P>Error in loading image [%s]<br>\n", $szRetURL);
			exit();
		}


		$widthzz = imagesx($imgzz);
		$heightzz = imagesy($imgzz);

		// calculate thumbnail size
		$new_width = $thumbWidth;  // to reduce loading time
		
		$fScaleFactor = 1.0*$thumbWidth/$widthzz/$fConfigScale;
		$new_height = floor($heightzz*($thumbWidth/$widthzz));

		// create a new temporary image
		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		// copy and resize old image into new image
		// imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);

		// better quality compared with imagecopyresized
		imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
		
		$red = imagecolorallocate($tmp_img, 255, 0, 0);
		$green = imagecolorallocate($tmp_img, 0, 255, 0);

		//print_r($arBoundingBoxList[$szShotID]);
		//exit($szKeyFrameIDz);
		
		$nMatch = 0;
		foreach($arBoundingBoxList[$szShotID] as $szKeyFrameIDz => $arCoods)
		{
		    //print_r($arCoods); exit();
		    //exit("$szKeyFrameIDz - $szImg");
		    if(strstr($szKeyFrameIDz, $szImg))
		    {
		      $nLeft = intval($arCoods['l']*$fScaleFactor);
		      $nTop = intval($arCoods['t']*$fScaleFactor);
		      $nRight = intval($arCoods['r']*$fScaleFactor);
		      $nBottom = intval($arCoods['b']*$fScaleFactor);
		      
		      $arSelList[] = $szImg;
		      $nMatch = 1;
		      break;
		    }
		    else  // keep it for the case of no match 
		    {
		        $nLeft = intval($arCoods['l']*$fScaleFactor);
		        $nTop = intval($arCoods['t']*$fScaleFactor);
		        $nRight = intval($arCoods['r']*$fScaleFactor);
		        $nBottom = intval($arCoods['b']*$fScaleFactor);
		    }
        }

		if($nMatch)
		{
			imagerectangle($tmp_img, $nLeft, $nTop, $nRight, $nBottom, $red);	// true detection result
		}
		else
		{
			imagerectangle($tmp_img, $nLeft, $nTop, $nRight, $nBottom, $green); // just for reference	because the keyframe is different - might be OK if two frames are adjcent
		}
			
        
 //       print_r($arBoundingBoxList[$szShotID]);
//        print_r($arImgList);exit();
        
		ob_start();
		imagejpeg($tmp_img);
		$szImgContent = base64_encode(ob_get_clean());
		// update Jul 13, 2014 --> adding URL to view matched points
				
		$arOutput[] = sprintf("<IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64,". $szImgContent ."' />", $szURL, $szShotID, $fScore);

		imagedestroy($imgzz);
		imagedestroy($tmp_img);
		///
		//		$arOutput[] = sprintf("<IMG SRC='%s' WIDTH='100' TITLE='%s - %s'/> \n", $szURLImg, $szImg, $fScore);
		$nCountz++;
		if($nCountz>=$nNumShownKFPerShot)
		{
			break;
		}
	}

	$arOutput[] = sprintf("<BR>\n");


// RANSAC
$nMatchingMethod = $_REQUEST['vMatchMethod'];
$nMatchingMethod = 1; //default
$arMatchingMethodDesc = array(
//0 => "BOW", 
1 => "RANSAC");

foreach($arMatchingMethodDesc as $nMatchingMethod => $szDesc)
{
	$arOutput[] = sprintf("<P><H1>Matching Result by Using [%s] </H1>\n", $szDesc);
	
	printf("<!-- Matching by [%s] -->", $szDesc);
	$szCodeDir = "/net/per900c/raid0/ledduy/github-projects/kaori-ins2014/nvtiep/web"; // must be set to 777 before running

	$szOutputDirz = $szTmpDirz1; // must be set to 777 before running
	
	// unique dir for each pair (query, shot)
	$szTmpOutputDir = sprintf("%s/%s-%s-%s", $szOutputDirz, $szDesc, $szQueryID, $szShotID);
	makeDir($szTmpOutputDir);
	$szCmdLine = sprintf("chmod 777 %s", $szTmpOutputDir);
	system($szCmdLine);
	printf("<!-- Making dir: [%s]-->", $szTmpOutputDir); 
	
	// for commands
	$szTmpOutputFN = sprintf("%s/zz%s-%s.sh", $szTmpOutputDir, $szQueryID, $szShotID);
	
	// for output data
	$szFPOutputFNz = sprintf("%s/zz%s-%s-%s.logz", $szTmpOutputDir, $szQueryID, $szShotID, $nMatchingMethod);
	
	$arCmdLine = array();
	$arCmdLine[] = sprintf("export MATLAB_PREFDIR=%s", $szTmpOutputDir);   // change pref dir to avoid permission error
	$arCmdLine[] = sprintf("cd %s", $szCodeDir);
	
	if($nMatchingMethod == 0)
	{
		$arCmdLine[] = sprintf("/usr/local/matlab/bin/matlab -nodisplay -nojvm -r \"find_pair_matching_set2set_BOW('%s' , '%s', '%s' , '%s', '%s', '%s' , '%s', '%s')\"", 
		    $szTVYear, $szTestPat, $szQueryPat, $szFPOutputFNz, $szRunID, $szQueryID, $szShotID, $szTmpOutputDir);
	}

	if($nMatchingMethod == 1)
	{
		$arCmdLine[] = sprintf("/usr/local/matlab/bin/matlab -nodisplay -nojvm -r \"find_pair_matching_set2set_RANSAC('%s' , '%s', '%s' , '%s', '%s', '%s' , '%s', '%s')\"", 
		    $szTVYear, $szTestPat, $szQueryPat, $szFPOutputFNz, $szRunID, $szQueryID, $szShotID, $szTmpOutputDir);
	}
	saveDataFromMem2File($arCmdLine, $szTmpOutputFN);
	$szCmdLine = sprintf("chmod 777 %s", $szTmpOutputFN);
	system($szCmdLine);
	print_r($arCmdLine);
	system($szTmpOutputFN);
	
	if(!file_exists($szFPOutputFNz))
	{
	    printf("<P>Error result file not found [%s]\n", $szFPOutputFNz);
	    continue;
	}
	loadListFile($arTmpzz, $szFPOutputFNz);
	$arScoreList = array();
	$arFGMatchList = array();
	$arBGMatchList = array();
	foreach($arTmpzz as $szLine)
	{
		$arT = explode("#$#", $szLine);
		$szImageID = trim($arT[0]);
		$nNumFGMatch = intval($arT[1]);
		$nNumBGMatch = intval($arT[2]);
		$fScore = floatval($arT[3]);
		
		$arScoreList[$szImageID] = $fScore;
		$arFGMatchList[$szImageID] = $nNumFGMatch;
		$arBGMatchList[$szImageID] = $nNumBGMatch;
	}
	arsort($arScoreList);
//	$arMatchingImgList = collectFilesInOneDir($szTmpOutputDir, "", ".jpg");
	//print_r($arMatchingImgList);
	
	$nCount = 0;
	foreach($arScoreList as $szImg => $fScore)
	{
		$nNumFGMatch = $arFGMatchList[$szImg];
		$nNumBGMatch = $arBGMatchList[$szImg];
	
		$szRetURL = sprintf("%s/%s.jpg", $szTmpOutputDir, $szImg);
		//printf("Loading image [%s]\n", $szRetURL);

		$imgzz = imagecreatefromjpeg($szRetURL);
		$widthzz = imagesx($imgzz);
		$heightzz = imagesy($imgzz);

		// calculate thumbnail size
		$thumbWidth = intval($widthzz*0.75);
		$new_width = $thumbWidth;  // to reduce loading time
		$new_height = floor($heightzz*($thumbWidth/$widthzz));

		// create a new temporary image
		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		// copy and resize old image into new image
		// imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);

		// better quality compared with imagecopyresized
		imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
		//output to buffer
		ob_start();
		imagejpeg($tmp_img);
		$szImgContent = base64_encode(ob_get_clean());
		$arOutput[] = sprintf("<P><IMG  TITLE='%s - Score: %0.4f' SRC='data:image/jpeg;base64,". $szImgContent ."' /> - [%dfg+%dbg - %0.6f]", $szImg, $fScore, $nNumFGMatch, $nNumBGMatch, $fScore);

		imagedestroy($imgzz);
		imagedestroy($tmp_img);
		
		$nCount++;
		if($nCount>5)
			break;  // to speedup
	}
	// SAU KHI LOAD XONG HET THI XOA CAC FILE TRONG DAY
	$szCmdLine = sprintf("rm -rf %s", $szTmpOutputDir);
	system($szCmdLine);
}

foreach($arOutput as $szLine)
{
	printf("%s\n", $szLine);
}

//ob_flush_end();
exit();

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


// Update Jul 12, 2014
// Use one global var to store judged shots --> in tv2013, there are many relevant shots but un-judged

function parseNISTResult($szFPInputFN)
{
	global $arJudgedShots;

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
		
		$arJudgedShots[$szQueryID][] = $szShotID; 
	}

	return $arOutput;
}


function loadRankedList($szResultDir, $nTVYear)
{
    
    global $arBoundingBoxList;
    
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
    		

    		// for dmp
    		$fLeft = floatval($arTmp[3]);
    		$fTop = floatval($arTmp[4]);
    		$fRight = floatval($arTmp[5]);
    		$fBottom = floatval($arTmp[6]);
    		 
    		$arBoundingBoxList[$szShotID][$szTestKeyFrameID]['l'] = $fLeft;
    		$arBoundingBoxList[$szShotID][$szTestKeyFrameID]['t'] = $fTop;
    		$arBoundingBoxList[$szShotID][$szTestKeyFrameID]['r'] = $fRight;
    		$arBoundingBoxList[$szShotID][$szTestKeyFrameID]['b'] = $fBottom;
    		//print_r($arBoundingBoxList); exit();
    		    		
    	}
    }
    arsort($arRankList);

    return ($arRankList);
}


?>
