<?php

require_once(dirname(__FILE__).'/../lib/Swiftmailer/lib/swift_required.php');

//
// Simple SMTP Swift Mailer Wrapper
//

class Sushee_SMTPMailer
{
	function Sushee_SMTPMailer($smtp_config)
	{
		if ( empty($smtp_config['SMTPServer']) )
		{
			return false;
		}

		if (empty($smtp_config['SMTPPort']))
		{
			$smtp_config['SMTPPort'] = 25;
		}

		$transport = Swift_SmtpTransport::newInstance($smtp_config['SMTPServer'],$smtp_config['SMTPPort']);

		if (!empty($smtp_config['SMTPSecurity']))
		{
			$transport->setEncryption($smtp_config['SMTPSecurity']);
		}

		if (!empty($smtp_config['SMTPLogin']))
		{
			$transport->setUsername($smtp_config['SMTPLogin']);
			$transport->setPassword($smtp_config['SMTPPassword']);
		}

		$this->mailer = Swift_Mailer::newInstance( $transport );
		$this->email = Swift_Message::newInstance();
		$this->email->setCharset('utf-8');
	}

	function send_mail($mail,$mime)
	{
		$from = $mail['from'];					// string
		$to = $mail['to'];						// string or array
		$subject = $mail['subject'];			// string
		$message_html = $mail['message_html'];	// string
		$message_txt = $mail['message_txt'];	// string
		$cc = $mail['cc'];						// string or array of string
		$bcc = $mail['bcc'];					// string or array of string
		$attachments = $mail['attachments'];	// string or array of string
		$bounce = $email['bounce'];				// string

		if (empty($from) || empty($to))
		{
			return false; // no sender, no recipient > no mail
		}
		if (empty($message_html) && empty($message_txt))
		{
			return false; // no message > no mail
		}
		// Values checking
		if (gettype($from) === 'string')
			$from = array($from);
		if (gettype($to) === 'string')
			$to = array($to);
		if (gettype($cc) === 'string')
			$cc = array($cc);
		if (gettype($bcc) === 'string')
			$bcc = array($bcc);
		if (empty($subject))
			$subject = '[empty]';
		if (gettype($attachments) === 'string')
			$attachments = array($attachments);
		// Fill email
		$this->email->setFrom( $from );
		$this->email->setTo( $to );
		$this->email->setSubject( $subject );
		
		if (!empty($bounce))
		{
			$this->email->setReturnPath( $bounce );
		}
	 	if (!empty($cc))
		{
			$this->email->setCc( $cc );
		}
		if (!empty($bcc))
		{
			$this->email->setBcc( $bcc );
		}
		if (!empty($message_html))
		{
			$this->email->setBody( $message_html , 'text/html' );
		}
		if (!empty($message_txt))
		{
			$this->email->addPart( $message_txt , 'text/plain' );
		}
		if (!empty($attachments))
		{
			foreach ($attachments as $file)
			{
				$this->email->attach( Swift_Attachment::fromPath($file) );
			}
		}

 		$result = $this->mailer->send( $this->email );
 		return $result;
	}
}