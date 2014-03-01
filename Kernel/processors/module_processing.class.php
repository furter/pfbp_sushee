<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/module_processing.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/namespace.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');
require_once(dirname(__FILE__).'/../common/nectil_user.class.php');

class sushee_CREATE_MODULE_processor{
	
	function preprocess($data){
		$denomination = $this->firstNode->valueOf('INFO/DENOMINATION');
		if(!$denomination){
			$error = new SusheeProcessorException('No name was provided for the module to be created');
			return $error;
		}
		
		// Secret attribute to create native objects, without namespace and with ID lower than 1024
		$sushee_admin = $this->firstNode->getAttribute('native')==='true';
		if(!$sushee_admin){
			// extracting namespace
			$explosion = explode(':',$denomination);
			$namespace_str = $explosion[0];

			if(sizeof($explosion)==1){
				$error = new SusheeProcessorException('Your module must have a XML namespace. Ex: mycompany:mymodule');
				return $error;
			}

			// checking namespace
			$namespace = new SusheeNamespace($namespace_str);
			if(!$namespace->exists()){
				$error = new SusheeProcessorException('Namespace `'.$namespace_str.'` doesnt exist');
				return $error;
			}
		}
		
		
		$moduleName = strtolower($denomination);
		$moduleName = SQLNameCleaner::execute($denomination);
		// checking there is no module already with this name
		$sql = 'SELECT `ID` FROM `modules` WHERE `Denomination`="'.encodeQuote($moduleName).'";';
		$db_conn = db_connect();
		sql_log($sql);
		$row = $db_conn->getRow($sql);
		if($row){
			$error = new SusheeProcessorException('There is already a module with this denomination in the same namespace');
			return $error;
		}
		
		
		$nextID = $this->getModuleID();
		//forcing the ID
		$this->firstNode->setAttribute('ID',$nextID);
		$infoNode = $this->firstNode->getElement('INFO');
		$denominationNode = $infoNode->getElement('DENOMINATION');
		
		$denominationNode->setValue($moduleName);
		$extends = $this->firstNode->valueOf('INFO/EXTENDS');
		
		if($extends){
			$nativeModule = moduleInfo($extends);
			if(!$nativeModule->loaded){
				$error = new SusheeProcessorException('The Module `'.$extends.'` you want to extend doesnt exist.');
				return $error;
			}
			$table = $nativeModule->getTable();
			//$infoNode->appendChild('<TABLENAME>'.$table->getName().'</TABLENAME>');
			$data->setValue('TABLENAME',$table->getName());
		}else{
			// given tablename ?
			$tableName = $infoNode->valueOf('TABLENAME');
			if(!$tableName){
				$tableName = $moduleName;
				//$infoNode->appendChild('<TABLENAME>'.$moduleName.'</TABLENAME>');
				$data->setValue('TABLENAME',$moduleName);
			}
		}
		//die($this->firstNode->toString());
		
		return true;
	}
	
	function postprocess($data){
		$denomination = $this->firstNode->valueOf('INFO/DENOMINATION');
		$moduleName = strtolower($denomination);
		$infoNode = $this->firstNode->getElement('INFO');
		
		$extends = $this->firstNode->valueOf('INFO/EXTENDS');
		if($extends){
			$nativeModule = moduleInfo($extends);
			$table = $nativeModule->getTable();
			// a boolean field allowing to say that the element is part of the extension
			$booleanField = new TableField(strtolower($moduleName));
			$booleanField->setType('boolean');
			$table->addField($booleanField);
			
			// we added a new field ! 
			$nativeModule = moduleInfo($extends);
			$nativeModule->clearInSession();
			
		}else{
			
			// given tablename ?
			$tableName = $infoNode->valueOf('TABLENAME');
			if(!$tableName){
				$tableName = $moduleName;
			}
			
			// creating the table
			$table = new ModuleDatabaseTable($tableName);
			if($table->exists()){
				$this->setError('Table `'.$tableName.'` already exists');
				return false;
			}
			$table->create();
			
			$db_conn = db_connect();
			// default element, formerly used as template in Officity
			$sql = 'INSERT INTO `'.$table->getName().'`(`ID`,`Activity`) VALUES(\'1\',\'1\');';
			sql_log($sql);
			$db_conn->Execute($sql);
			
			
		}
		// advanced security is a system allowing to have multi owners on a single element
		$advancedSecurity = $this->firstNode->valueOf('INFO/ADVANCEDSECURITY');
		if($advancedSecurity==1){
			$table->enableAdvancedSecurity(true);
		}
		
		// adding a modulekey for the new module
		$shell = new Sushee_Shell();
		
		$user = new Sushee_User();
		$keyring = $user->getKeyring();
		if($keyring){
			$keyringID = $keyring->getID();
		}else{
			$keyringID = 2; // admin keyring
		}
		
		$shell->addCommand(
			'<CREATE disable-processors="true">
				<MODULEKEY>
					<INFO>
						<MODULETOID>'.$data->getID().'</MODULETOID>
						<DENOMINATION>Access to '.$data->getValue('DENOMINATION').'</DENOMINATION>
						<ISPRIVATE>D</ISPRIVATE>
						<FIELDS>
							<ID>W</ID>
							<ACTIVITY>W</ACTIVITY>
						</FIELDS>
					</INFO>
					<DEPENDENCIES>
						<DEPENDENCY type="keyringModuleKeys" mode="reverse">
							<KEYRING ID="'.$keyringID.'"/>
						</DEPENDENCY>
					</DEPENDENCIES>
				</MODULEKEY>
			</CREATE>');
		
		$shell->execute();
		
		return true;
	}
	
	function getModuleID(){
		// taking an ID bigger than 1024, because lower values are reserved for Sushee proprietary objects
		$sql = 'SELECT `ID` FROM `modules`';
		if(!$sushee_admin){
			$sql.= ' WHERE `ID` >= 1024';
		}else{
			$sql.= ' WHERE `ID` < 1024'; // only sushee admins are authorized to use ID lower than 1024 (reserved for sushee native objects)
		}	
		$sql.= ' ORDER BY `ID` DESC';
		sql_log($sql);
		$db_conn = db_connect();
		$row = $db_conn->getRow($sql);
		if($row){
			$nextID = $row['ID'] + 1;
		}else{
			$nextID = 1024;
		}
		return $nextID;
	}
}

class sushee_DELETE_MODULE_processor{
	function preprocess($data){
		return true;
	}

	function postprocess($data){
		$shell = new Sushee_Shell();
		$shell->addCommand(
			'<KILL disable-processors="true"><!-- to avoid processors that would do the same job ? -->
				<MODULEKEY>
					<WHERE>
						<INFO>
							<MODULETOID>'.$data->getID().'</MODULETOID>
						</INFO>
					</WHERE>
				</MODULEKEY>
			</KILL>');
		
		$shell->execute();
		return true;
	}
}

class sushee_KILL_MODULE_processor extends sushee_DELETE_MODULE_processor{}
