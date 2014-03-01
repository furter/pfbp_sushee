<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/event_postprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/date.class.php');
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");



$create_event_repeated =  ($requestName == "CREATE" && $values['Repeat']!=='none' && $values['RepeatEnd'] && $values['RepeatMasterID']==0 );
$set_event_as_repeated = ($requestName=="UPDATE" && $former_values['Repeat']=='none' && $values['Repeat']!='none' && $values['Repeat'] && $values['RepeatMasterID']==0);
$remove_event_repetition = ($requestName=="KILL") || ($requestName=="DELETE") || ($requestName=="UPDATE" && $former_values['Repeat']!='none' && $values['Repeat']!=$former_values['Repeat'] && $values['Repeat'] && $values['RepeatMasterID']==0);
$change_event_repetition = ($requestName=="UPDATE" && $former_values['Repeat']!='none' && $values['Repeat']!=$former_values['Repeat'] && $values['Repeat']!='none' && $values['Repeat'] && $values['RepeatMasterID']==0);

$not_propagated_data_change = array('ID','CreationDate','ModificationDate');
$change_event_data = false;//= ($requestName=="UPDATE" && ( ($former_values['Start']!=$values['Start'] && $values['Start']) || ($former_values['End']!=$values['End'] && $values['End'])) && ($values['RepeatException']==0 || !$values['RepeatException']) /*&& $values['RepeatMasterID']==0*/);
if($requestName=="UPDATE" &&  ($values['RepeatException']==0 || !$values['RepeatException']) && (($values['Repeat']!='none') || (!isset($values['Repeat']) && $former_values['Repeat']!='none') || (isset($values['Repeat']) && $values['Repeat']!='none') ) ){
	$fieldnames = $moduleInfo->getFieldsBySecurity('W');
	foreach($fieldnames as $fieldname){
		$value = $former_values[$fieldname];
		if(isset($values[$fieldname]) && $values[$fieldname] != $value && !in_array($fieldname,$not_propagated_data_change)){
			$change_event_data = true;
			if($fieldname != 'Start' && $fieldname!='End')
				$changed_event_datas[]=$fieldname;
		}
	}
}


$change_end_repeating = ($requestName=="UPDATE" /*&& $values['RepeatMasterID']==0*/ && $former_values['RepeatEnd']!=$values['RepeatEnd'] && $values['RepeatEnd']);

// for future : may change a repetition by changing one of its occurences
if($former_values['RepeatMasterID']!=0)
	$masterID = $former_values['RepeatMasterID'];
else
	$masterID = $ID;

if($remove_event_repetition){
	if(!$xml->getElement($firstNodePath.'/INFO/REPEATEXCEPTION[.=1]')){
		if($masterID){
			$remove_event_repetition_sql = 'SELECT `ID` FROM `events` WHERE `RepeatMasterID`=\''.$masterID.'\' AND `Activity`=1 AND `ID` != \''.$masterID.'\'';
			$repeat_rs = $db_conn->Execute($remove_event_repetition_sql);
			if($repeat_rs){
				while($repeat_values = $repeat_rs->FetchRow()){
					$event_to_remove = new Event($repeat_values);
					$event_to_remove->delete();
				}
			}
		}
		
	}
	
}
if ( $create_event_repeated || $set_event_as_repeated || $change_event_repetition){
	if($requestName=="UPDATE")
		$repeat_values = $new_values;
	else
		$repeat_values = $values;
	$repeat_values['ID'] = $ID;
	$start_date = new Date($repeat_values['Start']);
	$end_date = new Date($repeat_values['End']);
	$repeatEnd_date = new Date($repeat_values['RepeatEnd']);
	//$repeat_values['RepeatMasterID'] = $masterID;
	if($start_date->isValid() && $end_date->isValid() && $repeatEnd_date->isValid()){
		$masterEvent = new Event($repeat_values);
		$masterEvent->createOccurences($start_date,$repeatEnd_date);
		$masterEvent->cleanFields();
		$masterEvent->setField('RepeatMasterID',$masterEvent->getID());
		$masterEvent->update();
	}else
		debug_log('One of the date is not valid');
}
if($change_event_data){
	$all_values = $new_values;
	$former_start_date = new Date($former_values['Start']);
	$former_end_date = new Date($former_values['End']);
	$start_date = new Date($all_values['Start']);
	$end_date = new Date($all_values['End']);
	
	$diff_start_secs = $start_date->getDifference($former_start_date);
	$diff_end_secs = $end_date->getDifference($former_end_date);
	if($masterID==$ID)
		$repetitions_sql = 'SELECT * FROM `events` WHERE `RepeatMasterID`=\''.$masterID.'\' AND `Activity`=1 AND `RepeatException`!=1 AND `ID`!='.$ID;
	else
		$repetitions_sql = 'SELECT * FROM `events` WHERE (`RepeatMasterID`=\''.$masterID.'\' OR `ID`=\''.$masterID.'\') AND `Activity`=1 AND `RepeatException`!=1 AND `ID`!='.$ID;
	// if we move the end date, we must change the end date for every event
	$repeat_rs = $db_conn->Execute($repetitions_sql);
	if($repeat_rs){
		while($repeat_values = $repeat_rs->FetchRow()){
			$event = new Event($repeat_values);
			$start_date = new Date($event->getField('Start'));
			$end_date = new Date($event->getField('End'));
			$start_date->addSecond($diff_start_secs);
			$end_date->addSecond($diff_end_secs);
			$event->setField('Start',$start_date->getDatetime());
			$event->setField('End',$end_date->getDatetime());
			foreach($changed_event_datas as $fieldname){
				$event->setField($fieldname,$values[$fieldname]);
			}
			$event->update();
		}
	}
}

if($change_end_repeating){
	$all_values = $new_values;
	$all_values['ID'] = $ID;
	$repeatEnd_date = new Date($all_values['RepeatEnd']);
	$former_repeatEnd_date = new Date($former_values['RepeatEnd']);
	
	if($repeatEnd_date->isLowerThan($former_repeatEnd_date)){
		// we must delete all the repeats between the new date and the former date
		$repetitions_sql = 'SELECT * FROM `events` WHERE `RepeatMasterID`=\''.$masterID.'\' AND `Activity`=1 AND `RepeatException`!=1 AND `Start` > "'.$repeatEnd_date->getDateTime().'"';
		$repeat_rs = $db_conn->Execute($repetitions_sql);
		if($repeat_rs){
			while($repeat_values = $repeat_rs->FetchRow()){
				$event_to_remove = new Event($repeat_values);
				$event_to_remove->delete();
			}
		}
		// we must change the repeatend of all events before the repeatend
		$repetitions_sql = 'SELECT * FROM `events` WHERE `RepeatMasterID`=\''.$masterID.'\' AND `Activity`=1 AND `RepeatException`!=1';
		$repeat_rs = $db_conn->Execute($repetitions_sql);
		if($repeat_rs){
			while($repeat_values = $repeat_rs->FetchRow()){
				$event_to_update = new Event($repeat_values);
				$event_to_update->setField('RepeatEnd',$repeatEnd_date->getDatetime());
				$event_to_update->update();
			}
		}
	}
	else{
		// we must change the repeatend of all ocurrences
		$repetitions_sql = 'SELECT * FROM `events` WHERE `RepeatMasterID`=\''.$masterID.'\' AND `Activity`=1 AND `RepeatException`!=1';
		$repeat_rs = $db_conn->Execute($repetitions_sql);
		if($repeat_rs){
			while($repeat_values = $repeat_rs->FetchRow()){
				$event_to_update = new Event($repeat_values);
				$event_to_update->setField('RepeatEnd',$repeatEnd_date->getDatetime());
				$event_to_update->update();
			}
		}
		// must create new repetition occurrences
		$masterEvent = new Event($all_values);
		
		// we must find the last occurence
		$repetitions_sql = 'SELECT `Start` FROM `events` WHERE `RepeatMasterID`=\''.$masterID.'\' AND `Activity`=1 AND `RepeatException`!=1 ORDER BY `Start` DESC LIMIT 0,1';
		$last_repeat = $db_conn->getRow($repetitions_sql);
		if($last_repeat){
			$last_repeat_date = new Date($last_repeat['Start']);
		}else{ // taking the master event as last repeat
			$last_repeat_date = new Date($all_values['Start']);
		}
		
		$masterEvent->createOccurences($last_repeat_date,$repeatEnd_date);
	}
}
return true;
?>