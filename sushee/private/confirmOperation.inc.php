<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/confirmOperation.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/file.class.php');


class sushee_confirmOperation extends RetrieveOperation{
	
	function parse(){
		$this->elementID = $this->firstNode->getData("@ID");
		if(!$this->elementID){
			$this->setError("No operation ID was given. You must set an attribute called ID, which identifies which operation is to confirm.");
			return false;
		}
		return true;
	}
	
	function operate(){
		$file = new File('/confirm/subquery_'.$this->elementID.'.xml');
		if(!$file->exists()){
			$this->setError("This operation doesn't exist or was already confirmed and already executed. ");
			return false;
		}
		$subquery = $file->toString();
		$file->delete();
		$result = request($subquery,true,false,false,false,$GLOBALS["restrict_language"],$GLOBALS["priority_language"],$GLOBALS["php_request"],$GLOBALS["dev_request"]);
		$this->xml = $result;
		return true;
	}
	
}

?>