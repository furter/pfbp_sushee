<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchCategories.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/categories.inc.php');

class searchCategories extends RetrieveOperation{
	var $totalElements;
	var $depth;
	var $languageID;
	var $sql;
	
	function parseProfile(){
		if($this->operationNode->exists('RETURN')){
			$profileNode = $this->operationNode->getElement('RETURN');
		}else{
			$profileNode = $this->firstNode->getElement('WITH');
		}
		$this->languageID = $this->firstNode->valueOf('/@languageID');
		if($profileNode){
			$this->languageID = $profileNode->valueOf('/@languageID');
			
			$this->depth = $profileNode->valueOf('@depth');
			if(!$this->depth)
				$this->depth=1;
			$this->totalElements = $profileNode->valueOf('@totalElements');
			if($this->totalElements!=='true')
				$this->totalElements = false;
		}
		if ($GLOBALS["php_request"] === true && $GLOBALS["restrict_language"] && $this->languageID == false)
			$this->languageID = $_SESSION[$GLOBALS["nectil_url"]]["language"];
		if($this->languageID==='all')
			$this->languageID = false;
	}
	
	function parse(){
		$this->parseProfile();
		$select_sql = 'SELECT * FROM `categories`';
		$path = $this->firstNode->valueOf('@path');
		if($path){
			if($this->firstNode->nodeName()=='CATEGORY'){
				$sql = ' WHERE Activity=1 AND Path="'.$path.'"';
			}else{
				$fatherID = resolveCategPath($path);
				if ($fatherID!=false){
					$sql = ' WHERE Activity=1 AND FatherID='.$fatherID.'';
				}else{
					// it might be the /contact/ or /media/ case
					$explosion = explode('/',$path);
					$moduleInfo = moduleInfo($explosion[1]);
					if($explosion[1]=='generic'){
						$sql = ' WHERE `Activity`=1 AND `ModuleID` = \'0\' AND `FatherID`=\'0\'';
					}elseif(sizeof($explosion)<=3 && $explosion[0]=='' && $explosion[2]=='' && $moduleInfo->loaded){
						$sql = ' WHERE `Activity`=1 AND `ModuleID` = \''.$moduleInfo->getID().'\' AND `FatherID`=0';
					}else{
						$sql = ' WHERE `ID`=-1';
					}
				}
			}
		}else{
			$ID = $this->firstNode->valueOf('@ID');
			if($ID!=false){
				$sql = ' WHERE Activity=1 AND ID='.$ID.'';
			}else{
				$fatherID = $this->firstNode->valueOf('@fatherID');
				if ($fatherID!==false){
					$sql = ' WHERE Activity=1 AND FatherID='.$fatherID.'';
				}else{
					$father = $this->firstNode->valueOf('FATHERNAME');
					if($father!==false){
						$sql = ' WHERE Activity=1 AND Path LIKE "%/'.$father.'/%" AND Denomination!="'.$father.'"';
					}else{
						$name = $this->firstNode->valueOf('UNIQUENAME');
						if(!$name){
							$name = $this->firstNode->valueOf('@name');
						}
						if($name!==false){
							$sql = ' WHERE Denomination="'.$name.'"';
						}else{
							$fulltext = $this->firstNode->valueOf('FULLTEXT');
							if(!$fulltext){
								$fulltext = $this->firstNode->valueOf('LABEL');
							}
							if($fulltext){
								$db_conn = db_connect();
								include_once(dirname(__FILE__).'/../private/metasearch_datatypes.inc.php');
								$remove_accents_sql = sql_removeaccents('trads','Text');
								$fulltext = removeaccents($fulltext);
								$trad_sql = 'SELECT `CategoryID` FROM `categorytraductions` AS trads WHERE '.$remove_accents_sql.' LIKE "%'.encode_for_db($fulltext).'%"';
								if($this->languageID){
									$trad_sql.=' AND trads.`LanguageID`="'.encode_for_db($this->languageID).'"';
								}
								sql_log($trad_sql);
								$rs = $db_conn->execute($trad_sql);
								if($rs){
									$sql = ' WHERE `ID` IN (';
									$first = true;
									while($row = $rs->FetchRow()){
										if(!$first)
											$sql.=',';
										$sql.='\''.$row['CategoryID'].'\'';
										$first = false;
									}
									if($first){// no match found in categorytraductions --> no matching categs
										$sql.='-1';
									}
									$sql.=')';

								}
							}else{
								if($this->firstNode->nodeName()=='CATEGORY'){
									$sql = ' WHERE `ID`=-1;';
								}else{
									$sql = ' WHERE Activity=1';
								}
							}

						}
					}
				}
			}
		}
		$sortNode = $this->operationNode->getElement('SORT');
		if($sortNode){
			$select = $sortNode->valueOf('@select');
			switch($select){
				case '@ID':
					$order_sql = ' ORDER BY `ID`';
					break;
				case '@path':
					$order_sql = ' ORDER BY `Path`';
					break;
				case 'UNIQUENAME':
					$order_sql = ' ORDER BY `Denomination`';
					break;
				case '@fatherID':
						$order_sql = ' ORDER BY `FatherID`';
						break;
				case 'LABEL':
					if($this->languageID){
						$select_sql.= ' LEFT JOIN `categorytraductions` AS trad ON (trad.`CategoryID`=`ID` )';
						$sql.= ' AND trad.`LanguageID`="'.$this->languageID.'"';
						$order_sql = ' ORDER BY trad.`Text`';
					}
					break;
				default:
			}
			if($order_sql){
				$order = $sortNode->valueOf('@order');
				switch($order){
					case 'descending':
						$order_sql.=' DESC';
						break;
					default:
						$order_sql.=' ASC';
				}
			}
		}
		$this->sql = $select_sql.$sql.$order_sql;
		
		
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		$db_conn = db_connect();
		sql_log($this->sql);
		$rs = $db_conn->Execute($this->sql);
		//$this->sysconsole->addMessage($this->sql); deprecated
		if ($rs){
			while($row = $rs->FetchRow()){
				$xml.=generateCategoryXML($row,$this->languageID,'html',$this->depth,$this->totalElements);
			}
		}else{
			$this->setError("Problem getting the categories : the sql query failed.");
			return false;
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
