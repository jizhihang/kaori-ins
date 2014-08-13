<?php
/**
 * 		@file 	ksc-Tool-Convert2NISTSubmissionFormat.php
 * 		@brief 	Convert rank list to NIST submission (xml).
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 22 Aug 2013.
 */


require_once "ksc-AppConfig.php";

/////////////////////////////

$arRunList = array();

$arRunList["NII-UIT.Sakura.Saigon"]['ResultDir'] = "R0_tv2013.surrey.hard.soft+DPM+RANSAC";  // dir name in result/ 
$arRunList["NII-UIT.Sakura.Saigon"]['Desc'] = "Fusion of BOW+DPM+RANSAC: BoW (HesAff - 1M codebook, hard-DB.soft-QUERY assignment, soft assignment-3NN), DPM (voc-release5, default setting- 2comps-8parts), FACE (Viola-Jones Face Detector, VGG-Face Feature, L1-dist).";
$arRunList["NII-UIT.Sakura.Saigon"]['Priority'] = 2; 

$szAllSubmissionDir = sprintf("%s/result/tv2014/test2014/NISTSubmission", $gszRootBenchmarkDir);

foreach($arRunList as $szSysID =>$arInfo)
{
    $szRunID = $arInfo['ResultDir'];
    $szResultDir = sprintf("%s/result/tv2014/test2014/%s", $gszRootBenchmarkDir, $szRunID);
    
    $szOutputDir = $szResultDir;
    $szInputDir = $szResultDir;
	
	$arFinalNISTOutput = array();
	
	$arFinalNISTOutput[] = sprintf("<!DOCTYPE videoSearchResults SYSTEM \"http://www-nlpir.nist.gov/projects/tv2014/dtds/videoSearchResults.dtd\">");
	$arFinalNISTOutput[] = sprintf("<videoSearchResults>");
	
	$nPriority = $arInfo['Priority'];
	$szSysDesc  = $arInfo['Desc'];
	
	//<videoSearchRunResult pType="F"  pid="SiriusCyberCo" priority="2" condition="NO" exampleSet="C"
	//desc="This automatic run uses algorithm 1" >
	
	$arFinalNISTOutput[] = sprintf("<videoSearchRunResult pType=\"F\"  pid=\"%s\" priority=\"%d\" condition=\"NO\" exampleSet=\"D\" 
	desc=\"%s\" >", $szSysID, $nPriority, $szSysDesc);

	// for each query
	for($nQueryID=9099; $nQueryID<=9128; $nQueryID++)
	{
		
		$fElapsedTime = 5 + 4*60 + 40*60 + 8*50000;  // TrainDPM 40mins/query. MatchDPM: 8sec*50K. RANSAC: 4mins/query, BOW: 5sec. 

		// <videoSearchTopicResult tNum="9048" elapsedTime="15.0" searcherId="X">
		$arFinalNISTOutput[] = sprintf("<videoSearchTopicResult tNum=\"%d\" elapsedTime=\"%0.0f\" searcherId=\"KAORI-INS\">",
		    $nQueryID, $fElapsedTime);
		$nRank = 1;
		
		$szFPInputFN = sprintf("%s/%s.rank", $szInputDir, $nQueryID);
		$nNumRowszz = loadListFile($arRankList, $szFPInputFN);
		
		if($nNumRowszz != 10000)
		{
		    printf("Serious error numRows [%d] != 10K \n", $nNumRowszz);
		}
		foreach($arRankList as $szLine)
		{
			$arTmp = explode("#$#", $szLine);
			$szShotID = trim($arTmp[0]);
			$fScore = floatval($arTmp[1]);

			// <item seqNum="1000" shotId="shot432_24" />
			
			$arFinalNISTOutput[] = sprintf("<item seqNum=\"%d\" shotId=\"%s\" />", $nRank, $szShotID);
			$nRank++;
			if($nRank>1000)
			{
				break;
			}
		}
		$arFinalNISTOutput[] = sprintf("</videoSearchTopicResult>");
	}

	$arFinalNISTOutput[] = sprintf("</videoSearchRunResult>");
	$arFinalNISTOutput[] = sprintf("</videoSearchResults>");

	$szFPOutputFN = sprintf("%s/%s.R%s.xml", $szOutputDir, $szSysID, $nPriority);

	saveDataFromMem2File($arFinalNISTOutput, $szFPOutputFN, "wt");
	
	// make a copy to AllSubmissionDir
	$szFPOutputFN = sprintf("%s/%s.R%s.xml", $szAllSubmissionDir, $szSysID, $nPriority);
	saveDataFromMem2File($arFinalNISTOutput, $szFPOutputFN, "wt");
	
}

/*

<!-- Example video search results for a automatic instance search run  -->
<!DOCTYPE videoSearchResults SYSTEM "http://www-nlpir.nist.gov/projects/tv2014/dtds/videoSearchResults.dtd">   

<videoSearchResults>

<videoSearchRunResult pType="F"  pid="SiriusCyberCo" priority="2" condition="NO" exampleSet="C"
desc="This automatic run uses algorithm 1" >

<videoSearchTopicResult tNum="9048" elapsedTime="90.0" searcherId="X">
<item seqNum="1" shotId="shot4324_2" />
<item seqNum="2" shotId="shot484_4" />
<item seqNum="3" shotId="shot459_43" /> 
<item seqNum="4" shotId="shot1663_34" /> 
<item seqNum="5" shotId="shot2415_16" /> 
<item seqNum="6" shotId="shot7_765" /> 
<item seqNum="7" shotId="shot35_4" /> 
<item seqNum="8" shotId="shot3246_54" /> 
<item seqNum="9" shotId="shot22_255" /> 
<!-- ... -->
<item seqNum="1000" shotId="shot432_24" />
</videoSearchTopicResult>

<!-- ... -->

<videoSearchTopicResult tNum="9068" elapsedTime="354.9" searcherId="X">
<item seqNum="1" shotId="shot459_5" />
<item seqNum="2" shotId="shot1957_794" />
<item seqNum="3" shotId="shot7_54" /> 
<item seqNum="4" shotId="shot35_679" /> 
<item seqNum="5" shotId="shot1_712" /> 
<item seqNum="6" shotId="shot3246_461" /> 
<item seqNum="7" shotId="shot22_15" /> 
<item seqNum="8" shotId="shot1663_84" /> 
<item seqNum="9" shotId="shot484_67" /> 
<!-- ... -->
<item seqNum="1000" shotId="shot666_43" />
</videoSearchTopicResult>


</videoSearchRunResult>

</videoSearchResults>*/
?>