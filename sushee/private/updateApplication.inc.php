<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateApplication.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/namespace.class.php');
require_once(dirname(__FILE__).'/../common/nectil_publisher.class.php');

class updateApplication extends NQLOperation{
	
	var $denomination;
	var $publisher = false;
	
	function setPublisher($publisher){
		$this->publisher = $publisher;
	}
	
	function getPublisher(){
		return $this->publisher;
	}
	
	function parse(){
		$denomination = $this->firstNode->valueOf('INFO/DENOMINATION');
		if(!$denomination){
			$this->setError('No name was provided for the application to update');
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
		if(!$row){
			$this->setError('There is no application with this denomination');
			return false;
		}
		$this->ID = $row['ID'];
		
		return true;
	}
	
	function getID(){
		return $this->ID;
	}
	
	function operate(){
		$namespace = $this->publisher->getNamespace();
		
		$namespace->register();
		$applicationName = $namespace->getName().':'.$this->denomination;
		$db_conn = db_connect();
		if($this->firstNode->getElement('INFO/DOCKABLE')){
			$dockable = $this->firstNode->valueOf('INFO/DOCKABLE');
			$sql = 'UPDATE `applications` SET `Dockable` = \''.$dockable.'\' WHERE `ID`=\''.$this->getID().'\';';
			sql_log($sql);
			$db_conn->Execute($sql);
		}
		if($this->firstNode->getElement('INFO/ICON')){
			$icon = $this->firstNode->valueOf('INFO/ICON');
			$sql = 'UPDATE `applications` SET `Icon` = \''.$icon.'\' WHERE `ID`=\''.$this->getID().'\';';
			sql_log($sql);
			$db_conn->Execute($sql);
		}
		
		$this->setSuccess('Application succesfully updated');
		return true;
	}
}

?>