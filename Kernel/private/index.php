<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/index.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
error_reporting ( FATAL | ERROR );
// trying at different levels to be sure to get it
include_once("../common/common_functions.inc.php");
include_once("../sushee/common/common_functions.inc.php");
include_once("./sushee/common/common_functions.inc.php");
include_once("../../common/common_functions.inc.php");
include_once("../../../common/common_functions.inc.php");

echo transform(dir_xml(getcwd()),$GLOBALS["backoffice_dir"]."/private/dir.xsl");

?>
