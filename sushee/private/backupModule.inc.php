<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/backupModule.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');
require_once(dirname(__FILE__).'/../common/nectil_user.class.php');

/*
Backup the table of a module (only INFO at the moment)

<BACKUP>
	<MODULE ID="" />
</BACKUP>

<BACKUP>
	<MODULE denomination="" />
</BACKUP>

<BACKUP>
	<MODULE>
		<INFO>
			<DENOMINATION></DENOMINATION>
		</INFO>
	</MODULE>
</BACKUP>

*/

class sushee_backupModule extends NQLOperation{
	
	var $moduleID = false;
	
	function setModule($moduleInfo){
		$this->moduleID = $moduleInfo->getID();
	}
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
	
	function parse(){
		$ID = $this->firstNode->getAttribute('ID');
		if($ID){
			$moduleInfo = moduleInfo($ID);
		}else{
			$denomination = $this->firstNode->getAttribute('denomination');
			if(!$denomination){
				$denomination = $this->firstNode->valueOf('INFO/DENOMINATION');
			}
			if($denomination){
				$moduleInfo = moduleInfo($denomination);
			}
		}
		if($moduleInfo){
			
			if(!$moduleInfo->loaded){
				$this->setError('Module `'.$moduleInfo->getName().'` does not exist or is forbidden to you');
				return false;
			}
			$this->setModule($moduleInfo);
			return true;
		}
		$this->setError('Please give the ID or the denomination of the module to backup');
		return false;
	}
	
	function operate(){
		$moduleInfo = $this->getModule();
		
		$table = new DatabaseTable(false,$moduleInfo->getTableName());
		$backup_name = $moduleInfo->getTableName().'-'.date('Ymd').'-'.date('H').'h'.date('i').'-'.Sushee_User::getID();
		$res = $table->duplicate($backup_name);
		if(!$res){
			$this->setError('Table duplication failed : '.$table->getError());
			return false;
		}
		
		$this->setSuccess('`'.$moduleInfo->getTableName().'` copied to `'.$backup_name.'`');
		
		return true;
	}
	
}



?>