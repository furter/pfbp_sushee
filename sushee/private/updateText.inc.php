<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateText.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_updateText extends NQLOperation{

	function operate(){

		$concept = $this->firstNode->valueOf();
		$name = $this->firstNode->getAttribute('name');
		$language = $this->firstNode->getAttribute('languageID');

		$db_conn = db_connect();

		// First, to check if a the concept we want to update exists.

		$exist = $db_conn->getRow('SELECT * FROM `thesaurus_text` WHERE `Name`="'.encode_for_db($name).'" AND `LanguageID`="'.encode_for_db($language).'" AND `Native` = "0"'); 

		if($exist){

			// Case 1 : it does exist, we update it

			$handle = $db_conn->execute('UPDATE `thesaurus_text` SET `Text`="'.encode_for_db($concept).'" WHERE `Name`="'.encode_for_db($name).'" AND `LanguageID`="'.encode_for_db($language).'" AND `Native` = "0"');

			if(!($handle==NULL)){
				$this->setSuccess('Update successful');
				return true;
			}else{
				$this->setError('Error while updating');
				return false;
			}

		}
		else{

			// Case 2 : it does NOT exist, we insert it

			$handle = $db_conn->execute('INSERT INTO `thesaurus_text`(`Name`,`Text`,`LanguageID`,`Native`) VALUES ("'.encode_for_db($name).'","'.encode_for_db($concept).'","'.encode_for_db($language).'","0")');

			if(!($handle==NULL)){
				$this->setSuccess('Update successful');
				return true;
			}else{
				$this->setError('Error while updating');
				return false;
			}
		}
		
	}
}

?>