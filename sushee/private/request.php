<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/request.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../private/request_function.inc.php");
//---------------------------------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------------------------------
//  request.php
//  This page dispatches the requests
//---------------------------------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------------------------------
error_reporting(E_ERROR | /*E_WARNING |*/ E_PARSE);
session_write_close();
if(isset($_POST['NQL'])){
	$HTTP_RAW_POST_DATA = stripcslashes($_POST['NQL']);
}
if(isset($_GET['NQL'])){
	$HTTP_RAW_POST_DATA = stripcslashes($_GET['NQL']);
}
	
xml_out(request(utf8_decode(utf8_To_UnicodeEntities($HTTP_RAW_POST_DATA))));
?>
