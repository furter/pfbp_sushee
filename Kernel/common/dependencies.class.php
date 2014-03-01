<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/dependencies.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/console.class.php");
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../private/create.nql.php");
require_once(dirname(__FILE__).'/exception.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');

define('FROM_ORIGIN',0);
define('TO_ORIGIN',1);

class DependenciesFactory extends SusheeObject
{
	var $xmlNode;
	var $elementID;
	var $ModuleID;
	var $elementValues; // for virtual security
	var $console;
	
	function DependenciesFactory($ModuleID,$xmlNode,$elementID,$elementValues=array())
	{
		$this->console = new XMLConsole();
		
		$this->ModuleID = $ModuleID;
		$this->moduleInfo = moduleInfo($ModuleID);
		
		$this->xmlNode = $xmlNode;
		$this->elementID = $elementID;
		$this->elementValues = $elementValues;
	}
	
	function setElementID($elementID)
	{
		$this->elementID = $elementID;
	}
	
	function getXML()
	{
		return $this->console->getXML();
	}
	
	function resolveDependency($deptype,$mode,$targetID,$ordering = false)
	{
		if($mode == FROM_ORIGIN)
			return new Dependency($deptype,$this->elementID,$targetID,$ordering);
		else
			return new Dependency($deptype,$targetID,$this->elementID/*,$ordering*/); /* in this direction, ordering is not taken in account */
	}

	function execute()
	{
		$timelinkNodes = $this->xmlNode->getElements('./DEPENDENCIES/DEPENDENCY');
		if(sizeof($timelinkNodes)>0)
		{
			$this->console->addMessage('<DEPENDENCIES>');
			foreach($timelinkNodes as $node)
			{
				$operation = $node->valueOf('@operation');
				$mode = $node->valueOf('@mode');
				if($mode=='reverse')
				{
					$mode=TO_ORIGIN;
				}
				else
				{
					$mode=FROM_ORIGIN;
				}
				
				if($operation=='')
				{
					$operation='replace';
				}
				$type = $node->valueOf('@type');
				$deptype = new dependencyType($type);
				if($deptype->exists())
				{
					$this->console->addMessage('<DEPENDENCY type="'.$type.'" operation="'.$operation.'">');
					$factory = new DependencyFactory($deptype,$mode,$operation,$this->elementID,$node);
					$factory->execute();
					$this->console->addMessage($factory->getXML());
					$this->console->addMessage('</DEPENDENCY>');
				}
				else
				{
					$this->console->addMessage('<DEPENDENCY>Type '.$type.' doesnt exist</DEPENDENCY>');
				}
			}
			$this->console->addMessage('</DEPENDENCIES>');
		}
	}
}

class DependencyFactory extends SusheeObject
{
	var $mode;
	var $operation;
	var $type;
	var $xmlNode;
	var $elementID;
	var $console;
	
	function DependencyFactory($type,$mode,$operation,$elementID,$xmlNode)
	{
		$this->elementID = $elementID;
		$this->type = $type;
		$this->mode = $mode;
		$this->operation = $operation;
		$this->xmlNode = $xmlNode;
		$this->console = new XMLConsole();
		$this->sysconsole = new LogConsole();
	}

	function getXML()
	{
		return $this->console->getXML();
	}

	function resolveMode($targetID,$ordering = false)
	{
		$deptype = $this->type;
		if($this->mode == FROM_ORIGIN)
			return new Dependency($deptype,$this->elementID,$targetID,$ordering);
		else
			return new Dependency($deptype,$targetID,$this->elementID/*,$ordering*/); /* in this direction, ordering is not taken in account */
	}

	function execute()
	{
		switch($this->operation)
		{
			case 'remove':
				$this->delete();
				break;
			default:
				$this->create();
		}
	}
	
	function delete()
	{
		$elementNodes = $this->xmlNode->getElements('./*');
		foreach($elementNodes as $node)
		{
			$targetID = $node->valueOf('@ID');
			if($targetID && is_numeric($targetID))
			{
				$dep = $this->resolveMode($targetID);
				
				// developer can disable processor using a special attribute in the command
				if($this->xmlNode->exists('ancestor::*[@disable-processors="true"]'))
				{
					$dep->disableProcessors();
				}
				
				$res = $dep->delete();
				
				if($res)
				{
					$this->console->addMessage('<'.$node->name.' ID="'.$targetID.'">'.$dep->getMsg().'</'.$node->name.'>');
				}
				else
				{
					$this->console->addMessage('<'.$node->name.' ID="'.$targetID.'" errorCode="'.$dep->getError()->getCode().'">'.$dep->getError()->getMessage().'</'.$node->name.'>');
				}
			}
			else
			{
				$this->console->addMessage('<'.$node->name.'>ID is empty</'.$node->name.'>');
			}
		}
	}
	
	function create()
	{
		$elementNodes = $this->xmlNode->getElements('./*');
		$deptype = $this->type;
		$mode = $this->mode;
		$i = 1;
		foreach($elementNodes as $node){
			$targetID = $node->valueOf('@ID');
			
			// CREATING THE ELEMENT IF NO ID AND INFO NODE
			if($targetID===false && $node->exists("INFO/*")){
				// creating element before attaching it
				$shell = new Sushee_Shell();
				$shell->addCommand('<CREATE>'.$node->toString().'</CREATE>');
				$targetID = $shell->valueOf('/RESPONSE/MESSAGE/@elementID');
			}
			if($targetID=='visitor'){
				$targetID = Sushee_User::getID();
			}
			if($targetID && is_numeric($targetID)){
				
				// MODE
				if($this->operation=="append")
					$dep = $this->resolveMode($targetID);
				else
					$dep = $this->resolveMode($targetID,$i);
				
				// developer can disable processor using a special attribute in the command
				if($this->xmlNode->exists('ancestor::*[@disable-processors="true"]')){
					$dep->disableProcessors();
				}
				
				// DEPINFO + COMMENT
				$comment= $node->valueOf("COMMENT[1]");
				$depInfoNode = $node->getElement('DEPINFO[1]');
				if($depInfoNode){
					$depInfo=$depInfoNode->copyOf("/*");
					if (!$depInfo)
						$depInfo=$depInfoNode->valueOf();

					$depInfoOperator = $depInfoNode->getxSusheeOperator();
					if($depInfoOperator){
						$formerDepInfo = $dep->getFormerDepInfo();
						$depInfo = handleFieldOperator($depInfoOperator,$formerDepInfo,$depInfo);
					}
					$dep->setDepInfo($depInfo);
				}
				
				$dep->setComment($comment);
				
				
				if ($deptype->getName()=='keyringUsers')
					$already_existed = $dep->exists();
				// CREATE THE DEP
				$res = $dep->create();
				$i++;
				
				
				if($res){
					$this->console->addMessage('<'.$node->name.' ID="'.$targetID.'">');
					// dep creation successful
					$targetIDs[]=$targetID;
					if ($deptype->getName()=='keyringUsers' && !$already_existed){
						$this->sendKeyring($this->xmlNode,$dep);
					}

					if($depInfoOperator){
						$this->console->addMessage('<DEPINFO>'.$depInfo.'</DEPINFO>');
					}
					$this->console->addMessage($dep->getMsg());
					$this->console->addMessage('</'.$node->name.'>');
				}else{
					$this->console->addMessage('<'.$node->name.' ID="'.$targetID.'" errorCode="'.$dep->getError()->getCode().'">');
					// dep message by processor that caused the failure of the dep creation/update/delete
					$this->console->addMessage($dep->getError()->getMessage());
					$this->console->addMessage('</'.$node->name.'>');
				}
			}else{
				$this->console->addMessage('<'.$node->name.'>ID is empty</'.$node->name.'>');
			}
		}
		// deleting the dependencies that are not valid anymore
		if($this->operation=="replace"){
			if(sizeof($targetIDs)>0){
				if($mode == FROM_ORIGIN){
					$sql = 'SELECT * FROM `'.$deptype->getTableName().'` WHERE `DependencyTypeID`="'.$deptype->getIDInDatabase().'" AND `'.$deptype->getOriginFieldName().'`=\''.$this->elementID.'\' AND `'.$deptype->getTargetFieldName().'` NOT IN ('.implode(',',$targetIDs).')';
				}
			}else{
				if($mode == FROM_ORIGIN){
					$sql = 'SELECT * FROM `'.$deptype->getTableName().'` WHERE `DependencyTypeID`="'.$deptype->getIDInDatabase().'" AND `'.$deptype->getOriginFieldName().'`=\''.$this->elementID.'\'';
				}
			}
			if($sql){
				$db_conn = db_connect();
				$rs = $db_conn->Execute($sql);
				sql_log($sql);
				while($row = $rs->FetchRow()){
					$dep = new Dependency($deptype,$row[$deptype->getOriginFieldName()],$row[$deptype->getTargetFieldName()]);
					$dep->delete();
				}
			}
		}
	}

	function sendKeyring($xmlNode,$dep)
	{
		$contactID = $dep->targetID;
		$deptype = $this->type;
		$mode = $this->mode;
		$db_conn = db_connect();
		$former_keyring_sql = 'SELECT `'.$deptype->getOriginFieldName().'` FROM `'.$deptype->getTableName().'` WHERE `'.$deptype->getTargetFieldName().'`=\''.$contactID.'\' AND `DependencyTypeID`=\''.$deptype->getIDInDatabase().'\' AND `'.$deptype->getOriginFieldName().'`!=\''.$dep->originID.'\'';
		$this->sysconsole->addMessage($former_keyring_sql);
		$former_keyring = $db_conn->getRow($former_keyring_sql);

		if($former_keyring && $former_keyring['OriginID']==$dep->originID)
		{
			// if its the same keyring, no need to send an access 
			$this->sysconsole->addMessage('Nothing to send its the same keyring as before '.$former_keyring['OriginID']);
			return;
		}
		else
		{
			$delete_former_keyring_sql = 'DELETE FROM `'.$deptype->getTableName().'` WHERE `DependencyTypeID`=\''.$deptype->getIDInDatabase().'\' AND `'.$deptype->getTargetFieldName().'`=\''.$contactID.'\' AND `'.$deptype->getOriginFieldName().'`!=\''.$dep->originID.'\'';
			$this->sysconsole->addMessage($delete_former_keyring_sql);
			$db_conn->Execute($delete_former_keyring_sql);
		}

		$contact_moduleInfo = $deptype->getModuleTarget();
	    $contact = getInfo($contact_moduleInfo,$contactID);

		if ($contact['Email1'])
		{
			$this->sysconsole->addMessage('Sending access to '.$contact['Email1']);
			
			// if no password, generating one and sending it in the mail
			if (!$contact['Password'])
			{
				$password = $xmlNode->valueOf('@password');
				if(!$password)
				{
					$password = generate_password(8,1,'L');
				}

				$encrypted_password = mysql_password($password);

				// doing it with the shell to have the modification logged
				$shell = new Sushee_Shell();
				$shell->addCommand(
					'<UPDATE disable-processors="true">
						<CONTACT ID="'.$contact['ID'].'">
							<INFO>
								<PASSWORD>'.$password.'</PASSWORD>
							</INFO>
						</CONTACT>
					</UPDATE>');
				$shell->execute();

				$contact['Password'] = $password;
				$return_values['Password'] = $encrypted_password;

				$former_password = false;
			}
			else
			{
				$former_password = true;
			}

			if(!$xmlNode->exists("@sendMail[.='false']"))
			{
				include_once(dirname(__FILE__).'/../common/keyringmail.class.php');
				$keyringmail = new KeyringMail();
				$keyring = new Keyring($dep->originID);
				$contactElt = new Contact($contact);
				$keyringmail->setContact($contactElt);
				$keyringmail->setKeyring($keyring);
				if(!$former_password)
				{
					$keyringmail->setPassword($password);
				}

				$keyringmail->send();
			}
		}
	}
}

class Dependency extends SusheeObject{
	var $type;
	var $originID;
	var $targetID;
	var $ordering;
	var $console;
	var $depInfo;
	var $comment;
	var $row = false;
	var $processors_enabled = true;
	
	function getType(){
		return $this->type;
	}
	
	function getPosition(){
		return $this->ordering;
	}
	
	function Dependency($type/* object */,$originID,$targetID,$ordering=false,$depInfo=false,$comment=false){
		$this->type = $type;
		$this->originID = $originID;
		$this->targetID = $targetID;
		$this->ordering = $ordering;
		$this->console = new LogConsole();
		$this->depInfo = $depInfo;
		$this->comment = $comment;
	}
	
	function getOriginID(){
		return $this->originID;
	}
	
	function getTargetID(){
		return $this->targetID;
	}
	
	function setDepInfo($depInfo){
		$this->depInfo = $depInfo;
	}
	function setComment($comment){
		$this->comment = $comment;
	}
	
	function exists(){
		$db_conn = db_connect();
		$sql = 'SELECT `'.$this->type->getOrderingFieldname().'` FROM `'.$this->type->getTableName().'` WHERE `DependencyTypeID`="'.$this->type->getIDInDatabase().'" AND `'.$this->type->getOriginFieldname().'`=\''.$this->originID.'\' AND `'.$this->type->getTargetFieldname().'`=\''.$this->targetID.'\'';
		$this->console->addMessage($sql);
		$row = $db_conn->GetRow($sql);
		if($row)
			return true;
		else
			return false;
	}
	
	function getFormerRow(){
		if(!$this->row){
			$db_conn = db_connect();
			$sql = 'SELECT `'.$this->type->getOrderingFieldname().'`,`DepInfo`,`Comment` FROM `'.$this->type->getTableName().'` WHERE `DependencyTypeID`="'.$this->type->getIDInDatabase().'" AND `'.$this->type->getOriginFieldname().'`=\''.$this->originID.'\' AND `'.$this->type->getTargetFieldname().'`=\''.$this->targetID.'\'';
			sql_log($sql);
			$this->row = $db_conn->GetRow($sql);
		}
		return $this->row;
	}
	
	function getFormerOrdering(){
		$row = $this->getFormerRow();
		if($row)
			return $row[$this->type->getOrderingFieldname()];
		else
			return false;
	}
	
	function getFormerDepInfo(){
		$row = $this->getFormerRow();
		if($row)
			return $row['DepInfo'];
		else
			return false;
	}
	function getFormerComment(){
		$row = $this->getFormerRow();
		if($row)
			return $row['Comment'];
		else
			return false;
	}
	
	function getXML(){
		$moduleInfo = $this->type->getModuleTarget();
		$nodename = strtoupper($moduleInfo->name);
		return '<'.$nodename.' ID="'.$this->targetID.'"/>';
	}
	
	function getSearchtext(){
		$SearchTxt=$this->comment.$this->depInfo;
		$SearchTxt=strtolower(removeaccents(decode_from_XML($SearchTxt)));
		return $SearchTxt;
	}
	
	function create(){
		if($this->exists())
			return $this->update();
		
		// --- PREPROCESSING ---
		if($this->processorsEnabled()){
			$process = $this->type->preProcess('create',$this);
			if($process->containsError()){
				$this->setError($process->getError());
				return false;
			}else{
				$this->setMsg($process->getResponse());
			}
		}
		
		
		$db_conn = db_connect();
		if($this->ordering)
			$order = $this->ordering;
		else
			$order = $this->getNextOrdering();
		
		// composing SQL
		$SearchTxt = $this->getSearchtext();
		
		$fieldnames = '`DependencyTypeID`,`'.$this->type->getOriginFieldname().'`,`'.$this->type->getTargetFieldname().'`,`'.$this->type->getOrderingFieldname().'`,`Comment`,`DepInfo`,`SearchText`';
		$values = '"'.$this->type->getIDInDatabase().'",\''.$this->originID.'\',\''.$this->targetID.'\',\''.$order.'\',"'.encodeQuote($this->comment).'","'.encodeQuote($this->depInfo).'","'.encodeQuote($SearchTxt).'"';
		
		// if its a bidirectional dep we need to put an ordering for the dep in the other direction too (otherwise it will be the default value=1, and will appear above former deps of the same type)
		$bidir = !$this->type->isUTurn() && $this->type->getReturnType();
		if($bidir){
			
			$fieldnames.=',`'.$this->type->getTargetOrderingFieldname().'`';
			
			// getting the next ordering for the dep in the other direction
			$dep = new Dependency($this->type->getReturnType(),$this->targetID,$this->originID);
			$target_ordering = $dep->getNextOrdering();
			$values.=',\''.$target_ordering.'\'';
		}
		
		//  --- SQL --- 
		$sql = 'INSERT INTO `'.$this->type->getTableName().'`
						('.$fieldnames.') 
				VALUES	('.$values.')';
		sql_log($sql);
		$db_conn->Execute($sql);
		
		// --- POSTPROCESSING ---
		if($this->processorsEnabled()){
			$this->type->postProcess('create',$this);
		}
		
		//  --- ACTION LOGGING --- 
		$action_log_file = new UserActionLogFile();
		$moduleInfo = $this->type->getModuleOrigin();
		$action_object = new UserActionObject($moduleInfo->getName(),$this->originID);
		
		$action_target = new UserActionDependency(UA_OP_APPEND,UA_SRV_DEP,$this->type->getName(),$this->targetID,'Order',$order);
		$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
		$action_log_file->log( $action_log );
		
		if($this->depInfo){
			$action_target = new UserActionDependency(UA_OP_APPEND,UA_SRV_DEP,$this->type->getName(),$this->targetID,'DepInfo',$this->depInfo);
			$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
			$action_log_file->log( $action_log );
		}
		if($this->comment){
			$action_target = new UserActionDependency(UA_OP_APPEND,UA_SRV_DEP,$this->type->getName(),$this->targetID,'Comment',$this->comment);
			$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
			$action_log_file->log( $action_log );
		}
		// also writing a log for the return dep, even if now only one line is written in database
		if($bidir){
			$targetModuleInfo = $this->type->getModuleTarget();
			$returntype =$this->type->getReturnType();
			// object of the log is target of the dependency
			$action_object = new UserActionObject($targetModuleInfo->getName(),$this->targetID);
			$action_target = new UserActionDependency(UA_OP_APPEND,UA_SRV_DEP,$returntype->getName(),$this->originID,'Order',$target_ordering);
			$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
			// logging
			$action_log_file->log( $action_log );
		}
		//  --- END LOGGING ---
		
		if($this->type->isUTurn()){ // we only keep one dep for returning dependency, but for uturns we write two deps
			$dep = new Dependency($this->type->getReturnType(),$this->targetID,$this->originID);
			if(!$dep->exists())
				$dep->create();
		}
		
		return true;
	}
	
	function update(){
		$db_conn = db_connect();
		
		if($this->ordering || $this->depInfo!==false || $this->comment!==false ){
			
			// --- PREPROCESSING ---
			if($this->processorsEnabled()){
				$process = $this->type->preProcess('update',$this);
				
				if($process->containsError()){
					$this->setError($process->getError());
					return false;
				}else{
					$this->setMsg($process->getResponse());
				}
			}
			//  --- ACTION LOGGING --- 
			$action_log_file = new UserActionLogFile();
			$moduleInfo = $this->type->getModuleOrigin();
			$action_object = new UserActionObject($moduleInfo->getName(),$this->originID);
			
			$fields_to_update = array();
			if($this->ordering){
				$fields_to_update[] = '`'.$this->type->getOrderingFieldname().'`=\''.$this->ordering.'\'';
				
				//  --- ACTION LOGGING --- 
				if($this->getFormerOrdering()!=$this->ordering){ // if ordering is different than before
					$action_target = new UserActionDependency(UA_OP_MODIFY,UA_SRV_DEP,$this->type->getName(),$this->targetID,'Order',$this->ordering);
					$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
					$action_log_file->log( $action_log );
				}
			}
			if($this->depInfo!==false || $this->comment!==false){
				if($this->depInfo!==false && $this->depInfo!=$this->getFormerDepInfo()){
					$fields_to_update[] = '`DepInfo`=\''.$this->depInfo.'\'';
				
					//  --- ACTION LOGGING --- 
					$action_target = new UserActionDependency(UA_OP_MODIFY,UA_SRV_DEP,$this->type->getName(),$this->targetID,'DepInfo',$this->depInfo);
					$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
					$action_log_file->log( $action_log );
				}
				if($this->comment!==false && $this->depInfo!=$this->getFormerComment()){
					$fields_to_update[] = '`Comment`=\''.$this->comment.'\'';
				
					//  --- ACTION LOGGING --- 
					$action_target = new UserActionDependency(UA_OP_MODIFY,UA_SRV_DEP,$this->type->getName(),$this->targetID,'Comment',$this->comment);
					$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
					$action_log_file->log( $action_log );
				}
			
				if(!$this->depInfo) // depInfo stays the same
					$this->depInfo = $this->getFormerDepInfo();
				if(!$this->comment) // comment stays the same
					$this->comment = $this->getFormerComment();
				// if depInfo or comment is modified, searchtext should be modified too
				$SearchTxt = $this->getSearchtext();
				$fields_to_update[] = '`SearchText`=\''.$SearchTxt.'\'';
			}
			
			//  --- SQL --- 
			$sql = 'UPDATE `'.$this->type->getTableName().'` SET '.implode(',',$fields_to_update).' WHERE `DependencyTypeID`="'.$this->type->getIDInDatabase().'" AND `'.$this->type->getOriginFieldname().'`=\''.$this->originID.'\' AND `'.$this->type->getTargetFieldname().'`=\''.$this->targetID.'\'';
			$this->console->addMessage($sql);
			$db_conn->Execute($sql);
			
			// --- POSTPROCESSING ---
			if($this->processorsEnabled()){
				$this->type->postProcess('update',$this);
			}
			
			return true;
		}else{
			$this->setError(new SusheeException('Link already exists',SUSHEE_ERROR_ELTEXISTS));
			
			return false;
		}
	}
	
	function delete(){
		if(!$this->exists()){
			$this->setError(new SusheeException('Dependency doesnt exist',SUSHEE_ERROR_ELTNOTFOUND));
			return false;
		}
		
		
		// --- PREPROCESSING ---
		if($this->processorsEnabled()){
			$process = $this->type->preProcess('remove',$this);
			if($process->containsError()){
				$this->setError($process->getError());
				return false;
			}else{
				$this->setMsg($process->getResponse());
			}
		}
		//  --- ACTION LOGGING --- 
		$action_log_file = new UserActionLogFile();
		$moduleInfo = $this->type->getModuleOrigin();
		$action_object = new UserActionObject($moduleInfo->getName(),$this->originID);
		$action_target = new UserActionDependency(UA_OP_REMOVE,UA_SRV_DEP,$this->type->getName(),$this->targetID);
		$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
		$action_log_file->log( $action_log );
		
		//  --- SQL --- 
		$db_conn = db_connect();
		$sql = 'DELETE FROM `'.$this->type->getTableName().'` WHERE `DependencyTypeID`=\''.$this->type->getIDInDatabase().'\' AND `'.$this->type->getOriginFieldname().'`=\''.$this->originID.'\' AND `'.$this->type->getTargetFieldname().'`=\''.$this->targetID.'\' LIMIT 1';
		sql_log($sql);
		$db_conn->Execute($sql);
		
		// --- POSTPROCESSING ---
		if($this->processorsEnabled()){
			$this->type->postProcess('remove',$this);
		}
		// --- RETURN LINK ---
		if($this->type->isUturn()){ // we only keep one dep for returning dependency, but for uturns we write two deps
			$dep = new Dependency($this->type->getReturnType(),$this->targetID,$this->originID);
			if($dep->exists()){
				$dep->delete();
			}
		}
		return true;
	}
	
	function getNextOrdering(){
		$db_conn = db_connect();
		$order_sql = 'SELECT MAX(`'.$this->type->getOrderingFieldname().'`) AS maximum FROM `'.$this->type->getTableName().'` WHERE `'.$this->type->getOriginFieldname().'`=\''.$this->originID.'\' AND `DependencyTypeID`="'.$this->type->getIDInDatabase().'"'; 
		$this->console->addMessage($sql);
		$row = $db_conn->GetRow($order_sql);
		if(!$row["maximum"])
			$order = 1;
		else
			$order = $row['maximum']+1;
		return $order;
	}
	
	function disableProcessors(){
		$this->processors_enabled = false;
	}
	
	function processorsEnabled(){
		return $this->processors_enabled;
	}
}