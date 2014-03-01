<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/fileAction.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");
require_once(dirname(__FILE__)."/../file/file_request.inc.php");
require_once(dirname(__FILE__)."/../common/get_xml.inc.php");
session_write_close();

if(!$xml->loaded)
	die( xml_msg("1","0","0",$strResponse));
// checking the request is "signed" -> must have a userID
$userID = $xml->getData("/QUERY/@userID");
if ( !isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) )
{
    die( xml_msg("1","0","0","You're not logged : your session must have expired."));
}

$current_path = '/QUERY/FILEORDIRECTORY[1]';
//$FILEORDIRECTORY_element = get_element_by_tagname($user,'FILEORDIRECTORY');
$FILEORDIRECTORY_element = $xml->getData($current_path);
if( $FILEORDIRECTORY_element===FALSE )
	die( xml_msg("1","-1","-1","XML request invalid : No FILEORDIRECTORY section"));

$query_result = filerequest('',$xml,'FILEORDIRECTORY',$current_path,false,false);
header ("content-type: text/xml");
$strResponse=$query_result;

include_once(dirname(__FILE__)."/../common/output_xml.inc.php");