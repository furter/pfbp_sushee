<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_move.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/descriptions.inc.php');

function file_move($queryname,$action,$target,$target2){
	global $directoryRoot;
	$target = transformPath(unhtmlentities($target));
	$target2 = transformPath(unhtmlentities($target2));
	
	$right =  getPathSecurityRight($target);
	if ($right===0)
		return generateMsgXML(1,"Not authorized to copy/move this file.",0,'',$queryname);
	$right =  getPathSecurityRight($target2);
	if ($right!=="W")
		return generateMsgXML(1,"Not authorized to copy/move to this target.",0,'',$queryname);
	
	$sourcename = $target;
	$targetname = $target2;
	$pathExt = "";
	$targetpathExt = "";
	$nname="put there the only file name (without directories)";
	
	// Is New name Okay?
	if (hidecheck($nname)) { $go=1; }
	
	if($go==1) {
			// if the user tries to move a published file, we don't really move it, but copy it instead : it's more transparent than an obscure message about the file being published
			$source_begin = substr($target,0,7);
			$target_begin = substr($target2,0,7);
			if ( ($source_begin=='/media/' && $target_begin!='/media/' && isFileInDescription($target)) || (substr($target,0,6)=='/mail/') ){
				$action='copy';
				$strError = 'not_moved_but_copied';
			}else
				$strError = 0;
			if ($action=='copy') {
				if(!file_exists($directoryRoot.$pathExt.$sourcename))
					return generateMsgXML(1,"File to copy doesn't exist.",0,'',$queryname);
				if(is_dir($directoryRoot.$pathExt.$sourcename)){
					makeDir($directoryRoot.$targetpathExt.$targetname);
					$cm_result = copy_content($directoryRoot.$pathExt.$sourcename,$directoryRoot.$targetpathExt.$targetname,false);
				}else
					$cm_result = copy($directoryRoot.$pathExt.$sourcename, $directoryRoot.$targetpathExt.$targetname);
				if($cm_result)
					return generateMsgXML(0,retransformPath($sourcename)." successfully Copied!",0,'',$queryname);
				else
					return generateMsgXML(1,"Permission denied, contact your Nectil administrator",0,'',$queryname);
			} else { // $action='move'
				require_once(dirname(__FILE__)."/../common/services.inc.php");
				changeUsedFiles($sourcename,$targetname);
				//Move
				if (is_dir($directoryRoot.$pathExt.$sourcename)){
					$cm_result = rename($directoryRoot.$pathExt.$sourcename, $directoryRoot.$targetpathExt.$targetname);
					$is_dir = TRUE;
				}else if(file_exists($directoryRoot.$pathExt.$sourcename))
					$cm_result = copy($directoryRoot.$pathExt.$sourcename, $directoryRoot.$targetpathExt.$targetname);
				else
					return generateMsgXML(1,"File to move doesn't exist.",0,'',$queryname);
				if($cm_result){
					$conclusion = TRUE;
					if (!$is_dir)
						$conclusion = unlink($directoryRoot.$pathExt.$sourcename);
					if($conclusion)
						return generateMsgXML(0,retransformPath($sourcename)." successfully Moved!",0,'',$queryname);
					else
						return generateMsgXML(1,"Permission denied, contact your Nectil administrator",0,'',$queryname);
				}else
					return generateMsgXML(1,"Permission denied, contact your Nectil administrator",0,'',$queryname);
			}
	}
}
?>