<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchTraductions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nectil_user.class.php');
require_once(dirname(__FILE__).'/../common/application.class.php');

define('OFFICIAL_APPLICATION',1);
define('CUSTOM_APPLICATION',2);


class searchTraductions extends RetrieveOperation{
	var $applicationName = false;
	var $languageID = false;
	var $applicationType = OFFICIAL_APPLICATION;
	var $getShared = false;
	
	function parse(){
		$this->applicationName = $this->firstNode->valueOf('APPLICATION[1]');
		if(!$this->applicationName){
			$this->applicationName = $this->firstNode->valueOf('APPLICATION_CUSTOM[1]');
			$this->applicationType = CUSTOM_APPLICATION;
		}
		
		if(!$this->applicationName){
			$this->setError('Missing application name.');
			return false;
		}
		$this->languageID = $this->firstNode->valueOf('LANGUAGEID[1]');
		if(!$this->languageID){
			$request = new Sushee_Request();
			$this->languageID = $request->getLanguage();
		}
		
		$this->getShared = ($this->firstNode->valueOf('@shared')=='true');
		
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		
		$languageID = $this->languageID;
		$application = $this->applicationName;
		
		switch($this->applicationType){
			case OFFICIAL_APPLICATION:
				$app = new OfficialApplication($this->applicationName);
			
				break;
			case CUSTOM_APPLICATION:
				$app = new CustomApplication($this->applicationName);
			
				break;
		}
		if($app){
			$xml.=$app->getTraductionXML($languageID);
		}
		
		
		if($this->getShared){
			$apps = new ApplicationCollection();
			$xml.=$apps->getSharedXML($languageID);
		}
		$xml.='</RESULTS>';
		$this->setXML($xml);
		return true;
	}
}


?>