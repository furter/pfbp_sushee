<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateDeptype.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');

class updateDepType extends NQLOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		$db_conn = db_connect();
		
		// looking every deptype in the entity (and entity is a deptype and its return type, if it exists)
		$depType_array = $this->firstNode->getElements("DEPENDENCYTYPE");
		foreach($depType_array as $depTypeNode){
			$ID=$depTypeNode->getData("@ID");
			if ($ID!=FALSE){
				// parsing the fields
				$domain = $depTypeNode->valueOf("DOMAIN[1]");
				$temporal = $depTypeNode->valueOf("TEMPORAL[1]");
				$description = $depTypeNode->valueOf("DESCRIPTION[1]");
				$tableName = $depTypeNode->valueOf("TABLENAME[1]");
				$config = $depTypeNode->toString("CONFIG[1]/*");
				
				// composing the SQL
				$fields = '';
				if($domain!==false){
					$fields.=',`Domain`="'.encode_for_DB($domain).'"';
				}
				if($temporal!==false){
					$fields.=',`Temporal`="'.encode_for_DB($temporal).'"';
				}
				if($description!==false){
					$fields.=',`Description`="'.encode_for_DB($description).'"';
				}
				if($tableName!==false){
					$fields.=',`TableName`="'.encode_for_DB($tableName).'"';
					
					// looking if the tablename is new
					$depType = depType($ID);
					if($depType->getTableName()!=$tableName){
						// moving the table
						$table = new DependenciesTable($tableName);
						$table->create();
						
						// only one side of the dep is saved, the other is deduced from the other
						if($depType->isSavedInDatabase()){
							// copying the content of the actual table to the new one
							$actualTable = $depType->getTable();
							$sql = 'INSERT INTO '.$table->getSQLName().' SELECT * FROM '.$actualTable->getSQLName().' WHERE `DependencyTypeID`=\''.$depType->getID().'\'';
							$db_conn->execute($sql);
						}
						
						
						//changing tablename in session, this way the user can use it immediately
						$depType->setTablename($tableName);
						
						// changing tablename of returntype too, because with bidirectional deptypes, the dep is only saved once
						if($depType->returnIsDependency() && !$depType->isUTurn()){
							
							$returnType = $depType->getReturnType();
							$sql = 'UPDATE `dependencytypes` SET `TableName`="'.encode_for_DB($tableName).'" WHERE `ID` = \''.$returnType->getID().'\'';
							$db_conn->execute($sql);
							
							// only one side of the dep is saved, the other is deduced from the other
							if($returnType->isSavedInDatabase()){
								// copying the content of the actual table to the new one
								$actualTable = $returnType->getTable();
								$sql = 'INSERT INTO '.$table->getSQLName().' SELECT * FROM '.$actualTable->getSQLName().' WHERE `DependencyTypeID`=\''.$returnType->getID().'\'';
								$db_conn->execute($sql);
							}
							
							//changing tablename in session, this way the user can use it immediately
							$returnType->setTablename($tableName);
						}
						
						
					}
				}
				if($config!==false){
					$fields.=',`Config`="'.encode_for_DB($config).'"';
				}
				// removing the first comma
				$fields = substr($fields,1);
				
				// updating 
				$sql = "UPDATE `dependencytypes` SET ".$fields." WHERE `ID`='$ID';";
				sql_log($sql);
				$db_conn->Execute($sql);
				
				
				// modifying the traductions
				
				$label_array = $depTypeNode->getElements("DENOMINATION/LABEL");
				foreach($label_array as $labelNode){
					$trad = $labelNode->getData();
					$languageID = $labelNode->getData("@languageID");
					$searchLabel = $labelNode->valueOf('../SEARCHLABEL[@languageID="'.$languageID.'"]');
					
					// first deleting
					$sql = "DELETE FROM `dependencytraductions` WHERE `DependencyTypeID`='$ID' AND `LanguageID` = \"".$languageID."\";";
					$db_conn->Execute($sql);
					
					if($trad || $searchLabel){
						$sql = "INSERT INTO `dependencytraductions` (`DependencyTypeID`,`LanguageID`,`Text`,`SearchLabel`) VALUES($ID,\"$languageID\",\"".encode_for_DB($trad)."\",\"".encode_for_DB($searchLabel)."\");";
						$db_conn->Execute($sql);
					}
				}
			}else{
				$this->setError('No ID for the dependency type to modify');
				return false;
			}
		}
		$this->setSuccess("Dependency type modification successful.");
		return true;
	}
	
}
?>
