<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/cronChecker.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common.php");
require_once(dirname(__FILE__)."/../common/cron.class.php");
require_once(dirname(__FILE__)."/../common/mail.class.php");
require_once(dirname(__FILE__)."/../common/sushee.class.php");

// CronChecker check if the crons from 00:00 to the current hour are well executed
// Executed at 12:00 for the noon check, and 23:59 for the midnight daily check

class CronChecker extends SusheeObject
{
	function CronChecker(){}

	function execute()
	{
		$db_conn = db_connect();
		$moduleInfo = moduleInfo('cron');
		$this_hour = date('G');
		$this_minute = date(i);
		$hours = $this->getHourSelect($this_hour);
		$complete_log = '';

		// select crons that had to be executed since this morning hours
		$sql = 'SELECT * FROM `'.$moduleInfo->tableName.'` WHERE
		(
			(' . $hours . ' `Hour`="") 
			AND (`Day` LIKE "%'.date('d').',%" OR `Day`="") 
			AND (`Month` LIKE "%'.date('m').',%" OR `Month`="") 
			AND (`Weekday` LIKE "%'.date('N').',%" OR `Weekday`="") 
		)
		AND (`Command` != "" OR `ClassFile` != "")
		AND `Status` != "disabled"
		AND `Activity` = 1
		ORDER BY `ID`';

echo $sql .'<br />';

		$rs = $db_conn->Execute($sql);
		if($rs)
		{
			while($row = $rs->FetchRow())
			{
				$cron = new Cron($row);

				// other values than minutes and hours are useless
				// cron have to be executed today according to previous selection

				$c_id = $cron->getID();
				
				$c_minute = $cron->getField('Minute');
				if ($c_minute)
				{
					$c_minutes = explode(',',$c_minute);
				}
				else
				{
					$c_minutes = array();
					$count = 0;
					while ($count < 60)
					{
						$c_minutes[] = str_pad($count,2,'0',STR_PAD_LEFT);
						$count++;
					}
				}

				$c_hour = $cron->getField('Hour');
				if ($c_hour)
				{
					$c_hours = explode(',',$c_hour);
				}
				else
				{
					$c_hours = array();
					$count = 0;
					while ($count <= $this_hour)
					{
						$c_hours[] = str_pad($count,2,'0',STR_PAD_LEFT);
						$count++;
					}
				}

				$main_sql = 'SELECT * FROM `cronlogs` WHERE `CronID` = "' . $c_id . '" AND `Activity` = 1 AND CreationDate LIKE "';

				// check if adequate cronlog for each hour/minute combinaison
				foreach ($c_hours as $h)
				{
					foreach ($c_minutes as $m)
					{
						if ($h == $this_hour && intval($m) > intval($this_minute))
						{
							// do nothing
						}
						else if ($h && $m && intval($h) <= intval($this_hour))
						{
							$sql =  $main_sql . date('Y-m-d') . ' ' . $h . ':' . $m . ':%";';

							//echo $sql . '<br />';

							$c_log = $db_conn->Execute($sql);
							if(!$c_log || $c_log->RecordCount() == 0)
							{
								$complete_log .= 'Cron ' . $c_id . ' not executed at ' . $h . ':' . $m . PHP_EOL;
							}
						}
					}
				}
			}

			$sushee = new Sushee_Instance();

			if ($complete_log != '')
			{
				$subject = 'Cron error on ' . $sushee->getUrl() . ': missing cronlogs';
				$message = $complete_log;
			}
			else
			{
				$subject = 'Cron ok on' . $sushee->getUrl();
				$message = $subject;
			}

			$email = $GLOBALS["admin_email"];
			if($email)
			{
				$serverMail = new ServerMail();
				$serverMail->setSender('cron@sushee.com');
				$serverMail->setSubject($subject);
				$serverMail->setText($message);
				$serverMail->addRecipient($email);
				$serverMail->execute();
			}
		}
		else
		{
			echo $sql;
		}
	}

	function getHourSelect($this_hour)
	{
		if (!$this_hour)
			$this_hour = date('G');

		$hour_string = '';
		$hour = 0;
		while($hour <= $this_hour)
		{
			$hour_string .= '`Hour` LIKE "%'.str_pad($hour,2,'0',STR_PAD_LEFT).',%" OR';
			$hour++;
		}
		return $hour_string;
	}
}

if ($_GET['exec'] === 'true')
{
	$check = new CronChecker();
	$check->execute();
}