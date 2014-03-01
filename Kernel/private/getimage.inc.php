<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getimage.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/image.class.php');
class getImage extends RetrieveOperation{
	
	var $image = false;
	
	function parse(){
		$path = $this->firstNode->valueOf('@path');
		$this->image = new Image($path);
		if(!$this->image->exists()){
			$this->setError('File '.$path.' doesn\'t exist');
			return false;
		}
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		$xml.=		'<IMAGE path="'.$this->image->getPath().'" name="'.$this->image->getShortName().'"';
		if($this->image->getExtension()){
			$xml.=' ext=".'.$this->image->getExtension().'"';
		}
		$xml.='>';
		$xml.=		'<INFO>';
		$xml.=$this->image->getInfoXML();
		$xml.=		'</INFO>';
		$xml.=		'</IMAGE>';
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
}
?>