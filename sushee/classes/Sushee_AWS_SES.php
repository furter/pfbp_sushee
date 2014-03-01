<?php

require_once(dirname(__FILE__).'/../lib/Swiftmailer/lib/swift_required.php');
require_once(dirname(__FILE__).'/../lib/Swiftmailer-ses/classes/Swift/Transport/AWSTransport.php');
require_once(dirname(__FILE__).'/../lib/Swiftmailer-ses/classes/Swift/AWSTransport.php');
require_once(dirname(__FILE__).'/../lib/Swiftmailer-ses/classes/Swift/AWSInputByteStream.php');

//
// Simple Wrapper to AMAZON SES using Swift Mailer and SES Transport
//
// Keys must be defined in /sushee.conf.php
// 
// define( 'AWSAccessKeyId'	, 'XXXX' );
// define( 'AWSSecretKey'	, 'YYYY' );
//

class Sushee_AWS_SES
{
	function Sushee_AWS_SES()
	{
		$transport = Swift_AWSTransport::newInstance( AWSAccessKeyId, AWSSecretKey );
		$transport->setDebug( true ); // Print's the response from AWS for debugging.

		$this->mailer = Swift_Mailer::newInstance( $transport );
		$this->email = Swift_Message::newInstance();
		$this->email->setCharset('utf-8');
	}

	function send_mail($email,$mime)
	{
		$from = $email['from'];					// string
		$to = $email['to'];						// string or array
		$subject = $email['subject'];			// string
		$message_html = $email['message_html'];	// string
		$message_txt = $email['message_txt'];	// string
		$cc = $email['cc'];						// string or array of string
		$bcc = $email['bcc'];					// string or array of string
		$attachments = $email['attachments'];	// string or array of string
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

		$from = $this->checkAuthorizedSenders($from);

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

		$sendrate = AWSSendRate;
		if (!$sendrate)
			$sendrate = 1;

		$sleep = ceil( 1000000 / $sendrate );
		usleep($sleep);

		return $result;
	}
	
	function checkAuthorizedSenders($array)
	{
		// Check sender(s)
		if ( defined(AWSSenders) )
		{
			$authorized = array();
			$senders = explode(',',AWSSenders);
			foreach ($array as $address)
			{
				foreach ($senders as $sender)
				{
					if (strpos($address,$sender) !== false)
					{
						$authorized[] = $address;
					}
				}
			}
			$array = $authorized;
			if (empty($array))
			{
				return false; // no sender, no recipient > no mail
			}
		}
		
		return $array;
	}
}