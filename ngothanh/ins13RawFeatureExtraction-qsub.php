<?php
if($argc != 6)
{
	printf("Usage: %s <trecvid year> <MinVal> <MaxVal> <Step> <shFile>\n", $argv[0]);
	printf("Example: %s tv2011 1 2100 2 ./runme_RawFeatureExtraction.sh\n", $argv[0]);
	exit();
}

$szScript = "/net/per610a/export/das11f/ledduy/ndthanh/InstanceSearch2013/code/matlab/ins13RawFeatureExtraction.sgejob.sh";
$year = $argv[1];
$nMinVal = $argv[2];
$nMaxVal = $argv[3];
$nStep = $argv[4];
$szShFile = $argv[5];

$fsh = fopen($szShFile,"w");
for ($i=$nMinVal; $i<=$nMaxVal; $i+=$nStep)
{
	$x1 = $i;
	$x2 = $i + $nStep - 1;
	$szParam = sprintf("%s %s %s", $year, $x1, $x2);
	$szCmdLine = sprintf("qsub -e /dev/null -o /dev/null %s %s",$szScript,$szParam);
	fprintf($fsh,"%s\n",$szCmdLine);
}
fclose($fsh);
?>