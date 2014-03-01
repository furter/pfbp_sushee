<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getLexicon.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_getLexicon extends RetrieveOperation{

	function operate($node){

		$defaultLanguages = $this->firstNode->getAttribute('languageID');

		$path = $this->firstNode->getAttribute('name');

		$xml = '<RESULTS'.$this->getOperationAttributes().'>';

		$db_conn = db_connect();
		$row = $db_conn->getRow('SELECT `Texts` FROM `thesaurus_lexicon` WHERE `Name` = "'.encode_for_db($path).'"');
		$concepts = $row['Texts'];
		
		// We gained the concepts, that is the texts inside the lexicon. But the string being a comma separated value, we have to extract each value, and then translate it.
		
		// The translatedConcepts function deals with this.

		$string = $this->translatedConcepts($concepts,$defaultLanguages);
		$xml .= $string;

		$xml.='</RESULTS>';

		$this->setXML($xml);

		return true;
	}

	public function translatedConcepts($concepts,$languages){ 

		$db_conn = db_connect();

		$conceptsArray = explode(",",$concepts);
		$concepts = implode("','", $conceptsArray);
		$concepts = "'".$concepts."'";

		if(!$languages){
			$newLanguages="'eng'";
		}
		else{
			$languagesArray = explode(",",$languages);
			$newLanguages = implode("','", $languagesArray);
			$newLanguages = "'".$newLanguages."'";
		}

		$db_conn = db_connect();

		$rs = $db_conn->execute('SELECT `Name`,`Text`,`LanguageID` FROM `thesaurus_text` WHERE `Name` IN ('.encode_for_db($concepts).') AND `LanguageID` IN('.encode_for_db($newLanguages).') AND `Native` = 0 
								UNION
		                    	SELECT `Name`,`Text`,`LanguageID` FROM thesaurus_text WHERE `Name` IN ('.encode_for_db($concepts).') AND `LanguageID` IN('.encode_for_db($newLanguages).') AND `Native` = 1 AND     (`Name`,`LanguageID`) NOT IN (SELECT `Name`, `LanguageID` FROM thesaurus_text WHERE `Name` IN ('.encode_for_db($concepts).') AND `LanguageID` IN('.encode_for_db($newLanguages).') AND `Native` = 0)');

		// If previous request was sucessful, returning xml result

		if($rs){

			if($languages){
								
				// If at least one language was specified

				$retour;
				foreach($rs as $row){$retour .= '<TEXT name="'.encode_to_xml($row['Name']).'" language="'.encode_to_xml($row['LanguageID']).'">'.$row['Text'].'</TEXT>';}
				return $retour;
			}
			else{
				
				// If no language was specified
				
				$retour;
				foreach($rs as $row){$retour .= '<TEXT name="'.encode_to_xml($row['Name']).'">'.$row['Text'].'</TEXT>';}
				return $retour;
			}	
		}
		
		// else returning nothing
		
		else{
			return "";
		}

	}

}



?>