<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateModule.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');
require_once(dirname(__FILE__).'/../common/namespace.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');
require_once(dirname(__FILE__).'/../private/update.nql.php');

class updateModule extends NQLOperation{
	
	var $denomination=false;
	var $ID=false;
	
	function parse(){
		$ID = $this->firstNode->valueOf('@ID');
		if(!$ID){
			$this->setError('No name/ID was provided for the module to update');
			return false;
		}
		$this->ID = $ID;
		return true;
	}
	
	function operate(){
		// $this->logFunction('operate');
		$moduleInfo = moduleInfo($this->ID);

		if(!$moduleInfo->loaded){
			$this->setError('Module with ID:"'.$this->ID.'" doesn\'t exist');
			return false;
		}
		$tableName = $this->firstNode->valueOf('INFO/TABLENAME');
		if($tableName){
			$table = $moduleInfo->getTable();
			if($table){
				$res = $table->changeName($tableName);
				if(!$res){
					$this->setError($table->getError());
					return false;
				}
			}else{
				$this->setError('Table of the module couldnot be found');
				return false;
			}
		}

		// managing advanced security (multigroup and multiowners on objects)
		$isAdvancedSecurityEnabled = $moduleInfo->isAdvancedSecurityEnabled();
		$advancedSecurity = $this->firstNode->valueOf('INFO/ADVANCEDSECURITY');
		if($advancedSecurity!==false){

			$table = $moduleInfo->getTable();

			if($table){
				if($advancedSecurity==0 && $isAdvancedSecurityEnabled){
					// removing advanced security
					$table->enableAdvancedSecurity(false);
					// for user in session, we disable the security immediately
					$moduleInfo->enableAdvancedSecurity(false);
					
				}else if($advancedSecurity==1 && !$isAdvancedSecurityEnabled){
					// enabling advanced security
					$table->enableAdvancedSecurity(true);
					// for user in session, we enable the security immediately
					$moduleInfo->enableAdvancedSecurity(true);
				}
			}
		}

		// we need to register the fields before returning the module infos, because fields are automatically created when found on the database
		$moduleInfo->registerFields();

		// processors were formerly a basic table and not attached to the module with a dependency, registerProcessors creates the dependency with the module
		$moduleInfo->registerProcessors();
		
		// letting the usual update in NQL do its job
		$update = new UpdateElement($this->getName(),$this->operationNode);
		$update->execute();
		$this->setMsg($update->getMsg());
		
		// forcing the session to reload the definition of the module to be synchronised with the changes made
		$moduleInfo->clearInSession();

		return true;
	}
}