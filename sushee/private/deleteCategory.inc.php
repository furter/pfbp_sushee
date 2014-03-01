<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/deleteCategory.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function deleteCategory($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	
	$current_path = $current_path."/CATEGORY[1]";
	$ID = $xml->getData($current_path."/@ID");
	if (!$ID)
		return generateMsgXML(1,'You should set a valid ID.',0,'',$name);
	
	$child_action = $xml->getData($current_path."/@child_action");
	if (!$child_action || $child_action!="delete")
		$child_action = "adoption";
	
	// get the fatherID of this category
	$row = $db_conn->GetRow("SELECT * FROM categories WHERE ID=$ID;");
	if (!$row)
		return generateMsgXML(1,'You should set a valid ID : this one doesn\' exist.',0,'',$name);
	$fatherID=$row["FatherID"];
		
	if ($child_action=="adoption"){
		/*// the father of this category adopt his children if it's not a root
		if ($fatherID==0){
			$sql = "SELECT ID FROM categories WHERE FatherID=$ID;";
			$rs = $db_conn->Execute($sql);
			if ($rs->RecordCount()>1)
				return generateMsgXML(1,'Adoption is not possible because the category is a root and has more than one child.',0,'',$name);
		}*/
		$sql = "UPDATE categories SET FatherID=$fatherID WHERE FatherID=$ID;";
		$db_conn->Execute($sql);
		$sql = "DELETE FROM categories WHERE ID=$ID;";
		$db_conn->Execute($sql);
		return generateMsgXML(0,'The category was deleted and its children adopted by its father.',0,'',$name);
	}else{
		// recursive delete
		//$current_deleted_ID=$ID;
		$to_delete = array($ID);
		while(sizeof($to_delete)>0){
			// queueing all the subcategories of the category
			$sql = "SELECT ID FROM categories WHERE FatherID=".$to_delete[0].";";
			$rs = $db_conn->Execute($sql);
			while($row = $rs->FetchRow()){
				$to_delete[]=$row['ID'];
			}
			// deleting the category and its translations
			$sql = "DELETE FROM categorytraductions WHERE CategoryID=".$to_delete[0].";";
			$db_conn->Execute($sql);
			$sql = "DELETE FROM categories WHERE ID=".$to_delete[0].";";
			$db_conn->Execute($sql);
			// finished with this one
			array_shift($to_delete);
		}
		$sql = "DELETE FROM categories WHERE ID=$ID;";
		$db_conn->Execute($sql);
		return generateMsgXML(0,'The category and all its children were deleted.',0,'',$name);
	}
}
?>
