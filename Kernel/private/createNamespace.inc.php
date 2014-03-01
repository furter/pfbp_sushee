<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createNamespace.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../private/create.nql.php');
require_once(dirname(__FILE__).'/../common/namespace.class.php');

class sushee_createNamespace extends NQLOperation{
	
	function parse(){
		return true;
	}
	
	function operate(){
		// letting the usual creation in NQL do its job
		$create = new CreateElement($this->getName(),$this->operationNode);
		$create->execute();
		$this->setMsg($create->getMsg());
		
		// cleaning the namespaces saved in session
		$namespaces = new NamespaceCollection();
		$namespaces->clearInSession();
		return true;
	}
	
}
?>