<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/getTree.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/XML.class.php");
require_once(dirname(__FILE__)."/file_functions.inc.php");
require_once(dirname(__FILE__)."/file_config.inc.php");

//session_start();
require_once("../common/get_xml.inc.php");

// checking the request is "signed" -> must have a userID
$userID = $xml->getData("/QUERY/@userID");
if ( $userID==FALSE ){
    die( xml_msg("1","0","0","XML request invalid."));
}
if ( !isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) ){
    die( xml_msg("1","0","0","You're not logged : your session must have expired."));
}


//$pathRequest="/";
$pathRequest=$xml->getData("/QUERY/PATH[1]");
if( $pathRequest===false )
	die( xml_msg("1","-1","-1","XML request invalid : No PATH section"));
	

$strResponse = getTree("",$pathRequest);

include_once(dirname(__FILE__)."/../common/output_xml.inc.php");
?>