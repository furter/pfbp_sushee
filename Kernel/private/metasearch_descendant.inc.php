<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_descendant.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function tags_DESCENDANT(&$xml,$nodes_array,$moduleInfo){
	$parentsIDs = array();
	foreach($nodes_array as $path){
		// foreach DESCENDANT, take all possible parents and put them in the arry, because two DESCENDANT nodes is a OR
		$parents_array= tag_DESCENDANT($xml,$path,$moduleInfo);
		if(sizeof($parents_array)>0){
			$parentsIDs = array_merge($parentsIDs,$parents_array);
		}
	}
	return $parentsIDs;
}



function tag_DESCENDANT(&$xml,$parentPath,$moduleInfo){
	$parentsIDs = array();
	$nodes_array = $xml->match($parentPath."/*");
	$allowed_depTypes = $xml->getData($parentPath."/@type");
	if($allowed_depTypes)
		$allowed_depTypes = explode(',',$allowed_depTypes);
	foreach($nodes_array as $path){
		$descendantNodename = $xml->nodeName($path);
		$descendantModuleInfo = moduleInfo($descendantNodename);
		$sql = '';
		$small_xml = new XML('<SEARCH>'.$xml->toString($path).'<RETURN><NOTHING/></RETURN></SEARCH>');
		$descendant_rs = getResultSet($descendantModuleInfo,$small_xml,'/SEARCH[1]',$sql);
		if($descendant_rs){
			$parents_array = getAncestors($moduleInfo,$descendantModuleInfo,$descendant_rs,$allowed_depTypes);
			if(sizeof($parents_array)>0){
				if(sizeof($parentsIDs)>0)
				$parentsIDs = array_intersect($parentsIDs,$parents_array);
				else
				$parentsIDs = $parents_array;
				if(sizeof($parentsIDs)==0){
					break; // no need to search more, there will be no match
				}
			}else{
				$parentsIDs = array();
			}
			// managing DESCENDANT-OR-SELF : including the medias from descendant_rs in the array
			if($xml->nodeName($parentPath)=='DESCENDANT-OR-SELF'){
				// returning to the beginning of the set
				$descendant_rs->MoveFirst();
				while($search_row = $descendant_rs->FetchRow()){
					$ID = $search_row['ID'];
					$parentsIDs[$ID] = $ID;
				}
			}
			
		}
	}
	return $parentsIDs;
}
function getAncestors(&$ancestorModuleInfo,&$descendantModuleInfo,$where_rs,$allowed_depTypes = false){
	$ancestors = array(); // ancestors
	$ancestors_ok = array(); // ancestors fo which we have correctly queued the parents (we climb back parent by parent)
	$db_conn = db_connect();
	$depTypes = new DependencyTypeSet(false,$descendantModuleInfo->getID());
	while($search_row = $where_rs->FetchRow()){
		$depTypes->reset();
		while($dependencyType = $depTypes->next()){
			$deps_rs = getDependenciesTo($descendantModuleInfo->ID,$search_row['ID'],$dependencyType->getID());
			while($dep_row = $deps_rs->FetchRow()){
				$ok = true;
				// staying inside the depTypes allowed
				if($allowed_depTypes !=false && is_array($allowed_depTypes) && !in_array($dependencyType->name,$allowed_depTypes)){
					$ok = false;
				}
				if($ok)
					$ancestors[$descendantModuleInfo->name.$dep_row['OriginID']]=array('ID'=>$dep_row['OriginID'],'moduleID'=>$dependencyType->ModuleOriginID);
			}
		}
		
	}
	$elementInfo = array_shift($ancestors);
	while($elementInfo){
		$elementID = $elementInfo['ID'];
		$moduleInfo = moduleInfo($elementInfo['moduleID']);
		$ok_go_through = true;
		if ($GLOBALS["php_request"] && $moduleInfo->name=='media' && !($GLOBALS["take_unpublished"]===true)){
			$check_published_sql = 'SELECT `Published` FROM '.$moduleInfo->tableName.' WHERE `ID`='.$elementID.' AND `Published`=1';
			$media_row = $db_conn->GetRow($check_published_sql);
			if(!$media_row)
				$ok_go_through = false;
		}
		if($ok_go_through){
			$depTypes = new DependencyTypeSet(false,$moduleInfo->getID());
			while($dependencyType = $depTypes->next()){
				$deps_rs = getDependenciesTo($moduleInfo->ID,$elementID,$dependencyType->getID());
				while($dep_row = $deps_rs->FetchRow()){
					// staying inside the depTypes allowed
					$ok = true;
					if($allowed_depTypes !=false && is_array($allowed_depTypes) && !in_array($dependencyType->name,$allowed_depTypes)){
						$ok = false;
					}
					if($ok){
						$parentModuleInfo = moduleInfo($dependencyType->ModuleOriginID);
						if(!isset($ancestors_ok[$parentModuleInfo->name][$dep_row['OriginID']]) && !isset($ancestors[$parentModuleInfo->name.$dep_row['OriginID']]) ){
							$ancestors[$parentModuleInfo->name.$dep_row['OriginID']]=array('ID'=>$dep_row['OriginID'],'moduleID'=>$dependencyType->ModuleOriginID);
						}
					}
				}
			}
			
			$ancestors_ok[$moduleInfo->name][$elementID]=$elementID;
		}
		$elementInfo = array_shift($ancestors);
	}
	
	return $ancestors_ok[$ancestorModuleInfo->name];
}

function getElementWithDescendantsMatching(&$xml,$element_path,$moduleInfo){
	$parentsIDs = array();
	$excludeIDs = array();
	
	// DESCENDANT OR DESCENDANT-OR-SELF: operator exists
	$nodes_array = $xml->match($element_path."/DESCENDANT[not(@operator) or @operator='exists']");
	$nodes_array = array_merge($nodes_array,$xml->match($element_path."/DESCENDANT-OR-SELF[not(@operator) or @operator='exists']"));
	$parentsIDs = tags_DESCENDANT($xml,$nodes_array,$moduleInfo);
	if(sizeof($nodes_array)>0 && sizeof($parentsIDs)==0)
		$parentsIDs[]=-1;
	
	// DESCENDANT OR DESCENDANT-OR-SELF: operator not
	$nodes_array = $xml->match($element_path."/DESCENDANT[@operator='not_exists' or @operator='not']");
	$nodes_array = array_merge($nodes_array,$xml->match($element_path."/DESCENDANT-OR-SELF[@operator='not_exists' or @operator='not']"));
	$excludeIDs = tags_DESCENDANT($xml,$nodes_array,$moduleInfo);
	
	return array($parentsIDs,$excludeIDs);
}

?>