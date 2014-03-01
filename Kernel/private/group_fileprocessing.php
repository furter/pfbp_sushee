<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/group_fileprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
// manage Favorites 
if ( $xml->nodeName($current_path)=="CREATE" || $xml->nodeName($current_path)=="UPDATE" ){
	include_once(dirname(__FILE__)."/../file/file_config.inc.php");
	include_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	if ($values["IsTeam"]=="1"){
		foreach($IDs_array as $ID){
			$target="/group/$ID";
			global $directoryRoot;
			// unzip if zipped in the past
			if (file_exists($directoryRoot.$target.".zip")){
				unzip($directoryRoot.$target.".zip",$directoryRoot."/group/");
				unlink($directoryRoot.$target.".zip");
			}
			if(!is_dir($directoryRoot.$target."/")){
				makedir($directoryRoot.$target."/");
			}
		}
	}else if ( $values["IsTeam"]=="0" ){ // formerly a team and no more a team -> zipping the directory
		foreach($IDs_array as $ID){
			$target = "/group/$ID";
			global $directoryRoot;
			if (file_exists($directoryRoot.$target."/")){
				zip($directoryRoot.$target."/",$directoryRoot.$target.".zip");
				killDirectory($directoryRoot.$target."/");
			}
		}
	}
}
if ( $xml->nodeName($current_path)=="DELETE" ){
	include_once(dirname(__FILE__)."/../file/file_config.inc.php");
	include_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	foreach($IDs_array as $ID){
		$target = "/group/$ID";
		global $directoryRoot;
		if (file_exists($directoryRoot.$target."/")){
			zip($directoryRoot.$target."/",$directoryRoot.$target.".zip");
			killDirectory($directoryRoot.$target."/");
		}
	}
}
return TRUE;
?>
