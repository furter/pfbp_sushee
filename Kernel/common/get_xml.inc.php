<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/get_xml.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
// getting the xml from the raw_post_data
if($stringofXML=="" || $stringofXML==NULL )
	$stringofXML = utf8_decode(utf8_To_UnicodeEntities($HTTP_RAW_POST_DATA));

// Logging
query_log($stringofXML);

$stringofXML=trim($stringofXML);

//special patch used for the "SAFARI" bug
if( $stringOfXML!="" && substr($stringofXML,-1) != ">" ){
    $stringofXML.=">";
}

// building a tree with the xmlString
$xml = new XML($stringofXML);
//debug_log("tree from request built");
if (!$xml->loaded)
	$strResponse='<MESSAGE msgType="1">Invalid XML Message</MESSAGE>';

?>
