<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getSecurity.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nectil_user.class.php');
require_once(dirname(__FILE__)."/../common/module.class.php");
require_once(dirname(__FILE__)."/../common/application.class.php");

class getSecurity extends RetrieveOperation{

	function parse(){
		return true;
	}

	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml .= '<RESULTS'.$attributes.'>';

		// modules security
		$modulesSecurity = Sushee_Session::getVariable('modulesLightSecurity');
		if(!$modulesSecurity)
		{
			$modules = new Modules();
			$modulesSecurity = $modules->getLightSecurityXML();
			Sushee_Session::saveVariable('modulesLightSecurity',$modulesSecurity);
		}
		$xml .= $modulesSecurity;

		// applications security
		$appsSecurity = Sushee_Session::getVariable('appsLightSecurity');
		if(!$appsSecurity)
		{
			$apps = new ApplicationCollection();
			$appsSecurity = $apps->getLightSecurityXML($languageID);
			Sushee_Session::saveVariable('appsLightSecurity',$appsSecurity);
		}
		$xml .= $appsSecurity;

		$xml .= '</RESULTS>';
		$this->setXML($xml);
		return true;
	}
}