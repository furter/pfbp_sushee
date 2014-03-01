<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/downloadFile.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/url.class.php');
require_once(dirname(__FILE__).'/../common/file.class.php');

class sushee_downloadFile extends RetrieveOperation{
	
	var $from = false;
	var $to = false;
	
	function parse(){
		
		$this->from = $this->firstNode->valueOf('FROM');
		if(!$this->from){
			$this->from = $this->firstNode->valueOf('@from');
		}
		
		$this->to = $this->firstNode->valueOf('TO');
		if(!$this->to){
			$this->to = $this->firstNode->valueOf('@to');
		}
		
		if(!$this->from){
			$this->setError('Missing node `FROM` representing the URL of the file you want to download');
			return false;
		}
		
		if(!$this->to){
			$this->setError('Missing node `TO` representing the folder where you want to download the file');
			return false;
		}
		
		return true;
	}
	
	function operate(){
		$url = new URL($this->from);
		$file_in_str = $url->execute();
		if(!$file_in_str){
			$this->setError('File at `'.$this->from.'` could not be downloaded');
			return false;
		}
		$default_filename = 'untitled.html';
		// use can give a folder (ending by slash) or the complete file path
		if(substr($this->to,-1)=='/'){
			$folder = new Folder($this->to);
			if(!$folder->exists()){
				$folder->create();
			}
			// getting the last part of the url to know what the filename should be
			$filename = $url->getFilename();
			if(!$filename){
				$filename = $default_filename;
			}
			$file = $folder->getChild($filename);
		}else{
			$file = new File($this->to);
			// if asked file  is actually a folder (we believed it was a file because no ending slash)
			if($file->isDirectory()){
				$folder = $file->casttoclass('Folder');
				$filename = $url->getFilename();
				if(!$filename){
					$filename = $default_filename;
				}
				$file = $folder->getChild($filename);
			}
		}
		if($file->isForbidden()){
			$this->setError('Not authorized to write this type of files for security reasons (ex: PHP files, Apache files)');
			return false;
		}
		
		// checking security
		$right =  $file->getSecurity();
		if ($right!=="W"){
			$this->setError("Not authorized to download the file at this place (`".$file->getPath()."`).");
			return false;
		}
		// saving the content of the file on the disk
		$file->save($file_in_str);
		
		
		$this->setXML('<RESULTS'.$this->getOperationAttributes().'>'.$file->getXML().'</RESULTS>');
		
		return true;
	}
	
}


?>