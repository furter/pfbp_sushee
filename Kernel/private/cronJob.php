<?php

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/commandline.class.php");
require_once(dirname(__FILE__)."/../common/batch.class.php");
require_once(dirname(__FILE__)."/../common/cron.class.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

@set_time_limit(1200);
echo 'connecting...'.PHP_EOL;
$db_conn = db_connect();
if ($db_conn)
{
	echo 'connected.'.PHP_EOL;
	echo 'getting mailing status...'.PHP_EOL;

	$moduleInfo = moduleInfo('mailing');
	$sql = 'SELECT `ID`,`MailingMinInterval`,`MailingCurInterval`,`LastSendingDate`,`Status` FROM `mailing_status` WHERE `ID`=1';
	$status = $db_conn->getRow($sql);
	$mailerResidents = array();
	if ($status)
	{
		$now = date("Y-m-d H:i:s");
		$min_interval = intval($status['MailingMinInterval']);
		$cur_interval = intval($status['MailingCurInterval']);

		if ($min_interval == 0)
		{
			$min_interval = 15;
		}

		if ( $cur_interval >= ($min_interval-1) )
		{
			echo 'Interval reached.'.PHP_EOL;

			if ($status['Status'] == 'pending')
			{
				echo 'Status pending, find a mailing...'.PHP_EOL;

				$sql = 'SELECT ID FROM `'.$moduleInfo->tableName.'` WHERE ID!=1 AND Activity=1 AND (Status="pending" OR Status="sending") AND SendingDate <= "'.$now.'";';
				$row = $db_conn->getRow($sql);
				if ($row)
				{
					echo 'Mailing to send.'.PHP_EOL;

					// yes, there is a mailing to send
					// reset the counter and set the status to running
					$sql = 'UPDATE `mailing_status` SET `LastSendingDate`="'.$now.'",`MailingCurInterval`=0,`Status`="running" WHERE `ID`=1';
					$db_conn->Execute($sql);

					require_once($GLOBALS["backoffice_dir"]."private/send_mailings.php");

					// $url = $status['xxxxxx'].'/sushee/private/send_mailings.php';
					// $fp = fopen($url,'r');
					// if ($fp)
					// {
					// 	while (!feof ($fp))
					// 	{
					// 		$buffer = fgets($fp, 64);
					// 		echo $buffer;
					// 	}
					// 	fclose ($fp);
					// }

					// reset the status to pending
					$sql = 'UPDATE `mailing_status` SET `Status`="pending" WHERE `ID`=1';
					$db_conn->Execute($sql);
				}
				else
				{
					echo 'No mailing to send.'.PHP_EOL;
	
					// no mailing to send, reset counter
					$sql = 'UPDATE `mailing_status` SET `MailingCurInterval`=0 WHERE `ID`=1';
					$db_conn->Execute($sql);
				}
			}
			else
			{
				echo 'Status running, still sending mailing even after MailingMinInterval period...'.PHP_EOL;

				// bad configuration and/or packets too big and/or sending process failed
				// reset counter and status
				$sql = 'UPDATE `mailing_status` SET `MailingCurInterval`=0,`Status`="pending" WHERE `ID`=1';
				$db_conn->Execute($sql);

				// warn administrator
				$admin = $GLOBALS["admin_email"];
				if($admin)
				{
					$message = 'Mailing overlap on '.$_SERVER['SERVER_NAME'];
					sendMail($recipient_email,$message,$message,"officity-mailer",$admin);
				}
			}
		}
		else
		{
			echo 'Current Interval '.$cur_interval.' too low, keep counting to reach '.$min_interval.PHP_EOL;

			$sql = 'UPDATE `mailing_status` SET `MailingCurInterval`="' . ($cur_interval+1) . '" WHERE `ID`=1';
			$db_conn->Execute($sql);
		}
	}
	else
	{
		echo '/!\ Problem getting the mailing status.'.PHP_EOL;
	}
}
else
{
	echo '/!\ Problem connecting to database.'.PHP_EOL;
}

$queue = new BatchQueue();
$queue->Execute();

$queue = new CronQueue();
$queue->Execute();

require_once(dirname(__FILE__).'/../common/kernel.class.php');

$kernel = new NectilKernel();
$kernel->launchResidentsBatches();
$kernel->launchResidentsCrons();

// deleting the session after usage, to keep the session directory clean
session_destroy();