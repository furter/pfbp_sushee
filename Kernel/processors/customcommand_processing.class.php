<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/customcommand_processing.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/


class sushee_CREATE_CUSTOMCOMMAND_processor{
	
	function preprocess($data){
		
		$operation = $data->getValue('OPERATION');
		if(!$operation){
			return new SusheeProcessorException('`OPERATION` is empty.');
		}
		$target = $data->getValue('TARGET');
		if(!$target){
			return new SusheeProcessorException('`TARGET` is empty.');
		}
		
		$path = $data->getValue('PATH');
		if(!$path){
			return new SusheeProcessorException('`PATH` is empty.');
		}
		$processor_classfile = new KernelFile($path);
		if(!$processor_classfile->exists()){
			return new SusheeProcessorException('File `'.$path.'` does not exist.');
		}
		if($processor_classfile->getExtension()!='php'){
			return new SusheeProcessorException('File `'.$path.'` is not a PHP file.');
		}
		
		return true;
	}
	
	function postprocess($data){
		return true;
	}
	
}