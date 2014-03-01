<?php

require_once(dirname(__FILE__).'/../common/Mail/pop3.php');
require_once(dirname(__FILE__).'/../common/Mail/mime.php');
require_once(dirname(__FILE__).'/../common/Mail/mimeDecode.php');
require_once(dirname(__FILE__).'/../file/file_functions.inc.php');
require_once(dirname(__FILE__).'/../common/automatic_classifier.class.php');
require_once(dirname(__FILE__).'/../common/crypt.class.php');
require_once(dirname(__FILE__).'/../common/nectil_element.class.php');
require_once(dirname(__FILE__).'/../common/registers.class.php');
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../classes/Sushee_SMTPMailer.php");

define('MAIL_RETREIVING_MODE_DESC'	, 1);
define('MAIL_RETREIVING_MODE_ASC'	, 2);
define('MAIL_REGEX_RECIPIENTS'		, '/(,)(?=(?:[^"]|"[^"]*")*$)/');

class ServerMail extends SusheeObject
{
	var $mime = false;
	var $sendertype = 'system';
	var $sender = false;
	var $bounce = false;
	var $subject = false;
	var $recipients = array();
	var $suppl_headers = false;
	var $priority = false;

	var $smtp_config;

	var $message_html;
	var $message_text;
	var $cc = array();
	var $bcc = array();
	var $attachments = array();

	function ServerMail()
	{
		if (getServerOS()=='windows')
			$crlf = "\r\n";
		else
			$crlf = "\n";
		$this->mime = new Mail_mime($crlf);
	}

	function addHeader($header)
	{
		$this->suppl_headers.=$header;
	}

	function formatMailAdress($email,$name)
	{
		return formatMailAdress($email,$name);
	}

	function setSubject($subject)
	{
		$this->subject = $subject;
	}

	function setText($text)
	{
		$this->message_text = $text;
		$this->mime->setTXTBody(UnicodeEntities_To_utf8($text));
	}

	function setHTML($html)
	{
		$this->message_html = $html;
		$this->mime->setHTMLBody($html);
	}

	function addHTMLImage($file,$mime,$id)
	{
		$this->mime->addHTMLImage($file, $mime ,$id);
	}

	function addAttachment($file)
	{
		$attach = $file;
		if(!is_file($attach))
			$attach = $GLOBALS["directoryRoot"].$file;
		if(!is_file($attach))
			$attach = $GLOBALS["nectil_dir"].$file;

		if(is_file($attach))
		{
			$this->attachments[] = $attach;
			$this->mime->addAttachment($attach);
		}
	}

	function addCc($cc)
	{
		$this->mime->addCc($cc);
		if (preg_match( MAIL_REGEX_RECIPIENTS , $cc ))
		{
			$list = preg_split( MAIL_REGEX_RECIPIENTS , $cc );
			foreach ($list as $recipient)
			{
				$this->cc[] = $recipient;
			}
		}
		else
		{
			$this->cc[] = $cc;
		}
	}

	function addBcc($bcc)
	{
		$this->mime->addBcc($bcc);
		if (preg_match( MAIL_REGEX_RECIPIENTS , $bcc ))
		{
			$list = preg_split( MAIL_REGEX_RECIPIENTS , $bcc );
			foreach ($list as $recipient)
			{
				$this->bcc[] = $recipient;
			}
		}
		else
		{
			$this->bcc[] = $bcc;
		}
	}

	function setSender($sender)
	{
		$this->sender = $sender;
	}
	function getSender()
	{
		if ($this->sender)
		{
			return $this->sender;
		}
		else
		{
			return $GLOBALS["admin_email"];
		}
	}

	function setBounce($bounce)
	{
		$this->bounce = $bounce;
	}
	function getBounce()
	{
		return $this->bounce;
	}

	function setSMTPconfig($array)
	{
		$this->smtp_config = $array;
	}
	function getSMTPconfig()
	{
		return $this->smtp_config;
	}

	function setPriority($priority)
	{
		$this->priority = $priority;
	}
	function getPriority()
	{
		return $this->priority;
	}

	function execute()
	{
		$message = $this->mime->get(array("text_charset"=>"utf-8","head_charset"=>"utf-8","html_charset"=>"utf-8","text_format"=>"flowed"));

		$real_from_mime = $this->mime->_encodeHeaders(array('From'=>UnicodeEntities_To_utf8($this->getSender())));
		$real_from = $real_from_mime['From'];

		if(!$this->bounce)
		{
			$this->bounce = $real_from;
		}

		$hdrs = array(
			'From'    => $real_from,
			'return-path' => $this->bounce,
			'errors-to' => $this->bounce,
			'bounces-to' => $this->bounce,
			'X-Mailer' => 'Sushee Mail'
		);

		if($this->priority!=false)
		{
			$hdrs['X-Priority']= $this->priority;
		}

		$this->mime->headers($hdrs);
		$headers = $this->mime->txtHeaders();
		$headers.= $this->suppl_headers;
		$recipient = implode(',',$this->recipients);

		$headers_array = $this->mime->_encodeHeaders(array('To'=>UnicodeEntities_To_utf8($recipient),'Subject'=>UnicodeEntities_To_utf8($this->subject)));

		// we force sendmail to use return-path that we want (because many mail servers assume its spam if the return path is different from the sender)
		$additional_parameters = '';
		$sendmail_path = ini_get('sendmail_path');

		// if the parameter is already set, we dont force because this parameter cannot be forced twice
		if(strpos($sendmail_path,' -f ')===false)
		{
			// real_from == "Name" <email@domain.com>
			$real_from_email_pos = strpos($this->bounce,' <');
			if($real_from_email_pos!==false)
			{
				// part with the email
				$real_from_email_end = strpos($this->bounce,'>',$real_from_email_pos);
				// +2 because of the space and the <
				$this->bounce = substr($this->bounce,$real_from_email_pos + 2,$real_from_email_end - $real_from_email_pos - 2);
			}
			$additional_parameters = ' -f'.$this->bounce.' -r'.$this->bounce;
		}

		$total_headers = $this->mime->headers();
	
		$to = $headers_array['To'];
		if (preg_match( MAIL_REGEX_RECIPIENTS , $to ))
		{
			$to = preg_split( MAIL_REGEX_RECIPIENTS , $to );
		}
		
		$cc = $total_headers['Cc'];
		if (preg_match( MAIL_REGEX_RECIPIENTS , $cc ))
		{
			$cc = preg_split( MAIL_REGEX_RECIPIENTS , $cc );
		}

		$bcc = $total_headers['Bcc'];
		if (preg_match( MAIL_REGEX_RECIPIENTS , $bcc ))
		{
			$bcc = preg_split( MAIL_REGEX_RECIPIENTS , $bcc );
		}	

		$email = array(
			 'from'			=> $real_from
			,'to'			=> $to
			,'subject'		=> $headers_array['Subject']
			,'message_html'	=> $this->message_html
			,'message_txt'	=> $this->message_text
			,'cc'			=> $cc //$this->cc
			,'bcc'			=> $bcc //$this->bcc
			,'attachments'	=> $this->attachments
			,'bounce'		=> $this->bounce
		);
		
		$smtp = $this->getSMTPconfig();
		if ($smtp)
		{
			$mailer = new Sushee_SMTPMailer($smtp);
		}
		else
		{
			$mailerclass = $GLOBALS['ServerMailSenderClass'];
			if (!empty($mailerclass) && class_exists($mailerclass))
			{
				$mailer = new $mailerclass();
			}
		}

		if ($mailer)
		{
			$response = $mailer->send_mail($email,$this->mime);
		}
		else
		{
			$response = mail($headers_array['To'], $headers_array['Subject'], $message, $headers, $additional_parameters);
		}

		return $response;
	}

	function addRecipient($recipient)
	{
		$this->recipients[] = $recipient;
	}
}

class Mail extends ModuleElement{
	function Mail($values){
		$moduleInfo = moduleInfo('mail');
		parent::ModuleElement($moduleInfo->ID,$values);
	}

	function create(){
		$this->values['ID'] = $this->getModule()->getNextID();
		return parent::create();
	}

	function generatePlainTextFromHTML($html){ // use the global function
		return generatePlainTextFromHTML($html);
	}

	function clean_string_input($input)
	{
	   $search = array(
	       '/[\x00-\x09\x0b\x0c\x0e-\x1f\x7f-\x9f]/i'    // all other non-ascii
	   );
	   $replace = array(
	       ''
	   );
	   return preg_replace($search,$replace,$input);
	}

	function decodeHeader($str){
		$str = str_replace(array("\r","\n"),'',$str);
		return utf8_To_UnicodeEntities(stripslashes($str));
	}

	function saveAttachment(&$string,$location){
		$fp = @fopen($location,'wb');
		if($fp!==false){
			fwrite($fp, $string);
			fclose($fp);
			chmod_Nectil($location);
		}
	}

	function handleAttachments($mime_part,&$values,&$attachments,$dest_dir){

		global $slash;
		if($mime_part->headers['content-id'])
			$attach_id = $mime_part->headers['content-id'];
		else
			$attach_id = 'nectil'.sizeof($attachments);
		////$this->log('attachment '.$attach_id);
		if($mime_part->ctype_parameters['name'] || $mime_part->d_parameters['name'] || $mime_part->d_parameters['filename'] || $mime_part->ctype_parameters['filename'] ){
			if($mime_part->d_parameters['filename'])
				$attach_name = $mime_part->d_parameters['filename'];
			else if($mime_part->d_parameters['name'])
				$attach_name = $mime_part->d_parameters['name'];
			else if($mime_part->ctype_parameters['filename'])
				$attach_name = $mime_part->ctype_parameters['filename'];
			else
				$attach_name = $mime_part->ctype_parameters['name'];
			$attach_name= setFilename($attach_name);
			$ext = getFileExt($attach_name);
			global $BlockedExt;

			if(in_array($ext,$BlockedExt)){
				$attach_name = getFilenameWithoutExt($attach_name).'.phps';
			}
			$start_attach_name = $attach_name;
			$filename = $dest_dir.$slash.$attach_name;
			$particle = 1;
			while(file_exists($filename)){
				$ext = getFileExt($filename);
				$simplename = getFilenameWithoutExt($start_attach_name);
				$attach_name = $simplename.$particle;
				if($ext)
					$attach_name.='.'.$ext;
				$filename = $dest_dir.$slash.$attach_name;
				$particle++;
			}
			$attachments[$attach_id]=$attach_name;
			////$this->log('saving as '.$filename);
			$this->saveAttachment($mime_part->body,$filename);
		}else if(sizeof($mime_part->parts)>0){
			foreach($mime_part->parts as $subpart)
				$this->handleAttachments($subpart,$values,$attachments,$dest_dir);
		}else{
			$attach_name = 'attachment'.date('Ymd');
			if($mime_part->ctype_primary=='image'){
				if(strlen($mime_part->ctype_secondary)<=4 && $mime_part->ctype_secondary!='php')
					$attach_name.='.'.$mime_part->ctype_secondary;
			}
			$attach_name= setFilename($attach_name);
			$attachments[$attach_id]=$attach_name;
			$filename = $dest_dir.$slash.$attach_name;
			////$this->log('saving as '.$filename);
			$this->saveAttachment($mime_part->body,$filename);
		}
	}

	function handleMimePart($mime_part,&$values,&$attachments_array,$dest_dir){
		$mime_part->ctype_primary = strtolower($mime_part->ctype_primary);
		$mime_part->ctype_secondary = strtolower($mime_part->ctype_secondary);
		////$this->log('mime part '.$mime_part->ctype_primary.'/'.$mime_part->ctype_secondary);
		if ( !($mime_part->ctype_primary=='message' && $mime_part->ctype_secondary=='rfc822') && isset($mime_part->disposition) && ($mime_part->disposition=='attachment' || $mime_part->disposition=='inline') && !(($mime_part->ctype_primary=='text' && $mime_part->ctype_secondary=='plain') || ($mime_part->ctype_primary=='text' && $mime_part->ctype_secondary=='html')) ) {
			////$this->log('attachment');
			makeDir($dest_dir);
			$this->handleAttachments($mime_part,$values,$attachments_array,$dest_dir);
		}else{

			if($mime_part->ctype_primary=='text' && $mime_part->ctype_secondary=='plain'){

				$mail_is_utf8 = false;
				$mail_is_cp1252 = false;
				$charset = strtolower($mime_part->ctype_parameters['charset']);
				if($charset=='utf-8'){
					$mail_is_utf8 = true;
				}
				if($charset=='windows-1250' || $charset=='windows-1252'){
					$mail_is_cp1252 = true;
				}
				if($mail_is_utf8){
					$plaintext = utf8_decode(utf8_To_UnicodeEntities($mime_part->body));
				}else if($mail_is_cp1252){
					$plaintext = utf8FromCP1252($mime_part->body);
					$plaintext = utf8_To_UnicodeEntities($plaintext);
				}else{
					$plaintext = iso_To_UnicodeEntities($mime_part->body);
				}
				if($mime_part->ctype_parameters['format']=='flowed'){
					$plaintext= ereg_replace(" +\r\n>* *"," ",$plaintext);
				}
				$plaintext = str_replace("\r\n","\n",$plaintext);
				$values['PlainText'].=$plaintext;
			}else if($mime_part->ctype_primary=='text' && $mime_part->ctype_secondary=='html'){
				$mail_is_utf8 = false;
				$mail_is_cp1252 = false;
				$charset = strtolower($mime_part->ctype_parameters['charset']);
				if($charset=='utf-8'){
					$mail_is_utf8 = true;
				}
				if($charset=='windows-1250' || $charset=='windows-1252'){
					$mail_is_cp1252 = true;
				}
				if($mail_is_utf8){
					$values['RichText'] = utf8_decode(utf8_To_UnicodeEntities($mime_part->body));
				}else if($mail_is_cp1252){
					$values['RichText'] = utf8_To_UnicodeEntities(utf8FromCP1252($mime_part->body));
				}else{
					$mail_is_utf8 = isUTF8($mime_part->body);
					if(!$mail_is_utf8){
						$values['RichText'] = iso_To_UnicodeEntities($mime_part->body);
					}else{
						$values['RichText']= utf8_To_UnicodeEntities(iso_To_UnicodeEntities($mime_part->body));
					}

				}
			}else if(sizeof($mime_part->parts)>0){
				foreach($mime_part->parts as $subpart)
					$this->handleMimePart($subpart,$values,$attachments_array,$dest_dir);
			}else{
				////$this->log('attachment without disposition');
				makeDir($dest_dir);
				$this->handleAttachments($mime_part,$values,$attachments_array,$dest_dir);
			}
		}
	}

	function parse(){
		global $slash;
		$reg = &new Sushee_MailsAccountRegister('');

		$accountID = $this->getField('AccountID');
		$account = &$reg->getElement($accountID);

		// ID was already assigned in a first pass
		// but set with Activity=2 to indicate it still has to be parsed
		$ID = $this->getID();

		if(!$account)
		{
			return false;
		}

		$unique_id = $this->getField('UniqueID');
		$msg = $account->getPopMessage($unique_id,true);
		$moduleInfo = $this->getModule();
		$user = new Sushee_User();
		$mailFolderPath = $slash.$moduleInfo->getName().$slash.$user->getID().$slash.date('Y-m').$slash.'received'.$slash.date('d').$slash.$ID.$slash;
		$mailFolder = new Folder($mailFolderPath);
		$mailFolder->create();
		$mailFilesFolder = $mailFolder->createDirectory('files');

		$month_indexes = array(
							"Jan"=>1 , "Feb"=>2  , "Mar"=>3  , "Apr"=>4 ,
	                        "May"=>5 , "Jun"=>6	 , "Jul"=>7	 , "Aug"=>8 ,
	                        "Sep"=>9 , "Oct"=>10 , "Nov"=>11 , "Dec"=>12);

		$params = array();
		$params['include_bodies'] = true;
		$params['decode_bodies']  = true;
		$params['decode_headers'] = true;
		$params['crlf']           = "\r\n";

		if($msg)
		{
			$values = array();

			$params['include_bodies'] = false;
			$params['decode_bodies']  = false;
			$mime_dec = new Mail_mimeDecode($msg);
			$structure = $mime_dec->decode($params);

			$headers = $structure->headers;
			$values['UniqueID'] = $unique_id;

			$recents++;

			// parsing the body now
			$mail_too_large = (strlen($msg)>26214400); // 25mb of attachments
			if(!$mail_too_large)
			{
				$params['include_bodies'] = true;
				$params['decode_bodies']  = true;
				$structure = $mime_dec->decode($params);
			}

			if(!$mail_too_large)
			{
				// if mail is too large, we will need the complete mail content afterwards to save it in a file
				unset($msg);
				$msg = '';
			}

			$body_length = strlen($structure->body);
			$mail_is_utf8 = null;
			$values['Attachments']='';

			$attachments_array = array();
			$this->handleMimePart(&$structure,$values,$attachments_array,$mailFilesFolder->getCompletePath());

			unset($structure);
			if(strlen($values['PlainText'])>256000 || $mail_too_large) // 256 Kb of text
			{
				$filename_dest = 'message.txt';
				$final_dest = $mailFolder->getCompletePath().$slash.$filename_dest;
				if($mail_too_large)
				{
					$filename_dest = 'message.eml';
					$final_dest = $dest_dir.$slash.$filename_dest;
					$this->saveAttachment($msg,$final_dest);
					unset($msg);
				}
				else
				{
					// only attachment is too large
					$this->saveAttachment($values['PlainText'],$final_dest);
					$smallpart = substr($values['PlainText'],0,512);
				}
				$simple_eol = "\r\n";
				$double_eol = $simple_eol.$simple_eol;

				$values['PlainText'] = 'WARNING: the message is too heavy to be completely rendered and has been moved in attachments ('.$filename_dest.').';
				if($small_part)
					$values['PlainText'].=$simple_eol.' See the beginning (512 chars) of the message below.';
				$values['PlainText'].= $double_eol.'Attention: le message est trop lourd pour &#234;tre affich&#233; et a &#233;t&#233; enregistr&#233; dans les pi&#232;ces jointes ('.$filename_dest.').';
				if($small_part)
					$values['PlainText'].= $simple_eol.' Vous trouverez le d&#233;but du message (512 chars) ci-dessous.';
				if($small_part)
					$values['PlainText'].= $double_eol.$smallpart.'...';
				$attachments_array['nectil'.sizeof($attachments_array)] = $filename_dest;
			}

			$values['PlainText'] = $this->clean_string_input($values['PlainText']);

			$values['Attachments'] = implode(',',$attachments_array);

			if(trim($values['PlainText'])=='' && $values['RichText']!='')
			{
				$values['PlainText'] = utf8_To_UnicodeEntities($this->generatePlainTextFromHTML($values['RichText']));
			}

			$now = $GLOBALS["sushee_today"];
			$values["CreationDate"]=$now;
			$values["ModificationDate"]=$now;
			if (isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']))
			{
				$values["CreatorID"]=$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
				$values["ModifierID"]=$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
				$values["OwnerID"]=$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
			}

			$values['Subject'] = $this->decodeHeader($headers['subject']);
			$values['Subject'] = $this->clean_string_input($values['Subject']);
			$values['AccountID'] = $account->getID();
			$values['Type'] = 'in';
			$values['To'] = $this->decodeHeader($headers['to']);
			$values['Cc'] = $this->decodeHeader($headers['cc']);
			$values['Bcc'] = $this->decodeHeader($headers['bcc']);

			if(is_array($headers['from']))
				$from = $headers['from'][0];
			else
				$from = $headers['from'];

			$values['From'] = $this->decodeHeader($from);
			$values['From'] = $this->clean_string_input($values['From']);

			if(isset($headers['x-priority']))
			{
				$priors = explode(' ',$headers['x-priority']);
				foreach($priors as $prior)
				{
					if(is_numeric($prior))
					{
						$values['Priority']=$prior;
						break;
					}
				}
			}

			$sending_date = trim($headers['date']);
			$virg_pos = strpos($sending_date,',');
			if($virg_pos!==false)
			{
				$sending_date = trim(substr($sending_date,$virg_pos+1));
			}
			$date_divide = explode(" ",$sending_date);
			$hours_divide = explode(":",$date_divide[3]);
			$timestamp = mktime($hours_divide[0],$hours_divide[1],$hours_divide[2],$month_indexes[$date_divide[1]],$date_divide[0],$date_divide[2]);

			$values['SendingDate'] = date("Y-m-d H:i:s",$timestamp);
			$values['ReceivingDate'] = $now;

			if(isset($headers['received']))
			{

				if(is_array($headers['received']))
					$received = $headers['received'][0];
				else
					$received = $headers['received'];

				$received_expl = explode(';',$received);
				if(sizeof($received_expl)>1)
				{
					$received = trim($received_expl[1]);
					$virg_pos = strpos($received,',');
					if($virg_pos!==false)
					{
						$received = trim(substr($received,$virg_pos+1));
					}
					$date_divide = explode(" ",$received);
					$hours_divide = explode(":",$date_divide[3]);
					$timestamp = mktime($hours_divide[0],$hours_divide[1],$hours_divide[2],$month_indexes[$date_divide[1]],$date_divide[0],$date_divide[2]);

					// turning into gmt
					$zone = $date_divide[4];
					$operator = substr($zone,0,1);
					$zone = substr($date_divide[4],1);
					$hours_to_gmt  = (int)(substr($zone,0,2));
					$minutes_to_gmt = (int)(substr($zone,2,4));
					if($operator=='-')
					{
						$timestamp+=$hours_to_gmt*3600;
						$timestamp+=$minutes_to_gmt*60;
					}
					else
					{
						$timestamp-=$hours_to_gmt*3600;
						$timestamp-=$minutes_to_gmt*60;
					}

					// bringing back to local time
					$timestamp+=date('Z');

					$values['ReceivingDate'] = date("Y-m-d H:i:s",$timestamp);
				}
			}

			// necessary to save html file and attachments
			$values['Folder'] = $mailFolderPath;

			if(sizeof($attachments_array)>0)
			{
				$needles = array();
				$replacments = array();
				foreach($attachments_array as $key=>$file)
				{
					if(substr($key,0,1)=='<' && substr($key,-1,1)=='>')
					{
						$key = substr($key,1,-1);
						$needles[]='cid:'.$key;
						$replacments[]='[files_url]'.$values['Folder'].$slash.'files'.$slash.$file;
					}
				}

				if(sizeof($needles)>0)
				{
					$values['RichText'] = str_replace($needles,$replacments,$values['RichText']);
				}
			}

			$values["SearchText"] = $moduleInfo->generateSearchText($values);
			$values['Junk'] = 2;
			$values['JunkDetection'] = 'none';

			// first trust SpamAssassin if used
			if(isset($headers['x-spam-level']))
			{
				$use_spam_assassin = true;
			}
			else
			{
				$use_sushee_filter = true;
			}

			if ($use_spam_assassin)
			{
				// disable sushee filter
				$use_sushee_filter = false;

				$level = strlen($headers['x-spam-level']);
				$status = $headers['x-spam-status'];
				$flag = $headers['x-spam-flag'];
				$score = $headers['x-spam-score'];

				if (strlen($flag))
				{
					if ($flag == 'NO')
					{
						$values['Junk'] = 0;
					}
					else
					{
						$values['Junk'] = 1;
					}
					$values['JunkDetection'] = 'computer';
				}
				else if (strlen($level))
				{
					if (strlen($level) >= 7)
					{
						$values['Junk'] = 1;
					}
					else
					{
						$values['Junk'] = 0;
					}
					$values['JunkDetection']='computer';
					$use_sushee_filter = false;
				}
				else if ($score)
				{
					if ($score >= 3.4)
					{
						$values['Junk'] = 1;
					}
					else
					{
						$values['Junk'] = 0;
					}
					$values['JunkDetection']='computer';
					$use_sushee_filter = false;
				}
				else
				{
					// re-enable sushee filter
					$use_sushee_filter = true;
				}

			}

			if ($use_sushee_filter)
			{
				// sushee junk filter
				if (strlen($values['PlainText'])<64000)
				{
					$classifier = new automatic_classifier();

					$scores = $classifier->classify($values['From'].$values['Subject'].$values['PlainText']);
					if ($scores['mail_spam'] > $scores['mail_notspam'] && $scores['mail_spam']>0.9 && ! $classifier->computing_problems)
					{
						$values['Junk'] = 1;
						$values['JunkDetection'] = 'computer';
					}
					else if (!$classifier->computing_problems)
					{
						$values['Junk'] = 0;
						$values['JunkDetection'] = 'computer';
					}
				}
				// assuming empty messages are spam
				if (strlen(trim($values['PlainText']))==0)
				{
					$values['Junk'] = 1;
					$values['JunkDetection'] = 'computer';
				}
			}

			if($values['RichText'])
			{
				// saving html in a separate file to avoid overloading the database
				$richtextFile = $mailFolder->getChild('mail.html');
				$richtextFile->save($values['RichText']);

				// to indicate there is an HTML file
				$values['HTML'] = 1;
			}

			// remove RichText from SQL command in update()
			unset($values['RichText']);

			// activate the mail | from 2 -> 1
			$values['Activity'] = 1;

			$this->setFields($values);
			$this->update();

			// cleaning memory of the possibly heavy fields
			unset($values['PlainText']);
			unset($values['SearchText']);

			return true;
		}
		else
		{
			$this->log('problem getting back a mail');
		}

		return false;
	}
}

class MailsAccount extends ModuleElement{

	var $pop3 = false;
	var $lastUnread = false;
	var $lastRegistered = false;
	var $numMsg = false;
	var $retreiving_mode = MAIL_RETREIVING_MODE_ASC;
	var $lastMail_index = false;
	var $msgToDelete = array();

	function MailsAccount($values){
		$moduleInfo = moduleInfo('mailsaccount');
		parent::ModuleElement($moduleInfo->ID,$values);
	}

	function setRetreivingMode($mode){
		if($mode==MAIL_RETREIVING_MODE_ASC || $mode==MAIL_RETREIVING_MODE_DESC){
			$this->mode = $mode;
			return true;
		}else{
			return false;
		}
	}

	function &connect(){
		$pop3 = &new Net_POP3();
		$port = $this->getField('Port');
		if(!$port){
			$port = 110;
		}
		$host = $this->getField('Host');
		if($port=='995'){
			$host='ssl://'.$host;
		}
		if($pop3->connect(
				$host,
				$port
				)
			){
			$this->pop3 = &$pop3;
			return $this->pop3;
		}else
			return false;
	}

	function &getPop3(){
		if(/*getServerOS()=='windows' ||*/ !$this->pop3){
			if($this->connect()){
				if($this->login()){
					/*$reg = new MailsAccountRegister();
					if(!$reg->exists($this->getID()))
						$reg->add($this->getID(),$this);*/
				}
			}
		}
		return $this->pop3;
	}

	function login(){
		$pop3 = &$this->pop3;
		if($pop3){
			$login = $this->getField('Login');
			$password = $this->getField('Password');
			$user = new NectilUser();
			$password = $this->decrypt($password,$user->getSessionPassword(),$this->getField('Encryption'));
			$password = trim($password);
			$login_res = $pop3->login($login,$password);
			$reg = new Sushee_MailsAccountRegister('');
			if($login_res && !$reg->exists($this->getID()))
				$reg->add($this->getID(),$this);
			return $login_res;
		}else
			return false;
	}

	function disconnect(){
		//$this->log('disconnect');
		$pop3 = &$this->getPop3();
		if($pop3){
			foreach($this->msgToDelete as $i){
				$pop3->deleteMsg($i);
			}
			$pop3->disconnect();
		}
	}

	function encrypt($string,$key,$encryption){
		$cipher = new Crypt();
		$cipher->setKey($key);
		$cipher->setAlgo($encryption);
		return $cipher->execute($string);
	}
	function decrypt($string,$key,$encryption){
		$cipher = new Decrypt();
		$cipher->setKey($key);
		$cipher->setAlgo($encryption);
		return $cipher->execute($string);
	}

	function getPopMessage($uidl,$delete=false){
		$pop3 = &$this->getPop3();
		if($pop3){
			$num = $this->getNumMsg();
			if($num){
				$this->lastMail_index = false;
				$borne1 = 1;//$this->getLastUnread();
				$borne_top = $num;
				for($i=$borne_top;$i>=$borne1;$i--){
					$msg_uidl = $pop3->getListing($i);
					if($msg_uidl['uidl']==$uidl){
						//$this->log('found in looping in all mails '.$this->lastMail_index);
						$this->lastMail_index = $i;
						break;
					}else{
						//$this->log('uidl is '.$msg_uidl['uidl']);
					}
				}
				$this->log('mail is on index'.$this->lastMail_index);
				if($this->lastMail_index){
					$msg = $pop3->getMsg($this->lastMail_index);
					if($msg && $delete && $this->getField('LeaveOnServer')==0){
						$this->log('deleting msg '.$this->lastMail_index);
						$this->deleteMsg($this->lastMail_index);
					}
					return $msg;
				}

			}
		}
		return false;
	}

	function deleteMsg($i){
		$this->msgToDelete[]=$i;
	}

	function getNumMsg(){
		if($this->numMsg===false){
			$pop3 = &$this->getPop3();
			if($pop3){
				$this->numMsg = $pop3->numMsg();
				//return $pop3->numMsg();
			}
		}
		return $this->numMsg;
	}

	function getLastUnread(){
		//if($this->lastUnread===false){
			$this->lastUnread = $this->_getLast(array(0,1));
		//}

		return $this->lastUnread;
	}

	function getLastRegistered(){
		//if($this->lastRegistered===false){
			$this->lastRegistered = $this->_getLast(array(0,1,2));
		//}

		return $this->lastRegistered;
	}


	function _getLast($activity_values=false){
		if ($activity_values===false)
		{
			$activity_values = array(0,1);
		}

		$pop3 = &$this->getPop3();
		$db_conn = db_connect();
		$moduleInfo = moduleInfo('mail');
		if($pop3)
		{
			$num = $this->getNumMsg();
			if($num)
			{
				$found_last_unread = false;
				$borne1 = 1;
				$borne2 = $num;
				$middle = floor($borne1+($borne2-$borne1)/2);
				$loops = 1;

				// first testing last mail : if it's unread there is no new mail
				$uidl = $pop3->getListing($borne2);
				$unique_id = $uidl['uidl'];
				$last_sql = 'SELECT `UniqueID` FROM `'.$moduleInfo->getTableName().'` WHERE `AccountID`=\''.$this->getID().'\' AND `Type`="in" AND `UniqueID`="'.$unique_id.'" AND `Activity` IN ('.implode(',',$activity_values).') LIMIT 1';
				//$this->log($last_sql);
				$read = $db_conn->getRow($last_sql);
				$reading_status[$unique_id]=$read;
				if($read)
				{
					$found_last_unread = true;
					$borne1 = $num+1;
					$loops = 1;
				}

				while(!$found_last_unread)
				{
					$uidl = $pop3->getListing($middle);
					$unique_id = $uidl['uidl'];

					if(isset($reading_status[$unique_id]))
					{
						$read = $reading_status[$unique_id];
					}
					else
					{
						$sql = 'SELECT `UniqueID` FROM `'.$moduleInfo->getTableName().'` WHERE `AccountID`=\''.$this->getID().'\' AND `Type`="in" AND `UniqueID`="'.$unique_id.'" AND `Activity` IN ('.implode(',',$activity_values).')  LIMIT 1';
						$read = $db_conn->getRow($sql);
					}

					if($read)
						$borne1=$middle;
					else
						$borne2=$middle;

					$found_last_unread = ($borne1==$borne2 || $borne2==$borne1+1);
					$middle = floor($borne1+($borne2-$borne1)/2);

					$loops++;
					if($middle==$num)
						break;
					if($loops>$num)
						break;
				}
			}
		}
		return $borne1;
	}

	function prepareEnteringMails()
	{
		$totalCount = 0;
		$db_conn = db_connect();
		$pop3 = &$this->getPop3();
		$moduleInfo = moduleInfo('mail');
		$user = new NectilUser();
		$num = $this->getNumMsg();
		if($num)
		{
			$borne1 = $this->getLastRegistered();
			$borne_top = $num;
			for($i=$borne1;$i<=$borne_top;$i++)
			{
				$new_msg = true;
				$uidl = $pop3->getListing($i);

				if(!$uidl['uidl'])
				{
					$new_msg = FALSE;
				}
				else
				{
					$unique_id = $uidl['uidl'];
					if (isset($reading_status[$unique_id]))
					{
						$already_handled = $reading_status[$unique_id];
					}
					else
					{
						$sql = 'SELECT `UniqueID` FROM `'.$moduleInfo->getTableName().'` WHERE `AccountID`=\''.$this->getID().'\' AND `Type`="in" AND `UniqueID`="'.$unique_id.'" LIMIT 1';
						sql_log($sql);
						$already_handled = $db_conn->getRow($sql);
					}

					if ($already_handled)
					{
						$new_msg = FALSE;
					}
				}

				if($new_msg)
				{
					$values = array();
					$values['UniqueID'] = $unique_id;
					$values['Type'] = 'in';
					$values['Status'] = 'unread';
					$values['Activity'] = 2;
					$values['AccountID'] = $this->getID();
					$values['OwnerID'] = $user->getID();

					$mail = new Mail($values);
					$mail->create();
					$totalCount++;
				}
				header('X-pmaPing: Pong');
			}
		}
		return $totalCount;
	}
}