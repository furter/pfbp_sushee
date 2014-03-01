<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/metasearch.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");

class Sushee_xSusheeCritMatch extends SusheeObject{
	var $xmlNode;
	var $includedIDs;
	var $excludedIDs;
	var $loaded = false;
	
	function Sushee_xSusheeCritMatch($xmlNode,$moduleID){
		$this->xmlNode = $xmlNode;
		$this->moduleID = $moduleID;
	}
	
	function emptyResult(){
		$this->includedIDs = array(-1);
		$this->excludedIDs = array();
	}
	
	
	function getElementsIncluded(){
		if(!$this->loaded)
			$this->execute();
		return $this->includedIDs;
	}
	
	function getElementsExcluded(){
		if(!$this->loaded)
			$this->execute();
		return $this->excludedIDs;
	}
	
	function getNode(){
		return $this->xmlNode;
	}
}

/*

Manage a group of criterias and manage how they will be mixed or intersected together to give the final list of element IDs

*/
class Sushee_xSusheeCritGroup extends Sushee_Object{
	var $name;
	var $vector;
	var $operator;
	
	function Sushee_xSusheeCritGroup($name){
		$this->name = $name;
		$this->operator = 'OR';
		$this->loaded = false;
		$this->vector = array();
	}
	
	function getName(){
		return $this->name;
	}
	
	function setOperator($operator){
		$this->operator = $operator;
	}
	
	function add(&$nqlcrit){
		$this->vector[]=&$nqlcrit;
	}
	
	function execute(){
		$this->loaded = true;
		$includedIDs = false;
		$excludedIDs = false;
		foreach($this->vector as $nqlcrit){
			$crit_includedIDs = $nqlcrit->getElementsIncluded();
			$crit_excludedIDs = $nqlcrit->getElementsExcluded();
			//debug_log('group '.$this->getName().' adding '.implode(',',$crit_includedIDs).' matching node '.$nqlcrit->getNode()->toString());
			//debug_log('group '.$this->getName().' substracting '.implode(',',$crit_includedIDs).' matching node '.$nqlcrit->getNode()->toString());
			
			if($this->operator=='AND'){
				if($crit_includedIDs!==false){
					if($includedIDs!==false)
						$includedIDs = array_intersect($includedIDs,$crit_includedIDs);
					else
						$includedIDs = $crit_includedIDs;
				}else{ // using excludedIDs
					if($excludedIDs!==false)
						$excludedIDs = array_merge($excludedIDs,$crit_excludedIDs);
					else
						$excludedIDs = $crit_excludedIDs;
				}
			}else{
				if($crit_includedIDs!==false){
					if($includedIDs!==false)
						$includedIDs = array_merge($includedIDs,$crit_includedIDs);
					else
						$includedIDs = $crit_includedIDs;
				}else{ // using excludedIDs
					if($excludedIDs!==false)
						$excludedIDs = array_intersect($excludedIDs,$crit_excludedIDs);
					else
						$excludedIDs = $crit_excludedIDs;
				}
			}
		}
		if(sizeof($includedIDs)==0)
			$includedIDs[]=-1;
		$this->includedIDs = $includedIDs;
		$this->excludedIDs = $excludedIDs;
		
	}
	
	function getElementsIncluded(){
		if(!$this->loaded)
			$this->execute();
		return $this->includedIDs;
	}
	
	function getElementsExcluded(){
		if(!$this->loaded)
			$this->execute();
		return $this->excludedIDs;
	}
}



?>