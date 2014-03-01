<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/application.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/namespace.class.php");

class ApplicationCollection extends SusheeObject{
	
	var $list=false;
	var $index = 0;
	
	// Give the official native apps for Officity Flash OS which is now deprecated : prefer the getApplicationRs method
	function getOfficialApplicationRs(){
		$request = new Sushee_Request();
		$db_conn = $request->getApplicationDbConn();
		$sql = 'SELECT * FROM `applications` WHERE `ID` > 2 AND `ID` < 1024 ORDER BY `Denomination`';
		sql_log($sql);
		$app_rs = $db_conn->Execute($sql);
		return $app_rs;
	}
	
	function getApplicationRs(){
		$request = new Sushee_Request();
		$db_conn = $request->getApplicationDbConn();
		$sql = 'SELECT * FROM `applications` WHERE `ID` > 2 AND `URL` != "" AND `Activity` = 1 ORDER BY `Denomination`';
		sql_log($sql);
		$app_rs = $db_conn->Execute($sql);
		return $app_rs;
	}
	
	function _initList(){
		$this->list = array();
		$app_rs = $this->getApplicationRs();
		$user = new NectilUser();
		if($app_rs){
			while($row = $app_rs->FetchRow() ){
				$app = new OfficialApplication($row['Denomination']);
				$app->setRow($row);
				if($app->isActive() && $user->hasApplication($app))
					$this->list[]=$app;
			}
		}
		$custom_app_rs = $this->getCustomApplicationRs();
		if($custom_app_rs){
			while($row = $custom_app_rs->FetchRow() ){
				$app = new CustomApplication($row['Denomination']);
				$app->setRow($row);
				if($app->isActive() && $user->hasApplication($app))
					$this->list[]=$app;
			}
		}
	}
	
	function next(){
		if($this->list===false){
			$this->_initList();
		}
		$app = $this->list[$this->index];
		if($app)
			$this->index++;
		return $app;
	}
	// Give the custom apps for Officity Flash OS which is now DEPRECATED : prefer the getApplicationRs method
	function getCustomApplicationRs(){
		$request = new Sushee_Request();
		$db_conn = $request->getApplicationDbConn();
		$custom_app_rs = $db_conn->Execute('SELECT * FROM `applications_custom` ORDER BY `Denomination`');
		if(!$custom_app_rs || $custom_app_rs->RecordCount()==0){
			$sql = 'SELECT * FROM `applications` WHERE `ID` >= 1024 AND `Activity` = 1 ORDER BY `Denomination`';
			sql_log($sql);
			$custom_app_rs = $db_conn->Execute($sql);
		}
		return $custom_app_rs;
	}
	
	function getLoginXML($languageID){
		
		$user = new NectilUser();
		
		$strResponse='<APPLICATIONS>';
		$app_rs = $this->getOfficialApplicationRs();
		if($app_rs){
			while($row = $app_rs->FetchRow() ){
				$app = new OfficialApplication($row['Denomination']);
				$app->setRow($row);
				if($app->isActive() && $user->hasApplication($app))
					$strResponse.=$app->getLoginXML($languageID);
			}
		}
		$custom_app_rs = $this->getCustomApplicationRs();
		if($custom_app_rs){
			while($row = $custom_app_rs->FetchRow() ){
				$app = new CustomApplication($row['Denomination']);
				$app->setRow($row);
				if($app->isActive() && $user->hasApplication($app))
					$strResponse.=$app->getLoginXML($languageID);
			}
		}
		$strResponse.='</APPLICATIONS>';
		return $strResponse;
	}
	
	function getLightSecurityXML($languageID){
		
		$user = new NectilUser();
		
		$strResponse='<APPLICATIONS>';
		$app_rs = $this->getApplicationRs();
		if($app_rs){
			while($row = $app_rs->FetchRow() ){
				$app = new OfficialApplication($row['Denomination']);
				$app->setRow($row);
				if($app->isActive() && $user->hasApplication($app))
					$strResponse.=$app->getLightSecurityXML($languageID);
			}
		}
		$strResponse.='</APPLICATIONS>';
		return $strResponse;
	}
	
	function getSharedXML($languageID){
		$db_conn = db_connect(TRUE);
		$xml = '';
		$xml.='<APPLICATIONS languageID="'.$languageID.'">';
		
		$app_rs = $this->getOfficialApplicationRs();
		if($app_rs){
			while($row = $app_rs->FetchRow() ){
				$app = new OfficialApplication($row['ID']);
				$app->setRow($row);
				if ($app->isActive()){
					$xml.=$app->getSharedXML($languageID);
				}else{
					$this->log('ApplicationCollection.getSharedXML : disabled '.$row['Denomination']);
				}
			}
		}
		
		$app_rs = $this->getCustomApplicationRs();
		if($app_rs){
			while($row = $app_rs->FetchRow() ){
				$app = new CustomApplication($row['ID']);
				$app->setRow($row);
				if ($app->isActive()){
					$xml.=$app->getSharedXML($languageID);
				}else{
					$this->log('ApplicationCollection.getSharedXML : disabled '.$row['Denomination']);
				}
			}
		}
		
		
		$xml.='</APPLICATIONS>';
		return $xml;
	}
	
	function getTraductionXML($languageID){
		
	}
}

class OfficialApplication extends SusheeObject{
	var $ID = false;
	var $name = false;
	var $url = false;
	var $row = false;
	
	function getID(){
		if($this->ID){
			return $this->ID;
		}else{
			$row = $this->getRow();
			return $row['ID'];
		}
	}
	
	function getSQLCondition(){
		if($this->ID){
			return ' WHERE `ID`=\''.$this->ID.'\'';
		}else if($this->url){
			$explosion = explode('/',$this->url);
			$namespace = $explosion[0];
			$url = $explosion[1];
			return ' WHERE `URL`="'.encodeQuote($url).'" AND `Namespace` = "'.encodeQuote($namespace).'"';
		}else{
			return ' WHERE `Denomination`=\''.$this->name.'\'';
		}
	}
	
	function setRow($row){
		$this->row = $row;
	}
	
	function exists(){
		return $this->getRow();
	}
	
	function getRow(){
		if(!$this->row){
			$request = new Sushee_Request();
			$db_conn = $request->getApplicationDbConn();
			$sql = 'SELECT * FROM `applications` '.$this->getSQLCondition();
			sql_log($sql);
			$this->row = $db_conn->GetRow($sql);
		}
		return $this->row;
	}
	
	function isActive(){
		$row = $this->getRow();
		if($this->getID()==15){
			$sql = 'SELECT `ID` FROM `residents` WHERE `ID`=1';
			$db_conn = db_connect();
			$template = $db_conn->getRow($sql);
			if(!$template)
				return false;
		}
		if($this->getID()==10){
			$sql = 'SELECT `ID` FROM `mailings` WHERE `ID`=1';
			$db_conn = db_connect();
			$template = $db_conn->getRow($sql);
			if(!$template)
				return false;
		}
		if($this->getID()==11 || $this->getID()==12){
			$sql = 'SELECT `ID` FROM `sound_arts` WHERE `ID`=1';
			$db_conn = db_connect();
			$template = $db_conn->getRow($sql);
			if(!$template)
				return false;
		}
		if($this->getID()==14){
			$sql = 'SELECT `ID` FROM `documents` WHERE `ID`=1';
			$db_conn = db_connect();
			$template = $db_conn->getRow($sql);
			if(!$template)
				return false;
		}
		if($this->getID()==13 || $this->getID()==17){
			if($GLOBALS["nectil_url"]!='officity.com')
				return false;
		}
		
		if(!isNectilMaster($GLOBALS["nectil_url"]) && $GLOBALS["generic_backoffice"] && !isset($GLOBALS['resident_applications'][$row['Denomination']]))
			return false;
		else
			return true;
	}
	
	function OfficialApplication($ID_or_name_or_URL){
		if(is_numeric($ID_or_name_or_URL)){
			$this->ID = $ID_or_name_or_URL;
		}else{
			if(strpos($ID_or_name_or_URL,'/')!==false){
				$this->url = $ID_or_name_or_URL;
			}else{
				$this->name = $ID_or_name_or_URL;
			}
		}
	}

	function getLoginXML($languageID){
		$row = $this->getRow();
		$user = new NectilUser();
		$key = $user->getApplicationKey($this);
		if($key){
			$security = $key->getField('Permissions');
		}
		
		return '<APPLICATION ID="'.$row["ID"].'" type="'.$row["Type"].'" dockable="'.$row["Dockable"].'" icon="'.$row["Icon"].'" url="'.$row["URL"].'" namespace="'.$row["Namespace"].'"><APPNAME>'.$row["Denomination"].'</APPNAME><SECURITY>'.$security.'</SECURITY></APPLICATION>';
	}

	function getLightSecurityXML(){
		$row = $this->getRow();
		$user = new NectilUser();
		$key = $user->getApplicationKey($this);
		if($key){
			$security = $key->getField('Permissions');
		}
		return '<APPLICATION name="'.encode_to_xml($row["Denomination"]).'" ID="'.$row["ID"].'" url="'.$row["URL"].'" namespace="'.$row["Namespace"].'">'.$security.'</APPLICATION>';
	}
	
	function getSharedXML($languageID){
		$db_conn = db_connect(TRUE);
		$row = $this->getRow();
		$sql = "SELECT trad.`Denomination`,trad.`SharedText` FROM `traductions` as trad WHERE trad.`LanguageID`=\"$languageID\" AND trad.`ApplicationID`=".$row['ID'].";";
		$trad_row = $db_conn->GetRow($sql);
		if ( !$trad_row ){
			$appTrad=$row["Denomination"];
		}else{
			$appTrad=$trad_row["Denomination"];
			$sharedText = $trad_row["SharedText"];
		}
		$xml = '';
		if($row['Dockable']==0){
			$dockable = 'false';
		}else{
			$dockable = 'true';
		}
		$xml.='<APPLICATION Dockable="'.$dockable.'">';
		$xml.=		'<APPNAME>'.$row["Denomination"].'</APPNAME>';
		$xml.=		'<NAME>'.$appTrad.'</NAME>';
		$xml.=		'<VISUAL>'.$row["IconType"].'</VISUAL>';
		$xml.=		'<SHAREDTEXT>'.$sharedText.'</SHAREDTEXT>';
		$xml.=		'<TYPE>'.$row["Type"].'</TYPE>';
		$xml.='</APPLICATION>';
		return $xml;
	}
	
	function getTraductionXML($languageID){
		$db_conn = db_connect(TRUE);
		$app_row = $this->getRow();
		$xml = '';
		$sql='SELECT `Text` FROM `traductions` WHERE `ApplicationID`=\''.$app_row['ID'].'\' AND `LanguageID`="'.$languageID.'"';
		sql_log($sql);
		$row = $db_conn->GetRow($sql);
		if (!$row){
			$languageID='eng';
			$sql='SELECT `Text` FROM `traductions` WHERE `ApplicationID`=\''.$app_row['ID'].'\' AND `LanguageID`="'.$languageID.'"';
			$row = $db_conn->GetRow($sql);
		}
		
		$xml.='<TRADUCTION languageID="'.$languageID.'">';
		$xml.=		'<TEXT>'.$row["Text"].'</TEXT>';
		$xml.='</TRADUCTION>';
		return $xml;
	}
	
	function getXML(){
		$row = $this->getRow();
		if($row['Dockable']==0){
			$dockable = 'false';
		}else{
			$dockable = 'true';
		}
		$xml.='<APPLICATION ID="'.$row['ID'].'" Dockable="'.$dockable.'">';
		$xml.=		'<APPNAME>'.$row["Denomination"].'</APPNAME>';
		$xml.=		'<TEMPLATE>'.$row["SecurityTemplate"].'</TEMPLATE>';
		$xml.=		'<VISUAL>'.$row["IconType"].'</VISUAL>';
		$xml.=		'<TYPE>'.$row["Type"].'</TYPE>';
		$xml.='</APPLICATION>';
		return $xml;
	}
}

/* 
We try now to avoid the use of this class and to have all in OfficialApplication
This class was intented to make the distinction between native app and custom app in Officity Flash OS, which is deprecated
 */ 
class CustomApplication extends SusheeObject{
	var $ID = false;
	var $name = false;
	var $row = false;
	
	function CustomApplication($ID_or_name){
		if(is_numeric($ID_or_name)){
			$this->ID = $ID_or_name;
		}else{
			$this->name = $ID_or_name;
		}
		
	}
	
	function getID(){
		if($this->ID){
			return $this->ID;
		}else{
			$row = $this->getRow();
			return $row['ID'];
		}
	}
	
	function getName(){
		if($this->name){
			return $this->name;
		}else{
			$row = $this->getRow();
			$this->name = $row['Denomination'];
			return $this->name;
		}
	}
	
	function getNamespace(){
		$name = $this->getName();
		$explosion = explode(':',$name);
		if(sizeof($explosion)>1){
			return new SusheeNamespace($explosion[0]);
		}else
			return false;
	}
	
	
	function setRow($row){
		$this->row = $row;
	}
	
	function getSQLCondition(){
		if($this->ID){
			return ' WHERE `ID`=\''.$this->ID.'\'';
		}else{
			return ' WHERE `Denomination`=\''.$this->name.'\'';
		}
	}
	
	function exists(){
		return is_array($this->getRow());
	}
	
	function isNative(){
		return ($this->getID() < 1024);
	}
	
	function getRow(){
		if(!$this->row){
			$db_conn = db_connect();
			$sql = 'SELECT * FROM `applications_custom` '.$this->getSQLCondition(); // DEPRECATED : NOW ALL APP ARE IN APPLICATIONS TABLE
			$this->row = $db_conn->GetRow($sql);
			if(!$this->row){
				$sql = 'SELECT * FROM `applications` '.$this->getSQLCondition();
				$this->row = $db_conn->GetRow($sql);
			}
		}
		return $this->row;
	}
	
	function isActive(){
		return true;
	}
	
	function getLoginXML($languageID){
		$row = $this->getRow();
		
		return '<APPLICATION_CUSTOM ID="'.$row["ID"].'" type="'.$row["Type"].'" dockable="'.$row["Dockable"].'" icon="'.$row["Icon"].'" url="'.$row["URL"].'"><APPNAME>'.$row["Denomination"].'</APPNAME><SECURITY></SECURITY><VISUAL>'.$row["Icon"].'</VISUAL><URL>'.$row["URL"].'</URL><TYPE>'.$row["Type"].'</TYPE><CLASS>'.$row["Class"].'</CLASS></APPLICATION_CUSTOM>';
	}
	
	function getSharedXML($languageID){
		$db_conn = db_connect();
		$row = $this->getRow();
		$sql = "SELECT `Title` FROM `descriptions` WHERE `LanguageID` IN (\"$languageID\",\"shared\") AND `ModuleTargetID` = '6' AND `TargetID`=".$row['ID']." AND `Status` = \"published\";";
		sql_log($sql);
		$trad_row = $db_conn->GetRow($sql);
		if ( !$trad_row ){
			$appTrad = $row["Denomination"];
		}else{
			$appTrad = $trad_row["Title"];
		}
		$xml = '';
		$xml.='<APPLICATION_CUSTOM Dockable="true">';
		$xml.=		'<APPNAME>'.$row["Denomination"].'</APPNAME>';
		$xml.=		'<NAME>'.$appTrad.'</NAME>';
		$xml.=		'<VISUAL>'.$row["Icon"].'</VISUAL>';
		$xml.=		'<URL>'.$row["URL"].'</URL>';
		$xml.=		'<TYPE>'.$row["Type"].'</TYPE>';
		$xml.=		'<CLASS>'.$row["Type"].'</CLASS>';
		$xml.='</APPLICATION_CUSTOM>';
		return $xml;
	}
	
	// DEPRECATED : ONLY FOR COMPAT WITH FLASH OS
	function getTraductionXML($languageID){
		$xml.='<TRADUCTION languageID="'.$languageID.'">';
		$xml.=		'<TEXT></TEXT>';
		$xml.='</TRADUCTION>';
		return $xml;
	}
	
	function getXML(){
		$row = $this->getRow();
		$xml.='<APPLICATION_CUSTOM ID="'.$row['ID'].'" Dockable="true">';
		$xml.=		'<APPNAME>'.$row["Denomination"].'</APPNAME>';
		$xml.=		'<TEMPLATE>'.$row["SecurityTemplate"].'</TEMPLATE>';
		$xml.=		'<VISUAL>'.$row["Icon"].'</VISUAL>';
		$xml.=		'<URL>'.$row["URL"].'</URL>';
		$xml.=		'<TYPE>'.$row["Type"].'</TYPE>';
		$xml.='</APPLICATION_CUSTOM>';
		return $xml;
	}
}