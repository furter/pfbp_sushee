<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/security.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/exception.class.php");
require_once(dirname(__FILE__)."/../common/module.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/application.class.php");
require_once(dirname(__FILE__)."/../common/dependency.class.php");

class SusheeSecurityException extends SusheeException{
	
}

class SusheeSecurity extends SusheeObject{
	
	var $separator = ',';
	
	function checkAuthentication(){
		$user = new NectilUser();
		if(!$user->isAuthentified()){
			throw new SusheeSecurityException('User not connected');
		}
	}
	
	function checkApp($apps){
		// first a general authentication check
		$this->checkAuthentication();
		
		$user = new NectilUser();
		$apps = explode($this->separator,$apps);
		$at_least_one_app = false;
		foreach($apps as $appName){
			$app = new OfficialApplication($appName);
			if($app->exists()){
				if($user->hasApplication($app)){
					$at_least_one_app = true;
				}
			}
		}
		if(!$at_least_one_app){
			throw new SusheeSecurityException('No access to any of these applications');
		}
		return true;
	}
	
	function checkModule($modules){
		// first a general authentication check
		$this->checkAuthentication();
		
		$user = new NectilUser();
		$modules = explode($this->separator,$modules);
		foreach($modules as $moduleName){
			$module = moduleInfo($moduleName);
			if(!$module->exists()){
				throw new SusheeSecurityException('Module `'.$moduleName.'` doesn\'t exist');
			}
			if(!$user->hasModule($module)){
				throw new SusheeSecurityException('User cannot use module `'.$moduleName.'`');
			}
		}
		return true;
	}
	
	function checkField($moduleName,$fieldName,$requiredSecurity='W'){
		// first a general authentication check
		$this->checkAuthentication();
		
		$module = moduleInfo($moduleName);
		$fieldSecurity = $module->getFieldSecurity($fieldName);
		if( ($requiredSecurity == 'R' || $requiredSecurity == 'W') && $fieldSecurity =='W'){
			return true;
		}else if($fieldSecurity=='R' && $requiredSecurity=='R'){
			return true;
		}
		if($fieldSecurity=='R'){
			throw new SusheeSecurityException('Field `'.$fieldName.'` is read-only');
		}else{
			throw new SusheeSecurityException('Field `'.$fieldName.'` is forbidden');
		}
	}
	
	function checkDep($depName,$requiredDepSecurity='W'){
		// first a general authentication check
		$this->checkAuthentication();
		
		
		
		$depType = new dependencyType($depName);
		$moduleOrigin = $depType->getModuleOrigin();
		// first checking we have access to the module
		$this->checkModule($moduleOrigin->getName());
		
		// checking if the service is accessible
		$this->checkService($moduleOrigin->getName(),'dependencies');
		
		$depTypeSecurity = $moduleOrigin->getDepTypeSecurity($depName);
		if($depTypeSecurity=='W'){
			return true;
		}
		if($requiredDepSecurity=='R' && $depTypeSecurity=='R'){
			return true;
		}
		if($depTypeSecurity=='R'){
			throw new SusheeSecurityException('Dependency type `'.$depName.'` is read-only');
		}else{
			throw new SusheeSecurityException('Dependency type `'.$depName.'` is forbidden');
		}
	}
	
	function checkDepType($depName){
		$this->checkDep($depName);
	}
	
	function checkDependency($depName){
		$this->checkDependency($depName);
	}
	
	function checkService($moduleName,$serviceName,$requiredServiceSecurity='W'){
		// canonical form
		$serviceName = strtolower($serviceName);
		
		// first a general authentication check
		$this->checkAuthentication();
		
		$module = moduleInfo($moduleName);
		
		// first checking we have access to the module
		$this->checkModule($moduleName);
		
		$serviceSecurity = $module->getServiceSecurity($serviceName);
		if($serviceSecurity=='W' || ($serviceSecurity=='D' && ($serviceName=='comment' || $serviceName=='comments'))){ // D is Administrator for the comment service
			return true;
		}
		if($requiredServiceSecurity=='R' && $serviceSecurity=='R'){
			return true;
		}
		if($serviceSecurity=='R'){
			throw new SusheeSecurityException('Service `'.$serviceName.'` is read-only');
		}else{
			throw new SusheeSecurityException('Service `'.$serviceName.'` is forbidden');
		}
	}
	
	function checkFunction($appName,$featureName,$requiredFeatureValue){
		// first a general authentication check
		$this->checkAuthentication();
		
		
		// first checking the access to the application
		$this->checkApp($appName);
		
		// new checking for the feature itself (its located in the applicationKey, in the Permissions field)
		$user = new NectilUser();
		$app = new OfficialApplication($appName);
		$key = $user->getApplicationKey($app);
		if($key){
			$security = $key->getField('Permissions');
			$xml = new XMLFastParser($security);
			$featureValue = $xml->valueOf($featureName);
			if($featureValue===false){
				throw new SusheeSecurityException('Function `'.$featureName.'` is forbidden');
			}else if($requiredFeatureValue != $featureValue){
				throw new SusheeSecurityException('Function `'.$featureName.'` is different from "'.$requiredFeatureValue.'"');
			}
			return true;
		}else{
			return true;
		}
	}
}