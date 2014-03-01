<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createApplication.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/namespace.class.php');
require_once(dirname(__FILE__).'/../common/nectil_publisher.class.php');

class createApplication extends NQLOperation{
	
	var $denomination;
	var $publisher;
	
	function parse(){
		$denomination = $this->firstNode->valueOf('INFO/DENOMINATION');
		if(!$denomination){
			$this->setError('No name was provided for the application to be created');
			return false;
		}else{
			$this->denomination = $denomination;
			
		}
		
		$publisherNode = $this->firstNode->getElement('PUBLISHER');
		if(!$publisherNode){
			$this->setError('No publisher was provided for the application to be created');
			return false;
		}
		$publisherLogin = $publisherNode->valueOf('LOGIN');
		$publisherPassword = $publisherNode->valueOf('PASSWORD');
		if(!$publisherLogin || !$publisherPassword){
			$this->setError('Login or password for publisher was not set');
			return false;
		}
		
		
		$publisher = new NectilPublisher();
		$res = $publisher->authenticate($publisherLogin,$publisherPassword);
		if(!$res){
			$this->setError('This publisher is not referenced on officity.com');
			return false;
		}
		
		$this->publisher = $publisher;
		$namespace = $publisher->getNamespace();
		if(substr($this->denomination,0,strlen($namespace->getName())+1)==$namespace->getName().':'){
			$this->denomination = substr($this->denomination,strlen($namespace->getName())+1);
		}
		$applicationName = $namespace->getName().':'.$this->denomination;
		// checking there is no application already with this name
		$sql = 'SELECT `ID` FROM `applications` WHERE `Denomination`="'.encodeQuote($applicationName).'";';
		$db_conn = db_connect();
		sql_log($sql);
		$row = $db_conn->getRow($sql);
		if($row){
			$this->setError('There is already an application with this denomination in the same namespace');
			return false;
		}
		
		return true;
	}
	
	function operate(){
		$namespace = $this->publisher->getNamespace();
		
		$namespace->register();
		$applicationName = $namespace->getName().':'.$this->denomination;
		
		// taking an ID bigger than 1024, because smaller values are reserved for Officity native applications
		$sql = 'SELECT `ID` FROM `applications` WHERE `ID` > 1024 ORDER BY `ID` DESC';
		sql_log($sql);
		$db_conn = db_connect();
		$row = $db_conn->getRow($sql);
		if($row){
			$nextID = $row['ID'] + 1;
		}else{
			$nextID = 1024;
		}

		$sql = 'INSERT INTO `applications`(`ID`,`Denomination`,`URL`,`Dockable`) VALUES(\''.$nextID.'\',"'.$applicationName.'","/apps/'.$namespace->getName().'/'.$this->denomination.'/",\'0\');';
		sql_log($sql);
		$db_conn->Execute($sql);
		
		include_once dirname(__FILE__)."/../private/updateApplication.inc.php";
		$nqlOp = new updateApplication($this->getName(),$this->getOperationnode());
		$nqlOp->execute();
		
		$this->setSuccess('Application succesfully created');
		return true;
	}
}

?>