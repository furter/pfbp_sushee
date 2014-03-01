<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/date.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");

class Date extends SusheeObject
{
	var $year;
	var $month;
	var $day;
	var $hour;
	var $minute;
	var $second;
	var $time;

	function isValid()
	{
		if($this->year==0 || $this->day==0 || $this->month==0)
		return false;
		if($this->day>31)
		return false;
		if($this->month>12)
		return false;
		if($this->hour!=0 && $this->hour>24)
		return false;
		if($this->minute!=0 && $this->minute>59)
		return false;
		if($this->second!=0 && $this->second>59)
		return false;
		return true;
	}

	function Date(/* SQL datetime */ $datetime=false)
	{

		if($datetime===false)
		{
			$datetime = date('Y-m-d H:i:s');
		}

		$this->year = (int)substr($datetime,0,4);
		$this->month = (int)substr($datetime,5,2);
		$this->day = (int)substr($datetime,8,2);

		if($this->day > $this->DaysInMonth())
		{
			$this->day = $this->DaysInMonth();
		}

		$this->hour = (int)substr($datetime,11,2);
		$this->minute = (int)substr($datetime,14,2);
		$this->second = (int)substr($datetime,17,2);

		$this->computeTime();
	}

	function computeTime()
	{
		$this->time = mktime($this->hour,$this->minute,$this->second,$this->month,$this->day,$this->year);
	}

	function getMonth()
	{
		return $this->pad($this->month);
	}

	function setMonth($month)
	{
		$this->month = (int)$month;
		$this->computeTime();
	}

	function getYear()
	{
		return str_pad($this->year,4,'0',STR_PAD_LEFT);
	}

	function setYear($year)
	{
		$this->year = (int)$year;
		$this->computeTime();
	}

	function setHour($hour)
	{
		$this->hour = (int)$hour;
		$this->computeTime();
	}

	function setMinute($min)
	{
		$this->minute = (int)$min;
		$this->computeTime();
	}

	function getDay(){
		return $this->pad($this->day);
	}

	function setDay($day)
	{
		$this->day = (int)$day;
		$days_in_month = $this->DaysInMonth();
		if($this->day > $days_in_month && $days_in_month!=0)
		{
			$this->day = $this->DaysInMonth();
		}
		$this->computeTime();
	}

	function getHour()
	{
		return $this->pad($this->hour);
	}

	function getMinute()
	{
		return $this->pad($this->minute);
	}

	function getSecond()
	{
		return $this->pad($this->second);
	}

	function setSecond($sec)
	{
		$this->second = (int)$sec;
		$this->computeTime();
	}

	function getDatetime()
	{
		return
		$this->getYear().'-'.
		$this->getMonth().'-'.
		$this->getDay().' '.
		$this->getHour().':'.
		$this->getMinute().':'.
		$this->getSecond();
	}
	
	function toString()
	{
		return $this->getDatetime();
	}

	function getDate()
	{
		return
		$this->getYear().'-'.
		$this->getMonth().'-'.
		$this->getDay();
	}

	function pad($nb){
		if($nb===false)
		$nb = 0;
		if($nb<10){
			return '0'.((int)$nb);
		}else
		return $nb;
	}

	function addYear($nb=1){
		$new_year = $this->year + $nb;
		$this->year = $new_year;
		$this->computeTime();
	}

	function addMonth($nb=1){
		$new_month = $this->month + $nb;
		$last_day_in_month = false;
		if($this->day == $this->DaysInMonth()){
			$last_day_in_month = true;
		}
		if($new_month>12){
			$new_year = $this->year + floor($new_month / 12);
			$new_month = $new_month % 12;
			$this->year = $new_year;
		}
		if($new_month<=0){
			$new_month--;
			$new_year = $this->year - ceil((-$new_month) / 12);
			$new_month = 12 + (($new_month+1) % 12) ;
			$this->year = $new_year;
		}
		$this->month = $new_month;
		if($this->day > $this->DaysInMonth() || $last_day_in_month){
			$this->day = $this->DaysInMonth();
		}
		$this->computeTime();
	}

	function addDay($nb=1){
		/*$this->time+=$nb*24*3600;
		 $this->year = date('Y',$this->time);
		 $this->month = date('m',$this->time);
		 $this->day = date('d',$this->time);*/
		$abs_nb = abs($nb);
		$i = 0;
		if($nb>0){
			while($i<$abs_nb){
				$this->day+=1;
				if($this->day>$this->DaysInMonth()){
					$this->day=1;
					$this->month++;
					if($this->month>12){
						$this->year++;
						$this->month = 1;
					}
				}
				$i++;
			}
		}else{
			while($i<$abs_nb){
				$this->day-=1;
				if($this->day<1){
					$this->month--;
					if($this->month<1){
						$this->year--;
						$this->month = 12;
					}
					$this->day=$this->DaysInMonth();
				}
				$i++;
			}
		}

		$this->computeTime();
	}

	function addSecond($nb=1){
		$this->time+=$nb;
		$this->year = date('Y',$this->time);
		$this->month = date('m',$this->time);
		$this->day = date('d',$this->time);
		$this->hour = date('H',$this->time);
		$this->minute = date('i',$this->time);
		$this->second = date('s',$this->time);
	}

	function addMinute($nb=1){
		$this->time+=$nb*60;
		$this->year = date('Y',$this->time);
		$this->month = date('m',$this->time);
		$this->day = date('d',$this->time);
		$this->hour = date('H',$this->time);
		$this->minute = date('i',$this->time);
		$this->second = date('s',$this->time);
	}

	function addHour($nb=1){
		$this->time+=$nb*3600;
		$this->year = date('Y',$this->time);
		$this->month = date('m',$this->time);
		$this->day = date('d',$this->time);
		$this->hour = date('H',$this->time);
		$this->minute = date('i',$this->time);
		$this->second = date('s',$this->time);
	}


	function getWeekdayPosition(){ // if it's the first, the second, the third {monday,tuesday, wednesday, etc} of the month
		$month_start = $this->duplicate();
		$month_start->setDay(1); // returning at the first day of the month
		$weekday_position = 0;
		$weekday = $this->getWeekday();
		if($month_start->getWeekday()==$weekday){
			$weekday_position++;
		}
		while($month_start->getDay()!=$this->getDay()){
			$month_start->addDay();
			if($month_start->getWeekday()==$weekday){
				$weekday_position++;
			}
		}
		return $weekday_position;
	}

	function moveToXWeekday($weekday,$x){
		$this->setDay(1);
		$y = 0;
		if($this->getWeekday()==$weekday){
			$y++;
		}
		while($x!=$y){
			$this->addDay(1);
			if($this->getWeekday()==$weekday){
				$y++;
			}
		}
	}

	function moveToLast($weekday)
	{
		// move to the last {monday,friday, tuesday, ...} of the same month
		$this->setDay($this->DaysInMonth());
		while($this->getWeekday()!=$weekday)
		{
			$this->addDay(-1);
		}
	}

	function DaysInMonth()
	{
		$month = $this->month;
		$year = $this->year;
		$last_day = date("j", mktime(0,0,0, $month + 1, 0, $year));
		return $last_day;
	}

	function getDifference($other_date)
	{
		// returns the difference between two dates in second
		return ($this->time - $other_date->time);
	}

	function addWeek($nb=1)
	{
		$this->addDay($nb*7);
	}

	function isLowerThan($other_date)
	{
		return ($this->time < $other_date->time);
	}

	function isLowerOrEqualThan($other_date)
	{
		return ($this->time <= $other_date->time);
	}

	function isGreaterThan($other_date)
	{
		return ($this->time > $other_date->time);
	}

	function isGreaterOrEqualThan($other_date)
	{
		return ($this->time >= $other_date->time);
	}

	function equals($other_date)
	{
		return ($this->time == $other_date->time);
	}

	function getTime()
	{
		return $this->time;
	}

	function getWeekday()
	{
		return date('w',$this->getTime());
	}

	function getWeekNumber()
	{
		return date('W',$this->getTime());
	}

	function isToday()
	{
		if($this->getDay() == date('d') && $this->getMonth() == date('m') && $this->getYear() == date('Y'))
		return true;
		return false;
	}

	function getWeekOfMonth()
	{
		// 1,2,3,4,5 the position of the week in the month
		$month_start = $this->duplicate();
		$month_start->setDay(1); // returning at the first day of the month
		$weekofmonth = 1;
		while($month_start->getDay()!=$this->getDay())
		{
			$month_start->addDay();
			if($month_start->getWeekday()==1)
			{
				$weekofmonth++;
			}
		}
		return $weekofmonth;
	}
}

class Time extends SusheeObject
{
	var $seconds;

	function Time($seconds=0)
	{
		$this->seconds = $seconds;
	}

	function addSeconds($seconds)
	{
		$this->seconds = $seconds;
	}

	function addMinutes($minutes)
	{
		$this->seconds+= $minutes*60;
	}

	function addHours($hours)
	{
		$this->seconds+= $hours*3600;
	}

	function getHours()
	{
		return floor($this->seconds / 3600 );
	}

	function getMinutes()
	{
		$hours = $this->getHours();
		return floor(($this->seconds - 3600*$hours) / 60);
	}

	function pad($nb)
	{
		if($nb===false)
		$nb = 0;
		if($nb<10){
			return '0'.((int)$nb);
		}else
		return $nb;
	}

	function getSeconds()
	{
		$seconds = $this->seconds;
		$hours = $this->getHours();
		$minutes = $this->getMinutes();
		$seconds-=$hours*3600;
		$seconds-=$minutes*60;
		return $seconds;
	}

	function getSQLTime()
	{
		return $this->pad($this->getHours()).':'.$this->pad($this->getMinutes()).':'.$this->pad($this->getSeconds());
	}
}

class DateTimeKeywordConverter extends SusheeObject
{
	var $value;
	var $operator;

	function DateTimeKeywordConverter($value,$operator=''){
		$this->value = $value;
		$this->operator = $operator;
	}
	
	function setOperator($operator){
		$this->operator = $operator;
	}
	
	function setValue($value){
		$this->value = $value;
	}

	function execute(){
		// getting the parameters
		$str = $this->getValue();
		$operator = $this->getOperator();

		// trying to cut to see if there is a computing to do (ex: +5days, -5months)
		$computing = explode('+',$str);
		$computing2 = explode('-',$str);
		if($str == 'now'){
			$str=$GLOBALS["sushee_today"];
		}else if ($str == "today" || $str == "this_day"){
			$str = date("Y-m-d",strtotime($GLOBALS["sushee_today"]));
			if(!$operator)
			$operator = 'LIKE';
			if($operator=='LT=' || $operator=='GT'){
				$str.=' 23:59:59';
			}
		}
		elseif ($str == "this_month"){
			$str = date("Y-m",strtotime($GLOBALS["sushee_today"]));
			if(!$operator || $operator==='='){
				$str.= '%';
				$operator = 'LIKE';
			}else
			$str.='-01';
		}else if($str == "this_week"){
			$today_time = strtotime($GLOBALS['sushee_today']);
			$day = getdate($today_time);
			$weekday = $day['wday'];
			$week_begin = $today_time - ($weekday-1)*24*60*60;
			$str = date('Y-m-d',$week_begin);
			if(!$operator || $operator==='='){
				$operator = 'GT=';
			}else if($operator==='GT'){
				$next_week_begin=$week_begin+7*24*60*60;
				$str = date('Y-m-d',$next_week_begin);
			}
		}
		elseif ($str == "this_year"){
			$str = date("Y",strtotime($GLOBALS["sushee_today"]));
			if(!$operator || $operator==='='){
				$operator = 'LIKE';
				$str.= '%';
			}else
			$str.='-01-01';
		}else if( sizeof($computing)>1 || (sizeof($computing2)>1 && (strpos($str,'days')!==FALSE || strpos($str,'months')!==FALSE || strpos($str,'month')!==FALSE || strpos($str,'years')!==FALSE || strpos($str,'hours')!==FALSE || strpos($str,'hour')!==FALSE || strpos($str,'minutes')!==FALSE || strpos($str,'mins')!==FALSE || strpos($str,'minute')!==FALSE || strpos($str,'min')!==FALSE)) ){
			// there is no '+' --> it must be a negative computing
			if(sizeof($computing)<=1){
				$computing = $computing2;
				$negative = TRUE;
			}
			if(trim($computing[0])==''){
				$start_date = strtotime($GLOBALS["sushee_today"]);
			}else{
				if(trim($computing[0]) == "this_week"){
					$today_time = strtotime($GLOBALS['sushee_today']);
					$day = getdate($today_time);
					$weekday = $day['wday'];
					$week_begin = $today_time - ($weekday-1)*24*60*60;
					$computed_date = date('Y-m-d',$week_begin)." 00:00:00";
				}else if(trim($computing[0]) == "this_month"){
					$computed_date = date("Y-m",strtotime($GLOBALS["sushee_today"]));
					$computed_date.="-01 00:00:00";
				}else if(trim($computing[0]) == "this_year"){
					$computed_date = date("Y",strtotime($GLOBALS["sushee_today"]));
					$computed_date.="-01-01 00:00:00";
				}else
				$computed_date = $GLOBALS["sushee_today"];
				$start_date = strtotime($computed_date);
			}
			$decalage = explode(' ',$computing[1]);
			$units = round($decalage[0]);
			if($decalage[1]=='min' || $decalage[1]=='minutes' || $decalage[1]=='mins'){
				$time = $units*60;
				if($negative)
				$str = date('Y-m-d H:i',$start_date-$time).':00';
				else
				$str = date('Y-m-d H:i',$start_date+$time).':00';
			}else if($decalage[1]=='hours' || $decalage[1]=='hour'){
				$time = $units*60*60;
				if($negative)
				$str = date('Y-m-d H',$start_date-$time).':00:00';
				else
				$str = date('Y-m-d H',$start_date+$time).':00:00';
			}else if($decalage[1]=='days' || $decalage[1]=='day'){
				$time = $units*24*60*60;
				if($negative)
				$str = date('Y-m-d',$start_date-$time);
				else
				$str = date('Y-m-d',$start_date+$time);
			}else if($decalage[1]=='years' || $decalage[1]=='year'){
				$start_date_year = date('Y',$start_date);
				$start_date_other = date('-m-d H:i:s',$start_date);
				if($negative){
					$final_year = $start_date_year-$units;
					$final_time = strtotime($final_year.$start_date_other);
				}else{
					$final_year = $start_date_year+$units;
					$final_time = strtotime($final_year.$start_date_other);
				}
				$str = date('Y-m-d',$final_time);
			}else if($decalage[1]=='months' || $decalage[1]=='month'){
				$start_date_year = date('Y',$start_date);
				$start_date_month = date('m',$start_date);
				$start_date_other = date('-d H:i:s',$start_date);
				if($negative){
					$temp_month = $start_date_month-$units;
					if(abs($temp_month)==$temp_month){
						$final_month = $temp_month;
						$final_year = $start_date_year;
					}else{
						$final_month = 12-(abs($temp_month)%12);
						$final_year = $start_date_year-floor(abs($temp_month)/12)-1;
					}
				}else{
					$final_month = ($start_date_month+$units)%12;
					$final_year = $start_date_year+floor(($start_date_month+$units)/12);
				}
				$final_time = strtotime($final_year.'-'.$final_month.$start_date_other);
				$str = date('Y-m-d',$final_time);
			}
			if(!$operator){
				$operator = 'LIKE';
			}
			if( ($operator=='LT=' || $operator=='GT') && strlen($str)==10 && $decalage[1]=='days'){
				$str.=' 23:59:59';
			}
		}
		// re-assigning the new values, so the caller can get these back calling getOperator and getValue
		$this->value = $str;
		$this->operator = $operator;

		return $this->value;
	}

	function getOperator()
	{
		return $this->operator;
	}

	function getValue()
	{
		return $this->value;
	}
}