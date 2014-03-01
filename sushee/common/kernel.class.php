<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/kernel.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");

class NectilKernel extends SusheeObject{
	var $installLoginMD5 = false;
	var $installPasswordMD5 = false;
	
	function NectilKernel(){
		
	}
	
	function getResidents(){
		$db_conn = db_connect();
		$sql = 'SELECT `ID`,`URL`,`DbName` FROM `residents` WHERE (`Activity`=1 AND  `ID` != 1 AND `IsTemplate`!=1 AND (`ExpirationDate`="0000-00-00" OR `ExpirationDate` > "'.$GLOBALS["sushee_today"].'" OR `ExpirationDate`="0000-01-01")) ';
		$residents = array();
		$rs = $db_conn->Execute($sql);
		if($rs){
			while($row = $rs->FetchRow()){
				/*if($row['ID']==1){
					$row['URL']='http://'.$GLOBALS["NectilMasterURL"];
				}*/
				$residents[]= new Resident($row);
			}
		}
		return $residents;
	}
	
	function launchResidentsBatches(){
		$residents = $this->getResidents();
		foreach($residents as $resident){
			$resident->launchBatches();
		}
	}
	
	function launchResidentsCrons(){
		$residents = $this->getResidents();
		foreach($residents as $resident){
			$resident->launchCrons();
		}
	}
	
	function loadInstallDatas(){
		if($this->installLoginMD5==false){
			if(isset($GLOBALS["NectilInstallLogin"])){
				$this->installLoginMD5 = md5($GLOBALS["NectilInstallLogin"]);
				$this->installPasswordMD5 = md5($GLOBALS["NectilInstallPassword"]);
			}else{
				$updatePassFile = new KernelFile('.updatepass');
				$updatePassStr = $updatePassFile->toString();
				$updatePassArray = explode(':',$updatePassStr);
				$this->installLoginMD5 = $updatePassArray[0];
				$this->installPasswordMD5 = str_replace(array("\r","\n"),'',$updatePassArray[1]);
			}
		}
	}
	
	function getInstallLoginMD5(){
		$this->loadInstallDatas();
		return $this->installLoginMD5;
	}
	
	function getInstallPasswordMD5(){
		$this->loadInstallDatas();
		return $this->installPasswordMD5;
	}
}
?>