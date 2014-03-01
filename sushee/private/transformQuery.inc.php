<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/transformQuery.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class sushee_transformQuery extends RetrieveOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		//executing the commands
		$shell = new Sushee_shell();
		$shell->addCommand($this->firstNode->toString('/*'));
		
		$xslnode = $this->operationNode->getElement('XSL');
		if(!$xslnode){
			$this->setError('No XSL node found in TRANSFORM');
			return false;
		}
		// a path to a file is given
		$xslpath = $xslnode->valueOf('@path');
		if($xslpath){
			$xsl = new KernelFile($xslpath);
		}else{
			// saving the xsl template (found directly in the request) in a temporary file
			$xsl = new TempFile();
			$xsl->save($xslnode->toString('/*'));
		}
		if(!$xsl->exists()){
			$this->setError('XSL file '.$xsl->getPath().' does not exist');
			return false;
		}
		
		// transforming using the temporary file
		$result = $shell->transform($xsl->getCompletePath());
		
		$resultXML = new XML($result);
		if(!$resultXML->loaded){
			$result = encode_to_xml($result);
		}else{
			$result = $resultXML->toString('/',''); // mreoving the xml header
		}
		
		$attributes = $this->getOperationAttributes();
		$this->setXML('<RESULTS'.$attributes.'>'.$result.'</RESULTS>');
		
		return true;
	}
	
}


?>