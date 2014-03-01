<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/webaccount/webaccount.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/oauth_webaccount.class.php');
require_once(dirname(__FILE__).'/../common/nectil_element.class.php');
require_once(dirname(__FILE__).'/../common/exception.class.php');
require_once(dirname(__FILE__).'/webaccount_objects.class.php');

/* a webservice account e.g. twitter account, linkedin profile */
class Sushee_WebAccount extends sushee_Element{
	
	var $api = false;
	var $loaded = false;
	var $method = 'get';
	var $params = array();
	var $headers = array();
	var $files = array();
	
	/* -------------------------------
	METHOD TO REDEFINE FOR EACH PROTOCOL : OAUTH, OAUTH2
	----------------------------------*/
	
	 // return the response of the query
	function request(){
		// to implement in each protocol
		return false;
	}
	
	 // return the type of output : text/html, application/json, text/xml, etc
	function getOutputContentType(){
		// to implement in each protocol
		return $this->contentType;
	}
	
	function authorize(){
		// to implement in each protocol
		return false;
	}
	
	
	/* -------------------------------
	COMMON PART
	----------------------------------*/
	
	function Sushee_WebAccount($values){
		$moduleInfo = moduleInfo('webaccount');
		parent::sushee_Element($moduleInfo->ID,$values);
	}
	
	function loadFields(){
		if(!$this->loaded){
			parent::loadFields();
			$this->loaded = true;
		}
	}
	
	function reloadFields(){
		$this->loaded = false;
		$this->loadFields();
	}
	
	function isAuthorized(){
		$this->loadFields();
		$authorizationState = $this->getField('Authorization_State');
		return ($authorizationState == 'authorized');
	}
	
	function getWebAPI(){
		if(!$this->api){
			$this->loadFields();
			$apiType = $this->getField('API');
			
			if(!$apiType){
				$this->setError('API is empty in webaccount (ID:'.$this->getID().')');
				return false;
			}

			// api properties
			$db_conn = db_connect();
			$apiModuleInfo = moduleInfo('webapi');
			$api_sql = 'SELECT * FROM `'.$apiModuleInfo->getTableName().'` WHERE `Denomination` = "'.encode_for_db($apiType).'" AND `Activity` = 1;';
			sql_log($api_sql);
			$api_row = $db_conn->getRow($api_sql);
			 if(!$api_row){
				$this->setError('WebAPI `'.$apiType.'` doesn\'t exist');
				return false;
			}
			$this->api = new Sushee_WebAPI($api_row);
		}
		
		return $this->api;
	}
	
	function setURL($url){
		$this->url = $url;
	}
	
	function getURL(){
		return $this->url;
	}
	
	// HTTP method to use. Usually POST or GET
	function setMethod($method){
		$this->method = $method;
	}
	
	function getMethod(){
		return strtolower($this->method);
	}
	
	function addParam($key,$value){
		$this->params[$key] = $value;
	}
	
	function getParams(){
		return $this->params;
	}
	
	function addFile($key,$path){
		$this->files[$key] = $path;
	}
	
	function getFiles(){
		return $this->files;
	}
	
	function addHeader($key,$value){
		$this->headers[$key] = $value;
	}

	function getHeaders(){
		return $this->headers;
	}

	function setBody($body){
		$this->body = $body;
	}

	function getBody(){
		return $this->body;
	}

	function execute(){
		$this->response = $this->request();
		return $this->response;
	}

	function getResponse(){
		return $this->response;
	}

	function json2xml($json){
		// transforming json string in a PHP array
		$json_array = json_decode(decode_from_xml($json),true);
		
		// converting the json array to an XML string
		$converter = new sushee_PHPObjects2XML();
		$xml = '<root>'.$converter->execute($json_array).'</root>';
		
		return $xml;
	}
}

/* allow to send errors when calling unrecognized operation on a webapi */
class Sushee_WebaccountException extends SusheeException{}

/* a webservice API e.g. twitter, linkedin, dropbox, etc. */
class Sushee_WebAPI extends Sushee_Element{
	
	function Sushee_WebAPI($values){
		$moduleInfo = moduleInfo('webapi');
		parent::sushee_Element($moduleInfo->ID,$values);
	}
}