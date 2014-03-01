<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createDeptype.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');

class createDepType extends NQLOperation
{
	var $moduleOriginInfo;
	var $moduleTargetInfo;
	var $label;
	
	function parse(){
		$moduleOrigin = $this->firstNode->valueOf("/DEPENDENCYTYPE[@type='start']/@from");
		$moduleTarget = $this->firstNode->valueOf("/DEPENDENCYTYPE[@type='start']/@to");
		$this->moduleOriginInfo = moduleInfo($moduleOrigin);
		$this->moduleTargetInfo = moduleInfo($moduleTarget);
		if (!$this->moduleOriginInfo->loaded || !$this->moduleTargetInfo->loaded){
			$this->setError("The start module or the target module doesn't exist.");
			return false;
		}
		$this->label = $this->firstNode->valueOf("/DEPENDENCYTYPE[@type='start']/TYPE[1]");
		if(!$this->label)
			$this->label = $this->firstNode->valueOf("/DEPENDENCYTYPE[@type='start']/DENOMINATION/LABEL[@languageID='eng']");
		if (!$this->label){
			$this->setError("There must be at least an english label.");
			return false;
		}
		// checking the type does not exist yet
		$db_conn = db_connect();
		$sql = 'SELECT * FROM `dependencytypes` WHERE `Denomination`="'.encodeQuote($this->label).'";';
		$row = $db_conn->getRow($sql);
		if($row){
			$this->setError('Type "'.$this->label.'" already exists');
			return false;
		}
		
		// checking the return link is correct
		$depReturnNode = $this->firstNode->getElement("/DEPENDENCYTYPE[@type='return']");
		if($depReturnNode){
			$label_return = $depReturnNode->valueOf("TYPE[1]");
			if(!$label_return)
				$label_return = $depReturnNode->valueOf("DENOMINATION/LABEL[@languageID='eng']");
			if(!$label_return){
				$this->setError('Return Type has no denomination : please indicate one');
				return false;
			}
			$sql = 'SELECT * FROM `dependencytypes` WHERE `Denomination`="'.encodeQuote($label_return).'";';
			$row = $db_conn->getRow($sql);
			if($row){
				$this->setError('Type "'.$label_return.'" (return type) already exists');
				return false;
			}
		}
		return true;
	}
	
	function operate(){
		$db_conn = db_connect();
		$depStartNode = $this->firstNode->getElement("DEPENDENCYTYPE[@type='start']");
		// taking the preferences of the dependencytype
		$config = $depStartNode->copyOf("CONFIG[1]/*");
		$temporal = $depStartNode->valueOf("TEMPORAL[1]");
		$domain = $depStartNode->valueOf("DOMAIN[1]");
		$description = $depStartNode->valueOf("DESCRIPTION[1]");
		// dependencies can be saved in a separate table, or in the generic table (dependencies)
		$tableName = $depStartNode->valueOf("TABLENAME[1]");
		if(!$tableName){
			$tableName = 'dependencies'; // generic table
		}
		// generating a unique name to identify the type
		$label = ($moduleOriginInfo->name).$this->label;
		// making a pseudo request to know the structure of the database
		$sql = "SELECT * FROM `dependencytypes` WHERE `ID`=-1;";
		$pseudo_rs = $db_conn->Execute($sql);
		// final sql query is :
		$values = array();
		$values['ModuleOriginID']=$this->moduleOriginInfo->ID;
		$values['ModuleTargetID']=$this->moduleTargetInfo->ID;
		$values['Denomination']=$label;
		$values['Config']=$config;
		$values['Temporal']=$temporal;
		$values['Domain']=$domain;
		$values['Description']=$description;
		$values['TableName']=$tableName;
		$sql = $db_conn->GetInsertSQL($pseudo_rs, $values);
		$db_conn->Execute($sql);
		$ID = $db_conn->Insert_Id();
		
		// if tableName is not 'dependencies', creating a separate table
		$table = new DependenciesTable($tableName);
		$table->create();
		
		
		// also introducing the traductions
		$label_array = $depStartNode->getElements("DENOMINATION/LABEL");
		foreach($label_array as $label_node){
			$trad = $label_node->valueOf();
			$languageID = $label_node->valueOf("@languageID");
			$searchLabel = $label_node->valueOf('../SEARCHLABEL[@languageID="'.$languageID.'"]');
			$sql = "INSERT INTO `dependencytraductions` (`DependencyTypeID`,`LanguageID`,`Text`,`SearchLabel`) VALUES($ID,\"$languageID\",\"".encode_for_DB($trad)."\",\"".encode_for_DB($searchLabel)."\");";
			$db_conn->Execute($sql);
		}
		
		// treatment of the return : freelink if not precised or dependency if described in a second DEPENDENCYTYPE node
		$returnTypeID = 0;
		if ($this->firstNode->getElement("/DEPENDENCYTYPE[@type='return']") && $this->firstNode->valueOf("/@type")!="uturn")
		{
			$depReturnNode = $this->firstNode->getElement("/DEPENDENCYTYPE[@type='return']");
			$label_return = $depReturnNode->valueOf("TYPE[1]");
			if(!$label_return)
				$label_return = $depReturnNode->valueOf("DENOMINATION/LABEL[@languageID='eng']");
			$label_return = ($moduleTargetInfo->name).$label_return;
			$config_return = $depReturnNode->copyOf("CONFIG[1]/*");
			$temporal = $depReturnNode->valueOf("TEMPORAL[1]");
			$description = $depReturnNode->valueOf("DESCRIPTION[1]");
			
			$values = array();
			$values['ModuleOriginID']=$this->moduleTargetInfo->ID;
			$values['ModuleTargetID']=$this->moduleOriginInfo->ID;
			$values['Denomination']=$label_return;
			$values['Config']=$config_return;
			$values['Temporal']=$temporal;
			$values['Domain']=$domain;
			$values['Description']=$description;
			$values['TableName']=$tableName;
			$values['ReturnTypeID']=$ID;
			
			$sql = $db_conn->GetInsertSQL($pseudo_rs, $values);
			$db_conn->Execute($sql);
			$returnTypeID = $db_conn->Insert_Id();
			
			// also saving the translations
			if($returnTypeID)
			{
				$label_array = $depReturnNode->getElements("DENOMINATION/LABEL");
				foreach($label_array as $label_node)
				{
					$trad = $label_node->valueOf();
					$languageID = $label_node->valueOf("@languageID");
					$searchLabel = $label_node->valueOf('../SEARCHLABEL[@languageID="'.$languageID.'"]');
					$sql = "INSERT INTO `dependencytraductions` (`DependencyTypeID`,`LanguageID`,`Text`,`SearchLabel`) VALUES($returnTypeID,\"$languageID\",\"".encode_for_DB($trad)."\",\"".encode_for_DB($searchLabel)."\");";
					$db_conn->Execute($sql);
				}
			}
		}
		else if($this->firstNode->valueOf("/@type")=="uturn")
		{
			$returnTypeID = $ID;
		}
		
		// updating the first dependencyType
		if ($returnTypeID != 0 )
		{
			$sql = "UPDATE `dependencytypes` SET `ReturnTypeID`='$returnTypeID' WHERE `ID`=$ID;";
			$db_conn->Execute($sql);
		}
		
		// writing the ID of the deptype in response
		$this->setElementID($ID);

		// force types lists to be resaved in session		
		$set = new DependencyTypeSet($this->moduleOriginInfo->ID);
		$set->clearInSession();
		
		$set = new DependencyTypeSet(false,$this->moduleTargetInfo->ID);
		$set->clearInSession();
		
		$set = new DependencyTypeSet($this->moduleOriginInfo->ID,$this->moduleTargetInfo->ID);
		$set->clearInSession();
		
		$this->setSuccess("Dependency_entity creation successful.");
		return true;
	}
}