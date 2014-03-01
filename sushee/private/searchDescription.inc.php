<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchDescription.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/descriptions.class.php');
class searchDescription extends RetrieveOperation{
	var $ID;
	function parse(){
		$IDs_string = $this->firstNode->valueOf('@ID');
		if ($IDs_string==FALSE){
			$this->setError("No ID was indicated.");
			return false;
		}
		$this->ID = $IDs_string;
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		
		$desc = new Description($this->ID);
		$res = $desc->load();
		if($res){
			$xml.=$desc->getXML();
		}else{
			$this->setError('Description couldn\'t be loaded from database');
			return false;
		}
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
}
?>
