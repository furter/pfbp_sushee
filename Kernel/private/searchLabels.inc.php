<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchLabels.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../private/metaSearch.inc.php');

class sushee_searchLabels extends RetrieveOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		$db_conn = db_connect();

		$languageID = $this->firstNode->valueOf('@languageID');
		if(!$languageID)
			$languageID = $GLOBALS["NectilLanguage"];

		// forcing the attributes to be transformed as node in INFO, WHERE to be removed, etc, to have a clean NQL request
		canonicalizeNQL($this->firstNode->getDocument(),$this->firstNode->getPath());

		$condition = ''; // all labels
		$search_nodes = $this->firstNode->getElements('INFO/*');

		// vector with nodenames and their matching fieldname
		$fieldnames = array('NAME'=>'Denomination','DENOMINATION'=>'Denomination','VALUE'=>'Text','TEXT'=>'Text');

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

		$sql = 'SELECT * FROM `labels` WHERE ';
		if($languageID!=='all'){
			$sql.='`LanguageID` IN ("'.$languageID.'","shared")';
		}else{
			$sql.='1 = 1';
		}
		$sql.=' '.$condition;
		sql_log($sql);
		$rs = $db_conn->Execute($sql);
		if ($rs){
			$attributes = $this->getOperationAttributes();
			
			$query_result='<RESULTS'.$attributes.'>';
			while($row = $rs->FetchRow()){
				$query_result.='<LABEL name="'.$row['Denomination'].'" languageID="'.$row['LanguageID'].'">';
				$query_result.=$row['Text'];
				$query_result.='</LABEL>';
			}
			$query_result.='</RESULTS>';
			
			$this->setXML($query_result);
			return true;
		}else{
			$this->setError("Problem with the sql query *".$sql."*.");
			return false;
		}
	}
}
?>
