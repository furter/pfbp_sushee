<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/delete.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__)."/../common/dependencies.class.php");
require_once(dirname(__FILE__)."/../common/descriptions.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/descriptions.inc.php");
require_once(dirname(__FILE__)."/../common/dependencies.inc.php");
require_once(dirname(__FILE__)."/../common/categories.inc.php");
require_once(dirname(__FILE__)."/../common/log.class.php");
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");

class deleteElement extends NQLOperation{
	var $moduleInfo = false;
	var $elementIDs = array();
	var $values = array(); // field values of an element. We only keep the values of one element, because it would take too much ram for multiple elements. When operating multiple elements, these values have to be taken from database each time (in checkElement AND in operateElement)
	var $force = false;
	
	function parse(){
		// $this->logFunction('deleteElement.parse');
		$moduleName  = $this->firstNode->nodeName();
		$moduleInfo = moduleInfo($moduleName);
		if ($moduleInfo->loaded==FALSE){
			$this->setError("The informations about the module $moduleName couldn't be found.");
			return false;
		}
		$force = $this->operationNode->getAttribute('force');
		if($force==='true'){
			$this->force = true;
		}
		
		$this->moduleInfo = $moduleInfo;
		return true;
	}
	
	function operate(){
		// $this->logFunction('deleteElement.operate');
		$db_conn = db_connect();
		$moduleInfo = $this->moduleInfo;
		$xml = $this->firstNode->getDocument();
		$current_path = $this->operationNode->getPath();
		$firstNodePath = $this->firstNode->getPath();
		$firstNode = $this->firstNode->nodeName();
		$requestName = $this->operationNode->nodeName();
		$name = $this->name;
		$IDs_string = $this->firstNode->valueOf("@ID");
		
		if ($IDs_string==FALSE){
			// trying to find a WHERE node : deleting multiple elements matching what is inside the WHERE node
			$where_node = $this->firstNode->getElement("WHERE[1]");
			if( $where_node ){
				
				
				// composing a smaller XML with only a SEARCH command with the content of the WHERE
				$small_xml = new XML(
					'<SEARCH><'.$firstNode.'>'.$where_node->toString().'</'.$firstNode.'><RETURN><NOTHING/></RETURN></SEARCH>');
				// we apply preprocessing to have the extension boolean added
				$preprocess = $moduleInfo->preProcess('SEARCH',false,$small_xml->getElement('/SEARCH'),$former_values,$new_values,$return_values);	
					
				$where_sql = "";
				$where_rs = getResultSet($moduleInfo,$small_xml,'/SEARCH[1]',$where_sql);
				// the SQL request in order to resolve the WHERE failed : we return the error to the user
				if (is_string($where_rs)){
					$this->setError($where_rs);
					return false;
				}
					
				if (!$where_rs){
					$this->setError($db_conn->ErrorMsg().$where_sql);
					return false;
				}
				
				// we have the elements IDs, checking all of them can be deleted (no locked or private elements)
				$first = true;
				while($search_row = $where_rs->FetchRow()){
					$res = $this->checkElement($search_row['ID']);
					if(!$res){
						return false;
					}
					$first = false;
				}
				$where_rs->MoveFirst();
				while($search_row = $where_rs->FetchRow()){
					$this->operateElement($search_row['ID']);
				}
				if ($first==true){
					$this->setError("The search hasn't given any result -> no deletion has been processed.",4);
					return false;
				}
					
			}else{
				$this->setError("No ID was set -> no deletion has been processed.");
				return false;
			}
		}else{
			$IDs_array = explode(",",$IDs_string);
			foreach($IDs_array as $ID){
				$res = $this->checkElement($ID);
				if(!$res){
					return false;
				}
			}
			foreach($IDs_array as $ID){
				$this->operateElement($ID);
			}
		}
		$this->setSuccess('Delete successfully processed.');
		return true;
	}
	
	function saveValues($ID,$values){
		// field values of an element. We only keep the values of one element, because it would take too much ram for multiple elements. When operating multiple elements, these values have to be taken from database each time (in checkElement AND in operateElement)
		$this->values = array();
		$this->values[$ID] = $values;
	}
	
	function getValues($ID){
		if(isset($this->values[$ID])){
			$values = $this->values[$ID];
		}else{
			$values = getInfo($this->moduleInfo,$ID);
		}
		$this->saveValues($ID,$values);
		return $values;
	}
	
	function checkElement($ID){
		// $this->logFunction('checkElement.operateElement '.$ID);
		$db_conn = db_connect();
		$moduleInfo = $this->moduleInfo;
		$xml = $this->firstNode->getDocument();
		$current_path = $this->operationNode->getPath();
		$firstNodePath = $this->firstNode->getPath();
		$firstNode = $this->firstNode->nodeName();
		$requestName = $this->operationNode->nodeName();
		
		$IDs_array[0] = $ID; // for compatibility !!!
		$user = new NectilUser();
		$userID = $user->getID();
		
		$values = $this->getValues($ID);
		
		// ------------------------------------------------------------------------
		// PREPROCESSING
		// ------------------------------------------------------------------------
		
		$nativeModule = $moduleInfo->getParentModule();
		
		$new_values = array();
		$this->preprocess = $preprocess = $moduleInfo->preProcess($requestName,$ID,$this->operationNode,$values,$new_values,$return_values);
		// processors can send errors. If there is an error, we stop the update and return the error message
		if($preprocess->containsError()){
			$error = $preprocess->getError();
			$this->setError($error->getMessage(),$error->getCode());
			return false;
		}

		// ------------------------------------------------------------------------
		// SECURITY CHECKS
		// ------------------------------------------------------------------------
		
		// we forbid the delete of the native contacts of nectil employees
		if( !$this->force && ($moduleInfo->name=='contact' && $userID==$ID && $userID!=1857 && $userID!=3334) ){
			$this->setSecurityError('You can\'t erase your own contact : you wouldn\'t be able to connect to Officity anymore.');
			return false;
		}
			
		// verifying delete is autorized for the user 
		if(!$moduleInfo->getActionSecurity('DELETE',$values)){
			$this->setSecurityError("You're not authorized to delete elements of this module (ID:".$ID.").");
			return false;
		}
		// locked elements are elements that cannot be deleted
		if( !$this->force && $values['IsLocked']==1){
			$this->setSecurityError('This element (ID:'.$ID.') is locked : it cannot been deleted.');
			return false;
		}
		// private element cannot be deleted unless the user is the owner (valid only if user is limited to private elements)
		if($moduleInfo->IsPrivacySensitive){
			if(!$moduleInfo->isElementAuthorized($values,'W')){
				$this->setSecurityError("You're not authorized to delete this element (ID:$ID) (you didn't create it and  it doesn't belong to your team).");
				return false;
			}
		}
		// forbidding to delete element if they still have dependencies (KILL is not limited by this behaviour)
		if ($requestName!=='KILL'){
			$linked = hasTypedLinks($moduleInfo->ID,$ID);
			if ($linked){
				$this->setSecurityError('This element (ID:'.$ID.') is linked to another element. Try to destroy it instead.');
				return false;
			}
		}

		return true;
	}
	
	function operateElement($ID){
		// $this->logFunction('deleteElement.operateElement '.$ID);
		$db_conn = db_connect();
		$moduleInfo = $this->moduleInfo;
		$xml = $this->firstNode->getDocument();
		$current_path = $this->operationNode->getPath();
		$firstNodePath = $this->firstNode->getPath();
		$firstNode = $this->firstNode->nodeName();
		$requestName = $this->operationNode->nodeName();
		$IDs_array[0] = $ID; // for compatibility !!!

		// ------------------------------------------------------------------------
		// RE-GETTING THE VALUES BEFORE REAL DELETE, FOR EVENTUAL POSTPROCESS
		// ------------------------------------------------------------------------

		$values = $former_values = $this->getValues($ID);
		
		// ------------------------------------------------------------
		// SERVICES MANAGEMENT : DEPENDENCIES, CATEGORIES, DESCRIPTIONS
		// ------------------------------------------------------------

		// dependencies
		// dependencies are deleted anyway, even if its an extension, because extension have their own dependencytype
		$othersql.=deleteDependenciesTo($moduleInfo->ID,$ID);
		$othersql.=deleteDependenciesFrom($moduleInfo->ID,$ID);
		
		// other services are deleted only on the native module
		if(!$moduleInfo->isExtension()){
			//categories
			$othersql.=removeFromCategories($moduleInfo->ID,$ID);
			//descriptions
			$othersql.=deleteDescriptions($moduleInfo->ID,$ID);
			// comments
			$othersql.=deleteComments($moduleInfo->ID,$ID);
			// omnilinks
			$destructor = new sushee_omnilinksDestructor($moduleInfo,$ID);
			$destructor->execute();
		}

		// ------------------------------------------------------------------------
		// SQL TREATMENT
		// ------------------------------------------------------------------------

		$sql = "";
		// generating the condition with the entry Ids to disable or delete
		$IDs_condition = " WHERE ";
		$IDs_condition.='`ID` = \''.$ID.'\'';
		// final sql query is :
		if($moduleInfo->getName()=='resident' && $GLOBALS["NectilMasterURL"]!='nectil.com'){
			$real_delete = TRUE;
		}
		if($moduleInfo->getName()=='module' || $moduleInfo->getName()=='field'){
			$real_delete = TRUE;
		}
		if ( $real_delete == TRUE ){
		   $sql = 'DELETE FROM `'.$moduleInfo->getTableName().'` '.$IDs_condition.';';
		}else{
			$user = new NectilUser();
			$userID = $user->getID();
			if($moduleInfo->isExtension()){
				// if delete is on an extension, only disabling the element from the extension and its descending extension
				$to_disable = $moduleInfo->getExtensions();
				$disabling_sql = '`'.$moduleInfo->getName().'` = 0,';
				while($disabled  = $to_disable->next()){
					$disabling_sql.= '`'.$disabled->getName().'` = 0,';
				}
				$sql = 'UPDATE `'.$moduleInfo->getTableName().'` SET '.$disabling_sql.' `ModificationDate` = "'.$GLOBALS['sushee_today'].'",`ModifierID`=\''.$userID.'\' '.$IDs_condition.';';
			}else{
				if($moduleInfo->getName() == 'mail'){
					$sql = 'UPDATE `'.$moduleInfo->getTableName().'` SET `Activity`=0,`ModificationDate`="'.$GLOBALS['sushee_today'].'",`From`="",`PlainText`="",`Subject`="",`To`="",`Cc`="",`Folder`="",`Attachments`="",`SearchText`="" '.$IDs_condition.';';
				}else{
					$sql = 'UPDATE `'.$moduleInfo->getTableName().'` SET `Activity`=0,`ModificationDate`="'.$GLOBALS['sushee_today'].'",`ModifierID`=\''.$userID.'\' '.$IDs_condition.';';
				}
			}
		}
		sql_log($sql);
		$db_conn->Execute($sql);

		// ------------------------------------------------------------------------
		// ACTION LOGGING
		// ------------------------------------------------------------------------

		$action_log_file = new UserActionLogFile();
		$action_object = new UserActionObject($moduleInfo->getName(),$ID);
		$action_log = new UserActionLog($this->getOperation(), $action_object );
		$action_log_file->log( $action_log );

		// ------------------------------------------------------------------------
		// POSTPROCESSING
		// ------------------------------------------------------------------------

		$nativeModule = $moduleInfo->getParentModule();

		$this->postprocess = $moduleInfo->postProcess($requestName,$ID,$this->operationNode,$former_values,$values,$return_values);

		if ($nativeModule && file_exists(dirname(__FILE__)."/../private/".$nativeModule->getName()."_fileprocessing.php") )
			include(dirname(__FILE__)."/../private/".$nativeModule->getName()."_fileprocessing.php");
		else
			include(dirname(__FILE__)."/../private/general_fileprocessing.php");
		$this->elementIDs[]=$ID;
		return true;
	}
	
	function setSuccess($msg){
		$attributes = $this->getOperationAttributes();
		$this->msg = "<MESSAGE".$attributes." msgType=\"0\" hits=\"".sizeof($this->elementIDs)."\" elementID=\"".implode(',',$this->elementIDs)."\">".encode_to_xml($msg).$this->preprocess->getXML().$this->postprocess->getXML()."</MESSAGE>";
	}
}

