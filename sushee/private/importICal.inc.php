<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/importICal.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/date.class.php");

class FileWithLines extends File{

	function FileWithLines($path){
		File::File($path);
		ini_set('auto_detect_line_endings','1');
	}

	function open(){
		$this->fp = @fopen($this->getCompletePath(),'r');
		return $this->fp;
	}

	function close(){
		if($this->fp)
		fclose($this->fp);
	}

	function getNextLine(){
		if(!$this->fp)
		$this->open();
		if($this->fp){
			$line = fgets( $this->fp);
			return $line;
		}else{
			return false;
		}

	}
}

class ICalValue extends SusheeObject{
	var $value;
	var $key;
	var $attr;

	function ICalValue($key,$value,$attr){
		$this->key = $key;
		$this->value = $value;
		$this->attr = $attr;
	}
}

class ICalEvent extends SusheeObject{

	var $values;

	function ICalEvent($str){
		$this->log($str);
		$lines = explode("\n",$str);
		foreach($lines as $line){
			if($line){
				list($key, $value) = explode(':', $line, 2);
				list($key,$attr) = explode(';', $key, 2);
				$this->setValue($key,new ICalValue($key,$value,$attr));
			}
		}
	}

	function getValue($key){
		if(isset($this->values[strtolower($key)]))
		return $this->values[strtolower($key)]->value;
	}

	function setValue($key,$value){
		$this->log('Value '.$key.' = '.$value->value);
		$this->values[$key] = $value;
	}
}

class ICalFile extends FileWithLines{

	function getNextEvent(){
		$event_str = '';
		$found_event = false;
		while($line = $this->getNextLine()){
			$cleaned_line = $this->clean($line);
			if($cleaned_line=='begin:vevent'){
				$found_event = true;
				break;
			}
		}
		if(!$found_event){
			return false;
		}
		while($line = $this->getNextLine()){
			$cleaned_line = $this->clean($line);
			$event_str.=$cleaned_line."\n";
			if($cleaned_line=='end:vevent'){
				return new ICalEvent($event_str);
			}
		}
		return false;
	}

	function clean($str){
		return strtolower(trim($str));
	}

}

/*class Logdev extends SusheeObject{

	var $path;
	var $lineNum;
	var $date;
	var $id;
	var $info;
	var $prev;
	var $next;


	function Logdev($path,$num,$date,$id,$info,$prev,$next){
		$this->path = $path;
		$this->lineNum = $num;
		$this->date = $date;
		$this->id = $id;
		$this->prev = $prev;
		$this->next = $next;
		$this->info = $info;
		
	}


	function getPath(){
		return $this->path;
	}


	function getLineNum(){
		return $this->line_num;
	}

	function getDate(){
		return $this->date;
	}

	function getId(){
		return $this->id;
	}

	function getInfo(){
		return $this->info;
	}

	function getPrev(){
		return $this->prev;
	}

	function getNext(){
		return $this->next;
	}


	function setPath($path){
		$this->path = $path;
	}


	function setLineNum($num){
		$this->lineNum = $num;
	}

	function setDate($date){
		$this->date = $date;
	}

	function setId($id){
		$this->id = $id;
	}

	function setInfo($info){
		$this->info = $info;
	}

	function setPrev($prev){
		$this->prev = $prev;
	}

	 function setNext ($next){
		$this->next = $next;
	}
	
	function getXML(){
		$xml = '<LOGDEV>'.$this->newline.'<INFO>'.$this->newline;
		$xml .= '<USERID>'.$this->getId().'</USERID>'.$this->newline;
		$xml .= '<DATE>'.$this->getDate().'</DATE>'.$this->newline;
		$xml .= '<PATH>'.$this->getPath().'</PATH>'.$this->newline;
		$xml .= '<CONTENT>'.$this->getInfo().'</CONTENT>'.$this->newline;
		$xml .= '</INFO>'.$this->newline.'</LOGDEV>'.$this->newline;
		return $xml;
	}
		
	
}
*/
class importICal extends RetrieveOperation{

	var $file = false;

	function parse(){
		$path = $this->firstNode->valueOf('@path');
		$this->file = new ICalFile($path);
		if(!$this->file->exists()){
			$this->setError('File `'.$path.'` doesn\'t exist');
			return false;
		}
		return true;
	}

	function operate(){
		$checkExistenceNQL = new NQL(false);
		$nqlFile = new TempFile();
		$nqlFile->setExtension('nql');
		$nqlFile->append('<?xml version="1.0"?><QUERY>');
		$i = 0;
		while($event = $this->file->getNextEvent()){
			$evt_start = $event->getValue('DTSTART');
			$evt_end = $event->getValue('DTEND');
			$summary = $event->getValue('SUMMARY');
				
			$start = new Date();
			$start->setYear(substr($evt_start,0,4));
			$start->setMonth(substr($evt_start,4,2));
			$start->setDay(substr($evt_start,6,2));
			$start->setHour(substr($evt_start,9,2));
			$start->setMinute(substr($evt_start,11,2));
			$start->setSecond(substr($evt_start,13,2));
			$end = new Date();
			$end->setYear(substr($evt_end,0,4));
			$end->setMonth(substr($evt_end,4,2));
			$end->setDay(substr($evt_end,6,2));
			$end->setHour(substr($evt_end,9,2));
			$end->setMinute(substr($evt_end,11,2));
			$end->setSecond(substr($evt_end,13,2));
				
			$checkExistenceNQL->addCommand(
				'<SEARCH name="check">
					<EVENT>
						<INFO>
							<START>'.$start->getDatetime().'</START>
							<END>'.$start->getDatetime().'</END>
							<TITLE>'.encode_to_xml($summary).'</TITLE>
						</INFO>
					</EVENT>
				</SEARCH>');
			$evt = $checkExistenceNQL->getElement('/RESPONSE/RESULTS/EVENT');
			if($evt){

			}else{
				$newEvtNQL =
				'<CREATE>
					<EVENT>
						<INFO>
							<START>'.$start->getDatetime().'</START><!--DTSTART>'.$evt_start.'</DTSTART-->
							<END>'.$end->getDatetime().'</END><!--DTEND>'.$evt_end.'</DTEND-->
							<TITLE>'.encode_to_xml($summary).'</TITLE>
						</INFO>
					</EVENT>
				</CREATE>';
				$nqlFile->append($newEvtNQL);
				$i++;
			}
			if($i>2){
				break;
			}
		}


		$nqlFile->append('</QUERY>');
		$xml = '';
		$xml.='<NQL>'.$nqlFile->getPath().'</NQL>';

		$this->xml = $xml;
		$this->xml = $nqlFile->toString();
		return true;
	}
}


?>