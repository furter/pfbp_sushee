<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/xsushee.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/pdf.class.php");

class Sushee_Shell extends SusheeObject{
	var $query;
	var $include_navigation;
	var $public;
	var $result=false;
	var $xml=false;
	var $languageID=false;
	var $include_supp = true;
	var $include_unpublished = false;
	var $nl2br = null;
	var $entities = true;
	var $params = array();
	
	function Sushee_Shell($include_navigation=true,$public=true){
		$this->operations = array();
		$this->include_navigation = $include_navigation;
		$this->public = $public;
	}

	function setLanguage($languageID){
		$this->languageID = $languageID;
	}
	
	function includeNavigation($include_navigation=true){
		$this->include_navigation = $include_navigation;
	}
	
	function includeSuppParams($bool=true){
		$this->include_supp = $bool;
	}
	
	function includeUnpublished($bool=true){
		$this->include_unpublished = $bool;
	}
	
	function setPublic($public=true){
		$this->public = $public;
	}
	
	function setSecurity($secure=true){
		$this->public = !$secure;
	}
	
	function setNl2br($boolean){
		$this->nl2br = $boolean;
	}
	
	function enableNl2br(){
		$this->nl2br = true;
	}
	
	function disableNl2br(){
		$this->nl2br = false;
	}
	
	function addOperation($xSusheeOperation){
		$this->addCommand($xSusheeOperation);
	}
	
	function addParam($key,$value){
		$this->params[$key] = $value;
	}

	function addURLParam($key,$value){
		$_GET[$key] = $value;
	}

	function reset(){
		$this->result=false;
		$this->operations = array();
		$this->xml = false;
	}
	
	function addCommand($xSusheeOperation){
		if($this->result!==false)
			$this->reset();
		if(is_object($xSusheeOperation)){
			$this->operations[]=$xSusheeOperation->copyOf('.');
		}else{
			$this->operations[]=$xSusheeOperation;
		}
		
	}
	
	function addCommands($xSusheeOperations){
		if(is_array($xSusheeOperations)){
			foreach($xSusheeOperations as $xSusheeOperation){
				$this->addCommand($xSusheeOperation);
			}
		}else{
			$this->addCommand($xSusheeOperations);
		}
	}
	
	function outputXML(){
		$this->execute();
		xml_out($this->getResponse());
	}
	
	
	function xml_out(){
		$this->outputXML();
	}
	
	function getResponse($default_nl2br=true){ // default nl2br is the behaviour usually used by the output (pdf,text or html). We use it only if the user did not change it by hand
		if($this->xml && $this->xml->isModified){
			$this->result = $this->xml->toString();
		}
		if($this->nl2br!==null){
			$default_nl2br = $this->nl2br;
		}
		
		if($default_nl2br)
			return nl2br($this->result);
		else
			return $this->result;
	}
	
	function getQuery(){
		require_once(dirname(__FILE__)."/../common/namespace.class.php");
		$namespaces = new NamespaceCollection();
		$namespaces_str = $namespaces->getXMLHeader();
		$frontNode = '<QUERY'.$namespaces_str.'>';
		if($this->languageID){
			$frontNode = '<QUERY languageID="'.encode_to_xml($this->languageID).'"'.$namespaces_str.'>';
		}
		return ($frontNode.$this->commonOperations().implode('',$this->operations).'</QUERY>');
	}
	
	function commonOperations(){
		return '';
	}
	
	function handleOutputType($outputType,$response){
		if($_GET['xml']=='true'){
			$outputType = 'xml';
		}
		
		switch($outputType){
			case 'query':
				xml_out($this->getQuery());
			case 'html':
			case 'echo':
				echo $response;
				break;
			case 'text':
				die($response);
			case 'xml':
				xml_out($response);
		}
	}
	
	function execute($outputType=false){ // outputType can be : query,echo,text,xml
		if($this->result==false){
			Sushee_Timer::lap('xsushee query start');

			$before_query_take_unpublished = $GLOBALS['take_unpublished'];
			$GLOBALS['take_unpublished'] = $this->include_unpublished;
			$this->result = query($this->getQuery(),$this->include_navigation,true,$this->public,$this->include_supp,false,false);
			$GLOBALS['take_unpublished'] = $before_query_take_unpublished;

			Sushee_Timer::lap('xsushee query end');
		}
		if($outputType)
			$this->handleOutputType($outputType,$this->getResponse());
		return $this->result;
	}
	
	function loadXML(){
		$this->execute();
		if($this->xml===false)
			$this->xml = new XML($this->result);
		$this->xml->enableEntities($this->entities);
	}
	
	function valueOf($xpath){
		$this->loadXML();
		return $this->xml->valueOf($xpath);
	}
	
	function getLastError(){
		$this->loadXML();
		return $this->xml->getLastError();
	}
	
	function getElement($xpath){
		$this->loadXML();
		return $this->xml->getElement($xpath);
	}
	
	function getElements($xpath){
		$this->loadXML();
		return $this->xml->getElements($xpath);
	}
	
	function nodeName(){
		$this->loadXML();
		return $this->xml->nodeName();
	}
	
	function copyOf($xpath){
		$this->loadXML();
		return $this->xml->copyOf($xpath);
	}
	
	function exists($xpath){
		$this->loadXML();
		return $this->xml->exists($xpath);
	}
	
	function replaceValue($new_value){
		$this->loadXML();
		return $this->xml->replaceValue($new_value);
	}
	
	function count($xpath){
		$this->loadXML();
		return $this->xml->count($xpath);
	}
	
	function implode($separator,$xpath){
		$this->loadXML();
		return $this->xml->implode($separator,$xpath);
	}

	function enableEntities($boolean = true){
		$this->entities = $boolean;
		if($this->xml)
			$this->xml->enableEntities($this->entities);
	}

	function transform($template,$outputType=false)
	{
		$this->execute();

		if(is_object($template))
			$template = $template->getCompletePath();

		// asking nl2br=true which is the default value for this kind of output. getResponse will eventually replace it by the user value
		$response = $this->getResponse(true);

		// logging and debugging handling
		$this->handleOutputType($outputType,$response);

		// nl2br are now handled by getResponse
		$html = transform($response,$template,$this->params,false,/* nl2br */ false,true,'html',$this->entities); 

		if ($_GET['stats'] === 'true' || $GLOBALS['sushee_stats'] === true)
		{
			debug_log( '
' . Sushee_Timer::toString() . '
');
		}

		return $html;
	}
	
	function display($template){
		echo $this->transform($template);
	}
	
	function transformToText($template,$outputType=false){
		$this->execute();
		if(is_object($template))
			$template = $template->getCompletePath();
		
		// asking nl2br=false which is the default value for this kind of output. getResponse will eventually replace it by the user value
		$response = $this->getResponse(true);
		
		// logging and debugging handling
		$this->handleOutputType($outputType,$response);
		return transform_to_text($response,$template,$this->params,/* nl2br */ false);// nl2br are now handled by getResponse
	}
	
	function transformToPDF($template,$output=true,$outputType=false){
		$this->execute();
		if(is_object($template))
			$template = $template->getCompletePath();
			
		// asking nl2br=false which is the default value for this kind of output. getResponse will eventually replace it by the user value
		$response = $this->getResponse(false);
		
		// logging and debugging handling
		$this->handleOutputType($outputType,$response);
		return pdf_transform($response,$template,$output,/* nl2br */ false);// nl2br are now handled by getResponse
	}
	
	function transformToRTF($template,$output=true,$outputType=false){
		$this->execute();
		if(is_object($template))
			$template = $template->getCompletePath();
		else{
			$template_file = new KernelFile($template);
			$template = $template_file->getCompletePath();
		}
		
		// asking nl2br=false which is the default value for this kind of output. getResponse will eventually replace it by the user value
		$response = $this->getResponse(false);
		
		// logging and debugging handling
		$this->handleOutputType($outputType,$response);
		
		$rtf_generator = new sushee_RTFGenerator();
		$rtf_generator->setTemplate($template);
		$res = $rtf_generator->execute($response);
		
		if(!$res){
			debug_log('Problem in the generation of the RTF');
			return false;
		}
		
		$file = $rtf_generator->getFile();
		if($output){
			$file->forceDownload();
		}else{
			return $file->getPath();
		}
	}

	function toString(){
		return '<?xml version="1.0" encoding="utf-8"?>'.$this->getQuery();
	}
	
	function encode($str){
		return encode_to_xml($str);
	}

	function validate($validator=null){
		$this->validation = true;
		$query = $this->getQuery();
		
		if($validator=='DOM'){
			$validator = new DOMValidator();
			$bool = $validator->execute($query);
			$this->result = $validator->getXML();
			return $bool;
		}
	}

	function setUserID($ID){
		$user = new Sushee_User();
		return $user->setID($ID);
	}

	function containsError(){
		$this->loadXML();
		if($this->xml->exists('/RESPONSE/MESSAGE[@msgType=1]')){
			return true;
		}
		return false;
	}

	function enableException($boolean = true){
		Sushee_Request::enableException($boolean);
	}
}

class NQL extends Sushee_Shell{
	function NQL($include_navigation=true,$public=true){
		parent::Sushee_Shell($include_navigation,$public);
	}
}

class DOMValidator extends SusheeObject{
	
	var $xml = NULL;
	
	function execute($query){
		libxml_use_internal_errors(true);
		$tempDom = new DomDocument();
		$success = $tempDom->loadXML($query);
		if($success == true){
			$result =  $tempDom->relaxNGValidate('/Users/macmini/Sites/'.Sushee_dirname.'/file/NQL.rng');	 
		
			if(!$result){
				$xmlErrorMsg = '<RESPONSE><RESULTS>';
				$errors = libxml_get_errors();
				foreach($errors as $error){
					$xmlErrorMsg.= '<ERROR errorCode="'.$error->code.'" line="'.$error->line.'" column="'.$error->column.'">'.$error->message.'</ERROR>';	
				}
			$xmlErrorMsg.='</RESULTS></RESPONSE>';
			libxml_clear_errors();
			$this->xml = $xmlErrorMsg;
			
			
			}
			return $result;
		}
		else{
			return false;
		}
	}
	
	function getXML(){
		return $this->xml;
	}
}

class XMLStarletValidator extends SusheeObject{
	function execute($query){
		$file = new TempFile();
		$file->setExtension('xml');
		$file->save($str);
		$cmdLine = new commandLine('/usr/bin/xmllint '.$file->getCompletePath());
		echo $cmdLine->execute();
	}
}

class MiniNQL extends SusheeObject{
	// class executing a single command and returning it without RESPONSE node
	var $command;
	
	function MiniNQL($command){
		$this->command = $command;
	}
	
	function getQuery(){
		require_once(dirname(__FILE__)."/../common/namespace.class.php");
		$namespaces = new NamespaceCollection();
		$namespaces_str = $namespaces->getXMLHeader();
		$frontNode = '<QUERY'.$namespaces_str.'>';
		return ($frontNode.$this->command.'</QUERY>');
	}
	
	function execute(){
		$this->result = query($this->getQuery(),false,true,true,false,true,false);
		return $this->result;
	}
}