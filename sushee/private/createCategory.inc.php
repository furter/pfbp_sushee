<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createCategory.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function createCategory($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	
	$current_path = $current_path."/CATEGORY[1]";
	
	// handling the properties of the category
	$UniqueName = $xml->getData($current_path."/UNIQUENAME");
	if (!$UniqueName)
		return generateMsgXML(1,'You should set a valid uniquename.',0,'',$name);
	$module = $xml->getData($current_path."/@module");
	if ($module){
		$moduleInfo = moduleInfo($module);
		if ($moduleInfo->loaded)
		$moduleID = $moduleInfo->ID;
		else
		$moduleID = 0;
	}else
		$moduleID = 0;
	$fatherID = $xml->getData($current_path."/@fatherID");
	if(!$fatherID){
		if ($xml->getData($current_path."/FATHERNAME")){
			$sql = 'SELECT `ID` FROM `categories` WHERE `Activity`=1 AND `Denomination`="'.$xml->getData($current_path."/FATHERNAME").'";';
			$row = $db_conn->GetRow($sql);
			if($row)
				$fatherID = $row['ID'];
			else
				$fatherID = 0;
		}else
			$fatherID = 0;
	}
	
	if($fatherID){
		$sql = 'SELECT `Path` FROM `categories` WHERE `Activity`=1 AND `ID`=\''.$fatherID.'\' AND `ModuleID`=\''.$moduleID.'\';';
		$row = $db_conn->GetRow($sql);
		$path = $row['Path'];
		$path.=$UniqueName.'/';
	}else{
		if($moduleID)
			$path = '/'.$moduleInfo->name;
		else
			$path = '/generic';
		$path.='/';
		$path.=$UniqueName.'/';
	}
	$isAssignable = $xml->getData($current_path."/ISASSIGNABLE");
	if(!$isAssignable){
		$isAssignable = 1;
	}
	$sql = "INSERT INTO `categories`(`ModuleID`,`FatherID`,`Denomination`,`Path`,`IsAssignable`) VALUES('$moduleID','$fatherID',\"".encode_for_DB($UniqueName)."\",\"".$path."\",".$isAssignable.");";
	$db_conn->Execute($sql);
	sql_log($sql);
	$ID = $db_conn->Insert_Id();
	if(!$ID){
		$query_result = generateMsgXML(1,'Creation of the category failed :'.encode_to_xml($sql).'.',0,$ID,'',$name);
		return $query_result;
	}
	// category created --> now putting the traductions
	$labels_array = $xml->match($current_path."/LABEL[@languageID!='']");
	$sql="INSERT INTO `categorytraductions`(`CategoryID`,`LanguageID`,`Text`) VALUES";
	$first = true;
	foreach($labels_array as $label_path){
		$languageID=$xml->getData($label_path.'/@languageID');
		$text = $xml->getData($label_path);
		if ($first != true) $sql.=",";
		else $first = false;
		$sql.="($ID,\"$languageID\",\"".encode_for_DB($text)."\")";
	}
	if (sizeof($labels_array)>0){
		$db_conn->Execute($sql);
	}
	
	$query_result = generateMsgXML(0,'Creation of the category successful.',0,$ID,'',$name);
	return $query_result;
}
?>
