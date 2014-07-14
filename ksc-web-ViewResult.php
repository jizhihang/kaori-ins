<?php

/**
 * 		@file 	ksc-web-ViewResult.php
 * 		@brief 	View query, groundtruth, and ranking result.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 12 Jul 2014.
 */

//  09 Jul 2014
// Caizhi deleted keyframes in jpg format and replace by png format

// 12 Jul 2014
// Check and show unjudged shots

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

// Update Jul 12, 2014
// Use one global var to store judged shots --> in tv2013, there are many relevant shots but un-judged

$arJudgedShots = array();


////////////////// START //////////////////

$nAction = 0;
if(isset($_REQUEST['vAction']))
{
	$nAction = $_REQUEST['vAction'];
}

if($nAction == 0)
{
	printf("<P><H1>Select TVYear</H1>\n");
	printf("<FORM TARGET='_blank'>\n");
	printf("<P>TVYear<BR>\n");
	printf("<SELECT NAME='vTVYear'>\n");
	printf("<OPTION VALUE='2013'>2013</OPTION>\n");
	printf("<OPTION VALUE='2012'>2012</OPTION>\n");
	printf("<OPTION VALUE='2011'>2011</OPTION>\n");
	printf("</SELECT>\n");

	printf("<P>Partition<BR>\n");
	printf("<SELECT NAME='vPatName'>\n");
	printf("<OPTION VALUE='test2013-new'>test2013-new</OPTION>\n");
	printf("<OPTION VALUE='subtest2012-new'>subtest2012-new</OPTION>\n");
	printf("<OPTION VALUE='test2012-new'>test2012-new</OPTION>\n");
	printf("<OPTION VALUE='test2011-new'>test2011-new</OPTION>\n");
	printf("</SELECT>\n");
	
	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}

//$arVideoPathLUT[2012] = "tv2012/subtest2012-new";
//$arVideoPathLUT[2013] = "tv2013/test2013-new";

$nTVYear = $_REQUEST['vTVYear'];
$szTVYear = sprintf("tv%d", $nTVYear);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);
$szPatName = $_REQUEST['vPatName'];

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

//$szVideoPath = $arVideoPathLUT[$nTVYear];
$szVideoPath = sprintf("%s/%s", $szTVYear, $szPatName);

$szResultDir = sprintf("%s/result", $gszRootBenchmarkDir);
$arDirList = collectDirsInOneDir($szResultDir);
sort($arDirList);

// setting 777
foreach($arDirList as $szDirName)
{
	if(stristr($szDirName, "del"))
	{
		$szCmd = sprintf("chmod -R 777 %s/%s", $szResultDir, $szDirName);
		system($szCmd);
		//printf("<--%s-->\n", $szCmd);
	}
}

$szImgFormat = $arImgFormatLUT[$nTVYear];

//print_r($arQueryListCount);
// show form
if($nAction == 1)
{
	printf("<P><H1>View Results</H1>\n");
	printf("<FORM TARGET='_blank'>\n");
	printf("<P>Query<BR>\n");
	// load xml file
	printf("<SELECT NAME='vQueryID'>\n");
	
	foreach($arQueryList as $szQueryID => $szText)
	{
	    if(isset($arQueryListCount[$szQueryID]))
	    {
	        printf("<OPTION VALUE='%s#%s'>%s - %d</OPTION>\n", $szQueryID, $szText, $szText, $arQueryListCount[$szQueryID]);  	        
	    }
	    else
	    {
            printf("<OPTION VALUE='%s#%s'>%s</OPTION>\n", $szQueryID, $szText, $szText);
	    }
	}
	printf("</SELECT>\n");

	printf("<P>View GroundTruth<BR>\n");
	printf("<SELECT NAME='vShowGT'>\n");
	printf("<OPTION VALUE='0'>No</OPTION>\n");
	printf("<OPTION VALUE='1'>Yes</OPTION>\n");
	printf("</SELECT>\n");
	
	printf("<P>RunID<BR>\n");
	// load xml file
	printf("<SELECT NAME='vRunID'>\n");
	foreach($arDirList as $szRunID)
	{
	    if(!strstr($szRunID, $nTVYear))
	    {
	        continue;
	    }
	     
	   printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szRunID, $szRunID);
	}
//	printf("<OPTION VALUE='RunDetection'>Detecting Using DPM</OPTION>\n");
//	printf("<OPTION VALUE='RunFusion'>Fusion of Matching and Detection Scores</OPTION>\n");
	printf("</SELECT>\n");

	printf("<P>PageID<BR>\n");
	printf("<INPUT TYPE='TEXT' NAME='vPageID' VALUE='1' SIZE=10>\n");

	printf("<P>Max Videos Per Page<BR>\n");
	printf("<INPUT TYPE='TEXT' NAME='vMaxVideosPerPage' VALUE='100' SIZE=10>\n");

	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='2'>\n");
	printf("<P><INPUT TYPE='HIDDEN' NAME='vTVYear' VALUE='%s'>\n", $nTVYear);
	printf("<P><INPUT TYPE='HIDDEN' NAME='vPatName' VALUE='%s'>\n", $szPatName);
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}

// view query images
$szQueryIDz = $_REQUEST['vQueryID'];
$arTmp = explode("#", $szQueryIDz);
$szQueryID = trim($arTmp[0]);
$szText = trim($arTmp[1]);

$szRunID = $_REQUEST['vRunID'];

// include both jpg and png file
$szQueryPatName = sprintf("query%s-new", $nTVYear);
$szFPQueryImgListFN = sprintf("%s/%s/%s.prg", $szMetaDataDir, $szQueryPatName, $szQueryID);
loadListFile($arQueryImgList, $szFPQueryImgListFN);
//print_r($arQueryImgList);
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

$szFPModelConfigFN = sprintf("%s/%s.cfg", $szMetaDataDir, $szQueryID);
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
		$szURLImg = sprintf("%s/%s/%s/%s.%s", $szKeyFrameDir, $szQueryPatName, $szQueryID, $szQueryImg, "jpg");
		//exit($szURLImg);
		$szRetURL = $szURLImg;
		$imgzz = imagecreatefromjpeg($szRetURL);
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

//// VERY SPECIAL ****
$nShowGT = $_REQUEST['vShowGT'];
if($nShowGT)
{
	$arRawList = $arNISTList[$szQueryID];
}
else
{
    //printf("Path:$szVideoPath <BR>\n");
    $szQueryResultDir1 = sprintf("%s/%s/%s", $szResultDir, $szRunID, $szVideoPath);
    $szQueryResultDir = sprintf("%s/%s/%s/%s", $szResultDir, $szRunID, $szVideoPath, $szQueryID);

    $szFPOutputFN = sprintf("%s/%s.rank", $szQueryResultDir1, $szQueryID);
	
	//if the run is using DPM --> need to load .res since it contains info of bounding box
	if(stristr($szRunID, "dpm"))
	{
		$nLoadBoundingBox = 1;
	}
	else
	{	
		$nLoadBoundingBox = 0;
	}
    if(!file_exists($szFPOutputFN) || $nLoadBoundingBox)
    {
        $arRawListz = loadRankedList($szQueryResultDir, $nTVYear);
        $arRawList = array();
        $nCount = 0;
        foreach($arRawListz as $szShotID => $fScore)
        {
            $arRawList[] = sprintf("%s#$#%0.4f", $szShotID, $fScore);
            $nCount++;
            if($nCount>20000)
                break;
        }
        //saveDataFromMem2File($arRawList, $szFPOutputFN);
    }
    else
    {
        loadListFile($arRawList, $szFPOutputFN);
    }
}

$nNumVideos = sizeof($arRawList);
$arScoreList = array();
foreach($arRawList as $szLine)
{
    $arTmp = explode("#$#", $szLine);
    $szShotID = trim($arTmp[0]);
    $fScore = floatval($arTmp[1]);
    if(sizeof($arScoreList) < 10000)
    {
        $arScoreList[$szShotID] = $fScore;
    }
}

//$arTmpzzz = computeTVAveragePrecision($arAnnList, $arScoreList, $nMaxDocs=10000);
//print_r($arTmpzzz);

$arTmpzzz = computeTVAveragePrecision($arAnnList, $arScoreList, $nMaxDocs=1000);
$fMAP = $arTmpzzz['ap'];
$nTotalHitsz = $arTmpzzz['total_hits'];
$arOutput[] = sprintf("<P><H3>MAP: %0.2f. Num hits (@1000): %d<BR>\n", $fMAP, $nTotalHitsz);
////

$nCount = 0;
//foreach($arRawList as $szLine)

$nMaxVideosPerPage = intval($_REQUEST['vMaxVideosPerPage']);
$nPageID = max(0, intval($_REQUEST['vPageID'])-1);
$nStartID = $nPageID*$nMaxVideosPerPage;
$nEndID = min($nStartID+$nMaxVideosPerPage, $nNumVideos, 1000);

$nNumPages = min(20, intval(($nNumVideos+$nMaxVideosPerPage-1)/$nMaxVideosPerPage));
$queryURL = sprintf("vPatName=%s&vQueryID=%s&vRunID=%s&vMaxVideosPerPage=%s&vTVYear=%d&vAction=%d&", 
    $szPatName, urlencode($szQueryIDz), urlencode($szRunID), urlencode($nMaxVideosPerPage), $nTVYear, $nAction);
	//printf($queryURL);

$szURLz = sprintf("ksc-web-ViewResult.php?%s&vShowGT=1", $queryURL);

$nViewImg = 0;
if($nShowGT)
{
	$arOutput[] = sprintf("<P><H1>Ranked List - [Ground Truth] - [%d] Video Clips</H1>\n", $nNumVideos);
}
else
{
	$arOutput[] = sprintf("<P><H1>Total Relevant Videos <A HREF='%s'>[%s]</A>. Click the link to view all relevant ones!</H1>\n",
			$szURLz, sizeof($arNISTList[$szQueryID]));
}
$arOutput[] = sprintf("<P><H1>Page: ");
for($i=0; $i<$nNumPages; $i++)
{
	if($i != $nPageID)
	{
		$szURL = sprintf("ksc-web-ViewResult.php?%s&vPageID=%d&vShowGT=%d", $queryURL, $i+1, $nShowGT);
		$arOutput[] = sprintf("<A HREF='%s'>%02d</A> ", $szURL, $i+1);
	}
	else
	{
		$arOutput[] = sprintf("%02d ", $i+1);
	}
}

$arOutput[] = sprintf("<BR>\n");
//print_r($arScoreList);exit();
for($i=$nStartID; $i<$nEndID; $i++)
{
	$szLine = $arRawList[$i];
	$arTmp = explode("#$#", $szLine);
	$szShotID = trim($arTmp[0]);
	$fScore = floatval($arTmp[1]);

	$szShotKFDir = sprintf("%s/test/%s", $szKeyFrameDir, $szShotID);
	
	//$arImgList = collectFilesInOneDir($szShotKFDir, "", ".jpg");
	$arImgList = collectFilesInOneDir($szShotKFDir, "", "." . $szImgFormat);
	
	$arOutput[] = sprintf("%d. ", $nCount+1);
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
		$szURL = sprintf('ksc-web-ViewMatch.php?vQueryID=%s&vShotID=%s&vTVYear=%s&vPatName=%s&vRunID=%s', urlencode($szQueryIDz), $szShotID, $nTVYear, $szPatName, urlencode($szRunID));
				
		$arOutput[] = sprintf("<A HREF='%s' TARGET=_blank><IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64,". $szImgContent ."' /></A>", $szURL, $szShotID, $fScore);

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

/*	
	if(sizeof($arSelList) == 0)
	{
	    print_r($arBoundingBoxList[$szShotID]);
	    print_r($arImgList);exit();
	}
*/
		
	if(in_array($szShotID, $arNISTList[$szQueryID]))
	{
		$arOutput[] = sprintf("<IMG SRC='winky-icon.png'><BR>\n");
		$nHits++;
	}
	else
	{
		if(in_array($szShotID, $arJudgedShots[$szQueryID]))
		{
			$arOutput[] = sprintf("<IMG SRC='sad-icon2.png'><BR>\n");
		}
		else
		{
			$arOutput[] = sprintf("<IMG SRC='unknown-icon.png' WIDTH=50><BR>\n");
		}
	}

	$arOutput[] = sprintf("<BR>\n");

	$nCount++;
	if($nCount > 100)
	{
		break;
	}
}

$arOutput[] = sprintf("<P><H1>Num hits (top %s): %d/%d.</H1>\n", $nMaxVideosPerPage, $nHits, $nTotalHits);

$arOutput[] = sprintf("<P><H1>Page: ");
for($i=0; $i<$nNumPages; $i++)
{
	if($i != $nPageID)
	{
		$szURL = sprintf("ksc-web-ViewResult.php?%s&vPageID=%d&vShowGT=%d", $queryURL, $i+1, $nShowGT);
		$arOutput[] = sprintf("<A HREF='%s'>%02d</A> ", $szURL, $i+1);
	}
	else
	{
		$arOutput[] = sprintf("%02d ", $i+1);
	}
}
$arOutput[] = sprintf("<P><BR>\n");

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
