<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/warn_before_expiration.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");

debug_log('checking for expiration');
if(isset($GLOBALS['residentID']) && $GLOBALS['residentPublished']==1){
	$check_state = "SELECT ExpirationDate FROM residents WHERE ID='".$GLOBALS['residentID']."' AND Activity=1";
	$db_conn=db_connect(TRUE);
	$row = $db_conn->GetRow($check_state);
	$expiration_date = $row['ExpirationDate'];
	if($expiration_date!='0000-01-01' && $expiration_date!='0000-00-00' && $expiration_date!='9999-12-31'){
		$time_now = mktime ( 0 , 0 , 0 , date('m') , date('d') , date('Y') );
		//$time_now = mktime ( 0 , 0 , 0 , 4 , 30 , 2007 );
		$month = substr($expiration_date,5,2);
		$day = substr($expiration_date,8,2);
		$year = substr($expiration_date,0,4);
		$time_expiration = mktime ( 0 , 0 , 0 , $month ,$day  ,$year  );
		$diff = $time_expiration - $time_now;
		$one_day = 24*3600;
		$one_week = $one_day*7;
		$one_month = 31*$one_day;
		$three_month = 91*$one_day;
		$warn_client = false;
		$warn_account = false;
		//echo $year.'-'.$month.'-'.$day.'diff is '.$diff.'  '.(-$one_day).'<br/>';
		if($diff< (-$one_week)){
			// too late : expired for more than one week
			$state['eng'] = 'has expired';
			$state['fre'] = 'a expiré';
			$delay['eng'] = 'for too long';
			$delay['fre'] = 'depuis longtemps';
		}else if($diff<(-$one_day) && $diff>=(-$one_week)){
			$state['eng'] = 'has expired';
			$state['fre'] = 'a expiré';
			$warn_account = $warn_client = true;
			$delay['eng'] = 'for one week';
			$delay['fre'] = 'depuis une semaine';
		}else if($diff<0 && $diff>=(-$one_day)){
			$state['eng'] = 'has expired';
			$state['fre'] = 'a expiré';
			$warn_account = $warn_client = true;
			$delay['eng'] = 'since yesterday';
			$delay['fre'] = 'depuis hier';
		}else if($diff==0){
			$state['eng'] = 'has expired';
			$state['fre'] = 'a expiré';
			$warn_account = $warn_client = true;
			$delay['eng'] = 'today';
			$delay['fre'] = 'aujourd\'hui';
		}else if($diff <= $one_day ){ // the day before
			$state['eng'] = 'will expire';
			$state['fre'] = 'expire';
			$warn_account = $warn_client = true;
			$delay['eng'] = 'tomorrow';
			$delay['fre'] = 'demain';
		}else if($diff <= $one_week && $diff > $one_week-$one_day ){ // one week before
			$state['eng'] = 'will expire';
			$state['fre'] = 'expire';
			$warn_account = $warn_client = true;
			$delay['eng'] = 'in one week';
			$delay['fre'] = 'dans une semaine';
		}else if($diff <= $one_month && $diff > $one_month-$one_day){ // one month before
			$state['eng'] = 'will expire';
			$state['fre'] = 'expire';
			$warn_account = true;
			$delay['eng'] = 'in one month';
			$delay['fre'] = 'dans un mois';
		}else if($diff <= $three_month && $diff > $three_month-$one_day){ // three month before
			$state['eng'] = 'will expire';
			$state['fre'] = 'expire';
			$warn_account = true;
			$delay['eng'] = 'in three months';
			$delay['fre'] = 'dans trois mois';
		}else{
			$state = array();
			$delay = array();
			//debug_log($diff/(24*3600).' days before expiration ');
		}
		//echo 'delay is '.$delay.'<br/>';
		if($warn_account || $warn_client){
			
			$mails_to_send = array();
			$clientmails_to_send = array();
			if($warn_account){
				// accounts
				$contact_sql = 'SELECT c.Email1 FROM dependencies AS dep LEFT JOIN contacts AS c ON dep.TargetID = c.ID WHERE dep.DependencyTypeID = \'50\' AND dep.OriginID =\''.$GLOBALS['residentID'].'\';';
				$contact_rs = $db_conn->Execute($contact_sql);
				while($contact_row = $contact_rs->FetchRow()){
					if($contact_row['Email1'])
						$mails_to_send[$contact_row['Email1']] = $contact_row['Email1'];
				}
				// locked contacts in the database
				$db_conn=db_connect(false);
				$contact_sql = 'SELECT c.Email1 FROM contacts AS c WHERE c.IsLocked = 1;';
				$contact_rs = $db_conn->Execute($contact_sql);
				while($contact_row = $contact_rs->FetchRow()){
					if($contact_row['Email1'])
						$mails_to_send[$contact_row['Email1']] = $contact_row['Email1'];
				}
				$db_conn=db_connect(true);
			}
			if($warn_client){
				// clients
				$contact_sql = 'SELECT c.Email1 FROM dependencies AS dep LEFT JOIN contacts AS c ON dep.TargetID = c.ID WHERE dep.DependencyTypeID = \'49\' AND dep.OriginID =\''.$GLOBALS['residentID'].'\';';
				$contact_rs = $db_conn->Execute($contact_sql);
				while($contact_row = $contact_rs->FetchRow()){
					if($contact_row['Email1'])
						$clientmails_to_send[$contact_row['Email1']] = $contact_row['Email1'];
				}
			}
			if(sizeof($mails_to_send)>0){
				$eol = "\n";
				$message = "Le résident ".$GLOBALS['resident_name']." ".$state['fre']." ".$delay['fre'].". Si la licence a été contractée pour une nouvelle année, n'oubliez pas de reporter la date d'expiration d'un an!".$eol.$eol.
				"The resident ".$GLOBALS['resident_name']." ".$state['eng']." ".$delay['eng'].". If the licence is contracted for a new year, do not forget to defer the expiration date of one year!";
				$subj = $GLOBALS['resident_name'].' Nectil OS '.$state['eng'].' '.$delay['eng'];
				foreach($mails_to_send as $email){
					echo 'must mail to '.$email.'<br/>';
					//if($email =='boris@nectil.com')
						sendMail($email, $subj , utf8_decode($message) );
				}
			}
		}
	}else{
			debug_log('This resident is immortal');
	}
}else{
	debug_log('not a resident or not published '.$GLOBALS['residentID']);
}
?>
