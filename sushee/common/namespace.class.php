<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/namespace.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../common/application.class.php");
require_once(dirname(__FILE__)."/../common/susheesession.class.php");

class NamespaceCollection extends SusheeObject{
	
	var $vector = false;
	var $namespaces_str = false;
	
	function NamespaceCollection(){
		// if namespaces available in session, taking them in session
		$namespaces_str = Sushee_Session::getVariable($this->getSessionVarname());
		if($namespaces_str!==false){
			$this->namespaces_str = $namespaces_str;
		}
	}
	
	// loadding the namespaces from the database
	function load(){
		if(!$this->vector){
			$this->vector = new Vector();
			$db_conn = db_connect();
			$sql = 'SELECT `Namespace`,`URL` FROM `namespaces` WHERE `ID` > 1 AND `Activity` = 1'; // activity=0 means deleted and ID=1 is the template
			sql_log($sql);
			$rs = $db_conn->execute($sql);
			if($rs){
				while($row = $rs->FetchRow()){
					$this->vector->add($row['Namespace'],new SusheeNamespace($row['Namespace'],$row['URL']));
				}
			}
		}
	}
	
	function &next(){
		$this->load();
		if($this->vector){
			return $this->vector->next();
		}
		return false;
	}
	
	function getXMLHeader(){
		// if namespaces xml header already composed, returning it immediately
		if($this->namespaces_str!==false){
			return $this->namespaces_str;
		}
		// loadding the namespaces from the database
		$this->load();
		
		// composing the xml header
		//$namespaces_str = ' xmlns="http://sushee.com/xsushee"';
		$namespaces_str = '';

		while($nspace = $this->next()){
			$namespaces_str .= $nspace->getXMLHeader();
		}
		$this->namespaces_str = $namespaces_str;
		
		// saving the xmlheader in session
		Sushee_Session::saveVariable($this->getSessionVarname(),$namespaces_str);
		return $namespaces_str;
	}
	
	function getSessionVarname(){
		return 'NSXMLHeader';
	}
	
	function clearInSession(){
		Sushee_Session::clearVariable($this->getSessionVarname());
	}
}

class SusheeNamespace extends SusheeObject{
	var $name;
	var $url;
	var $ID;
	
	function SusheeNamespace($name=false,$url=false){
		$this->name = $name;
		$this->url = $url;
	}
	
	function getID(){
		if(!$this->ID){
			$this->load();
		}
		return $this->ID;
	}
	
	function setID($ID){
		$this->ID = $ID;
	}
	
	function getName(){
		if(!$this->name && $this->url){
			$this->load();
		}
		return $this->name;
	}
	
	function setName($name){
		$this->name = $name;
	}
	
	function getURL(){
		if(!$this->url && $this->name){
			$this->load();
		}
		return $this->url;
	}
	
	function setURL($url){
		$this->url = $url;
	}
	
	function exists(){
		if($this->getURL() && $this->getName()){
			return true;
		}
		return false;
	}
	
	function load(){
		if($this->name){
			$sql = 'SELECT `ID`,`Namespace`,`URL` FROM `namespaces` WHERE `Namespace` LIKE "'.$this->name.'"';
		}else if($this->url){
			$sql = 'SELECT `ID`,`Namespace`,`URL` FROM `namespaces` WHERE `URL` LIKE "'.$this->url.'"';
		}
		if($sql){
			$db_conn = db_connect();
			sql_log($sql);
			$row = $db_conn->getRow($sql);
			if($row){
				$this->setName($row['Namespace']);
				$this->setURL($row['URL']);
				$this->setID($row['ID']);
			}
		}
		
	}
	
	function getModules(){
		$db_conn = db_connect();
		$rs = $db_conn->Execute('SELECT `Denomination` FROM `modules` WHERE `Denomination` LIKE "'.$this->getName().':%" ORDER BY `Denomination`;');
        $modules = array();
        while( $module = $rs->FetchRow() ){
            $modules[] = moduleInfo($module['Denomination']);
        }
		return $modules;
	}
	
	function getApplications(){
		$db_conn = db_connect();
		$rs = $db_conn->Execute('SELECT `Denomination` FROM `applications` WHERE `Denomination` LIKE "'.$this->getName().':%" ORDER BY `Denomination`;');
        $applications = array();
        while( $app = $rs->FetchRow() ){
            $applications[] = new CustomApplication($module['Denomination']);
        }
		return $modules;
	}
	
	function delete(){
		$modules = $this->getModules();
		$count_modules = sizeof($modules);
		if($count_modules>0){
			return false;
		}
		$applications = $this->getApplications();
		$count_applications = sizeof($applications);
		if($count_applications>0){
			return false;
		}
		$sql = 'DELETE FROM `namespaces` WHERE `Namespace`="'.$this->getName().'";';
		$db_conn = db_connect();
		$db_conn->Execute($sql);

		return true;
	}

	function getXMLHeader(){
		if (strtolower($this->getName()) == 'sushee'){
			// special case for sushee - it's the native namespace
			$xml_header = '';
		}else{
			$xml_header = ' xmlns:'.strtoupper($this->getName()).'="'.$this->getURL().'"';
			$xml_header.= ' xmlns:'.strtolower($this->getName()).'="'.$this->getURL().'#lower"';
		}
		return $xml_header;
	}

	function register(){
		$sql = 'SELECT `ID`,`Namespace`,`URL` FROM `namespaces` WHERE `Namespace`="'.$this->getName().'"';
		$db_conn = db_connect();
		//sql_log($sql);
		$row = $db_conn->getRow($sql);
		if($row){
			return true;
		}else{
			$sql = 'INSERT INTO `namespaces`(`Namespace`,`URL`) VALUES("'.encodeQuote($this->getName()).'","'.encodeQuote($this->getURL()).'");';
			//sql_log($sql);
			$res = $db_conn->Execute($sql);
			return $res;
		}
	}
}

/* removes the xml namespace from a query, used in query_log to have readable shorter logs */
class sushee_NamespaceCleaner extends SusheeObject{

	function execute($str){
		$str = preg_replace('/ xmlns:[a-zAZ]*="[^"]*"/i','',$str);
		return $str;
	}
}