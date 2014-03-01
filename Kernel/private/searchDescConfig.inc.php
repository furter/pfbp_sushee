<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchDescConfig.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nectil_user.class.php');

class searchDescriptionConfig extends RetrieveOperation{
	var $moduleInfo = false;
	var $languageID = false;
	
	
	function parse(){
		$languageID = $this->firstNode->valueOf("@languageID");
		if($languageID){
			$this->languageID = $languageID;
		}else{
			$request = new Sushee_Request();
			if($request->isProjectRequest()){
				$this->languageID = $request->getLanguage();
			}
		}
		
		$module = $this->firstNode->getData("@module");
		if($module){
			$moduleInfo = moduleInfo($module);
			if(!$moduleInfo->loaded){
				$this->setError("The informations about the module $module couldn't be found.");
				return false;
			}else{
				$this->moduleInfo = $moduleInfo;
			}
		}else{
			$this->setError("No module indicated.");
			return false;
		}
		
		
		return true;
	}
	
	function operate(){
		$moduleInfo = $this->moduleInfo;
		$languageID = $this->languageID;
		$db_conn = db_connect();
		
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		
		if($languageID)
			$sql = 'SELECT * FROM `descriptionsconfig` WHERE `LanguageID`="'.$languageID.'" AND `ModuleID`='.$moduleInfo->ID.';';
		else
			$sql = 'SELECT * FROM `descriptionsconfig` WHERE `ModuleID`='.$moduleInfo->ID.';';

		$rs = $db_conn->Execute($sql);

		if ($rs){
			$first = true;
			while($row = $rs->FetchRow()){
				$first = false;
				$xml.=$this->getItemXML($row);
			}
			if($first){ // taking the row with the default language
				$lg_sql = 'SELECT `languageID` FROM `medialanguages` ORDER BY `priority` LIMIT 0,1';
				$lg_row = $db_conn->getRow($lg_sql);
				$languageID = $lg_row['languageID'];
				$sql = 'SELECT * FROM `descriptionsconfig` WHERE `LanguageID`="'.$languageID.'" AND `ModuleID`='.$moduleInfo->ID.';';
				$rs = $db_conn->Execute($sql);
				while($row = $rs->FetchRow()){
					$first = false;
					$xml.=$this->getItemXML($row);
				}
			}
		}else{
			sql_log($sql);
			$this->setError("Internal problem: sql request failed.");
			return false;
		}
		
		$xml.='</RESULTS>';
		$this->setXML($xml);
		return true;
	}
	
	function getItemXML($row){
		$moduleInfo = $this->moduleInfo;
		$query_result='<DESCRIPTIONCONFIG ID="'.$row['ID'].'" module="'.$moduleInfo->name.'" languageID="'.$row['LanguageID'].'"'.(($row['Alingual']==1)?' alingual="1"':'').'>';
		$query_result.='<CONFIG>'.$row["Config"].'</CONFIG>';
		$query_result.='</DESCRIPTIONCONFIG>';
		return $query_result;
	}
}

?>