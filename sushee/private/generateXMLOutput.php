<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/generateXMLOutput.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/searchoutputmanager.class.php");

function generateXMLOutput(&$main_rs,&$moduleInfo,$profile,$depth=false,$elements_for_depth = array())
{
	$must_get_files = is_array($GLOBALS['used_Files']);
	$manager = new Sushee_SearchOutputManager();
	$manager->setModule($moduleInfo->ID);
	$manager->setResultSet($main_rs);
	$manager->setProfileConfig($profile);
	$manager->setDepth($depth);

	if($must_get_files)
		$manager->setFilesCollect(true);

	$xml = $manager->getXML();

	if($must_get_files)
		$GLOBALS['used_Files'] = $manager->getFiles();

	return $xml;
}