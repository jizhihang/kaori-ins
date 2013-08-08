<?php

require_once "ksc-AppConfig.php";

$nAction = 0;

if(isset($_REQUEST['vAction']))
{
	$nAction = $_REQUEST['vAction'];
}

if($nAction == 0)
{
	printf("<H3>Submit your job on SGE\n");
	printf("<FORM METHOD='POST'>\n");
	printf("<P>Job File:<BR>");
	printf("<INPUT TYPE='TEXT' NAME='vJobFile' VALUE='Paste your full path file SGE job here' SIZE='200'> \n");

	printf("<P>Owner:<BR>");
	printf("<SELECT NAME='vOwner'>\n");
	printf("<OPTION VALUE='plsang'>plsang</OPTION>\n");
	printf("<OPTION VALUE='ngothanh'>ngothanh</OPTION>\n");
	printf("<OPTION VALUE='minhduc'>minhduc</OPTION>\n");
	printf("<OPTION VALUE='lqvu'>lqvu</OPTION>\n");
	printf("</SELECT>\n");
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1'>\n");
	printf("<P><INPUT TYPE='Submit' NAME='vSubmit' VALUE='Submit'>\n");
	printf("</FORM>\n");
	exit();
}

$szFPJobFN = $_REQUEST['vJobFile'];

$szGridPoolDir = "/net/per610a/export/das11f/ledduy/tmp/gridpool";
$szOwner = $_REQUEST['vOwner'];
$szFPSignalFN = sprintf("%s/%s.signal", $szGridPoolDir, $szOwner);

while(1)
{
	if(file_exists($szFPSignalFN))
	{
		continue;
	}
	$arOutput = array();
	$arOutput[] = sprintf("%s", $szFPJobFN);
	saveDataFromMem2File($arOutput, $szFPSignalFN);
	printf("Your job has been submitted! Check <A HREF='http://ns.hpc.vpl.nii.ac.jp/admin/sge.php?main=summary'>here</A>\n");
	break;
}

?>