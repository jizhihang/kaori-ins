<?php

require_once "ksc-AppConfig.php";

$szGridPoolDir = "/net/per610a/export/das11f/ledduy/tmp/gridpool";

$arOwnerList = array("plsang", "ngothanh", "minhduc", "lqvu");
while(1)
{
	foreach($arOwnerList as $szOwner)
	{
		$szFPSignalFN = sprintf("%s/%s.signal", $szGridPoolDir, $szOwner);
		if(file_exists($szFPSignalFN))
		{
			loadListFile($arList, $szFPSignalFN);
			foreach($arList as $szCmdLine)
			{
				printf("###Start submitting job for [%s] [%s]...\n", $szOwner, $szCmdLine);
				system($szCmdLine);
				printf("###[%s] - Finish submitting job for [%s] [%s]...\n", date("Y-m-d H:i:s"), $szOwner, $szCmdLine);
			}
			deleteFile($szFPSignalFN);
		}
		else
		{
			continue;
		}
	}
}
?>