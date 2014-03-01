<?php

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php");

@set_time_limit(0);

global $slash;

$moduleInfo = moduleInfo('mailing');
$moduleContactInfo = moduleInfo('contact');

if ($moduleInfo->loaded )
{
	$db_conn = db_connect();

	$now = date("Y-m-d H:i:s");
	$sql = 'SELECT * FROM `'.$moduleInfo->tableName.'` WHERE `ID`!=1 AND `Activity` = 1 AND (`Status`="pending" OR `Status`="sending") AND `SendingDate` <= "'.$now.'";';

	$rs = $db_conn->Execute($sql);
	if ($rs)
	{
		if (isset($GLOBALS["MaxEmailPacket"]))
		{
			$emails_by_sending = $GLOBALS["MaxEmailPacket"];
		}
		else
		{
			$emails_by_sending = 100;
		}

		$emails_sent = 0;

		while ($row = $rs->FetchRow())
		{
			mailing_log('start mailing '.$row['ID']);

			// two possible engines : pure xslt or one xslt + grep (more limited in features)
			if ($row['Engine']=='grep')
			{
				// -- GREP ENGINE --
				// -- xslt + grep (more limited in features) --
				// -- get the content once for all recipients --

				$grep_templates_dir = $GLOBALS["directoryRoot"].$slash."tmp".$slash.'mailing_grep_templates'.$row['ID'].$slash;
				if (!file_exists($grep_templates_dir))
				{
					makeDir($grep_templates_dir);
					$all_fields = $moduleContactInfo->getFieldsBySecurity('0');
					$fake_contact = array();
					// preparing a fake contact to generate a generic template with xslt an then replace the occurences of [FIELD] by the value for each contact
					foreach($all_fields as $fieldname)
					{
						$fake_contact[$fieldname] = '['.strtoupper($fieldname).']';
					}
					// for each possible sending language, generate a template, we will choose at sending which one is better for the recipient
					$sql = "SELECT * FROM medialanguages";
					$lg_rs = $db_conn->Execute($sql);
					while ($lg_row = $lg_rs->FetchRow())
					{
						$current_lg = $lg_row['languageID'];
						$fake_contact['LanguageID'] = $current_lg;
						$subject ='';
						$html_str='';
						$text_str='';
						$_GET['cache']='refresh';
						$xml_str = generateCompleteMailingXML(true,$row,$fake_contact,'[viewing_code]',$subject);
						$params = array('backoffice_url'=>$GLOBALS['backoffice_url'],'in_mailbox'=>'true');
						$template_path = $GLOBALS['library_dir'].'mailing/templates/'.$row['Template'];
						$text_template_path = $GLOBALS['library_dir'].'mailing/templates/text.xsl';

						if (file_exists($template_path))
						{
							$html_str = real_transform($xml_str,$template_path,$params,true,false);
						}
						
						if (file_exists($text_template_path))
						{
							$text_str = real_transform($xml_str,$text_template_path,$params,true,false);
						}

						saveInFile($subject,$grep_templates_dir.'subject_'.$current_lg.'.txt');
						saveInFile($text_str,$grep_templates_dir.'plaintext_'.$current_lg.'.txt');
						saveInFile($html_str,$grep_templates_dir.'html_'.$current_lg.'.txt');
					}
				}
			}

			$this_emails_sent = 0;
			$successful_emails_sent = 0;
			$new_status = "sending";
			$recipientsGenerated = generateMailingRecipients($row);

			if (!$recipientsGenerated)
			{
				$sending_attempt = date("Y-m-d H:i:s");
				$total_to_send = getMailingRecipientsCount($row['ID'],'not_sent');
	
				if($row['NbrSent']==0)
				{
					$update_sending_date = ',`SendingDate`="'.$sending_attempt.'"';
					// first time we send the mails, must update the medias, because maybe someone previewed them in navigator
					$_GET['cache']='refresh';
				}
				else
				{
					$update_sending_date = '';
				}
				
				if ($row['EmailPacket']>$emails_by_sending)
				{
					$recip_condition = ' WHERE `Status`="not_sent" AND `MailingID`='.$row['ID'].' LIMIT '.$emails_by_sending;
				}
				else
				{
					$recip_condition = ' WHERE `Status`="not_sent" AND `MailingID`='.$row['ID'].' LIMIT '.$row['EmailPacket'];
				}
					
				$recip_sql = 'SELECT * FROM `mailing_recipients`'.$recip_condition;
				$recip_update = 'UPDATE `mailing_recipients` SET `Status`="sending"'.$recip_condition;
				
				mailing_log('mark recipient '.$recip_sql);
				mailing_log('recipients '.$recip_update);
				
				$recip_rs = $db_conn->Execute($recip_sql);
				$db_conn->Execute($recip_update);
				while($recipient=$recip_rs->FetchRow())
				{
					mailing_log('start recipient '.$recipient['ContactID']);
					$viewing_code = $recipient['ViewingCode'];
					$contact_ID = $recipient['ContactID'];
					$contact = getInfo($moduleContactInfo,$contact_ID);

					$subject ='';

					// trick to get the chosen email from the contact (Email1 or Email2) 
					$contact['Email1'] = $recipient['Email'];

					if ($row['Engine'] == 'grep')
					{
						echo 'Using Grep engine'.PHP_EOL;
						$chosen_lg = $contact['LanguageID'];
						if(!file_exists($grep_templates_dir.'subject_'.$chosen_lg.'.txt'))
						{
							$chosen_lg = $row['DefaultLanguage'];
						}
						$subject = file_in_string($grep_templates_dir.'subject_'.$chosen_lg.'.txt');
						$html_str = file_in_string($grep_templates_dir.'html_'.$chosen_lg.'.txt');
						$text_str = file_in_string($grep_templates_dir.'plaintext_'.$chosen_lg.'.txt');
						$searches = array('[viewing_code]','%5Bviewing_code%5D'); // %5B is [ in attributes
						$replacments = array($viewing_code,$viewing_code);
						foreach($contact as $field=>$value)
						{
							$searches[]='['.strtoupper($field).']';
							$replacments[]=$value;
							$searches[]='%5B'.strtoupper($field).'%5D';
							$replacments[]=$value;
						}
						list($subject,$html_str,$text_str) = str_replace($searches,$replacments,array($subject,$html_str,$text_str));
						$res = 0;
						$res = sendMailofMailing($row,$contact,'',$subject,$viewing_code,$html_str,$text_str);
					}
					else
					{
						$xml_str = generateCompleteMailingXML(true,$row,$contact,$viewing_code,$subject);
						$res = sendMailofMailing($row,$contact,$xml_str,$subject,$viewing_code);
					}
					
					mailing_log('emails_sent '.$emails_sent.' this_emails_sent '.$this_emails_sent.' and res is '.$res);

					$emails_sent++;
					$this_emails_sent++;

					// updating recipient status

					if ($res === 'no_content')
					{
						setMailingRecipientStatus($row['ID'],$viewing_code,'no_content',$sending_attempt);
						$successful_emails_sent++;
						mailing_log('recipient no_content '.$recipient['ContactID']);
					}
					else if ($res)
					{
						$sql = 'UPDATE `mailing_recipients` SET `Status`="sent",`UniqueSendingDate`="'.date('Y-m-d H:i:s').'",`SendingDate`="'.$sending_attempt.'" WHERE `ContactID`="'.$recipient['ContactID'].'" AND `MailingID`='.$row['ID'].' AND `Status`="sending";';
						$db_conn->Execute($sql);
						$successful_emails_sent++;
						mailing_log('recipient  sent'.$recipient['ContactID']);
					}
					else
					{
						setMailingRecipientStatus($row['ID'],$viewing_code,'error');
						$successful_emails_sent++;
						mailing_log('recipient error '.$recipient['ContactID']);
					}

					if ($this_emails_sent >= $row['EmailPacket'] || $this_emails_sent >= $emails_by_sending)
					{
						break;
					}

					mailing_log('finish recipient '.$recipient['ContactID']);
				}
				
				if ($total_to_send==$successful_emails_sent)
				{
					$new_status = "sent";
				}
			}

			$sql = 'UPDATE `'.$moduleInfo->tableName.'` SET `Status`="'.$new_status.'"'.$update_sending_date.' WHERE ID='.$row['ID'].';';
			mailing_log('finished recipients from this packet ');
			$db_conn->Execute($sql);
			updateMailingCounts($row['ID']);
			if ($emails_sent >= $emails_by_sending)
			{
				break;
			}
		}
	}
	else
	{
		echo 'no mailing.'.PHP_EOL;
	}
}
else
{
	echo 'module Mailing not loaded.'.PHP_EOL;
}

echo getTimer('mailings sent.').PHP_EOL;