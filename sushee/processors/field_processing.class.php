<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/field_processing.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

class sushee_DELETE_FIELD_processor{
	
	function preprocess($data){
		$node = $data->getNode();
		
		// taking the ID of the field to delete
		// kill on fields are only possible with the ID, WHERE is too dangerous, because it could modify too much on the database
		$fieldID = $node->valueOf('*[1]/@ID');
		if(!$fieldID && !$node->valueOf('@allow-multiple-delete')==='true'){
			return new SusheeProcessorException('You didnt provide the ID of the field to delete (<WHERE> is forbidden to avoid catastrophe)');
		}
		
		// first getting back the former field informations in the database, in order to work in database
		$fieldModuleElt = new FieldModuleElement($data->getID()); // not using $fieldID directly because in allow-multiple-delete, ID might be not given and a WHERE used
		$fieldModuleElt->loadFields();
		
		// module from which is the field
		$moduleInfo = moduleInfo($fieldModuleElt->getField('Module'));
		// real field in Database
		$fieldname = $fieldModuleElt->getField('FieldName');
		$DBField = $moduleInfo->getField($fieldname);
		
		if($DBField && $DBField->exists()){
			// verifying user doesnt try to delete an important field like ID, Activity, Searchtext, which are vital to the working of the module
			if($DBField->isMandatory()){
				return new SusheeProcessorException('This field cannot be deleted, its deletion would prevent the module from working.');
			}
			$res = $DBField->delete();
			if(!$res){
				$this->setError($DBField->getError());
				return false;
			}
			if($DBField->exists()){
				return new SusheeProcessorException('Problem deleting the field from the table');
			}
		}
		
		return true;
	}
	
	function postprocess($data){
		// KILLING ALL FIELDS OF THE MODULES THAT SHARES THE SAME TABLE
		
		$shell = new Sushee_Shell();
		
		$moduleName = $data->getValue('MODULE');
		$fieldName = $data->getValue('DENOMINATION');
		
		// getting the tablename of the field module 
		$shell->addCommand(
			'<SEARCH>
				<MODULE denomination="'.$moduleName.'"/>
				<RETURN>
					<INFO>
						<TABLENAME/>
					</INFO>
				</RETURN>
			</SEARCH>');
		
		$tableName = $shell->valueOf('/RESPONSE/RESULTS/MODULE/INFO/TABLENAME');
		
		if($tableName && $moduleName && $fieldName){
			// getting all modules sharing the same table
			$shell->reset();
			$shell->addCommand(
				'<SEARCH>
					<MODULE>
						<INFO>
							<TABLENAME op="=">'.$tableName.'</TABLENAME>
							<DENOMINATION op="!=">'.$moduleName.'</DENOMINATION>
						</INFO>
					</MODULE>
					<RETURN>
						<INFO>
							<DENOMINATION/>
						</INFO>
					</RETURN>
				</SEARCH>');

			$modules = $shell->getElements('/RESPONSE/RESULTS/MODULE');

			// killing the field
			foreach($modules as $node){
				$moduleName = $node->valueOf('INFO/DENOMINATION');
				$shell->addCommand(
					'<KILL disable-sushee-processors="true" allow-multiple-delete="true"><!-- disable-sushee-processors to avoid the same processor to be executed recursively -->
						<FIELD>
							<WHERE>
								<INFO>
									<DENOMINATION op="=">'.$fieldName.'</DENOMINATION>
									<MODULE op="=">'.$moduleName.'</MODULE>
								</INFO>
							</WHERE>
						</FIELD>
					</KILL>'
					);
				// also forcing module to reload in session (fields are saved in session for optim)
				$moduleInfo = moduleInfo($moduleName);
				$moduleInfo->clearInSession();
			}
			$shell->execute();

			return true;
		}
		
	}
	
}

class sushee_KILL_FIELD_processor extends sushee_DELETE_FIELD_processor{}

class sushee_SEARCH_FIELD_processor{
	
	function preprocess($data){
		$node = $data->getNode();
		if($node->valueOf('*[1]/@analyse-table')==='true'){
			// analyse the table before searching in the fields table, to have all fields (if user added fields at manually)
			$list = new Vector();
			$moduleNodes = $this->firstNode->getElements('./INFO/MODULE');
			$moduleNodes[]=$this->firstNode->getElement('@module');
			// if no module indicated, handling fields of all modules
			if(sizeof($moduleNodes)==0){
				$list = new modules();
			}else{
				// handling fields of the modules indicated
				foreach($moduleNodes as $node){
					if(is_object($node)){
						$moduleInfo = moduleInfo($node->valueOf());
						if($moduleInfo->loaded){
							$list->add($moduleID,$moduleInfo);
						}
					}
				}
			}
			$db_conn = db_connect();
			while($moduleInfo = $list->next()){
				$moduleInfo->registerFields();
			}
		}
		return true;
	}
	
	function postprocess($data){
		return true;
	}
}