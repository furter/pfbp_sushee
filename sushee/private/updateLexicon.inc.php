<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateLexicon.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_updateLexicon extends NQLOperation{

	function operate(){
		
		// Case 1 : the new collection, i.e. the update for the previous collection is inserted via <Lexicon ... texts="I,can,count,to,potatoes" >
		
		$newCollection = $this->firstNode->getAttribute('texts');
		$operator = $this->firstNode->getAttribute('operator');
		$op = $this->firstNode->getAttribute('op');
		$name = $this->firstNode->getAttribute('name');

		// Case 2 : the new collection is inserted via <Text> inside the <Lexicon> tag

		if(!$newCollection){
			
			$xml = '';

			$texts = $this->firstNode->getChildren();

			foreach( $texts as $text ){
				$nom = $text->getAttribute('name');
				$xml .= $nom.',';
			}
			
			// We just created a string "I,can,count,to,potatotes," so we have to remove that last comma
			
			$xml = substr($xml, 0, -1);
			$newCollection = $xml;

		}
		
		// Below are the different ways of signifying that your update will append to the previous lexicon.

		if($operator=="+=" || $operator=="append" || $op=="+=" || $op=="append"){

			// In this case, you have to make sure you are not inserting a value that already exists in you lexicon. Thus, you will need to compare every each value of your previous lexicon ...
			// ... with every each value of your new lexicon, and then insert only values that do not exist in the previous one.

			$db_conn = db_connect();
			if($newCollection){

				$row = $db_conn->getRow('SELECT * FROM `thesaurus_lexicon` WHERE `Name`="'.encode_for_db($name).'"');
				$previousCollection = $row['Texts'];
				if($row){	

					$newCollection_Array = explode(",",$newCollection);
					$previousCollection_Array = explode(",",$previousCollection);
					$updatedCollection = $previousCollection;
					
					// Below we compare the values of the previous lexicon and the values of the updated lexicon

					foreach($newCollection_Array as $newText){

						$toInsert = true;

						foreach($previousCollection_Array as $text){

							if($text==$newText){$toInsert = false;}

						}

						if ($toInsert){$updatedCollection .= ','; $updatedCollection .= $newText;}

					}
					
					$newCollection = $updatedCollection;

				}
			}
		}
		
		$db_conn= db_connect();
		$handle = $db_conn->execute('UPDATE `thesaurus_lexicon` SET `Texts` = "'.encode_for_db($newCollection).'" WHERE `Name`="'.encode_for_db($name).'"');
		if(!($handle==NULL)){
			$this->setSuccess('Update successful');
			return true;
		}else{
			$this->setError('Error while updating');
			return false;
		}
	}

}

?>