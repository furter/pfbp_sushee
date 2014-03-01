<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/image.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/commandline.class.php");
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/commandline.class.php");
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");

class ImageCache extends SusheeObject{
	
	function ImageCache(){
		global $directoryRoot;
		if (!is_dir($directoryRoot."/images/"))
			makedir($directoryRoot."/images/");
	}
	
	function getPossibleExtensions(){
		return array('jpg','gif','png');
	}
	
	function get($ID){
		// trying a set of possible extensions
		$extensions  = $this->getPossibleExtensions();
		foreach($extensions as $ext){
			$file = new Image('/images/'.$ID.'.'.$ext);
			if($file->exists()){
				return $file;
			}
		}
		// if not found returning a fake image in order to save the future picture
		$file = new Image('/images/'.$ID);
		return $file;
	}
	
	function exists($ID){
		$file = $this->get($ID);
		if($file->exists()){
			return true;
		}
		return false;
	}
}

class ImageTransformer extends SusheeObject{
	
	var $destination_file = false;
	var $transformation_xml = false;
	var $final_image = false;
	
	function ImageTransformer($transformation_xml){
		$this->setXML($transformation_xml);
	}
	
	function setXML($transformation_xml){
		// Loading the XML describing the transformation
		if(is_object($transformation_xml)){
			$this->transformation_xml = $transformation_xml;
		}else{
			$this->transformation_xml = new XML($transformation_xml);
		}
		if(!$this->transformation_xml->loaded){
			debug_log('XML describing the image transformation is not valid.');
			return false;
		}
	}
	
	function execute(){
		require_once(dirname(__FILE__)."/../common/image_functions.inc.php");
		if(!$this->transformation_xml->loaded){
			return false;
		}
		// Getting the picture to transform
		$original_picture_path = $this->transformation_xml->getData("/*[1]/@path");
		$original_picture = new Image($original_picture_path);
		
		// Verifying this picture exists
		if(!$original_picture->exists()){
			debug_log("File doesn't exist : please be sure to use the Nectil notation. e.g. /media/picture.jpg");
			return false;
		}
		
		// generating a unique ID to represent this image and put it into the cache
		$id_array = array('imageTransform',$this->transformation_xml->toString(),$original_picture->getSize(),$original_picture->getModificationTime());
		$id = generateID($id_array);
		
		$cache = new ImageCache();
		$request = new Sushee_Request();
		// if picture exists in cache and request is in a mode where it can use the cache, returning the image from the cache
		if($cache->exists($id)  && $request->useCache()){
			
			$this->final_image = $cache->get($id);
			
			return true;
		}
		
		// Checking the picture is not too large to be handled
		$size_loaded = $original_picture->loadSize();
		$maxwidth = $GLOBALS['TranformationMaxWidth'];
		if (!isset($maxwidth))
			$maxwidth = 1600;
		$maxheight = $GLOBALS['TranformationMaxHeight'];
		if (!isset($maxheight))
			$maxheight = 1600;
		$maxsize = $GLOBALS['TranformationMaxSize'];
		if (!isset($maxsize))
			$maxsize = 512;
		if($size_loaded){
			if($original_picture->getWidth() > $maxwidth || $original_picture->getHeight() > $maxheight ){
				debug_log("File is too big. On-the-fly transformation is disabled on too big files (max. ".$maxwidth."x".$maxheight.").");
				return false;
			}
		}else if( $original_picture->getSize() > $maxsize ){
			debug_log("File is too large. On-the-fly transformation is disabled on too large files (max. ".$maxsize."Ko).");
			return false;
		}
		
		// Transforming the image
		$final_image_path = imageCreation($this->transformation_xml,'/*[1]',$original_picture->getCompletePath());
		if($final_image_path===false){
			return false;
		}
		$result_image = new Image($final_image_path);
		$cache_image = $cache->get($id);
		$cache_image->setExtension($result_image->getExtension());
		$this->final_image = $result_image->copy($cache_image);
		if(!$this->final_image){
			debug_log('Could not copy the resulting image in cache');
			$this->final_image = $result_image;
		}else{
			//$result_image->delete();
		}
		return true;
	}
	
	function getFinalImage(){
		return $this->final_image;
	}
	
}

class Image extends File{

	var $height=false;
	var $width=false;
	var $mimetype=false;
	
	var $IPTCTags = array (
		0=> 'recordversion',
		3=> 'objecttype',
		4=> 'objectattribute',
		5=> 'objectname',
		7=> 'editstatus',
		8=> 'editorialupdate',
		10=> 'urgency',
		12=> 'subjectreference',
		15=> 'category',
		20=> 'supplementalcategory',
		22=> 'fixtureidentifier',
		25=> 'keyword',
		26=> 'locationcode',
		27=> 'locationname',
		30=> 'releasedate',
		35=> 'releasetime',
		37=> 'expirationdate',
		38=> 'expirationtime',
		40=> 'specialinstructions',
		42=> 'actionadvised',
		45=> 'referenceservice',
		47=> 'referencedate',
		50=> 'referencenumber',
		55=> 'datecreated',
		60=> 'timecreated',
		62=> 'digitalcreationdate',
		63=> 'digitalcreationtime',
		65=> 'originatingprogram',
		70=> 'programversion',
		75=> 'objectcycle',
		80=> 'byline',
		85=> 'bylinetitle',
		90=> 'city',
		92=> 'sublocation',
		95=> 'province_state',
		100=> 'country_primary_locationcode',
		101=> 'country_primary_locationname',
		103=> 'originaltransmissionreference',
		105=> 'headline',
		110=> 'credit',
		115=> 'source',
		116=> 'copyrightnotice',
		118=> 'contact',
		120=> 'caption_abstract',
		122=> 'writer_editor',
		125=> 'caption_rasterised',
		130=> 'imagetype',
		131=> 'imageorientation',
		135=> 'languageidentifier'
	);

	function Image($nectil_path){
		parent::File($nectil_path);
	}
	
	function isImage(){
		$ext = $this->getExtension();
		$images_ext = array('jpg','jpeg','gif','png','bmp','jpe','swf');
		
		if(in_array($ext,$images_ext)){
			return true;
		}
		return false;
	}
	
	function getMimeType(){
		$this->loadSize();
		return $this->mimetype;
	}
	
	function output(){
		// checking it has been modified since last loading (header sent by navigator)
		$if_modified_since = preg_replace('/;.*$/', '', $HTTP_IF_MODIFIED_SINCE);
		$mtime = $this->getModificationTime();
		$gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
		// if it has not been modified, sending special header, telling the navigator to use cached version
		if ($if_modified_since == $gmdate_mod) {
		    header("HTTP/1.0 304 Not Modified");
		    exit;
		}

		$expires = 60*60*24*14;
		header("Pragma: public");
		header("Cache-Control: maxage=".$expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
		header("Last-Modified: $gmdate_mod");
		header("Content-Type: ".$this->getMimeType() );
		header("Content-Length: ".$this->getSize());
		@readfile($this->getCompletePath());
	}

	function loadSize(){
		if(!$this->height || !$this->width || !$this->mimetype){
			$size = @getimagesize($this->getCompletePath());
			if ($size){
				$this->height=$size[1];
				$this->width=$size[0];
				$this->mimetype=$size['mime'];
				return true;
			}
			return false;
		}
	}

	function getHeight(){
		$this->loadSize();
		return $this->height;
	}

	function getWidth(){
		$this->loadSize();
		return $this->width;
	}

	function getInfoXML(){
		$analyser = new ImageAnalyser();
		$analyser->setSource($this);
		$props = $analyser->getPropertiesXML();
		$this->log($props);
		return $props;
	}

	function getCreationDate(){
		$creationdate = date('Y-m-d H:i:s');

		if (function_exists('exif_read_data'))
		{
			$exif = exif_read_data($this->getCompletePath(),0,true);

			if (isset($exif['EXIF']['DateTimeOriginal']))
				$date = $exif['EXIF']['DateTimeOriginal'];
			elseif (isset($exif['IFD0']['DateTimeDigitized']))
				$date = $exif['IFD0']['DateTimeDigitized'];
			elseif (isset($exif['IFD0']['ImageDescription']))
				$date = $exif['IFD0']['ImageDescription'];
			elseif (isset($exif['THUMBNAIL']['DateTime']))
				$date = $exif['THUMBNAIL']['DateTime'];
			else
			{
				$name = $this->getShortName();
				// --- check if date is in file name YYYY*MM*DD*HH*MM*SS ---
				$pattern = ereg("([0-9]{4}).?([0-9]{2}).?([0-9]{2}).?([0-9]{2}).?([0-9]{2}).?([0-9]{2})",$name,$regs);
				if ($pattern)
					$creationdate = $regs[1].'-'.$regs[2].'-'.$regs[3].' '.$regs[4].':'.$regs[5].':'.$regs[6];
				elseif (isset($exif['FILE']['FileDateTime']))
	 				$creationdate = date('Y-m-d H:i:s',$exif['FILE']['FileDateTime']);

				return $creationdate; 
			}
			// --- format checker (EXIF dates are often YYYY:MM and not YYYY-MM) ---
			$creationdate = substr($date,0,4).'-'.substr($date,5,2).'-'.substr($date,8,2).' '.substr($date,11);
		}
		return $creationdate;
	}

	function getRawExif()
	{
		$path = $this->getCompletePath();
		$exif = exif_read_data($path,0,true);
		$data = 'EXIF data for : ' . $this->getName();
		foreach ($exif as $key => $section){
			foreach ($section as $name => $val){
				$data .= "$key.$name: $val";
			}
		}
		return $data;
	}

	function getExifXML()
	{
		$path = $this->getCompletePath();
		$exif = exif_read_data($path,0,true);
		$data = '';
		foreach ($exif as $key => $section){
			$data .= "<$key>";
			foreach ($section as $name => $val){
				$data .= "<$name>".encode_to_xml($val)."</$name>";
			}
			$data .= "</$key>";
		}
		return $data;
	}

	function charset_decode_x_mac_roman ($string) {
	    // don't do decoding when there are no 8bit symbols

	    $mac_roman = array(
	        "\x80" => '&#196;',
	        "\x81" => '&#197;',
	        "\x82" => '&#199;',
	        "\x83" => '&#201;',
	        "\x84" => '&#209;',
	        "\x85" => '&#214;',
	        "\x86" => '&#220;',
	        "\x87" => '&#225;',
	        "\x88" => '&#224;',
	        "\x89" => '&#226;',
	        "\x8A" => '&#228;',
	        "\x8B" => '&#227;',
	        "\x8C" => '&#229;',
	        "\x8D" => '&#231;',
	        "\x8E" => '&#233;',
	        "\x8F" => '&#232;',
	        "\x90" => '&#234;',
	        "\x91" => '&#235;',
	        "\x92" => '&#237;',
	        "\x93" => '&#236;',
	        "\x94" => '&#238;',
	        "\x95" => '&#239;',
	        "\x96" => '&#241;',
	        "\x97" => '&#243;',
	        "\x98" => '&#242;',
	        "\x99" => '&#244;',
	        "\x9A" => '&#246;',
	        "\x9B" => '&#245;',
	        "\x9C" => '&#250;',
	        "\x9D" => '&#249;',
	        "\x9E" => '&#251;',
	        "\x9F" => '&#252;',
	        "\xA0" => '&#8224;',
	        "\xA1" => '&#176;',
	        "\xA2" => '&#162;',
	        "\xA3" => '&#163;',
	        "\xA4" => '&#167;',
	        "\xA5" => '&#8226;',
	        "\xA6" => '&#182;',
	        "\xA7" => '&#223;',
	        "\xA8" => '&#174;',
	        "\xA9" => '&#169;',
	        "\xAA" => '&#8482;',
	        "\xAB" => '&#180;',
	        "\xAC" => '&#168;',
	        "\xAD" => '&#8800;',
	        "\xAE" => '&#198;',
	        "\xAF" => '&#216;',
	        "\xB0" => '&#8734;',
	        "\xB1" => '&#177;',
	        "\xB2" => '&#8804;',
	        "\xB3" => '&#8805;',
	        "\xB4" => '&#165;',
	        "\xB5" => '&#181;',
	        "\xB6" => '&#8706;',
	        "\xB7" => '&#8721;',
	        "\xB8" => '&#8719;',
	        "\xB9" => '&#960;',
	        "\xBA" => '&#8747;',
	        "\xBB" => '&#170;',
	        "\xBC" => '&#186;',
	        "\xBD" => '&#937;',
	        "\xBE" => '&#230;',
	        "\xBF" => '&#248;',
	        "\xC0" => '&#191;',
	        "\xC1" => '&#161;',
	        "\xC2" => '&#172;',
	        "\xC3" => '&#8730;',
	        "\xC4" => '&#402;',
	        "\xC5" => '&#8776;',
	        "\xC6" => '&#8710;',
	        "\xC7" => '&#171;',
	        "\xC8" => '&#187;',
	        "\xC9" => '&#8230;',
	        "\xCA" => '&#160;',
	        "\xCB" => '&#192;',
	        "\xCC" => '&#195;',
	        "\xCD" => '&#213;',
	        "\xCE" => '&#338;',
	        "\xCF" => '&#339;',
	        "\xD0" => '&#8211;',
	        "\xD1" => '&#8212;',
	        "\xD2" => '&#8220;',
	        "\xD3" => '&#8221;',
	        "\xD4" => '&#8216;',
	        "\xD5" => '&#8217;',
	        "\xD6" => '&#247;',
	        "\xD7" => '&#9674;',
	        "\xD8" => '&#255;',
	        "\xD9" => '&#376;',
	        "\xDA" => '&#8260;',
	        "\xDB" => '&#8364;',
	        "\xDC" => '&#8249;',
	        "\xDD" => '&#8250;',
	        "\xDE" => '&#64257;',
	        "\xDF" => '&#64258;',
	        "\xE0" => '&#8225;',
	        "\xE1" => '&#183;',
	        "\xE2" => '&#8218;',
	        "\xE3" => '&#8222;',
	        "\xE4" => '&#8240;',
	        "\xE5" => '&#194;',
	        "\xE6" => '&#202;',
	        "\xE7" => '&#193;',
	        "\xE8" => '&#203;',
	        "\xE9" => '&#200;',
	        "\xEA" => '&#205;',
	        "\xEB" => '&#206;',
	        "\xEC" => '&#207;',
	        "\xED" => '&#204;',
	        "\xEE" => '&#211;',
	        "\xEF" => '&#212;',
	        "\xF0" => '&#63743;',
	        "\xF1" => '&#210;',
	        "\xF2" => '&#218;',
	        "\xF3" => '&#219;',
	        "\xF4" => '&#217;',
	        "\xF5" => '&#305;',
	        "\xF6" => '&#710;',
	        "\xF7" => '&#732;',
	        "\xF8" => '&#175;',
	        "\xF9" => '&#728;',
	        "\xFA" => '&#729;',
	        "\xFB" => '&#730;',
	        "\xFC" => '&#184;',
	        "\xFD" => '&#733;',
	        "\xFE" => '&#731;',
	        "\xFF" => '&#711;');

	    $string = str_replace(array_keys($mac_roman), array_values($mac_roman), $string);

	    return $string;
	}

	function getIPTCXML()
	{
		$path = $this->getCompletePath();
		$size = getimagesize($path,$info);
		$xml = '<IPTC path="'.$path.'">';
		
		if(is_array($info))
		{
			$iptc = iptcparse($info["APP13"]);
			foreach (array_keys($iptc) as $s)
			{
				$c = count($iptc[$s]);

				if (substr($s,-3) < 235 && isset($iptc[$s][0]) && $c > 0 && $s != '2#000')
				{
					$code = substr($s,-3);
					$codeint = 0+$code;
					$data = $this->IPTCTags[$codeint];
					if (!$data)
						$data = 'custom';

					$xml.= '<'.$data.' id="'.$code.'">';

					for ($i=0; $i<$c; $i++)
					{
						$value = $iptc[$s][$i];


						if (isCP1252($value))
						{
							//$value = utf8_To_UnicodeEntities(utf8FromCP1252($value));	
							$value = $this->charset_decode_x_mac_roman($value);
						}
						else if (!isUTF8($value))
						{
							$value = iso_To_UnicodeEntities($value);	
						}

						$xml.= encode_to_xml($value);
						if ($i != ($c-1))
							$xml.= ', ';
					}

					$xml.= '</'.$data.'>';
				}
			}
		}
		$xml .= '</IPTC>';
		return $xml;
	}

	function getIPTC($data)
	{
		$path = $this->getCompletePath();
		$size = getimagesize($path,$info);
		if(is_array($info))
		{
			$iptc = iptcparse($info["APP13"]);
			if(is_array($iptc))
			{
				if (strlen($data) == 1)
					$data = '00' . $data;
				if (strlen($data) == 2)
					$data = '0' . $data;
				if (strlen($data) == 3)
					$data = '2#' . $data;

				$value = $iptc[$data][0];
				if (isset($value))
				{
					if (isCP1252($value))
					{
						//$value = utf8_To_UnicodeEntities(utf8FromCP1252($value));	
						$value = $this->charset_decode_x_mac_roman($value);
					}
					else if (!isUTF8($value))
					{
						$value = iso_To_UnicodeEntities($value);	
					}

					$xml.= encode_to_xml($value);

					return utf8_decode(utf8_To_UnicodeEntities($value));
				}
			}
		}

		return FALSE;	
	}
	
	function autoRotate()
	{
		$path = $this->getCompletePath();
		$exif = exif_read_data($path,'IFD0');
		$orientation = $exif['Orientation'];

		if (isset($orientation))
		{
			$rotate=false;
			$flip=false;

			switch($orientation)
			{
				case 1: // nothing
				break;

				case 2: // horizontal flip
					$flip='horizontal';
				break;

				case 3: // 180 rotate left
					$rotate=-180;
				break;

				case 4: // vertical flip
					$flip='vertical';
				break;

				case 5: // vertical flip + 90 rotate right
					$flip='vertical';
					$rotate=90;
				break;

				case 6: // 90 rotate right
					$rotate=90;
				break;

				case 7: // horizontal flip + 90 rotate right
					$flip='horizontal';
					$rotate=90;
				break;

				case 8: // 90 rotate left
					$rotate=-90;
				break;
			}

			if ($rotate)
			{
				$effect = new RotateImageEffect();
				$effect->setAngle($rotate);
				$effect->setSource(new File($path));
				$effect->setTarget(new File($path));
				$effect->execute();
			}

			if ($flip)
			{
				$effect = new FlipImageEffect();
				$effect->setAxis($flip);
				$effect->setSource(new File($path));
				$effect->setTarget(new File($path));
				$effect->execute();
			}
		}
	}
}

class ImageAnalyser extends SusheeObject{

	var $source;
	var $executable;
	var $properties = false;

	function ImageAnalyser(){
		$OS = getServerOS();
		if ($OS=='windows'){
			$imageExecutable = $GLOBALS["ImageMagickPath"]."identify";
			$imageExecutable = makeExecutableUsable($imageExecutable);
		}else{
			$imageExecutable = $GLOBALS["ImageMagickPath"]."identify";
		}
		$this->executable = $imageExecutable;

	}

	function setSource(&$file){
		$this->source = &$file;
	}

	function execute(){
		$this->properties = new Vector();
		$cmd = new CommandLine();
		$cmd->setCommand($this->executable.' -verbose '.$this->source->getCompletePath());
		$output = $cmd->execute();
		if(strpos($output,"\r\n")!==false){
			$delim = "\r\n";
		}else if(strpos($output,"\r")!==false){
			$delim = "\r";
		}else{
			$delim = "\n";
		}
		$lines = explode($delim,$output);
		array_shift($lines);
		array_shift($lines);
		$stack = new Stack();
		$stack->push($this->properties);
		foreach($lines as $line){
			$values = explode(':',$line);
			$name = $values[0];
			$value = $values[1];
			if(trim($name)!=''){
				$level = 0;
				while($name[$level]==' '){
					$level++;
				}
				while($prec_level>$level && $stack->getCurrent()!==false){
					$elt = &$stack->pop();
					$prec_level-=2;
				}
				if($stack->getCurrent()!==false){
					$where_to_push = &$stack->getCurrent();
				}
				if(!$value){
					$vect = &new Vector();
					$vect->setName(trim($name));
					$where_to_push->add(trim($name),$vect);
					$stack->push($vect);
				}else{
					$where_to_push->add($name,new ImageProperty(trim($name),trim($value)));
				}
				$prec_level = $level;
			}

		}
		return $output;
	}


	function getPropertiesXML(){
		if(!$this->properties)
			$this->execute();
		return $this->properties->getXML();
	}
}

class ImageProperty extends SusheeObject{
	var $name;
	var $value;

	function ImageProperty($name,$value){
		$this->name = $name;
		$this->value = $value;
	}

	function getXML(){
		$stringnode = new StringXMLNode($this->name,$this->value);
		return $stringnode->getXML();
	}
}

class ImageEffect extends SusheeObject{

	var $executable;
	var $status;
	var $target=false;

	function ImageEffect(){
		$OS = getServerOS();
		if ($OS=='windows'){
			$imageExecutable = $GLOBALS["ImageMagickPath"]."convert";
			$imageExecutable = makeExecutableUsable($imageExecutable);
		}else{
			$imageExecutable = $GLOBALS["ImageMagickPath"]."convert";
		}
		$this->executable = $imageExecutable;
	}

	function setSource(&$file){
		$this->source = &$file;
	}

	function getSource(){
		return $this->source;
	}

	function setTarget(&$file){
		$this->target = &$file;
	}

	function getTarget(){
		if(!$this->target){
			$this->target = &new TempFile();
		}
		return $this->target;
	}

	function getStatus(){
		return $this->status;
	}

	function getMessage(){
		return $this->message;
	}
}

class ResizeImageEffect extends ImageEffect{
	var $height=false;
	var $width=false;

	function setHeight($height){
		$this->height = $height;
	}
	function setWidth($width){
		$this->width = $width;
	}

	function execute(){
		$target = $this->getTarget();
		if(!$target->exists()){
			$target->create();
		}
		$width = $this->width;
		$height = $this->height;
		if($width && $height){
			$force = "!";
			$option = " -resize ".$width."x".$height.$force;
		}else if($width){
			$option = " -resize ".$width;
		}else if($height){
			$option = " -resize x".$height;
		}
		$options.='-strip -colorspace RGB '.$option.' ';
		$options.='"'.$this->source->getCompletePath().'" "'.$target->getCompletePath().'"';
		$command = $this->executable.' '.$options;
		$command = &new CommandLine($command);
		$command->execute();
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
			chmod_nectil($this->target->getCompletePath());
		}
		return $this->status;
	}
}

class ResampleImageEffect extends ImageEffect{
	var $resolution=72;

	function setResolution($resolution){
		$resolution = str_replace('dpi','',$resolution);
		$this->log($resolution);
		if(is_numeric($resolution)){
			$this->resolution = $resolution;
			return true;
		}else
			return false;
	}

	function execute(){
		$target = $this->getTarget();
		$options.='-strip -colorspace RGB -resample '.$this->resolution.' ';
		$options.='"'.$this->source->getCompletePath().'" "'.$target->getCompletePath().'"';
		$command = $this->executable.' '.$options;
		$command = &new CommandLine($command);
		$command->execute();
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
			chmod_nectil($this->target->getCompletePath());
		}
		return $this->status;
	}
}

class RotateImageEffect extends ImageEffect{
	var $angle=false;

	function setAngle($angle){
		$this->angle = $angle;
	}

	function execute(){
		$target = $this->getTarget();
		$options.='-strip -colorspace RGB -rotate '.$this->angle.' ';
		$options.='"'.$this->source->getCompletePath().'" "'.$target->getCompletePath().'"';
		$command = $this->executable.' '.$options;
		$command = &new CommandLine($command);
		$command->execute();
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
			chmod_nectil($this->target->getCompletePath());
		}
		return $this->status;
	}
}

class FlipImageEffect extends ImageEffect{
	var $axis=false;

	function setAxis($axis){
		$this->axis = $axis;
	}

	function execute(){
		$target = $this->getTarget();
		$axis = $this->axis;
		if ($axis=='vertical') $option = ' -flip';
		else $option = ' -flop';

		$options.='-strip -colorspace RGB ';
		$options.='"'.$this->source->getCompletePath().'" "'.$target->getCompletePath().'"';
		$command = $this->executable.' '.$options;
		$command = &new CommandLine($command);
		$command->execute();
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
			chmod_nectil($this->target->getCompletePath());
		}
		return $this->status;
	}
}

class CompositingImageEffect extends ImageEffect{
	function CompositingImageEffect(){
		if ($OS=='windows'){
			$compositeExecutable = $GLOBALS["ImageMagickPath"]."composite";
			$compositeExecutable  = makeExecutableUsable($compositeExecutable);
		}else{
			$compositeExecutable = $GLOBALS["ImageMagickPath"]."composite";
		}
		$this->executable = $compositeExecutable;
	}
}

class ImageScenario extends ImageEffect{
	var $name;

	function ImageScenario($name){
		$this->name = $name;
	}

	function execute(){
		$source = $this->getSource();
		if(!$source)
			return false;
		$location = $source->getCompletePath();
		return imageTransformation($location,$this->name);
	}
}

class MassImageTransformer extends SusheeObject{
	var $files = array();
	var $effect = false;

	function addFile($file){
		$this->files[]=$file;
	}

	function execute(){
		if(!$this->effect)
			return false;
		foreach($this->files as $file){
			$this->effect->setSource($file);
			$this->effect->setTarget($file);
			$this->effect->execute();
		}
		return true;
	}

	function setImageEffect($effect/* object interfacing ImageEffect */){
		$this->effect = $effect;
	}
}

class DistortedText extends ImageEffect{

	var $text;
	var $fontSize;
	var $font;
	var $target;

	function DistortedText($text){
		parent::ImageEffect();
		$this->text = $text;
		$this->font = 'Helvetica';
		$this->target = false;
		$this->backgroundColor = 'white';
		$this->color = 'black';
		$this->waveAmplitude = 3;
		$this->waveLength = 30;
	}

	function setFontSize($fontSize){
		$this->fontSize = $fontSize;
	}

	function setColor($color){
		$this->color = $color;
	}

	function setBackgroundColor($backgroundColor){
		$this->backgroundColor = $backgroundColor;
	}

	function getTarget(){
		return $this->target;
	}

	function setTarget($target){
		$this->target = $target;
	}

	function setFont($font){
		$this->font = $font;
	}

	function execute(){
		if(!$this->target)
			$this->target = &new TempFile();
		$this->target->setExtension('gif');
		$options.=' -font "'.$this->font.'"';
		$options.=' -pointsize "'.$this->fontSize.'"';
		$options.=' -background "'.$this->backgroundColor.'"';
		$options.=' -fill "'.$this->color.'"';
		$options.=' label:"'.encodeQuote($this->text).'"';
		$options.=' -wave "'.$this->waveAmplitude.'x'.$this->waveLength.'"';
		$options.=' "'.$this->target->getCompletePath().'"';
		$command = $this->executable.$options;
		$command = &new CommandLine($command);
		$command->execute();
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
			chmod_nectil($this->target->getCompletePath());
		}
		return $this->status;
	}
}
?>