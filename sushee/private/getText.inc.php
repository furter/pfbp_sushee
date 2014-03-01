<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getText.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_getText extends RetrieveOperation{

	function operate($node){

		// defaultLanguages are the languages specified by the user. If none, then there will be no translation ( i.e. translation to english)

		$defaultLanguages = $this->firstNode->getAttribute('languageID');
		$concepts = $this->firstNode->getAttribute('name');

		$xml = '<RESULTS'.$this->getOperationAttributes().'>';

		// We have to translate every each value we received in $concepts. This translation will be done with only one SQL instruction. 
		// Thus, we need to call for the translatedConcepts function, which will take care of the translation.

		$string = $this->translatedConcepts($concepts,$defaultLanguages);
		$xml .= $string;

		$xml.='</RESULTS>';

		$this->setXML($xml);
		
		return true;
	}
	
public function translatedConcepts($concepts,$languages){ 

			$db_conn = db_connect();

			$xml;

			$conceptsArray = explode(",",$concepts);
			$concepts = implode("','", $conceptsArray);
			$concepts = "'".$concepts."'";
			
			// If no other language, language by default is english
			if(!$languages){
				$newLanguages="'eng'";
			}
			else{
				// splitting languages (comma separated)
				$languagesArray = explode(",",$languages);
				$newLanguages = implode("','", $languagesArray);
				$newLanguages = "'".$newLanguages."'";
			}

			$db_conn = db_connect();

			$rs = $db_conn->execute(
				'SELECT `Name`,`Text`,`LanguageID` FROM `thesaurus_text` WHERE `Name` IN ('.encode_for_db($concepts).') AND `LanguageID` IN ('.encode_for_db($newLanguages).') AND `Native` = 0 
					UNION
				SELECT `Name`,`Text`,`LanguageID` FROM `thesaurus_text` WHERE `Name` IN ('.encode_for_db($concepts).') AND `LanguageID` IN ('.encode_for_db($newLanguages).') AND `Native` = 1 AND (`Name`,`LanguageID`) NOT IN (SELECT `Name`, `LanguageID` FROM `thesaurus_text` WHERE `Name` IN ('.encode_for_db($concepts).') AND `LanguageID` IN ('.encode_for_db($newLanguages).') AND `Native` = 0)');



			if($rs){
				if($languages){
					// returning the language if specific language asked by user
					$retour = '';
					foreach($rs as $row){
						$retour .= '<TEXT name="'.encode_to_xml($row['Name']).'" language="'.encode_to_xml($row['LanguageID']).'">'.$row['Text'].'</TEXT>';
					}
					return $retour;
				}
				else{
					$retour = '';
					foreach($rs as $row){
						$retour .= '<TEXT name="'.encode_to_xml($row['Name']).'">'.$row['Text'].'</TEXT>';
					}
					return $retour;
				}	
			}
			else{
				return "";
			}

		}

	}



?>