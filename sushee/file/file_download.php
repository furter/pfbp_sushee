<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_download.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");

global $slash;
global $directoryRoot;
session_write_close();
if(isset($_GET["target"]))
	$target = transformPath($_GET["target"]);

//check if target exist
if (isset($target)){
    //check security for this target
	$right = 'R';
	$subdir = substr($target,0,5);
	if($subdir!='/pdf/' && $subdir!='/tmp/')
		$right =  getPathSecurityRight($target);
    
    if($right===0){
		debug_log("Access to this directory refused ".$target);
        htmlErrorMsg("Download error","Access to this directory refused");
    }
    if(!hidecheck($target)){
		debug_log("This file is blocked. ".$target);
        htmlErrorMsg("Download error","This file is blocked.");
    }
	
    global $slash;
	global $directoryRoot;
    $location = $directoryRoot.$target;
    if(is_dir($location)){
		$complete_archivePath = $GLOBALS["directoryRoot"].$slash."tmp".$slash.str_replace('.','',getmicrotime()).".zip";
		zip($location,$complete_archivePath);
		chmod_Nectil($complete_archivePath);
		if(isset($_GET['rename'])){
			fileUpload($complete_archivePath,$_GET['rename'].'.zip');
		}else
        	fileUpload($complete_archivePath,BaseFilename(removeAccents($target)).'.zip');
        unlink($complete_archivePath);
        exit();
    }else{
		if(isset($_GET['rename'])){
			$ext = getFileExt($location);
			fileUpload($location,utf8_encode($_GET['rename']).(($ext)?'.':'').$ext);
		}else
			fileUpload($location);
        exit();
    }
}else{
    htmlErrorMsg("Download error","Target filename is missing");
}

?>