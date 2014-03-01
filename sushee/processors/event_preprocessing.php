<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/event_preprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
if(($requestName=="KILL") || ($requestName=="DELETE")){
	$know_masterID_sql = 'SELECT `RepeatMasterID` FROM `events` WHERE `ID`=\''.$IDs_array[0].'\' AND `Activity`=1';
	$event_row = $db_conn->getRow($know_masterID_sql);
	$former_values['RepeatMasterID'] = $event_row['RepeatMasterID'];
}


$start = $xml->getData($firstNodePath."/INFO/START");
$end = $xml->getData($firstNodePath."/INFO/END");

if($start){
	
	$start_year = substr($start,0,4);
	$start_month = substr($start,5,2);
	$start_day = substr($start,8,2);
	
	$start_hour = substr($start,11,2);
	$start_minute = substr($start,14,2);
	$start_second = substr($start,17,2);
	
	if($start_year && $start_month && $start_day){
		$time = mktime(0,0,0,$start_month,$start_day,$start_year);
		$return_values['StartWeekday']=$values['StartWeekday']=date('w',$time);
		$return_values['StartWeekNumber']=$values['StartWeekNumber']=date('W',$time);
	}
}

	
if($end){
	$end_year = substr($end,0,4);
	$end_month = substr($end,5,2);
	$end_day = substr($end,8,2);
	
	$end_hour = substr($end,11,2);
	$end_minute = substr($end,14,2);
	$end_second = substr($end,16,2);
	
	if($end_year && $end_month && $end_day){
		$time = mktime(0,0,0,$end_month,$end_day,$end_year);
		$return_values['EndWeekday']=$values['EndWeekday']=date('w',$time);
		$return_values['EndWeekNumber']=$values['EndWeekNumber']=date('W',$time);
	}
}

return TRUE;
?>