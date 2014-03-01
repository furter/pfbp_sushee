<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/nectil_element.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/console.class.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/descriptions.inc.php");
require_once(dirname(__FILE__)."/../common/comments.inc.php");

class ModuleElement extends SusheeObject{
	var $values;
	var $ID=false;
	var $moduleID;
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function ModuleElement($moduleID,$values){
		$this->moduleID = $moduleID;
		if(is_array($values)){
			$this->setFields($values);
		}else if(is_numeric($values)){
			$this->setFields(array('ID'=>$values));
		}
		
	}
	
	function setFields($values){
		if($values['ID']){
			$this->ID = $values['ID'];
			unset($values['ID']);
		}
		$this->values = array();
		foreach($values as $key=>$value){
			if(!is_numeric($key))
				$this->values[$key] = $values[$key];
		}
	}
	
	function loadFields($fields_collection=false){
		if($this->getID()){
			$moduleInfo = $this->getModule();
			if($fields_collection && is_object($fields_collection)){
				$what_to_select = $fields_collection->implode(',');
			}else{
				$what_to_select = '*';
			}
			$sql = 'SELECT '.$what_to_select.' FROM `'.$moduleInfo->tableName.'` WHERE `ID`=\''.$this->getID().'\'';
			sql_log($sql);
			$db_conn = db_connect();
			$values = $db_conn->getRow($sql);
			$this->setFields($values);
			return $values;
		}
	}
	
	function setID($ID){
		$this->ID = $ID;
	}
	
	function setField($name,$value){
		if($name=='ID')
			$this->ID = $value;
		else
			$this->values[$name]=$value;
	}
	
	function getField($name){
		if($name=='ID')
			return $this->ID;
		else if(isset($this->values[$name]))
			return $this->values[$name];
		else
			return false;
	}
	
	function getFields(){
		return $this->values;
	}
	
	// allowing NQL name
	function getValue($fieldname){
		$field = $this->getModule()->getField($fieldname);
		if($field){
			$realname = $field->getName();
			return $this->getField($realname);
		}else{
			return false;
		}
	}
	
	function create(){
		//$this->logFunction('create');
		// first stripping non existing and not authorized fields
		$moduleInfo = moduleInfo($this->moduleID);
		foreach($this->values as $key=>$value){
			if($moduleInfo->existField($key)===false){
				unset($this->values[$key]);
			}
		}
		
		$now = $GLOBALS["sushee_today"];
		$this->values["CreationDate"]=$now;
		$this->values["ModificationDate"]=$now;
		if (isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'])){
			$this->values["CreatorID"]=$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
			$this->values["ModifierID"]=$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
		}
		
		$db_conn = db_connect();
		
		$this->values["SearchText"]=$moduleInfo->generateSearchText($this->values);
		
		$field_values = "";
		$first = true;
		foreach($this->values as $field=>$content){
			if(!$first)
				$fields_values.=',';
			if($moduleInfo->isXMLField($field))
				$fields_values.="\"".encodeQuote($content)."\"";
			else
				$fields_values.="\"".encode_for_DB($content)."\""; // decode for xml AND encodeQuote
			$first = false;
		}
		$sql = 'INSERT INTO `'.$moduleInfo->tableName.'` (`'.implode('`,`',array_keys($this->values)).'`) VALUES('.$fields_values.')';
		sql_log($sql);
		$res = $db_conn->Execute($sql);
		if(!$res){
			$this->setError($db_conn->ErrorMsg());
			return false;
		}
		$this->ID = $db_conn->Insert_Id();
		//$this->log('ID is '.$this->ID);
		return true;
	}
	
	function update(){
		if($this->ID){
			$moduleInfo = moduleInfo($this->moduleID);
			if(!isset($this->values["ModificationDate"])){
				$now = $GLOBALS["sushee_today"];
				$this->values["ModificationDate"]=$now;
			}
			
			$user = new Sushee_User();
			if ($user->isAuthenticated()){
				$this->values["ModifierID"] = $user->getID();
			}
			$fields_values = "";
			
			foreach($this->values as $field=>$content){
				if($moduleInfo->isXMLField($field))
					$fields_values.="`".$field."`=\"".encodeQuote($content)."\",";
				else
					$fields_values.="`".$field."`=\"".encode_for_DB($content)."\","; // decode for xml AND encodeQuote
			}
			$fields_values = substr($fields_values,0,-1);
			// generating the condition with the entry Ids to update
			$IDs_condition = ' WHERE ID=\''.$this->ID.'\'';
			$sql = "UPDATE `".$moduleInfo->tableName."` SET $fields_values $IDs_condition";
			sql_log($sql);
			$db_conn = db_connect();
			$db_conn->Execute($sql);
		}
	}
	
	function delete($soft = false){
		require_once(dirname(__FILE__)."/../common/dependencies.inc.php");
		require_once(dirname(__FILE__)."/../common/categories.inc.php");
		require_once(dirname(__FILE__)."/../common/descriptions.inc.php");
		require_once(dirname(__FILE__)."/../common/comments.inc.php");
		$moduleInfo = moduleInfo($this->moduleID);
		$ID = $this->ID;
		// dependencies
		deleteDependenciesTo($moduleInfo->ID,$ID);
		deleteDependenciesFrom($moduleInfo->ID,$ID);
		//categories
		removeFromCategories($moduleInfo->ID,$ID);
		//descriptions
		deleteDescriptions($moduleInfo->ID,$ID);
		// comments
		deleteComments($moduleInfo->ID,$ID);
		if($soft)
			$sql = 'UPDATE `'.$moduleInfo->tableName.'` SET `Activity`=0 WHERE `ID`='.$this->ID.';';
		else
			$sql = 'DELETE FROM `'.$moduleInfo->tableName.'` WHERE `ID`='.$this->ID.';';
		$db_conn = db_connect();
		$db_conn->Execute($sql);
	}
	
	function cleanFields(){
		$this->values = array();
	}
	
	function exists(){
		$moduleInfo = $this->getModule();
		$sql = 'SELECT `ID` FROM `'.$moduleInfo->tableName.'` WHERE `ID`=\''.$this->getID().'\' AND `Activity`=1';
		sql_log($sql);
		$db_conn = db_connect();
		$values = $db_conn->getRow($sql);
		if($values)
			return true;
		return false;
	}
	
	function getID(){
		return $this->ID;
	}
	
	// allows to know whether an element of a native module is part of a specific extension (based on the boolean in database)
	function isPartOfExtension($extension){
		if(!$extension->isExtension()){
			// module asked is not an extension, its a native module, so element is automatically part of it
			return true;
		}
		if($this->getValue($extension->getxSusheeName())){
			return true;
		}
		return false;
	}
	
	function getUniqueID(){
		return $this->getModule()->getxSusheeName().'_'.$this->getID();
	}
}

class sushee_Element extends ModuleElement{
	
	function sushee_Element($moduleID,$values){
		parent::ModuleElement($moduleID,$values);
	}
	
}

class Event  extends ModuleElement{
	function Event($values){
		$moduleInfo = moduleInfo('event');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
	
	function create(){
		$start_date = new Date($this->values['Start']);
		$end_date = new Date($this->values['End']);
		$this->values['StartWeekday']=$start_date->getWeekday();
		$this->values['StartWeekNumber']=$start_date->getWeekNumber();
		$this->values['EndWeekday']=$end_date->getWeekday();
		$this->values['EndWeekNumber']=$end_date->getWeekNumber();
		parent::create();
	}
	
	function createOccurences(/* Date object */$repeatStart_date,/* Date object */ $repeatEnd_date){
		// $this->logFunction('createOccurences');
		// calculating the difference between the start of the event and the end of the event to create the new events with the same period
		$start_date = new Date($this->values['Start']);
		$end_date = new Date($this->values['End']);
		$alarm_date = new Date($this->values['AlarmDate']);
		$diff = $end_date->getDifference($start_date);
		
		$end_date = new Date($repeatStart_date->getDatetime());
		$end_date->addSecond($diff);
		
		$repeatedEvent = new Event($this->values);

		if($this->getField('RepeatMasterID') && $this->getField('RepeatMasterID')>0) // same master
			$repeatedEvent->setField('RepeatMasterID',$this->getField('RepeatMasterID'));
		else // this is the master
			$repeatedEvent->setField('RepeatMasterID',$this->ID);
		switch($this->values['Repeat']){
			case 'yearly':
				while( $repeatStart_date->isLowerOrEqualThan($repeatEnd_date) ){
					$repeatStart_date->addYear(1);
					$end_date->addYear(1);
					$alarm_date->addYear(1);
					if($repeatStart_date->isLowerOrEqualThan($repeatEnd_date)){
						$repeatedEvent->setField('Start',$repeatStart_date->getDatetime());
						$repeatedEvent->setField('End',$end_date->getDatetime());
						$repeatedEvent->create();
					}
				}
				break;
			case 'monthly':
				while( $repeatStart_date->isLowerOrEqualThan($repeatEnd_date) ){
					debug_log('new event');
					$repeatStart_date->addMonth(1);
					$end_date->addMonth(1);
					$alarm_date->addMonth(1);
					if($repeatStart_date->isLowerOrEqualThan($repeatEnd_date)){
						$repeatedEvent->setField('Start',$repeatStart_date->getDatetime());
						$repeatedEvent->setField('End',$end_date->getDatetime());
						$repeatedEvent->setField('AlarmDate',$alarm_date->getDatetime());
						$repeatedEvent->create();
					}
				}
				break;
			case 'weekly':
				while( $repeatStart_date->isLowerOrEqualThan($repeatEnd_date) ){
					$repeatStart_date->addWeek(1);
					$end_date->addWeek(1);
					$alarm_date->addWeek(1);
					if($repeatStart_date->isLowerOrEqualThan($repeatEnd_date)){
						$repeatedEvent->setField('Start',$repeatStart_date->getDatetime());
						$repeatedEvent->setField('End',$end_date->getDatetime());
						$repeatedEvent->setField('AlarmDate',$alarm_date->getDatetime());
						$repeatedEvent->create();
					}
				}
				break;
			case 'same_weekday_in_month':
				$start_weekday = $start_date->getWeekday();
				$start_weekday_pos = $start_date->getWeekdayPosition();
				$end_weekday = $end_date->getWeekday();
				$end_weekday_pos = $end_date->getWeekdayPosition();
				while( $repeatStart_date->isLowerOrEqualThan($repeatEnd_date) ){
					$repeatStart_date->addMonth(1);
					$end_date->addMonth(1);
					$repeatStart_date->moveToXWeekday($start_weekday,$start_weekday_pos);
					$end_date->moveToXWeekday($end_weekday,$end_weekday_pos);
					$alarm_date->moveToXWeekday($end_weekday,$end_weekday_pos);
					if($repeatStart_date->isLowerOrEqualThan($repeatEnd_date)){
						$repeatedEvent->setField('Start',$repeatStart_date->getDatetime());
						$repeatedEvent->setField('End',$end_date->getDatetime());
						$repeatedEvent->setField('AlarmDate',$alarm_date->getDatetime());
						$repeatedEvent->create();
					}
				}
				break;
			case 'last_weekday_in_month':
				$start_weekday = $start_date->getWeekday();
				$end_weekday = $end_date->getWeekday();
				while( $repeatStart_date->isLowerOrEqualThan($repeatEnd_date) ){
					$repeatStart_date->addMonth(1);
					$end_date->addMonth(1);
					$repeatStart_date->moveToLast($start_weekday);
					$end_date->moveToLast($end_weekday);
					$alarm_date->moveToLast($end_weekday);
					if($repeatStart_date->isLowerOrEqualThan($repeatEnd_date)){
						$repeatedEvent->setField('Start',$repeatStart_date->getDatetime());
						$repeatedEvent->setField('End',$end_date->getDatetime());
						$repeatedEvent->setField('AlarmDate',$alarm_date->getDatetime());
						$repeatedEvent->create();
					}
				}
				break;
			case 'daily':
				while( $repeatStart_date->isLowerOrEqualThan($repeatEnd_date) ){
					$repeatStart_date->addDay(1);
					$end_date->addDay(1);
					$alarm_date->addDay(1);
					if($repeatStart_date->isLowerOrEqualThan($repeatEnd_date)){
						$repeatedEvent->setField('Start',$repeatStart_date->getDatetime());
						$repeatedEvent->setField('End',$end_date->getDatetime());
						$repeatedEvent->setField('AlarmDate',$alarm_date->getDatetime());
						$repeatedEvent->create();
						debug_log('repeat on '.$repeatStart_date->getDateTime());
					}else{
						debug_log('NO repeat because '.$repeatStart_date->getDateTime().' > '.$repeatEnd_date->getDateTime());
					}
				}
			break;
		}
	}
}
class Contact extends ModuleElement{
	function Contact($values){
		$moduleInfo = moduleInfo('contact');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
	
	function getLanguage(){
		if(!$this->getField('LanguageID')){
			$this->loadFields();
		}
		return $this->getField('LanguageID');
	}
	
	function getEmail(){
		if(!$this->getField('Email1')){
			$this->loadFields();
		}
		return $this->getField('Email1');
	}
	
	function getKeyring(){
		$db_conn = db_connect();
		$keyring_sql = 'SELECT `OriginID` FROM `dependencies` WHERE `TargetID`=\''.$this->getID().'\' AND `DependencyTypeID`=\'3\'';
		sql_log($keyring_sql);
		$keyring_row = $db_conn->GetRow($keyring_sql);
		if(!$keyring_row){
			return false;
		}
		$keyringID = $keyring_row['OriginID'];
		return new Keyring($keyringID);
	}
}
class Media extends ModuleElement{}
class Group extends ModuleElement{
	function Group($values){
		$moduleInfo = moduleInfo('group');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
}
class Keyring extends ModuleElement{
	function Keyring($values){
		$moduleInfo = moduleInfo('keyring');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
	
	function isSuperAdmin(){
		return ($this->getID()==2);
	}
	
}
class ModuleKey extends ModuleElement{}
class ApplicationKey extends ModuleElement{
	function ApplicationKey($values){
		$moduleInfo = moduleInfo('applicationkey');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
}
class Mailing extends ModuleElement{}
class Resident extends ModuleElement{
	function Resident($values){
		$moduleInfo = moduleInfo('resident');
		parent::ModuleElement($moduleInfo->ID,$values);
	}

	function launchBatches(){
		$db_conn = db_connect();
		$db_name = $this->getField('DbName');
		// --- pre-check to avoid lauching new processes for nothing ---
		$sql = 'SELECT COUNT(ID) AS num FROM `'.$db_name.'`.`batches`  WHERE `WishedStart` <= "'.date('Y-m-d H:i:s').'" AND `Status` IN ("pending","running") AND `Command`!="" AND `Activity`=1;';
		$count = $db_conn->GetRow($sql);
		if($count['num'] != '0' && $count['num'] != ''){
			$url = $this->getField('URL');
			$url.='/'.Sushee_dirname.'/private/launch_batches.php';
			$url_handler = new URL($url);
			return $url_handler->execute();
		}
	}

	function launchCrons(){
		$db_conn = db_connect();
		$db_name = $this->getField('DbName');
		// --- pre-check to avoid lauching new processes for nothing ---
		$sql = 'SELECT COUNT(`ID`) AS num FROM `'.$db_name.'`.`crons` WHERE (`Minute` LIKE "%'.date('i').',%" OR `Minute`="") AND (`Hour` LIKE "%'.date('H').',%" OR `Hour`="") AND (`Day` LIKE "%'.date('d').',%" OR `Day`="") AND (`Month` LIKE "%'.date('m').',%" OR `Month`="")  AND (`Weekday` LIKE "%'.date('N').',%" OR `Weekday`="") AND (`Command`!="" OR `ClassFile` !="") AND `Activity`=1 AND `Status`="pending";';
		$count = $db_conn->GetRow($sql);
		if($count['num'] != '0' && $count['num'] != ''){
			$url = $this->getField('URL');
			$url.='/'.Sushee_dirname.'/private/launch_crons.php';
			$url_handler = new URL($url);
			return $url_handler->execute();
		}
	}

	function getFolder(){
		$denomination = $this->getField('Denomination');
		return new KernelFolder('Residents/'.$denomination.'/');
	}

	function clean(){
		// $this->logFunction('Resident.clean');
		
		$dbName = $this->getField('DbName');
		$folder = $this->getFolder();
		
		if($dbName){
			require_once(dirname(__FILE__)."/../common/db_manip.class.php");
			$resident_db = new Database($dbName);
			// creating a backup first, in the resident directory (which will be compressed, creating a complete archive of the resident)
			if($folder->exists()){
				$backupFile = $folder->createFile('database.sql');
				$resident_db->export($backupFile);
			}
			$resident_db->delete();
		}
		if($GLOBALS['VirtualMinResidents']){
			return; // letting the cron do the job
		}
				
		if($folder->exists()){
			
			$os_folder = $folder->getChild('OS');
			if($os_folder){
				$os_folder->unlink();
			}
			
			$beta_folder = $folder->getChild('Beta');
			if($beta_folder){
				$beta_folder->unlink();
			}
			
			$kernel_folder = $folder->getChild('Kernel');
			if($kernel_folder){
				$kernel_folder->unlink();
			}
			// keeping a zip with the files and libraries and public website
			$folder->compress();
			
			$public_folder = $folder->getChild('Public');
			if($public_folder){
				if($public_folder->isSymlink()){
					$public_folder->unlink();
				}else{
					$public_folder->delete();
				}
			}
			
			$files_folder = $folder->getChild('Files');
			if($files_folder){
				$files_folder->delete();
			}
			
			$library_folder = $folder->getChild('Library');
			if($library_folder){
				if($library_folder->isSymlink()){
					$library_folder->unlink();
				}else{
					$library_folder->delete();
				}
			}
			// finaly deleting the directory
			if(!$kernel_folder->exists() && !$beta_folder->exists() && !$os_folder->exists()){
				$folder->delete();
			}
		}
	}
}

class License extends ModuleElement{
	function License($values){
		$moduleInfo = moduleInfo('license');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
}

class Template extends ModuleElement{
	function Template($values){
		$moduleInfo = moduleInfo('template');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
}

class FieldModuleElement extends ModuleElement{
	function FieldModuleElement($values){
		$moduleInfo = moduleInfo('field');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
}

class LoginModuleElement extends ModuleElement{
	
	var $saved = false;
	
	function LoginModuleElement($values){
		$moduleInfo = moduleInfo('login');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
	
	function isSaved(){
		return isset($GLOBALS['loginSaved']);
	}
	
	function save(){
		$this->setField('LastAction',$GLOBALS['sushee_today']);
		$this->update();
		$GLOBALS['loginSaved'] = true;
	}
}