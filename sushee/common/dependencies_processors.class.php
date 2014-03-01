<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/dependencies_processors.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/classcaller.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");

class sushee_DependencyProcessing extends SusheeObject{
	
	var $path = false;
	var $className = false;
	var $method = false;
	var $response = false;
	
	function execute(){
		$classcaller = new sushee_PHPClassCaller($this->path,$this->className,$this->method,$this->data);
		$res = $classcaller->execute();
		if($res){
			$this->response = $classcaller->getResponse();
			return $response;
		}else{
			$this->setError($classcaller->getError());
			$this->response = new SusheeProcessorException($this->getError());
			return false;
		}
	}
	
	function getResponse(){
		return $this->response;
	}
	
	function setPath($path){
		$this->path = $path;
	}
	
	function setClassName($className){
		$this->className = $className;
	}
	
	function setMethod($method){
		$this->method = $method;
	}
	
	function load($row){
		$this->path= $row['Path'];
		$this->className= $row['ClassName'];
		$this->method= $row['Method'];
	}
	
	function setData($data){
		$this->data = $data;
	}
}

class sushee_DependencyProcessingQueue extends SusheeObject{
	
	var $depType = false;
	var $dep = false;
	var $loaded = false;
	var $processors = array();
	var $error = false;
	var $message = false;
	var $type = false;
	var $depOperation = false;
	
	function sushee_DependencyProcessingQueue($depType,$depOperation,$procType){
		$this->depType = $depType;
		$this->depOperation = $depOperation;
		$this->type = $procType;
	}
	
	function setDepType($depType){
		$this->depType = $depType;
	}
	
	// processor type : preprocessor, postprocessor
	function setType($type){
		$this->type = $type;
	}
	
	function getType(){
		return $this->type;
	}
	
	// dep operation: create, update, remove
	function setDepOperation($operation){
		$this->depOperation = $operation;
	}
	
	function getDepOperation(){
		return $this->depOperation;
	}
	
	function load(){
		if($this->depType && !$this->loaded){
			$sql = 'SELECT `ID`,`Path`,`ClassName`,`Method` FROM `dependencies_processors` WHERE `DependencyType`="'.encode_for_db($this->depType->getName()).'" AND `Activity` = 1';
			if($this->getType()){
				$sql.=' AND `Type` = "'.encode_for_db($this->getType()).'" ';
			}
			if($this->getDepOperation()){
				if($this->getDepOperation()=='create' || $this->getDepOperation()=='update')
					$sql.=' AND `DepOperation` IN ("'.encode_for_db($this->getDepOperation()).'","create/update")';
				else
					$sql.=' AND `DepOperation` = "'.encode_for_db($this->getDepOperation()).'"';
			}
			sql_log($sql);
			
			$db_conn = db_connect();
			$rs = $db_conn->execute($sql);
			
			if($rs){
				while($row = $rs->fetchRow()){
					$process = &new sushee_DependencyProcessing();
					$process->load($row);
					$this->processors[] = $process;
				}
				$this->loaded = true;
			}
		}
		
	}
	
	function setDependency($dep){
		$this->dep = $dep;
	}
	
	function execute(){
		//------------------------------------------------------------------------------
		// LOADING THE PROCESSORS, FROM THE DATABASE
		//------------------------------------------------------------------------------
		$this->load();
		$this->error = false;
		
		//------------------------------------------------------------------------------
		// EXECUTION OF THE PROCESSORS LOADED FROM THE DATABASE
		//------------------------------------------------------------------------------
		$data = new sushee_DependencyProcessorData();
		$data->setDepType($this->depType);
		$data->setDependency($this->dep);
		$data->setOperation($this->getDepOperation());
		foreach($this->processors as $process){
			$process->setData($data);
			$process_res = $process->execute();
			$process_response = $process->getResponse();
			if(!$process_res && get_class($process_response)=='SusheeProcessorException'){
				$this->error = $process_response;
				return false;
			}
		}
	}
	
	function containsError(){
		return is_object($this->error);
	}
	
	function getResponse(){ // response of processors as string
		$res='';
		
		foreach($this->processors as $process){
			$process_res = $process->getResponse();
			if(is_object($process_res) && method_exists($process_res,'getMessage')){
				$res.=$process_res->getMessage();
			}else if(is_string($process_res)){
				$res.=$process_res;
			}
		}
		return $res;
	}
	
}

class sushee_DependencyProcessorData extends SusheeObject{
	
	var $dep;
	var $depType;
	var $operation;
	
	function getType(){
		return $this->getDepType();
	}
	
	function getOperation(){
		return $this->operation;
	}
	
	function getDepType(){
		return $this->depType;
	}
	
	function setDepType($depType){
		$this->depType = $depType;
	}
	
	function setDependency($dep){
		$this->dep = $dep;
	}
	
	function setOperation($operation){
		$this->operation = $operation;
	}
	
	function getDep(){
		return $this->dep;
	}
	
	function getOriginID(){
		return $this->getDep()->getOriginID();
	}
	
	function getTargetID(){
		return $this->getDep()->getTargetID();
	}
	
	function getOriginElement(){
		$elt =  new ModuleElement($this->getDepType()->getModuleOriginID(),$this->getOriginID());
		$elt->loadFields();
		return $elt;
	}
	
	function getTargetElement(){
		$elt =  new ModuleElement($this->getDepType()->getModuleTargetID(),$this->getTargetID());
		$elt->loadFields();
		return $elt;
	}
	
}

?>