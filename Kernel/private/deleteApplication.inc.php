<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/deleteApplication.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');
require_once(dirname(__FILE__).'/../common/namespace.class.php');
require_once(dirname(__FILE__).'/../common/application.class.php');

class deleteApplication extends NQLOperation{
	
	var $denomination;
	
	function parse()
	{
		$denomination = $this->firstNode->valueOf('DENOMINATION');
		if(!$denomination)
		{
			$this->setError('No name was provided for the application to delete');
			return false;
		}
		$this->denomination = $denomination;
		return true;
	}

	function operate()
	{
		$application = new CustomApplication($this->denomination);
		$db_conn = db_connect();

		if(!$application->exists())
		{
			$this->setError('Application "'.$this->denomination.'" doesn\'t exist');
			return false;
		}

		$dev_servers = array('www.officity.com','www.sushee.com');
		if( $application->isNative() && in_array($_SERVER['SERVER_NAME'] , $dev_servers) === false )
		{
			$this->setError('Application "'.$this->denomination.'"  is native and may not be deleted');
			return false;
		}

		$sql = 'DELETE FROM `applications` WHERE `ID`=\''.$application->getID().'\';';
		$db_conn->Execute($sql);

		$namespace = $application->getNameSpace();
		if($namespace)
		{
			$namespace->delete();
		}

		$this->setMsg('Application deleted');
		return true;
	}
}