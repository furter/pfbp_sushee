<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/dependency.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/db_manip.class.php");
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../common/susheesession.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");

define('SUSHEE_DEP_REVERSE_MODE','reverse');
define('SUSHEE_DEP_NORMAL_MODE','normal');

class dependencyType extends SusheeObject{
    var $ID;
    var $name;
    var $ModuleOriginID;
    var $ModuleTargetID;
    //var $Config;
    var $ReturnTypeID;
    var $loaded;
    var $_lastError;
	var $isTemporal;
	var $tableName;
	var $mode = SUSHEE_DEP_NORMAL_MODE;
    
    /*function getLastError(){
        return $_latsError;
    }*/
    function returnIsTypedLink(){
        return $this->ReturnTypeID == "0";
    }
    function returnIsDependency(){
        return $this->ReturnTypeID != "0";
    }
	function getReturnType(){
		// using global depType function that saves the depType in session for faster handling
		return depType($this->ReturnTypeID);
	}
	
	function getID(){
		return $this->ID;
	}
	
	function isUTurn(){
		return $this->ReturnTypeID == $this->ID;
	}
	
	function exists(){
		return $this->loaded;
	}
	function getModuleTarget(){
		if($this->isReverseMode()){
			return moduleInfo($this->ModuleOriginID);
		}else{
			return moduleInfo($this->ModuleTargetID);
		}
	}
	
	function getModuleTargetID(){
		if($this->isReverseMode()){
			return $this->ModuleOriginID;
		}else{
			return $this->ModuleTargetID;
		}
	}
	
	function getModuleOrigin(){
		if($this->isReverseMode()){
			return moduleInfo($this->ModuleTargetID);
		}else{
			return moduleInfo($this->ModuleOriginID);
		}
	}
	
	function getModuleOriginID(){
		if($this->isReverseMode()){
			return $this->ModuleTargetID;
		}else{
			return $this->ModuleOriginID;
		}
	}
	
	function getName(){
		return $this->name;
	}
	function getDomain(){
		return $this->domain;
	}
	function isTemporal(){
		return $this->isTemporal;
	}
	// we can change the tablename to use another table than dependencies to have faster searches
	function getTableName(){
		return $this->tableName;
	}
	
	function getTable(){
		return new DependenciesTable($this->getTableName());
	}
	
	function setTableName($tableName){
		session_start(); // to force the update in session
		$this->tableName = $tableName;
	}
	
	// dependencies that are the returns of others are not saved, but deduced from the dependency in the other direction, so the ID in table is the one of the other dependencytype
	function isSavedInDatabase(){
		return ($this->ReturnTypeID >= $this->ID || $this->ReturnTypeID==0);
	}
	
	// using the dependency in the other direction ?
	function setReverseMode(){
		$this->mode = SUSHEE_DEP_REVERSE_MODE;
	}
	
	function IsReverseMode(){
		return ($this->mode == SUSHEE_DEP_REVERSE_MODE);
	}
	
	
	function getIDInDatabase(){
		if(!$this->isSavedInDatabase()){
			return $this->ReturnTypeID;
		}
		return $this->ID;
	}
	
	function getOriginFieldname(){
		// dependency is not saved: we use the dependency in the other  direction
		if(!$this->isSavedInDatabase() && !$this->isReverseMode()){ // in reverse mode, the change of direction  is cancelled by the reversing of the dependency
			return 'TargetID';
		}
		if($this->isSavedInDatabase() && $this->isReverseMode()){
			return 'TargetID';
		}
		return 'OriginID';
	}
	
	function getTargetFieldname(){
		// dependency is not saved: we use the dependency in the other  direction
		if(!$this->isSavedInDatabase() && !$this->isReverseMode()){
			return 'OriginID';
		}
		if($this->isSavedInDatabase() && $this->isReverseMode()){
			return 'OriginID';
		}
		return 'TargetID';
	}
	
	function getOrderingFieldname(){
		// dependency is not saved: we use the dependency in the other  direction
		if(!$this->isSavedInDatabase()){
			return 'TargetOrdering';
		}
		return 'Ordering';
	}
	
	function getTargetOrderingFieldname(){
		// dependency is not saved: we use the dependency in the other  direction
		if(!$this->isSavedInDatabase()){
			return 'Ordering';
		}
		return 'TargetOrdering';
	}
	
	
    function dependencyType($type/* can be the ID too */,$moduleName=""){
        $this->loaded = false;
        $db_conn = db_connect();
        // if it's a number, we consider it's the ID instead of the name
        if ( !is_numeric($type) ){
            $sql = "SELECT * FROM `dependencytypes` WHERE `Denomination`=\"".$type."\";";
        }else{
            $sql = "SELECT * FROM `dependencytypes` WHERE `ID`=$type;";
        }
        $recordSet = $db_conn->Execute($sql);
        if (!$recordSet){
            $this->_lastError = "SQL Problem finding the dependencyType ".$type.".";
            return $this;
        }else{ // an ID cannot be ambiguous
            if ( $recordSet->RecordCount()>1 ){ // ambiguous -> we must use the module to determine which type it is
                $moduleInfo = moduleInfo($moduleName);
                if (!$moduleInfo->loaded){
                    $this->_lastError = "Ambiguous dependencyType named ".$type." -> precise the module";
                    return $this;
                }
                // looping while we found the correct module
                while ($elem = $recordSet->FetchRow()) {
                    if ($elem["ModuleOriginID"]==$moduleInfo->ID){
                        $found = true;
                        break;
                    }
                }
                if (!$found){
                    $this->_lastError = "Ambiguous dependencyType named `".$type."` and no valid module precised.";
                    return $this;
                }
            }else
                $elem = $recordSet->FetchRow();
            // setting the attributes of the object
            $this->name = $elem["Denomination"];
            $this->ID = $elem["ID"];
            $this->ModuleOriginID = $elem["ModuleOriginID"];
            $this->ModuleTargetID = $elem["ModuleTargetID"];
			$this->isTemporal = $elem['Temporal'];
			$this->domain = $elem['Domain'];
            $this->ReturnTypeID = $elem["ReturnTypeID"];
			$this->tableName = $elem['TableName'];
            
			// checking security
			$moduleInfo = moduleInfo($this->ModuleOriginID);
			if ($moduleInfo->getServiceSecurity('dependencies')==='0' && $moduleInfo->getDepTypeSecurity($this->name)==='0')
				$this->loaded = false;
			else
            	$this->loaded = true;
        }
    }

	function getTraductionsXML(){
		$db_conn = db_connect();
		$trad_sql = "SELECT * FROM `dependencytraductions` WHERE `DependencyTypeID`=".$this->ID;
		
		$request = new Sushee_Request();
		if($request->isLanguageRestricted()){
			$trad_sql.= ' AND `LanguageID` IN ("'.$request->getLanguage().'","shared")';
		}
		$trad_rs = $db_conn->Execute($trad_sql);
		$trads='<DENOMINATION>';
		if($trad_rs){
			while($trad_row = $trad_rs->FetchRow()){
				if($trad_row["Text"]===''){
					$trad_row["Text"]=$this->getName();
				}
				$trads.="<LABEL languageID='".$trad_row["LanguageID"]."'>".encode_to_XML($trad_row["Text"])."</LABEL>";
				$trads.="<SEARCHLABEL languageID='".$trad_row["LanguageID"]."'>".encode_to_XML($trad_row["SearchLabel"])."</SEARCHLABEL>";
			}
		}
		$trads.="</DENOMINATION>";
		return $trads;
	}
	function getTraductionXML($languageID){
		$db_conn = db_connect();
		$trad_sql = 'SELECT `Text`,`SearchLabel`,`LanguageID` FROM `dependencytraductions` WHERE `DependencyTypeID`='.$this->ID.' AND `LanguageID` IN ("'.$languageID.'","shared");';
		$trad_row = $db_conn->GetRow($trad_sql);
		$trads='';
		if ($trad_row){
			if($trad_row["Text"]===''){
				$trad_row["Text"]=$this->getName();
			}
			$trads.='<LABEL languageID="'.$trad_row['LanguageID'].'">'.encode_to_XML($trad_row["Text"]).'</LABEL>';
		}else{
			$trads.='<LABEL languageID="'.$languageID.'">'.encode_to_XML($this->getName()).'</LABEL>';
		}
		return $trads;
	}
	
	function getTemporalXML(){
		if($this->isTemporal())
			$entity_result.="<TEMPORAL>1</TEMPORAL>";
		else
			$entity_result.="<TEMPORAL>0</TEMPORAL>";
		return $entity_result;
	}
	
	function getTypeXML(){
		$entity_result.="<TYPE>".encode_to_XML($this->getName())."</TYPE>";
		return $entity_result;
	}
	
	function getAnnexFieldsXML(){
		$entity_result.="<CONFIG>".$this->config."</CONFIG>";
		$entity_result.="<DOMAIN>".encode_to_XML($this->domain)."</DOMAIN>";
		$entity_result.=$this->getTemporalXML();
		$entity_result.="<TABLENAME>".encode_to_XML($this->getTableName())."</TABLENAME>";
		return $entity_result;
	}
	
	function getXML(){
		$entity_result = '';
		$entity_result.=$this->getTypeXML();
		$entity_result.=$this->getTraductionsXML();
		$entity_result.=$this->getAnnexFieldsXML();
		return $entity_result;
	}
	
	function preprocess($depOperation,$dep){
		require_once(dirname(__FILE__)."/../common/dependencies_processors.class.php");
		$process = new sushee_DependencyProcessingQueue($this,$depOperation,SUSHEE_PREPROCESSOR);
		$process->setDependency($dep);
		$process->execute();
		return $process;
	}
	
	function postprocess($depOperation,$dep){
		require_once(dirname(__FILE__)."/../common/dependencies_processors.class.php");
		$process = new sushee_DependencyProcessingQueue($this,$depOperation,SUSHEE_POSTPROCESSOR);
		$process->setDependency($dep);
		$process->execute();
		return $process;
	}
	
	function delete(){
		$db_conn = db_connect();
		$ID = $this->getID();
		$returnTypeID = $this->ReturnTypeID;
		
		// only saving one dep even for a bidirectional deptype, so only needs to delete one deptype
		$sql = "DELETE FROM `".$this->getTablename()."` WHERE `DependencyTypeID`=".$this->getIDinDatabase();
		$db_conn->Execute($sql);

		$sql = "DELETE FROM `dependencytraductions` WHERE `DependencyTypeID`=$ID OR `DependencyTypeID`=$returnTypeID;";
		$db_conn->Execute($sql);

		$sql = "DELETE FROM `dependencytypes` WHERE `ID`=$ID OR `ID`=$returnTypeID;";
		$db_conn->Execute($sql);
		
		// force types lists to be resaved in session		
		$set = new DependencyTypeSet($this->moduleOriginID);
		$set->clearInSession();
		
		$set = new DependencyTypeSet(false,$this->ModuleTargetID);
		$set->clearInSession();
		
		$set = new DependencyTypeSet($this->moduleOriginID,$this->moduleTargetID);
		$set->clearInSession();
		
		return true;
	}
	
	function getSecurity(){
		$moduleOriginInfo = $this->getModuleOrigin();
		$moduleTargetInfo = $this->getModuleTarget();
		
		// modules dont exist
		if(!$moduleOriginInfo->loaded || !$moduleTargetInfo->loaded){
			return false;
		}
		
		// modules are not allowed to user
		if(!$moduleOriginInfo->getGeneralSecurity() || !$moduleTargetInfo->getGeneralSecurity()){
			return false;
		}
		return $moduleOriginInfo->getDepTypeSecurity($this->getName());
	}
}

class sushee_dependencyType extends dependencyType{
	
}

class DependencyTypeSet extends SusheeObject{
	var $ModuleTargetID;
	var $ModuleOriginID;
	var $vector;
	var $uturn;
	var $domain;
	function DependencyTypeSet(/* int */ $ModuleOriginID=false,/* int */ $ModuleTargetID=false){
		$this->ModuleTargetID = $ModuleTargetID;
		$this->ModuleOriginID = $ModuleOriginID;
		$this->domain = false;
		$this->rebuild();
	}
	
	function setModuleOrigin(/* int */ $ModuleID){
		$this->ModuleOriginID = $ModuleID;
	}
	function setModuleTarget(/* int */ $ModuleID){
		$this->ModuleTargetID = $ModuleID;
	}
	function setDomain(/* String */ $domain){
		$this->domain = $domain;
		$this->rebuild();
	}
	
	function setUturn($bool){
		$this->uturn = $bool;
		$this->rebuild();
	}
	
	function rebuild(){
		$this->vector = new Vector();
		
		// IDs of depTypes are maybe saved in session
		// the name of the variable in session
		$varname = $this->getSessionVarname();
		$IDs_array_in_session = Sushee_Session::getVariable($varname);
		if(is_array($IDs_array_in_session)){
			// loading the deptypes from the session
			foreach($IDs_array_in_session as $ID){
				$this->vector->add($ID,depType($ID));
			}
		}else{
			// loading the deptypes from the database
			
			$sql = '/* DependencyTypeSet */ SELECT `ID` FROM `dependencytypes`';
			$fields = array();
			if($this->ModuleTargetID)
				$fields[]='`ModuleTargetID`=\''.$this->ModuleTargetID.'\'';
			if($this->ModuleOriginID)
				$fields[]='`ModuleOriginID`=\''.$this->ModuleOriginID.'\'';
			if($this->uturn===false)
				$fields[]='`ReturnTypeID`!=`ID`';
			if($this->domain!==false)
				$fields[]='`Domain`="'.$domain.'"';
			if(sizeof($fields)>0)
				$sql.=' WHERE '.implode(' AND ',$fields);
			$db_conn = db_connect();
			sql_log($sql);
			$rs = $db_conn->Execute($sql);
			if($rs){
				// array to save in session
				$IDs_array = array();
				while($row = $rs->FetchRow()){
					$depType = depType($row['ID']);
					if($depType->getSecurity()){
						$this->vector->add($row['ID'],depType($row['ID']));
						$IDs_array[]= $row['ID'];
					}
				}
				Sushee_Session::saveVariable($varname,$IDs_array);
			}
		}
		
		
	}
	
	function getTypes(){
		return $this->vector;
	}
	
	function getType($type){
		if($this->vector->exists($type))
			return $this->vector->getElement($type);
		else
			return false;
	}
	
	function next(){
		return $this->vector->next();
	}
	
	function reset(){
		$this->vector->reset();
	}
	
	function getSessionVarname(){
		if($this->ModuleTargetID)
			return 'DependencyTypeSet'.$this->ModuleOriginID.'_'.$this->ModuleTargetID;
		else
			return 'DependencyTypeSet'.$this->ModuleOriginID;
	}
	
	function clearInSession(){
		$varname = $this->getSessionVarname();
		Sushee_Session::clearVariableStartingWith($varname);
	}
}

?>
