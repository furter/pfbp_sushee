<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_zip.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");
//require_once(dirname(__FILE__)."/../file/zip/pclzip.lib.php");
function file_zip($queryname,$action,$target){
	$target = transformPath(unhtmlentities($target));
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
		
		$location = $directoryRoot.$target;
		 
		if(is_dir($location)){
			$is_used = isFileUsed($target);
			if($is_used){
				if (is_object($is_used['moduleInfo']) && $is_used['moduleInfo']->loaded)
					return generateMsgXML(1,"Permission denied, file is used in ".$is_used['moduleInfo']->name." '".$is_used['denomination']."' (ID:".$is_used['element']['ID'].")",0,'',$queryname);
					//die( xml_msg("1","-1","-1","Permission denied, the directory or one of its files is used in ".$is_used['moduleInfo']->name." '".$is_used['denomination']."'"));
				else
					return generateMsgXML(1,"Permission denied, file is used in database.",0,'',$queryname);
					//die( xml_msg("1","-1","-1","Permission denied, the directory or one of its files is used in database."));
			}
			$zipped = zip($location,simplify($location).".zip");
			if ($zipped){
				killDirectory($location);
				return generateMsgXML(0,"Zipping of directory successful.",0,'',$queryname);
				//die(xml_msg(0,0,0,"Zipping of directory successful."));
			}else
				return generateMsgXML(1,"Zipping of directory failed.",0,'',$queryname);
				//die(xml_msg(1,0,0,"Zipping of directory failed."));
		}else{
			require_once(dirname(__FILE__)."/../common/services.inc.php");
			$is_used = isFileUsed($target);
			if($is_used){
				if (is_object($is_used['moduleInfo']) && $is_used['moduleInfo']->loaded)
					return generateMsgXML(1,"Permission denied, file is used in ".$is_used['moduleInfo']->name." '".$is_used['denomination']."' (ID:".$is_used['element']['ID'].")",0,'',$queryname);
					//die( xml_msg("1","-1","-1","Permission denied, file is used in ".$is_used['moduleInfo']->name." '".$is_used['denomination']."'"));
				else
					return generateMsgXML(1,"Permission denied, file is used in database.",0,'',$queryname);
					//die( xml_msg("1","-1","-1","Permission denied, file is used in database."));
			}
			$zipped = zip($location,simplify($location).".zip");
			if ($zipped){
				unlink($location);
				return generateMsgXML(0,"Zipping of file successful.",0,'',$queryname);
				//die(xml_msg(0,0,0,"Zipping of file successful."));
			}else
				return generateMsgXML(1,"Zipping of file failed.",0,'',$queryname);
				//die(xml_msg(1,0,0,"Zipping of file failed."));
		}
	}else{
		return generateMsgXML(1,"Target filename is missing",0,'',$queryname);
		//die(xml_msg(1,"Target filename is missing"));
	}
}
?>