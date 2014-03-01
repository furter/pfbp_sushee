<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/image_transform.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/image_functions.inc.php");

function image_transform($queryname,&$xml,$current_path,$action,$source,$target){
	$source = transformPath(unhtmlentities($source));
	$target = transformPath(unhtmlentities($target));
	global $directoryRoot;
	$pathExt = "";
	$right =  getPathSecurityRight($source);
	if ($right===0)
		return generateMsgXML(1,"Not authorized to transform this file.",0,'',$queryname);
		//die( xml_msg("1","-1","-1","Not authorized to transform this file."));
	$scenario_name = $xml->getData($current_path."/TRANSFORMATION[1]/@name");
	if(!$scenario_name){
		$right =  getPathSecurityRight($target);
		if ($right!=="W")
			return generateMsgXML(1,"Not authorized to transform this image and name the transformation like this.",0,'',$queryname);
			//die( xml_msg("1","-1","-1","Not authorized to transform this image and name the transformation like this."));
	}
	$nname=BaseFilename($target);
	if(!$scenario_name)
		$oldname=basename($directoryRoot.$pathExt."$source");
	
	if (hidecheck($oldname)) { $go1=1; }
	// Is New name Okay?
	if(!$scenario_name){
		if (hidecheck($nname)) { $go2=1; }
	}else
		$go2=1;
	if ($go1+$go2==2) {
		
		if($scenario_name){
			$eol="\r\n";
			
			if(is_dir($directoryRoot.$source)){
				
				$dir = @opendir($directoryRoot.$source);
				$images_ext = array('jpg','jpeg','gif','png','bmp','jpe','tif','tiff','pdf','svg');
				$errors = array();
				$transf_ok = true;
				while($file = readdir($dir)) {
					
					$isFileVisible=true;
					// if the name is not a directory and the name is not the name of this program file
					if($file == "." || $file == "..") {
						$isFileVisible = false;
					}
					if (!hidecheck($file)) { $isFileVisible=false; }
					// if there were no matches the file should not be hidden
					if($isFileVisible) {
						$ext = strtolower(getFileExt($file));
						if(in_array ( $ext, $images_ext)){
							
							$resultFile = imageTransformation($directoryRoot.$source.$file,$scenario_name);
							if($resultFile===false){
								$transf_ok = false;
								$errors[]=$file;
							}
							usleep(250000);
						}
					}
				}
			}else{
				$resultFile = imageTransformation($directoryRoot.$source,$scenario_name);
				$transf_ok = $resultFile!=false;
				$errors[]=$directoryRoot.$source;
			}
			if($transf_ok!=false)
				return generateMsgXML(0,retransformPath($source)." successfully transformed!",0,'',$queryname);
				//die(xml_msg("0",$userID,$sessionID,retransformPath($source)." successfully transformed!"));
			else
				return generateMsgXML(1,"Problem in the transformation of the image(s). Error on :".$eol.implode($eol,$errors),0,'',$queryname);
				//die( xml_msg("1","-1","-1","Problem in the transformation of the image"));
		}else{
			$resultFile = imageCreation($xml,$current_path."/TRANSFORMATION[1]",$directoryRoot.$source);
			if($resultFile!=false){
				copy($resultFile,$directoryRoot.$target);
				chmod_Nectil($directoryRoot.$target);
				if(is_writable($resultFile) && $resultFile!=($directoryRoot.$source) )
					unlink($resultFile);
				return generateMsgXML(0,retransformPath($source)." successfully transformed!",0,'',$queryname);
				//die(xml_msg("0",$userID,$sessionID,retransformPath($source)." successfully transformed!"));
			
			}else
				return generateMsgXML(1,"Problem in the transformation of the image",0,'',$queryname);
				//die( xml_msg("1","-1","-1","Problem in the transformation of the image"));
		}
	}else{
		if ($go1==0) {
			return generateMsgXML(1,"The file:".retransformPath($source)." has been blocked for this action, contact your Nectil administrator",0,'',$queryname);
			//die( xml_msg("1","-1","-1","The file:".retransformPath($sourcename)." has been blocked for this action, contact your Nectil administrator"));
		}else{
			return generateMsgXML(1,"The file:".retransformPath($target)." has been blocked for this action, contact your Nectil administrator",0,'',$queryname);
			//die( xml_msg("1","-1","-1","The file:".retransformPath($targetname)." has been blocked for this action, contact your Nectil administrator"));
		}
	}
}
?>
