<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/webaccount/webaccount_objects.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

class Sushee_WebaccountObject extends SusheeObject{
	
}

class Sushee_WebaccountFile extends Sushee_WebaccountObject{
	
	var $url;
	var $localPath;
	
	function Sushee_WebaccountFile($url){
		$this->url = $url;
	}
	
	function getSize(){
		
	}
	
	function getURL(){
		return $this->url;
	}
	
	function getExtension(){
		
	}
	
	function getPath(){
		if(!$this->path){
			$this->get();
		}
		return $this->path;
	}
	
	function get(){
		$urlHandler = new URL($this->url);
		
		$fileContent = $urlHandler->execute();
		if(!$fileContent){
			throw new Sushee_WebaccountException('File `'.$this->url.'` could not be retrieved');
		}
		
		$name = $this->getName();
		
		$file = new File('/tmp/'.$name);
		$file->save($fileContent);
		
		$this->path = $file->getPath();
	}
	
	function getName(){
		$lastSlash = strrpos($this->getURL(),'/');
		if($lastSlash){
			$name = substr($this->getURL(),$lastSlash);
		}else{
			$name = 'noname';
		}
		return $name;
	}
	
}
