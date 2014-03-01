<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/emptyCache.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../private/emptyCache.inc.php");

// command executed asynchronously on the command line to empty cache

$module = $argv[1]; // first argument on the commandline after the script filename
$elementID = $argv[2]; // first argument on the commandline after the script filename
$regex = $argv[3]; // first argument on the commandline after the script filename
$tool = $argv[4]; // first argument on the commandline after the script filename

$cleaner = new sushee_emptyCache_asynchronous();
$cleaner->setModule($module);
$cleaner->setElementID($elementID);
$cleaner->setRegex($regex);
$cleaner->setRegex($tool);

$cleaner->execute();

?>