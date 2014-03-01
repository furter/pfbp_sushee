<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/cron.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/commandline.class.php");
require_once(dirname(__FILE__)."/../common/nql.class.php");
require_once(dirname(__FILE__)."/../common/url.class.php");
require_once(dirname(__FILE__)."/../common/date.class.php");
require_once(dirname(__FILE__)."/../common/mail.class.php");
require_once(dirname(__FILE__)."/../common/sushee.class.php");
require_once(dirname(__FILE__)."/../common/log.class.php");

class Cronlog extends ModuleElement
{
	function Cronlog($values='')
	{
		$moduleInfo = moduleInfo('cronlog');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
}

class Cron extends ModuleElement
{
	function Cron($values)
	{
		$moduleInfo = moduleInfo('cron');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
	
	function logCron($str)
	{
		$file = new LogFile('cron.log');
		$file->setMaxSize(false);
		$file->log( new SusheeLog($str,false) );	
	}
	
	function launchInBackground()
	{
		$cmd = new Sushee_BackgroundProcess('cron'.$this->getID());
		$cmd->setCommand(Sushee_instance::getConfigValue('phpExecutable').' "'.dirname(__FILE__).'/../private/launch_cron.php" '.$this->getID());
		$cmd->execute();

		// saving process ID
		$pid = $cmd->getPID();

		// not used at the moment, may be implemented later
		//$this->setField('PID',$pid);
		//$this->update();

		$this->logCron('Cron ' . $this->getID() . ' launched in background at ' . date('Y-m-d H:i:s') . ' with pID ' . $pid . ' and command ' . $cmd->getCommand());

		return $pid;
	}
	
	function execute()
	{
		$this->loadFields();
		$type = $this->getField('Type');
		
		// --- ici créer un cronlog ---
		$cronlog = new Cronlog();
		$cronlog->setField('Status','running');
		$cronlog->setField('CronID',$this->getField('ID'));
		$cronlog->create();
		
		$this->setField('Status','running');
		$this->update();

		$this->logCron('Cron ' . $this->getID() . ' executed at ' . date('Y-m-d H:i:s'));

		switch($type)
		{
			case 'nql':
				$response = $this->executeNQL();
				break;
			case 'url':
				$response = $this->executeURL();
				if($response===false)
				{
					$response = '<ERROR>URL '.$this->getField('Command').' inacessible</ERROR>';
				}
				break;
			case 'shell':
				$response = $this->executeShell();
				break;
			case 'phpclass':
				$response = $this->executePHPClassMethod();
		}

		unset($GLOBALS["conn"]); // force reconnect, because MySQL connection might have expired

		// --- ici récupérer le cronlog et le mettre à jour ---

		// reloading fields because Status could have been modified
		$cronlog->loadFields();
		$this->loadFields();

		if($this->getField('Status')=='running' || $this->getField('Status')=='timeout')
		{
			$this->setField('Status','pending');
		}

		$cronlog->setField('ModificationDate',date('Y-m-d H:i:s'));
		$cronlog->setField('Status','finished');

		$this->update();
		$cronlog->update();
		
		$this->logCron('Cron ' . $this->getID() . ' finished at ' . date('Y-m-d H:i:s'));

		// first check is response is xml-friendly
		$xml = new DOMDocument();
		if (!$xml->loadXML($response))
		{
			$response = encode_to_xml($response);
		}

		// in case response couldnot be saved because too long, we do it apart 
		$cronlog->setField('Response',$response);
		$cronlog->update();

		$this->callBack();
	}

	function callBack()
	{
		if($this->getField('Callback')!='')
		{
			$url_handler = new URL($this->getField('Callback'));
			$url_handler->execute();
		}
	}

	function setCronUser()
	{
		$ID = $this->getID();

		// creating a system user, that will allow to retrieve which cron has executed the commands
		$sql = 'SELECT `ID` FROM `contacts` WHERE `FirstName` = "'.$ID.'" AND `LastName` = "Cron" AND `Activity` = 3 LIMIT 0,1';
		$db_conn = db_connect();
		$system_user = $db_conn->getRow($sql);
		if($system_user)
		{
			// user already exists
			$system_userID = $system_user['ID'];
		}
		else
		{
			// creating the user
			$sql = 'INSERT INTO `contacts`(`Activity`,`Denomination`,`FirstName`,`LastName`) VALUES(3,"Cron '.$ID.'","'.$ID.'","Cron")';
			$db_conn->execute($sql);
			$system_userID = $db_conn->Insert_Id();
		}
		$user = new Sushee_User();
		$user->setID($system_userID);
	}
	
	function executeNQL()
	{
		$this->setCronUser();

		$NQL = new NQL();
		$NQL->includeUnpublished();
		$NQL->addCommand($this->getField('Command'));
		return $NQL->execute();
	}

	function executeURL()
	{
		$command = decode_from_xml($this->getField('Command'));
		// if url starts with slash and not http, it must be an absolute path to our own version of officity, we compose the complete url
		if($command[0]=='/')
		{
			$command = $GLOBALS['nectil_url'].$command;
		}
		$url_handler = new URL($command);
		return $url_handler->execute();
	}

	function executeShell()
	{
		$cmd = new commandLine($this->getField('Command'));
		return $cmd->execute();
	}

	function executePHPClassMethod()
	{
		$this->setCronUser();

		// $this->logFunction('executePHPClassMethod');
		$classfile = $this->getField('ClassFile');
		if($classfile)
		{
			$classfile = new KernelFile($classfile);
			if($classfile->exists()){
				$classname = $this->getField('ClassName');
				// checking we know which class we have to instantiate
				if(!$classname)
				{
					return 'Classname is empty in cron';
				}
	
				if(class_exists($classname))
				{
					return 'A class with the same name (`'.$classname.'`) already exists in Sushee, please use another classname';
				}
	
				// including the class file
				include_once($classfile->getCompletePath());

				if(!class_exists($classname))
				{
					return 'Class `'.$classname.'` is not defined in the file `'.$classfile->getCompletePath().'`';
				}
				$new_object = new $classname();
				$method = $this->getField('Method');
				if(!$method)
				{
					return 'Method is empty in cron';
				}

				if(!method_exists($new_object,$method))
				{
					return 'Method `'.$method.'` does not exist in the class';
				}
				return $new_object->$method();
			}
			else
			{
				return 'ClassFile `'.$classfile->getCompletePath().'` does not exist';
			}
		}
		else
		{
			return 'ClassFile is empty';
		}
	}

	function isRunning()
	{
		$status = $this->getField('Status');
		if($status=='running')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function getLastCronlog()
	{
		$moduleInfo = moduleInfo('cronlog');
		$sql = 'SELECT * FROM `'.$moduleInfo->tableName.'` WHERE CronID = \''.$this->getID().'\' ORDER BY `ID` DESC LIMIT 0,1';
		$db_conn = db_connect();
		
		$row = $db_conn->getRow($sql);
		if($row)
		{
			return new Cronlog($row);
		}
		return false;
	}

	function isInTimeOut()
	{
		$timeout = $this->getField('TimeOut');
		if($timeout)
		{
			$cronlog = $this->getLastCronlog();
			if($cronlog)
			{
				$startDate = new Date($cronlog->getField('CreationDate'));
				$endDate = new Date(date('Y-m-d H:i:s'));
				$diff = $endDate->getTime() - $startDate->getTime();
				$diff = $diff / 60; // because timeout is expressed in minutes (seconds have no sense, batch are launched every minute)
				if($diff > $timeout)
				{
					return true;
				}
			}
		}
		return false;
	}
	
	function warnOfTimeOut()
	{
		// is there someone in charge ?
		$email = $this->getField('TimeOutEmail');
		if($email)
		{
			// sending an email to the person in charge
			$serverMail = new ServerMail();
			$sushee = new Sushee_Instance();
			$subject = 'Cron:'.$this->getID().' aborted (timeout) on server `'.$sushee->getUrl().'`';
			$text = $subject;

			$serverMail->setSender('cron@sushee.com');
			$serverMail->setSubject($subject);
			$serverMail->setText($text);
			$serverMail->addRecipient($email);

			$serverMail->execute();
			return true;
		}
		return false;
	}
}

class CronQueue extends SusheeObject
{
	function CronQueue(){}

	function execute()
	{
		$db_conn = db_connect();
		$moduleInfo = moduleInfo('cron');
		
		// 1. checking timeouts

		$sql = 'SELECT * FROM `'.$moduleInfo->tableName.'` WHERE `Status` = "running" ORDER BY `ID`';
		$rs = $db_conn->Execute($sql);
		if($rs)
		{
			while($row = $rs->FetchRow())
			{
				$cron = new Cron($row);

				// checking timeout is not over
				if($cron->isInTimeOut())
				{
					$cron->warnOfTimeOut();
					$cron->setField('Status','timeout');
				}
				$cron->update();
			}
		}

		// executing crons in the queue
		$sql = 'SELECT * FROM `'.$moduleInfo->tableName.'` WHERE (`Minute` LIKE "%'.date('i').',%" OR `Minute`="") AND (`Hour` LIKE "%'.date('H').',%" OR `Hour`="") AND (`Day` LIKE "%'.date('d').',%" OR `Day`="") AND (`Month` LIKE "%'.date('m').',%" OR `Month`="")  AND (`Weekday` LIKE "%'.date('N').',%" OR `Weekday`="") AND (`Command`!="" OR `ClassFile`!="") AND `Activity`=1 AND `Status` = "pending" ORDER BY `ID`';
		$rs = $db_conn->Execute($sql);
		if($rs)
		{
			while($row = $rs->FetchRow())
			{
				$cron = new Cron($row);
				if(getServerOS()=='windows')
				{
					// on windows background processing is not yet implemented
					$cron->execute();
				} 
				else
				{
					$cron->launchInBackground();
				}
			}
		}
	}
}