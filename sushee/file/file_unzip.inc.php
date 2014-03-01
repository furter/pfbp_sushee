<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_unzip.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");
//require_once(dirname(__FILE__)."/../file/zip/pclzip.lib.php");
function file_unzip($queryname,$action,$target){
	$target = transformPath($target);
	global $directoryRoot;
	//check if target exist
	if (isset($target)){
		//check security for this target 
		$right =  getPathSecurityRight($target);
		
		if($right !=="W"){
			return generateMsgXML(1,"Access to this directory refused",0,'',$queryname);
			//die(xml_msg(1,0,0,"Access to this directory refused"));
		}
		
		if(!hidecheck($target)){
			return generateMsgXML(1,"This file is blocked.",0,'',$queryname);
			//die(xml_msg(1,0,0,"This file is blocked."));
		}
		
		$location = unhtmlentities($directoryRoot.$target);
		 
		if(file_exists($location)){
			$tmp_dir = realpath($directoryRoot."/tmp").'/'.date('YmdHis');
			makedir($tmp_dir);
			$unzipped = unzip($location,$tmp_dir);
			if ($unzipped){
				cleanFilenames_in($tmp_dir);
				copy_content($tmp_dir,dirname($location));
				killDirectory($tmp_dir);
				unlink($location);
				return generateMsgXML(0,"Unzipping successful.",0,'',$queryname);
				//die(xml_msg(0,0,0,"Unzipping successful."));
			}else
				return generateMsgXML(1,"Zipping of directory failed.",0,'',$queryname);
				//die(xml_msg(1,0,0,"Zipping of directory failed."));
		}else{
			return generateMsgXML(1,"File doesn't exist.",0,'',$queryname);
			//die(xml_msg(1,0,0,"File doesn't exist."));
		}
	}else{
		return generateMsgXML(1,"Target filename is missing",0,'',$queryname);
		//die(xml_msg(1,0,0,"Target filename is missing"));
	}
}
?>