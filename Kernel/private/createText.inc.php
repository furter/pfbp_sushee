<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createText.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_createText extends NQLOperation{

	function operate(){

		$concept = $this->firstNode->valueOf();
		$name = $this->firstNode->getAttribute('name');
		$language = $this->firstNode->getAttribute('languageID');

		$db_conn = db_connect();
		
		// Forcing native to 0 because native elements are already in database and only non native texts can be created. Native are reserved for sushee
		$rs = $db_conn->execute('INSERT INTO `thesaurus_text`(`Name`,`LanguageID`,`Text`,`Native`) VALUES("'.encode_for_db($name).'","'.encode_for_db($language).'","'.encode_for_db($concept).'","0")');

		// Creating a string for xml return as an alternative to $this->msg

		$xml = '<RESULTS'.$this->getOperationAttributes().'>';

		$xml.='<TEXT name="'.$name.'" languageID="'.$language.'">'.$concept.'</TEXT>';

		$xml.='</RESULTS>';

		if(!($rs==NULL)){
			$this->setSuccess('Creation successful');
			return true;
		}else{
			$this->setError('Error while creating');
			return false;
		}
	}
}

?>