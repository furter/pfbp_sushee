<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/processor_processing.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

class sushee_PROCESSOR_processor{
	
	function postprocess($data){
		// cleaning the session
		$cmd = $data->getValue('COMMAND');
		$type = $data->getValue('TYPE');
		$moduleInfo = moduleInfo($data->getValue('MODULEID'));
		$varName = 'dbproc'.$moduleInfo->getName().$cmd[0].substr($type,0,3);
		Sushee_Session::clearVariable($varName);
	}
	
}

class sushee_CREATE_PROCESSOR_processor extends sushee_PROCESSOR_processor{
	
	function preprocess($data){
		// allowing to set a MODULE, not knowing the real MODULEID
		$moduleName = $data->getElementNode()->valueOf('INFO/MODULE');
		if($moduleName){
			$moduleInfo = moduleInfo($moduleName);
			if($moduleInfo->getID()){
				$data->setValue('MODULEID',$moduleInfo->getID());
			}
		}
		
		// checking there is a module
		if(!$data->getValue('MODULEID')){
			return new SusheeProcessorException('The MODULEID field must be defined for a processor to work');
		}
		// checking there is a type and its valid
		$type = $data->getValue('TYPE');
		if($type!='preprocessor' && $type!='postprocessor' && $type!='searchtext'){
			return new SusheeProcessorException('The TYPE field must be `preprocessor` or `postprocessor` or `searchtext`');
		}
		
		$path = $data->getValue('PATH');
		if(!$path){
			return new SusheeProcessorException('`PATH` is empty.');
		}
		$processor_classfile = new KernelFile($path);
		
		// doing the existence check only if the path is absolute. if its relative, it might be in different environments, and we dont know the environment at this stage
		if( $path[0] == '/' && !$processor_classfile->exists() ){
			
			return new SusheeProcessorException('File `'.$path.'` does not exist.');
		}
		
		if($processor_classfile->getExtension()!='php'){
			return new SusheeProcessorException('File `'.$path.'` is not a PHP file.');
		}
		
		// if no default env, setting the default env at the prod directory. This way processors can also work in /Library and /Public
		if(!$data->getValue('DEFAULTENV')){
			$shell = new Sushee_Shell();
			$shell->addCommand(
				'<SEARCH>
					<OFFICITY:ENVIRONMENT type="production"/>
					<RETURN>
						<INFO>
							<FOLDER/>
						</INFO>
					</RETURN>
				</SEARCH>');
			$prodEnvFolder = $shell->valueOf('/RESPONSE/RESULTS/OFFICITY:ENVIRONMENT/INFO/FOLDER');
			if($prodEnvFolder){
				$prodEnvFolder = '/'.$prodEnvFolder.'/';
			}
			
			$data->setValue('DEFAULTENV',$prodEnvFolder);
		}
		
		return true;
	}
	
}

class sushee_UPDATE_PROCESSOR_processor extends sushee_PROCESSOR_processor{
	
	function preprocess($data){
		return true;
	}
}

class sushee_KILL_PROCESSOR_processor extends sushee_PROCESSOR_processor{
	
	function preprocess($data){
		return true;
	}
	
}

class sushee_DELETE_PROCESSOR_processor extends sushee_PROCESSOR_processor{
	
	function preprocess($data){
		return true;
	}
	
}

class sushee_SEARCH_PROCESSOR_processor{
	
	function preprocess($data){
		// allowing to search with the module name and not only with the moduleID
		$moduleName = $data->getElementNode()->valueOf('INFO/MODULE');
		if($moduleName){
			$moduleInfo = moduleInfo($moduleName);
			if($moduleInfo->getID()){
				$data->getElementNode()->getElement('INFO')->appendChild('<MODULEID>'.$moduleInfo->getID().'</MODULEID>');
			}
		}
		return true;
	}
	
}

?>