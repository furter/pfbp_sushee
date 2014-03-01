<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/processor.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/susheesession.class.php");

class ModuleProcessingData extends SusheeObject{
	
	var $former_values;
	var $new_values;
	var $return_values;
	var $elementID;
	var $node;
	var $moduleID;
	
	function ModuleProcessingData(){
		$this->former_values = array();
		$this->new_values = array();
		$this->return_values = array();
		$this->elementID = false;
		$this->node = false;
	}
	
	function setModule($moduleInfo){
		$this->moduleID = $moduleInfo->getID();
	}
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function setNode(&$node){
		$this->node = $node;
	}
	
	function &getNode(){
		return $this->node;
	}
	
	function &getElementNode(){
		$node = $this->getNode();
		if($node){
			return $node->getFirstChild();
		}
	}
	
	function setElementID($ID){
		$this->elementID = $ID;
	}
	
	function getElementID(){
		return $this->elementID;
	}
	
	function getID(){
		return $this->getElementID();
	}
	
	function setFormerValues($values){
		$this->former_values = $values;
	}
	
	function getFormerValues(){
		return $this->former_values;
	}
	
	function setNewValues($values){
		$this->new_values = $values;
	}
	
	function setNewValue($fieldname,$value){
		$field = $this->getModule()->getField($fieldname);
		if($field){
			$realname = $field->getName();
			$this->new_values[$realname]=$value;
		}else{
			return false;
		}
	}
	
	function setValue($fieldname,$value){ // alias of setNewValue
		$this->setNewValue($fieldname,$value);
	}
	
	function getValue($fieldname){
		$field = $this->getModule()->getField($fieldname);
		if($field){
			$realname = $field->getName();
			
			// for the postprocessor on a delete command
			// we dont have the fields, we take them in the database
			if(!$this->new_values && !$this->former_values){
				$elt = new ModuleElement($this->getModule()->getID(),$this->getID());
				$this->former_values = $elt->loadFields();
			}
			
			if(isset($this->new_values[$realname])){
				return $this->new_values[$realname];
			}else{
				return $this->former_values[$realname];
			}
		}else{
			return false;
		}
	}
	
	function getNewValue($fieldname){
		$field = $this->getModule()->getField($fieldname);
		if($field){
			$realname = $field->getName();
			if(isset($this->new_values[$realname])){
				return $this->new_values[$realname];
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	function getFormerValue($fieldname){
		$field = $this->getModule()->getField($fieldname);
		if($field){
			$realname = $field->getName();
			return $this->former_values[$realname];
		}else{
			return false;
		}
	}
	
	function getValues(){
		return array_merge($this->getFormerValues(),$this->getNewValues());
	}
	
	function getNewValues(){
		return $this->new_values;
	}
	
	function setNoticeableValues($values){
		$this->return_values = $values;
	}
	
	function getNoticeableValues(){
		return $this->return_values;
	}
}

class ModuleProcessingQueue extends SusheeObject{
	
	var $command = false;
	var $type = false; // post or pre
	var $data;
	var $moduleID;
	var $loaded = false;
	var $processors = false;
	var $error = false;
	var $moduleInfoProc_excludes = array();
	
	function ModuleProcessingQueue(){
		
	}
	
	function setData(&$data){
		$this->data = $data;
	}
	
	function &getData(){
		return $this->data;
	}
	
	function setModule($moduleInfo){
		$this->moduleID = $moduleInfo->getID();
	}
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function setCommand($command){
		$this->command = $command;
	}
	
	function getCommand(){
		return $this->command;
	}
	
	function setType($type){
		$this->type = $type;
	}
	
	function getType(){
		return $this->type;
	}
	
	function getTypeFilePostfix(){
		if($this->getType()==SUSHEE_PREPROCESSOR){
			return 'preprocessing';
		}else if($this->getType()==SUSHEE_POSTPROCESSOR){
			return 'postprocessing';
		}
		return false;
	}
	
	function add($processID,$process){
		$this->processors->add($processID,$process);
	}
	
	function load(){
		if(!$this->loaded){
			$db_conn = db_connect();
			$native = $this->getModule()->getParentModule();
			$type = $this->getType();
			$cmd = $this->getCommand();
			
			$this->processors = new Vector();

			//------------------------------------
			// processor that adds the extension's boolean in the request (UPDATE, SEARCH)
			// ----> !! MUST BE IN THE FIRST PLACE TO SET THE RIGHT MODULE PROPS !! <----
			//------------------------------------
			if($this->getModule()->isExtension() && $type==SUSHEE_PREPROCESSOR){
				$extension_modifier = &new sushee_ModuleExtensionProcessing();
				$extension_modifier->setCommand($this->getCommand());
				$extension_modifier->setModule($this->getModule());
				$this->add($extension_modifier->getID(),$extension_modifier);
			}

			//------------------------------------
			// adding static processors, included by default in sushee
			//------------------------------------
			$module_prefix = $this->getModule()->getName()."_".$this->getTypeFilePostfix();
			// simple PHP script : old school processors
			$filepath = dirname(__FILE__)."/../processors/".$module_prefix.".php";
			if(file_exists($filepath)){
				$process = &new sushee_nativeModuleProcessing();
				$process->setPath($filepath);
				$process->setModule($this->getModule());
				$process->setCommand($this->getCommand());
				$process->setType($this->getType());
				$this->add($filepath,$process);
			}
			// class file : new processor system
			$filepath = dirname(__FILE__)."/../processors/".$this->getModule()->getName()."_processing.class.php";
			if(file_exists($filepath)){
				$process = &new sushee_nativeClassModuleProcessing();
				$process->setPath($filepath);
				$process->setModule($this->getModule());
				$process->setCommand($this->getCommand());
				$process->setType($this->getType());
				$this->add($filepath,$process);
			}
			
			//------------------------------------
			// taking the processors from the parent module (and eventual ancestors)
			//------------------------------------
			if($native && !in_array($native->getName(),$this->moduleInfoProc_excludes)){
				if($type==SUSHEE_PREPROCESSOR){
					$native_processing_queue = $native->getPreProcessors($cmd);
				}else if($type==SUSHEE_POSTPROCESSOR){
					$native_processing_queue = $native->getPostProcessors($cmd);
				}
				if($native_processing_queue){
					$native_processing_queue->excludeModuleProcessors($this->getModule());
					$native_processing_queue->reset();
					while($process = $native_processing_queue->next()){
						$process->setCommand($this->getCommand());
						$process->setType($this->getType());
						$this->add($process->getID(),$process);
					}
				}
			}

			//------------------------------------
			// loading the processors from the database, current module
			//------------------------------------
			// name of variable in session : name of the module, first letter of command and three first letters of the type (pre,pos,sea == preprocessor,postprocessor,searchtext)
			$varName = 'dbproc'.$this->getModule()->getName().$cmd[0].substr($type,0,3);
			$dbprocessors = Sushee_Session::getVariable($varName);
			
			if(is_array($dbprocessors)){
				// they were saved in session
				// reloading them
				foreach($dbprocessors as $row){
					$process = &new ModuleProcessing();
					$process->load($row);
					$process->setModule($this->getModule());
					$process->setCommand($this->getCommand());
					$process->setType($this->getType());
					$this->add($process->getID(),$process);
				}
			}else{
				$sql = 'SELECT `ID`,`Path`,`ClassName`,`Method`,`Env`,`DefaultEnv` FROM  `modules_processors` WHERE `Activity` = 1 AND `ModuleID` = \''.$this->getModule()->getID().'\'  AND `Type`="'.$type.'"';

				if($cmd){
					if($cmd=='SEARCH'){
						// not allowing generic postprocessing on search command, because could be much too slow (postprocessing is called on every element)
						$sql.=' AND `Command` = "'.$cmd.'"';
					}else if($cmd == 'UPDATE' || $cmd=='CREATE'){
						$sql.=' AND `Command` IN ("'.$cmd.'","CREATE/UPDATE","")'; // create/update can be used to apply a processor whenever its a create or a update
					}else if($cmd == 'KILL' || $cmd=='DELETE'){
						$sql.=' AND `Command` IN ("'.$cmd.'","KILL/DELETE","")';// kill/delete can be used to apply a processor whenever its a kill or a delete
					}else{
						$sql.=' AND `Command` IN ("'.$cmd.'","")'; // empty value can be used to apply a processor on every command
					}
				}
				$sql.=' ORDER BY `Ordering` ASC';
				sql_log($sql);
				$rs = $db_conn->Execute($sql);
				$dbprocessors = array();
				if($rs){
					while($row = $rs->FetchRow()){
						$process = &new ModuleProcessing();
						$process->load($row);
						$process->setModule($this->getModule());
						$process->setCommand($this->getCommand());
						$process->setType($this->getType());
						$this->add($process->getID(),$process);
						$dbprocessors[]=$row;
					}
				}
				Sushee_Session::saveVariable($varName,$dbprocessors);
			}

			//------------------------------------
			// taking the processors from the extensions
			//------------------------------------
			$extensions = $this->getModule()->getDirectExtensions();
			while($extension = $extensions->next()){
				// excluding certain extension to avoid recursive loading
				if(!in_array($extension->getName(),$this->moduleInfoProc_excludes)){
					if($type==SUSHEE_PREPROCESSOR){
						$extension_processing_queue = $extension->getPreProcessors($cmd);
					}else if($type==SUSHEE_POSTPROCESSOR){
						$extension_processing_queue = $extension->getPostProcessors($cmd);
					}
					if($extension_processing_queue){
						// not reloading the processors of the current module
						$extension_processing_queue->excludeModuleProcessors($this->getModule());
						$extension_processing_queue->reset();
						while($process = $extension_processing_queue->next()){
							$process->setCommand($this->getCommand());
							$this->add($process->getID(),$process);
						}
					}
				}
			}

			$this->loaded = true;
		}
	}
	
	// allows to exclude the loading of processors for a certain extension
	// it allows to avoid recursivity in processor loading :  extension processor --> parent processor --> extension processor
	function excludeModuleProcessors($moduleInfo){
		$this->moduleInfoProc_excludes[] = $moduleInfo->getName();
	}
	
	function execute(){

		//------------------------------------------------------------------------------
		// LOADING THE PROCESSORS, FROM THE DATABASE
		//------------------------------------------------------------------------------
		$this->load();
		$this->error = false;
		$res = true;
		
		//------------------------------------------------------------------------------
		// PROCESSORS IN TESTING, IN THE REQUEST
		// <PROCESSORS type="preprocessor/postprocessor">
		//		<PROCESSOR path="..." classname="..." method="..." />
		//	</PROCESSORS>
		//------------------------------------------------------------------------------
		// processors can be tested using a notation inside the NQL
		$node = $this->data->getNode();
		if($node){
			$processorsNodes = $node->getElements('*[1]/PROCESSORS[@type="'.$this->getType().'"]/PROCESSOR');
			if($processorsNodes){
				foreach($processorsNodes as $node){
					$process = &new ModuleProcessing();
					$process->setPath($node->getAttribute('path'));
					$process->setClassName($node->getAttribute('classname'));
					$process->setMethod($node->getAttribute('method'));
					$process->setModule($this->getModule());
					$process->setCommand($this->getCommand());
					$this->add($process->getPath(),$process);
				}
			}
		}

		//------------------------------------------------------------------------------
		// EXECUTION OF THE PROCESSORS, IN THE REQUEST AND  LOADED FROM THE DATABASE
		//------------------------------------------------------------------------------
		$this->reset();
		while($process = $this->next()){
			// giving the current data (because the queue is not created for every object, we reuse the same queue)
			$process->setData($this->getData());
			if($process->isValid()){ // only if process is valid for the current element
				$process_res = $process->execute();
				if(is_object($process_res) && get_class($process_res)=='SusheeProcessorException'){
					$this->error = $process_res;
					$res = false;
				}
				$this->data = $process->getData();
			}
		}
		/*
		// resetting the data inside the processess to 'blank' them and have them ready for a further execution
		// it's also necessary for good serialization inside a session
		$this->reset();
		while($process = $this->next()){
			$data = false;
			$this->setData($data);
		}*/
		
		return $res;
	}
	
	function containsError(){
		return is_object($this->error);
	}
	
	function getError(){
		return $this->error;
	}
	
	function getResult(){
		return $this->result;
	}
	
	function getResponse(){ // response of processors as string
		$res='';
		$this->reset();
		while($process = $this->next()){
			if($process->isValid()){
				$process_res = $process->getResponse();
				if(is_object($process_res) && method_exists($process_res,'getMessage')){
					$res.=$process_res->getMessage();
				}else if(is_string($process_res)){
					$res.=$process_res;
				}
			}
			
		}
		return $res;
	}
	
	function delete(){
		$db_conn = db_connect();
		
		$sql = 'DELETE FROM `modules_processors` WHERE ';
		$sql.= '`ModuleID`= \''.$this->getModule()->getID().'\'';
		if($this->getType()){
			$sql.= 'AND `Type`="'.$this->getType().'"';
		}
		if($this->getCommand()){
			$sql.= 'AND `Command`="'.$this->getCommand().'"';
		}
		sql_log($sql);
		$db_conn->Execute($sql);
		
	}
	
	function getXML(){
		$xml = '<PROCESSORS type="'.encode_to_xml($this->getType()).'">';
		$this->reset();
		while($process = $this->next()){
			if($process->isValid()){
				$xml.=$process->getXML();
			}
		}
		$xml.='</PROCESSORS>';
		return $xml;
	}
	
	// aliases to the Vector methods
	function &next(){
		if(!is_object($this->processors)){
			$this->load();
		}
		if($this->processors)
			return $this->processors->next();
	}
	
	function reset(){
		if(!is_object($this->processors)){
			$this->load();
		}
		if($this->processors)
			$this->processors->reset();
	}
	
	function size(){
		if($this->processors)
			return $this->processors->size();
		return 0;
	}
	
	function &getElement($ID){
		if($this->processors)
			return $this->processors->getElement($ID);
		return false;
	}
	
}

// common interface for objects in a processing queue
abstract class sushee_ProcessingQueueElement extends SusheeObject{
	
	abstract function getID();
	abstract function getCommand();
	abstract function setCommand($cmd);
	abstract function getModule();
	abstract function setModule($moduleInfo);
	abstract function getData();
	abstract function setData($data);
	abstract function isValid();
	abstract function isNative();
	abstract function validate($boolean = true);
	abstract function execute();
	abstract function getXML();
	
	// function called before serialization
	// we reset data and valid, to avoid errors when saving the processors in session (Fatal error: Exception thrown without a stack frame in Unknown on line 0 )
	// its impossible to save in session object with reference to other object (&)
	function __sleep(){
		$this->data = null;
		$this->valid = null;
	}
	
	// function called before UNserialization
	function __wakeup(){
		$this->data = null;
		$this->valid = null;
	}
	
}

// this class is used in the same way as a processor, but its goal is to complete the requests about extensions : extension boolean set in creation/update/search
class sushee_ModuleExtensionProcessing extends sushee_ProcessingQueueElement{
	
	var $moduleID;
	var $data;
	var $command;
	var $valid = true;
	
	function getID(){
		return $this->getModule()->getName();
	}
	
	function setCommand($command){
		$this->command = $command;
	}
	
	function getCommand(){
		return $this->command;
	}
	
	function setModule($moduleInfo){
		$this->moduleID = $moduleInfo->getID();
	}
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function getData(){
		return $this->data;
	}
	
	function setData($data){
		$this->data = $data;
	}
	
	// is the processor te be executed on the element ?
	function isValid(){
		return $this->valid;
	}
	
	function isNative(){
		return false;
	}

	// allows to arbitrarily say the processor should be executed. For example, on creation of an element of this module
	function validate($boolean = true){
		$this->valid = $boolean;
	}
	
	function setType($type){
		$this->type = $type;
	}
	
	function getType(){
		return $this->type;
	}
	
	function execute(){
		//------------------------------------------------------------------------------
		// MANAGING THE BOOLEANS THAT ALLOWS TO FILTER THE EXTENDED ELEMENTS FROM THE NATIVE ELEMENTS WITH AN EXTENDED MODULE
		//------------------------------------------------------------------------------
		
		$data = $this->getData();
		$module_asked_by_user = ($data->getModule()->getName() == $this->getModule()->getName());
		if($this->getCommand()=='CREATE' || $this->getCommand()=='UPDATE'){
			$field = $this->getModule()->getExtensionField();
			if( $field ){ // there is a field with the name of the extension that allows to filter)
				$new_values = $this->data->getNewValues();
				$return_values = $this->data->getNoticeableValues();
				$node = $this->data->getNode();
				
				// assigning the boolean true, to indicates it's an element of that extension
				$must_append = false;
				$value_to_append = false;

				// if enabling the extension
				/* ex:
				<UPDATE>
					<native>
						<INFO>
							<extension>1</extension>
							...
						</INFO>
					</native>
				</UPDATE>
				or
				<UPDATE>
					<extension>...</extension>
				</UPDATE>
				*/
				
				if($module_asked_by_user){
					$must_append = true;
					$value_to_append = 1;
				}
				
				// if an extension is enabled, activating this parent too
				$extensions = $this->getModule()->getExtensions();
				$extensions->reset();
				while($extension = $extensions->next()){
					$extension_field = $extension->getExtensionField();
					
					$extension_asked_by_user = ($data->getModule()->getName() == $extension->getName());
					if($extension_field){
						if($extension_asked_by_user || $data->getNewValue($extension_field->getName())==1){
							$must_append = true;
							$value_to_append = 1;
						}
					}
				}
				
				// if a parent is disabled, disabling this extension too
				$parents = $this->getModule()->getParents();
				$parents->reset();
				while($parent = $parents->next()){
					if($parent->isExtension()){
						$extension_field = $parent->getExtensionField();
						if($extension_field){
							$extension_val = $data->getNewValue($extension_field->getName());
							if($extension_val==='0' || $extension_val===0){ // not == because would allow the field to be absent
								$must_append = true;
								$value_to_append = 0;
							}
						}
					}
				}
				
				
				if($must_append){
					$data->setNewValue($field->getName(),$value_to_append);
				}
				
			}
		}else if($this->getCommand()=='SEARCH' && $module_asked_by_user){
			$node = $this->data->getNode();
			if($node){ // for postprocessing on freelinks result, there is no search node ($node), so not needed to add anything
				$fieldname = strtoupper($this->getModule()->getName());
				$elementNode = $node->getElement('*[1]');
				if($elementNode){

					if(!$elementNode->valueOf('@'.strtolower($fieldname))){ // if there is already an attribute indicating the value, dont force it
						$info_nodes = $elementNode->getElements('INFO');
						$where_info_nodes = $elementNode->getElements('WHERE/INFO');
						$info_nodes = array_merge($info_nodes,$where_info_nodes);
						if(sizeof($info_nodes)>0){
							foreach($info_nodes as $info_node){
								if(!$info_node->getElement($fieldname)){ // if there is already a node indicating the value, dont force it
									$info_node->appendChild('<'.$fieldname.'>1</'.$fieldname.'>');
								}else{
									;
								}
							}
						}else{
							$elementNode->appendChild('<INFO><'.$fieldname.'>1</'.$fieldname.'></INFO>');
						}
					}
				}
			}
		}else if($this->getCommand()=='WHERE' && $module_asked_by_user){
			$node = $this->data->getNode();
			$fieldname = strtoupper($this->getModule()->getName());
			$info_nodes = $node->getElements('INFO');

			if(sizeof($info_nodes)>0){
				foreach($info_nodes as $info_node){
					if(!$info_node->getElement($fieldname)){
						$info_node->appendChild('<'.$fieldname.'>1</'.$fieldname.'>');
					}
				}
			}else{
				$node->appendChild('<INFO><'.$fieldname.'>1</'.$fieldname.'></INFO>');
			}
		}
		return true;
	}
	
	function getResponse(){
		return false;
	}
	
	function getXML(){
		/*$xsusheename = $this->getModule()->getxSusheeName();
		if($this->isValid()){
			$mark = '1';
		}else{
			$mark = '0';
		}
		return '<'.$xsusheename.'>'.$mark.'</'.$xsusheename.'>';*/
	}
}

class ModuleProcessing extends sushee_ProcessingQueueElement{
	
	var $data;
	var $moduleID; // the module concerned by the processor
	var $path; // the path of the PHP file containing the processor
	var $command; // the xsushee command for which is called the processor
	var $type; // postprocessor (after the command execution) or preprocessor (before the command execution)
	var $ordering = false;
	var $className = false; // the class containing the processor
	var $method = false; // the method in the processor class 
	var $defaultEnv = false; // if the processor file doesnt exist in the current environment, looking in the default environment
	var $res = false;
	var $ID = false;
	var $valid = null;
	var $env; // the processor can be limited to one environment, indicated in the ENV field of the processor
	
	function isValid(){
		// processor limited to a specific environment
		if($this->getEnvironment()!=''){
			$request = new Sushee_Request();
			// checking we are in the environment concerned by the processor
			if( $request->getEnvironment() != $this->getEnvironment() ){
				return false;
			}
		}
		
		$data = $this->getData();
		if($data && $data->getNode() && $data->getNode()->getAttribute('disable-processors')==='true'){
			return false;
		}
		
		if($this->valid===null){
			
			$elt = new ModuleElement($this->getModule()->getID(),$data->getValues());
			$this->valid = $elt->isPartOfExtension($this->getModule());
		}
		return $this->valid;
	}
	
	function isNative(){
		return false;
	}

	// allows to arbitrarily say the processor should be executed. For example, on creation of an element of this module
	function validate($boolean = true){
		$this->valid = $boolean;
	}
	
	function execute(){
		require_once(dirname(__FILE__).'/nql.class.php');
		require_once(dirname(__FILE__).'/file.class.php');
		require_once(dirname(__FILE__).'/exception.class.php');
		
		
		$requestName = $command = $this->command;
		if($this->data){
			// global variables available to the processors (for processors without classname and method)
			$db_conn = db_connect();
			$ID = $this->data->getElementID();
			// use in some processor: we ensure compatiblity
			$IDs_array[] = $ID;
			$node = $this->data->getNode();
			if($node){
				$xml = $node->getDocument();
				$current_path = $node->getPath();
				$firstNode = $node->getFirstchild();
				$firstNodePath = $firstNode->getPath();
				$firstNode = $firstNode->nodeName();
			}else{
				$res = new SusheeProcessorWarning('No node to apply processor');
				return $res;
			}
			$moduleInfo = $this->getModule();
			$new_values = $this->data->getNewValues();
			$values = &$new_values;
			$former_values = $this->data->getFormerValues();
			$return_values = $this->data->getNoticeableValues();
		}
		
		$phpfilepath = $this->getPath();
		$phpfile = new KernelFile($phpfilepath);
		if($phpfile->exists()){
			
			// Is it a class with a method to call or a simple PHP script to execute ?
			$classname = $this->getClassName();
			if($classname){
				// checking the classname is not a native sushee class
				$already_included = false;
				// we must first check that the file has not yet been included, otherwise class_exists will return true, but because the file was already included
				$included_files = get_included_files();
				foreach ($included_files as $filename) {
				    if(realpath($filename)==realpath($phpfile->getCompletePath())){
						$already_included = true;
					}
				}
				if(!$already_included && class_exists($classname)){
					$res = new SusheeProcessorWarning('Classname `'.$classname.'` is already used : please use another classname');
				}else{
					// a class with a method to call
					include_once($phpfile->getCompletePath());
					if(!class_exists($classname)){
						if (!$this->isNative()) {
							$res = new SusheeProcessorWarning('Class `'.$classname.'` is not defined in the file `'.$phpfile->getCompletePath().'`');
						}
					}else{

						$new_object = new $classname($this->data);
						// setting as properties the node of the requests, this way we can use NQLOperation as processors
						if($node){
							$new_object->firstNode = $node->getFirstchild();
							$new_object->operationNode = $node;
						}

						// calling the processor method
						$method = $this->getMethod();
						if(!$method){
							if (!$this->isNative()) {
								$res = new SusheeProcessorWarning('Method is not defined for processor `'.$phpfile->getCompletePath().'`');
							}
						}else{
							if(!method_exists($new_object,$method)){
								if (!$this->isNative()) {
									$res = new SusheeProcessorWarning('Method `'.$method.'` does not exist in the class `'.$classname.'`');
								}
							}else{
								$res =  $new_object->$method($this->data);
							}
						}
					}
				}
			}else{
				// simple PHP script to execute
				$res = include($phpfile->getCompletePath());
				// native sushee processors send the message directly (they should be rewritten)
				if($this->className()=='sushee_nativemoduleprocessing' && $res!==true){
					// processor sent an error!
					$message_xml = new XML($res);
					$res = new SusheeProcessorException($res,$message_xml->valueOf('/*[1]/@errorCode'));
					
				}elseif($this->data){ // re-assigning the values in the data object to get it back in the calling script (CREATE,UPDATE)
					$this->data->setNewValues($new_values);
					$this->data->setFormerValues($former_values);
					$this->data->setNoticeableValues($return_values);
				}
			}
			
		}else{
			$res = new SusheeProcessorWarning('PHP file `'.$phpfile->getCompletePath().'` does not exist');
		}
		$this->res = $res;
		return $res;
	}
	
	function load($row){
		$this->setID($row['ID']);
		$this->setPath($row['Path']);
		$this->setModule(moduleInfo($row['ModuleID']));
		$this->setClassName($row['ClassName']);
		$this->setMethod($row['Method']);
		$this->setEnvironment($row['Env']);
		$this->setDefaultEnvironment($row['DefaultEnv']);
	}
	
	function setID($ID){
		$this->ID = $ID;
	}
	
	function getID(){
		return $this->ID;
	}
	
	function setModule($moduleInfo){
		$this->moduleID = $moduleInfo->getID();
	}
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function getData(){
		return $this->data;
	}
	
	function setData($data){
		$this->data = $data;
	}
	
	function setPath($path){
		$this->path = $path;
		if(!$this->ID){
			$this->ID = $this->path;
		}
	}
	
	function getPath(){
		$phpfilepath = $this->path;
		if($phpfilepath[0] != '/'){
			// relative path, need to add the environment (first directory after the domain : apps,apps,Public) before the path
			$request = new Sushee_Request();
			$phpfilepath = $request->getEnvironment().$this->path;
			
			// checking the file exists in the current environment
			// if not falling back on the default env
			$phpfile = new KernelFile($phpfilepath);
			if(!$phpfile->exists()){
				$phpfilepath = $this->getDefaultEnvironment().$this->path;
			}
		}
		
		return $phpfilepath;
	}
	
	function setCommand($command){
		$this->command = $command;
	}
	
	function getCommand(){
		return $this->command;
	}
	
	function setType($type){
		$this->type = $type;
	}
	
	function getType(){
		return $this->type;
	}
	
	function setClassName($classname){
		$this->className = $classname;
	}
	
	function getClassName(){
		return $this->className;
	}
	
	function setMethod($method){
		$this->method = $method;
	}
	
	function getMethod(){
		return $this->method;
	}
	
	function setOrdering($ordering){
		$this->ordering = $ordering;
	}
	
	function getOrdering(){
		return $this->ordering;
	}
	
	function setEnvironment($env){
		$this->env = $env;
	}
	
	function getEnvironment(){
		return $this->env;
	}
	
	function setDefaultEnvironment($defaultEnv){
		$this->defaultEnv = $defaultEnv;
	}

	function getDefaultEnvironment(){
		return $this->defaultEnv;
	}

	function create(){
		$db_conn = db_connect();
		$moduleInfo = $this->getModule();
		$sql = 'INSERT INTO `modules_processors`(`ModuleID`,`ApplicationID`,`Type`,`Command`,`Path`,`ClassName`,`Method`,`Ordering`) VALUES(\''.$moduleInfo->getID().'\',0,"'.$this->getType().'","'.$this->getCommand().'","'.$this->getPath().'","'.$this->getClassName().'","'.$this->getMethod().'",\''.$this->getOrdering().'\');';
		sql_log($sql);
		$db_conn->Execute($sql);
	}

	function getXML(){
		$xml='';
		// @francois added 20111228
		// native processors:avoid sending response if no class/mathods
		if ($this->res) {
			$xml .='<PROCESSOR path="'.$this->getPath().'"';
			$classname = $this->getClassName();
			if($classname){
				$xml.=' classname="'.encode_to_xml($classname).'"';
				$method = $this->getMethod();
				if($method){
					$xml.=' method="'.encode_to_xml($method).'"';
				}
			}
			$xml.='>';
			if(is_object($this->res) && method_exists($this->res,'getMessage')){
				$xml.=$this->res->getMessage();
			}else if(is_string($this->res)){
				$xml.=$this->res;
			}
			$xml.='</PROCESSOR>';
		}
		return $xml;
	}

	function getResponse(){
		return $this->res;
	}
	
}
/* sushee native module processors : contact email check, group isFavorite handling, etc */
class sushee_nativeModuleProcessing extends ModuleProcessing{
	
	function isValid(){
		$data = $this->getData();
		if($data && $data->getNode() && $data->getNode()->getAttribute('disable-sushee-processors')==='true'){
			return false;
		}
		$cmd = $this->getCommand();
		if($cmd == 'CREATE' || $cmd == 'UPDATE' || $cmd == 'DELETE' || $cmd == 'KILL'){
			return true;
		}
		// only preprocessors on SEARCH, because postprocessors, executed on every element would be too slow
		if($cmd=='SEARCH' && $this->getType()=='preprocessor'){
			return true;
		}
		
		return false;
	}
	
	function isNative(){
		return true;
	}

}
/* sushee native module processors, but as a class and not as a simple PHP script like sushee_nativeModuleProcessing */
class sushee_nativeClassModuleProcessing extends sushee_nativeModuleProcessing{

	function getClassname(){
		return 'sushee_'.$this->getCommand().'_'.$this->getModule()->getName().'_processor';
	}

	function getMethod(){
		if($this->getType()=='preprocessor'){
			return 'preprocess';
		}else{
			return 'postprocess';
		}
	}
}