<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/omnilinks.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/console.class.php");
require_once(dirname(__FILE__)."/../common/susheesession.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/omnilinktype.class.php");


// class handling OMNILINKS nodes in an element creation/update
class sushee_OmnilinksFactory extends SusheeObject{
	
	var $xmlNode;
	var $elementID;
	var $ModuleID;
	var $elementValues; // for virtual security
	var $console;
	
	function sushee_OmnilinksFactory($ModuleID,$xmlNode,$elementID,$elementValues=array()){
		$this->console = new XMLConsole();
		
		$this->ModuleID = $ModuleID;
		$this->moduleInfo = moduleInfo($ModuleID);
		
		$this->xmlNode = $xmlNode;
		$this->elementID = $elementID;
		$this->elementValues = $elementValues;
	}
	
	function getElementID(){
		return $this->elementID;
	}
	
	function setElementID($elementID){
		$this->elementID = $elementID;
	}
	
	function getModule(){
		return moduleInfo($this->ModuleID);
	}
	
	function getModuleID(){
		return $this->ModuleID;
	}
	
	function execute(){
		$omnilinks_nodes = $this->xmlNode->getElements('OMNILINKS');
		foreach($omnilinks_nodes as $omnilinks_node){
			$this->console->addMessage('<OMNILINKS>');
			$omnilink_nodes = $omnilinks_node->getElements('OMNILINK');
			foreach($omnilink_nodes as $node){
				$operation = $node->getAttribute('operation');
				if(!$operation){
					$operation = 'replace';
				}
				$mode = $node->getAttribute('mode');
				$type_val = $node->getAttribute('type');
				$type = sushee_OmnilinkType($type_val);
				$this->console->addMessage('<OMNILINK type="'.$type->getName().'" operation="'.$operation.'">');
				if(!$type->isLoaded()){
					$this->console->addMessage('Type `'.$type_val.'` doesnt exist');
				}else{
					switch($operation){
						case 'append':
							$this->append($type,$mode,$node);
							break;
						case 'remove':
							$this->remove($type,$mode,$node);
							break;
						default:
							$this->replace($type,$mode,$node);
							break;
					}
				}
				$this->console->addMessage('</OMNILINK>');
			}
			$this->console->addMessage('</OMNILINKS>');
		}
		
	}
	
	// appending new omnilinks
	function append($type,$mode,$xmlNode){
		$targetNodes = $xmlNode->getChildren();
		// looping on the element nodes <CONTACT ID=""/>, <MEDIA ID=""/>
		$elt_parser = new sushee_ElementParser();
		foreach($targetNodes as $targetNode){
			$targetNodename = $targetNode->nodename();
			if(!$elt_parser->execute($targetNode)){
				$console_message = $elt_parser->getLastError();
			}else{
				$newLink = $this->getOmnilink($type,$this->getElementID(),$elt_parser->getModuleID(),$elt_parser->getElementID());
				$newLink->create();
				$console_message = $newLink->getLastError();
			}
			$this->console->addMessage('<'.$targetNodename.' ID="'.$elt_parser->getElementID().'">'.$console_message.'</'.$targetNodename.'>');
		}
	}
	
	// removing the specific omnilink indicated in the request
	function remove($type,$mode,$xmlNode){
		$targetNodes = $xmlNode->getChildren();
		// looping on the element nodes <CONTACT ID=""/>, <MEDIA ID=""/>
		$elt_parser = new sushee_ElementParser();
		foreach($targetNodes as $targetNode){
			$targetNodename = $targetNode->nodename();
			if(!$elt_parser->execute($targetNode)){
				$console_message = $elt_parser->getLastError();
			}else{
				$newLink = $this->getOmnilink($type,$this->getElementID(),$elt_parser->getModuleID(),$elt_parser->getElementID());
				$newLink->delete();
				$console_message = $newLink->getLastError();
			}
			$this->console->addMessage('<'.$targetNodename.' ID="'.$elt_parser->getElementID().'">'.$console_message.'</'.$targetNodename.'>');
		}
	}
	
	// return the omnilink to create, in the right direction
	function getOmnilink($type,$sourceID,$targetModuleID,$targetElementID,$ordering=false){
		// testing which direction is the link
		if($this->getModuleID()==$type->getModuleID()){
			// from the omnilinked element to the multi-element
			$newLink = new sushee_Omnilink($type,$sourceID,$targetModuleID,$targetElementID,$ordering);
		}else{
			$newLink = new sushee_Omnilink($type,$targetElementID,$this->getModuleID(),$sourceID,$ordering);
		}
		return $newLink;
	}
	
	// replacing all the omnilinks of the element
	function replace($type,$mode,$xmlNode){
		// first removing the omnilink already present
		$previous_links = new sushee_ElementOmnilinked($type,$this->getElementID());
		while($link = $previous_links->next()){
			$targetModuleInfo = $link->getModuleTarget();
			if(!$xmlNode->getElement($targetModuleInfo->getxSusheeName().'[@ID='.$link->getElementID().']')){
				$link->delete();
			}
		}
		
		// managing the new ones and changing the ordering 
		$targetNodes = $xmlNode->getChildren();
		// looping on the element nodes <CONTACT ID=""/>, <MEDIA ID=""/>
		$elt_parser = new sushee_ElementParser();
		$ordering = 1;
		foreach($targetNodes as $targetNode){
			$targetNodename = $targetNode->nodename();
			if(!$elt_parser->execute($targetNode)){
				$console_message = $elt_parser->getLastError();
			}else{
				$newLink = $this->getOmnilink($type,$this->getElementID(),$elt_parser->getModuleID(),$elt_parser->getElementID(),$ordering);
				$newLink->create();
				$console_message = $newLink->getLastError();
			}
			$this->console->addMessage('<'.$targetNodename.' ID="'.$elt_parser->getElementID().'">'.$console_message.'</'.$targetNodename.'>');
			$ordering++;
		}
	}
	
	// returning a description of the handling in XML
	function getXML(){
		return $this->console->getXML();
	}
	
}

// helper class : parses a node and returns its module and ID
class sushee_ElementParser extends SusheeObject{
	
	var $moduleInfo;
	var $elementID;
	var $error;
	
	function sushee_ElementParser(){}
	
	function execute($xmlNode){
		$nodename = $xmlNode->nodename();
		$this->elementID = $xmlNode->getAttribute('ID');
		$this->moduleInfo = moduleInfo($nodename);
		if(!$this->moduleInfo->loaded){
			$this->error = $this->moduleInfo->getLastError();
			return false;
		}else if(!$this->elementID){
			if($xmlNode->exists("INFO/*")){
				// creating element before attaching it
				
				// composing a new request with <CREATE>
				$createRequest = new XML('<CREATE>'.$xmlNode->toString().'</CREATE>');
				
				$nqlOp = new createElement($name,$createRequest->getElement('/CREATE'));
				$nqlOp->execute();
				$this->elementID = $nqlOp->getID();
			}
			if(!$this->elementID){
				$this->error = 'No ID on this element';
				return false;
			}
			
		}
		return true;
	}
	
	function getElementID(){
		return $this->elementID;
	}
	
	function getModule(){
		return $this->moduleInfo;
	}
	
	function getModuleID(){
		return $this->moduleInfo->getID();
	}
	
	function getLastError(){
		return $this->error;
	}
}

// class handling the destruction of an element omnilinks
class sushee_OmnilinksDestructor extends SusheeObject{
	
	var $moduleID;
	var $elementID;
	var $error;
	
	function sushee_OmnilinksDestructor($moduleInfo,$elementID = false){
		$this->moduleID = $moduleInfo->getID();
		$this->elementID = $elementID;
	}
	
	function execute(){
		// taking the different types of omnilink for this module
		$types = new sushee_omnilinkTypeSet($this->getModule()->getID());
		
		while($type = $types->next()){
			$elt_omnilinks = new sushee_ElementOmnilinkers($type,$this->getModule()->getID(),$this->getElementID());
			while($omnilink = $elt_omnilinks->next()){
				$res = $omnilink->delete();
				if(!$res){
					$this->setError('Could not delete all omnilinks : '.$omnilink->getLastError());
					return $res;
				}
			}
			$elt_omnilinks = new sushee_ElementOmnilinked($type,$this->getElementID());
			while($omnilink = $elt_omnilinks->next()){
				$res = $omnilink->delete();
				if(!$res){
					$this->setError('Could not delete all omnilinks : '.$omnilink->getLastError());
					return $res;
				}
			}
		}
		return true;
	}
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function getElementID(){
		return $this->elementID;
	}
	
	function setElementID($elementID){
		$this->elementID = $elementID;
	}
	
	function getLastError(){
		return $this->error;
	}
	
	function setError($error){
		$this->error = $error;
	}
	
}

abstract class sushee_OmnilinksSet extends SusheeObject{
	function reset(){
		$this->loaded = false;
	}
	
	function next(){
		$this->load();
		if($this->rs){
			$row = $this->rs->fetchRow();
			if($row){
				return new sushee_Omnilink($this->type,$row['OmnilinkerID'],$row['ModuleID'],$row['ElementID'],$row['Ordering']);
			}else{
				return false;
			}
		}
		return false;
	}
	
	abstract function load();
}

// class handling the omnilinkers of an element (Iterator)
class sushee_ElementOmnilinkers extends sushee_OmnilinksSet{
	
	var $type;
	var $moduleID;
	var $elementID;
	var $loaded;
	var $rs;
	
	function sushee_ElementOmnilinkers($type,$moduleID,$ID){
		$this->type = $type;
		if(is_object($moduleID)){ // the method can also receive the moduleInfo object
			$this->moduleID = $moduleID->getID();
		}else{
			$this->moduleID = $moduleID;
		}
		$this->elementID = $ID;
	}
	
	function load(){
		if(!$this->loaded){
			$type = $this->type;
			$sql = 'SELECT `OmnilinkerID`,`ModuleID`,`ElementID`,`Ordering` FROM `'.$type->getTableName().'` WHERE `TypeID` = \''.$type->getID().'\' AND `ModuleID` = \''.encode_for_db($this->moduleID).'\' AND `ElementID` = \''.encode_for_db($this->elementID).'\' AND `Activity` = 1 ;';
			$db_conn = db_connect();
			sql_log($sql);
			$this->rs = $db_conn->Execute($sql);
			$this->loaded = true;
		}
		
	}
	
	
}
// class handling the elements omnilinked by a specific elt
class sushee_ElementOmnilinked extends sushee_OmnilinksSet{
	
	function sushee_ElementOmnilinked($type,$ID){
		$this->type = $type;
		$this->elementID = $ID;
	}
	
	
	
	function load(){
		if(!$this->loaded){
			$type = $this->type;
			$sql = 'SELECT `OmnilinkerID`,`ModuleID`,`ElementID`,`Ordering` FROM `'.$type->getTableName().'` WHERE `TypeID` = \''.$type->getID().'\' AND `OmnilinkerID` = \''.encode_for_db($this->elementID).'\' AND `Activity` = 1 ;';
			$db_conn = db_connect();
			sql_log($sql);
			$this->rs = $db_conn->Execute($sql);
			$this->loaded = true;
		}
		
	}
}

// class representing an omnilink, allowing to link a certain type of element to any type of other elements
class sushee_Omnilink extends SusheeObject{
	
	var $moduleID;
	var $omnilinkerID;
	var $elementID;
	var $ordering;
	var $error = false;
	var $row = null;
	var $exists = null;
	
	function sushee_Omnilink($type,$omnilinkerID,$moduleID,$elementID,$ordering=false){
		$this->type = $type;
		$this->omnilinkerID = $omnilinkerID;
		$this->moduleID = $moduleID;
		$this->elementID = $elementID;
		$this->ordering = $ordering;
	}
	
	function getModuleTarget(){
		return moduleInfo($this->moduleID);
	}
	
	function getElementID(){
		return $this->elementID;
	}
	
	function getOmnilinkerID(){
		return $this->omnilinkerID;
	}
	
	function create(){
		$db_conn = db_connect();
		$type = $this->type;
		
		if(!$this->exists()){
			$request = new Sushee_Request();
			$user = new Sushee_User();
			
			if($this->ordering)
				$order = $this->ordering;
			else
				$order = $this->getNextOrdering();
			
			// replacing because might exist yet but in Activity = 0, from a previous deleted omnilink
			$sql = 'REPLACE `'.$type->getTableName().'` SET `Activity` = 1 , `CreatorID` = \''.$user->getID().'\', `ModifierID` = \''.$user->getID().'\' , `CreationDate` = \''.$request->getDateSQL().'\' , `ModificationDate` = "'.$request->getDateSQL().'" , Ordering = \''.encode_for_db($order).'\' , `TypeID` = \''.$type->getID().'\' , `OmnilinkerID` = \''.encode_for_db($this->omnilinkerID).'\' , `ModuleID` = \''.encode_for_db($this->moduleID).'\' , `ElementID` = \''.encode_for_db($this->elementID).'\';';
			
			sql_log($sql);
			$res = $db_conn->execute($sql);
			
			if(!$res){
				$this->setError($db_conn->errorMsg());
				return false;
			}
			
			//  --- ACTION LOGGING --- 
			$action_log_file = new UserActionLogFile();
			$moduleInfo = $this->type->getModule();
			$action_object = new UserActionObject($moduleInfo->getName(),$this->getOmnilinkerID());

			$action_target = new Sushee_UserActionOmnilink(UA_OP_APPEND,UA_SRV_OMNI,$this->type->getName(),$this->getModuleTarget()->getName(),$this->getElementID(),'Order',$order);
			$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
			$action_log_file->log( $action_log );
			
			return true;
		}else{
			$res = $this->update();
			$this->setError('Link exists: updated');
			return $res;
		}
	}
	
	function delete(){
		$db_conn = db_connect();
		$type = $this->type;
		
		if($this->exists()){
			$request = new Sushee_Request();
			$user = new Sushee_User();
			
			$sql = 'UPDATE `'.$type->getTableName().'` SET `Activity` = 0 , `ModifierID` = \''.$user->getID().'\' , `ModificationDate` = "'.$request->getDateSQL().'" , Ordering = \''.encode_for_db($this->ordering).'\' WHERE `TypeID` = \''.$type->getID().'\' AND `OmnilinkerID` = \''.encode_for_db($this->omnilinkerID).'\' AND `Activity` = 1 AND `ModuleID` = \''.encode_for_db($this->moduleID).'\' AND `ElementID` = \''.encode_for_db($this->elementID).'\';';

			sql_log($sql);
			$res = $db_conn->execute($sql);
			
			if(!$res){
				$this->setError($db_conn->errorMsg());
				return false;
			}else{
				return true;
			}
		}else{
			$this->setError('Link doesnt exist');
			return false;
		}
	}
	
	function update(){
		$db_conn = db_connect();
		$request = new Sushee_Request();
		$user = new Sushee_User();
		$type = $this->type;
		
		// only updating and logging if the link is changing (only thing that can change is the ordering)
		$row = $this->getRow();
		if($this->ordering && $this->ordering != $row['Ordering']){
			$fields_update = '';
			if($this->ordering){
				$fields_update.=', Ordering = \''.encode_for_db($this->ordering).'\'';
			}

			$sql = 'UPDATE `'.$type->getTableName().'` SET `ModifierID` = \''.$user->getID().'\' , `ModificationDate` = "'.$request->getDateSQL().'" '.$fields_update.'  WHERE `TypeID` = \''.$type->getID().'\' AND `OmnilinkerID` = \''.encode_for_db($this->omnilinkerID).'\' AND `Activity` = 1 AND `ModuleID` = \''.encode_for_db($this->moduleID).'\' AND `elementID` = \''.encode_for_db($this->elementID).'\';';

			sql_log($sql);
			$res = $db_conn->execute($sql);

			if(!$res){
				$this->setError($db_conn->errorMsg());
				return false;
			}

			//  --- ACTION LOGGING --- 
			$action_log_file = new UserActionLogFile();
			$moduleInfo = $this->type->getModule();
			$action_object = new UserActionObject($moduleInfo->getName(),$this->getOmnilinkerID());

			$action_target = new Sushee_UserActionOmnilink(UA_OP_MODIFY,UA_SRV_OMNI,$this->type->getName(),$this->getModuleTarget()->getName(),$this->getElementID(),'Order',$this->ordering);
			$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
			$action_log_file->log( $action_log );
		}
		
		
		return true;
	}
	
	function exists(){
		if($this->exists===null){
			if($this->getRow()){
				$this->exists = true;
			}else{
				$this->exists = false;
			}
		}
		
		return $this->exists;
	}
	
	function getRow(){
		if($this->row===null){
			$db_conn = db_connect();
			$type = $this->type;

			$sql = 'SELECT `Ordering` FROM `'.$type->getTableName().'` WHERE `TypeID` = \''.$type->getID().'\' AND `OmnilinkerID` = \''.encode_for_db($this->omnilinkerID).'\' AND `Activity` = 1 AND `ModuleID` = \''.encode_for_db($this->moduleID).'\' AND `elementID` = \''.encode_for_db($this->elementID).'\';';

			sql_log($sql);
			$this->row = $db_conn->getRow($sql);
		}
		return $this->row;
	}
	
	function getLastError(){
		return $this->error;
	}
	
	function setError($error){
		$this->error = $error;
	}
	
	function getNextOrdering(){
		$db_conn = db_connect();
		$order_sql = 'SELECT MAX(`Ordering`) AS maximum FROM `'.$this->type->getTableName().'` WHERE `'.$this->type->getOriginFieldname().'`=\''.$this->omnilinkerID.'\' AND `TypeID`="'.$this->type->getID().'" AND `Activity` = 1'; 
		
		$row = $db_conn->GetRow($order_sql);
		if(!$row["maximum"])
			$order = 1;
		else
			$order = $row['maximum']+1;
		return $order;
	}
	
}

?>