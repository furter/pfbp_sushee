<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/style_functions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once(dirname(__FILE__)."/../common/phpSniff.class.php");
$client =& new phpSniff();

$user_agent=$client->property('ua');
$IP=$client->property('ip');
$browser=$client->property('long_name');
$platform=$client->property('platform');
header("Content-type: text/css");
$last_modified = filemtime(__FILE__);
header("Last-Modified: ".gmdate("D, d M Y H:i:s",filemtime($_SERVER['SCRIPT_FILENAME'])));
?>
