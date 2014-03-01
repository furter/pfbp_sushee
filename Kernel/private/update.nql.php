<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/update.nql.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/dependencies.class.php");
require_once(dirname(__FILE__)."/../common/descriptions.class.php");
require_once(dirname(__FILE__)."/../common/categories.class.php");
require_once(dirname(__FILE__)."/../common/omnilinks.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/log.class.php');
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");

class UpdateFieldOperator extends SusheeObject{
	
	var $operator;
	var $fieldName;
	
	function setOperator($operator){
		$this->operator = $operator;
	}
	
	function getOperator(){
		return $this->operator;
	}
	
	function setFieldname($fieldName){
		$this->fieldName = $fieldName;
	}
	
	function getFieldname(){
		return $this->fieldName;
	}
}

class UpdateFieldOperatorSet extends Vector{
	function UpdateFieldOperatorSet(){
		parent::Vector();
	}
	
	function add($fieldOperator){
		parent::add($fieldOperator->getFieldname(),$fieldOperator);
	}
}

class updateElement extends NQLOperation{
	var $moduleInfo = false;
	var $elementID = false;
	var $dependencies_updates = false;
	var $descriptions_updates = false;
	var $categories_updates = false;
	
	function parse(){
		if(!$this->firstNode){
			$this->setError('No element to update in query `'.$this->operationNode->toString().'`');
			return false;
		}
		$moduleName  = $this->firstNode->nodeName();
		$moduleInfo = moduleInfo($moduleName);
		if ($moduleInfo->loaded==FALSE){
			$this->setError("The informations about the module `$moduleName` couldn't be found.");
			return false;
		}
		$this->moduleInfo = $moduleInfo;
		return true;
	}
	
	function checkServicesUpdate()
	{
		$xml = $this->firstNode->getDocument();
		$moduleInfo = $this->moduleInfo;
		$firstNodePath = $this->firstNode->getPath();
		
		if($this->firstNode->getElement('DEPENDENCIES/DEPENDENCY')){
			$this->dependencies_updates = true;
			// Services Helper class
			$this->depFactory = new DependenciesFactory($moduleInfo->ID,$xml->getElement($firstNodePath),$ID,$values);
		}
		
		if($this->firstNode->getElement('DESCRIPTIONS/DESCRIPTION')){
			$this->descriptions_updates = true;
			// Services Helper class
			$this->descFactory = new DescriptionsFactory($moduleInfo->ID,$xml->getElement($firstNodePath),$ID,$values);
		}

		if($this->firstNode->getElement('CATEGORIES')){
			$this->categories_updates = true;
			// Services Helper class
			$this->categFactory = new CategoriesFactory($moduleInfo->ID,$this->firstNode,$ID,$values);
		}
		
		if($this->firstNode->getElement('OMNILINKS')){
			$this->omnilinks_updates = true;
			// Services Helper class
			$this->omnilinksFactory = new sushee_OmnilinksFactory($moduleInfo->ID,$this->firstNode,$ID,$values);
		}
	}
	
	function operate(){
		//------------------------------------------------------------------------------
		// PREPARING GENERAL USEFUL VARIABLES
		//------------------------------------------------------------------------------
		$db_conn = db_connect();
		// Modules
		$moduleInfo = $this->moduleInfo;
		$nativeModule = $moduleInfo->getParentModule(); // the module extended, we need it in order to apply its processors
		// XML nodes and path
		$xml = $this->firstNode->getDocument();
		$current_path = $this->operationNode->getPath();
		$firstNodePath = $this->firstNode->getPath();
		$firstNode = $this->firstNode->nodeName();
		$requestName = 'UPDATE';
		$name = $this->name;
		// User
		$user = new NectilUser();
		$userID = $user->getID();
		// Logging
		$action_log_file = new UserActionLogFile();
		$user_action_filter = array('SearchText','ModificationDate','ModifierID','CreatorID','CreationDate'); // fields we dont want to log
		
		//------------------------------------------------------------------------------
		//UPDATING MULTIPLE ELEMENT STARTING FROM A NQL SEARCH : COLLECTING THE IDs OF THE ELEMENTS TO UPDATE
		//------------------------------------------------------------------------------
		$IDs_string = $this->firstNode->valueOf("@ID");
		
		if ($IDs_string==FALSE){ // only if no ID is indicated
			// trying to find a WHERE node : updating multiple elements matching what is inside the WHERE node
			if( $xml->match($firstNodePath."/WHERE[1]") ){
				
				// composing a smaller XML with only a SEARCH command with the content of the WHERE
				$where_node = $xml->getElement($firstNodePath."/WHERE[1]");
				$small_xml = new XML(
					'<SEARCH><'.$firstNode.'>'.$where_node->toString().'</'.$firstNode.'><RETURN><NOTHING/></RETURN></SEARCH>');
				// we apply preprocessing to have the extension boolean added
				$moduleInfo->preProcess('SEARCH',false,$small_xml->getElement('/SEARCH'),$former_values,$new_values,$return_values);
				
				$where_sql = "";
				$where_rs = getResultSet($moduleInfo,$small_xml,'/SEARCH[1]',$where_sql);
				
				// the SQL request in order to resolve the WHERE failed : we return the error to the user
				if (is_string($where_rs)){
					$this->msg = $where_rs;
					return false;
				}
				if (!$where_rs){
					$this->setError($db_conn->ErrorMsg().$where_sql);
					$this->logError($db_conn->ErrorMsg());
					return false;
				}
				
				// we have the elements IDs, saving them
				$first = true;
				while($search_row = $where_rs->FetchRow()){
					$first = false;
					$IDs_array[] = $search_row['ID'];
				}
				if ($first==true){
					$this->setError("The search hasn't given any result -> no update has been processed.",4);
					return false;
				}
					
			}else if($moduleInfo->name=='contact' && $viewing_code = $this->firstNode->valueOf('/@viewing_code')){
				// no ID is indicated, only a viewing-code corresponding to a CONTACT having received a MAILING
				$mailingID = false;
				if(strlen($viewing_code)>32){
					$mailingID = substr($viewing_code,33);
					$viewing_code = substr($viewing_code,0,32);
				}
				$recip_sql = 'SELECT `ContactID` FROM `mailing_recipients` WHERE `Status`="sent" AND `ViewingCode`="'.$viewing_code.'"'.(($mailingID!==false)?' AND `MailingID`=\''.$mailingID.'\' ':'');
				$recipient = $db_conn->GetRow($recip_sql);
				if($recipient)
					$IDs_array[] = $recipient['ContactID'];
				else{
					// no recipient with this viewing code was found
					$this->setError("The viewing_code is not valid -> no update has been processed.");
					return false;
				}
					
			}else{
				// not possible to determine what element to update
				$this->setError("No ID was set -> no update has been processed.");
				return false;
			}
		}else if($IDs_string =='visitor'){
			require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
			$user = new NectilUser();
			$IDs_array[] = $user->getID();
		}else{
			$IDs_array = explode(",",$IDs_string);
			foreach($IDs_array as $ID){
				if(!is_numeric($ID)){
					$this->setError("One of the ID (".$ID.") is not numeric.");
					return false;
				}
			}
		}
		//------------------------------------------------------------------------------
		// PARSING THE DATAS
		//------------------------------------------------------------------------------
		$values = array();
		$return_values = array();
		$now = $GLOBALS["sushee_today"];
		$modification_date = $this->firstNode->valueOf("/INFO[1]/MODIFICATIONDATE[1]"); // to check if someone did not modify the element after last loading (the check is operated below in the loop where we update every element)
		$INFO_node_contents = $xml->getElements($current_path."/*[1]/INFO/*");
		// generating an array with all the values
		// checking the nodes have a corresponding field and prepare the node values in an array
		foreach($INFO_node_contents as $node){
			$nodeName = $node->nodeName();
			$field = $moduleInfo->getField($nodeName);
			if($field){ // field was recognized
				$values[$field->getName()] = $field->encodeForSQL($node);
			}
		}
		$values_before_security_check = $values;
		foreach($values as $fieldName=>$content){
			if($moduleInfo->getFieldSecurity($fieldName,$values_before_security_check)!=='W' && !($moduleInfo->name=='contact' && sizeof($IDs_array)==1 && $IDs_array[0]==$userID && $fieldName!=='AdminNotes')){
				unset($values[$fieldName]);
			}
		}
		// handling operators, used to work on field without knowing former value (append, increment, decrement)
		$operations_array = $xml->match($current_path."/*[1]/INFO/*[@operator or @op]");
		$fieldOperatorSet = new UpdateFieldOperatorSet();
		foreach($operations_array as $operation_path){
			$operator = $xml->getxSusheeOperator($operation_path);
			$nodeName = $xml->nodeName($operation_path);
			$fieldName = $moduleInfo->getFieldName($nodeName);
			if($fieldName && $operator && isset($values[$fieldName])){ // values could have been removed from datas because of security
				$fieldOperator = &new UpdateFieldOperator();
				$fieldOperator->setOperator($operator);
				$fieldOperator->setFieldname($fieldName);
				$fieldOperatorSet->add($fieldOperator);
			}
		}
		
		//------------------------------------------------------------------------------
		// WATCHING WHAT IS UPDATE (ONLY INFO, SOME SERVICES ?)
		//------------------------------------------------------------------------------
		$this->checkServicesUpdate();
		// keeping a version of the data as given by the user (values will be used as the working array)
		$values_as_given_in_xml = $values;
		
		
		
		//------------------------------------------------------------------------------
		// CHECKING WE ARE AUTHORIZED TO MODIFY THE ELEMENTS WITH THESE VALUES
		//------------------------------------------------------------------------------
		if(!$moduleInfo->getActionSecurity($this->getOperation(),$values) && !($moduleInfo->name=='contact' && sizeof($IDs_array)==1 && $IDs_array[0]==$userID)){
			$this->setSecurityError("You're not authorized to update elements in this module.");
			return false;
		}
		//------------------------------------------------------------------------------
		// APPLYING THE UPDATE, ELEMENT AFTER ELEMENT
		//------------------------------------------------------------------------------
		$if_exist = false;
		// checking there is no if_exist attribute
		$if_exist=$this->firstNode->valueOf("/@if_exist");
		if(!$if_exist)
			$if_exist=$this->firstNode->valueOf("@if-exist"); // three way to write if-exist are accepted
		if(!$if_exist)
			$if_exist=$this->firstNode->valueOf("@if-exists");
		// now we have our IDs array with all the IDs of the elements to update, we apply the update to everyone of them
		foreach($IDs_array as $ID){
			//------------------------------------------------------------------------------
			// GETTING FORMER VALUES IN ORDER TO GENERATE THE SEARCHTEXT AND TO APPLY THE EVENTUAL OPERATORS (append,increment,decrement)
			//------------------------------------------------------------------------------
			
			$former_values = getInfo($moduleInfo,$ID);
			if(!$former_values){
				$this->setError('Element with ID '.$ID.' doesn\'t exist');
				return false;
			}
			//------------------------------------------------------------------------------
			// DO NOT ALLOW UPDATE IF MODIFICATIONDATE EXISTS AND IS SMALLER
			//------------------------------------------------------------------------------
			if ($modification_date && $former_values["ModificationDate"]!=$modification_date){
				$moduleContactInfo = moduleInfo('contact');
				$modifier = getInfo($moduleContactInfo,$former_values['ModifierID']);
				if($modifier["FirstName"] || $modifier["LastName"])
					$modifierStr = $modifier["FirstName"]." ".$modifier["LastName"];
				else
					$modifierStr = $modifier["Denomination"];
				$this->setError(ucfirst($moduleInfo->name)." was modified by `".$modifierStr."` since you loaded it. Please reload it before saving again.",3);
				return false;
			}
		
			
			// ------------------------------------------------------------------------
			// PREPROCESSING
			// ------------------------------------------------------------------------
			// preprocessing of the native module if extended module (well actually its above all for contacts, to have the multi email check on the extension of contact)
			// this is the old system of processors
			$nativeModule = $moduleInfo->getParentModule();

			// operations on field (not simply set the field)
			$fieldOperatorSet->reset();
			while($fieldOperator = $fieldOperatorSet->next()){
				$operator = $fieldOperator->getOperator();
				$fieldName = $fieldOperator->getFieldname();
				
				switch($operator){
					case 'append':
						$values[$fieldName]=$former_values[$fieldName].$values[$fieldName];
						break;
					case '+':
						$values[$fieldName]=$former_values[$fieldName]+$values[$fieldName];
						break;
					case '-':
						$values[$fieldName]=$former_values[$fieldName]-$values[$fieldName];
						break;
					case '*':
						$values[$fieldName]=$former_values[$fieldName]*$values[$fieldName];
						break;
					case '/':
						if($values[$fieldName]!=0)
						$values[$fieldName]=$former_values[$fieldName]/$values[$fieldName];
						break;
					case '++':
						$values[$fieldName]=$former_values[$fieldName]+1;
						break;
					case '--':
						$values[$fieldName]=$former_values[$fieldName]-1;
						break;
					case 'encrypt':
						$values[$fieldName]=mysql_password($values[$fieldName]);
						break;
					case 'uppercase':
						if($values[$fieldName]){
							$values[$fieldName]=strtoupper($values[$fieldName]);
						}else{
							$values[$fieldName]=strtoupper($former_values[$fieldName]);
						}
						break;
					case 'lowercase':
						if($values[$fieldName]){
							$values[$fieldName]=strtolower($values[$fieldName]);
						}else{
							$values[$fieldName]=strtolower($former_values[$fieldName]);
						}
						break;
					case 'capitalize':
						if($values[$fieldName]){
							$values[$fieldName]=ucfirst(strtolower($values[$fieldName]));
						}else{
							$values[$fieldName]=ucfirst(strtolower($former_values[$fieldName]));
						}
						break;
					default:
				}
				$return_values[$fieldName] = $values[$fieldName];
			}
			// new system of processors
			
			$preprocess = $moduleInfo->preProcess('UPDATE',$ID,$this->operationNode,$former_values,$values,$return_values);
			// processors can send errors. If there is an error, we stop the update and return the error message
			if($preprocess->containsError()){
				$error = $preprocess->getError();
				$this->setError($error->getMessage(),$error->getCode());
				return false;
			}
			// processors could modify the request. If the request is modified, we have to re-evaluate which services have to be handled
			if($this->operationNode->xml->isModified){
				$this->checkServicesUpdate();
			}
			// the attribute if-exists can be used to determine a behaviour when an element already exists. If it's "fill", it will remove the fields which are already filled
			if($if_exist==='fill'){
				foreach($values as $field=>$content){
					if(isset($former_values[$field]) && $former_values[$field]!='')
						unset($values[$field]);
				}
			}
			
			
			if($moduleInfo->IsPrivacySensitive){
				if(!$moduleInfo->isElementAuthorized($former_values,'W')){
					$this->setSecurityError("You're not authorized to update this element (ID:".$ID.") : you are not one of its owners and it doesn't belong to your team.");
					return false;
				}
					
			}
			
			$new_values = array_merge($former_values,$values);
			
			$values['SearchText'] = $moduleInfo->generateSearchText($new_values,$ID,$this->operationNode);
			
			// ------------------------------------------------------------------------
			// SQL TREATMENT
			// ------------------------------------------------------------------------
			$fields_values = '';
			foreach($values as $field=>$content){
				if($moduleInfo->isXMLField($field))
					$fields_values.="`".$field."`=\"".encodeQuote($content)."\",";
				else
					$fields_values.="`".$field."`=\"".encode_for_DB($content)."\","; // decode for xml AND encodeQuote
			}
			// always adding a modificationDate because even if no field were updated, maybe dependencies were changed, etc
			$fields_values.= "`ModificationDate`=\"$now\"";
			if($userID){
				$return_values['ModifierID'] = $userID; // mentionning the modifier in the response
				$fields_values.= ',`ModifierID`="'.$userID.'"';
			}
			
			// generating the condition with the entry Ids to update
			$IDs_condition = ' WHERE `ID` = \''.$ID.'\'';

			// final sql query is :
			$sql = "UPDATE `".$moduleInfo->tableName."` SET $fields_values $IDs_condition;";
			sql_log($sql);
			$success = $db_conn->Execute($sql);

			if ( !$success ){
				$this->setError("Modification failed.*$sql*");
				$this->logError($db_conn->ErrorMsg());
				return false;
			}

			// ------------------------------------------------------------------------
			// ACTION LOGGING
			// ------------------------------------------------------------------------

			$action_object = new UserActionObject($moduleInfo->getName(),$ID);
			foreach($values as $field=>$content){
				if($former_values[$field]!=$content  && !in_array($field,$user_action_filter)){
					$action_target = new UserActionTarget(UA_OP_MODIFY,UA_SRV_INFO,$field,$content);
					$action_log = new UserActionLog($this->getOperation(), $action_object , $action_target );
					$action_log_file->log( $action_log );
				}
			}

			// ------------------------------------------------------------
			// SERVICES MANAGEMENT : DEPENDENCIES, CATEGORIES, DESCRIPTIONS
			// ------------------------------------------------------------

			if($this->dependencies_updates){
				$this->depFactory->setElementID($ID);
				$this->depFactory->execute();
			}

			if($this->categories_updates){
				$this->categFactory->setElementID($ID);
				$this->categFactory->execute();
			}

			if($this->descriptions_updates){
				$this->descFactory->setElementID($ID);
				$this->descFactory->execute();
			}

			if($this->omnilinks_updates){
				$this->omnilinksFactory->setElementID($ID);
				$this->omnilinksFactory->execute();
			}

			// ------------------------------------------------------------------------
			// POSTPROCESSING
			// ------------------------------------------------------------------------

			$postprocess = $moduleInfo->postProcess($this->getOperation(),$ID,$this->operationNode,$former_values,$values,$return_values);

			// processors can send errors. If there is an error, we stop the update and return the error message
			if($postprocess->containsError()){
				$error = $postprocess->getError();
				$this->setError($error->getMessage(),$error->getCode());
				return false;
			}
			
			// resetting to data given by user for next element
			$values = $values_as_given_in_xml;
		}
		
		// ------------------------------------------------------------------------
		// RESPONSE COMPOSITION
		// ------------------------------------------------------------------------
		
		$IDs_string = implode(',',$IDs_array);
		$msg_content = '<'.$firstNode.' ID="'.$IDs_string.'"><INFO>';
		$msg_content.='<CREATIONDATE>'.$former_values["CreationDate"].'</CREATIONDATE><MODIFICATIONDATE>'.$now.'</MODIFICATIONDATE>';
		if(is_array($return_values)){
			foreach($return_values as $key=>$val){
				$n = strtoupper($key);
				$msg_content.='<'.$n.'>'.encode_to_xml($val).'</'.$n.'>';
			}
		}
		$msg_content.='</INFO>';
		if($this->descriptions_updates){
			$msg_content.=$this->descFactory->getXML();
		}
		if($this->categories_updates){
			$msg_content.=$this->categFactory->getXML();
		}
		if($this->dependencies_updates){
			$msg_content.=$this->depFactory->getXML();
		}
		if($this->omnilinks_updates){
			$msg_content.=$this->omnilinksFactory->getXML();
		}
		if($preprocess){
			$msg_content.=$preprocess->getXML();
		}
		if($postprocess){
			$msg_content.=$postprocess->getXML();
		}
		$msg_content.='</'.$firstNode.'>';
		$this->msg = generateMsgXML(0,$msg_content,0,$IDs_string,$name,$former_values["CreationDate"],$now,'hits="'.sizeof($IDs_array).'"');

		return true;
	}
}