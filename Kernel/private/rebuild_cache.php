<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/rebuild_cache.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
include_once(dirname(__FILE__)."/../common/Cache/Lite.php");

class sushee_CacheFilesCleaner extends SusheeObject{

	var $ct = 0;
	
	function execute(){
		// $this->logFunction('CacheFilesCleaner.execute');
		session_write_close();
		@set_time_limit(0);

		$folders = array();
		$folders[] = new Folder('/cache/xsushee/');
		$folders[] = new Folder('/cache/html/');

		foreach($folders as $folder){
			while($file = $folder->getNextFile()){
				if($file->isWritable()){
					$file->delete();
					$this->ct++;
				}
			}
		}
	}

	function countDeletedFiles(){
		return $this->ct;
	}
}

$cleaner = new sushee_CacheFilesCleaner();
$cleaner->execute();

echo $cleaner->countDeletedFiles()." cleaned<br/>";
echo getTimer('end of rebuild cache');