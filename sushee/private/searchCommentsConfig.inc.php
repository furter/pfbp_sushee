<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchCommentsConfig.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/*

DEPRECATED : WAS USED BY FLASH OS

*/
function searchCommentsConfig($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	
	$languageID = $xml->getData($firstNodePath."/@languageID");
	$module = $xml->getData($firstNodePath."/@module");
	$moduleInfo = moduleInfo($module);
	
	if ($moduleInfo->loaded==FALSE){
		$query_result = generateMsgXML(1,"The informations about the module $module couldn't be found.",0,'',$name);//"<MESSAGE name='$name' msgType='1'>The informations about the module couldn't be found.</MESSAGE>";
		return $query_result;
	}
	
	$sql='SELECT * FROM `commentsconfig` WHERE `ModuleID`='.$moduleInfo->ID.' ';
	if($languageID)
		$sql.=' AND `LanguageID`="'.$languageID.'"';
		
	$rs = $db_conn->Execute($sql);
	if (!is_object($rs)){
		sql_log($sql);
		return generateMsgXML(1,"Internal problem: sql request failed.",0,'',$name);
	}
	if ($name)
		$attributes.=" name='$name'";
	$external_file = $xml->getData($current_path.'/@fromFile');
	if($external_file)
		$attributes.=" fromFile='".$external_file."'";
	$query_result='<RESULTS'.$attributes.'>';
	while($row = $rs->FetchRow()){
		$query_result.='<COMMENTSCONFIG ID="'.$row['ID'].'" languageID="'.$row['LanguageID'].'" module="'.$moduleInfo->name.'">';
		$query_result.='<CONFIG>'.$row['Config'].'</CONFIG>';
		$query_result.='</COMMENTSCONFIG>';
	}
	$query_result.="</RESULTS>";
	
	return $query_result;
}
?>
