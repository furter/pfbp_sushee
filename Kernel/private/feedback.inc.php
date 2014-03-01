<?php

require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/mail.class.php");
require_once(dirname(__FILE__)."/../common/sushee.class.php");
require_once(dirname(__FILE__)."/../common/nqlOperation.class.php");

class Sushee_createFeedback extends NQLOperation
{
	var $ID = false;
	var $values = array();
	
	function parse()
	{
		$values = array();
		$user = new NectilUser();
		$request = $user->getSushee_Request();
		
		$values['UserLongName'] = $user->getField("FirstName")." ".$user->getField("LastName")." ".$user->getField("Email1");
		$values['Resident'] = $request->getResidentName();
		$values['CreationDate']=$request->getDateSQL();
		$values['UserAgent']=$user->getUserAgent();
		$values['IP']=$user->getIP();
		$values['provider']=$user->getProvider();
		
		$values['user'] = decode_from_XML($this->operationNode->valueOf("USER[1]"));
		$values['application'] = decode_from_XML($this->operationNode->valueOf("APPLICATION[1]"));
		$values['type'] = decode_from_XML($this->operationNode->valueOf("TYPE[1]"));
		$values['description'] = decode_from_XML($this->operationNode->valueOf("DESCRIPTION[1]"));
		$values['logs'] = decode_from_XML($this->operationNode->valueOf("LOGS[1]"));
		$values['FlashVersion']=decode_from_XML($this->operationNode->valueOf("FLASHVERSION[1]"));
		
		$this->setValues($values);
		return true;
	}

	function setValues($values)
	{
		$this->values = $values;
	}

	function getValues()
	{
		return $this->values;
	}

	function operate()
	{
		// feedback datas
		$feedback = $this->getValues();
		
		// user datas
		$user = new Sushee_User();
		$email = $user->getField('Email1');
		$username = $user->getField('FirstName')." ".$user->getField('LastName');
		$sushee = new Sushee_Instance();
		
		$message="Client: ".$sushee->getUrl()."\n";
		$message.="User: ".$username." ".$email."\n";
		$message.= "Application : ".$feedback['application']."\n"."Report type : ".$feedback['type']."\n";
		$message.= "UserAgent : ".$feedback['UserAgent']."\n";
		$message.="---------------------------------------------------\n";
		$message.=$feedback['description'];
		$message.="\n\n---------------------------------------------------\n\nFlash version :".$feedback['FlashVersion'];

		$mail = new ServerMail();
		$mail->setSubject("Feedback from ".$username);
		$mail->setText($message);
		$mail->setSender($sushee->getAdminEmail());

		if($sushee->getAdminEmail())
		{
			$recipient = $sushee->getAdminEmail();
		}
		else
		{
			$recipient = 'support@officity.com';
		}

		$mail->addRecipient($recipient);
		$mail->execute();
		$this->setSuccess("Your feedback was transmitted to the Sushee administrator");
		return true;
	}

	function getID()
	{
		return $this->ID;
	}
}