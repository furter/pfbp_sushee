<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createRTF.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/file.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');
require_once(dirname(__FILE__).'/../common/pdf.class.php');

class sushee_createRTF extends RetrieveOperation{
	
	var $template = false;
	
	function parse(){
		
		$this->template = $this->firstNode->getData('@template');
		if(!$this->template){
			$this->setError("You didn't mention any template");
			return false;
		}
		$template_file = new KernelFile($this->template);
		if(!$template_file->exists()){
			$this->setError("The mentioned template `".$this->template."` doesn't exist");
			return false;
		}
		return true;
	}
	
	function operate(){
		// executing the request
		$shell = new Sushee_Shell(false);
		$shell->addCommand($this->firstNode->toString('QUERY/*'));
		
		$response = $shell->execute();

		// generating the rtf
		$rtf_generator = new sushee_RTFGenerator();
		$template_file = new KernelFile($this->template);
		$rtf_generator->setTemplate($template_file->getCompletePath());
		$res = $rtf_generator->execute($response);
		// captured error
		if(!$res){
			$this->setError('Problem encountered while generating RTF : see debug.log for details');
			return false;
		}
		
		// controlling output
		$file = $rtf_generator->getFile();
		if(!$file->exists() || $file->getSize()==0){
			$this->setError('Problem encountered while generating RTF : see debug.log for details');
			return false;
		}
		$xml = '<RESULTS '.$this->getOperationAttributes().'>';
		$xml.='<RTF>'.$file->getPath().'</RTF>';
		$xml.='</RESULTS>';
		
		$this->setXML($xml);
		
		return true;
	}
	
}



?>