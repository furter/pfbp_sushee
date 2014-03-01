<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_delete.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/descriptions.inc.php');

function file_delete($queryname,$action,$target){
	
	global $directoryRoot;
	$target = transformPath(unhtmlentities($target));
	
	$right =  getPathSecurityRight($target);
	if ($right!=="W")
		return generateMsgXML(1,"Not authorized to delete this file.",0,'',$queryname);
		//die( xml_msg("1","-1","-1","Not authorized to delete this file."));
	
	$finalname = $target;
	
	$delete="put there the only file name (without directories)";
	
	//security and prerequisities
	$path_array= explode("/",$finalname);
	// Ok2Edit
	if (hidecheck($delete)) {
		/* delete the file or directory */
		if(is_dir($directoryRoot.$finalname)) {
			if($action=="recdelete"){
				$is_used = isFileUsed($finalname);
				if($is_used){
					if (is_object($is_used['moduleInfo']) && $is_used['moduleInfo']->loaded)
						return generateMsgXML(1,"Permission denied, directory is used in ".$is_used['moduleInfo']->name." '".$is_used['denomination']."' (ID:".$is_used['element']['ID'].")",0,$is_used['element']['ID'],$queryname);
					else
						return generateMsgXML(1,"Permission denied, directory is used in database.",0,'',$queryname);
				}
				//kill everything
				$killed = killDirectory($directoryRoot.$finalname,TRUE);
				if (!$killed)
					return generateMsgXML(1,"Some files are used in the database, they have been kept. The others have been deleted.",0,'',$queryname);
					//die( xml_msg("1","-1","-1","Some files are used in the database, they have been kept. The others have been deleted."));
			}else{
				if(isDirEmpty($directoryRoot.$finalname)){
					killDirectory($directoryRoot.$finalname);
				}else{
					$str = "<CONFIRM>recursive delete ?</CONFIRM>";
					return generateMsgXML(0,$str,0,'',$queryname);
					//die( xml_msg("0","-1","-1",$str));
				}
			}
			return generateMsgXML(0,"Directory:".retransformPath($finalname)." successfully removed!",0,'',$queryname);
			//die (xml_msg("0",$userID,$sessionID,"Directory:".retransformPath($finalname)." successfully removed!"));
		} else {
			
			//check if fileName is in DB (Description)
			//require_once("../common/services.inc.php");
			$is_used = isFileUsed($finalname);
			if($is_used){
				if (is_object($is_used['moduleInfo']) && $is_used['moduleInfo']->loaded)
					return generateMsgXML(1,"Permission denied, file is used in ".$is_used['moduleInfo']->name." '".$is_used['denomination']."' (ID:".$is_used['element']['ID'].")",0,$is_used['element']['ID'],$queryname);
					//die( xml_msg("1","-1","-1","Permission denied, file is used in ".$is_used['moduleInfo']->name." '".$is_used['denomination']."' (ID:".$is_used['element']['ID'].")"));
				else
					return generateMsgXML(1,"Permission denied, file is used in database.",0,'',$queryname);
					//die( xml_msg("1","-1","-1","Permission denied, file is used in database."));
			}
			if (file_exists($directoryRoot.$finalname)){ 
				if(unlink($directoryRoot.$finalname))
					return generateMsgXML(0,"File: ".retransformPath($finalname)." successfully deleted!",0,'',$queryname);
					//die (xml_msg("0",$userID,$sessionID,"File: ".retransformPath($finalname)." successfully deleted!"));
				else
					return generateMsgXML(1,"Permission denied, contact your Nectil administrator",0,'',$queryname);
					//die( xml_msg("1","-1","-1","Permission denied, contact your Nectil administrator"));
			  }else{
				  $str = "File: ".retransformPath($finalname)." doesn't exist.";
				  return generateMsgXML(1,$str,0,'',$queryname);
				  //die( xml_msg("1","-1","-1",$str));
			  }
		}
	} else {
		$str = "Deleting file:".retransformPath($finalname)." is a BLOCKED action.";
		return generateMsgXML(1,$str,0,'',$queryname);
		//die( xml_msg("1","-1","-1",$str)); 
	}
}
?>