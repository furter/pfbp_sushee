<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/console.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

class XMLConsole{
	var $vector;
	function XMLConsole(){
		$this->vector = array();
	}
	
	function addMessage($message){
		$this->vector[]= $message;
	}
	
	function getXML(){
		$xml = '';
		foreach($this->vector as $elt)
			$xml.=$elt;
		return $xml;
	}
}

class LogConsole{
	var $vector;
	function LogConsole(){
		$vector = array();
	}
	
	function addMessage($message){
		$vector[]= $message;
		//debug_log($message);
	}
	
	function getXML(){
		$xml = '';
		foreach($vector as $elt)
			$xml.=encode_to_xml($elt);
		return $xml;
	}
}

?>