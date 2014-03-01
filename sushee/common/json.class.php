<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/json.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/json.pear.php");

class sushee_json extends SusheeObject{

	static function encode($var){
		if (!function_exists('json_encode')){
		    $json = new Services_JSON();
			return $json->encode($var);
		}else{
			return json_encode($var);
		}
	}

	static function decode($json_str){
		if (!function_exists('json_decode')){
		    $json = new Services_JSON();
			return $json->decode($json_str);
		}else{
			return json_decode($json_str);
		}
	}
}

?>