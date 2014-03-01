<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/nectil_user.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
include_once(dirname(__FILE__)."/../common/phpSniff.class.php");


class NectilUser extends SusheeObject{

	var $keyring = false;
	var $contactElt = false;
	
	function NectilUser(){
		
	}

	function hasModule($module){
		if($module->getActionSecurity("SEARCH")!==false){
			return true;
		}else{
			return false;
		}
	}

	function hasApplication($application){
		if(!is_object($application)){
			return false;
		}

		$db_conn = db_connect();
		$applicationID = $application->getID();
		if(!$applicationID){
			return false;
		}
		$sql = 'SELECT perm. * FROM `applicationkeys` AS perm, `dependencies` AS ksetUsers, `dependencies` AS ksetKeys WHERE ksetUsers.`DependencyTypeID` =3 AND ksetKeys.`DependencyTypeID` =5 AND ksetUsers.`TargetID` =\''.$this->getID().'\' AND ksetKeys.`OriginID` = ksetUsers.`OriginID` AND ksetKeys.`TargetID` = perm.`ID` AND perm.`ApplicationToID` =\''.$applicationID.'\';';
		sql_log($sql);
		$count_sql = "SELECT COUNT(*) AS ct FROM `applicationkeys` WHERE `ApplicationToID`='".$applicationID."';";
		sql_log($count_sql);
		$count_row = $db_conn->GetRow($count_sql);
		
		$security_row = $db_conn->GetRow($sql);
		
		$keyring = $this->getKeyring();
		if(!$keyring)
			return false;
		$keyringID = $keyring->getID();
		if ($this->getID() && ($security_row || ($count_row['ct']=='0' && $keyringID==2)) )
			return true;
		return false;
	}
	
	function getApplicationKey($application){
		$db_conn = db_connect();
		$applicationID = $application->getID();
		if(!$applicationID){
			return false;
		}
		$sql = 'SELECT perm. * FROM `applicationkeys` AS perm, `dependencies` AS ksetUsers, `dependencies` AS ksetKeys WHERE ksetUsers.`DependencyTypeID` =3 AND ksetKeys.`DependencyTypeID` =5 AND ksetUsers.`TargetID` =\''.$this->getID().'\' AND ksetKeys.`OriginID` = ksetUsers.`OriginID` AND ksetKeys.`TargetID` = perm.`ID` AND perm.`ApplicationToID` =\''.$applicationID.'\';';
		sql_log($sql);
		$security_row = $db_conn->GetRow($sql);
		if($security_row){
			$key = new ApplicationKey($security_row);
			return $key;
		}
		return false;
	}

	function getKeyring(){
		if($this->keyring===false){
			$db_conn = db_connect();
			$keyring_sql = 'SELECT `OriginID` FROM `dependencies` WHERE `TargetID`=\''.$this->getID().'\' AND `DependencyTypeID`=\'3\'';
			sql_log($keyring_sql);
			$keyring_row = $db_conn->GetRow($keyring_sql);
			if(!$keyring_row){
				return false;
			}
			$keyringID = $keyring_row['OriginID'];
			$this->keyring = new Keyring($keyringID);
		}
		return $this->keyring;
	}

	function setID($ID){
		session_start();
		$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'] = $ID;
		session_write_close();
		return $_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
	}

	function isAuthentified(){
		return isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']);
	}

	function isAuthenticated(){
		return $this->isAuthentified();
	}

	static function getID(){
		if (isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'])) {
			return $_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
		} else {
			return 0;
		}
	}

	function setSessionPassword($password){
		session_start();
		$_SESSION[$GLOBALS["nectil_url"]]['password'] = $new_crypt_password = sha1($password);
		session_write_close();
		return $new_crypt_password;
	}

	function getSessionPassword(){
		if(!isset($_SESSION[$GLOBALS["nectil_url"]]['password']))
			return false;
		return $_SESSION[$GLOBALS["nectil_url"]]['password'];
	}

	function loadContactElement(){
		if($this->contactElt==false){
			$this->contactElt = new Contact($this->getID());
			$this->contactElt->loadFields();
		}
	}

	function getField($name){
		$this->loadContactElement();
		if($this->contactElt){
			return $this->contactElt->getField($name);
		}else
			return false;
	}
	
	function getIP(){
		return $_SERVER['REMOTE_ADDR'];
	}
	
	function getCountryID(){
		$ip = $this->getIP();
		
		if(!$ip){
			return false;
		}
		
		$num_ip = str_replace('.','',$ip);
		if(!is_numeric($num_ip)){
			return false;
		}
		$db_conn = db_connect(true);
		$slots = explode(".",$ip);
		$ipsolved = $slots[0] * 16777216 + $slots[1] * 65536 + $slots[2] * 256 + $slots[3];

		$sql = "SELECT `iso3` FROM `ipv4` WHERE /*".$ip."*/  `from` < " . ($ipsolved+1) . " AND `to` > " . ($ipsolved-1) . " LIMIT 0,1;";
		$this->log($sql);
		
		$row = $db_conn->getRow($sql);
		if(!$row)
			return false;
		return $row['iso3'];
	}
	
	function getProvider(){
		// removing IP numerics from the adress and keeping only the general provider
		$remote_host = $_SERVER['REMOTE_HOST'];
		$doms = explode('.',$remote_host);
		$count_pts=0;
		if(!$remote_host || $remote_host==''){
			return '';
		}
		$provider=$doms[count($doms)-1];
		for($i=count($doms)-2;$i>=0;$i--){
			$provider=$doms[$i].".".$provider;
			if($count_pts>=2 || strlen($doms[$i])>3){
				break;
			}
			$count_pts++;
		}
		return $provider;
	}
	
	function getBrowser(){
		$browser = new Sushee_UserBrowser();
		return $browser;
	}
	
	function getOS(){
		$client = new phpSniff();
		return $client->property('platform');
	}
	
	function getUserAgent(){
		return $_SERVER['HTTP_USER_AGENT'];
	}
	
	function getSushee_Request(){
		return new Sushee_Request();
	}
	
	function authenticate($login,$password){
		login($login,$password);
		if($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']){
			return true;
		}
		return false;
	}
	
	function connect($login,$password){
		$this->authenticate($login,$password);
	}
	
	function logout(){
		logout();
	}
	
	function disconnect(){
		$this->logout();
	}
	
	function isInTeam($groupID){
		$groupModuleInfo = moduleInfo('group');
		$teamIDs = $groupModuleInfo->teamIDs;
		
		if(in_array($groupID,$teamIDs)){
			return true;
		}
		return false;
	}
	
	function registerLoginObject($loginObject){
		if(is_object($loginObject)){
			session_start();
			$_SESSION[$GLOBALS["nectil_url"]]['loginObjectID'] = $loginObject->getID();
			session_write_close();
		}
	}
	
	function getLoginObject(){
		if(isset($_SESSION[$GLOBALS["nectil_url"]]['loginObjectID'])){
			$loginObject = new LoginModuleElement($_SESSION[$GLOBALS["nectil_url"]]['loginObjectID']);
			return $loginObject;
		}
		return false;
	}
}

class OfficityUser extends NectilUser{
	
}

class Sushee_User extends NectilUser{
	
}

// object describing the request made to sushee
class Sushee_Request extends SusheeObject{
	function Sushee_Request(){
		;
	}
	
	function getDate(){
		require_once(dirname(__FILE__)."/../common/date.class.php");
		return new Date($GLOBALS["sushee_today"]); 
	}
	
	function getDateSQL(){
		return $GLOBALS["sushee_today"];
	}
	
	function getUser(){
		return new NectilUser();
	}
	
	function getResident(){
		require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
		return new Resident($GLOBALS['residentID']);
	}
	
	function getDatabase(){
		require_once(dirname(__FILE__)."/../common/db_manip.class.php");
		return new Database($GLOBALS['db_name']);
	}
	
	function getResidentName(){
		return $GLOBALS['resident_name'];
	}
	
	function isOsRequest(){
		if (isset($GLOBALS['php_request'])) {
			return ($GLOBALS['php_request']!==true);
		} else {
			return true;
		}
	}

	function isProjectRequest(){
		if (isset($GLOBALS['php_request'])) {
			return ($GLOBALS['php_request']==true);
		} else {
			return false;
		}
	}

	static function isSecured(){
		if (isset($GLOBALS["dev_request"])) {
			return (!$GLOBALS["dev_request"]===true);
		} else {
			return true;
		}
	}

	function getLanguage(){
		return $GLOBALS["NectilLanguage"];
	}
	
	function isLanguageRestricted(){
		return ($GLOBALS["restrict_language"] && $GLOBALS['php_request']==true);
	}
	
	function isOnSharedModules(){
		$db_conn = db_connect();
		if(!isset($GLOBALS['shared_modules'])){
			// first trying in specific table then only in generic table
			$row = $db_conn->GetRow('SELECT `ID` FROM `modules` LIMIT 0,1');
			if($row){
				$GLOBALS['shared_modules']=false;
			}else{
				$GLOBALS['shared_modules']=true;
			}
		}
		return $GLOBALS['shared_modules'];
	}
	
	function isOnSharedApplications(){
		$db_conn = db_connect();
		if(!isset($GLOBALS['shared_applications'])){
			// first trying in specific table then only in generic table
			$row = $db_conn->GetRow('SELECT `ID` FROM `applications` LIMIT 0,1');
			if($row){
				$GLOBALS['shared_applications']=false;
			}else{
				$GLOBALS['shared_applications']=true;
			}
		}
		return $GLOBALS['shared_applications'];
	}
	
	function getModuleDbConn(){
		if($this->isOnSharedModules()){
			$db_conn = db_connect(TRUE);
		}else{
			$db_conn = db_connect();
		}
		return $db_conn;
	}
	
	function getApplicationDbConn(){
		if($this->isOnSharedApplications()){
			$db_conn = db_connect(TRUE);
		}else{
			$db_conn = db_connect();
		}
		return $db_conn;
	}
	
	function useCache(){
		if($_GET['cache']!=='false' && $_GET['cache']!=='refresh'){
			return true;
		}
		return false;
	}
	
	function refreshCache(){
		if($_GET['cache']=='refresh'){
			return true;
		}
		return false;
	}
	
	static function enableException($boolean = true){
		$GLOBALS['sushee_exception_enabled'] = $boolean;
	} 
	
	static function exceptionEnabled(){
		return $GLOBALS['sushee_exception_enabled'];
	}
	
	// Environment is the first directory after the domain, from where sushee is used
    function getEnvironment(){
		$calledPath = $this->getCalledPath();
		
		$slashPos = strpos($calledPath,'/',1); // searching the slash after the first slash just at the end of the domain
		if($slashPos){
			return substr( $calledPath,0,$slashPos + 1);
		}
		return false;
	}
	
	function getScript(){
		return basename($_SERVER['SCRIPT_NAME']);
	}
	
	function getCalledURL(){
		$sushee = new Sushee_Instance();
		return $sushee->getHost().$_SERVER['REQUEST_URI'];
	}
	
	function getCalledPath(){
		$sushee = new Sushee_Instance();
		return substr($this->getCalledURL(),strlen($sushee->getURL()));
	}
}

class Sushee_UserBrowser extends SusheeObject{
	
	var $sniff;
	
	function Sushee_UserBrowser(){
		$this->sniff = new phpSniff();
	}
	
	function getName(){
		return $this->sniff->property('long_name');
	}
	
	function getVersion(){
		return $this->sniff->property('version');
	}
}