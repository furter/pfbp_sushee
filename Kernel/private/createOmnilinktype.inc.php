<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createOmnilinktype.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../private/delete.inc.php');

class sushee_createOmnilinkstype extends NQLOperation{
	
	var $moduleID;
	var $denomination;
	var $tablename;
	
	function parse(){
		
		$this->moduleID = $this->firstNode->valueOf('/INFO/MODULEID');
		$this->denomination = $this->firstNode->valueOf('/INFO/DENOMINATION');
		$this->tablename = $this->firstNode->valueOf('/INFO/TABLENAME');
		
		if(!$this->moduleID){
			$this->setError('No moduleID was given');
			return false;
		}
		
		$type = sushee_OmnilinkType($this->denomination);
		if($type->loaded){
			$this->setError('A type with the same denomination `'.$this->denomination.'` already exists');
			return false;
		}
		
		return true;
	}
	
	function operate(){
		
		// creating in database
		$create = new createElement($this->getName(),$this->getOperationNode());
		$create->execute();
		$this->setMsg($create->getMsg());
		
		// type definition
		$type = sushee_OmnilinkType($create->getElementID());
		
		// clearing the type of the same module in session
		$types = new sushee_OmnilinkTypeSet($type->getModule()->getID());
		$types->clearInSession();
		
		return true;
	}
	
}



?>