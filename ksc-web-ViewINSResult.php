<?php

/**
 * 		@file 	ksc-web-ViewINSResult.php
 * 		@brief 	View query, groundtruth, and ranking result
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 04 Aug 2013.
 */


require_once "ksc-AppConfig.php";

//ob_start("ob_gzhandler"); 


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
	// load xml file
	printf("<SELECT NAME='vTVYear'>\n");
	printf("<OPTION VALUE='2013'>2013</OPTION>\n");
	printf("<OPTION VALUE='2012'>2012</OPTION>\n");
	printf("<OPTION VALUE='2011'>2011</OPTION>\n");
	printf("</SELECT>\n");

	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}

$nTVYear = $_REQUEST['vTVYear'];
$szTVYear = sprintf("tv%d", $nTVYear);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
$szMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szTVYear);

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
	printf("<OPTION VALUE='1'>Yes</OPTION>\n");
	printf("<OPTION VALUE='0'>No</OPTION>\n");
	printf("</SELECT>\n");
	
	printf("<P>RunID<BR>\n");
	// load xml file
	printf("<SELECT NAME='vRunID'>\n");
	printf("<OPTION VALUE='RunMatching'>Matching Using BoW</OPTION>\n");
	printf("<OPTION VALUE='RunDetection'>Detecting Using DPM</OPTION>\n");
	printf("<OPTION VALUE='RunFusion'>Fusion of Matching and Detection Scores</OPTION>\n");
	printf("</SELECT>\n");

	printf("<P>PageID<BR>\n");
	printf("<INPUT TYPE='TEXT' NAME='vPageID' VALUE='1' SIZE=10>\n");

	printf("<P>Max Videos Per Page<BR>\n");
	printf("<INPUT TYPE='TEXT' NAME='vMaxVideosPerPage' VALUE='100' SIZE=10>\n");

	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='2'>\n");
	printf("<P><INPUT TYPE='HIDDEN' NAME='vTVYear' VALUE='%s'>\n", $nTVYear);
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

// tv2013.test.lst 
$szFPTestVideoListFN = sprintf("%s/%s.test.lst", $szMetaDataDir, $szTVYear);

// include both jpg and png file
$szFPQueryImgListFN = sprintf("%s/query/Q_%s.prg", $szMetaDataDir, $szQueryID);
loadListFile($arQueryImgListz, $szFPQueryImgListFN);
$arQueryImgList = array();
sort($arQueryImgListz);
foreach($arQueryImgListz as $szLine)
{
	$arTmpx = explode("#$#", $szLine);
	$szQueryIDx = trim($arTmpx[0]);
	$szID = trim($arTmpx[1]);

	// 9068#$#9068.1.src
	$arQueryImgList[] = $szID;

}
//print_r($arQueryImgList);
//ins.search.qrels.tv2011
$szFPNISTResultFN = sprintf("%s/ins.search.qrels.%s", $szMetaDataDir, $szTVYear);

if(file_exists($szFPNISTResultFN))
{
	$arNISTList = parseNISTResult($szFPNISTResultFN);
}

$szFPOutputFN = sprintf("%s/ins.search.qrels.%s.csv", $szMetaDataDir, $szTVYear);
if(!file_exists($szFPOutputFN))
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
$arOutput[] = sprintf("<P><H1>Query - %s</H1><BR>\n", $szText);
foreach($arQueryImgList as  $szQueryImg)
{
		$szURLImg = sprintf("%s/query/%s/%s.jpg", $szKeyFrameDir, $szQueryID, $szQueryImg);
		$szRetURL = $szURLImg;
		$imgzz = imagecreatefromjpeg($szRetURL);
		$widthzz = imagesx($imgzz);
		$heightzz = imagesy($imgzz);

		// calculate thumbnail size
		$new_width = $thumbWidth = 200;  // to reduce loading time
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

// View result
$szRunID = $_REQUEST['vRunID'];
$szTopicID = $szQueryID;

$szResultDir = sprintf("%s/submission/%s", $szRootData, $szRunID);
$szFPRankListFN = sprintf("%s/%s.rank", $szResultDir, $szTopicID);

//$nNumVideos = loadListFile($arRawList, $szFPRankListFN);

//// VERY SPECIAL ****
$nShowGT = $_REQUEST['vShowGT'];
if($nShowGT)
{
	$arRawList = $arNISTList[$szQueryID];
	$nNumVideos = sizeof($arRawList);
}

////

$nCount = 0;
$nNumShownKFPerShot = 3;
//foreach($arRawList as $szLine)

$nMaxVideosPerPage = intval($_REQUEST['vMaxVideosPerPage']);
$nPageID = max(0, intval($_REQUEST['vPageID'])-1);
$nStartID = $nPageID*$nMaxVideosPerPage;
$nEndID = min($nStartID+$nMaxVideosPerPage, $nNumVideos, 1000);

$nNumPages = min(20, intval(($nNumVideos+$nMaxVideosPerPage-1)/$nMaxVideosPerPage));
$queryURL = sprintf("vQueryID=%s&vRunID=%s&vMaxVideosPerPage=%s&vAction=1&", urlencode($szQueryIDz), urlencode($szRunID), urlencode($nMaxVideosPerPage));
	//printf($queryURL);

$szURLz = sprintf("ksc-web-ViewINSResult.php?%s&vShowGT=1", $queryURL);

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
		$szURL = sprintf("ksc-web-ViewINSResult.php?%s&vPageID=%d&vShowGT=%d&vTVYear=%d&vAction=%d", $queryURL, $i+1, $nShowGT, $nTVYear, $nAction);
		$arOutput[] = sprintf("<A HREF='%s'>%02d</A> ", $szURL, $i+1);
	}
	else
	{
		$arOutput[] = sprintf("%02d ", $i+1);
	}
}
$arOutput[] = sprintf("<P><BR>\n");

for($i=$nStartID; $i<$nEndID; $i++)
{
	$szLine = $arRawList[$i];
	$arTmp = explode("#$#", $szLine);
	$szShotID = trim($arTmp[0]);
	$fScore = floatval($arTmp[1]);

	$szShotKFDir = sprintf("%s/test/%s", $szKeyFrameDir, $szShotID);
	$arImgList = collectFilesInOneDir($szShotKFDir, "", ".jpg");

	$arOutput[] = sprintf("%d. ", $nCount+1);
	$nCountz = 0;
	$nSampling = 0;
	$nNumKFzz = sizeof($arImgList);
	$nSamplingRate = intval($nNumKFzz/$nNumShownKFPerShot);
	foreach($arImgList as $szImg)
	{
		$nSampling++;
		if(($nSampling % $nSamplingRate) != 0)
		{
			continue;
		}

		$szURLImg = sprintf("%s/test/%s/%s.jpg",
				$szKeyFrameDir, $szShotID, $szImg);

		///
		// generate thumbnail image
		$szRetURL = $szURLImg;
		$imgzz = imagecreatefromjpeg($szRetURL);
		$widthzz = imagesx($imgzz);
		$heightzz = imagesy($imgzz);

		// calculate thumbnail size
		$new_width = $thumbWidth = 200;  // to reduce loading time
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
		$arOutput[] = sprintf("<IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64,". $szImgContent ."' />", $szShotID, $fScore);

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
	if(in_array($szShotID, $arNISTList[$szQueryID]))
	{
		$arOutput[] = sprintf("<IMG SRC='winky-icon.png'><BR>\n");
		$nHits++;
	}
	else
	{
		$arOutput[] = sprintf("<IMG SRC='sad-icon2.png'><BR>\n");
	}

	$arOutput[] = sprintf("<BR>\n");

	$nCount++;
	if($nCount > 100)
	{
		break;
	}
}

$arOutput[] = sprintf("<P><H1>Num hits (top %s): %d.</H1>\n", $nMaxVideosPerPage, $nHits);

$arOutput[] = sprintf("<P><H1>Page: ");
for($i=0; $i<$nNumPages; $i++)
{
	if($i != $nPageID)
	{
		$szURL = sprintf("ksc-web-ViewINSResult.php?%s&vPageID=%d&vShowGT=%d&vTVYear=%d&vAction=%d", $queryURL, $i+1, $nShowGT, $nTVYear, $nAction);
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

function parseNISTResult($szFPInputFN)
{
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
	}

	return $arOutput;
}
?>