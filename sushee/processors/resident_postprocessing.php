<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/resident_postprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

	require_once(dirname(__FILE__)."/../common/services.inc.php");

	if ( $requestName == "CREATE" && $values['IsTemplate'] != 1 )
	{
		$db_conn = db_connect(TRUE);
		// drop existing database
		$drop_db_sql = 'DROP DATABASE `'.$values["DbName"].'` ;';
		$db_conn->Execute($drop_db_sql);
		// create the database
		$create_db_sql = 'CREATE DATABASE `'.$values["DbName"].'` ;';
		$db_conn->Execute($create_db_sql);
		// grant access to the user
		$login = '"'.$values['Denomination'].'"@"localhost"';
		$mysql_access = 'GRANT ALL ON '.$values["DbName"].'.* TO '.$login.' IDENTIFIED BY "'.$values['Password'].'"';
		$db_conn->Execute($mysql_access);
	}

	$virtualmin_creation = $GLOBALS['VirtualMinResidents'];
	if ( $virtualmin_creation && $requestName=='CREATE' )
	{
		$sql = 'UPDATE `residents` SET `Activity`=2 WHERE `ID`='.$IDs_array[0];
		$db_conn->execute($sql);
	}

	return true;
