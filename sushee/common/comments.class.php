<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/comments.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/comments.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");

class NectilElementComment extends SusheeObject{
	
	var $ID = false;
	var $title = false;
	var $body = false;
	var $type = false;
	var $file = false;
	var $creator = false;
	var $checked = false;
	var $targetID;
	var $moduleID;
	
	function getID(){
		return $this->ID;
	}
	
	function loadFields($fields_collection=false){
		if($this->getID()){
			$sql = 'SELECT * FROM `comments` WHERE `ID`=\''.$this->getID().'\'';
			sql_log($sql);
			$db_conn = db_connect();
			$values = $db_conn->getRow($sql);
			$this->title = $values['Title'];
			$this->body = $values['Body'];
			$this->type = $values['Type'];
			$this->file = $values['File'];
			$this->creator = $values['CreatorID'];
			$this->checked = $values['Checked'];
			$this->targetID = $values['TargetID'];
			$this->moduleID = $values['ModuleTargetID'];
		}
	}
	
	function NectilElementComment($ID=false){
		$this->ID = $ID;
	}
	
	function setTitle($title){
		$this->title = $title;
	}
	
	function setBody($body){
		$this->body = $body;
	}
	
	function setChecked($checked){
		$this->checked = $checked;
	}
	
	function setModule($module){
		$this->moduleID = $module->ID;
	}
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function setTargetID($targetID){
		$this->targetID = $targetID;
	}
	
	function setType($type){
		$this->type = $type;
	}
	
	function setFile($file){
		$this->file = $file;
	}
	
	function setCreator($creator){
		$this->creator = $creator;
	}
	
	function save(){
		if(!$this->ID){
			return $this->create();
		}else{
			return $this->update();
		}
	}
	
	function prepareValuesArray(){
		$values = array();
		
		
		if($this->targetID)
			$values["TargetID"] = $this->targetID;
		if($this->moduleID)
			$values["ModuleTargetID"]=$this->moduleID;
		if($this->creator){
			$values["CreatorID"]=$this->creator;
		}else{
			$user = new NectilUser();
			if($user->isAuthentified()){
				$values["CreatorID"] = $user->getID();
			}
		}
			
		if($this->type)
			$values["Type"]=$this->type;
		if($this->checked!==false)
			$values["Checked"]=$this->checked;
		if($this->title)
			$values["Title"]=$this->title;
		if($this->body)
			$values["Body"]=$this->body;
		if($this->file)
			$values["File"]=$this->file;
			
		if($this->ID){
			$former_values = getComment($this->ID);
			if ( ($values['File']!=FALSE && $values['File']!=$former_values['File']) || ($values['Title']!=FALSE && $values['Title']!=$former_values['Title']) || ($values['Body']!=FALSE && $values['Body']!=$former_values['Body']) || ($values['Type']!=FALSE && $values['Type']!=$former_values['Type']) ){
				$values["ModificationDate"]=$GLOBALS['sushee_today'];
			}
			$new_values = array_merge($former_values,$values);
		}else{
			$values["ModificationDate"]=$GLOBALS['sushee_today'];
			$new_values = $values;
		}
		
		$SearchTxt=$new_values["Title"];
		if($SearchTxt)
			$SearchTxt.=' ';
		$SearchTxt.=$new_values["Body"];
		$values['SearchText']=strtolower(removeaccents(decode_from_XML($SearchTxt)));
		
		$values['IP']=$_SERVER['REMOTE_ADDR'];
		
		
		
		return $values;
	}
	
	function create(){
		
		//  --- SQL TREATMENT --- 
		$db_conn = db_connect();
		$values = $this->prepareValuesArray();
		$values["CreationDate"]=$GLOBALS['sushee_today'];
		$pseudo_sql = "SELECT * FROM `comments` WHERE `ID`=-1;";
		$pseudo_rs = $db_conn->Execute($pseudo_sql);
		$sql = $db_conn->GetInsertSQL($pseudo_rs, $values);
		sql_log($sql);
		$db_conn->Execute($sql);
		$this->ID = $db_conn->Insert_Id();
		//  --- SQL END --- 
		
		//  --- ACTION LOGGING --- 
		$action_log_file = new UserActionLogFile();
		$moduleInfo = $this->getModule();
		$action_object = new UserActionObject($moduleInfo->getName(),$this->targetID);
		$user_action_filter = array('SearchText','ModificationDate','ModifierID','CreatorID','CreationDate','TargetID','ModuleTargetID'); // fields we dont want to log
		foreach($values as $field=>$content){
			if($content && !in_array($field,$user_action_filter)){
				$action_target = new UserActionTarget(UA_OP_APPEND,UA_SRV_COMM,$field,$content,$this->ID);
				$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
				$action_log_file->log( $action_log );
			}
		}
		//  --- END LOGGING ---
		
		return true;
	}
	
	function update(){
		
		//  --- SQL TREATMENT ---
		$db_conn = db_connect();
		$values = $this->prepareValuesArray();
		$comment_rs = $db_conn->Execute("SELECT * FROM `comments` WHERE `ID`='".$this->ID."';");
		$sql = $db_conn->GetUpdateSQL($comment_rs, $values);
		$former_values = $comment_rs->FetchRow();
		sql_log($sql);
		$db_conn->Execute($sql);
		//  --- SQL END --- 
		
		//  --- ACTION LOGGING --- 
		$action_log_file = new UserActionLogFile();
		$moduleInfo = $this->getModule();
		$action_object = new UserActionObject($moduleInfo->getName(),$this->targetID);
		$user_action_filter = array('SearchText','ModificationDate','ModifierID','CreatorID','CreationDate','TargetID','ModuleTargetID'); // fields we dont want to log
		foreach($values as $field=>$content){
			if($former_values[$field] !=$content && !in_array($field,$user_action_filter)){
				$action_target = new UserActionTarget(UA_OP_MODIFY,UA_SRV_COMM,$field,$content,$this->ID);
				$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
				$action_log_file->log( $action_log );
			}
		}
		//  --- END LOGGING ---
		
		
		return true;
	}
	
	function delete(){
		//  --- SQL TREATMENT ---
		$this->loadFields(); // for further logging (need to know the module, etc)
		$db_conn = db_connect();
		$sql = "DELETE FROM `comments` WHERE `ID`='".$this->getID()."';";
		sql_log($sql);
		$db_conn->Execute($sql);
		//  --- SQL END --- 
		
		//  --- ACTION LOGGING --- 
		$action_log_file = new UserActionLogFile();
		$moduleInfo = $this->getModule();
		$action_object = new UserActionObject($moduleInfo->getName(),$this->targetID);
		$action_target = new UserActionTarget(UA_OP_REMOVE,UA_SRV_COMM,false,false,$this->ID);
		$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
		$action_log_file->log( $action_log );
		//  --- END LOGGING ---
		
		
	}
}

?>