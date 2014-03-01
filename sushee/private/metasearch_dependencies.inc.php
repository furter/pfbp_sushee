<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_dependencies.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/metasearch.class.php");

 /* matching of a single Sushee object
Ex:

<DEPENDENCY type="navigation">
	
-->	<CONTACT>
		...
	</CONTACT>
	
</DEPENDENCY>

*/

class Sushee_DependencyElementMatch extends Sushee_xSusheeCritMatch{ 
	
	var $type = false;
	var $reverse = false;
	
	function Sushee_DependencyElementMatch($xmlNode,$moduleID,$type=false,$reverse=false){
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
		
		if($this->type){
			// forcing the elements inside to the nodename of the target object of the deptype
			$depType = depType($this->type);
			if($this->reverse){
				$moduleTargetInfo = $depType->getModuleOrigin();
			}else{
				$moduleTargetInfo = $depType->getModuleTarget();
			}
			
			$moduleTargetNodename = strtoupper($moduleTargetInfo->getName());
		}
		$xml_str = '<SEARCH><'.$moduleTargetNodename.'>'.$this->xmlNode->toString("/*[name()!='DEPINFO' and name()!='COMMENT']").'</'.$moduleTargetNodename.'><RETURN><NOTHING/></RETURN></SEARCH>';
		$small_xml = new XML( $xml_str );
		$dep_sql ='';
		// we apply preprocessing to have the extension boolean added
		$former_values = $new_values = $return_values = array();
		$moduleTargetInfo->preProcess('SEARCH',false,$small_xml->getElement('/SEARCH'),$former_values,$new_values,$return_values);
		
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

/* matching of a complete dependency : can match several elements inside (two different types of contact)
Ex:

--> <DEPENDENCY type="navigation" [operator="exist/none"]>
	
	<CONTACT>
		...
	</CONTACT>
	<CONTACT>
		...
	</CONTACT>
	
</DEPENDENCY>

*/
class Sushee_DependencyMatch extends Sushee_xSusheeCritMatch{
	
	var $reverse; // if the dep exists in its original direction, or in the inverse direction
	var $comma_implosion; // list of potential elements (result of the search on the elements inside the DEP node)
	var $target_condition; // whether there is a list of potential elements (if DEP node is empty, all elements are potential)
	
	// having a list of potential elements, this function verifies which elements also have a dependency of a specific deptype
	function executeOnDepType($depType,$elementNode){
		
		// conditions on the number of deps
		// <DEPENDENCY type="...">
		//		<object><HITS operator="LT/LT=/GT/GT=/=">number</HITS></object>
		// </DEPENDENCY>
		$hits_value = $elementNode->valueOf('HITS');
		$hits_condition = ($hits_value!==false);
		
		$sql = 'SELECT ';
		if($hits_condition){
			$sql.= 'COUNT( * ) AS hits,';
		}
		// Normal mode: from the origin to the target
		if(!$this->reverse){
			$sql.= ' dep.`'.$depType->getOriginFieldname().'` AS eltID FROM `'.$depType->getTablename().'` AS dep WHERE ';
			$sql.=' dep.`DependencyTypeID` = \''.$depType->getIDInDatabase().'\'';
			if($this->target_condition){
				$sql.=' AND dep.`'.$depType->getTargetFieldname().'` IN ('.$this->comma_implosion.')';
			}
			
		}else{
		// Reverse mode : from the target to the origin
			$sql.= 'dep.`'.$depType->getTargetFieldname().'` AS eltID FROM `'.$depType->getTablename().'` AS dep WHERE ';
			$sql.=' dep.`DependencyTypeID` = \''.$depType->getIDInDatabase().'\'';
			if($this->target_condition){
				$sql.=' AND dep.`'.$depType->getOriginFieldname().'` IN ('.$this->comma_implosion.')';
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
		// Conditions on DEPINFO and COMMENT
		$depCompl_array = array('DEPINFO'=>'DepInfo','COMMENT'=>'Comment');
		$depCompl_nosecurity = true; // put in a variable because its passed to the function by reference
		$varname = 'dep';
		$depCompl_string = tag_INFO($this->xmlNode->getDocument(),$elementNode->getPath(), $depCompl_array , $varname , $depCompl_nosecurity , $depType->getTablename() );
		if($depCompl_string){
			$sql.=' AND ('.$depCompl_string.')';
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
		// @type can be a comma separated list of deptype. e.g. type="navigation,content"
		$typeAttr = $this->xmlNode->getAttribute('type');
		$types = explode(',',$typeAttr);
		$this->reverse = ($this->xmlNode->getAttribute('mode') == 'reverse');
		
		// pour tous les éléments (CONTACT/MEDIA), trouver des éléments correspondants et rassembler tous les éléments correspondants pour en faire une grande liste unique  
		
		$potentialElts = array(); // potential elements with or without the dependency
		$IDs = array(); // potential elements that satisfy the condition on the dependeny
		
		$elementNodes = $this->xmlNode->getElements('/*');
		if(sizeof($elementNodes)==0){
			// if no element in the dependency node, adding a fake one, allowing to match any element
			$this->xmlNode->appendChild('<ELEMENT/>');
			$elementNodes = $this->xmlNode->getElements('/*');
		}
		foreach($types as $type){
			foreach($elementNodes as $node){


				$matcher = new Sushee_DependencyElementMatch($node,$this->moduleID,$type,$this->reverse);
				$potentialElts = $matcher->getElementsIncluded();

				$this->target_condition = sizeof($potentialElts)>0;
				$this->comma_implosion = implode(',',$potentialElts);

				// we have the elements, now seeing which one have dependency
				if($type){
					// <DEPENDENCY type="...">
					// 		<object>...</object>
					// </DEPENDENCY>
					$depType = depType($type);
					if(!$depType->loaded){
						throw new SusheeException('Deptype `'.$type.'` unknown');
					}
					$IDs = array_merge($IDs,$this->executeOnDepType($depType,$node));
				}else{
					// <DEPENDENCIES>
					// 		<object>...</object>
					// </DEPENDENCIES>
					// taking all deptypes going from the object handled to the type of object in the DEPENDENCIES node
					$firstNode = $elementNodes[0];
					$targetModuleInfo = moduleInfo($firstNode->nodename());
					$depTypes = new DependencyTypeSet($this->moduleID,$targetModuleInfo->getID());

					while($depType = $depTypes->next()){
						$IDs = array_merge($IDs,$this->executeOnDepType($depType,$node));
					}
				}
			}
		}
		
		
		
		
		$operator = $this->xmlNode->getxSusheeOperator();
		if( $operator == 'none' || $operator == 'not' || $operator == 'not_exist'){
			$this->excludedIDs = $IDs;
			$this->includedIDs = false;
		}else{
			$this->includedIDs = $IDs;
			$this->excludedIDs = false;
		}
		$this->loaded = true;
		return $this->loaded;
	}
}

/*
matching multiple dependency node

<DEPENDENCIES>
	
	<DEPENDENCY type="navigation">...</DEPENDENCY>
	<DEPENDENCY type="content">...</DEPENDENCY>

</DEPENDENCIES>

*/

class Sushee_DependenciesMatch extends Sushee_xSusheeCritMatch{
	
	// new execute with and_group and or_group management
	function execute(){
		$includedIDs = false;
		$excludedIDs = false;
		$db_conn = db_connect();

		if($this->xmlNode->exists("DEPENDENCIES[@or_group]") || $this->xmlNode->exists("DEPENDENCY[@or_group]")){
			$between_groups_operator='AND';
			$grouping_attr = 'or_group';
			$in_group_operator='OR';
		}else{
			$between_groups_operator='OR';
			$grouping_attr = 'and_group';
			$in_group_operator='AND';
		}
		$children = $this->xmlNode->getElements('DEPENDENCIES/DEPENDENCY');
		$children2 = $this->xmlNode->getElements('DEPENDENCIES[not(DEPENDENCY)]');
		$all_children = array_merge($children,$children2);
		$groups = &new Vector();
		$i = 1;
		foreach($all_children as $node){
			$dep_crit = &new Sushee_DependencyMatch($node,$this->moduleID);
			
			$groupname = $node->valueOf('@'.$grouping_attr);
			if(!$groupname){
				$groupname = 'nectil'.$i/*.$matcher->getOperator()*/; // if no group is defined, one is created for each different nodes
				$i++;
			}
			if($groups->exists($groupname)){
				$group = &$groups->getElement($groupname);
			}else{
				$group = &new Sushee_xSusheeCritGroup($groupname);
				$group->setOperator($in_group_operator);
				$groups->add($groupname,$group);
			}
			
			$group->add($dep_crit);

		}
		$groups->reset();
		while($group = &$groups->next()){
			$group_includedIDs = $group->getElementsIncluded();
			$group_excludedIDs = $group->getElementsExcluded();
			//debug_log('group '.$group->getName().' includes '.implode(',',$group_includedIDs));
			//debug_log('group '.$group->getName().' excludes '.implode(',',$group_excludedIDs));
			if($between_groups_operator=='AND'){
				if($group_includedIDs!==false){
					if($includedIDs!==false)
						$includedIDs = array_intersect($includedIDs,$group_includedIDs);
					else
						$includedIDs = $group_includedIDs;
				}
				if($group_excludedIDs!==false){// using excludedIDs
					if($excludedIDs!==false)
						$excludedIDs = array_merge($excludedIDs,$group_excludedIDs);
					else
						$excludedIDs = $group_excludedIDs;
				}
					
			}else{
				if($group_includedIDs!==false){
					if($includedIDs!==false)
						$includedIDs = array_merge($includedIDs,$group_includedIDs);
					else
						$includedIDs = $group_includedIDs;
				}
				if($group_excludedIDs!==false){// using excludedIDs
					if($excludedIDs!==false)
						$excludedIDs = array_intersect($excludedIDs,$group_excludedIDs);
					else
						$excludedIDs = $group_excludedIDs;
				}
					
			}
		}
		if(sizeof($includedIDs)==0 && $groups->size()>0) // if there is group of category, and we have no matching element IDs, setting a -1 in the array
			$includedIDs[]=-1;
		$this->includedIDs = $includedIDs;
		$this->excludedIDs = $excludedIDs;
	}
}

?>