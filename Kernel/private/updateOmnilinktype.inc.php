<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateOmnilinktype.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../private/update.nql.php');

class sushee_updateOmnilinktype extends NQLOperation{
	
	function parse(){
		// taking the ID of the type to update
		$this->elementID = $this->firstNode->valueOf('@ID');
		if(!$this->elementID){
			$this->setError('You didnt provide the ID of the type to update');
			return false;
		}
		return true;
	}
	
	function operate(){
		
		// type definition
		$type = sushee_OmnilinkType($this->getElementID());
		
		$denomination = $this->firstNode->valueOf('/INFO/DENOMINATION');
		$other_type = sushee_OmnilinkType($denomination);
		if($other_type->loaded){
			$this->setError('A type with the same denomination `'.$denomination.'` already exists');
			return false;
		}
		
		// updating in database
		$delete = new updateElement($this->getName(),$this->getOperationNode());
		$delete->execute();
		$this->setMsg($delete->getMsg());
		
		// updating the type in the session also
		$type->clearInSession();
		
		return true;
	}
	
}



?>