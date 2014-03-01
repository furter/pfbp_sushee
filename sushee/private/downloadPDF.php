<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/downloadPDF.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");

/*if ( !isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) ){
	echo "You're not logged";
	die();
}*/
session_write_close();
if(!isset($_GET['template']))
	$_GET['template'] = 'simple.xsl';
if (/*isset($_GET['languageID']) &&*/ isset($_GET['ID']) && isset($_GET['module']) && isset($_GET['template'])){
	$moduleInfo = moduleInfo($_GET['module']);
	$GLOBALS['no_variable_nectil_vars']=true;
	//languageID="'.$_GET['languageID'].'"
	$result = query('<QUERY><GET refresh="daily"><'.strtoupper($moduleInfo->name).' ID="'.$_GET['ID'].'"><WITH depth="all"><DESCRIPTIONS/></WITH></'.strtoupper($moduleInfo->name).'></GET></QUERY>',FALSE,TRUE,TRUE);
	if(!isset($_GET['output']))
		$_GET['output'] = 'pdf';
	$template = $GLOBALS["library_dir"].$moduleInfo->name.$slash."pdf".$slash.$_GET['template'];
	
	transform_to_pdf($result,$template);
}else{
	xml_out("<?xml version='1.0'?><RESPONSE>".generateMsgXML(1,"Parameter missing.")."</RESPONSE>");
}
?>
