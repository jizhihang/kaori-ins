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

$szRunID = "run_fusion2013-dpm-dense6-1x1-10+10";
$szResultDir = sprintf("%s/result/%s/tv2013/test2013-new", $gszRootBenchmarkDir, $szRunID);

$szOutputDir = $szResultDir;

$arSysID = array(
"NII-UIT.BOW-DPM" => "BOW: Dense6-SIFT, 1K codebook, soft assignment-4NN, L1Norm-L1Dist. DPM: voc-release5, default setting- 2comps-8parts. Fusion using norm scores with weights 1BOW-10DPM",
);

$arDirLUT = array(
"NII-UIT.BOW-DPM" => "RunCaizhi-Inter-SIM",
);


$nPriority = 4;
foreach($arSysID as $szSysID => $szSysDesc)
{
	$szInputDir = $szResultDir;
	
	$arFinalNISTOutput = array();
	$arFinalNISTOutput[] = sprintf("<!DOCTYPE videoSearchResults SYSTEM \"http://www-nlpir.nist.gov/projects/tv2013/dtds/videoSearchResults.dtd\">");
	$arFinalNISTOutput[] = sprintf("<videoSearchResults>");
	
	$arFinalNISTOutput[] = sprintf("<videoSearchRunResult pType=\"F\"  sysId=\"%s\" priority=\"%d\" condition=\"NO\"
	desc=\"%s\" >", $szSysID, $nPriority, $szSysDesc);

	// for each query
	for($nQueryID=9069; $nQueryID<=9098; $nQueryID++)
	{
		
		$fElapsedTime = 200*150+50*60+60;  // TrainDPM 60mins/query. BOW: 50*60mins. DPM: 200*150mins

		// <videoSearchTopicResult tNum="9048" elapsedTime="15.0" searcherId="X">
		$arFinalNISTOutput[] = sprintf("<videoSearchTopicResult tNum=\"%d\" elapsedTime=\"%0.0f\" searcherId=\"kaori-ins\">",
		    $nQueryID, $fElapsedTime);
		$nRank = 1;
		
		$szFPInputFN = sprintf("%s/%s.rank", $szInputDir, $nQueryID);
		loadListFile($arRankList, $szFPInputFN);
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

	$nPriority++;
}

/*

<!-- Example video search results for a automatic instance search run  -->
<!DOCTYPE videoSearchResults SYSTEM ""http://www-nlpir.nist.gov/projects/tv2013/dtds/videoSearchResults.dtd">   

<videoSearchResults>

<videoSearchRunResult pType="F"  sysId="SiriusCy6" priority="2" condition="NO" 
desc="This automatic run uses algorithm 1" >

<videoSearchTopicResult tNum="9048" elapsedTime="15.0" searcherId="X">
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

<videoSearchTopicResult tNum="9068" elapsedTime="5.9" searcherId="X">
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

</videoSearchResults>
*
*/
?>