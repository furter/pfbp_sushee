<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/StaticVersion.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");
require_once(dirname(__FILE__)."/../private/static_version.inc.php");
require_once(dirname(__FILE__)."/../private/checkLogged.inc.php");

@set_time_limit(600);
session_write_close();

$params = array();
$params['zip']=$_GET['zip'];
$params['progress']=$_GET['progress'];
$params['languageEncoding']=$_GET['languageEncoding'];
$params['html_extension']=$_GET['html_extension'];
$params['start_url']=$_GET['start_url'];
$params['naming']=$_GET['naming'];

generateStaticVersion($params);