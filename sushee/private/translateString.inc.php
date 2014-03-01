<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/translateString.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/translator.class.php');

class sushee_translateString extends RetrieveOperation{
	
	var $orig_lg = false;
	var $target_lg = false;
	
	function parse(){
		$this->orig_lg = $this->firstNode->valueOf('@from');
		$this->target_lg = $this->firstNode->valueOf('@to');
		if(!$this->orig_lg){
			$this->setError('No source language defined (attribute `from`)');
			return false;
		}
		if(!$this->target_lg){
			$this->setError('No target language defined (attribute `to`)');
			return false;
		}
		
		return true;
	}
	
	function operate(){
		
		$translator = new sushee_translator();
		$translator->setOriginLanguage($this->orig_lg);
		$translator->setTargetLanguage($this->target_lg);
		$translation = $translator->execute($this->firstNode->valueOf());
		
		$attributes = $this->getOperationAttributes();		
		
		$this->setXML('<RESULTS'.$attributes.'><STRING from="'.$this->orig_lg.'" to="'.$this->target_lg.'">'.encode_to_xml($translation).'</STRING></RESULTS>');
		
		return true;
	}
	
}
?>