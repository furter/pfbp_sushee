<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchConnected.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/*

DEPRECATED : WAS USED BY FLASH OS

*/
function parseTime($now_time,$action_time){
	$hour = substr($action_time,11,2);
	$minute = substr($action_time,14,2);
	$second = substr($action_time,17,2);
	$day = substr($action_time,8,2);
	$year = substr($action_time,0,4);
	$month = substr($action_time,5,2);
	$lastActionTime = mktime($hour,$minute,$second,$month,$day,$year);
	$elapsed = $now_time - $lastActionTime;
	$hour_elapsed = floor($elapsed/3600);
	$elapsed-=$hour_elapsed*3600;
	$minutes_elapsed = floor($elapsed/60);
	$elapsed-=$minutes_elapsed*60;
	if($elapsed<10)
		$elapsed='0'.$elapsed;
	if($hour_elapsed<10)
		$hour_elapsed='0'.$hour_elapsed;
	if($minutes_elapsed<10)
		$minutes_elapsed='0'.$minutes_elapsed;
	return array('hours_elapsed'=>$hour_elapsed,'minutes_elapsed'=>$minutes_elapsed,'seconds_elapsed'=>$elapsed);
}

function searchConnected($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	$time = time();
	$before_24h = $time - 3600;
	$sql = 'SELECT * FROM `connected` WHERE `Type`="real" AND (`Timestamp` >"'.date("Y-m-d H:i:s",$before_24h).'" OR `UserID`=\''.$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'].'\')';
	//echo $sql;
	//debug_log($sql);
	$rs = $db_conn->Execute($sql);
	$connected='';
	$ContactModuleInfo = moduleInfo('contact');
	while($complete_row = $rs->FetchRow()){
		
			$contact = getInfo($ContactModuleInfo,$complete_row['UserID']);
			
			$detail = parseTime($time,$complete_row['Timestamp']);
			$hour_elapsed = $detail['hours_elapsed'];
			$minutes_elapsed = $detail['minutes_elapsed'];
			$elapsed = $detail['seconds_elapsed'];
			$lastTop_sql = 'SELECT `Timestamp` FROM `connected` WHERE `UserID`='.$complete_row['UserID'].' AND `Type`="connected" ORDER BY `Timestamp` DESC LIMIT 0,1';
			$lastTop_row = $db_conn->getRow($lastTop_sql);
			if(!$lastTop_row)
				$lastTop_row['Timestamp']=$GLOBALS['sushee_today'];
			//debug_log($lastTop_sql);
			$detail = parseTime($time,$lastTop_row['Timestamp']);
			$hour_elapsed_top = $detail['hours_elapsed'];
			$minutes_elapsed_top = $detail['minutes_elapsed'];
			$elapsed_top = $detail['seconds_elapsed'];
			$connected.='<CONTACT IP="'.$complete_row['IP'].'" elapsedSinceTop="'.$hour_elapsed_top.':'.$minutes_elapsed_top.':'.$elapsed_top.'" LastTop="'.$lastTop_row['Timestamp'].'" elapsed="'.$hour_elapsed.':'.$minutes_elapsed.':'.$elapsed.'" lastAction="'.$complete_row['Timestamp'].'" ID="'.$complete_row['UserID'].'">'.generateInfoXML($ContactModuleInfo,$contact,$ContactModuleInfo->getFieldsBySecurity('R'),array('ID'=>true,'FIRSTNAME'=>true,'LASTNAME'=>true,'DENOMINATION'=>true,'EMAIL1'=>true)).'</CONTACT>';
	}
	$attributes = '';
	if ($name)
		$attributes.=" name='$name'";
	$external_file = $xml->getData($current_path.'/@fromFile');
	if($external_file)
		$attributes.=" fromFile='".$external_file."'";
	return '<RESULTS'.$attributes.'>'.$connected.'</RESULTS>';
}
?>
