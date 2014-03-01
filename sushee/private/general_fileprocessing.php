<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/general_fileprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
$moduleName = $moduleInfo->name;

include_once(dirname(__FILE__)."/../file/file_config.inc.php");
include_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	

if ( $requestName=="DELETE" ){
	if ($moduleName=="contact"){
		foreach($IDs_array as $ID){
			global $directoryRoot;
			$target = "/$moduleName/$ID";
			if (file_exists($directoryRoot.$target."/")){
				zip($directoryRoot.$target."/",$directoryRoot.$target.".zip");
				killDirectory($directoryRoot.$target."/");
			}
		}
	}
}
return TRUE;
?>
