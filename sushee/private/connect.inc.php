<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/connect.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once dirname(__FILE__)."/../common/nectil_user.class.php";
include_once dirname(__FILE__)."/../common/nqlOperation.class.php";

class SusheeConnectOperation extends NQLOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		$user = new NectilUser();
		
		$login = $this->operationNode->valueOf('/LOGIN');
		$password = $this->operationNode->valueOf('/PASSWORD');
		
		$res = $user->authenticate($login,$password);
		if($res){
			$this->setSuccess('Visitor connected');
		}else{
			$this->setError('Login or password erroneous');
		}
		return $res;
	}
	
}

class SusheeDisconnectOperation extends NQLOperation{
	function parse(){
		
		return true;
	}
	
	function operate(){
		$user = new NectilUser();
		
	    $user->logout();
		$this->setSuccess('Visitor disconnected');
		return true;
	}
}

?>