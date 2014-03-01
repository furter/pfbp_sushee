<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getenum.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

define('ENUM_NUMERIC',1);
define('ENUM_UPPER_ALPHA',2);
define('ENUM_LOWER_ALPHA',3);

class getEnum extends NQLOperation{
	var $start = 0;
	var $end = 10;
	var $step = 1;
	var $mode = ENUM_NUMERIC;
	function parse(){
		$start = $this->firstNode->valueOf('START');
		$end = $this->firstNode->valueOf('END');
		$step = $this->firstNode->valueOf('STEP');
		if($start===false || $start===''){
			$this->setError('Start is empty');
			return false;
		}
		if($end===false || $end===''){
			$this->setError('End is empty');
			return false;
		}
		if(!is_numeric($start) || !is_numeric($end)){
			$start = ord($start);
			$end = ord($end);
			if( $start < 97){
				$this->mode = ENUM_UPPER_ALPHA;
				$end = ord(strtoupper(chr($end)));
			}else{
				$this->mode = ENUM_LOWER_ALPHA;
				$end = ord(strtolower(chr($end)));
			}	
		}
		if($start > $end && ($step===false || $step>0)){
			if($step==false)
				$step = -1;
			elseif($step>0)
				$step = -$step;
		}
		$this->start = $start;
		$this->end = $end;
		if($step)
			$this->step = $step;
		return true;
	}
	
	function getItem($index){
		switch($this->mode){
			case ENUM_UPPER_ALPHA;
			case ENUM_LOWER_ALPHA:
				return chr($index);
				break;
			default: 
				return $index;
		}
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		$xml.=		'<ENUM>';
		if($this->start > $this->end){
			for($i=$this->start;$i>=$this->end;$i+=$this->step){
				$xml.=		'<E>'.$this->getItem($i).'</E>';
			}
		}else{
			for($i=$this->start;$i<=$this->end;$i+=$this->step){
				$xml.=		'<E>'.$this->getItem($i).'</E>';
			}
		}
		
		$xml.=		'</ENUM>';
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
	
	function getXML(){
		return $this->xml;
	}
	
	
}