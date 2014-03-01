<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_ancestor.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/dependencies.class.php');

function tags_ANCESTOR(&$xml,$nodes_array,$moduleInfo){
	$childsIDs = array();
	foreach($nodes_array as $path){
		// foreach ANCESTOR, take all possible parents and put them in the array, because two ANCESTOR nodes is a OR
		$childs_array= tag_ANCESTOR($xml,$path,$moduleInfo);
		if(sizeof($childs_array)>0){
			$childsIDs = array_merge($childsIDs,$childs_array);
		}
	}
	return $childsIDs;
}



function tag_ANCESTOR(&$xml,$parentPath,$moduleInfo){
	$parentsIDs = array();
	$nodes_array = $xml->match($parentPath."/*");
	$allowed_depTypes = $xml->getData($parentPath."/@type");
	if($allowed_depTypes)
		$allowed_depTypes = explode(',',$allowed_depTypes);
	foreach($nodes_array as $path){
		$descendantNodename = $xml->nodeName($path);
		$ancestorModuleInfo = moduleInfo($descendantNodename);
		$sql = '';
		$small_xml = new XML('<SEARCH>'.$xml->toString($path).'<RETURN><NOTHING/></RETURN></SEARCH>');
		$ancestor_rs = getResultSet($ancestorModuleInfo,$small_xml,'/SEARCH[1]',$sql);
		if($ancestor_rs){
			$childs_array = getDescendants($moduleInfo,$ancestorModuleInfo,$ancestor_rs,$allowed_depTypes);
			if(sizeof($childs_array)>0){
				if(sizeof($childsIDs)>0)
				$childsIDs = array_intersect($childsIDs,$childs_array);
				else
				$childsIDs = $childs_array;
				if(sizeof($childsIDs)==0){
					break; // no need to search more, there will be no match
				}
			}else{
				$childsIDs = array();
			}
			// managing ANCESTOR-OR-SELF : including the medias from ancestor_rs in the array
			if($xml->nodeName($parentPath)=='ANCESTOR-OR-SELF'){
				// returning to the beginning of the set
				$ancestor_rs->MoveFirst();
				while($search_row = $ancestor_rs->FetchRow()){
					$ID = $search_row['ID'];
					$childsIDs[$ID] = $ID;
				}
			}
			
		}
	}
	return $childsIDs;
}
function getDescendants(&$descendantModuleInfo,&$ancestorModuleInfo,$where_rs,$allowed_depTypes = false){
	$childs = array(); // ancestors
	$childs_ok = array(); // ancestors fo which we have correctly queued the parents (we climb back parent by parent)
	$db_conn = db_connect();
	$depTypes = new DependencyTypeSet($ancestorModuleInfo->getID());
	while($search_row = $where_rs->FetchRow()){
		$depTypes->reset();
		while($dependencyType = $depTypes->next()){
			$deps_rs = getDependenciesFrom($ancestorModuleInfo->ID,$search_row['ID'],$dependencyType->getID());
			while($dep_row = $deps_rs->FetchRow()){
				$ok = true;
				// staying inside the depTypes allowed
				if($allowed_depTypes !=false && is_array($allowed_depTypes) && !in_array($dependencyType->name,$allowed_depTypes)){
					$ok = false;
				}
				if($ok){
					$childs[$ancestorModuleInfo->name.$dep_row['TargetID']]=array('ID'=>$dep_row['TargetID'],'moduleID'=>$dependencyType->ModuleTargetID);
				}
			}
		}
		
	}
	$elementInfo = array_shift($childs);
	while($elementInfo){
		$elementID = $elementInfo['ID'];
		$moduleInfo = moduleInfo($elementInfo['moduleID']);
		$ok_go_through = true;
		if ($GLOBALS["php_request"] && $moduleInfo->name=='media' && !($GLOBALS["take_unpublished"]===true)){
			$check_published_sql = 'SELECT `Published` FROM `'.$moduleInfo->tableName.'` WHERE `ID`='.$elementID.' AND `Published`=1';
			$media_row = $db_conn->GetRow($check_published_sql);
			if(!$media_row)
				$ok_go_through = false;
		}
		if($ok_go_through){
			$depTypes = new DependencyTypeSet($moduleInfo->getID());
			while($dependencyType = $depTypes->next()){
				$deps_rs = getDependenciesFrom($moduleInfo->ID,$elementID,$dependencyType->getID());
				while($dep_row = $deps_rs->FetchRow()){
					// staying inside the depTypes allowed
					$ok = true;
					if($allowed_depTypes !=false && is_array($allowed_depTypes) && !in_array($dependencyType->name,$allowed_depTypes)){
						$ok = false;
					}
					if($ok){
						$childModuleInfo = moduleInfo($dependencyType->ModuleTargetID);
						if(!isset($childs_ok[$childModuleInfo->name][$dep_row['TargetID']]) && !isset($childs[$childModuleInfo->name.$dep_row['TargetID']]) ){
							$childs[$childModuleInfo->name.$dep_row['TargetID']]=array('ID'=>$dep_row['TargetID'],'moduleID'=>$dependencyType->ModuleTargetID);
						}
					}
				}
			}
			
			$childs_ok[$moduleInfo->name][$elementID]=$elementID;
		}
		$elementInfo = array_shift($childs);
	}
	
	return $childs_ok[$descendantModuleInfo->name];
}

function getElementWithAncestorsMatching(&$xml,$element_path,$moduleInfo){
	$childsIDs = array();
	$excludeIDs = array();
	
	$nodes_array = $xml->match($element_path."/ANCESTOR[not(@operator) or @operator='exists']");
	$nodes_array = array_merge($nodes_array,$xml->match($element_path."/ANCESTOR-OR-SELF[not(@operator) or @operator='exists']"));
	$childsIDs = tags_ANCESTOR($xml,$nodes_array,$moduleInfo);
	if(sizeof($nodes_array)>0 && sizeof($childsIDs)==0)
		$childsIDs[]=-1;
	
	$nodes_array = $xml->match($element_path."/ANCESTOR[@operator='not_exists' or @operator='not']");
	$nodes_array = array_merge($nodes_array,$xml->match($element_path."/ANCESTOR-OR-SELF[@operator='not_exists' or @operator='not']"));
	$excludeIDs = tags_ANCESTOR($xml,$nodes_array,$moduleInfo);
	
	return array($childsIDs,$excludeIDs);
}

?>