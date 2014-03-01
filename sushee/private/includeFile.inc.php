<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/includeFile.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');


//----------------------------------
// <INCLUDE file=""/>
// allows to include a xsushee external file or a normal XML file
//----------------------------------

class sushee_includeFile extends RetrieveOperation{
	
	var $include_file;
	
	function parse(){
		$this->include_file = $this->operationNode->getData('/@file');
		if (!$this->include_file){
			$this->setError("Include has no file specified.");
			return false;
		}
		return true;
	}
	
	function operate(){
		
		$include_xml = new XML();
		$include_xml->setSkipWhiteSpaces(true); // not to have <br/> in included files
		$include_xml->importFromFile($this->include_file);
		
		// trying inside /Files/
		if (!$include_xml->loaded)
			$include_xml->importFromFile($GLOBALS["nectil_dir"].$this->include_file);
			
		if ($include_xml->loaded){
			
			$isquery = $include_xml->nodeName('/*[1]');
			
			if ($isquery == 'QUERY' ){
				// xsushee QUERY
				$include_xml->setAttribute('/QUERY/*', 'fromFile', $include_file);
				$query_result = request($include_xml->toString(),true,false,false,false,$GLOBALS["restrict_language"],$GLOBALS["priority_language"],$GLOBALS["php_request"],$GLOBALS["dev_request"]);
				
			}else{
				// standard XML file
				$query_result = '<FILE path="'.$this->include_file.'">' . $include_xml->toString('/','') . '</FILE>';
			}
			$this->setXML($query_result);
		}else{
			
			$this->setError("File doesn't exist or couldn't be parsed (".$this->include_file.").");
			return false;
			
		}
		return true;
	}
	
}