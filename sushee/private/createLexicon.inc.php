<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createLexicon.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_createLexicon extends NQLOperation{

	function operate(){

		// Default case : lexicon is entered via ...texts="boris/twitter"

		$collection = $this->firstNode->getAttribute('texts');
		$name = $this->firstNode->getAttribute('name');
		
		// checking it doesnt exist yet
		$db_conn = db_connect();
		$row = $db_conn->getRow('SELECT * FROM `thesaurus_lexicon` WHERE `Name`="'.encode_for_db($name).'"');
		if($row){
			$this->setError('Lexicon with name `'.$name.'` already exists');
			return false;
		}

		// Other case : lexicon is entered via <Text name="cow"></Text>
		// Then, we have to deal with each <Text> node

		if(!$collection){
			
			$xml = '';

			$texts = $this->firstNode->getChildren();

			foreach( $texts as $text ){
				$nom = $text->getAttribute('name');
				$xml .= $nom.',';
			}

			$xml = substr($xml, 0, -1);
			$collection = $xml;
			
		}

		

		

		$handle = $db_conn->execute('INSERT INTO `thesaurus_lexicon`(`Name`,`Texts`) VALUES("'.encode_for_db($name).'","'.encode_for_db($collection).'")');

		// Creating a string for xml return as an alternative to $this->msg
		 
		$collection_Array = explode(",",$collection);

		$xml = '<RESULTS'.$this->getOperationAttributes().'>';

		foreach($collection_Array as $element){

			$xml.='<TEXT name="'.$element.'"></TEXT>';

		}

		$xml.='</RESULTS>';

		if(!($handle==NULL)){
			$this->setSuccess('Creation successful');
			return true;
		}else{
			$this->setError('Error while creating');
			return false;
		}

	}

}

?>