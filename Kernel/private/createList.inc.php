<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createList.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_CreateList extends NQLOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		$db_conn = db_connect();
		
		$listName = $this->firstNode->valueOf('@name');
		$listDomain = $this->firstNode->valueOf('@domain');
		$languageID = $this->firstNode->valueOf('@languageID');
		
		$itemsNodes = $this->firstNode->getElements('ITEM');
		$order = 0;
		foreach($itemsNodes as $itemNode){
			$order++;
			$itemValue = $itemNode->valueOf('@value');
			$itemLabel = $itemNode->valueOf('@label');
			
			$listName = encode_for_db(decode_from_xml($listName));
			$listDomain = encode_for_db(decode_from_xml($listDomain));
			$itemValue = encode_for_db(decode_from_xml($itemValue));
			$itemLabel = encode_for_db(decode_from_xml($itemLabel));
			$languageID = encode_for_db(decode_from_xml($languageID));
			
			// check the row doesnt exist yet
			$sql = 'SELECT `Name` FROM `lists` WHERE `Name`="'.$listName.'" AND Domain = "'.$listDomain.'" AND Value = "'.$itemValue.'";';
			sql_log($sql);
			$row = $db_conn->getRow($sql);
			if($row){
				$this->setError('List item already exists');
				return false;
			}
			
			$sql = 'INSERT INTO `lists`(`Name`,`Domain`,`Value`,`Label`,`LanguageID`,`Ordering`) VALUES("'.$listName.'", "'.$listDomain.'", "'.$itemValue.'", "'.$itemLabel.'","'.$languageID.'",\''.$order.'\')';
			sql_log($sql);
			$res = $db_conn->execute($sql);
			
			if(!$res){
				$this->setError($db_conn->ErrorMsg());
				return false;
			}
		}
		$this->setSuccess('List created');
		
		return true;
	}
}

