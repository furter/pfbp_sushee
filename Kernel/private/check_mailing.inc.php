<?php

require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php");
require_once(dirname(__FILE__)."/../common/Mail/pop3.php");

function check_mailing($name="",$xml=false,$requestName="",$current_path="",$firstNode="",$firstNodePath="")
{
	mailing_log('check started');
	$moduleInfo = moduleInfo('mailing');
	if ($moduleInfo->loaded)
	{
		$moduleContactInfo = moduleInfo('contact');
		$db_conn = db_connect();

		global $slash;

		$steps		= 2;
		$server		= $GLOBALS["PopServer"];
		$username	= $GLOBALS["PopUsername"];
		$password	= $GLOBALS["PopPassword"];

		$pop3 = new Net_POP3();

		if ($pop3->connect($server))
		{
			if ($pop3->login($username,$password))
			{
				mailing_log('Authenticated for check_mailing');
			}
			else
			{
				mailing_log('Problem with authentication on check_mailing');
				return generateMsgXML(1,'Problem with the POP3 connection : login or password is incorrect.',0,'',$name);
			}
		}
		else
		{
			return generateMsgXML(1,'Problem with the POP3 connection : could not connect.',0,'',$name);
		}


		$now = date("Y-m-d H:i:s");
		$now_timestamp = time();

		$num	= $pop3->numMsg();
		$noob	= true;

		mailing_log('nb messages to parse :'.$num);

		for($i=1;$i<=$num;$i++)
		{
			$get_msg = true;
			$delete = false;

			if (!$header = $pop3->getRawHeaders($i))
			{
				$get_msg = false;
			}

			if ($get_msg)
			{
				if (!$message = $pop3->getMsg($i))
				{
					$delete = false;
				}
				else
				{
					mailing_log('---------------------------------------------------------------------------------');
					mailing_log('analysing mail... ' . $i . ' / ' . $num);

					$viewing_code = '';
					$found_sender = false;
					$found_recipient = false;
					$found_info = false;
					$msg = '';

					// -----------------------------------------------
					// 1. test if message sent from authorized senders
					// -----------------------------------------------
					
					if (defined('AWSSenders'))
					{
						$senders = explode(',',AWSSenders);
						foreach ($senders as $sender)
						{
							if ($sender == 'support@officity.com' || $sender == 'no-reply@officity.com')
							{
								continue;
							}

							if (strstr($message,$sender))
							{
								mailing_log('found sender: '.$sender);
								$found_sender = true;
								break;
							}
						}
					}

					// -----------------------------------------------
					// 2. test line by line
					// -----------------------------------------------
					
					$lines = explode("\n",$message);

					for ($j=0;$j<count($lines);$j++)
					{
						$line = $lines[$j];

						// remove eol at end
						$line = str_replace(array("\r","\n"),"",$line);
						if (substr($line,-1)=='=')
						{
							// cut the = and the eol
							$line = substr($line,0,-1);
						}

						$regex_date = "^(Date:)";
						$regex_to = "^(To:)";
						$regex_mail = '/(?<!\'|")\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b(?!\'|")/is';
						
						// ------------------------------
						// -- Check date obsolecence  --
						// ----------------------------

						if (eregi($regex_date,$line))
						{
							$sending_date = $line;
							$timestamp = strtotime(substr($sending_date,6));

							// 604800 = 1 week | 86400 = 1 day | 2678400 = 1 month / 31 days | 5270400 = 2 months / 61 days
							if ( ($timestamp + 5270400) < $now_timestamp)
							{
								mailing_log("message is too old ".date("Y-m-d",$timestamp));
								$delete = true;
							}
						}
						
						// --------------------------------
						// -- Try to get the recipient  --
						// ------------------------------

						if (eregi($regex_to,$line))
						{
							if (preg_match($regex_mail,$line,$emails))
							{
								$email_to = $emails[0];
								mailing_log('found to: ' . $email_to);
							}
						}
						
						// ------------------------------------------
						// -- Try to get the SES Final recipient  --
						// ----------------------------------------

						if (strstr($line,'Final-Recipient: rfc822;'))
						{
							$found_recipient = true;
							$email_recipient = trim(substr($line,24));
							mailing_log('found recipient: ' . $email_recipient);
						}

						$msg .= ' ' . $line;
						unset($line);
					}

					// ---------------------------------------------
					// -- Solve recipient if no final recipient  --
					// -------------------------------------------

					if (!$email_recipient && $email_to)
					{
						if (!in_array($email_to,$senders) && $email_to !== $GLOBALS['PopMailAddress'])
						{
							$found_recipient = true;
							$email_recipient = $email_to;
						}
					}

					$spy_start = strpos($msg,'<mailing resident=3D"');

					if ($spy_start !== false && $found_sender)
					{
						mailing_log('found a mailing tag');

						$spy_end = strpos($msg,'</mailing',$spy_start);
						if ($spy_end===false)
						{
							$spy_end = $spy_start+150;
						}

						if ($spy_end!==false)
						{
							mailing_log('found spy! '.$spy_end);
							
							$spy_str = substr($msg,$spy_start,$spy_end-$spy_start);
							$viewing_code_start = strpos($spy_str,' viewing_code=3D"');
							$ID_start = strpos($spy_str,' ID=3D"');

							if ($viewing_code_start !== false && $ID_start !== false)
							{
								$viewing_code_end = strpos($spy_str,'"',$viewing_code_start+18);

								mailing_log("*** ".$spy_str." ***");

								$ID_end = strpos($spy_str,'"',$ID_start+8);
								$viewing_code = substr($spy_str,$viewing_code_start+17,$viewing_code_end-$viewing_code_start-17);
								$mailingID = substr($spy_str,$ID_start+7,$ID_end-$ID_start-7);
								mailing_log("viewing_code = ".$viewing_code);
								$found_info = true;
							}
							else
							{
								mailing_log("couldn't find ID or viewing_code ID?".$ID_start." viewing_code?".$viewing_code_start);
							}
						}
						else
						{
							mailing_log('found no closing mailing tag in '. substr($msg,$spy_start,'300'));
						}
					}

					$regex_over_quota = '(quota exceeded)|(exceeded storage allocation)|(mailbox is full)|(over quota)|(maximum allowed quota)';
					$regex_error = '(did not reach the following recipient)|(permanent fatal errors)|(Unable to deliver)|(Notification)|(Notice)|(Returned mail)|(Undeliverable)|(Delivery)|(Undelivered)|(Transcript of session)|(Failure notice)|(permanent error)|(blocked by administrator)|(failed permanently)|(Action: failed)';	

					$is_error = eregi($regex_over_quota.'|'.$regex_error,$msg);
					if (!$is_error)
					{
						mailing_log("It's not an error message");
					}

					$new_status = 'erroneous';
					$is_over_quota = eregi('(quota exceeded)|(exceeded storage allocation)|(mailbox is full)|(over quota)',$msg);
					if ($is_over_quota)
					{
						$new_status = 'over_quota';
					}

					if ($found_sender && /*$is_error && */ $found_info && is_numeric($mailingID) && $viewing_code != '')
					{
						if (strlen($viewing_code) > 32)
						{
							$viewing_code = substr($viewing_code,0,32);
						}

						$maildir = $GLOBALS["directoryRoot"].$slash.'mailing'.$slash.$mailingID;
						makeDir($maildir);

						$sql = 'SELECT * FROM `mailing_recipients` WHERE `Status` = "sent" AND `ViewingCode` = "'.$viewing_code.'" AND `MailingID` = '.$mailingID.';';
						$recipient = $db_conn->GetRow($sql);

						mailing_log($sql);
						mailing_log($recipient);

						if ($recipient)
						{
							$info_sql = "SELECT * FROM ".$moduleInfo->tableName." WHERE ID = ".$mailingID.";";
							mailing_log($info_sql);
							$info = $db_conn->GetRow($info_sql);
							$email = $recipient['Email'];

							// must complete the infos in the xml
							$contact_sql = "SELECT * FROM ".$moduleContactInfo->tableName." WHERE ID=".$recipient['ContactID'].";";
							mailing_log($contact_sql);
							$contact = $db_conn->GetRow($contact_sql);

							setMailingRecipientStatus($mailingID,$viewing_code,$new_status,false,$now,$steps);
							updateMailingCounts($mailingID);

							// must mark the email as incorrect (in db)
							if (!$is_over_quota)
							{
								// the condition on the Email is to avoid discarding something that has been modified since sending
								$update_sql = 'UPDATE `'.$moduleContactInfo->tableName.'` SET `ModificationDate` = "'.$GLOBALS["sushee_today"].'",`EmailInvalid`=`EmailInvalid`+1 WHERE `ID`=\''.$contact['ID'].'\' AND `Email1`="'.$email.'";';
								mailing_log($update_sql);
								$db_conn->Execute($update_sql);
							}
						}
						
						$save_in_path = $maildir.$slash.$viewing_code.'.txt';
						saveInFile($message,$save_in_path);
						mailing_log($save_in_path);

						// must delete the message
						$delete = true;
					}
					else if ($found_recipient && $found_sender)
					{
						$now = date('Y-m-d H:i:s');
						$time_select = '';
						if ($timestamp)
						{
							$time_select = '`UniqueSendingDate` > "' . date('Y-m-d H:i:s',$timestamp - 86400 - 86400 ) . '" AND `UniqueSendingDate` <= "' . date('Y-m-d H:i:s',$timestamp) . '" AND';
						}

						$sql = 'UPDATE `mailing_recipients` SET `Status`="'.$new_status.'",`RejectingDate`="'.$now.'" WHERE '.$time_select.' `Status` = "sent" AND Email = "'.$email_recipient.'";';
						$db_conn->Execute($sql);
						mailing_log($sql);

						if (!$is_over_quota)
						{
							$sql = 'UPDATE `'.$moduleContactInfo->tableName.'` SET `ModificationDate` = "'.$now.'",`EmailInvalid`=`EmailInvalid`+1 WHERE `Email1` LIKE "%'.$email_recipient.'%" OR ( `Email1`="" AND `Email2` LIKE "%'.$email_recipient.'%");';
							$db_conn->Execute($sql);
							mailing_log($sql);
						}

						// must delete the message
						$delete = true;
					}
					else if ($found_sender && !$found_recipient)
					{
						// no information > delete message
						$delete = true;
					}

					if ($delete == true)
					{
						mailing_log('deleting...');
						$pop3->deleteMsg($i);
					}
				}
			}

			// send Noob command
			if ($noob)
			{
				if (!$pop3->_cmdNoop())
				{
					$noob = false;
				}
			}

			// if ($i>500)
			// {
			// 	break;
			// }
		}

		$pop3->disconnect();
	}
	else
	{
		debug_log('mailing module not available');
	}

	$query_result = generateMsgXML(0,"The check was succesfully accomplished",0,'',$name);
	return $query_result;
}