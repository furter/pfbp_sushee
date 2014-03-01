<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/generatePDF.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");

//require_once(dirname(__FILE__)."/../private/checkLogged.inc.php");

$origin_xml = stripcslashes($_POST['xml_query']);
$template = $GLOBALS["nectil_dir"].$slash.stripcslashes($_POST['template']);

unset($_POST['xml_query']);
$_GET = $_POST;

$result = query($origin_xml,FALSE,TRUE,TRUE);

$filename = transform_to_pdf($result,$template,false);

if($filename === false)
	$query_result = generateMsgXML(1,"Generation of PDF failed for unknown reason",0);
else{
	if(substr($filename,0,strlen($GLOBALS["directoryRoot"]))==$GLOBALS["directoryRoot"] )
		$filename = substr($filename,strlen($GLOBALS["directoryRoot"]));
	$query_result = '<FILE>'.$filename.'</FILE>';
}
xml_out('<?xml version="1.0"?><RESPONSE>'.$query_result.'</RESPONSE>');
?>
