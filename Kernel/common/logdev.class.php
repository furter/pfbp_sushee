<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/logdev.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/date.class.php");


// objects allowing to manipulate and consult a logdev (log for developers, in Files/logdevs/)
class Logdev extends SusheeObject{

	var $path;
	var $offset;
	var $date;
	var $id;
	var $info;
	var $prev;
	var $next;


	function Logdev($path,$offset,$date,$id,$info,$prev,$next){
		$this->path = $path;
		$this->offset = $offset;
		$this->date = $date;
		$this->id = $id;
		$this->prev = $prev;
		$this->next = $next;
		$this->info = $info;
		
	}


	function getPath(){
		return $this->path;
	}


	function getOffset(){
		return $this->offset;
	}

	function getDate(){
		return $this->date;
	}

	function getId(){
		return $this->id;
	}

	function getInfo(){
		return $this->info;
	}

	function getPrev(){
		return $this->prev;
	}

	function getNext(){
		return $this->next;
	}


	function setPath($path){
		$this->path = $path;
	}

	
	function setDate($date){
		$this->date = $date;
	}

	function setId($id){
		$this->id = $id;
	}

	function setInfo($info){
		$this->info = $info;
	}

	function setPrev($prev){
		$this->prev = $prev;
	}

	 function setNext ($next){
		$this->next = $next;
	}
	
	
	function getXML(){
		$xml .= '<LOGDEV>'.$this->newline.'<INFO>'.$this->newline;
		$xml .= '<USERID>'.$this->getId().'</USERID>'.$this->newline;
		$xml .= '<DATE>'.$this->getDate().'</DATE>'.$this->newline;
		$xml .= '<PATH>'.$this->getPath().'</PATH>'.$this->newline;
		$xml .= '<CONTENT>'.$this->getInfo().'</CONTENT>'.$this->newline;
		$xml .= '</INFO>'.$this->newline.'</LOGDEV>'.$this->newline;
		return $xml;
	}
}

?>	