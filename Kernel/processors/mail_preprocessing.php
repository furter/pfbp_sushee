<?php

if ( $requestName == "CREATE" || $requestName == "UPDATE")
{
	$filesPath = Sushee_Instance::getFilesPath();
	$user = new Sushee_User();
	$userID = $user->getID();
	
	// we need the ID of the mail
	// to save the HTML version and the attachments
	if($requestName=='CREATE')
	{
		$mailID = $moduleInfo->getNextID();
		$values['ID'] = $mailID;
	}
	else
	{
		$mailID = $ID;
	}

	if($values['Type'] == 'out' || $values['Type'] == 'draft')
	{
		$mailDirPath = '/mail/'.$userID.'/'.date('Y-m').'/sent/'.date('d').'/'.$mailID.'/';
		$mailDir = new Folder($mailDirPath);
		$mailDir->create();
		$mailFilesDirPath = $mailDirPath.'files/';
		$mailFilesDir = new Folder($mailFilesDirPath);
		$mailFilesDir->create();
		
		// copying all attachments in maildir
		// handling the attachments
		if(isset($values['Attachments']))
		{
			/*
			<FOLDER> contains the folder with the mail attachments (inside a subdirectory called 'files') and the mail as HTML (mail.html)
			<ATTACHMENTS> contains the files to attach, separated by commas. Sushee resolves the different path given, copies them inside the mail directory and only saves the filenames separated by commas Eg : 23.jpg,Cv.txt,my.pdf
			*/
			$attachments = array();
			if($values['Attachments'])
			{
				$attachments = explode(',',$values['Attachments']);
			}

			$final_attachments_filenames = array();
			
			// copying attachments in a single directory
			foreach($attachments as $attachmentPath)
			{
				// new attachment
				if($attachmentPath && !startsWith($attachmentPath,$mailFilesDirPath) && $attachmentPath[0]=='/')
				{
					// only if file is not yet inside the folder and if its a sushee path
					$toattach = new File($attachmentPath);
					if($toattach->isFolder())
					{
						// we have to copy the multiple files the folder contains
						$toattach->copyContent($mailFilesDir);
						
						// we have to register the multiple files, to know we have to keep it
						$toattach = new Folder($toattach->getPath());
						while($file = $toattach->next())
						{
							$final_attachments_filenames[] = $file->getName();
						}
					}
					else
					{
						$toattach->copy($mailFilesDir);
						$final_attachments_filenames[] = $toattach->getName();
					}
				}
				else
				{
					// attachment already exists
					if($attachmentPath[0]=='/')
					{
						$attachment = new File($attachmentPath); // complete path
					}
					else
					{
						$attachment = new File($mailFilesDirPath.$attachmentPath); // only filename given, inside maildir
					}
					// registering it, to know we have to keep it
					if($attachment->exists())
					{
						$final_attachments_filenames[] = $attachment->getName();
					}
				}				
			}

			// cleaning maildir from removed attachment (no more transmitted in Attachments)
			$mailFilesDir->reset();
			while($file = $mailFilesDir->next())
			{
				$filename = $file->getName();
				if(!in_array($filename,$final_attachments_filenames))
				{
					$file->unlink();
				}
			}

			// saving all attachments names in field ATTACHMENT
			$attachments_filenames = array();
			$mailFilesDir->reset();
			while($file = $mailFilesDir->next())
			{
				$attachments_filenames[] = $file->getName();
			}

			// setting values for database saving and for XML return (return_values)
			$values['Attachments'] = implode(',',$attachments_filenames);
			$values['Folder'] = $mailDirPath;
			$return_values['Folder'] = $values['Folder'];
			$return_values['Attachments'] = $values['Attachments'];
		}
	}

	// sending mail
	if($values['Type'] == 'out')
	{
		require_once(dirname(__FILE__).'/../common/mail.class.php');
		require_once(dirname(__FILE__).'/../common/xslt.functions.php');

		$mime = new ServerMail();

		// attach the files to the mail
		$mailFilesDir->reset();
		while($file = $mailFilesDir->next())
		{
			if ($file->exists() && !$file->isFolder())
			{
				$mime->addAttachment($file->getCompletePath());
			}
		}

		// transforming the styled version to a plaintext version
		if($values['StyledText'])
		{
			$values['PlainText'] = CSStoPlain($xml,$firstNodePath."/INFO/STYLEDTEXT/CSS");
			$styledtext = $values['StyledText'];
		}
		else
		{
			// generating a styled version, with paragraphs for each newlines
			$styledtext = str_replace('\r\n','\n',encode_to_xml($values['PlainText']));
			$styledtext = '<p>'.str_replace('
','</p><p>',$styledtext).'</p>';
			$styledtext = '<CSS>'.$styledtext.'</CSS>';
		}

		// !!! StyledText DEPRECATED !!!
		unset($values['StyledText']);
		// !!! StyledText DEPRECATED !!!

		// adding the plaintext version in the mail
		$mime->setText(UnicodeEntities_To_utf8($values['PlainText']));
	

		// generating the HTML version with nice colored blockquotes

		$richtext = CSStoStyledQuote($styledtext);

		// replacing [files_url] by a real url
		$richtext = str_replace('[files_url]',$GLOBALS["files_url"],$richtext);
		$richtext = str_replace('&apos;',"'",$richtext);

		// replacing empty paragraphs by paragraphs with only a carriage return
		$richtext = str_replace(array('<p/>','<h1/>','<h2/>','<h3/>'),array('<p><br /></p>','<h1><br /></h1>','<h2><br /></h2>','<h3><br /></h3>'),$richtext);
		$richtext = xsl_plaintext_linkstring($richtext,true);

		if($richtext)
		{
			$values['HTML'] = 1;
			$richtextFile = $mailDir->createFile('mail.html');
			$richtextFile->save($richtext);
		}

		// including the css corresponding to the html
		$cssFile = new KernelFile('/Library/mail/css/inside_mail.css');
		if($cssFile->exists())
		{
			$css = $cssFile->toString();
		}

		// adding the HTML version of the mail
		$richtext = '<html><head><title>'.encode_to_xml($values['Subject']).'</title></head><body><style>'.$css.'</style>'.$richtext.'</body>';
		$mime->setHTML($richtext);
		
		// finalizing the mail
		$mime->setSender(decode_from_XML(UnicodeEntities_To_utf8($values['From'])));
		$mime->addRecipient(decode_from_XML(UnicodeEntities_To_utf8(stripEmail($values['To']))));
		$mime->addCC(decode_from_XML(UnicodeEntities_To_utf8(stripEmail($values['Cc']))));
		$mime->addBCC(decode_from_XML(UnicodeEntities_To_utf8(stripEmail($values['Bcc']))));
		$mime->setSubject(decode_from_XML(UnicodeEntities_To_utf8($values['Subject'])));
		$mime->setPriority($values['Priority']);

		// adding SMTP parameters
		$accountid = $values['AccountID'];
		$accoundsessid = 'MAILACCOUNT-'.$accountid;
		$account = Sushee_Session::getVariable($accoundsessid);
		if (!$account)
		{
			$db = db_connect();
			$sql = 'SELECT `SMTPServer`,`SMTPPort`,`SMTPSecurity`,`SMTPLogin`,`SMTPPassword` FROM `mailsaccounts` WHERE `ID` = "'.$accountid.'";';
			$account = $db->getRow($sql);
			if ($account)
			{
				Sushee_Session::saveVariable($accoundsessid,$account);
			}
		}

		if ($account)
		{
			$mime->setSMTPconfig($account);
		}

		// sending the mail
		$res = $mime->execute();

		if($res)
		{
			$values['Type'] = 'sent';
			$values['Read'] = 1;
			$values['SendingDate'] = $GLOBALS["sushee_today"];
			$values['ReceivingDate'] = $GLOBALS["sushee_today"];
		}
		else
		{
			return generateMsgXML(1,"The message could not be sent.",0,'',$name);
		}
	}
}
else if($requestName == 'KILL' || $requestName == 'DELETE')
{
	$mail_sql = 'SELECT `Folder`,`ID` FROM `'.$moduleInfo->getTableName().'` WHERE `ID` IN ('.implode(',',$IDs_array).') AND `Folder` != "" ';

	$folders_rs = $db_conn->Execute($mail_sql);
	while($mail = $folders_rs->FetchRow())
	{
		$mailFolder = new Folder($mail['Folder']);
		if ($mailFolder->exists())
		{
			$mailFolder->delete();
		}
	}
}

return true;