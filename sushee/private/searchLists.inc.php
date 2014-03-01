<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchLists.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../private/metaSearch.inc.php');

class searchLists extends RetrieveOperation{
	
	
	var $languageID = false;
	var $domain = false;
	var $listname = false;
	
	function parse(){
		$languageID = $this->firstNode->valueOf('/@languageID');
		if(!$languageID)
			$languageID = $GLOBALS["NectilLanguage"];
		$this->languageID = $languageID;
			
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		
		
		// forcing the attributes to be transformed as node in INFO, WHERE to be removed, etc, to have a clean NQL request
		canonicalizeNQL($this->firstNode->getDocument(),$this->firstNode->getPath());
		
		$condition = ' 1 '; // all lists
		$search_nodes = $this->firstNode->getElements('INFO/*');
		
		// vector with nodenames and their matching fieldname
		$fieldnames = array('DOMAIN'=>'Domain','NAME'=>'Name','VALUE'=>'Value');
		
		// foreach nodes, adding a corresponding sql crit
		foreach($search_nodes as $node){
			// matching the nodename with the real fieldname
			$nodename = $node->nodename();
			$fieldname = false;
			if(isset($fieldnames[$nodename])){
				$fieldname = $fieldnames[$nodename];
				
				// handling operators
				$operator = $node->getxSusheeOperator();
				// user can add ' number' at the end of the operator to force the field to be treated as a number
				$casttonumber = false;
				if(substr($operator,-7)==' number'){
					$operator = substr($operator,0,-7);
					$casttonumber = '+0 ';
				}
				$value = encode_for_db($node->valueOf());
				
				// composing the sql
				$condition.=' AND `'.$fieldname.'`'.$casttonumber.' ';
				switch($operator){
					case '=':
						$condition.='= "'.$value.'"';
						break;
					case 'starts-with':
						$condition.='LIKE "'.$value.'%"';
						break;
					case 'ends-with':
						$condition.='LIKE "%'.$value.'"';
						break;
					case 'GT':
						$condition.='> "'.$value.'"';
						break;
					case 'GT=':
						$condition.='>= "'.$value.'"';
						break;
					case 'LT':
						$condition.='< "'.$value.'"';
						break;
					case 'LT=':
						$condition.='<= "'.$value.'"';
						break;
					default:
						$condition.='LIKE "%'.$value.'%"';
				}
			}
			
		}
			
		$sql = 'SELECT * FROM `lists` WHERE '.$condition;
		if($this->languageID!=='all'){
			$sql.=' AND `LanguageID` IN ("'.$this->languageID.'","","shared")';
		}
		$sql.=' ORDER BY `Name`,`LanguageID`,`Domain`,`Ordering`;';
		sql_log($sql);
		
		if($this->domain=='OfficityMobile')
			$db_conn = db_connect(true);
		else
			$db_conn = db_connect();
		
		$rs = $db_conn->Execute($sql);
		if ($rs){
			$currentDomain = false;
			$currentName = false;
			$first = true;
			while($row = $rs->FetchRow()){
				$rowDomain = $row['Name'];
				$rowName = $row['Domain'];
				$rowLanguage = $row['LanguageID'];
				if($rowDomain!=$currentDomain || $rowName!=$currentName || $rowLanguage!=$currentLanguage){
					$currentDomain = $rowDomain;
					$currentName = $rowName;
					$currentLanguage = $rowLanguage;
					if(!$first)
						$xml.='</LIST>';
					$xml.='<LIST name="'.encode_to_xml($rowDomain).'" domain="'.encode_to_xml($rowName).'"';
					if($this->languageID=='all'){
						$xml.=' languageID="'.$rowLanguage.'"';
					}
					$xml.='>';
					$first = false;
				}
				$xml.='<ITEM label="'.encode_to_xml($row['Label']).'" value="'.encode_to_xml($row['Value']).'"/>';
			}
			if(!$first)
				$xml.='</LIST>';
		}
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
}
?>
