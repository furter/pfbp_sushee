<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/nectil_object.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");

class SusheeObject{
	function duplicate(){
		if (version_compare(phpversion(), '5.0') === -1)
			return $this;
		else
			return clone($this);
	}
	
	function className(){
		return strtolower(get_class($this));
	}
	
	function log($msg){
		debug_log($msg);
	}
	
	function logSQL($msg){
		sql_log($msg);
	}
	
	function logError($msg){
		errors_log($msg);
	}
	
	function logFunction($msg){
		//if($_GET['log']=='verbose'){
			$this->log($this->className().'.'.$msg);
		//}
	}
	
	function casttoclass($class)
	{
	  return unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', serialize($this)));
	}
	
	function getError(){
		return $this->error;
	}
	
	function setError($error){
		$this->error = $error;
	}
	
	function getMsg(){
		return $this->msg;
	}
	
	function setMsg($msg){
		$this->msg = $msg;
	}
	
	function addMsg($msg){
		$this->msg.=$msg;
	}
	
}

class NectilObject extends SusheeObject{
	
}

class sushee_Object extends SusheeObject{}

?>