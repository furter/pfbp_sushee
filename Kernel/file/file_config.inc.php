<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_config.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
//require_once(dirname(__FILE__)."/../file/zip/pclzip.lib.php");
// Determination de la requete de listing
/*if (!isset($GLOBALS["directoryRoot"])){
	global $directoryRoot;
	$directoryRoot=realpath($GLOBALS["nectil_dir"].'/Files');
}*/

//$directoryRoot='/Library/WebServer/Documents/Files';

// add any file names to this array which should remain invisible
//invisible files are indestructible and unmovable...
global $HiddenFiles;
$HiddenFiles = array(".htaccess",".DS_Store"); 

//add extension that shall not be uploaded
global $BlockedExt;
$BlockedExt = array("php","php3","php4","exe","sh","pl");

// add files that shall not be uploaded, copied over, renamed or deleted. 
//$BlockedFiles = array("history.txt");
// add characters to strip out of filenames
/*$snr = array("%","'","+","\\","/","#","..","!",'"',',','?','*','~');*/
global $directoryCHMOD;
$directoryCHMOD=0777;
// Limit amount of harddrive space and size of file to upload.
global $MaxFileSize;
$MaxFileSize = "20480"; // max file size in bytes
global $HDDSpace;
$HDDSpace = "24000000";/* max total size of all files in directory */

?>
