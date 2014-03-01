<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/public/login.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/db_functions.inc.php");
require_once(dirname(__FILE__)."/../common/XML.class.php");
require_once(dirname(__FILE__)."/../common/module.class.php");
require_once(dirname(__FILE__)."/../common/application.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
require_once(dirname(__FILE__)."/../common/sushee.class.php");


if(!$stringofXML)
	$xml = get_xml_from_post_data(false);
else
	$xml = new XML($stringofXML);

// this sushee is used as a proxy for another one, all requests are sent to the other one and returned as is
if(Sushee_Instance::isSlave()){
	require_once(dirname(__FILE__)."/../common/url.class.php");
	
	$urlHandler = new URL(Sushee_Instance::getMasterURL().'public/login.php');
	$urlHandler->setMethod('post');
	$urlHandler->setBody($xml->toString());
	$urlHandler->addHeader('Content-Type','text/xml;charset=utf-8');
	
	$strRet = $urlHandler->execute();
	
	// saving in local the necessary variable to make the bridge work and to know that the login was made
	$xml = new XML($strRet);
	Sushee_Instance::setSlaveSessionID($xml->valueOf('/RESPONSE/@sessionID'));
	$user= new NectilUser();
	$user->setID($xml->valueOf('/RESPONSE/@userID'));
	
	if ($output_xml!==FALSE)
		xml_out($strRet);
	
}else{
	// classic mode : local instance
	if ($xml->loaded){
		$login = $xml->getData('/QUERY[1]/LOGIN[1]');
		$password = $xml->getData('/QUERY[1]/PASSWORD[1]');
		$languageID = $xml->getData('/QUERY[1]/LANGUAGEID[1]');
		$xml_sessID = $xml->getData('/QUERY[1]/@sessid');
		$forgotten = $xml->getData('/QUERY[1]/@forgotten');
		if ($xml_sessID==='0')
			$xml_sessID = FALSE;
		
		if ( ($login===FALSE || $password===FALSE || $languageID===FALSE) && $xml_sessID===FALSE){
			// the correct xml nodes were not found
			$strResponse='<MESSAGE msgType="1">Invalid request*'.htmlentities($stringOfXML).'*</MESSAGE>';
		}else if($login==='' && $xml_sessID===FALSE ){
			$strResponse='<MESSAGE msgType="3">Anonymous Login not authorized</MESSAGE>';
		}else if($forgotten==='true'){
		
			$res = sendNewPassword($login);
			if ($res){
				$strResponse='<MESSAGE msgType="0">Your login and password have been sent in your mailbox.</MESSAGE>';
			}else{
				$strResponse='<MESSAGE msgType="3">You have no access to this instance of Nectil.</MESSAGE>';
			}
		}else if($password==='' && $xml_sessID===FALSE ){
			$strResponse='<MESSAGE msgType="3">Invalid password</MESSAGE>';
		}else if($xml_sessID!==FALSE && !isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) ){
			$strResponse='<MESSAGE msgType="3">Invalid session recuperation</MESSAGE>';
		}else {
			// verifying this user exists and gave the correct password
			$db_conn = db_connect();
			if ($xml_sessID===FALSE){
				$keyringUsers_sql = 'SELECT `TargetID` FROM `dependencies` WHERE `DependencyTypeID` =3';
				$keyringUsers_rs = $db_conn->Execute($keyringUsers_sql);
				$keyringCondition = '';
				while($keyringUser = $keyringUsers_rs->FetchRow()){
					$keyringCondition.=$keyringUser['TargetID'].',';
				}
				if($keyringCondition){
					$keyringCondition=substr($keyringCondition,0,-1);
				}
				$sql = 'SELECT * FROM `contacts` WHERE `ID` IN ('.$keyringCondition.') AND `Email1`="'.encodeQuote($login).'" AND `Password`="'.encodeQuote(mysql_password($password)).'" AND `Activity`=1;';
			}else
				$sql = 'SELECT * FROM `contacts` WHERE `ID`=\''.$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'].'\';';
		
			$recordSet = &$db_conn->Execute($sql);
			if ( $contact = $recordSet->FetchRow() ){
				session_start();
				session_cache_expire(24*60);
				resetNectilSession();
				$sessionID = session_id();
				
				$user = new Sushee_User();
				$request = new Sushee_request();
				$userID = $user->setID($contact['ID']);
				$user->setSessionPassword($password);
				
				// security for the modules
				$strResponse='<MODULES>';
				$modules = new modules();
				$modulekeyModuleInfo = moduleInfo('modulekey');
				$fields_array=array('ID','Denomination','IsPrivate','ModuleToID');
				while($moduleInfo = $modules->next()){
				
					$strResponse.='<MODULE ID="'.$moduleInfo->ID.'" module="'.$moduleInfo->name.'">';
					$moduleKey = getInfo($modulekeyModuleInfo,$moduleInfo->moduleKeyID);
					$strResponse.=generateInfoXML($modulekeyModuleInfo,$moduleKey,$fields_array,false);
					$strResponse.=$moduleInfo->getXMLSecurity();
					$strResponse.='</MODULE>';
					foreach($moduleInfo->depTypes as $depTypeName=>$props){
						depType($depTypeName);
					}
				}
				$strResponse.='</MODULES>';
			
				// security for the application
				$apps = new ApplicationCollection();
				$strResponse.=$apps->getLoginXML($languageID);
			
				$specific_db_conn = db_connect(FALSE);
				$ContactModuleInfo = moduleInfo('contact');
				$strResponse.='<CONTACT ID="'.$userID.'">'.generateInfoXML($ContactModuleInfo,$contact,$ContactModuleInfo->getFieldsBySecurity('0'),FALSE).'</CONTACT>';
				$groupModuleInfo = moduleInfo('group');
				$strResponse.='<TEAMS>';
				$group_sql = 'SELECT gp.`Denomination`,gp.`ID` FROM `dependencies` AS dep LEFT JOIN `groups` AS gp ON gp.`ID`=dep.`OriginID` WHERE gp.`IsTeam`=1 AND dep.`TargetID`='.$userID.' AND dep.`DependencyTypeID`=1;';
				$rs = $specific_db_conn->Execute($group_sql);
				while($row = $rs->FetchRow()){
					$strResponse.='<GROUP ID="'.$row['ID'].'"><DENOMINATION>'.encode_to_XML($row['Denomination']).'</DENOMINATION></GROUP>';
				}
				$strResponse.='</TEAMS>';
			
				// adding informations about the environment of the user
				$IP = $user->getIP();
				$browser = $user->getBrowser();
			
				$strResponse.='<USERAGENT>'.encode_to_XML($user->getUserAgent()).'</USERAGENT>';
				$strResponse.='<BROWSER>'.encode_to_XML($browser->getName()).'</BROWSER>';
				$strResponse.='<IP>'.encode_to_XML($IP).'</IP>';
			
				// saving the login datas in the database to follow the user activity
				$loginRegister['UserID'] = $userID;
				$loginRegister['Connection'] = $request->getDateSQL();
				$loginRegister['LastAction'] = $request->getDateSQL();
				$loginRegister['SessionID'] = $sessionID;
				$loginRegister['IP'] = $IP;
				$loginRegister['Browser'] = $browser->getName();
				$loginRegister['BrowserVersion'] = $browser->getVersion();
				$loginRegister['OS'] = $user->getOS();
				$loginRegister['Provider'] = $user->getProvider();
				$loginRegister['CountryID'] = $user->getCountryID();
				$loginRegister['LanguageID'] = $request->getLanguage();
			
				$loginRegisterObject = new LoginModuleElement($loginRegister);
				$loginRegisterObject->create();
			
				$user->registerLoginObject($loginRegisterObject);
			
			}else{
				$strResponse='<MESSAGE msgType="1">Invalid login or password</MESSAGE>';
			}
		}
	}



	require_once(dirname(__FILE__)."/../common/output_xml.inc.php");
}
?>
