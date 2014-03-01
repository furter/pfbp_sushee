<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateCategory.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class CategoryUpdateData extends SusheeObject{
	var $path;
	var $ID;
	
	function CategoryUpdateData($ID,$path){
		$this->ID = $ID;
		$this->path = $path;
	}
}

class updateCategory extends NQLOperation{

	var $row;

	function parse(){
		$db_conn = db_connect();

		$ID = $this->firstNode->getData("@ID");
		if (!$ID){
			$this->setError('You should set a valid ID.');
			return false;
		}
		$row = $db_conn->GetRow("SELECT * FROM `categories` WHERE `ID`='$ID';");
		
		if (!$row){
			$this->setError('You should set a valid ID : this one doesn\' exist.');
			return false;
		}
		$this->row = $row;
		return true;
	}
	
	function operate(){
		$row = $this->row;
		$ID = $row['ID'];
		$current_path = $this->firstNode->getPath();
		$db_conn = db_connect();
		
		
		// looking what is to update
		$fatherID = $this->firstNode->getData("@fatherID");
		if ($fatherID===false){
			$fatherID = $row["FatherID"];
		}
		$isAssignable = $this->firstNode->getData("ISASSIGNABLE");
		if($isAssignable===false){
			$isAssignable = $row["IsAssignable"];
		}

		$module = $this->firstNode->getData("@module");
		if($module===false){
			$new_moduleID = $row["ModuleID"];
		}else{
			$moduleInfo = moduleInfo($module);
			$new_moduleID = $moduleInfo->getID();
		}

		// updating the category if something is different than before in the datas
		if ($fatherID != $row["FatherID"] || $isAssignable!= $row["IsAssignable"] || $new_moduleID!=$row["ModuleID"]){
			// checking the fatherID is not in the descendance
			if($fatherID!=0){
				$to_check = array($ID);
				while(sizeof($to_check)>0){
					if ($to_check[0]==$fatherID){
						$this->setError('You cannot move the category there because it\' in its descendance.');
						return false;
					}
					// queueing all the subcategories of the category
					$sql = "SELECT `ID` FROM `categories` WHERE `FatherID`=".$to_check[0].";";
					$rs = $db_conn->Execute($sql);
					while($fatherrow = $rs->FetchRow()){
						$to_check[]=$fatherrow['ID'];
					}
					// finished with this one
					array_shift($to_check);
				}
				// it's ok we can change its fatherID
				$sql = "SELECT `ModuleID`,`Path` from `categories` WHERE `ID`=$fatherID;";
				$fatherrow = $db_conn->GetRow($sql);
				if (!$fatherrow){
					$this->setError('The new father you wan\'t to give to your category doesn\'t exist.');
					return false;
				}
				$new_path = $fatherrow['Path'].$row['Denomination']."/";
				$new_moduleID = $fatherrow["ModuleID"];
			}else{
				// no father ID, root category in the module
				if($new_moduleID){
					// first part
					$moduleInfo = moduleInfo($new_moduleID);
					$new_path = '/'.$moduleInfo->getName().'/'.$row['Denomination'].'/';
				}else{
					// generic category : no module
					$new_path = '/generic/'.$row['Denomination'].'/';
				}

			}
			$sql = "UPDATE `categories` SET `FatherID`='$fatherID',`ModuleID`='".$new_moduleID."',`IsAssignable`='".$isAssignable."',`Path`=\"".$new_path."\"  WHERE `ID`='$ID' LIMIT 1;";
			$db_conn->Execute($sql);

			// updating the descendants, for the path, the moduleID
			$to_update = array(new CategoryUpdateData($ID,$new_path));
			while(sizeof($to_update)>0){
				// taking next element for which children should be updated, the first one is the category we just updated
				$categ = array_shift($to_update);
				$ID = $categ->ID;
				$parent_path = $categ->path;

				// taking the children
				$sql = 'SELECT `ID`,`Denomination` FROM `categories` WHERE `FatherID`=\''.$ID.'\'';
				$rs = $db_conn->execute($sql);
				while($descendant_row = $rs->FetchRow()){
					$new_path = $parent_path.$descendant_row['Denomination'].'/';
					// putting the children in the array of the elements to update
					$to_update[] = new CategoryUpdateData($descendant_row['ID'],$new_path);
					// updating the path of the element
					$sql = 'UPDATE `categories` SET `Path`="'.$new_path.'",`ModuleID`=\''.$new_moduleID.'\' WHERE `ID` = \''.$descendant_row['ID'].'\' LIMIT 1';
					$db_conn->execute($sql);
				}
			}

		}
		// updating labels
		$labels_array = $this->firstNode->getElements("/LABEL[@languageID!='']");
		$sql="INSERT INTO `categorytraductions`(`CategoryID`,`LanguageID`,`Text`) VALUES";
		$first = true;
		$languages = array();
		foreach($labels_array as $node){
			$languageID=$node->getData('@languageID');
			$languages[] = $languageID;
			$text = $node->getData();
			if ($first != true) $sql.=",";
			else $first = false;
			$sql.="($ID,\"$languageID\",\"".encode_for_DB($text)."\")";
		}
		
		if (sizeof($labels_array)>0){
			$clean_sql = 'DELETE FROM `categorytraductions` WHERE `CategoryID`=\''.$ID.'\' AND `LanguageID` IN ("'.implode('","',$languages).'");';
			$db_conn->Execute($clean_sql);
			$db_conn->Execute($sql);
		}

		$this->setSuccess('Update of the category successful.');
		
		return true;
	}
	
}

?>
