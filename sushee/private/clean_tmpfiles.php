<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/clean_tmpfiles.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/file.class.php");

class TempFilesCleaner extends SusheeObject{

	function execute(){
		// $this->logFunction('TempFilesCleaner.execute');
		@set_time_limit(0);
		$folders = array();
		$folders[]= new Folder('/tmp/');
		$folders[]= new Folder('/cache/xsushee/');
		$folders[]= new Folder('/cache/html/');
		$folders[]= new Folder('/pdf/');
		$folders[]= new Folder('/confirm/');
		$folders[]= new Folder('/images/');

		foreach($folders as $folder){
			while($file = $folder->getNextFile()){
				if($file->isOld()){
					//$this->log('must delete '.$file->getCompletePath());
					if($file->isWritable()){
						$file->delete();
					}
				}
				//else{
				//	$this->log($file->getCompletePath().' is not old enough');
				//}
			}
		}
	}
}

$cleaner = new TempFilesCleaner();
$cleaner->execute();