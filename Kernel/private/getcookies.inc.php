<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getcookies.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nqlOperation.class.php");

class sushee_getCookies extends RetrieveOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		
		$xml.='<RESULTS'.$attributes.'>';
		
		foreach($_COOKIE as $key=>$value){
			$xml.='<COOKIE name="'.encode_to_xml($key).'">'.encode_to_xml($value).'</COOKIE>';
		}
		
		$xml.='</RESULTS>';
		$this->xml = $xml;
		
		
		return true;
	}
	
}

?>