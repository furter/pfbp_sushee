<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/susheesession.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");

class Sushee_Session extends SusheeObject{

	static function getVariable($name){
		if(isset($_SESSION[$GLOBALS["nectil_url"]]['sushee'][$name])){
			return $_SESSION[$GLOBALS["nectil_url"]]['sushee'][$name];
		}
		return false;
	}

	static function saveVariable($name,$value){
		session_start();
		$_SESSION[$GLOBALS["nectil_url"]]['sushee'][$name] = $value;
	}

	static function clearVariable($name){
		session_start();
		unset($_SESSION[$GLOBALS["nectil_url"]]['sushee'][$name]);
	}

	static function clearVariableStartingWith($prefix){
		session_start();
		foreach($_SESSION[$GLOBALS["nectil_url"]]['sushee'] as $name=>$value){
			if(substr($name,0,strlen($prefix))==$prefix){
				unset($_SESSION[$GLOBALS["nectil_url"]]['sushee'][$name]);
			}
		}
	}	
}