<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getdays.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class getDays extends NQLOperation{
	var $start = 0;
	var $end = 6;
	var $step = 1;
	var $languageID = false;
	function parse(){
		$start = $this->firstNode->valueOf('START');
		$end = $this->firstNode->valueOf('END');
		$step = $this->firstNode->valueOf('STEP');
		$languageID = $this->firstNode->valueOf('@languageID');
		if(!$languageID){
			if (isset($GLOBALS["NectilLanguage"]) && $GLOBALS['restrict_language'])
				$languageID = $GLOBALS["NectilLanguage"];
		}
		if($start===false || $start===''){
			$start = 1;
		}
		if($end===false || $end===''){
			$end = 7;
		}
		$this->start = $start;
		$this->end = $end;
		if($step)
			$this->step = $step;
		if($languageID)
			$this->languageID = $languageID;
		return true;
	}
	
	function getItem($index){
		$db_conn = db_connect(true);
		$php_index = $index;
		if($php_index==7)
			$php_index = 0;
		$xml ='<DAY ID="'.$php_index.'">';
		if($this->languageID){
			$sql = 'SELECT * FROM `days` WHERE `ID` = \''.($index - 1).'\' AND `LanguageID`="'.encodequote($this->languageID).'"';
		}else{
			$sql = 'SELECT * FROM `days` WHERE `ID` = \''.($index - 1).'\'';
		}
		$rs = $db_conn->Execute($sql);
		while($row = $rs->FetchRow()){
			$xml.=	'<LABEL languageID="'.encode_to_xml($row['LanguageID']).'">'.$row['Label'].'</LABEL>';
			$xml.=	'<SHORT languageID="'.encode_to_xml($row['LanguageID']).'">'.$row['Short'].'</SHORT>';
			$abbrev = $row['Short'];
			if($row['Dot']==1){
				$abbrev.='.';
			}
			$xml.=	'<ABBREV languageID="'.encode_to_xml($row['LanguageID']).'">'.$abbrev.'</ABBREV>';
		}
		$xml.='</DAY>';
		return $xml;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		
		$xml.='<RESULTS'.$attributes.'>';
		for($i=$this->start;$i<=$this->end;$i+=$this->step){
			$xml.=		$this->getItem($i);
		}
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
	
	function getXML(){
		return $this->xml;
	}
	
	
}
?>