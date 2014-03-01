<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/deleteLabel.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class sushee_deleteLabel extends NQLOperation{
	
	var $denomination = false;
	var $languageID = false;
	
	function parse(){
		$this->denomination = $this->firstNode->valueOf('@name');
		if (!$this->denomination){
			$this->setError("You haven't set a name for the label you want to delete.");
			return false;
		}
		
		$this->languageID = $this->firstNode->valueOf('@languageID');
		
		return true;
	}
	
	function operate(){
		$db_conn = db_connect();
			
		$sql_cond = 'FROM `labels` WHERE `Denomination`="'.encodeQuote(decode_from_XML($this->denomination)).'"';
		if($this->languageID){
			$sql_cond.=' AND `LanguageID`=\''.encode_for_db($this->languageID).'\'';
		}
		$check_sql = 'SELECT `Denomination` '.$sql_cond.' LIMIT 0,1';
		if(!$db_conn->getRow($check_sql)){
			$this->setError('No label with that name found');
			return false;
		}
		$delete_sql = 'DELETE '.$sql_cond;
		sql_log($delete_sql);
		$res = $db_conn->execute($delete_sql);
		if(!$res){
			$this->setError('Delete failed : '.$db_conn->errorMsg());
			return false;
		}
		$this->setSuccess('Delete successful');
		return true;
	}
	
}

?>
