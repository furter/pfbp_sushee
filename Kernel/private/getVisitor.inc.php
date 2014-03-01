<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getVisitor.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nectil_user.class.php');
require_once(dirname(__FILE__).'/../private/search.inc.php');

class getVisitor extends RetrieveOperation{
	
	function parse(){
		return true;
	}

	function operate(){
		$xml = '';
		$user = new NectilUser();

		// copying the return profile from the original request 
		$return = '';
		$returnNode = $this->operationNode->getElement('RETURN');
		if($returnNode){
			$return = $returnNode->toString();
		}
		// composing a new request to take the contact with the visitor ID
		$getContact = new XML(
			'<GET>
				<CONTACT ID="'.$user->getID().'"></CONTACT>
				'.$return.'
			</GET>');
		$search = new SearchElement($this->getName(),$getContact->getElement('/*[1]'));
		$this->xml = $search->execute();
		return true;
	}
}