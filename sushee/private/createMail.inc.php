<?php

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
include_once(dirname(__FILE__).'/../common/mail.class.php');

class createServerMail extends NQLOperation
{
	var $sender;
	var $subject;
	var $body;
	var $isHTML = false;
	var $recipients; // normal recipients
	var $forwards; // CC recipient
	var $bccs; // BCC recipient
	var $attachments; // files attached
	
	function decode($str)
	{
		return decode_from_XML(UnicodeEntities_To_utf8($str));
	}
	
	function parse()
	{
		// Sender
		$sender = $this->firstNode->valueOf("/SENDER[1]");
		if($sender)
		{
			// sender given in parameter
			$sender_name = $sender;
			$sender_mail = $this->firstNode->getData("/SENDER[1]/@email");
		}
		else if(isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']))
		{
			// automatic sender : user connected
			$moduleInfo = moduleInfo('contact');
			$contact = getInfo($moduleInfo,$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']);
			if($contact["LastName"])
				$sender_name = $contact["LastName"].' ';
			if($contact["FirstName"])
				$sender_name.=$contact["FirstName"].' ';
			if($contact["Denomination"])
				$sender_name.=$contact["Denomination"];
			$sender_mail = $contact["Email1"];
		}
		else
		{
			// default anonymous sender
			$sender_name = "Sushee";
			$sender_mail = "";
		}

		// Subject
		$mail_subject = $this->firstNode->valueOf("/SUBJECT[1]");
		if(!$mail_subject)
			$mail_subject = $_SERVER['REQUEST_URI'];
			
		// Body
		if($this->firstNode->getElement("/BODY/html[1]"))
		{
			$this->body = $this->firstNode->copyOf("/BODY/html[1]");
			$this->isHTML = true;
		}
		else
		{
			$this->body = $this->decode($this->firstNode->valueOf("/BODY[1]"));
			if(!$this->body)
			{
				$this->body = $_SERVER['HTTP_REFERER'];
			}
		}

		// Attachments
		$attachmentsNodes = $this->firstNode->getElements("/ATTACHMENT");
		$attachments = array();
		foreach($attachmentsNodes as $node)
		{
			$text = $node->valueOf();
			if($text)
			{
				$file = $this->decode($text);
				$attachment = $GLOBALS["directoryRoot"].$file;
				if(is_file($attachment))
				{
					$attachments[] = $attachment;
				}
			}
		}

		// Recipients
		$recipientsNodes = $this->firstNode->getElements("/RECIPIENT");
		$recipients = array();
		foreach($recipientsNodes as $node)
		{
			$recipient = $this->decode($node->valueOf());
			if($recipient!='')
			{
				$recipients[] = $recipient;
			}
		}
	
		// Forwards
		$recipientsNodes = $this->firstNode->getElements("/FORWARD");
		$forwards = array();
		foreach($recipientsNodes as $node)
		{
			$recipient = $this->decode($node->valueOf());
			if($recipient!='')
			{
				$forwards[] = $recipient;
			}
		}
		
		// BCC
		$recipientsNodes = $this->firstNode->getElements("/BCC");
		$bccs = array();
		foreach($recipientsNodes as $node)
		{
			$recipient = $this->decode($node->valueOf());
			if($recipient!='')
			{
				$bccs[] = $recipient;
			}
		}

		if($this->decode($sender_mail))
		{
			$this->sender = formatMailAdress($this->decode($sender_mail),$this->decode($sender_name));
		}
		else
		{
			$this->sender = $this->decode($sender_name);
		}

		$this->subject = $this->decode($mail_subject);
		//$this->body = $this->decode($mail_body);
		$this->recipients = $recipients;
		$this->forwards = $forwards;
		$this->bccs = $bccs;
		$this->attachments = $attachments;
		
		return true;
	}

	function operate()
	{
		
		$mail = new ServerMail();
		$mail->setSubject($this->subject);
		$mail->setSender($this->sender);
		
		// setting the mail content
		if($this->isHTML)
		{
			$mail->setHTML($this->body);
			$mail->setText(generatePlainTextFromHTML($this->body));
		}
		else
		{
			$mail->setText($this->body);
			$html_body = nl2br(str_replace('&apos;','&#39;',encode_to_xml($this->body)));
			$mail->setHTML('<html><body>'.$html_body.'</body></html>');
		}

		foreach($this->attachments as $filepath)
		{
			$mail->addAttachment($filepath);
		}

		// recipients
		foreach($this->recipients as $recipient)
		{
			$mail->addRecipient($recipient);
		}
		foreach($this->forwards as $recipient)
		{
			$mail->addCc($recipient);
		}
		foreach($this->bccs as $recipient)
		{
			$mail->addBcc($recipient);
		}
		
		//sending mail
		$res = $mail->execute();
		
		if($res)
			$this->setSuccess("Message sent");
		else
			$this->setError("Message NOT sent");
		return $res;
	}
}