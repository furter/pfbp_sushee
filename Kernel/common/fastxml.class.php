<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/fastxml.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");

class FastXMLParser extends SusheeObject{
	var $xml_str;
	var $start_openingtag_pos = false;
	var $end_openingtag_pos = false;
	var $start_closingtag_pos = false;
	var $end_closingtag_pos = false;
	
	function FastXMLParser($xml_str){
		$this->xml_str = $xml_str;
	}
	
	function getNextElement($tagname){
		// $this->logFunction('FastXMLParser.getNextElement ');
		$start_openingtag_pos = strpos($this->xml_str,'<'.$tagname.'>',$this->end_closingtag_pos);
		if($start_openingtag_pos===false){
			$start_openingtag_pos = strpos($this->xml_str,'<'.$tagname.' ',$this->end_closingtag_pos);
		}
		if($start_openingtag_pos !== false){
			$end_openingtag_pos = strpos($this->xml_str,'>',$start_openingtag_pos);
			if($end_openingtag_pos !== false){
				$end_openingtag_pos++;
				$start_closingtag_pos = strpos($this->xml_str,'</'.$tagname,$start_openingtag_pos);
				if($start_closingtag_pos !== false){
					$end_closingtag_pos = strpos($this->xml_str,'>',$start_openingtag_pos);
					if($end_closingtag_pos !== false){
						$end_closingtag_pos++;
						
						$this->start_openingtag_pos = $start_openingtag_pos;
						$this->end_openingtag_pos = $end_openingtag_pos;
						$this->start_closingtag_pos = $start_closingtag_pos;
						$this->end_closingtag_pos = $end_closingtag_pos;
						
						$content = substr($this->xml_str,$this->end_openingtag_pos,$this->start_closingtag_pos - $this->end_openingtag_pos);
						
						return new FastXMLParser($content);
						return true;
					}
				}
			}
			
			
		}
		return false;
	}
	
	function toString(){
		// $this->logFunction('FastXMLParser.toString');
		return $this->xml_str;
	}
	
}


?>