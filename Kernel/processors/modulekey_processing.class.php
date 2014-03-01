<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/modulekey_processing.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

class sushee_CREATEUPDATE_MODULEKEY_processor{
	
	function preprocess($data){
		$node = $data->getNode();
		
		$fieldActivity = $node->valueOf('MODULEKEY/INFO/FIELDS/ACTIVITY');
		$fieldID = $node->valueOf('MODULEKEY/INFO/FIELDS/ID');
		
		// to ensure compatibility with former notation in <FIELDS>
		if(!$data->getNewValue('DELETE')){
			// former notation to authorize delete was <ACTIVITY>W</ACTIVITY>
			if($fieldActivity==='W'){
				$data->setValue('DELETE',1);
			}else if($fieldActivity==='0' || $fieldActivity==='R'){
				$data->setValue('DELETE',0);
			}
		}
		if(!$data->getNewValue('CREATE')){
			// former notation to authorize create was <ID>W</ID>
			if($fieldID==='R' || $fieldID==='0'){
				$data->setValue('CREATE',0);
			}else if($fieldID==='W'){
				$data->setValue('CREATE',1);
			}
		}
		if(!$data->getNewValue('SEARCH')){
			// former notation to authorize create was <ID>W</ID>
			if($fieldID==='0'){
				$data->setValue('SEARCH',0);
			}else if($fieldID==='R' || $fieldID==='W'){
				$data->setValue('SEARCH',1);
			}
		}
		if(!$data->getNewValue('UPDATE')){
			// former notation to authorize create was <ID>W</ID>
			if($fieldID==='0'){
				$data->setValue('UPDATE',0);
			}else if($fieldID==='R' || $fieldID==='W'){
				$data->setValue('UPDATE',1);
			}
		}
		
		return true;
	}
	
	function postprocess($data){
		// if modulekey is the one of the user and he want to test it immediately
		// we need to clean the module in session to make the modulekey modifications active
		$moduleInfo = moduleInfo($data->getValue('MODULETOID'));
		if($moduleInfo->loaded){
			$moduleInfo->clearInSession();
		}
		
		return true;
	}
	
}

class sushee_CREATE_MODULEKEY_processor extends sushee_CREATEUPDATE_MODULEKEY_processor{}
class sushee_UPDATE_MODULEKEY_processor extends sushee_CREATEUPDATE_MODULEKEY_processor{}

