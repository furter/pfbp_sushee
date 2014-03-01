<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateCategories.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

/*--------------------------------------------------
Command to update categories in batch (sending them as tree representation)

<UPDATE>
   	<CATEGORIES module="module1">
      <CATEGORY >
         <UNIQUENAME>categ1</UNIQUENAME>
         <ISASSIGNABLE>{0,1}</ISASSIGNABLE>
      </CATEGORY>
   	</CATEGORIES>
	<CATEGORIES module="module2">
      <CATEGORY>
         <UNIQUENAME>categ2</UNIQUENAME>
         <ISASSIGNABLE>{0,1}</ISASSIGNABLE>
         [<CHILDS>]
		<CATEGORY >
            <UNIQUENAME>categ2.1</UNIQUENAME>
            <ISASSIGNABLE>{0,1}</ISASSIGNABLE>
            <LABEL languageID="eng">translation in english</LABEL>
            <LABEL languageID="fre">translation in french</LABEL>
         </CATEGORY>
		[</CHILDS>]
      </CATEGORY>
   </CATEGORIES>
</UPDATE>
*/
class sushee_updateCategories extends NQLOperation{
	
	var $categoriesHandled;
	var $modulesHandled;
	
	function parse(){
		return true;
	}
	
	function operate(){
		
		
		$categoriesNodes = $this->operationNode->getElements('/CATEGORIES');
		$this->categoriesHandled = array();
		$this->modulesHandled = array();
		$db_conn = db_connect();
		
		//---------------------
		// HANDLING ALL CATEGORIES NODES
		//---------------------
		foreach($categoriesNodes as $node){
			
			$module = $node->getData("/@module");
			$moduleInfo = moduleInfo($module);
			if($moduleInfo->loaded){
				$start_name = $moduleInfo->getName();
				$this->modulesHandled[] = $moduleInfo->getID();
			}else{
				$start_name = $module;
			}	
			$this->handleCategory($node,0,$moduleInfo,"/".$start_name."/");
			
		}
		//---------------------
		// DELETING CATEGORIES NOT IN THE REQUEST
		//---------------------
		// taking all categories not to keep (not in categoriesHandled) for the moduleHandled and deleting their datas (categories, lins, traductions)
		$sql = 'SELECT `ID` FROM `categories` WHERE `ID` NOT IN ('.implode(',',$this->categoriesHandled).') AND `ModuleID` IN ('.implode(',',$this->modulesHandled).')';
		sql_log($sql);
		
		$rs = $db_conn->execute($sql);
		while($row = $rs->fetchRow()){
			
			$delete_sql1 = 'DELETE FROM `categories` WHERE `ID` = \''.$row['ID'].'\'';
			$delete_sql2 = 'DELETE FROM `categorylinks` WHERE `CategoryID` = \''.$row['ID'].'\'';
			$delete_sql3 = 'DELETE FROM `categorytraductions` WHERE `CategoryID` = \''.$row['ID'].'\'';
			
			$db_conn->Execute($delete_sql1);
			$db_conn->Execute($delete_sql2);
			$db_conn->Execute($delete_sql3);
		}
		
		$this->setSuccess('Creation/Update of the categories successful.');
		
		return true;
	}
	
	function handleCategory($node,$fatherID,$moduleInfo,$path){
		$db_conn = db_connect();
		if($moduleInfo->loaded)
			$moduleID = $moduleInfo->getID();
		else
			$moduleID = 0;
			
		$categories_array = $node->getElements('/CATEGORY');
		foreach($categories_array as $categoryNode){
			
			$UniqueName = $categoryNode->valueOf("/UNIQUENAME");
			$ID = $categoryNode->valueOf("/@ID");
			$IsAssignable = $categoryNode->valueOf("/ISASSIGNABLE");
			
			if(!$ID){
				//---------------------
				// NEW CATEGORY
				//---------------------
				$particle = '';
				$success = false;
				while(!$success){
					$sql = "INSERT INTO `categories`(`ModuleID`,`FatherID`,`Denomination`,`Path`,`IsAssignable`) VALUES($moduleID,'$fatherID',\"".encode_for_DB($UniqueName.$particle)."\",\"".encode_for_DB($path).encode_for_DB($UniqueName.$particle)."/\",'".encode_for_DB($IsAssignable)."');";
					$success = $db_conn->Execute($sql);
					$finalUniqueName = $UniqueName.$particle;
					$particle++;
				}
				$ID = $db_conn->Insert_Id();
				sql_log($sql);
				
			}else{
				//---------------------
				// EXISTING CATEGORY
				//---------------------
				$finalUniqueName = $UniqueName;
				$sql = "UPDATE `categories` SET `IsAssignable`='".encode_for_DB($IsAssignable)."', `ModuleID`=$moduleID,`FatherID`='$fatherID',`Denomination`=\"".encode_for_DB($UniqueName)."\",`Path`=\"".encode_for_DB($path).encode_for_DB($UniqueName)."/\" WHERE `ID`='$ID';";
				$db_conn->Execute($sql);
				sql_log($sql);
				
			}
			$this->categoriesHandled[] = $ID;
			
			
			//---------------------
			// CATEGORY CREATED --> NOW SAVING THE TRANSLATIONS
			//---------------------
			$labels_array = $categoryNode->getElements("/LABEL[@languageID!='']");
			$sql="INSERT INTO `categorytraductions`(`CategoryID`,`LanguageID`,`Text`) VALUES";
			$first = true;
			$languages = array();
			
			foreach($labels_array as $labelNode){
				$languageID = $labelNode->getData('/@languageID');
				$languages[] = $languageID;
				$text = $labelNode->getData();
				if ($first != true) $sql.=",";
				else $first = false;
				$sql.="($ID,\"$languageID\",\"".encode_for_DB($text)."\")";
			}
			
			// if there is an ID, we delete the previous categorytraductions
			$clean_sql = 'DELETE FROM `categorytraductions` WHERE `CategoryID`=\''.$ID.'\' AND `LanguageID` IN ("'.implode('","',$languages).'");';
			$db_conn->Execute($clean_sql);
			if (sizeof($labels_array)>0){
				$db_conn->Execute($sql);
			}
			
			//---------------------
			// HANDLING SUBCATEGORIES
			//---------------------
			if($categoryNode->getElement('/CHILDS[1]')){
				$this->handleCategory($categoryNode->getElement('/CHILDS[1]'),$ID,$moduleInfo,$path.$finalUniqueName."/");
			}else{
				$this->handleCategory($categoryNode,$ID,$moduleInfo,$path.$finalUniqueName."/");
			}
		}
	}
}
