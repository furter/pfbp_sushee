<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_omnilinks.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");

 /* matching of a single Sushee object
Ex:

<OMNILINK type="comments">
	
-->	<CONTACT>
		...
	</CONTACT>
	
</OMNILINK>

*/

class OmnilinkMatchSusheeObject extends SusheeObject{
	var $xmlNode;
	var $includedIDs;
	var $excludedIDs;
	var $loaded = false;
	
	function OmnilinkMatchSusheeObject($xmlNode,$moduleID){
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
	
	function getModule(){
		return moduleInfo($this->moduleID);
	}
}

class OmnilinkElementMatch extends OmnilinkMatchSusheeObject{ 
	
	var $type = false;
	var $reverse = false;
	
	function OmnilinkElementMatch($xmlNode,$moduleID,$type=false,$reverse=false){
		$this->xmlNode = $xmlNode;
		
		$this->moduleID = $moduleID;
		$this->type = $type;
		$this->reverse = $reverse;
	}
	
	function execute(){
		// putting attributes in INFO, DESCRIPTIONS in DESCRIPTION, etc. --> canonicalizeNQL
		canonicalizeNQL($this->xmlNode->getDocument(),$this->xmlNode->getPath());
		
		$moduleTargetNodename = $this->xmlNode->nodename();
		$moduleTargetInfo = moduleInfo($moduleTargetNodename);
		
		$xml_str = '<SEARCH><'.$moduleTargetNodename.'>'.$this->xmlNode->toString("/*").'</'.$moduleTargetNodename.'><RETURN><NOTHING/></RETURN></SEARCH>';
		$small_xml = new XML( $xml_str );
		$dep_sql ='';
		// we apply preprocessing to have the extension boolean added
		$moduleTargetInfo->preProcess('SEARCH',false,$small_xml->getElement('/SEARCH'));
		
		$dep_rs = getResultSet($moduleTargetInfo,$small_xml,'/SEARCH[1]',$dep_sql);
		
		if($dep_rs){
			$this->includedIDs = array();
			while($row = $dep_rs->fetchRow()){
				$this->includedIDs[]=$row['ID'];
			}
			// if no result, putting a false element saying that no element is valid
			if(sizeof($this->includedIDs)==0){
				$this->emptyResult();
			}
		}else{
			$this->emptyResult();
		}
		$this->loaded = true;
		return $this->loaded;
	}
	
	
}

/* matching of a complete Omnilink : can match several elements inside (two different types of contact)
Ex:

--> <OMNILINK type="navigation" [operator="exist/none"]>
	
	<CONTACT>
		...
	</CONTACT>
	<CONTACT>
		...
	</CONTACT>
	
</OMNILINK>

*/
class OmnilinkMatch extends OmnilinkMatchSusheeObject{
	
	var $comma_implosion; // list of potential elements (result of the search on the elements inside the DEP node)
	var $target_condition; // whether there is a list of potential elements (if DEP node is empty, all elements are potential)
	
	// having a list of potential elements, this function verifies which elements also have a Omnilink of a specific deptype
	function executeOnType($type,$elementNode){
		
		// determining in which direction we have to search
		// normal is from the omnilinker to the multi elements
		// reverse is from the multi elements to the omnilinker
		if($this->getModule()->getID()==$type->getModule()->getID()){
			// normal mode
			$reverse = false;
		}else{
			$reverse = true;
		}
		if($this->xmlNode->getAttribute('mode') == 'reverse')
			$reverse = true;
		
		// conditions on the number of deps
		// <OMNILINK type="...">
		//		<object><HITS operator="LT/LT=/GT/GT=/=">number</HITS></object>
		// </OMNILINK>
		$hits_value = $elementNode->valueOf('HITS');
		$hits_condition = ($hits_value!==false);
		
		$sql = 'SELECT ';
		if($hits_condition){
			$sql.= 'COUNT( * ) AS hits,';
		}
		// Normal mode: from the omnilinker to the multi elements
		if(!$reverse){
			$sql.= ' omni.`'.$type->getOriginFieldname().'` AS eltID FROM `'.$type->getTablename().'` AS omni WHERE ';
			$sql.=' omni.`TypeID` = \''.$type->getID().'\' AND omni.`Activity` = 1 ';
			if($this->target_condition){
				$sql.=' AND omni.`'.$type->getTargetFieldname().'` IN ('.$this->comma_implosion.')';
			}
			
		}else{
		// Reverse mode : from the multi elements to the omnilinker
			$sql.= 'omni.`'.$type->getTargetFieldname().'` AS eltID FROM `'.$type->getTablename().'` AS omni WHERE ';
			$sql.=' omni.`TypeID` = \''.$type->getID().'\' AND omni.`Activity` = 1 ';
			if($this->target_condition){
				$sql.=' AND omni.`'.$type->getOriginFieldname().'` IN ('.$this->comma_implosion.')';
			}
		}
		if($hits_condition){
			$hits_operator = $elementNode->valueOf('HITS/@operator');
			switch($hits_operator){
				case 'NE':
				case '!=':
				case '<>':
					$sql_operator = '!=';
					break;
				case 'LT':
					$sql_operator = '<';
					break;
				case 'LT=':
					$sql_operator = '<=';
					break;
				case 'GT':
					$sql_operator = '>';
					break;
				case 'GT=':
					$sql_operator = '>=';
					break;
				default:
					$sql_operator = '=';
					
			}
			$sql.=' GROUP BY eltID HAVING hits '.$sql_operator.' \''.$hits_value.'\'';
		}
		
		// collecting the results
		$db_conn = db_connect();
		sql_log($sql);
		$dep_rs = $db_conn->execute($sql);
		if($dep_rs){
			while($row = $dep_rs->fetchRow()){
				$IDs[]=$row['eltID']; // eltID is an alias to the ID of the element
			}
			// if no result, putting a false element saying that no element is valid
			if(sizeof($IDs)==0){
				$IDs[] = -1;
			}
		}
		return $IDs;
	}
	
	function execute(){
		
		$type = $this->xmlNode->getAttribute('type');
		
		// pour tous les éléments (CONTACT/MEDIA), trouver des éléments correspondants et rassembler tous les éléments correspondants pour en faire une grande liste unique  
		
		$potentialElts = array(); // potential elements with or without the Omnilink
		$IDs = array(); // potential elements that satisfy the condition on the dependeny
		
		$elementNodes = $this->xmlNode->getElements('/*');
		if(sizeof($elementNodes)==0){
			// if no element in the OMNILINK node, adding a fake one, allowing to match any element
			$this->xmlNode->appendChild('<ELEMENT/>');
			$elementNodes = $this->xmlNode->getElements('/*');
		}
		foreach($elementNodes as $node){
			
			if($node->nodeName()=='ELEMENT'){ // any element
				$this->target_condition = false;
			}else{
				$matcher = new OmnilinkElementMatch($node,$this->moduleID,$type,$this->reverse);
				$potentialElts = $matcher->getElementsIncluded();

				$this->target_condition = sizeof($potentialElts)>0;
				$this->comma_implosion = implode(',',$potentialElts);
			}
			
			
			// we have the elements, now seeing which ones have Omnilink
			if($type){
				// <OMNILINK type="...">
				// 		<object>...</object>
				// </OMNILINK>
				$depType = sushee_OmnilinkType($type);
				$IDs = array_merge($IDs,$this->executeOnType($depType,$node));
			}else{
				// <OMNILINKS>
				// 		<object>...</object>
				// </OMNILINKS>
				// taking all deptypes going from the object handled to the type of object in the Omnilinks node
				$firstNode = $elementNodes[0];
				$targetModuleInfo = moduleInfo($firstNode->nodename());
				$types = new OmnilinkTypeSet($this->moduleID,$targetModuleInfo->getID());
				
				while($type = $types->next()){
					$IDs = array_merge($IDs,$this->executeOnType($type,$node));
				}
			}
		}
		
		
		$operator = $this->xmlNode->getxSusheeOperator();
		if( $operator == 'none' || $operator == 'not' || $operator == 'not_exist'){
			$this->excludedIDs = $IDs;
			$this->includedIDs = array();
		}else{
			$this->includedIDs = $IDs;
			$this->excludedIDs = array();
		}
		$this->loaded = true;
		return $this->loaded;
	}
}

/*
matching multiple Omnilink node

<OMNILINKS>
	
	<OMNILINK type="navigation">...</Omnilink>
	<OMNILINK type="content">...</Omnilink>

</OMNILINKS>

*/

class OmnilinksMatch extends OmnilinkMatchSusheeObject{
	
	
	function execute(){
		$user = new NectilUser();
		
		// pour tous les Omnilink, prendre les listes d'éléments inclus et prendre les éléments présents dans toutes les listes (AND!!!)
		$this->includedIDs = false;
		$this->excludedIDs = false;
		
		// nodes concerning Omnilinks
		$children = $this->xmlNode->getElements('OMNILINKS/OMNILINK');
		$children2 = $this->xmlNode->getElements('OMNILINKS[not(OMNILINK)]');
		$all_children = array_merge($children,$children2);
		
		foreach($all_children as $node){
			
			
			$matcher = new OmnilinkMatch($node,$this->moduleID);
			if(is_array($this->includedIDs)){ // already initialized : intersect with the previous == AND
				$this->includedIDs = array_intersect($this->includedIDs,$matcher->getElementsIncluded());
			}else{
				$this->includedIDs = $matcher->getElementsIncluded();
			}
			if(is_array($this->excludedIDs)){ // already initialized : merging with the previous (merging because excluded elements should be all excluded)
				$this->excludedIDs = array_merge($this->excludedIDs,$matcher->getElementsExcluded());
			}else{
				$this->excludedIDs = $matcher->getElementsExcluded();
			}
		}
		if(sizeof($this->includedIDs)==0 && sizeof($this->excludedIDs)==0){
			$this->includedIDs[] = -1;
		}
		$this->loaded = true;
		return $this->loaded;
	}
}

?>