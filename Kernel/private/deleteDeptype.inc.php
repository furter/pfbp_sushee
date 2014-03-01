<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/deleteDeptype.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class deleteDeptype extends NQLOperation{
	
	var $row;
	
	function parse(){
		$db_conn = db_connect();
		
		// verifying the datas
		$ID = $this->firstNode->getData("DEPENDENCYTYPE[1]/@ID");
		if (!$ID){
			$this->setError("ID of the dependencyType to delete was not given.");
			return false;
		}
		$sql = "SELECT * FROM `dependencytypes` WHERE `ID`=$ID;";
		$row = $db_conn->getRow($sql);
		if (!$row){
			$this->setError("The dependencyType with this ID was not found.");
			return false;
		}
		if($row['IsLocked']=="1"){
			$this->setError("The dependencyType with this ID is locked.");
			return false;
		}
		$this->row = $row;
		return true;
	}
	
	function operate(){
		
		
		$row = $this->row;
		$ID = $row['ID'];
		$returnTypeID = $row["ReturnTypeID"];

		// deleting the dependencies with these dependencyType(start & return)
		$depType = depType($ID);

		$depType->delete();

		$this->setSuccess("The dependencyType was deleted with all the concerned dependencies.");
		
		return true;
	}
}
?>
