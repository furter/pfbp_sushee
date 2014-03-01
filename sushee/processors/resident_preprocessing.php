<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/resident_preprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

	if ( $requestName=="CREATE" )
	{
		// only lowercase
		$values['Denomination'] = strtolower($values['Denomination']);

		// only ascii characters
		$values['Denomination'] = filter_var($values['Denomination'] , FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

		// only 16 chars max
		$values['Denomination'] = substr($values['Denomination'] , 0 , 16);
		
		// pre-check unicity
		$check_unicity = 'SELECT * FROM `'.$moduleInfo->tableName.'` WHERE `Denomination`="'.$values['Denomination'].'";';
		$row = $db_conn->getRow($check_unicity);
		if($row)
			return generateMsgXML(1,"A resident with this name already exists.",0,'',$name);

		// generating a password
		if(!isset($values["Password"]) || $values["Password"] == '')
			$return_values['Password'] = $values["Password"] = generate_password(8,1,'L');

		if($GLOBALS['VirtualMinResidents'])
			$values["URL"]='http://'.$values['Denomination'].'.officity.com';
		else
			$values["URL"]='http://'.$GLOBALS["NectilMasterURL"]."/Residents/".$values['Denomination'];
	}

	if(!isset($values['DbName']))
		$values['DbName'] = $values['Denomination'];

	if($requestName=='DELETE' || $requestName=='KILL')
	{
		require_once(dirname(__FILE__)."/../common/nectil_element.class.php");

		$resident = new Resident($values);
		$resident->clean();
	}

	return true;
