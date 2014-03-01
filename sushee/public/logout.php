<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/public/logout.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/db_functions.inc.php");
require_once(dirname(__FILE__)."/../common/XML.class.php");
require_once(dirname(__FILE__)."/../common/module.class.php");
require(dirname(__FILE__)."/../common/get_xml.inc.php");

//if ($xml->loaded){
	session_cache_expire(24*60);
	resetNectilSession();
	$strResponse='<MESSAGE msgType="0">Logout successful</MESSAGE>';
//}

require_once(dirname(__FILE__)."/../common/output_xml.inc.php");

?>
