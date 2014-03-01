<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_mkdir.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
// use $target

function file_mkdir($queryname,$action,$target){
	global $directoryRoot;
	$target = transformPath(unhtmlentities($target));
	
	$right =  getPathSecurityRight($target);
	
	if ($right!=="W")
		return generateMsgXML(1,"Not authorized to make a new directory there.",0,'',$queryname);
		//die( xml_msg("1","-1","-1","Not authorized to make a new directory there."));
	
	$sourcename = $target;
	$pathExt = "";
	$nname="put there the only file name (without directories)";
	
	if (hidecheck($sourcename)) {
		if(is_dir($directoryRoot.$pathExt.$sourcename)){
			$str = "Directory already exist.";
			return generateMsgXML(1,$str,0,'',$queryname);
			//die( xml_msg("1","-1","-1",$str));
		}
		$old_umask = umask(0);
		$result = mkdir($directoryRoot.$pathExt.$sourcename, 0777);
		umask($old_umask);
		if($result == 0) {
			$str = "Directory could not be created. Please contact your Nectil Administrator.";
			return generateMsgXML(1,$str,0,'',$queryname);
			//die( xml_msg("1","-1","-1",$str));
		}else{
			return generateMsgXML(0,"Directory:".retransformPath($sourcename)." successfully created!",0,'',$queryname);
			//die (xml_msg("0",$userID,$sessionID,"Directory:".retransformPath($sourcename)." successfully created!"));
		}
	}
	else {
		$str = 'Creating Directory: $dirname is a BLOCKED action.';
		return generateMsgXML(1,$str,0,'',$queryname);
		//die( xml_msg("1","-1","-1",$str));
	}
}
?>