<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_categories.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../common/metasearch.class.php");

class Sushee_CategoryBelonging extends Sushee_Object{
	var $ModuleTargetID;
	var $elementIDs;
	var $operator;
	var $ID;
	var $path;
	var $name;
	
	function Sushee_CategoryBelonging($ModuleTargetID){
		$this->ModuleTargetID = $ModuleTargetID;
		$this->operator = 'descendant';
		$this->loaded = false;
		$this->ID = false;
		$this->path = false;
		$this->name = false;
	}
	
	function setOperator($operator){
		if($operator=='!='){
			$operator='not';
		}
		$this->operator = $operator;
	}
	
	function getOperator(){
		return $this->operator;
	}
	
	function setID($ID){
		$this->ID = $ID;
	}
	
	function setPath($path){
		if(strpos($path,'*[')!==false){
			$db_conn = db_connect();
			$pos = strpos($path,'*[');
			$pos_end = strpos($path,']',$pos+1);
			$parent_path = substr($path,0,$pos);
			$index = substr($path,$pos+2,$pos_end-$pos-2);
			if(!is_numeric($index) || $index==0)
				$index = 1;
			$categ_collect = 'SELECT `ID` FROM `categories` WHERE `Path` = "'.encodeQuote($parent_path).'";';
			$categ_row = $db_conn->GetRow($categ_collect);
			$fatherID = $categ_row['ID'];
			$categ_collect = 'SELECT `Path` FROM `categories` WHERE `FatherID` = "'.$fatherID.'" LIMIT '.($index-1).',1;';
			$categ_row = $db_conn->GetRow($categ_collect);
			$path = $categ_row['Path'];
		}
		if(substr($path,-1)!='/')
			$path = $path.'/';
		$this->path = $path;
	}
	
	function setName($name){
		$this->name = $name;
	}
	
	function execute(){
		$this->loaded = true;
		$db_conn = db_connect();
		$categs_ok = array();
		if($this->ID){
			$categs_ok[]= $this->ID;
			if($this->operator=='descendant' || $this->operator=='not'){
				$categ_collect = 'SELECT `Path` FROM `categories` WHERE `ID` = "'.$this->ID.'";';
				$categ_row = $db_conn->GetRow($categ_collect);
				if($categ_row){
					$categ_collect = 'SELECT `ID` FROM `categories` WHERE `Path` LIKE "'.$categ_row['Path'].'%";';
					$categ_rs = $db_conn->Execute($categ_collect);
					while($categ_row = $categ_rs->FetchRow()){
						$categs_ok[]=$categ_row['ID'];
					}
				}
			}
		}else if($this->name){
			if($this->operator=='descendant' || $this->operator=='not'){
				$categ_collect = 'SELECT `ID` FROM `categories` WHERE `Path` LIKE "/%/'.encodeQuote($this->name).'/%";';
				$categ_rs = $db_conn->Execute($categ_collect);
				while($categ_row = $categ_rs->FetchRow()){
					$categs_ok[]=$categ_row['ID'];
				}
			}else{
				$categ_collect = 'SELECT `ID` FROM `categories` WHERE `Denomination` = "'.encodeQuote($this->name).'";';
				$categ_row = $db_conn->GetRow($categ_collect);
				if($categ_row){
					$categs_ok[]=$categ_row['ID'];
				}
			}
		}else if($this->path){
			if($this->operator=='descendant' || $this->operator=='not'){
				$categ_collect = 'SELECT `ID` FROM `categories` WHERE `Path` LIKE "'.encodeQuote($this->path).'%";';
				$categ_rs = $db_conn->Execute($categ_collect);
				while($categ_row = $categ_rs->FetchRow()){
					$categs_ok[]=$categ_row['ID'];
				}
			}else{
				$categ_collect = 'SELECT `ID` FROM `categories` WHERE `Path` = "'.$this->path.'";';
				$categ_row = $db_conn->GetRow($categ_collect);
				if($categ_row){
					$categs_ok[]=$categ_row['ID'];
				}
			}
		}
		$this->excludeIDs = false;
		$this->targetIDs = false;
		if(sizeof($categs_ok)>0){
			$categlinks_sql = 'SELECT `TargetID` FROM `categorylinks` WHERE `ModuleTargetID` =\''.$this->ModuleTargetID.'\'  AND `CategoryID` ';
			$categlinks_sql.=' IN ('.implode(',',$categs_ok).')';
			sql_log($categlinks_sql);
			$rs = $db_conn->Execute($categlinks_sql);
			while($row = $rs->FetchRow()){
				if($this->operator=='not'){
					$excludeIDs[]=$row['TargetID'];
				}else{
					$this->targetIDs[]=$row['TargetID'];
				}
			}
		}
		if($this->operator=='not'){
			if(sizeof($excludeIDs)==0)
				$excludeIDs[]=-1;
			// resolving the exclusion to rather have a list of inclusion instead
			$moduleInfo = moduleInfo($this->ModuleTargetID);
			$elements_sql = 'SELECT `ID` FROM `'.$moduleInfo->tableName.'` WHERE `ID` NOT IN('.implode(',',$excludeIDs).') AND `Activity`=1';
			$rs = $db_conn->Execute($elements_sql);
			while($row = $rs->FetchRow()){
				$this->targetIDs[]=$row['ID'];
			}
		}
			
		if( (sizeof($this->targetIDs)==0 || $this->targetIDs===false) && $this->operator!='not'){
			$this->targetIDs[]=-1; // because no match
		}
	}
	
	function getElementsIncluded(){
		if(!$this->loaded)
			$this->execute();
		return $this->targetIDs;
	}
	
	// fake function, not really used, but has to match the Sushee_xSusheeCritMatch interface
	function getElementsExcluded(){
		return false;
	}
}

class Sushee_CategoryMatch extends Sushee_Object{
	var $xmlNode;
	var $ModuleTargetID;
	
	function Sushee_CategoryMatch($xmlNode,$ModuleTargetID){
		$this->xmlNode = $xmlNode;
		$this->ModuleTargetID = $ModuleTargetID;
		$this->loaded = false;
	}
	
	function execute(){
		$this->loaded = true;
		$targetIDs = false;
		$excludeIDs = false;
		$db_conn = db_connect();

		$categ_path = './CATEGORY';
		if($this->xmlNode->exists($categ_path."[@or_group]") ){
			$between_groups_operator='AND';
			$grouping_attr = 'or_group';
			$in_group_operator='OR';
		}else{
			$between_groups_operator='OR';
			$grouping_attr = 'and_group';
			$in_group_operator='AND';
		}
		$categ_nodes = $this->xmlNode->getElements($categ_path);
		$groups = &new Vector();
		$i = 1;
		foreach($categ_nodes as $categ_node){
			$categ_belonging = &new Sushee_CategoryBelonging($this->ModuleTargetID);
			$operator = $categ_node->getxSusheeOperator();
			if($operator)
				$categ_belonging->setOperator($operator);
			$groupname = $categ_node->valueOf('@'.$grouping_attr);
			if(!$groupname){
				$groupname = 'nectil'.$i/*.$categ_belonging->getOperator()*/; // if no group is defined, one is created for each different nodes
				$i++;
			}
			if($groups->exists($groupname)){
				$categ_group = &$groups->getElement($groupname);
			}else{
				$categ_group = &new Sushee_xSusheeCritGroup($groupname);
				$categ_group->setOperator($in_group_operator);
				$groups->add($groupname,$categ_group);
			}
			
			$categ_name = $categ_node->valueOf('@name');
			$categID = $categ_node->valueOf('/@ID');
			$categ_path = $categ_node->valueOf('@path');
			if($categ_name){
				$categ_belonging->setName($categ_name);
				$categ_group->add($categ_belonging);
			}else if($categID){
				$categ_belonging->setID($categID);
				$categ_group->add($categ_belonging);
			}else if($categ_path){
				$categ_belonging->setPath($categ_path);
				$categ_group->add($categ_belonging);
			}else{
				// skipping because no indication on the category
			}

		}
		$groups->reset();
		while($categ_group = &$groups->next()){
			$group_targetIDs = $categ_group->getElementsIncluded();
			
			if($between_groups_operator=='AND'){
				if($group_targetIDs!==false){
					if($targetIDs!==false)
						$targetIDs = array_intersect($targetIDs,$group_targetIDs);
					else
						$targetIDs = $group_targetIDs;
				}
					
			}else{
				if($group_targetIDs!==false){
					if($targetIDs!==false)
						$targetIDs = array_merge($targetIDs,$group_targetIDs);
					else
						$targetIDs = $group_targetIDs;
				}
					
			}
		}
		if(sizeof($targetIDs)==0 && $groups->size()>0) // if there is group of category, and we have no matching element IDs, setting a -1 in the array
			$targetIDs[]=-1;
		$this->targetIDs = $targetIDs;
	}
	
	function getElementsIncluded(){
		if(!$this->loaded)
			$this->execute();
		return $this->targetIDs;
	}
}


function getElementWithCategoriesMatching(&$xml,$element_path,$moduleInfo){
	$categorymatch = new Sushee_CategoryMatch(new XMLNode($xml,$element_path),$moduleInfo->ID);
	return $categorymatch->getElementsIncluded();
}
?>