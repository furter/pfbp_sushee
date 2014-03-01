<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchWebservice.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/url.class.php');
require_once(dirname(__FILE__).'/../webaccount/webaccount.class.php');

class searchWebservice extends RetrieveOperation{
	
	var $url = false;
	var $method = false;
	var $params = array();
	var $format = 'xml';
	var $validFormats = array('xml','json','file','text','html','json2xml');
	
	function parse(){
		$url = decode_from_xml($this->firstNode->valueOf('@url'));
		$url = str_replace(' ','+',$url);
		if(!$url){
			$this->setError('You provided no url for the webservice');
			return false;
		}
		$this->url = $url;
		$this->method = decode_from_xml($this->firstNode->valueOf('@method'));
		
		$format = $this->firstNode->valueOf('@format');
		if($format){
			if(!in_array($format,$this->validFormats)){
				$this->setError('Format `'.$format.'` is not a supported webservice format ('.implode(', ',$this->validFormats).')');
				return false;
			}
			$this->format = $this->firstNode->valueOf('@format');
		}
		
		//$this->log($url);
		return true;
	}
	
	function getFormat(){
		return $this->format;
	}
	
	function operate(){
		$urlHandler = new URL($this->url);
		
		if($this->method)
			$urlHandler->setMethod($this->method);
		$paramsNodes = $this->firstNode->getElements('PARAMS/PARAM');
		$headersNodes = $this->firstNode->getElements('HEADERS/HEADER');
		$body = $this->firstNode->copyOf('BODY/*'); // XML node inside BODY
		if(!$body){
			$body = decode_from_xml($this->firstNode->valueOf('BODY')); // text inside BODY
		}else{
			// we send XML
			$urlHandler->addHeader('Content-Type','text/xml;charset=utf-8');
		}
		$encoding = $this->firstNode->valueOf('PARAMS/@encoding');
		if(!$encoding){
			$encoding = 'utf-8';
		}
		$encoding = strtolower($encoding);
		
		// XML BODY
		if($body){
			$urlHandler->setBody($body);
			if($urlHandler->getMethod()!='post'){
				$this->setError('To send a body, the method has to be `post`');
				return false;
			}
		}else{
			foreach($paramsNodes as $node){
				if($node->valueOf('@name')){
					if($node->getElements('./*')){
						$urlHandler->addParam(decode_from_xml($node->valueOf('@name')),'<?xml version="1.0"?>'.$node->copyOf('./*[1]'));
					}else{
						$param_name = decode_from_xml($node->valueOf('@name'));
						if($encoding=='utf-8')
							$urlHandler->addParam($param_name,entities_to_utf8(decode_from_xml($node->valueOf())));
						else
							$urlHandler->addParam($param_name,utf8_decode(entities_to_utf8(decode_from_xml($node->valueOf()))));
					}
				}

			}
		}
		
		
		
		// ADDING HEADERS
		foreach($headersNodes as $node){
			if($node->valueOf('@name')){
				$param_name = decode_from_xml($node->valueOf('@name'));
				if($encoding=='utf-8')
					$urlHandler->addHeader($param_name,entities_to_utf8(decode_from_xml($node->valueOf())));
				else
					$urlHandler->addHeader($param_name,utf8_decode(entities_to_utf8(decode_from_xml($node->valueOf()))));
			}
				
		}
		$urlOutput = $urlHandler->execute();
		if($urlOutput===false){
			$this->setError('The service isn\'t responding');
			return false;
		}
		$webservice_xml = new XML($urlOutput);
		$contentType = $urlHandler->getOutputContentType();
		
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		$xml.='<WEBSERVICE url="'.encode_to_xml($this->url).'">';
		
		if($this->getFormat() == 'xml'){
			
			$webservice_xml = new XML($urlOutput);
			//$xml_str = $webservice_xml->toString('/','');
			$root = $webservice_xml->nodeName('/*[1]');
			$start_root = strpos($urlOutput,'<'.$root);
			if($root && $start_root!==false){
				$xmlHeader = substr($urlOutput,0,$start_root);
				$xml_str = substr($urlOutput,$start_root);
			}else{
				$xml_str = $urlOutput;
			}
			unset($urlOutput);
			$urlOutput = null;
			
			$matches = array();
			$encoding_present = preg_match('/encoding="([^\"]+)"/',$xmlHeader,$matches);
			if($encoding_present){
				$encoding = strtolower($matches[1]);
				if($encoding =='iso-8859-1'){
					$xml_str = utf8_decode(iso_To_UnicodeEntities($xml_str));
				}else{
					$xml_str = utf8_decode(utf8_To_UnicodeEntities($xml_str));
				}
			}else{
				$xml_str = utf8_decode(utf8_To_UnicodeEntities($xml_str));
			}
			$xml_str = str_replace("\n",'',$xml_str);
		}else if($this->getFormat()=='json2xml'){
			debug_log('Json received is '.$urlOutput);
			$json_array = json_decode($urlOutput,true);

			// converting the json array to an XML string
			$converter = new sushee_PHPObjects2XML();
			$xml_str = $converter->execute($json_array);
			
		}else if($this->getFormat() == 'json' || $this->getFormat() == 'html' || $this->getFormat() == 'text'){
			
			// outputting in the returned XML, because the content is pure text
			$xml_str = encode_to_xml($urlOutput);
			
		}else if($this->getFormat() == 'file'){
			
			$saveas = $this->firstNode->valueOf('@saveas');
			if($saveas && $saveas[0]!='/'){
				$this->setError('File should be a sushee file (inside /Files/). e.g. /media/my.jpg, /tmp/my.txt');
				return false;
			}
			if($saveas && $saveas[0]=='/'){
				$saveFile = new File($saveas);
			}else{
				// saving in a separate file as content is binary
				$saveFile = new TempFile();
				$resolver = new Sushee_MimeTypeSolver();
				$saveFile->setExtension($resolver->getExtension($contentType));
			}
			
			$saveFile->save($urlOutput);
			
			$xml_str = getFileXML($saveFile->getCompletePath());
			
		}
		
		
		$xml.=$xml_str;
		$xml.='</WEBSERVICE>';
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
	
}

?>
