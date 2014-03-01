<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/languages.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_SearchLanguages extends RetrieveOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		$attributes = $this->getOperationAttributes();
		
		$query_result='<RESULTS '.$attributes.'>';
		$db_conn = db_connect(TRUE);
		$profile = $this->firstNode->valueOf("/@profile");
		
		$languageID = $this->firstNode->valueOf("/@languageID");
		if(!$languageID){
			if (isset($GLOBALS["NectilLanguage"]) && $GLOBALS['restrict_language'])
				$languageID = $GLOBALS["NectilLanguage"];
			else
				$languageID = "eng";
		}
		if(substr($languageID,0,3)=='fre'){
			$languageID = 'fre';
		}
		if ($profile == "System"){
			$sql = "SELECT DISTINCT lang.* FROM `languages` AS lang, `systemlanguages` AS sys WHERE sys.`languageID`=lang.`ID`";
		}else if ($profile == "Media"){
			$sql = "SELECT lang.*,publis.`published`,publis.`priority` FROM `".$GLOBALS["generic_backoffice_db"]."`.`languages` AS lang, `".$GLOBALS['db_name']."`.`medialanguages` AS publis WHERE publis.`languageID`=lang.`ID` ORDER BY publis.`priority`;";
		}else if ($profile == "LargeList")
			$sql = "SELECT * FROM `languages` WHERE `ISO1`!=\"\";";
		else if ($profile == "SmallList")
			$sql = "SELECT * FROM `languages` WHERE `SmallList`='1';";
		else if($this->firstNode->valueOf('@ID')){
			$sql = "SELECT * FROM `languages` WHERE `ID`='".encode_for_db($this->firstNode->valueOf('@ID'))."';";
		}else{
			$sql = "SELECT * FROM `languages`;";
		}
		sql_log($sql);
		$rs = $db_conn->Execute($sql);

		if ($rs){
			while ($row = $rs->FetchRow() ){
				if(isset($row['priority']) && $row['priority']==1)
					$attributes=" defaultLanguage='true' ";
				else
					$attributes='';
				$query_result.="<LANGUAGE ID='".$row["ID"]."' $attributes >";
				$query_result.="<UNIVERSAL>".$row["universal"]."</UNIVERSAL>";
				//$query_result.="<LABEL>".$row[$languageID]."</LABEL>";
				if($languageID=='all'){
					$lgs = array('afr','alb','amh','ara','arm','baq','bel','ben','bos','bre','bul','spa','cat','scr','cze','dan','dut','eng','epo','est','fao','fin','fre','gla','glg','geo','ger','gre','guj','hau','heb','hin','hun','ice','ind','ina','gle','ita','jpn','kan','kaz','khm','kor','kur','lat','lav','lin','lit','mac','mlg','may','mal','mlt','mar','mol','nep','nor','nno','oci','ori','orm','pan','per','pol','por','rum','rus','san','scc','sna','snd','sin','slo','slv','som','swa','swe','tgl','tam','tat','tel','tha','tur','twi','uig','ukr','urd','vie','wel','yor','por_bra','por_prt','chi_chn','chi_twn');
					foreach($lgs as $lg){
						if($row[$lg]){
							$query_result.='<LABEL languageID="'.$lg.'">'.$row[$lg].'</LABEL>';
						}
					}
				}else{
					$query_result.="<LABEL>".$row[$languageID]."</LABEL>";
				}
				$query_result.="<ISO1>".$row['ISO1']."</ISO1>";
				if (isset($row["published"]))
				$query_result.="<PUBLISHED>".$row["published"]."</PUBLISHED>";
				$query_result.="</LANGUAGE>";
			}
		}else{
			$this->setError("Problem getting the languages : the sql query failed.");
			return false;
		}
		$query_result.="</RESULTS>";
		
		$this->setXML($query_result);
		
		return true;
	}
	
}
?>
