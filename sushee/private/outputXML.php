<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/outputXML.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");

if (!isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'])){
    die("You're not logged");
}

global $slash;
$origin_xml = stripcslashes($_POST['xml_query']);
unset($_POST['xml_query']);
$_GET = $_POST;

$result = query($origin_xml,FALSE,TRUE,TRUE);

if (isset($_POST['template'])) {
	$template = $GLOBALS["nectil_dir"].$slash.stripcslashes($_POST['template']);
	xml_out(transform($result,$template));
} else {
	xml_out($result,$template);
}

?>