<?php

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");

class Sushee_Instance extends SusheeObject
{
	
	var $error = false;
	
	function initialize()
	{
		session_start();
		$this->initializeGlobals();
		$this->initializeURLs();
		$this->initializeLanguage();
		$this->initializeCacheState();
		$this->initializeLogState();
	}
	
	function initializeCacheState()
	{	
		$GLOBALS['cache_Lifetimes'] = array(
			'daily'		=>array(86400,3600),
			'monthly'	=>array(18748800,3600),
			'weekly'	=>array(604800,3600),
			'hourly'	=>array(3600,16*60),
			'yearly'	=>array(6843312000,3600),
			'quarter'	=>array(15*60,60),
			'minutely'	=>array(60,15)
		);

		// cache state taken from the session
		if ( isset($_SESSION[$GLOBALS["nectil_url"]]["cache"]) && !isset($_GET['cache']) )
			$_GET['cache'] = $_SESSION[$GLOBALS["nectil_url"]]["cache"];
		else if (isset($_GET['cache']))
			$_SESSION[$GLOBALS["nectil_url"]]["cache"] = $_GET['cache'];
	}

	function initializeLogState()
	{
		if (isset($_GET['logsdev']) && $GLOBALS['sushee_logsdev'] !== 'false')
		{
			$_SESSION[$GLOBALS['nectil_url']]['logsdev'] = $_GET['logsdev'];
		}
	}

	function initializeGlobals()
	{
		$OS = getServerOS();
		if($OS=='windows')
			$GLOBALS['slash'] = "\\";
		else
			$GLOBALS['slash'] = '/';
		$GLOBALS['serverOS'] = $OS;
		global $slash;
		

		$GLOBALS["sushee_today"] = date("Y-m-d H:i:s");

		// -- OBSOLETE - TO KILL ----
		$GLOBALS["generic_backoffice_db"] = $GLOBALS['db_name'];
		$GLOBALS["generic_backoffice"]=false;
		// -- OBSOLETE - TO KILL ----

		// defines the minimal length of the words contained in MYSQL fulltext indexes.
		// If a word is smaller than this size, we do not include it in the search (metasearchdatatypes.inc.php)
		if(!isset($GLOBALS['MySQLFullTextMinLength']))
		{
			$GLOBALS['MySQLFullTextMinLength'] = 3;
		}
	}

	function initializeURLs()
	{
		global $slash;

		if(!isset($_SERVER['REQUEST_URI']))
		{
			$params2 = substr($_SERVER['argv'][0], strpos($_SERVER['argv'][0], ';'));
			$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
			if ($params2)
			{
				$_SERVER['REQUEST_URI'].="?".$params2;
			}
		}

		if($GLOBALS['SusheeRoot'])
		{
			$root_dir = $GLOBALS['SusheeRoot'];
			if(substr($root_dir,-1)=='/')
			{
				$root_dir = substr($root_dir,0,-1);
			}
		}
		else
		{
			// this file is in /xxx/yyy/zzz/sushee/common/sushee.class.php
			$root_dir = dirname(dirname(dirname(__FILE__)));
		}

		if (substr($root_dir,-1)=='\\')
		{
			$root_dir = substr($root_dir,0,-1);
		}

		if ($root_dir=='')
		{
			die("FATAL Error. Problem getting Root_dir. Contact the website administrator.");
		}

		if (substr($root_dir,0,2)=='//')
		{
			$root_dir = substr($root_dir,1);
		}

		if (!isset($_SERVER['HTTP_HOST']))
		{
			// to get it working on command line
			$_SERVER['HTTP_HOST'] = $GLOBALS['SusheeDefaultURL'];
		}
	
		if (empty($_SERVER["DOCUMENT_ROOT"]))
		{
			// to get it working on the command line
			$_SERVER["DOCUMENT_ROOT"] = $root_dir;
		}

		if(substr($_SERVER["DOCUMENT_ROOT"],-1)==$slash)
		{
			// removing a possible ending slash
			$_SERVER["DOCUMENT_ROOT"] = substr($_SERVER["DOCUMENT_ROOT"],0,-1);
		}

		// for IIS
		$_SERVER["DOCUMENT_ROOT"] = str_replace("C:\\\\","C:\\",$_SERVER["DOCUMENT_ROOT"]);
		$_SERVER["DOCUMENT_ROOT"] = str_replace("D:\\\\","D:\\",$_SERVER["DOCUMENT_ROOT"]);
		$_SERVER["DOCUMENT_ROOT"] = str_replace("/",$slash,$_SERVER["DOCUMENT_ROOT"]);

		$short_root_dir = shorten($root_dir);

		if(substr($short_root_dir,0,1)!='/' && $short_root_dir)
		{
			// missing the trailing slash
			$short_root_dir = '/'.$short_root_dir;
		}

		if( substr($_SERVER['HTTP_HOST'],-3)===':80' )
		{
			$_SERVER['HTTP_HOST'] = substr($_SERVER['HTTP_HOST'],0,-3);
		}

		$protocol = 'http://';
		if($_SERVER['SERVER_PORT']=='443')
		{
			$protocol = 'https://';
		}
		else if (!$_SERVER['SERVER_PORT'] && !empty($GLOBALS['SusheeDefaultProtocol']))
		{
			// to get it working on the command line
			$protocol = $GLOBALS['SusheeDefaultProtocol'] . '://';
		}

		$GLOBALS['SusheeProtocol'] = $protocol;
		
		$officity_host = $protocol.$_SERVER['HTTP_HOST'];
		if(substr($_SERVER['REQUEST_URI'],0,strlen($root_dir))==$root_dir)
		{
			$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'],strlen($root_dir));
		}

		$officity_host_explosion = explode('?',$_SERVER['REQUEST_URI']);
		$officity_host_explosion = explode('/',$officity_host_explosion[0]);

		// remove the first empty element of the array
		array_shift($officity_host_explosion);

		$officity_path = '';
		if ($officity_host_explosion[0][0] === '~') 
		{
			// special case for apache userdir module /~user/
			$officity_path .= $officity_host_explosion[0] . '/';

			 // remove the user component of the path
			array_pop($officity_host_explosion);
		}

		// if sushee root is given, using it because its a better method
		if($GLOBALS['SusheeRoot'])
		{
			$officity_path .= substr($GLOBALS["SusheeRoot"],strlen($_SERVER["DOCUMENT_ROOT"]));
		}
		else
		{
			$found_stopper = false;
			$officity_host_parts = sizeof($officity_host_explosion);

			for($i=$officity_host_parts-1 ; $i>=0 ; $i--)
			{
				$part = strtolower($officity_host_explosion[$i]);
				array_pop($officity_host_explosion);
				if(in_array($part,$stopper_parts) || $part[0]=='~')
				{
					$next_part = strtolower($officity_host_explosion[$i-1]);
					if($next_part=='kernel' && $part=='public')
					{
						// special case for kernel/public/login.php
						; // going on, false alert
					}
					else if($next_part=='kernel' && $part=='library')
					{
						// special case for kernel/library/
						; // going on, false alert
					}
					else
					{
						$found_stopper = true;
						break;
					}
				}
			}

			if($found_stopper)
			{
				$officity_path .= implode('/',$officity_host_explosion);
			}
		}

		$GLOBALS["nectil_url"] = $officity_host.$officity_path;
		$GLOBALS["nectil_url"] = str_replace ( "\\", '/', $GLOBALS["nectil_url"]);
		if(substr($GLOBALS["nectil_url"],-1)=='/')
		{
			$GLOBALS["nectil_url"] = substr($GLOBALS["nectil_url"],0,-1);
		}

		$GLOBALS["nectil_dir"] = str_replace("/",$slash,$root_dir);

		define('Sushee_dirname', basename(dirname(dirname(__FILE__))));

		$GLOBALS["backoffice_url"] = $GLOBALS["nectil_url"]."/".Sushee_dirname."/";
		$GLOBALS["backoffice_dir"] = $GLOBALS["nectil_dir"].$slash.Sushee_dirname.$slash;

		$GLOBALS["library_dir"] = $GLOBALS["nectil_dir"].$slash."Library".$slash;
		
		$GLOBALS["OS_url"] = $GLOBALS["nectil_url"].'/';
		$GLOBALS["OS_dir"] = $GLOBALS["nectil_dir"].$slash;
		
		$GLOBALS["Public_dir"] = $GLOBALS["nectil_dir"].$slash."Public".$slash;
		$GLOBALS["Public_url"] = $GLOBALS["nectil_url"].'/Public/';
		
		$GLOBALS["directoryRoot"] = realpath($GLOBALS["nectil_dir"].$slash.'Files');
		$GLOBALS["files_url"] = $GLOBALS["nectil_url"]."/Files";
	}
	
	function initializeLanguage()
	{
		if (isset($_GET["language"]) && $_GET["language"]!='' && $_GET["language"]!='shared')
		{
			$language_ok = false;
			if(strlen($_GET["language"])==2)
			{
				// iso 2 chars
				$db_conn = db_connect(true);
				$rowISO1 = $db_conn->GetRow('SELECT `ID` FROM `languages` WHERE `ISO1`="'.encodeQuote($_GET["language"]).'";');
				if($rowISO1)
				{
					$language_ok = true;
					$_GET["language"] = $rowISO1['ID'];
				}
			}
			else if($_SESSION[$GLOBALS["nectil_url"]]["language"] != $_GET["language"])
			{
				// iso 3 chars
				$db_conn = db_connect(true);
				$sql = 'SELECT `ID` FROM `languages` WHERE `ID`="'.encodeQuote($_GET["language"]).'";';
				$row = $db_conn->GetRow($sql);
				if($row)
				{
					$language_ok = true;
				}
			}
			else if($_SESSION[$GLOBALS["nectil_url"]]["language"] == $_GET["language"])
			{
				$language_ok = true;
			}

			if($language_ok)
			{
				$_SESSION[$GLOBALS["nectil_url"]]["language"] = $_GET["language"];
			}
			else
			{	
				unset($_GET['language']);
			}
		}
		else
		{
			unset($_GET['language']);
		}

		if ( !isset($_SESSION[$GLOBALS["nectil_url"]]["language"]) )
		{
			$db_conn = db_connect();

			// first try to find which language is best for the user
			if($_SERVER["HTTP_ACCEPT_LANGUAGE"])
			{
				$availableLangs = array();
				$ISO3equiv = array();

				$sql = "SELECT lgs.`ISO1`,lgs.`ID` FROM `medialanguages` AS mlgs LEFT JOIN `backoffice`.`languages` AS lgs ON (mlgs.`languageID`=lgs.`ID`) WHERE `published`=1";
				$rs = $db_conn->Execute($sql);
				if($rs)
				{
					while($row = $rs->FetchRow())
					{
						$availableLangs[] = strtolower($row['ISO1']);
						$ISO3equiv[strtolower($row['ISO1'])]=$row['ID'];
					}

					foreach(split(',', $_SERVER["HTTP_ACCEPT_LANGUAGE"]) as $lang)
					{
					   if (preg_match('/^([a-z\-]+).*?(?:;q=([0-9.]+))?/i', $lang.';q=1.0', $split))
					{
						   $preferred_lg[sprintf("%f%d", $split[2], rand(0,9999))]=strtolower($split[1]);
					   }
					}

					krsort($preferred_lg);
					$best_lg = array_shift(array_merge(array_intersect($preferred_lg, $availableLangs), $availableLangs));
					if($best_lg && isset($ISO3equiv[$best_lg]))
					{
						$_SESSION[$GLOBALS["nectil_url"]]["language"] = $ISO3equiv[$best_lg];
					}
				}
			}
			else
			{
				//debug_log(' no HTTP_ACCEPT_LANGUAGE ');
			}

			// if no matching language found, taking the default language
			if(!isset($_SESSION[$GLOBALS["nectil_url"]]["language"]))
			{
				$sql = "SELECT * FROM `medialanguages` WHERE `published`=1 ORDER BY `priority` ASC LIMIT 1;";
				$row = $db_conn->GetRow($sql);
				$_SESSION[$GLOBALS["nectil_url"]]["language"] = $row["languageID"];
			}
		}

		$GLOBALS["NectilLanguage"] = $_SESSION[$GLOBALS["nectil_url"]]["language"];
	}
	
	function getError()
	{
		return $this->error;
	}
	
	function setError($error)
	{
		$this->error = $error;
	}
	
	function checkValidity()
	{
		// setting the right db_name if generic
		if ( !isNectilMaster($GLOBALS["nectil_url"]) && $GLOBALS["generic_backoffice"]){
			
			$db_conn = db_connect(TRUE);
			
			if(strpos($GLOBALS["nectil_url"],'http://www.')===false){
				$with_www = str_replace('http://','http://www.',$GLOBALS["nectil_url"]);
			}else{
				$with_www = $GLOBALS["nectil_url"];
			}
			$eol_db = "\r\n";
			$eol_flash = "\n";
			$condition_url2 = "( `URL2`=\"".$GLOBALS["nectil_url"]."\" 
			OR `URL2` LIKE \"".$GLOBALS["nectil_url"]."$eol_db%\" 
			OR `URL2` LIKE \"%$eol_db".$GLOBALS["nectil_url"]."\" 
			OR `URL2` LIKE \"%$eol_db".$GLOBALS["nectil_url"]."$eol_db%\" 
			OR `URL2` LIKE \"".$GLOBALS["nectil_url"]."$eol_flash%\" 
			OR `URL2` LIKE \"%$eol_flash".$GLOBALS["nectil_url"]."\" 
			OR `URL2` LIKE \"%$eol_flash".$GLOBALS["nectil_url"]."$eol_flash%\" 
			OR `URL2` LIKE \"".$with_www."$eol_db%\" 
			OR `URL2` LIKE \"%$eol_db".$with_www."\" 
			OR `URL2` LIKE \"%$eol_db".$with_www."$eol_db%\"
			OR `URL2` LIKE \"".$with_www."$eol_flash%\" 
			OR `URL2` LIKE \"%$eol_flash".$with_www."\" 
			OR `URL2` LIKE \"%$eol_flash".$with_www."$eol_flash%\"
			OR `URL2`=\"".$with_www."\")";
			$complete_cond = 
			$sql = "SELECT `ID`,`Published`,`Denomination`,`DbName`,`URL2`,`Profile` FROM `residents` WHERE ( (`URL`=\"".$GLOBALS["nectil_url"]."\" OR `URL`=\"".$with_www."\" ) OR ( `Published`=1 AND ".$condition_url2." ) )	AND `Activity`=1 AND (`ExpirationDate`=\"0000-00-00\" OR `ExpirationDate` > \"".$GLOBALS["sushee_today"]."\" OR `ExpirationDate`=\"0000-01-01\");";
			//die($sql);
			$row = $db_conn->GetRow($sql);
			if ($row){
				$GLOBALS['db_name']=$row['DbName'];
				if(!$row['DbName']){
					$GLOBALS['db_name'] = $row['Denomination'];
				}
				
				$GLOBALS['resident_name']=$row['Denomination'];
				$GLOBALS['residentID']=$row['ID'];
				$GLOBALS['residentPublished']=$row['Published'];
				if($row['URL2']!='')
					$GLOBALS['residentURL2']=$row['URL2'];
				$GLOBALS['resident_profile'] = new XML("<PROFILE>".$row['Profile']."</PROFILE>");
				// a simple application authorization control (could be a bit cleaner)
				$applications_array = $GLOBALS['resident_profile']->match("/PROFILE/APPLICATIONS/APPLICATION/APPNAME");
				$GLOBALS['resident_applications']=array();
				foreach($applications_array as $path){
					$data = $GLOBALS['resident_profile']->getData($path);
					$GLOBALS['resident_applications'][$data]=true;
				}
				return true;
			}else{
				$check_state = "SELECT `DbName`,`Denomination`,`ID`,`ExpirationDate`,`Published` FROM `residents` WHERE (`URL`=\"".$GLOBALS["nectil_url"]."\" OR `URL`=\"".$with_www."\" ) OR ".$condition_url2." AND `Activity`=1";
				$row_check = $db_conn->GetRow($check_state);
				$GLOBALS['db_name']=$row_check['DbName'];
				$GLOBALS['resident_name']=$row_check['Denomination'];
				$GLOBALS['residentID']=$row_check['ID'];
				$GLOBALS['residentPublished']=$row_check['Published'];
				if($row_check['Published']=='0'){
					$msg = 'Website under development, not yet published with its final URL';
				}else{
					$msg = 'Website has expired. Sorry for the inconvenience !';
					if(substr($_SERVER['SCRIPT_NAME'],-25)=='/'.Sushee_dirname.'/private/tasks.php'){
						include_once(dirname(__FILE__)."/../private/warn_before_expiration.php");
					}
				}
				$this->setError($msg);
				return false;
			}
		}else{
			// On Officity.com, validities are not checked anymore, residents are autonomous. but for mailing checks we need the resident name in the mailing spy
			// deducing the resident name from the url
			if(substr($GLOBALS["nectil_url"],-13)=='.officity.com'){
				$GLOBALS['resident_name'] = str_replace(array('http://','.officity.com'),'',$GLOBALS["nectil_url"]);
			}
			
		}
		return true;
	}

	function getURL(){
		return $GLOBALS["nectil_url"];
	}

	function getFilesURL(){
		return $GLOBALS["files_url"];
	}

	function getKernelURL(){
		return $GLOBALS["backoffice_url"];
	}
	
	function getPublicURL(){
		return $GLOBALS["Public_url"];
	}
	
	function getLibraryURL(){
		$GLOBALS["nectil_url"].'/Library/';
	}
	
	static function getPath(){
		return $GLOBALS["nectil_dir"];
	}
	
	function getFilesPath(){
		return $GLOBALS["directoryRoot"];
	}
	
	function getKernelPath(){
		return $GLOBALS["backoffice_dir"];
	}
	
	function getHost(){
		return $GLOBALS['SusheeProtocol'].$_SERVER['HTTP_HOST'];
	}
	
	static function getSusheePath(){
		return $GLOBALS["backoffice_dir"];
	}
	
	function getPublicPath(){
		return $GLOBALS["Public_dir"];
	}
	
	function getLibraryPath(){
		return $GLOBALS["library_dir"];
	}
	
	function getAdminEmail(){
		return $GLOBALS["admin_email"];
	}
	
	static function getConfigValue($name){
		return $GLOBALS[$name];
	}
	
	// a sushee slave is a sushee instance which calls a distant sushee to get its data
	static function isSlave(){
		return isset($GLOBALS['SusheeMaster']) && !empty($GLOBALS['SusheeMaster']);
	}
	
	// the master of a sushee slave is the sushee where the slave is getting its data
	static function getMasterURL(){
		return $GLOBALS['SusheeMaster'];
	}
	
	// session ID is necessary to stay connected to the master
	static function setSlaveSessionID($sessionID){
		$_SESSION['SusheeSlaveSessionID'] = $sessionID;
	}
	
	static function getSlaveSessionID(){
		return $_SESSION['SusheeSlaveSessionID'];
	}
	
	static function getConfigParam($paramName){
		if(isset($GLOBALS[$paramName]))
			return $GLOBALS[$paramName];
		return false;
	}
	
	static function isWindows(){
		if( getServerOS() == 'windows' ){
			return true;
		}
		return false;
	}
}