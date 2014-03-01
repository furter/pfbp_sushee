<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/classcaller.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");


class sushee_PHPClassCaller extends SusheeObject{
	
	var $response = false;
	var $path = false;
	var $className = false;
	var $method = false;
	var $data = array();
	var $constructorData = false;
	
	function sushee_PHPClassCaller($path,$className,$method,$data=array()){
		$this->path = $path;
		$this->className = $className;
		$this->method = $method;
		$this->data[] = $data;
		$this->constructorData = $data;
	}
	
	function setConstructorData($data){
		$this->constructorData = $data;
	}
	
	function setMethodData($data){
		$this->data = array();
		$this->data[] = $data;
	}
	
	function addMethodData($data){
		$this->data[] = $data;
	}
	
	function getClassName(){
		return $this->className;
	}
	
	function getPath(){
		return $this->path;
	}
	
	function getMethod(){
		return $this->method;
	}
	
	function execute(){
		if(!$this->getPath()){
			$this->setError('Path to classfile is empty');
			return false;
		}
		
		$phpfile = new KernelFile($this->getPath());
		if($phpfile->exists()){
			
			if($phpfile->isFolder() || $phpfile->getExtension()!='php'){
				$this->setError('File `'.$this->getPath().'` is not a PHP file');
				return false;
			}
			
			
			// Is it a class with a method to call or a simple PHP script to execute ?
			$classname = $this->getClassName();
			if($classname){
				// checking the classname is not a native sushee class
				$already_included = false;
				// we must first check that the file has not yet been included, otherwise class_exists will return true, but because the file was already included
				$included_files = get_included_files();
				foreach ($included_files as $filename) {
				    if($filename == $phpfile->getCompletePath() || filesize($filename) == filesize($phpfile->getCompletePath())){ // its the same file
						$already_included = true;
					}
				}
				if(!$already_included && class_exists($classname)){
					$this->setError('Classname `'.$classname.'` is already used : please use another classname');
					return false;
				}else{
					// a class with a method to call
					include_once($phpfile->getCompletePath());
					if(!class_exists($classname)){
						$this->setError('Class `'.$classname.'` is not defined in the file `'.$phpfile->getCompletePath().'`');
						return false;
					}else{

						$new_object = new $classname($this->constructorData);
						$method = $this->getMethod();
						if(!$method){
							$this->setError('Method is not defined for processor `'.$phpfile->getCompletePath().'`');
							return false;
						}else{

							if(!method_exists($new_object,$method)){
								$this->setError('Method `'.$method.'` does not exist in the class `'.$classname.'`');
							}else{
								if(sizeof($this->data)==1){
									// faster this way, but only possible with one argument
									$this->response =  $new_object->$method($this->data[0]);
								}else{
									$this->response =  call_user_func_array( array($new_object,$method) , $this->data );
								}
								
								return true;
							}
						}
					}
				}
			}else{
				$this->setError('ClassName is empty ( ClassFile is `'.$this->getPath().'`)');
				return false;
			}
		}else{
			$this->setError('PHP file `'.$phpfile->getCompletePath().'` does not exist');
		}
		return false;
	}
	
	function getResponse(){
		return $this->response;
	}
	
	
}

?>