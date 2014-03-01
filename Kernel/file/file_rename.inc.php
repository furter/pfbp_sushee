<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_rename.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
// use $target
function file_rename($queryname,$action,$target,$target2){
	
	$target = transformPath(unhtmlentities($target));
	$target2 = transformPath(unhtmlentities($target2));
	
	$right =  getPathSecurityRight($target);
	if ($right===0)
		return generateMsgXML(1,"Not authorized to rename this file.",0,'',$queryname);
	$right =  getPathSecurityRight($target2);
	if ($right!=="W")
		return generateMsgXML(1,"Not authorized to rename like this.",0,'',$queryname);
	global $directoryRoot;
	global $BlockedExt;
	global $directoryCHMOD;
	$sourcename = $target;
	$targetname = $target2;
	
	$nname=BaseFilename($targetname);
	$oldname=basename($directoryRoot.$sourcename);
	
	// Change name of existing file Okay?
	$ext = getFileExt($target2);
	if(in_array($ext,$BlockedExt))
		return generateMsgXML(1,"Not authorized to rename this file : extension forbidden.",0,'',$queryname);
	// Modify this file okay?
	if (hidecheck($oldname)) { $go1=1; }
	// Is New name Okay?
	if (hidecheck($nname)) { $go2=1; }
	if(strpos($nname,' ')!==false && (getServerOS()=='windows'))
		return generateMsgXML(1,"Not authorized to rename this file : no white space is authorized in the filename.",0,'',$queryname);
	if ($go1+$go2==2) {
		if (file_exists($directoryRoot.$pathExt."$sourcename")){
			require_once(dirname(__FILE__)."/../common/services.inc.php");
			if (is_dir($directoryRoot.$pathExt."$sourcename")&& substr($sourcename,-1)!="/"){
				$sourcename.="/";
				if (substr($targetname,-1)!="/")
					$targetname.="/";
			}
			changeUsedFiles($sourcename,$targetname);
			$renamed = rename ($directoryRoot.$sourcename, $directoryRoot.$targetname);
			if($renamed)
				return generateMsgXML(0,retransformPath($sourcename)." successfully renamed!",0,'',$queryname);
			else
				return generateMsgXML(1,"Rename failed, contact your Nectil administrator",0,'',$queryname);
		}else{
			return generateMsgXML(1,"File doesn't exist : try to refresh the parent directory.",0,'',$queryname);
		}
	}else{
		if ($go1==0) {
			return generateMsgXML(1,"The file:".retransformPath($sourcename)." has been blocked for this action, contact your Nectil administrator",0,'',$queryname);
		}else{
			return generateMsgXML(1,"The file:".retransformPath($targetname)." has been blocked for this action, contact your Nectil administrator",0,'',$queryname);
		}
	}
}
?>