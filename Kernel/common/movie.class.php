<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/movie.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/commandline.class.php");
require_once(dirname(__FILE__)."/../common/date.class.php");
require_once(dirname(__FILE__)."/../common/image.class.php");
require_once(dirname(__FILE__)."/../common/getid3/getid3.php");

/*
Sushee_Movie : handles a movie and gives special informations about it (flv, quicktime, mp4)
*/
class Sushee_Movie extends Sushee_File{
	
	function Sushee_Movie($path){
		parent::Sushee_File($path);
	}
	
	function getLength(){
		
		// calling open source PHP class to parse the tags of the movie
		$getID3 = new getID3();
		$file_info = $getID3->analyze($this->getCompletePath());
		
		// getting a readable version of the tags into the file_info array
		getid3_lib::CopyTagsToComments($file_info);
		
		$time_unix = strtotime("00:".$file_info['playtime_string']);
		
		$time = new Time();
		$time->addSeconds(date("s",$time_unix));
		$time->addMinutes(date("i",$time_unix));
		$time->addHours(date("H",$time_unix));
		
		return $time;
	}
}
/*
Sushee_ImageSushee_MovieEffects : Effects Stack transforming a movie to a picture (contains several Sushee_MovieEffect)
called by <PROCESS><MOVIE> sushee command
*/
class Sushee_ImageMovieEffects extends Sushee_FileEffects{
	
	var $effects;
	var $source;
	var $target;
	var $status;
	var $message;
	
	function Sushee_ImageMovieEffects(){
		$this->effects = array();
		$this->status = false;
	}
	
	function parseXMLNodes($effectsNodes){
		// parse the effects XML configuration
		foreach($effectsNodes as $effectNode){
			$nodeName = $effectNode->nodeName();
			
			switch(strtolower($nodeName)){
				case 'resize':
					// adding the effect in the effects stack
					$newEffect = new ResizeImageEffect();
					$newEffect->setWidth($effectNode->valueOf('@width'));
					$newEffect->setHeight($effectNode->valueOf('@height'));
					$this->add($newEffect);
					break;
				case 'convert':
					// adding the effect in the effects stack
					$newEffect = new Sushee_MovieImage();
					$newEffect->setTimecode($effectNode->valueOf('@timecode'));
					$this->add($newEffect);
					break;
				default:
			}
		}
	}
}
/*
Sushee_ImageSushee_MovieEffects : Effects Stack transforming a movie to another movie (contains several Sushee_MovieEffect)
called by <PROCESS><MOVIE> sushee command
*/
class Sushee_MovieEffects extends Sushee_FileEffects{
	
	var $effects;
	var $source;
	var $target;
	var $status;
	var $message;
	
	function Sushee_MovieEffects(){
		$this->effects = array();
		$this->status = false;
	}
	
	function parseXMLNodes($effectsNodes){
		// parse the effects XML configuration
		foreach($effectsNodes as $effectNode){
			
			$nodeName = $effectNode->nodeName();
			switch(strtolower($nodeName)){
				case 'resize':
					// adding the effect in the effects stack
					$newEffect = new Sushee_MovieResize();
					$newEffect->setWidth($effectNode->valueOf('@width'));
					$newEffect->setHeight($effectNode->valueOf('@height'));
					$this->add($newEffect);
					
					break;
				case 'convert':
					// adding the effect in the effects stack
					$newEffect = new Sushee_MovieConversion();
					$newEffect->setFormat($effectNode->valueOf('@format'));
					$newEffect->setCodec($effectNode->valueOf('@codec'));
					$newEffect->setBitrate($effectNode->valueOf('@bitrate'));
					$newEffect->setFps($effectNode->valueOf('@fps'));
					$newEffect->setMaxfilesize($effectNode->valueOf('@max-filesize'));
					$newEffect->setSize($effectNode->valueOf('@size'));
					$newEffect->setAudioCodec($effectNode->valueOf('@audio-codec'));
					$newEffect->setAudioFreq($effectNode->valueOf('@audio-freq'));
					$newEffect->setAudioBitrate($effectNode->valueOf('@audio-bitrate'));
					$newEffect->setAudioChannels($effectNode->valueOf('@audio-channels'));
					$this->add($newEffect);
					
					break;
				case 'cut':
					// adding the effect in the effects stack
					$newEffect = new Sushee_MovieCut();
					$newEffect->setDuration($effectNode->valueOf('@duration'));
					$newEffect->setStart($effectNode->valueOf('@start'));
					$newEffect->setAudioBitrate($effectNode->valueOf('@audio-bitrate'));
					$this->add($newEffect);
					
					break;
				default:
			}
		}
	}
}
/*
Sushee_MovieEffect : general class handling an effect on a movie 
*/
class Sushee_MovieEffect extends SusheeObject{
	
	var $source;
	var $target;
	var $executable;
	var $status;
	var $message;
	
	function Sushee_MovieEffect(){
		// using ffmpeg to apply the effect on the movie
		if(Sushee_instance::getConfigValue('phpExecutable'))
			$this->executable = Sushee_instance::getConfigValue('phpExecutable');
		else{
			$this->executable = 'ffmpeg';
		}
		
		$this->status = false;
	}
	
	function setSource(&$movie){
		$this->source = &$movie;
	}
	
	function setTarget(&$movie){
		$this->target = &$movie;
	}
	
	function execute(){}
	
	function getStatus(){
		return $this->status;
	}
	
	function getMessage(){
		return $this->message;
	}
}
/*
Sushee_MovieResize : movie resize
*/
class Sushee_MovieResize extends Sushee_MovieEffect{
	
	var $width;
	var $height;
	
	function Sushee_MovieResize(){
		parent::Sushee_MovieEffect();
	}
	
	function setWidth($width){
		$this->width = $width;
	}
	
	function setHeight($height){
		$this->height = $height;
	}
	
	function execute(){
		// calling ffmpeg external application to apply the effect
		$options = 
			'-i '.escapeshellarg($this->source->getCompletePath())
			.' -s '.escapeshellarg($this->width).'x'.escapeshellarg($this->height)
			.' -y '.escapeshellarg($this->target->getCompletePath());
		$command = $this->executable.' '.$options;
		$command = &new Sushee_CommandLine($command);
		
		$this->message = $command->execute();
		
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
		}
		return $this->status;
	}
}
/*
Sushee_MovieConversion : converting the movie to another format
*/
class Sushee_MovieConversion extends Sushee_MovieEffect{
	
	var $format;
	var $codec;
	var $bitrate;
	var $fps;
	var $maxfilesize;
	var $size;
	var $acodec;
	var $afreq;
	var $abitrate;
	var $achannels;
	
	function Sushee_MovieConversion(){
		parent::Sushee_MovieEffect();
	}
	
	function setFormat($format){
		$this->format = $format;
	}
	
	function setCodec($codec){
		$this->codec = $codec;
	}
	
	function setBitrate($bitrate){
		$this->bitrate = $bitrate;
	}
	
	function setFps($fps){
		$this->fps = $fps;
	}
	
	function setMaxfilesize($maxfilesize){
		$this->maxfilesize = $maxfilesize;
	}
	
	function setSize($size){
		$this->size = $size;
	}
	
	function setAudioCodec($acodec){
		$this->acodec = $acodec;
	}
	
	function setAudioFreq($afreq){
		$this->afreq = $afreq;
	}
	
	function setAudioBitrate($abitrate){
		$this->abitrate = $abitrate;
	}
	
	function setAudioChannels($achannels){
		$this->achannels = $achannels;
	}
	
	function execute(){
		// calling ffmpeg external application to apply the effect
		$this->target->setExtension($this->format);
		$options = 
			'-i '.escapeshellarg($this->source->getCompletePath());
		if($this->codec)
			$options.=' -vcodec '.escapeshellarg($this->codec);
		if($this->bitrate)
			$options.=' -b '.escapeshellarg($this->bitrate);
		if($this->fps)
			$options.=' -r '.escapeshellarg($this->fps);
		if ($this->maxfilesize)
			$options .= ' -fs '.escapeshellarg($this->maxfilesize);
		if($this->size)
			$options.=' -s '.escapeshellarg($this->size);
		if($this->acodec)
			$options.=' -acodec '.escapeshellarg($this->acodec);
		if($this->afreq)
			$options.=' -ar '.escapeshellarg($this->afreq);
		if($this->abitrate)
			$options.=' -ab '.escapeshellarg($this->abitrate);
		if($this->achannels)
			$options.=' -ac '.escapeshellarg($this->achannels);
		$options.=' -y '
			.escapeshellarg($this->target->getCompletePath());
		$command = $this->executable.' '.$options;
		
		$command = &new Sushee_CommandLine($command);
		$this->message = $command->execute();
		
		// process has failed, file is invalid
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
		}
		return $this->status;
	}
}
/*
Sushee_MovieCut : cutting a part of the movie
*/
class Sushee_MovieCut extends Sushee_MovieEffect{
	
	var $start;
	var $duration;
	var $abitrate= '192k';
	
	function setStart($start){
		$this->start = $start;
	}
	
	function setDuration($duration){
		$this->duration = $duration;
	}
	
	function setAudioBitrate($abitrate){
		if($abitrate !== false)
			$this->abitrate = $abitrate;
	}
	
	function execute(){
		// calling ffmpeg external application to apply the effect
		$options = 
			'-i "'.$this->source->getCompletePath().'"';
		if($this->duration){
			$time = new Time($this->duration);
			$options.=' -t '.escapeshellarg($time->getSQLTime());
		}
		if($this->abitrate){
			$options.=' -ab '.escapeshellarg($this->abitrate);
		}
		if($this->start){
			$time = new Time($this->start);
			$options.=' -ss '.escapeshellarg($time->getSQLTime());
		}
		$options.=' -y '
			.escapeshellarg($this->target->getCompletePath());
		$command = $this->executable.' '.$options;
		
		$command = &new Sushee_CommandLine($command);
		$this->message = $command->execute();
		
		// process has failed, file is invalid
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
		}
		return $this->status;
	}
}
/*
Sushee_MovieImage : getting a specific image out of a movie (in Jpg only)
*/
class Sushee_MovieImage extends Sushee_MovieEffect{
	
	var $timecode = false;
	
	function setTimecode($timecode){
		$this->timecode = $timecode;
	}
	
	function execute(){
		// calling ffmpeg external application to apply the effect
		/*'-vcodec mjpeg -vframes 1 -ss 00:00:04  -an -f rawvideo';*/
		$this->target->setExtension('jpg');
		$options = 
			'-i '.escapeshellarg($this->source->getCompletePath());
		$options.=' -vcodec mjpeg -vframes 1 ';
		if($this->timecode){
			if(strpos($this->timecode,':'))
				$options.=' -ss '.escapeshellarg($this->timecode);
			else{
				$time = new Time($this->timecode);
				$options.=' -ss '.escapeshellarg($time->getSQLTime());
			}
			
		}
		$options.=' -an -f rawvideo';
		$options.=' -y '
			.escapeshellarg($this->target->getCompletePath());
		$command = $this->executable.' '.$options;
		
		$command = &new Sushee_CommandLine($command);
		$this->message = $command->execute();
		
		// process has failed, file is invalid
		if(!$this->target->exists() || $this->target->getSize()==0){
			$this->status = false;
			$this->message = $command->getOutput();
		}else{
			$this->status = true;
		}
		return $this->status;
	}
}

?>