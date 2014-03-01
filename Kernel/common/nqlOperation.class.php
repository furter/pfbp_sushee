<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/nqlOperation.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/console.class.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/security.class.php");

class NQLOperation extends SusheeObject{
	var $operationNode;
	var $name;
	var $firstNode;
	var $msg;
	/*var $xml;*/
	var $success;
	var $elementID;
	
	function NQLOperation($name,$operationNode){
		$this->success = false;
		$this->sysconsole = new LogConsole();
		$this->name = $name;
		$this->operationNode = $operationNode;
		if(is_object($operationNode))
			$this->firstNode = $operationNode->getElement('./*[1]');
		else
			$this->setError('XML Node invalid');
	}
	
	function setNQL($nql){
		$xml = new XML($nql);
		$this->operationNode = $xml->getElement('/');
		if(is_object($this->operationNode))
			$this->firstNode = $this->operationNode->getElement('./*[1]');
		else
			$this->setError('XML Node invalid');
	}
	
	function setOperationnode($node){
		$this->operationNode = $node;
	}
	
	function getOperationnode(){
		return $this->operationNode;
	}
	
	function getOperation(){
		if($this->operationNode){
			return $this->operationNode->nodeName();
		}else{
			return false;
		}
	}
	
	function setFirstnode($node){
		$this->firstNode = $node;
	}
	
	function getFirstnode(){
		return $this->firstNode;
	}
	
	function setName($name){
		$this->name = $name;
	}
	
	function getName(){
		return $this->name;
	}
	
	function getOperationAttributes(){
		$attributes ='';
		
		if ($this->name)
			$attributes.=' name="'.$this->name.'"';
		if($this->operationNode)
			$external_file = $this->operationNode->valueOf('@fromFile');
		if($external_file)
			$attributes.=' fromFile="'.$external_file.'"';
		if($this->elementID)
			$attributes.=' elementID="'.$this->elementID.'"';
			
		$forbidden = array('page','name','fromFile','hits','pages','isLastPage','totalPages','totalCount','last-page');
		if(is_object($this->operationNode)){
			$operationNodeAttrs = $this->operationNode->getAttributes();
			foreach($operationNodeAttrs as $attrName => $attrValue){
				if(!in_array($attrName,$forbidden)){
					$attributes.=' '.$attrName.'="'.$attrValue.'"';
				}
			}
		}
		
		return $attributes;
	}
	
	function setError($msg,$error_code=0){
		// throwing exception if developer using sushee asked it (see Sushee_Shell->enableException)
		if(Sushee_Request::exceptionEnabled() || $this->operationNode->getAttribute('throw-exception')==='true'){
			throw new SusheeException($msg,$error_code);
		}
		
		$attributes = $this->getOperationAttributes();
		if(substr($msg,0,9)=='<MESSAGE '){
			$this->msg = '<MESSAGE '.$attributes.' '.substr($msg,9);
		}else{
			$this->msg = "<MESSAGE".$attributes." msgType=\"1\" errorCode=\"$error_code\">".encode_to_xml($msg)."</MESSAGE>";
		}
	}
	
	function setSecurityError($msg,$error_code=0){
		// throwing exception if developer using sushee asked it (see Sushee_Shell->enableException)
		if(Sushee_Request::exceptionEnabled() || $this->operationNode->getAttribute('throw-exception')==='true'){
			throw new SusheeSecurityException($msg,$error_code);
		}
		
		$attributes = $this->getOperationAttributes();
		$this->msg = "<MESSAGE".$attributes." msgType=\"3\" errorCode=\"$error_code\">".encode_to_xml($msg)."</MESSAGE>";
	}
	
	function setSuccess($msg){
		$attributes = $this->getOperationAttributes();
		$this->msg = "<MESSAGE".$attributes." msgType=\"0\">".encode_to_xml($msg)."</MESSAGE>";
	}
	
	function setElementID($ID){
		$this->elementID = $ID;
	}
	
	function getElementID(){
		return $this->elementID;
	}
	
	function getMsg(){
		return $this->msg;
	}
	
	function setMsg($msg){
		$this->msg = $msg;
	}
	
	function parse(){
		return true;
	}
	
	function operate(){
		return true;
	}
	
	function getXML(){
		return $this->getMsg();
	}
	
	function execute(){
		$params_ok = $this->parse();
		if($params_ok){
			$this->success = $this->operate();
			if($this->success)
				return $this->getXML();
			else
				return $this->getMsg();
		}else
			return $this->getMsg();
	}
	
	function getOperationSuccess(){
		return $this->success;
	}
}

class RetrieveOperation extends NQLOperation{
	var $xml;
	
	function RetrieveOperation($name,$operationNode){
		parent::NQLOperation($name,$operationNode);
	}
	
	function getXML(){
		return $this->xml;
	}
	function setXML($xml){
		$this->xml = $xml;
	}
}

?>