<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/create.nql.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__)."/../common/dependencies.class.php");
require_once(dirname(__FILE__)."/../common/descriptions.class.php");
require_once(dirname(__FILE__)."/../common/categories.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/log.class.php");
require_once(dirname(__FILE__)."/../common/omnilinks.class.php");

class createElement extends NQLOperation{
	
	var $moduleInfo = false;
	var $elementID = false;
	
	function getID(){
		return $this->elementID;
	}
	
	function parse(){
		$moduleName  = $this->firstNode->nodeName();
		$moduleInfo = moduleInfo($moduleName);
		if ($moduleInfo->loaded == FALSE)
		{
			$this->setError("The informations about the module $moduleName couldn't be found.");
			return false;
		}
		$this->moduleInfo = $moduleInfo;
		return true;
	}
	
	function operate(){
		$db_conn = db_connect();
		$moduleInfo = $this->moduleInfo;
		$xml = $this->firstNode->getDocument();
		$current_path = $this->operationNode->getPath();
		$firstNodePath = $this->firstNode->getPath();
		$firstNode = $this->firstNode->nodeName();
		$requestName = 'CREATE'; //$this->operationNode->nodeName();
		$number = 1; // number of elements to create
		$name = $this->name;

		// ------------------------------------------------------------------------
		// INFO FIELDS PARSING
		// ------------------------------------------------------------------------
		// automatically generate a query from the nodenames and their content
		
		$values = array();
		$return_values = array();

		if($this->firstNode->getAttribute('ID')){
			$values['ID'] = $this->firstNode->getAttribute('ID');
		}

		// checking the nodes have a corresponding field and prepare the node values in an array
		$INFO_node_contents = $this->firstNode->getElements("/INFO/*");
		foreach($INFO_node_contents as $node){
			$nodeName = $node->nodeName();
			$field = $moduleInfo->getField($nodeName);

			if($field){
				$fieldName = $field->getName();
				$content = $field->encodeForSQL($node);
				$operator = $node->getxSusheeOperator();
				switch($operator){
					case 'uppercase':
						$content = strtoupper($content);
						$return_values[$fieldName] = $content;
						break;
					case 'lowercase':
						$content = strtolower($content);
						$return_values[$fieldName] = $content;
						break;
					case 'capitalize':
						$content = ucfirst(strtolower($content));
						$return_values[$fieldName] = $content;
						break;
					case 'encrypt':
						$content = mysql_password($content);
						$return_values[$fieldName] = $content;
						break;
					case 'md5':
					case 'MD5':
						$content = md5($content);
						$return_values[$fieldName] = $content;
						break;
					default:
				}
				$values[$fieldName] = $content;
			}
		}

		// ------------------------------------------------------------------------
		// SECURITY CHECKS
		// ------------------------------------------------------------------------
		// checking the nodes have writing security, or remove it from the query
		$keep_values_complete = $values;
		foreach($values as $fieldName=>$content){
			if($moduleInfo->getFieldSecurity($fieldName,$keep_values_complete)!=='W'){
				unset($values[$fieldName]);
			}
		}
		unset($keep_values_complete);
		
		// we check the creation authorization only at this point because we must know the values to forbid the creation of element of a certain mediatype
		if(!$moduleInfo->getActionSecurity("CREATE",$values)){
			$this->setSecurityError("You're not authorized to create elements in this module.");
			return false;
		}

		$now = $GLOBALS["sushee_today"];
		$values["CreationDate"]=$now;
		$values["ModificationDate"]=$now;
		$former_values = array(); // empty array
		
		if($moduleInfo->isAdvancedSecurityEnabled()){
			if(!isset($values['Groups'])){ // to force init of this field, because empty text fields on windows can make the SQL query fail
				$values['Groups'] = '';
			}
			if(!isset($values['Owners'])){ // to force init of this field, because empty text fields on windows can make the SQL query fail
				$values['Owners'] = '';
			}
		}

		$user = new NectilUser();
		$userID = $user->getID();
		// user is authenticated, identified, so we can use his ID as the creatorID
		if($userID){
			if(!(isset($values["CreatorID"])))
				$values["CreatorID"]=$userID;
			if(!(isset($values["ModifierID"]) && $GLOBALS['php_request']==true))
				$values["ModifierID"]=$userID;
				
			if($moduleInfo->name!=='group' && !isset($values["OwnerID"]))
				$values["OwnerID"]=$userID;
		}

		// ------------------------------------------------------------------------
		// PREPROCESSING
		// ------------------------------------------------------------------------

		$preprocess = $moduleInfo->preProcess($this->getOperation(),false,$this->operationNode,$former_values,$values,$return_values);

		// processors can send error. If there is an error, we stop the creation and return the error message
		if($preprocess->containsError())
		{
			$error = $preprocess->getError();
			$this->setError($error->getMessage(),$error->getCode());
			return false;
		}

		// and now we can generate the searchText
		$values["SearchText"]= $moduleInfo->generateSearchText($values,$values['ID'],$this->operationNode);

		// ------------------------------------------------------------------------
		// SQL INSERTION
		// ------------------------------------------------------------------------
		$field_values = "";
		$first = true;
		
		// not authorizing empty ID
		if(isset($values['ID']) && $values['ID']=='')
		{
			unset($values['ID']);
		}
		
		// privateModule means every user has his own table for this module (suffixed by its ID)
		if($moduleInfo->privateModule == true && !$values['ID'])
		{
			$values['ID'] = $moduleInfo->getNextID();
		}

		foreach($values as $field=>$content)
		{
			if(!$first)
			{
				$fields_values.=',';
			}
			$fields_values.="\"".encodeQuote($content)."\"";
			$first = false;
		}

		$fields_comma_list = implode('`,`',array_keys($values));
		if(isset($values['ID']))
		{
			$sql = 'REPLACE INTO `'.$moduleInfo->tableName.'` (`'.$fields_comma_list.'`) VALUES('.$fields_values.')';
		}
		else
		{
			$sql = 'INSERT INTO `'.$moduleInfo->tableName.'` (`'.$fields_comma_list.'`) VALUES('.$fields_values.')';
		}

		$IDs_string="";
		$IDs_array = array();

		sql_log($sql);
		
		$success = $db_conn->Execute($sql);
		if ( !$success )
		{
			$this->setError("Creation failed : ".$db_conn->ErrorMsg());
			$this->logError($sql);
			return false;
		}

		$ID = $db_conn->Insert_Id();
		$IDs_string.=$ID;
		$IDs_array[]=$ID;
		$this->elementID = $ID;

		// ------------------------------------------------------------------------
		// ACTION LOGGING
		// ------------------------------------------------------------------------

		$action_log_file = new UserActionLogFile();
		$action_object = new UserActionObject($moduleInfo->getName(),$ID);
		$user_action_filter = array('SearchText','ModificationDate','ModifierID','CreatorID','CreationDate'); // fields we dont want to log
		foreach($values as $field=>$content)
		{
			if($content && !in_array($field,$user_action_filter))
			{
				$action_target = new UserActionTarget(UA_OP_MODIFY,UA_SRV_INFO,$field,$content);
				$action_log = new UserActionLog($this->getOperation(), $action_object , $action_target );
				$action_log_file->log( $action_log );
			}
		}

		// ------------------------------------------------------------
		// SERVICES MANAGEMENT : DEPENDENCIES, CATEGORIES, DESCRIPTIONS
		// ------------------------------------------------------------
		
		$factory = new DependenciesFactory($moduleInfo->ID,$this->firstNode,$ID,$values);
		$factory->execute();
		
		$categFactory = new CategoriesFactory($moduleInfo->ID,$this->firstNode,$ID,$values);
		$categFactory->execute();
		
		$descFactory = new DescriptionsFactory($moduleInfo->ID,$this->firstNode,$ID,$values);
		$descFactory->execute();
		
		$omnilinksFactory = new sushee_OmnilinksFactory($moduleInfo->ID,$this->firstNode,$ID,$values);
		$omnilinksFactory->setElementID($ID);
		$omnilinksFactory->execute();

		// ------------------------------------------------------------------------
		// POSTPROCESSING
		// ------------------------------------------------------------------------
		$suppl_info = '';

		foreach($IDs_array as $ID)
		{
			$postprocess = $moduleInfo->postProcess($this->getOperation(),$ID,$this->operationNode,$former_values,$values,$return_values);
		}

		$nativeModule = $moduleInfo->getParentModule();
		if ( file_exists(dirname(__FILE__)."/../private/".$nativeModule->name."_fileprocessing.php") )
		{
			include(dirname(__FILE__)."/../private/".$nativeModule->name."_fileprocessing.php");
		}
		else
		{
			include(dirname(__FILE__)."/../private/general_fileprocessing.php");
		}

		// ------------------------------------------------------------------------
		// RESPONSE COMPOSITION
		// ------------------------------------------------------------------------

		$msg_content = '<'.$firstNode.' ID="'.$IDs_string.'"><INFO>';
		$msg_content.='<CREATIONDATE>'.$values["CreationDate"].'</CREATIONDATE><MODIFICATIONDATE>'.$values["ModificationDate"].'</MODIFICATIONDATE>';
		if(is_array($return_values)){
			foreach($return_values as $key=>$val){
				$n = strtoupper($key);
				$msg_content.='<'.$n.'>'.encode_to_xml($val).'</'.$n.'>';
			}
		}
		$msg_content.='</INFO>'.$descFactory->getXML().$categFactory->getXML().$factory->getXML().$omnilinksFactory->getXML().$preprocess->getXML().$postprocess->getXML().'</'.$firstNode.'>';
		$this->msg = generateMsgXML(0,$msg_content,0,$IDs_string,$name,$values["CreationDate"],$values["ModificationDate"],$suppl_info);
		return true;
	}
}