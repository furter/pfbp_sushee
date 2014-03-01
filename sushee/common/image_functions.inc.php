<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/image_functions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/


function imageTransformation($filepath,$operationName){
	require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	$file_operations = $GLOBALS["library_dir"].'file/imageTransformations.xml';
	$xml = new XML($file_operations);
	if(!$xml->loaded){
		debug_log($xml->getLastError());
		return false;
	}
	$operation = $xml->match("/imageTransformations/transformation[@name='".$operationName."']");
	$filepath = realpath($filepath);
	if($operation!==false){
		
		$operation = $operation[0];
		$postFix = $xml->getData($operation."/@postfix");
		$target = $xml->getData($operation."/@target");
		/*if(!$postFix)
			$postFix = $operationName;*/
		$dir = dirname($filepath);
		$filename = basename($filepath);
		$without_ext = getFilenameWithoutExt($filename);
		if($postFix && !$target ){
			$destination = $dir."/".$without_ext;
			$destination.=$postFix;
			if (!file_exists($destination))
				makedir($destination);
			
		}else if($target){
			if(substr($target,0,1)!=='/')
				$destination = $dir.'/'.$target;
			else
				$destination = $GLOBALS["directoryRoot"].$target;
			if(substr($destination,-1)=='/')
				$destination=substr($destination,0,-1);
			if($postFix){
				$destination.='/'.$without_ext;
				$destination.=$postFix;
			}
			if (!file_exists($destination))
				makedir($destination);
		}else
			$destination = $dir;
		$imagesCreation_array = $xml->match($operation."/image");
		foreach($imagesCreation_array as $path){
			$imageTarget = $xml->getData($path.'/@target');
			$finalName = $xml->getData($path.'/@finalName');
			if(!$finalName){
				$postFix = $xml->getData($path.'/@postfix');
				//if($postFix!==false){
					$finalName = $without_ext.$postFix;
					if (strpos($postFix,'.')===false)
						$finalName.='.'.getFileExt( basename($filename) );
				//}
			}
			if($finalName){
				
				$resultFile = imageCreation($xml,$path,$filepath);
				if($resultFile!=false){
					if($imageTarget /*&& substr($imageTarget,0,1)=='/'*/){
						if(substr($imageTarget,0,1)!=='/')
							$imageDestination = $dir.'/'.$imageTarget;
						else
							$imageDestination = $GLOBALS["directoryRoot"].$imageTarget;
						if(substr($imageDestination,-1)=='/')
							$imageDestination=substr($imageDestination,0,-1);
						if (!file_exists($imageDestination))
							makedir($imageDestination);
					}else
						$imageDestination = $destination;
					/*$size = @getimagesize($resultFile);
					debug_log('type de limage finale '.$size['mime']);*/
					copy($resultFile,$imageDestination."/".$finalName);
					chmod_Nectil($imageDestination."/".$finalName);
					if(is_writable($resultFile) && $resultFile!=$filepath)
						unlink($resultFile);
				}else{
					debug_log('problem with resulting file'.$resultFile);
					return false;
				}
			}else
				return false;
		}
		// if deleteOriginal attribute is present, delete the original file (only if not used in desc)
		$deleteOriginal = $xml->match($operation."/@deleteOriginal[.='true']");
		if($deleteOriginal){
			// checking it's not used
			$isUsed = isFileUsed(getShortPath($filepath));
			if($isUsed)
				debug_log("isUsed");
			else{
				debug_log("is not Used: can be unlinked");
				@unlink($filepath);
			}
		}
		return true;
	}else{
		return false;
	}
}

function imageTransform($transformation_description_xmlstring,$output=true){
	require_once(dirname(__FILE__)."/../common/image.class.php");
	
	$transformation = new ImageTransformer($transformation_description_xmlstring);
	$res = $transformation->execute();
	
	if($res){
		$image = $transformation->getFinalImage();
		if($output && $image){
			$image->output();
		}else if($image){
			return $image->getPath();
		}else{
			return false;
		}
	}
	
}

function imageCreation(&$xml,$path,$filename,$is_choose=false){
	require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	
	if (file_exists($filename)){
		
		$children_array = $xml->match($path.'/*');
		foreach($children_array as $children_path){
			$size = @getimagesize($filename);
			$ratio = $xml->getData($children_path."/@ratio");
			if(!$ratio)
				$ratio = 1;
			if($size){
				$width = $size[0];
				$height = $size[1];
				//$isHorizontal = ($width>=$height);
				$isHorizontal = (($height/$width)<= $ratio );
				$isVertical = (($height/$width)>= $ratio );
				//$isVertical = ($height>=$width);
				$isSquare = ($isHorizontal && $isVertical);
			}else{
				$isHorizontal = false;
				$isVertical = false;
				$isSquare = false;
			}
			$op_done = false;
			$node_handled = false;
			$nodeName = $xml->nodeName($children_path);
			
			
			if ($nodeName=='square' ){
				if ($isSquare){
					$filename = imageCreation($xml,$children_path,$filename/*,$directory*/);
					$op_done = true;
				}
				$node_handled = true;
			}else if($nodeName=='vertical' ){
				if($isVertical){
					$filename = imageCreation($xml,$children_path,$filename/*,$directory*/);
					$op_done = true;
				}
				$node_handled = true;
			}else if($nodeName=='horizontal'/* && $isHorizontal*/){
				if($isHorizontal){
					$filename = imageCreation($xml,$children_path,$filename/*,$directory*/);
					$op_done = true;
				}
				$node_handled = true;
			}else if($nodeName=='larger-than'){
				$cond_width = $xml->getData($children_path.'/@width');
				$cond_height = $xml->getData($children_path.'/@height');
				if($cond_width && $cond_height && $cond_width<$width && $cond_height<$height){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}else if($cond_width && !$cond_height && $cond_width<$width){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}else if(!$cond_width && $cond_height && $cond_height<$height){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}
				$node_handled = true;
			}else if($nodeName=='smaller-than'){
				$cond_width = $xml->getData($children_path.'/@width');
				$cond_height = $xml->getData($children_path.'/@height');
				if($cond_width && $cond_height && $cond_width>$width && $cond_height>$height){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}else if($cond_width && !$cond_height && $cond_width>$width){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}else if(!$cond_width && $cond_height && $cond_height>$height){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}
				$node_handled = true;
			}else if($nodeName=='between'){
				$cond_width1 = $xml->getData($children_path.'/@width1');
				$cond_height1 = $xml->getData($children_path.'/@height1');
				$cond_width2 = $xml->getData($children_path.'/@width2');
				$cond_height2 = $xml->getData($children_path.'/@height2');
				if($cond_width1!==false && $cond_height1!==false && $cond_width1<$width && $cond_height1<$height && $cond_width2>$width && $cond_height2>$height){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}else if($cond_width1!==false && $cond_height1===false && $cond_width1<$width && $cond_width2>$width){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}else if($cond_width1===false && $cond_height1!==false && $cond_height1<$height && $cond_height2>$height){
					$filename = imageCreation($xml,$children_path,$filename);
					$op_done = true;
				}
				$node_handled = true;
			}else if($nodeName=='choose'){
				$filename = imageCreation($xml,$children_path,$filename,true);
				$node_handled = true;
			}else if($nodeName=='otherwise' && $is_choose==true){
				$filename = imageCreation($xml,$children_path,$filename,true);
				$node_handled = true;
			}
			if($node_handled===false){
				$filename = imageOperation($xml,$children_path,$filename,$size);
				$op_done = true;
			}
			if($filename===false){
				return false;
			}
			if($is_choose==true && $op_done){
				break;
			}
			/*}else
				return false;*/
		}
		return $filename;
	}else
		return false;
}
function textCreation($text,$font='Helvetica',$fontsize=18,$color='black',$background_color='white',$output=true){
	$width =  false;
	include_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	global $slash;
	$OS = getServerOS();
	if ($OS=='windows'){
		$imageExecutable = $GLOBALS["ImageMagickPath"]."convert";
		$imageExecutable = makeExecutableUsable($imageExecutable);
	}else{
		$imageExecutable = $GLOBALS["ImageMagickPath"]."convert";
		$compositeExecutable = $GLOBALS["ImageMagickPath"]."composite";
	}
	$fct_args = func_get_args();
	$microtime = generateID($fct_args);
	
	$resultFile = str_replace('/',$slash,$GLOBALS["directoryRoot"]).$slash."tmp".$slash.$microtime.".gif";
	$partialResultFile = $slash."tmp".$slash.$microtime.".gif";
	if(!file_exists($resultFile) || $_GET['cache']==='false' || $_GET['cache']==='refresh' ){
		$args.=' -background "'.$background_color.'"';
		$args.=' -fill "'.$color.'"';
		if(file_exists($GLOBALS["library_dir"].'fonts'.$slash.$font.".ttf")){
			$font = '"'.str_replace('/',$slash,$GLOBALS["library_dir"]).'fonts'.$slash.$font.'.ttf"';
		}else if(file_exists($GLOBALS["library_dir"].'fonts'.$slash.$font)){
			$font = '"'.str_replace('/',$slash,$GLOBALS["library_dir"]).'fonts'.$slash.$font.'"';
		}else
			$font = '"'.$font.'"';
		$args.=' -font '.$font;
		$args.=' -pointsize '.$fontsize;
		if($width===false || !is_numeric($width))
			$mode = 'label';
		if($mode=='label')
			$args.=' label:"'.encodeQuote($text).'"';
		else{
			$args.=' -size '.$width.'x';
			$args.=' caption:"'.encodeQuote($text).'"';
		}
		$command = $imageExecutable.' '.$args.' '.$resultFile.' 2>&1';
		if ($command == false)
			return false;
		//debug_log($command);
		$command = batchFile($command);
		$sys = shell_exec($command);
	}else{
		//debug_log("Cached text image");
	}
	if(file_exists($resultFile) && filesize($resultFile)>0){
		chmod_Nectil($resultFile);
		if($output){
			header('Cache-Control: max-age=43200, must-revalidate');
			header("Content-type: image/gif");
			@readfile($resultFile);
		}else
			return $partialResultFile;
	}else
		echo $sys;
}

function createText($xml_str,$output=true){
	include_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	$xml = new XML($xml_str);
	if(!$xml->loaded){
		echo 'Invalid XML';
		return;
	}
	$text = UnicodeEntities_To_utf8(decode_from_xml($xml->getData('/text')));
	$color = $xml->getData('/text/@color');
	if(!$color)
		$color = 'black';
	$background_color = $xml->getData('/text/@background-color');
	if(!$background_color)
		$background_color = 'white';
	$font = $xml->getData('/text/@font');
	if(!$font)
		$font = 'Helvetica';
	$fontsize = $xml->getData('/text/@size');
	if(!$fontsize || !is_numeric($fontsize))
		$fontsize = '18';
	$format = $xml->getData('/text/@format');
	if(!$format)
		$format = 'gif';
	$outlineWidth = $xml->getData('/text/@outline-width');
	if(!$outlineWidth || !is_numeric($outlineWidth))
		$outlineWidth = '0';
	$outlineColor = $xml->getData('/text/@outline-color');
	if(!$outlineColor)
		$outlineColor = 'red';
	$leading = $xml->getData('/text/@leading');
	if(!$leading)
		$leading = 0;
	global $slash;
	$OS = getServerOS();
	if ($OS=='windows'){
		$imageExecutable = $GLOBALS["ImageMagickPath"]."convert";
		$imageExecutable = makeExecutableUsable($imageExecutable);
	}else{
		$imageExecutable = $GLOBALS["ImageMagickPath"]."convert";
		$compositeExecutable = $GLOBALS["ImageMagickPath"]."composite";
	}
	$microtime = generateID(array($text,$font,$fontsize,$color,$background_color,$output,$outlineWidth,$outlineColor,$leading,$format));
	
	$resultFile = str_replace('/',$slash,$GLOBALS["directoryRoot"]).$slash."tmp".$slash.$microtime.".".$format;
	$partialResultFile = $slash."tmp".$slash.$microtime.".".$format;
	if(!file_exists($resultFile) || $_GET['cache']==='false' || $_GET['cache']==='refresh' ){
		$args.='-size 3000x'.($fontsize*2).' xc:none -box "%%%bkgcolor%%%"';
		
		if(file_exists($GLOBALS["library_dir"].'fonts'.$slash.$font.".ttf")){
			$font = '"'.str_replace('/',$slash,$GLOBALS["library_dir"]).'fonts'.$slash.$font.'.ttf"';
		}else if(file_exists($GLOBALS["library_dir"].'fonts'.$slash.$font)){
			$font = '"'.str_replace('/',$slash,$GLOBALS["library_dir"]).'fonts'.$slash.$font.'"';
		}else
			$font = '"'.$font.'"';
		$args.=' -font '.$font;
		$args.=' -pointsize '.$fontsize;
		$args.=' -fill "'.$color.'"';
		if($outlineWidth)
			$args.=' -stroke "'.$outlineColor.'" -strokewidth '.$outlineWidth;
		
		$args.=' -gravity NorthWest -annotate 0x0+0+0  "'.str_replace(array("\\","\""),array("\\\\","\\\""),$text).'"';
		if($background_color!='transparent')
			$args.=' -trim +repage '.(($format=='gif')?' -flatten ':'');
		$command = $imageExecutable.' '.$args.' '.$resultFile.' 2>&1';
		
		if($background_color=='transparent'){
			// must make a first image with a colored background to know the size of the box
			$fake_background_color = '#ff0000';
			if($color=='red' || strtolower($color)=='#ff0000')
				$fake_background_color = 'blue';
			$boxFile = str_replace('/',$slash,$GLOBALS["directoryRoot"]).$slash."tmp".$slash.$microtime."_box.".$format;
			$command2 = $imageExecutable.' '.$args.' -trim +repage '.$boxFile.' 2>&1';
			$command2 = str_replace('%%%bkgcolor%%%',$fake_background_color,$command2);
			//debug_log($command2);
			$command2 = batchFile($command2);
			$sys = shell_exec($command2);
		}
		$command = str_replace('%%%bkgcolor%%%',$background_color,$command);
		//debug_log($command);
		$command = batchFile($command);
		$sys = shell_exec($command);
		
		if($background_color=='transparent'){
			chmod_Nectil($resultFile);
			$size = getimagesize($boxFile);
			$command3 = $imageExecutable.' -crop '.$size[0].'x'.$size[1].'+0+0 '.$resultFile.' +repage '.$resultFile.' 2>&1';
			//debug_log($command3);
			$command3 = batchFile($command3);
			$sys = shell_exec($command3);
		}
		if(substr($leading,0,1)=='+')
			$leading = substr($leading,1);
		if($leading!=0 && is_numeric($leading)){
			chmod_Nectil($resultFile);
			$size = getimagesize($resultFile);
			$command3 = $compositeExecutable.' -compose Over '.$resultFile.' -size '.$size[0].'x'.($size[1]+$leading).' xc:"'.$background_color.'" '.$resultFile.' 2>&1';
			//debug_log($command3);
			$command3 = batchFile($command3);
			$sys = shell_exec($command3);
			//debug_log($sys);
		}
	}else{
		debug_log("Cached text image");
	}
	if(file_exists($resultFile) && filesize($resultFile)>0){
		chmod_Nectil($resultFile);
		if($output){
			header('Cache-Control: max-age=43200, must-revalidate');
			header("Content-type: image/".$format);
			@readfile($resultFile);
		}else
			return $partialResultFile;
	}else
		echo $sys;
}

function imageOperation(&$xml,&$path,$filename/*,$directory*/,$size){
	require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	include_once(dirname(__FILE__)."/../common/image.class.php");
	$OS = getServerOS();
	global $slash;
	if ($OS=='windows'){
		$imageExecutable = $GLOBALS["ImageMagickPath"]."convert";
		$compositeExecutable = $GLOBALS["ImageMagickPath"]."composite";
		$imageExecutable = makeExecutableUsable($imageExecutable);
		$compositeExecutable  = makeExecutableUsable($compositeExecutable);
	}else{
		$imageExecutable = $GLOBALS["ImageMagickPath"]."convert";
		$compositeExecutable = $GLOBALS["ImageMagickPath"]."composite";
	}
	$rand = rand(0, 1000000000000);
	$targetshortname = $slash."tmp".$slash.str_replace('.','',getmicrotime()).$rand;
	$targetname = $GLOBALS["directoryRoot"].$targetshortname;
	$ext = getFileExt( basename($filename) );
	$nodeName = $xml->nodeName($path);
	$tmpTargetname = $targetname.'_tmp.'.$ext;
	$completeTargetname= $targetname.'.'.$ext;
	$targetshortname.='.'.$ext;
	$width = $size[0];
	$height = $size[1];
	$command = false;
	switch($nodeName){
		case 'resize':
			$width = $xml->getData($path.'/@width');
			$height = $xml->getData($path.'/@height');
			$effect = new ResizeImageEffect();
			$effect->setWidth($width);
			$effect->setHeight($height);
			$effect->setSource(new File($filename));
			$effect->setTarget(new File($targetshortname));
			$effect->execute();
			break;
		case 'resample':
			$resolution = $xml->getData($path.'/@resolution');
			$effect = new ResampleImageEffect();
			$effect->setResolution($resolution);
			$effect->setSource(new File($filename));
			$effect->setTarget(new File($targetshortname));
			$effect->execute();
			break;
		case 'convert':
			$format = $xml->getData($path.'/@format');
			$raster = $xml->getData($path.'/@resolution');
			$progressive = $xml->getData($path.'/@progressive');
			if (!$raster)
				$raster = 144;

			if ($format)
			{
				$compression = $xml->getData($path.'/@compression');
				if($compression && is_numeric($compression))
				{
					if($compression>100)
						$compression = 100;
					$quality = ' -quality '.$compression.' ';
				}
				if($progressive==='true'){
					$quality.= ' -interlace JPEG ';
				}
				$completeTargetname= $targetname.'.'.$format;
				$this_ext = getFileExt( basename($filename) );

				if($this_ext=='gif' || $this_ext=='mpg' || $this_ext=='mpeg' || $this_ext=='avi' || $this_ext=='pdf')
					$framing = "[0]";

				if ( $this_ext=='pdf' ||  $this_ext=='svg')
					$density = " -density " . $raster;
				
				$command = $imageExecutable.$quality.$density.' -strip -colorspace RGB "'.$filename.$framing.'" "'.$completeTargetname.'"  2>&1';
			}
			else 
			{
				$command = false;
			}
			break;
		case 'crop':
			$position = $xml->getData($path.'/@position');
			$crop_width = $xml->getData($path.'/@width');
			$crop_height = $xml->getData($path.'/@height');
			$background_color = $xml->getData($path.'/@background-color');
			if(!$background_color)
				$background_color = 'white';
			if($crop_width || $crop_height){
				if(!$crop_width)
					$crop_width = $width;
				if(!$crop_height)
					$crop_height = $height;
				if(substr($crop_width,0,1)=='+'){
					$crop_width = $width+((int)substr($crop_width,1));
				}else if(substr($crop_width,0,1)=='-'){
					$crop_width = $width-((int)substr($crop_width,1));
				}
				if(substr($crop_height,0,1)=='+'){
					$crop_height = $height+((int)substr($crop_height,1));
				}else if(substr($crop_height,0,1)=='-'){
					$crop_height = $height-((int)substr($crop_height,1));
				}
			}
			if($crop_width && $crop_height){
				if( $crop_width<$width || $crop_height<$height ){
					// we first have to do an intermediary cut
					if(!$position)
						$position='centered';
					if($position=='centered' || $position=='center'){
						$x = floor(($width-$crop_width)/2);
						if($x<0)
							$x = 0;
						$y = floor(($height-$crop_height)/2);
						if($y<0)
							$y = 0;
					}else if($position=='topcenter' || $position=='top'){
						$x = floor(($width-$crop_width)/2);
						$y = 0;
					}else if($position=='upleft' || $position=='topleft'){
						$x = 0;
						$y = 0;
					}else if($position=='centerleft'){
						$x = 0;
						$y = floor(($height-$crop_height)/2);
						if($y<0)
							$y = 0;
					}else if($position=='centerright'){
						$x = $width-$crop_width;
						$y = floor(($height-$crop_height)/2);
						if($y<0)
							$y = 0;
					}else if($position=='upright' || $position=='topright'){
						$x = $width-$crop_width;
						if($x<0)
							$x=0;
						$y = 0;
					}else if($position=='bottomcenter' || $position=='bottom'){
						$x = floor(($width-$crop_width)/2);
						$y = $height-$crop_height;
					}else if($position=='bottomleft'){
						$x = 0;
						$y = $height-$crop_height;
						if($y<0)
							$y=0;
					}else if($position=='bottomright'){
						$x = $width-$crop_width;
						$y = $height-$crop_height;
					}else{
						$x = -1;
						$y = -1;
					}
					if($x>=0 && $y>=0){
						$option = " -crop ".$crop_width."x".$crop_height."+".$x."+".$y;
						$command = $imageExecutable.' '.$option.' "'.$filename.'" -strip -colorspace RGB "'.$tmpTargetname.'" 2>&1';
					}else{
						debug_log("Problem in crop handling x or y <0");
						$command = false;
						return false;
					}
					// only if there will be a composite with background after that
					//if($crop_width>$width || $crop_height>$height){
					debug_log($command);
					$command = batchFile($command);

					$sys = shell_exec("$command");
					if(!file_exists($tmpTargetname))
						return false;
						/*else
							$filename=$completeTargetname;*/
					//}
					$tmp_file = $tmpTargetname;
				}else
					$tmp_file = $filename;
				//if($crop_width>$width || $crop_height>$height){
				switch($position){
					case 'top':
					case 'topcenter':$gravity = 'North';break;
					case 'bottom':
					case 'bottomcenter':$gravity = 'South';break;
					case 'upleft':
					case 'topleft': $gravity = 'NorthWest';break;
					case 'upright': 
					case 'topright': $gravity = 'NorthEast';break;
					case 'bottomleft': $gravity = 'SouthWest';break;
					case 'bottomright': $gravity = 'SouthEast';break;
					case 'centerleft': $gravity = 'West';break;
					case 'centerright': $gravity = 'East';break;
					default: $gravity = 'Center';
				}
				$option = "-compose Over \"".$tmp_file."\" -gravity ".$gravity." -size ".$crop_width."x".$crop_height." -gravity ".$gravity." xc:\"".$background_color."\" -gravity ".$gravity." ";
				$command = $compositeExecutable.' '.$option.' "'.$completeTargetname.'" 2>&1';
				debug_log($command);
				//}
			}
			break;
		case 'rotate':
			$degrees = $xml->getData($path.'/@angle');
			if($degrees){
				$option = " -rotate ".$degrees;
				$command = $imageExecutable.' -strip -colorspace RGB '.$option.' "'.$filename.'" "'.$completeTargetname.'"  2>&1';
			}
			break;
		case 'flip':
			$direction =  $xml->getData($path.'/@direction');
			if ($direction=="vertical")
			$option = " -flip";
			else
			$option = " -flop";
			$command = $imageExecutable.' -strip -colorspace RGB '.$option.' "'.$filename.'" "'.$completeTargetname.'" 2>&1';
			break;
		case 'grayscale':
			$option = " -colorspace GRAY";
			$command = $imageExecutable.' -strip -colorspace RGB  "'.$filename.'" '.$option.' "'.$completeTargetname.'" 2>&1';
			break;
		case 'annotate':
			$text =  UnicodeEntities_To_utf8($xml->getData($path.'/@text'));
			if($text){
				$color =  $xml->getData($path.'/@color');
				if(!$color) $color = 'white';
				$fontsize =  $xml->getData($path.'/@font-size');
				if(!$fontsize || !is_numeric($fontsize) ) $fontsize = '18';
				$position = $xml->getData($path.'/@position');
				/*if($position=='bottomleft'){
					$x = 0;
					$y = $height;
				}else{
					$x = 0;
					$y = $fontsize;
				}*/
				$x = 0;
				$y = 0;
				$margintop =  $xml->getData($path.'/@margin-top');
				$marginbottom =  $xml->getData($path.'/@margin-bottom');
				$marginleft =  $xml->getData($path.'/@margin-left');
				$marginright =  $xml->getData($path.'/@margin-right');
				if(!is_numeric($margintop))$margintop=0;
				if(!is_numeric($marginbottom))$marginbottom=0;
				if(!is_numeric($marginleft))$marginleft=0;
				if(!is_numeric($marginright))$marginright=0;
				if($position=='topleft'){
					$gravity = 'northwest'; // ok
					$translationX = $marginleft-$marginright;
					$translationY = $margintop-$marginbottom;
				}else if($position=='topright'){
					$gravity = 'northeast'; // ok
					$translationX = $marginright-$marginleft;
					$translationY = $margintop-$marginbottom;
				}else if($position=='bottomleft'){
					$gravity = 'southwest';
					$translationX = $marginleft-$marginright;
				}else if($position=='center'){ 
					$gravity = 'center'; // ok
					$translationX = $marginleft-$marginright;
					$translationY = $margintop-$marginbottom;
				}else if($position=='bottomcenter'){
					$gravity = 'south'; // ok
					$translationX = $marginleft-$marginright;
					$translationY = $marginbottom-$margintop;
				}else if($position=='topcenter'){
					$gravity = 'north'; // ok
					$translationX = $marginleft-$marginright;
					$translationY = $margintop-$marginbottom;
				}else /*if($position=='bottomleft')*/{
					$gravity = 'southeast';
					$translationX = $marginright-$marginleft;
					$translationY = $marginbottom-$margintop;
				}
				$decalX = $xml->getData($path.'/@translateX');
				$decalY = $xml->getData($path.'/@translateY');
				if($decalX)
					$x+= $decalX;
				if($decalY)
					$x+= $decalY;
				
				$font = $xml->getData($path.'/@font');
				if($font!==false){
					if(file_exists($GLOBALS["library_dir"].'fonts'.$slash.$font.".ttf")){
						$font = '"'.str_replace('/',$slash,$GLOBALS["library_dir"]).'fonts'.$slash.$font.'.ttf"';
					}else if(file_exists($GLOBALS["library_dir"].'fonts'.$slash.$font)){
						$font = '"'.str_replace('/',$slash,$GLOBALS["library_dir"]).'fonts'.$slash.$font.'"';
					}else
						$font = '"'.$font.'"';
					$font_arg=' -font '.$font;
				}else
					$font_arg=' -font Helvetica ';
				$option = " $font_arg -fill \"$color\" -pointsize $fontsize -draw 'gravity $gravity translate ".$translationX.",".$translationY." text $x,$y \"$text\"'";
				$command = $imageExecutable.' -strip -colorspace RGB '.$option.' "'.$filename.'" "'.$completeTargetname.'" 2>&1';
			}
			break;
		case 'color-filter':
		// args : color,start-color
			$color =  $xml->getData($path.'/@color');
			$start_color = $xml->getData($path.'/@start-color');
			if(!$start_color)
				$start_color = 'white';
			if($color){
				$option = " -size ".$width."x".$height." gradient:\"$start_color-$color\" -fx \"v.p{0,(1-intensity)*v.h}\" ";
				$command = $imageExecutable.' -strip -colorspace RGB "'.$filename.'" '.$option.'  "'.$completeTargetname.'" 2>&1';
			}
			break;
		case 'rounded-corners':
		case 'rounded_corners':
		// args: color,size,position(topleft+/topright+/bottomleft+/bottomright)
			$color =  $xml->getData($path.'/@color');
			if(!$color)
				$color = 'white';
			$size =  $xml->getData($path.'/@size');
			if(!$size || !is_numeric($size))
				$size= 15;
			$position =  $xml->getData($path.'/@position');
			if(!$position){
				$corners = array("topleft","topright","bottomleft","bottomright");
			}else{
				$corners = explode(',',$position);
			}
			$border = $xml->getData($path.'/@border');
			if($border){
				$border_color =  $xml->getData($path.'/@border-color');
				if(!$border_color)
					$border_color = 'black';
			}else
				$border = 0;
			$rounded_corner_path = $GLOBALS["directoryRoot"].$slash."tmp".$slash."rounded_corner_".str_replace('#','',$color)."_".$size."_".str_replace('#','',$border_color)."_".$border.".png";
			//$rounded_border_path = $GLOBALS["directoryRoot"].$slash."tmp".$slash."rounded_border_".str_replace('#','',$border_color)."_".$size.".png";
			if(!file_exists($rounded_corner_path) || $_GET['cache']==='false' || $_GET['cache']==='refresh'){
				$double_size = $size*2;
				$draw_color = $color;
				if($color=='transparent')
					$draw_color = 'black';
				if($border)
					$rounded_comm = $imageExecutable." -strip -size ".$size."x".$size." xc:\"".$draw_color."\" -fill \"".$border_color."\" -draw \"circle 0,0 0,".($size-1)."\" \( xc:none -draw \"circle 0,0 0,".($size-1-$border)."\" \) -compose DstOut -composite -flip -flop ".$rounded_corner_path;
				else
					$rounded_comm = $imageExecutable." -strip -size ".$size."x".$size." xc:none -fill \"".$draw_color."\"  -draw \"rectangle 0,0 ".$size.",".$size."\" \\( xc:transparent -fill white -draw \"arc ".$double_size.",".$double_size." 0,0 180,0\" \\) -compose Dst_Out -composite  ".$rounded_corner_path." 2>&1";
				$rounded_comm = batchFile($rounded_comm);
				$sys = shell_exec("$rounded_comm");
				debug_log($rounded_comm);
				debug_log($sys);
			}
			if($color=='transparent')
				$masking ='-compose Dst_Out';
			else
				$masking = '';
			if($border)
				$option.='-bordercolor "'.$border_color.'" -border '.$border.' ';
			if ($OS=='windows'){
				$parenthesis_protector = '';
			}else{
				$parenthesis_protector = '\\';
			}
			if(in_array("topleft",$corners)){
				$option.=$parenthesis_protector.'( '.$rounded_corner_path.' '.$parenthesis_protector.') -gravity NorthWest '.$masking.' -composite ';
			}
			if(in_array("bottomleft",$corners)){
				$option.=$parenthesis_protector.'( '.$rounded_corner_path.' -flip '.$parenthesis_protector.') -gravity SouthWest '.$masking.' -composite ';
			}
			if(in_array("topright",$corners)){
				$option.=$parenthesis_protector.'( '.$rounded_corner_path.' -flop '.$parenthesis_protector.') -gravity NorthEast '.$masking.' -composite ';
			}
			if(in_array("bottomright",$corners)){
				$option.=$parenthesis_protector.'( '.$rounded_corner_path.' -flip -flop '.$parenthesis_protector.') -gravity SouthEast '.$masking.' -composite ';
			}
			$matte = '+matte';
			if($color=='transparent')
				$matte ='';
			$command = $imageExecutable.' -strip "'.$filename.'" -matte '.$option.' '.$matte.'  "'.$completeTargetname.'" 2>&1';
			
			break;
		case 'mask':
		case 'layer':
		//args: path(image to overlap),position(=topright/topleft/topcenter/center/centerleft/centerright/bottomcenter/bottomright/bottomleft),stretch(=none/vertical/horizontal/both),tile(=true/false)
			$layer_path =  $xml->getData($path.'/@path');
			$position =  $xml->getData($path.'/@position');
			$pattern =  $xml->getData($path.'/@pattern');
			if($pattern=='true')
				$tiling = '-tile';
			if(!$position)
				$position = "center";
			$stretch =  $xml->getData($path.'/@stretch');
			if(!$stretch)
				$stretch = 'none';
			$stretch_option='';
			$layer_complete_path = $GLOBALS["nectil_dir"].$layer_path;
			if(!file_exists($layer_complete_path))
				$layer_complete_path = $GLOBALS["directoryRoot"].$layer_path; // trying directly from /Files
			$margintop =  $xml->getData($path.'/@margin-top');
			$marginbottom =  $xml->getData($path.'/@margin-bottom');
			$marginleft =  $xml->getData($path.'/@margin-left');
			$marginright =  $xml->getData($path.'/@margin-right');
			if(!is_numeric($margintop))$margintop=0;
			if(!is_numeric($marginbottom))$marginbottom=0;
			if(!is_numeric($marginleft))$marginleft=0;
			if(!is_numeric($marginright))$marginright=0;
			if($stretch!='none'){
				$layer_size = @getimagesize($layer_complete_path);
				if($layer_size){
					$layer_width = $layer_size[0];
					$layer_height = $layer_size[1];
				}
				if($stretch=='vertical'){
					$stretch_option = ' -geometry '.$layer_width.'x'.($height-$margintop-$marginbottom).'!';
				}else if($stretch=='horizontal'){
					$stretch_option = ' -geometry '.($width-$marginleft-$marginright).'x'.$layer_height.'!';
				}else if($stretch=='both'){
					$stretch_option = ' -geometry '.$width.'x'.$height.'!';
				}
				$command = $imageExecutable.' '.$stretch_option.' "'.$layer_complete_path.'" -strip -colorspace RGB "'.$tmpTargetname.'" 2>&1';
				debug_log($command);
				$command = batchFile($command);
				$sys = exec($command);
				debug_log($sys);
				$tmp_file = $tmpTargetname;
				if(file_exists($tmp_file))
					$layer_complete_path = $tmp_file;
			}
			if($position=='topleft'){
				$gravity = 'northwest'; 
			}else if($position=='topright'){
				$gravity = 'northeast';
			}else if($position=='bottomleft'){
				$gravity = 'southwest';
			}else if($position=='center' || $position=='centered'){ 
				$gravity = 'center'; 
			}else if($position=='topcenter'){ 
				$gravity = 'north'; 
			}else if($position=='bottomcenter'){ 
				$gravity = 'south'; 
			}else if($position=='centerleft' || $position=='leftcenter'){ 
				$gravity = 'west'; 
			}else if($position=='centerright' || $position=='rightcenter'){ 
				$gravity = 'east'; 
			}else{
				$gravity = 'southeast';
			}
			if($nodeName=='mask'){
				$masking = 'Dst_Out';
				$matte = '-matte';
			}else{
				$masking = 'Over';
				$matte ='';
			}
			$option = '-compose '.$masking.' -strip '.$matte.' -colorspace RGB -gravity '.$gravity.' -geometry +'.$marginleft.'+'.$margintop.' '.$tiling.' "'.$layer_complete_path.'"';
			$command = $compositeExecutable.' '.$option.' "'.$filename.'"  "'.$completeTargetname.'" 2>&1';
			break;
		case 'sharpen':
			// args: amount (in pct, ex: 170 -> 170% -> 1.7)
			$amount =  $xml->getData($path.'/@amount');
			$threshold =  $xml->getData($path.'/@threshold');
			if(!$amount)
				$amount = '1';
			else
				$amount = $amount/100;
			if(!$threshold)
				$threshold='.10';
			$option = '-unsharp 1x2+'.$amount.'+'.$threshold;
			$command = $imageExecutable.' -strip -colorspace RGB  "'.$filename.'" '.$option.'  "'.$completeTargetname.'" 2>&1';
			break;
		case 'border':
			$size =  $xml->getData($path.'/@size');
			$color = $xml->getData($path.'/@color');
			if(!$size)
				$size = 15;
			if(!$color)
				$color = 'black';
			$option = '-bordercolor "'.$color.'" -border '.$size;
			$command = $imageExecutable.' -strip "'.$filename.'" '.$option.' "'.$completeTargetname.'" 2>&1';
			break;
		case 'distort':
			$option = '-wave -10x75';
			$command = $imageExecutable.' -strip "'.$filename.'" '.$option.' "'.$completeTargetname.'" 2>&1';
			break;
		default:$command=false;
	}
	// exec command
	if(!is_object($effect)){
		if ($command == false)
			return false;
		debug_log($command);
		$command = batchFile($command);
		$sys = exec($command);
	}
	
	// deleting the origin if it's a tmp file
	$tmpDir = str_replace('/',$slash,$GLOBALS["directoryRoot"]).$slash."tmp".$slash;
	if(file_exists($filename) && substr($filename,0,strlen($tmpDir))==$tmpDir){
		// to allow deletion/cleaning by anyone
		chmod_Nectil($filename);
		//unlink($filename);
	}
	$tmp_filename = $completeTargetname.'_tmp';
	if(file_exists($tmp_filename) && substr($tmp_filename,0,strlen($tmpDir))==$tmpDir){
		// to allow deletion/cleaning by anyone
		chmod_Nectil($tmp_filename);
		//unlink($tmp_filename);
	}
	if(file_exists($completeTargetname)){
		debug_log("file ".$completeTargetname." exists");
		chmod_Nectil($completeTargetname);
		return $completeTargetname;
	}else{
		debug_log($sys);
		debug_log("file ".$completeTargetname." doesn't exist");
		return false;
	}
}
?>
