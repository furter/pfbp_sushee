<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchCalendar.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/date.class.php');
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');


class searchCalendar extends NQLOperation{
	var $query;
	function parse(){
		$start_datetime = $this->firstNode->valueOf('START');
		$end_datetime = $this->firstNode->valueOf('END');
		if(!$start_datetime){
			$this->setError('Start date is empty');
			return false;
		}
		if(!$end_datetime){
			$this->setError('End date is empty');
			return false;
		}
		$this->start = new Date($start_datetime);
		$this->end = new Date($end_datetime);
		if(!$this->start->isValid()){
			$this->setError('Start date is invalid');
			return false;
		}
		if(!$this->end->isValid()){
			$this->setError('End date is invalid');
			return false;
		}
		if($this->firstNode->exists('QUERY')){
			$this->query = $this->firstNode->copyOf('QUERY');
		}else
			$this->query = false;
		return true;
	}
	
	function fillToSunday($current){
		$xml = '';
		$filling = $current->duplicate();
		
		// --- manage sunday ---
		$weekday = $filling->getWeekday();
		if ($weekday==0) $weekday = 7;

		for($i=$weekday;$i<=7;$i++){
			$xml.='<FILLING ID="'.$filling->getDay().'" weekday="'.$filling->getWeekday().'" month="'.$filling->getMonth().'" year="'.$filling->getYear().'"/>';
			$filling->addDay();
		}
		return $xml;
	}
	
	function fillFromMonday($current){
		$xml = '';
		$filling = $current->duplicate();
		
		// --- manage sunday ---
		$weekday = $filling->getWeekday();
		if ($weekday==0) $weekday = 7;

		$filling->addDay(-$weekday+1);
		for($i=1;$i<$weekday;$i++){
			$xml.='<FILLING ID="'.$filling->getDay().'" weekday="'.$filling->getWeekday().'" month="'.$filling->getMonth().'" year="'.$filling->getYear().'"/>';
			$filling->addDay();
		}
		return $xml;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		$xml.='<CALENDAR>';
		$xml.='<START>'.$this->start->getDate().'</START>';
		$xml.='<END>'.$this->end->getDate().'</END>';
		$current = $this->start;
		$xml.='<YEAR ID="'.$current->getYear().'">';
		$xml.='<MONTH ID="'.$current->getMonth().'">';
		$xml.='<WEEK ID="'.$current->getWeekNumber().'">';
		if($current->getWeekday()!=1){
			$xml.=$this->fillFromMonday($current);
		}
		while($current->isLowerOrEqualThan($this->end)){
			$xml.='<DAY ID="'.$current->getDay().'" weekday="'.$current->getWeekday().'"';
			if($current->isToday())
				$xml.=' today="true"';

			if($this->query)
			{
				$xml.='>';
				$today = $GLOBALS['sushee_today'];
				$GLOBALS['sushee_today'] = $current->getDatetime();
				$xml.=request($this->query,true,false,false,false,$GLOBALS["restrict_language"],false,$GLOBALS["php_request"],$GLOBALS["dev_request"]);
				$xml.='</DAY>';
			}
			else
			{
				$xml.='/>';	
			}

			$previous = $current->duplicate();
			$current->addDay();
			if($current->isLowerOrEqualThan($this->end)){ // not the last day of the serie
				if($previous->getWeekNumber() != $current->getWeekNumber()){
					$xml.='</WEEK>';
					if ($previous->getMonth() == $current->getMonth())
						$xml.='<WEEK ID="'.$current->getWeekNumber().'">';
				}
				if($previous->getMonth()!=$current->getMonth()){
					if($current->getWeekday()!=1)
					{
						$xml.=$this->fillToSunday($current);
						$xml.='</WEEK>';
					}
					$xml.='</MONTH>';
					if($previous->getYear() == $current->getYear()){
						$xml.='<MONTH ID="'.$current->getMonth().'"><WEEK ID="'.$current->getWeekNumber().'">';
						if($current->getWeekday()!=1){
							$xml.=$this->fillFromMonday($current);
						}
					}
				}
				if($previous->getYear()!=$current->getYear()){
					$xml.='</YEAR><YEAR ID="'.$current->getYear().'"><MONTH ID="'.$current->getMonth().'"><WEEK ID="'.$current->getWeekNumber().'">';
				}
			}
		}
		if($current->getWeekday()!=1){
			// --- must complete with empty days to have the whole week ---
			$xml.=$this->fillToSunday($current);
		}
		$xml.='</WEEK>';
		$xml.='</MONTH>';
		$xml.='</YEAR>';
		$xml.='</CALENDAR>';
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
	
	function getXML(){
		return $this->xml;
	}
	
	
	
	
}



?>