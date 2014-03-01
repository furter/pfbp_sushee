<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/batch.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
require_once(dirname(__FILE__)."/../common/commandline.class.php");
require_once(dirname(__FILE__)."/../common/nql.class.php");
require_once(dirname(__FILE__)."/../common/url.class.php");
require_once(dirname(__FILE__)."/../common/date.class.php");
require_once(dirname(__FILE__)."/../common/mail.class.php");
require_once(dirname(__FILE__)."/../common/sushee.class.php");

class Batch extends ModuleElement{
	function Batch($values){
		$moduleInfo = moduleInfo('batch');
		parent::ModuleElement($moduleInfo->ID,$values);
	}
	
	function launchInBackground(){
		$cmd = new Sushee_BackgroundProcess('batch'.$this->getID());
		$cmd->setCommand(Sushee_instance::getConfigValue('phpExecutable').' "'.dirname(__FILE__).'/../private/launch_batch.php" '.$this->getID());
		debug_log($cmd->getCommand());
		$cmd->execute();
		
		// saving process ID
		$pid = $cmd->getPID();
		// not used at the moment, may be implemented later
		/*$this->setField('PID',$pid);
		$this->update();*/
		
		return $pid;
	}

	function execute(){
		$this->loadFields();
		$type = $this->getField('Type');
		$this->setField('Status','running');
		$this->setField('Start',date('Y-m-d H:i:s'));
		$this->update();
		switch($type){
			case 'nql':
				$response = $this->executeNQL();
				break;
			case 'url':
				$response = $this->executeURL();
				if($response===false){
					$url_inaccessible = true;
					$response = '<ERROR>url inacessible</ERROR>';
				}
				break;
			case 'shell':
				$response = $this->executeShell();
				break;
		}
		unset($GLOBALS["conn"]); // force reconnect, because MySQL connection might have expired
		
		$this->loadFields(); // reloading fields because Status could have been modified
		if($this->getField('Status')!='timeout'){
			$this->setField('Status','finished');
		}
		
		$this->setField('End',date('Y-m-d H:i:s'));
		$this->update();

		// first check is response is xml-friendly
		$xml = new DOMDocument();
		if (!$xml->loadXML($response)) {
			$response = encode_to_xml($response);
		}

		// in case response couldnot be saved because too long, we do it apart 
		$this->setField('Response',$response);
		$this->update();
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
	
	function executeNQL()
	{
		$NQL = new NQL(false);
		$NQL->includeUnpublished();
		$NQL->addCommand($this->getField('Command'));
		return $NQL->execute();
	}
	
	function executeURL()
	{
		$url_handler = new URL(decode_from_xml($this->getField('Command')));
		return $url_handler->execute();
	}
	
	function executeShell()
	{
		$cmd = new commandLine($this->getField('Command'));
		return $cmd->execute();
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

	function isInTimeOut(){
		$timeout = $this->getField('TimeOut');
		if($timeout){
			$startDate = new Date($this->getField('Start'));
			$endDate = new Date(date('Y-m-d H:i:s'));
			$diff = $endDate->getTime() - $startDate->getTime();
			$diff = $diff / 60; // because timeout is expressed in minutes (seconds have no sense, batch are launched every minute)
			if($diff > $timeout){
				debug_log(' diff is '.$diff);
				return true;
			}
		}
		return false;
	}
	
	function warnOfTimeOut(){
		// is there someone in charge ?
		$email = $this->getField('TimeOutEmail');
		if($email){
			// sending an email to the person in charge
			$serverMail = new ServerMail();
			$sushee = new Sushee_Instance();
			$subject = 'Batch:'.$this->getID().' aborted (timeout) on server `'.$sushee->getUrl().'`';
			$text = $subject.' . Skipping and launching next batch in one minute.';
			
			$serverMail->setSender('batch@sushee.com');
			$serverMail->setSubject($subject);
			$serverMail->setText($text);
			$serverMail->addRecipient($email);

			$serverMail->execute();
			return true;
		}
		return false;
	}
}

class BatchQueue extends SusheeObject{
	function BatchQueue(){
	}
	
	function getNextBatch(){
		$db_conn = db_connect();
		$moduleInfo = moduleInfo('batch');
		$sql = 'SELECT * FROM `'.$moduleInfo->tableName.'` WHERE `WishedStart` <= "'.date('Y-m-d H:i:s').'" AND `Status` IN ("pending","running") AND `Command`!="" AND `Activity`=1 ORDER BY `WishedStart` , `ID` LIMIT 0,1';
		//$this->log($sql);
		$next_batch = $db_conn->getRow($sql);
		if(!$next_batch){
			return false;
		}
		return new Batch($next_batch);
	}
	
	function execute(){
		$batch = $this->getNextBatch();
		if(!$batch){
			return false;
		}
		if($batch->isRunning()){
			// checking timeout is not over 
			if($batch->isInTimeOut()){
				$batch->warnOfTimeOut();
				$batch->setField('Status','timeout');
			}
			$batch->setField('End',date('Y-m-d H:i:s'));
			$batch->update();
		}else{
			if(getServerOS()=='windows') // on windows background processing is not yet implemented
				$batch->execute();
			else
				$batch->launchInBackground();
		}
		return true;
	}
}

?>