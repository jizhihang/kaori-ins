<?php

/**
 * 		@file 	ksc-web-ViewScoreBoard.php
 * 		@brief 	View perf of multi-runs at the same time.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 16 Apr 2015.
 */

// 16 Apr 2015 
// Adding graph view
 
// 06 Aug 2014
// Modify code because the dir structure is changed
// Before: runID/tv2013/test2013
// Current: tv2013/test2013/runID
// Do not use szPatName

// 12 Jul 2014
// Copied from ksc-web-ViewResult.php

// 28 Jul 2014
// Adding MAP info to list of runs

require_once "ksc-AppConfig.php";
require_once "ksc-Tool-EvalMAP.php";

require_once ('./jpgraph-3.5.0b1/src/jpgraph.php');
require_once ('./jpgraph-3.5.0b1/src/jpgraph_bar.php');
require_once ('./jpgraph-3.5.0b1/src/jpgraph_line.php');

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
	printf("<OPTION VALUE='2014'>2014</OPTION>\n");
	printf("<OPTION VALUE='2013'>2013</OPTION>\n");
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

$szPatName4KFDir = sprintf("test%s", $nTVYear); 

// ins.topics.2013.xml 
$szFPInputFN = sprintf("%s/ins.topics.%d.xml", $szMetaDataDir, $nTVYear);
$arQueryListLUT = loadQueryDesc($szFPInputFN);
//print_r($arQueryListLUT);

$szFPInputFN = sprintf("%s/ins.search.qrels.%s.csv", $szMetaDataDir, $szTVYear);
$arQueryListCount = array();
if(file_exists($szFPInputFN))
{
    loadListFile($arList, $szFPInputFN);
    foreach($arList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        $szQueryIDx = intval(trim($arTmp[0]));
        $nCount = intval($arTmp[1]);
        $arQueryListCount[$szQueryIDx] = $nCount;
    }
}

$szResultDir = sprintf("%s/result/%s/%s", $gszRootBenchmarkDir, $szTVYear, $szPatName4KFDir);
$arDirList = collectDirsInOneDir($szResultDir);
sort($arDirList);
//print_r($arDirList);exit();
//print_r($arQueryListCount);
// show form
if($nAction == 1)
{
	printf("<P><H1>View Score Board</H1>\n");
	printf("<FORM TARGET='_blank'>\n");
	printf("<P>Select Multi-RunID <BR>\n");

	foreach($arDirList as $szRunID)
	{		
		// adding MAP info
		$szMAP = "N/A";
		$szFPMAPFN = sprintf("%s/%s/%s.eval.ksc.csv", $szResultDir, $szRunID, $szRunID);
		if(file_exists($szFPMAPFN))
		{
			$nNumRows = loadListFile($arTmpz, $szFPMAPFN);
			$szMAP = sprintf("%0.4f", floatval($arTmpz[$nNumRows-1]));
		}
		else
		{
			printf("<!--File does not exist [%s]-->\n", $szFPMAPFN);
		}

		printf("<INPUT TYPE='CHECKBOX' NAME='vRunList[]' VALUE='%s'>%s - MAP: [%s]</BR>\n", $szRunID, substr($szRunID, 0, 100), $szMAP);
	}

	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='2'>\n");
	printf("<P><INPUT TYPE='HIDDEN' NAME='vTVYear' VALUE='%s'>\n", $nTVYear);
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}

printf("<P><H1>View Score Board</H1>\n");

printf("<P><H3>Each cell shows query description and number of relevant shots</H3>\n");

// view query images
$arRunList = $_REQUEST['vRunList'];
//print_r($arRunList);exit();

$arScoreBoard = array();
$arQueryList = array();

$arRunMAP = array();
foreach($arRunList as $szRunID)
{
	// load csv file
	// sftp://ledduy@per610a.hpc.vpl.nii.ac.jp/export/das11f/ledduy/trecvid-ins-2013/result/run_fusion2013-TiepBoW_No1_10K+DPM[R1=R2.1xw1=2.0-R2=R2.2xw2=1.0-Norm=1]/tv2013/test2013-new/run_fusion2013-TiepBoW_No1_10K+DPM[R1=R2.1xw1=2.0-R2=R2.2xw2=1.0-Norm=1].eval.ksc.csv
	$szFPPerfFN = sprintf("%s/%s/%s.eval.ksc.csv", $szResultDir, $szRunID, $szRunID);
	
	if(file_exists($szFPPerfFN))
	{
		loadListFile($arTmp, $szFPPerfFN);
		//print_r($arTmp); exit();
		
		foreach($arTmp as $szLine)
		{
			$arTmp = explode(" ", $szLine);
			if(sizeof($arTmp) > 1)
			{
				$nQueryID = intval($arTmp[0]);
				$arQueryList[$nQueryID] = 1;
				$fMAP = floatval($arTmp[1]);
				
				$arScoreBoard[$szRunID][$nQueryID] = $fMAP;							
			}
			else
			{
				$fAllMAP = floatval($arTmp[0]);
				$arScoreBoard[$szRunID]["MAP"] = $fAllMAP;
				$arRunMAP[$szRunID] = $fAllMAP;
			}
		}
	}
	else
	{
		//exit("File not found [$szFPPerfFN]\n");
		printf("<P>File not found [$szFPPerfFN]\n");
	}
}

printf("<TABLE BORDER=1>\n");
printf("<TR>\n");
printf("<TD> RunID </TD>\n");
printf("<TD> MAP </TD>\n");
//print_r($arQueryList);
foreach($arQueryList as $nQueryID => $fTmp)
{			
	printf("<TD> %s </TD>\n", $nQueryID);
}
printf("</TR>\n");

// sort 
arsort($arRunMAP);
//print_r($arRunMAP);

//foreach($arScoreBoard as $szRunID => $arTmp)

$ydata = array();
foreach($arRunMAP as $szRunID => $fMAP)
{
	$arTmp = $arScoreBoard[$szRunID];
	printf("<TR>\n");
	printf("<TD> %s</TD>\n", substr($szRunID, 0, 60));
	printf("<TD> %0.2f </TD>\n", $arTmp["MAP"]);
	foreach($arQueryList as $nQueryID => $fTmp)
	{
		//http://per900c.hpc.vpl.nii.ac.jp/users-ext/ledduy//www/kaori-ins/ksc-web-ViewResult.php?vQueryID=9069%239069+-+OBJECT+-+a+circular+&vShowGT=0&vRunID=1.1.run_query2013-new_test2013-new_Caizhi_No1_TV2013_soft_fg%2Bbg_6sift_asym_0.6&vPageID=1&vMaxVideosPerPage=100&vAction=2&vTVYear=2013&vPatName=test2013-new
		
		$szQueryZ = sprintf("%s#%s", $nQueryID, str_replace("'", "|", $arQueryListLUT[$nQueryID]));
		$szURL = sprintf("ksc-web-ViewResult.php?vQueryID=%s&vShowGT=0&vRunID=%s&vPageID=1&vMaxVideosPerPage=100&vAction=2&vTVYear=%s", urlencode($szQueryZ), urlencode($szRunID), $nTVYear);
		printf("<TD> <A TITLE=\"%s - %s - %s\" HREF=\"%s\" TARGET=_blank>%0.2f</A> </TD>\n", 
		$szRunID, $arQueryListLUT[$nQueryID], $arQueryListCount[$nQueryID], 
		$szURL, $arTmp[$nQueryID]);
		
		$ydata[$szRunID][$nQueryID] = $arTmp[$nQueryID];
	}
	printf("</TR>\n");

}
printf("</TABLE>\n");

printf("<P><P>\n");

// 16 Apr 2015
// Adding graph view

$nNumRuns = sizeof($arRunMAP);
if($nNumRuns > 5)
{
	//exit(); // only view max 4 runs
}

// Create the graph. These two calls are always required

$arKeyQueryList = array_keys($arQueryList);
$xtickdata = array_values($arKeyQueryList);

$arColorList = array("red", "aqua", "aquamarine4", "azure4", "bisque", "blue", "blueviolet", "brown", 
"cadetblue", "chartreuse4", "darkolivegreen3", "darkorchid3", "hotpink3"); // http://jpgraph.net/download/manuals/chunkhtml/apd.html

$arColorList = array("red", "aqua", "aquamarine4", "azure4", "bisque", "blue", "blueviolet", "brown", 
"cadetblue", "chartreuse4", "darkolivegreen3", "darkorchid3", "hotpink3"); // http://jpgraph.net/download/manuals/chunkhtml/apd.html

shuffle($arColorList);
//print_r($arColorList);
//print_r($xtickdata);
$nWidth = 50*sizeof($xtickdata);  // 50
$nHeight = 600;
$graph = new Graph($nWidth,$nHeight);  // --> important (size, auto)
$graph->SetScale('textlin'); // x-axis ~ text, y-axis ~ number
// Add a drop shadow
//$graph->SetShadow();

$theme_class = new UniversalTheme;
$graph->SetTheme($theme_class);

// Adjust the margin a bit to make more room for titles
$graph->SetMargin(40,30,20,40);
$graph->legend->SetColumns(1); // important
$graph->xaxis->SetTickLabels($xtickdata); // important

$nPlotType = 1;
if($nPlotType==1)
{
	$colorID = 0;
	foreach($arRunMAP as $szRunID => $fMAP)
	{
		// Create the linear plot
		$ytickdata = array_values($ydata[$szRunID]);
		$lineplot = new LinePlot($ytickdata);
		$lineplot->SetColor($arColorList[$colorID]); 
		$colorID++;
		$lineplot->SetWeight( 2 );   // Two pixel wide
		//$lineplot1->mark->SetType(MARK_UTRIANGLE);
		//$lineplot1->mark->SetType(MARK_IMG,$arFaceLegend[$szTargetName1],'0.5');
		//	$lineplot1->mark->SetFillColor('red');
		$lineplot->SetFillFromYMin(5); // important

		// Set the legends for the plots
		$szRunID2 = sprintf("%s", $szRunID);
		$lineplot->SetLegend($szRunID2);

		// Add the plot to the graph
		$graph->Add($lineplot);
	}
}

// Setup the titles
$graph->title->Set("MAP");
$graph->xaxis->title->Set('Query');
$graph->yaxis->title->Set('MAP');

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$szOutputName = sprintf("mygraphINS");
$szOutputName = str_replace(" ", "+", $szOutputName);
$szOutputDir = "/net/per900c/raid0/ledduy/tmpann/kaori-person-search/graph";
$szOutputFN = sprintf("%s/%s.jpg", $szOutputDir, $szOutputName);
// Display the graph
$graph->Stroke($szOutputFN);
$szImgContent = file_get_contents($szOutputFN);
printf("<IMG SRC='data:image/jpeg;base64,".base64_encode($szImgContent)."' />");
deleteFile($szOutputFN);

exit();

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
			$nQueryID = intval($szQueryID);
			$arOutput[$nQueryID] = $szOutput;
		}
	}

	return $arOutput;
}

?>
