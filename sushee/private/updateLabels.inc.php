<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateLabels.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/translator.class.php');

class sushee_updateLabel extends NQLOperation{
	
	var $denomination = false;
	var $languageID = false;
	
	function parse(){
		$this->denomination = $this->firstNode->valueOf('@name');
		if (!$this->denomination){
			$this->setError("You haven't set a name for the label you want to update.");
			return false;
		}
		
		$this->languageID = $this->firstNode->valueOf('@languageID');
		if (!$this->languageID){
			$this->setError("You haven't put a language for the label you want to update.");
			return false;
		}
		
		return true;
	}
	
	function operate(){
		$db_conn = db_connect();
			
		$sql = 'SELECT * FROM `labels` WHERE `LanguageID`=\''.$this->languageID.'\' AND `Denomination`="'.encodeQuote(decode_from_XML($this->denomination)).'";';
		$row = $db_conn->GetRow($sql);
		$xml_value = $this->firstNode->valueOf();
		
		if($row){
			$update_sql = 'UPDATE `labels` SET `Text`="'.$xml_value.'" WHERE `LanguageID`="'.$this->languageID.'" AND `Denomination`="'.encodeQuote(decode_from_XML($this->denomination)).'";';
		}else{
			$update_sql = 'INSERT INTO `labels`(`LanguageID`,`Denomination`,`Text`) VALUES ("'.$this->languageID.'","'.encodeQuote(decode_from_XML($this->denomination)).'","'.$xml_value.'");';
		}

		$success = $db_conn->Execute($update_sql);
		
		if (!$success){
			$this->setError("Creation failed.*$update_sql*");
			return false;
		}else{
			// adding automatic translations if the attribute translate is positionned in the request
			if($this->firstNode->getAttribute('translate')=='true'){
				$translator = new sushee_translator();
				$translator->setOriginLanguage($this->languageID);
				
				$lgs = $translator->getClassicLanguages();

				foreach($lgs as $lg){
					$translator->setTargetLanguage($lg);
					$translation = $translator->execute(decode_from_xml(UnicodeEntities_To_utf8($xml_value)));
					if($translation){
						$update_sql = 'INSERT INTO `labels`(`LanguageID`,`Denomination`,`Text`) VALUES ("'.$lg->getID().'","'.encodeQuote(decode_from_XML($this->denomination)).'","'.encode_to_xml(utf8_to_unicodeentities($translation)).'");';
						$db_conn->Execute($update_sql);
					}
				}
				
			}
			
			
			$this->setSuccess("Update successful");
		}
		return true;
	}
	
}

?>
