<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateDescConfig.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function updateDescConfig($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	
	$sql = "SELECT * FROM `descriptionsconfig` WHERE ID=-1;";
	$pseudo_rs = $db_conn->Execute($sql);
	// cleaning descriptionsconfig for this module
	$moduleInfo = moduleInfo($xml->getData($current_path."/DESCRIPTIONCONFIG[1]/@module"));
	$clean_sql = "DELETE FROM `descriptionsconfig` WHERE `ModuleID`=".$moduleInfo->ID.";";
	$db_conn->Execute($clean_sql);
	
	$desc_config_array = $xml->match($current_path."/DESCRIPTIONCONFIG");
	foreach($desc_config_array as $path){
		/*$descID = $xml->getData($path."/@ID");*/
		$languageID = $xml->getData($path."/@languageID");
		//$moduleInfo = moduleInfo($xml->getData($path."/@module"));
		$DescConfig = $xml->toString($path."/CONFIG/*");
		$Alingual = $xml->getData($path."/@alingual");
		if($Alingual!=1)
			$Alingual = 0;
		$desc_row = array("LanguageID"=>$languageID,"ModuleID"=>$moduleInfo->ID,"Config"=>$DescConfig,"Alingual"=>$Alingual );
		/*if($descID){
			$recup_sql = "SELECT * FROM `descriptionsconfig` WHERE `ID`='$descID';";
			$former_rs = $db_conn->Execute($recup_sql);
			$sql = $db_conn->GetUpdateSQL($former_rs, $desc_row);
		}else{*/
		$sql = $db_conn->GetInsertSQL($pseudo_rs, $desc_row);
		//}
		$db_conn->Execute($sql);
		//debug_log($sql);
	}
	$query_result = generateMsgXML(0,'DescriptionConfigs successfully modified.',0,'',$name);
	return $query_result;
}
?>
