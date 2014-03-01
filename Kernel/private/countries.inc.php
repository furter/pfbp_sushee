<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/countries.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once(dirname(__FILE__)."/../private/metasearch_datatypes.inc.php");
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');


class searchCountries extends RetrieveOperation{
	var $countryID = false;
	var $profile = false;
	var $languageID = false;
	
	function parse(){
		
		$this->languageID = $this->firstNode->getAttribute("languageID");
		$this->profile = $this->firstNode->getAttribute("profile");
		$this->countryID = $this->firstNode->getAttribute("ID");
		
		if(!$this->languageID){
			if (isset($GLOBALS["NectilLanguage"]) && $GLOBALS['restrict_language'])
				$this->languageID = $GLOBALS["NectilLanguage"];
			else
				$this->languageID = "eng";
			
		}
		if(substr($this->languageID,0,3)=='fre'){
			$this->languageID = 'fre';
		}
		
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		
		$db_conn = db_connect(TRUE); // connection to the commmon database
		
		if ($this->profile=="SmallList")
			$sql = "SELECT * FROM `countries` AS ct WHERE `SmallList`=1";
		else if($this->profile=="Europe")
			$sql = "SELECT * FROM `countries` AS ct WHERE `Europe`=1";
		else if($this->countryID)
			$sql = "SELECT * FROM `countries` AS ct WHERE `ID`='".encodeQuote($this->countryID)."'";
		else
			$sql = "SELECT * FROM `countries` AS ct";
		if($this->languageID=='eng' || $this->languageID=='fre' || $this->languageID=='dut')
			$sql.=' ORDER BY '.sql_removeaccents('ct',$this->languageID).'';
		sql_log($sql);
		$rs = $db_conn->Execute($sql);
		
		$xml.='<RESULTS'.$attributes.'>';
		if ($rs){
			while ($row = $rs->FetchRow() ){
				$xml.="<COUNTRY ID='".$row["ID"]."'  ISO2='".$row['ISOAlpha2']."'>";
				$xml.="<UNIVERSAL>".$row["universal"]."</UNIVERSAL>";
				if($this->languageID=='all'){
					$lgs = array('eng','fre','dut','spa','ita','por','ger');
					foreach($lgs as $lg){
						$xml.='<LABEL languageID="'.$lg.'">'.$row[$lg].'</LABEL>';
					}
				}else{
					$xml.="<LABEL>".$row[$this->languageID]."</LABEL>";
				}
				
				$xml.="</COUNTRY>";
			}
		}else{
			$this->setError("Problem getting the countries : the sql query failed.");
			return false;
		}
		
		$xml.="</RESULTS>";
		$this->xml = $xml;
		return true;
	}
}

?>
