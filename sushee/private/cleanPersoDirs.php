<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/cleanPersoDirs.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);
if ($dir = @opendir($GLOBALS["directoryRoot"]."/contact/")) {
	echo "opening ".$GLOBALS["directoryRoot"]."/contact/";
	echo "<br/>";
	while($file = readdir($dir)) {
		if($file != "." && $file != "..") {
			/*echo $GLOBALS["directoryRoot"]."/contact/".$file;
			echo "<br/>";*/
			$items = count(glob($GLOBALS["directoryRoot"]."/contact/".$file."/*"));
			if($items==0){
				echo "deleting ".$GLOBALS["directoryRoot"]."/contact/".$file;
				if($_GET['delete']==='true' && is_dir($GLOBALS["directoryRoot"]."/contact/".$file))
					rmdir($GLOBALS["directoryRoot"]."/contact/".$file);
			}else
				echo "<b>must keep ".$file." ($items)</b>";
			echo "<br/>";
		}
	}
}else
	echo "couldnot open ".$GLOBALS["directoryRoot"]."contact/";

?>
