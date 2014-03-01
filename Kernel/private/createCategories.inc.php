<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createCategories.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/


$current_path = $current_path."/CATEGORIES[1]";

$moduleInfo = moduleInfo($xml->getData($current_path."/@module"));

function handleCategory(&$xml,$categories_path,$fatherID,$moduleInfo,$path){
	$db_conn = db_connect();
	
	$moduleID = $moduleInfo->ID;
	
	$categories_array = $xml->match($categories_path.'/CATEGORY');
	foreach($categories_array as $current_path){
		$UniqueName = $xml->getData($current_path."/UNIQUENAME");
		
		$sql = "INSERT INTO categories(ModuleID,FatherID,Denomination,Path) VALUES($moduleID,'$fatherID',\"".encode_for_DB($UniqueName)."\",\"".encode_for_DB($path).encode_for_DB($UniqueName)."/\");";
		$db_conn->Execute($sql);
		$ID = $db_conn->Insert_Id();
		
		// category created --> now putting the traductions
		$labels_array = $xml->match($current_path."/LABEL[@languageID!='']");
		$sql="INSERT INTO categorytraductions(CategoryID,LanguageID,`Text`) VALUES";
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
		handleCategory($xml,$current_path,$ID,$moduleInfo,$path.$UniqueName."/");
	}
}

handleCategory($xml,$current_path,0,$moduleInfo,"/".$moduleInfo->name."/");
return generateMsgXML(0,'Creation of the categories successful.',0,'',$name);
?>
