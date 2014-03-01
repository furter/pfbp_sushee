<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/omnilinktype.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/susheesession.class.php");

define('OMNILINK_KEYWORD','Omnilink');

// class representing a type of omnilink. A Type of omnilink indicates from which kind of element the link starts and technique details (table, etc)
class sushee_OmnilinkType extends SusheeObject{
	
	var $loaded;
	var $name;
	var $tableName;
	var $moduleID;
	var $ID;
	
	
	function sushee_OmnilinkType($denomination){
		// loading
		$sql = 'SELECT * FROM `omnilinktypes` WHERE `ID` != 1 AND `Activity` = 1 AND ';
		if(is_numeric($denomination)){
			// actually its the ID
			$sql.='`ID` = \''.encode_for_db($denomination).'\';';
		}else{
			$sql.='`Denomination` = "'.encode_for_db($denomination).'";';
		}
		
		$db_conn = db_connect();
		sql_log($sql);
		$row = $db_conn->getRow($sql);
		
		if(!$row){
			$this->loaded = false;
			$this->setError('Type doesnt exist');
		}else{
			$this->loaded = true;
			$this->ID = $row['ID'];
			$this->tableName = $row['TableName'];
			$this->name = $row['Denomination'];
			$this->moduleID = $row['ModuleID'];
		}
		
	}
	
	function setError($error){
		$this->error = $error;
	}
	
	function getError(){
		return $this->error;
	}
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function getModuleID(){
		return $this->moduleID;
	}
	
	function getName(){
		return $this->name;
	}
	
	function getID(){
		return $this->ID;
	}
	
	function getTableName(){
		return $this->tableName;
	}
	
	function isLoaded(){
		return $this->loaded;
	}
	
	function clearInSession(){
		Sushee_Session::clearVariable(OMNILINK_KEYWORD.$this->getName());
		Sushee_Session::clearVariable(OMNILINK_KEYWORD.$this->getID());
		Sushee_Session::clearVariable(OMNILINK_KEYWORD.'TypeSet'.$this->getModule()->getID());
	}
	
	function load(){
		// loading all links of this type
		if(!$this->rs){
			$sql = 'SELECT * FROM `'.$this->getTableName().'` WHERE `TypeID` = \''.$this->getID().'\'';
			$db_conn = db_connect();

			$this->rs = $db_conn->execute($sql);
		}
		
		return $this->rs;
	}
	
	function nextLink(){
		$rs = $this->load();
		$row = $rs->fetchRow();
		if(!$row)
			return false;
		return new sushee_Omnilink($this,$row[$this->getOriginFieldname()],$row['ModuleID'],$row[$this->getTargetFieldname()],$row['Ordering']);
	}
	
	function getOriginFieldname(){
		return 'OmnilinkerID';
	}
	
	function getTargetFieldname(){
		return 'ElementID';
	}
	
}

// function handling the caching of the types in session
function sushee_OmnilinkType($type_name){
	$variable_name = OMNILINK_KEYWORD.$type_name;
	if(!Sushee_Session::getVariable($variable_name)){
		$type = new sushee_OmnilinkType($type_name);
		// saving the two forms of accessing the module definition in the session (by ID and by name)
		if($type->isLoaded()){
			// saving with the two forms
			Sushee_Session::saveVariable(OMNILINK_KEYWORD.$type->getName(),$type);
			Sushee_Session::saveVariable(OMNILINK_KEYWORD.$type->getID(),$type);
		}else{
			// if its not a valid module, simply returning the object as it is and not saving in session something invalid
			return $type;
		}
		
	}
	return Sushee_Session::getVariable($prefix.$variable_name);
}

class sushee_OmnilinkTypeSet extends SusheeObject{
	
	var $ModuleOriginID;
	var $vector;
	
	function sushee_OmnilinkTypeSet(/* int */ $ModuleOriginID=false){
		$this->ModuleOriginID = $ModuleOriginID;
		$this->rebuild();
	}
	
	function setModuleOrigin(/* int */ $ModuleID){
		$this->ModuleOriginID = $ModuleID;
	}
	
	function rebuild(){
		$this->vector = new Vector();
		
		// IDs of types are maybe saved in session
		// the name of the variable in session
		$varname = $this->getSessionVarname();
		//$IDs_array_in_session = Sushee_Session::getVariable($varname);
		if(is_array($IDs_array_in_session)){
			// loading the types from the session
			foreach($IDs_array_in_session as $ID){
				$this->vector->add($ID,sushee_OmnilinkType($ID));
			}
		}else{
			// loading the types from the database
			$sql = '/* OmnilinkTypeSet */ SELECT `ID` FROM `omnilinktypes`';
			$fields = array();
			if($this->ModuleOriginID)
				$fields[]='`ModuleID`=\''.$this->ModuleOriginID.'\'';
			$sql.=' WHERE `Activity` = 1 AND `ID` != 1';
			if(sizeof($fields)>0){
				$sql.=' AND '.implode(' AND ',$fields);
			}
			$db_conn = db_connect();
			sql_log($sql);
			$rs = $db_conn->Execute($sql);
			if($rs){
				// array to save in session
				$IDs_array = array();
				while($row = $rs->FetchRow()){
					$this->vector->add($row['ID'],sushee_OmnilinkType($row['ID']));
					$IDs_array[] = $row['ID'];
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
		return OMNILINK_KEYWORD.'TypeSet'.$this->ModuleOriginID;
	}
	
	function clearInSession(){
		$varname = $this->getSessionVarname();
		Sushee_Session::clearVariableStartingWith($varname);
	}
}

?>