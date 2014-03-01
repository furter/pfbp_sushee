<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/deleteModule.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');
require_once(dirname(__FILE__).'/../common/namespace.class.php');
require_once(dirname(__FILE__).'/../private/delete.inc.php');

class deleteModule extends NQLOperation
{
	var $denomination;

	function parse()
	{
		$denomination = $this->firstNode->valueOf('INFO/DENOMINATION');
		if(!$denomination)
		{
			$ID = $this->firstNode->valueOf('@ID');
			if(!$ID)
			{
				$this->setError('No name/ID was provided for the module to update');
				return false;
			}
			$this->ID = $ID;
		}
		else
		{
			$this->denomination = $denomination;
		}
		
		return true;
	}
	
	function operate()
	{
		$db_conn = db_connect();
		
		if($this->denomination)
		{
			$moduleInfo = moduleInfo($this->denomination);
			if(!$moduleInfo->loaded)
			{
				$this->setError('Module "'.$this->denomination.'" doesn\'t exist');
				return false;
			}
		}
		else
		{
			$moduleInfo = moduleInfo($this->ID);
			if(!$moduleInfo->loaded)
			{
				$this->setError('Module with ID:"'.$this->ID.'" doesn\'t exist');
				return false;
			}
		}

		$dev_servers = array('www.officity.com','www.sushee.com');
		if( $moduleInfo->isNative() && in_array($_SERVER['SERVER_NAME'] , $dev_servers) === false )
		{
			$this->setError('Module "'.$this->denomination.'"  is native and may not be deleted');
			return false;
		}

		if(!$moduleInfo->isExtension())
		{
			$table = $moduleInfo->getTable();
			$table->delete();
		}
		else
		{
			$table = $moduleInfo->getTable();
			$field = $table->getField(strtolower($moduleInfo->getName()));
			if($field)
			{
				$field->delete();
			}
		}
	
		$shell = new Sushee_Shell();

		// DELETING PROCESSORS
		$shell->addCommand(
			'<KILL>
				<PROCESSOR>
					<WHERE>
						<INFO>
							<MODULEID>'.$moduleInfo->getID().'</MODULEID>
						</INFO>
					</WHERE>	
				</PROCESSOR>
			</KILL>');
		
		// DELETING ONMILINKSTYPE
		$shell->addCommand(
			'<KILL>
				<OMNILINKTYPE>
					<WHERE>
						<INFO>
							<MODULEID>'.$moduleInfo->getID().'</MODULEID>
						</INFO>
					</WHERE>
				</OMNILINKTYPE>
			</KILL>');
		$shell->execute();

		// DELETING DEPENDENCY TYPES
		// STARTING FROM THE MODULE
		$depTypesSet = new DependencyTypeSet($moduleInfo->getID());
		while($depType = $depTypesSet->next()){
			$depType->delete();
		}

		// ENDING AT THE MODULE
		$depTypesSet = new DependencyTypeSet(false,$moduleInfo->getID());
		while($depType = $depTypesSet->next()){
			$depType->delete();
		}

		// DELETING FIELDS DESCRIPTIONS
		$killXML = new XML(
			'<KILL>
				<FIELD>
					<WHERE>
						<INFO>
							<MODULE>'.$moduleInfo->getName().'</MODULE>
						</INFO>
					</WHERE>
				</FIELD>
			</KILL>');

		$killNode = $killXML->getElement('/KILL');
		
		// letting the delete in fields table be done
		$deleteFields = new deleteElement($this->getName(),$killNode);
		$deleteFields->execute();
		
		// letting the delete in fields table be done
		$delete = new deleteElement($this->getName(),$this->operationNode);
		$delete->execute();
		$this->setMsg($delete->getMsg());

		$moduleInfo->clearInSession();
		
		return true;
	}
}