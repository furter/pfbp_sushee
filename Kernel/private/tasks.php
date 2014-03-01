<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/tasks.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
error_reporting(E_ERROR | E_WARNING | E_PARSE);
// put here all the files you want to have started on a regular basis
@set_time_limit(0);
//
$db_conn = db_connect();
debug_log('Kernel/private/tasks.php : Scheduled tasks start ');
$rs = $db_conn->Execute('select * from dependencytypes');
if(!$rs){
	debug_log("/sushee/private/tasks.php : No connection to DB");
	die("/sushee/private/tasks.php: No connection to DB");
}

include_once(dirname(__FILE__)."/../private/warn_before_expiration.php");
echo 'warn_before_expiration OK<br/>';

if( file_exists($GLOBALS["Public_dir"]."tasks.php") )
	include_once($GLOBALS["Public_dir"]."tasks.php");

include_once(dirname(__FILE__)."/../private/clean_tmpfiles.php");
echo 'clean_tmpfiles OK<br/>';

debug_log('Kernel/private/tasks.php : Scheduled tasks executed ');
echo 'Scheduled tasks executed';
if ( isNectilMaster($GLOBALS["nectil_url"]) && $GLOBALS["generic_backoffice"]){
	debug_log('Calling the scheduled tasks for the residents');
	$sql = 'SELECT `ID`,`DbName`,`MailingMinInterval`,`MailingCurInterval`,`LastSendingDate`,`URL` FROM `residents` WHERE (`Activity`=1 AND `IsTemplate`!=1 AND (`ExpirationDate`="0000-00-00" OR `ExpirationDate` > "'.$GLOBALS["sushee_today"].'" OR `ExpirationDate`="0000-01-01"))';
	$rs = $db_conn->Execute($sql);
	echo '*'.$sql.'*';
	if($rs){
		while($row = $rs->FetchRow()){
			$url = $row['URL'].'/'.Sushee_dirname.'/private/tasks.php';
			debug_log('calling '.$url);
			$fp = @fopen($url,'r');
			if($fp){
				while (!feof ($fp)) {
					$buffer = fgets($fp, 64);
					//echo $buffer;
				  }
				  fclose ($fp);
			}
		}	
	}
}
if($GLOBALS["PopServer"])
	include_once(dirname(__FILE__)."/../private/check_mailings.php");