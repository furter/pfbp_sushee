<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/useful_vars.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/sushee.class.php');

$sushee = new Sushee_Instance();
$sushee->initialize();

// CHECKING DATABASE CONNECTION
$db_conn = db_connect();
$connected = $db_conn->isConnected();
if(!$connected){
	$msg = 'Database connection problem : '.$db_conn->ErrorMsg();
}else{
	// CHECKING SUSHEE VALIDITY (RESIDENT EXPIRATION DATE)
	$valid = $sushee->checkValidity();
	if(!$valid){
		$msg = $sushee->getError();
	}
}


if(!$valid || !$connected){
	die(
		"<html>
			<head>
				<title>$msg</title>
			</head>
			<body style='text-align:center;margin:0 auto;margin-top:50px;font-size:12px;'>
				<div>$msg</div>
			</body>
		</html>");
}

?>