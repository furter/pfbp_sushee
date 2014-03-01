<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/duplicate.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once(dirname(__FILE__)."/../private/create.nql.php");
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
require_once(dirname(__FILE__)."/../private/update.inc.php");
require_once(dirname(__FILE__)."/../common/dependencies.inc.php");
require_once(dirname(__FILE__)."/../common/dependencies.class.php");


function duplicate($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	
	$moduleInfo = moduleInfo($firstNode);
	if ($moduleInfo->loaded==FALSE){
		return generateMsgXML(1,"The informations about the module couldn't be found.",0,0,$name);
	}
	
	$IDs_original = $xml->getData($firstNodePath.'/@ID');
	if ($IDs_original==FALSE){
		// trying to find a WHERE node
		if( $xml->match($firstNodePath."/WHERE[1]") ){
			$where_sql = "";
			$where_rs = getResultSet($moduleInfo,$xml,$firstNodePath."/WHERE[1]",$where_sql);
			if (is_string($where_rs))
				return $where_rs;
			if (!$where_rs)
				return generateMsgXML(1,encode_to_xml($db_conn->ErrorMsg()).encode_to_xml($where_sql),0,'',$name);
			$IDs_string="";
			$first = true;
			while($search_row = $where_rs->FetchRow()){
				if($first!=true)
				$IDs_original.=",";
				$IDs_original.=$search_row['ID'];
				$first = false;
			}
			if ($first==true)
				return generateMsgXML(1,"The search hasn't given any result -> no deletion has been processed.",0,'',$name);
		}else{
			$query_result = generateMsgXML(1,"No ID were set -> no duplication has been processed.",0,'',$name);
			return $query_result;
		}
	}
	$IDs_array = explode(",",$IDs_original);
	$IDs_string="";
	$first = true;
	
	$profile = array('profile_name'=>'complete');
	$SAVE['restrict_language'] = $GLOBALS['restrict_language'];
	$GLOBALS['restrict_language'] = false;
	$IDs_list = false;
	while($elementID = array_pop($IDs_array)){
		if($first!=true)
			$IDs_string.=",";
		// now duplicate, deeply, with all services
		$duplicated = array();
		$moduleName = $moduleInfo->name;
		$first_media = array('module'=>$moduleName,'ID'=>$elementID,'depth'=>1,'parents'=>array());
		$to_duplicate = array();
		$duplicated = array();
		$media_to_duplicate = $first_media;
		$i = 0;
		
		
		while($media_to_duplicate){
			
			$to_duplicate_moduleName = $media_to_duplicate['module'];
			
			$moduleInfo = moduleInfo($to_duplicate_moduleName);
			$media_to_duplicate_row = getInfo($moduleInfo,$media_to_duplicate['ID']);
			if(!$media_to_duplicate_row){
				return generateMsgXML(1,"This element `".$to_duplicate_moduleName."`(".$media_to_duplicate['ID'].") doesnt exist.",0,'',$name);
			}
			$new_element_str = '<QUERY><CREATE>'.generateXMLOutput($media_to_duplicate_row,$moduleInfo,$profile,1,$IDs_list,true).'</CREATE></QUERY>';
			
			$new_element_xml = new XML($new_element_str);
			// removing the ID of the element, and of the descriptions
			$new_element_xml->removeAttribute('/QUERY[1]/CREATE[1]/*[1]','ID');
			$new_element_xml->setAttribute('/QUERY[1]/CREATE[1]/*[1]','if-exists','skip');
			$new_element_xml->removeChild('/QUERY[1]/CREATE[1]/*[1]/INFO[1]/ID[1]', TRUE);
			$new_element_xml->removeAttribute('/QUERY[1]/CREATE[1]/*[1]/DESCRIPTIONS/DESCRIPTION','ID');
			$new_element_xml->removeChild('/QUERY[1]/CREATE[1]/*[1]/DESCRIPTIONS[1]/DESCRIPTION/ID', TRUE);
			
			//debug_log($new_element_xml->toString());
			
			$nqlOp = new createElement('duplicate-'.$media_to_duplicate['ID'],new XMLNode($new_element_xml,'/QUERY[1]/CREATE[1]'));
			$create_result = $nqlOp->execute();
			$new_element_ID = $nqlOp->getID();
			
			//debug_log($media_to_duplicate['ID'].' duplicate to '.$new_element_ID);
			
			if($media_to_duplicate['depth']==1)
				$IDs_string.=$new_element_ID;
			
			// now creating the dependencies
			$media_to_duplicate['newID']=$new_element_ID;
			foreach($media_to_duplicate['parents'] as $dependencyTypeID=>$elements){
				foreach($elements as $originID=>$dep_row){
					//debug_log('attaching '.$originID.' to '.$media_to_duplicate['newID']);
					$dep = new Dependency(depType($dependencyTypeID),$originID,$media_to_duplicate['newID'],$dep_row['Ordering'],$dep_row['DepInfo'],$dep_row['Comment']);
					$dep->create();
					//createDependency($originID,$media_to_duplicate['newID'],$dependencyTypeID,$dep_row['Comment'],$dep_row['DepInfo'],$dep_row['Ordering']);
				}
			}
			
			// if it's a contact, it may not have been duplicated because of the email (no duplicate emails) and then its not necessary to go further down
			if($media_to_duplicate['ID'] != $new_element_ID){
				// taking all descendants
				// if a descendant has already been used elsewhere (it's in the 'duplicated' array), reusing it and creating the dependency from this element to it
				// if the descendant is in the 'toduplicate' array, adding this element in its 'parents' array
				// if the descendant was not met before, pushing it in the 'toduplicate' array, mentionning this element must be set as a parent (in the 'parents' array)
				$moduleInfo = moduleInfo($to_duplicate_moduleName);
				$depTypes = new DependencyTypeSet($moduleInfo->ID);
				while($dependencyType = $depTypes->next()){
					$deps_rs = getDependenciesFrom($moduleInfo->ID,$media_to_duplicate['ID'],$dependencyType->getID());
					while($dep_row = $deps_rs->FetchRow()){
						$dep_moduleInfo = moduleInfo($dependencyType->ModuleTargetID);
						$element_completeID = $dep_moduleInfo->name.$dep_row[$dependencyType->getTargetFieldname()];
						if(isset($duplicated[$element_completeID])){
							// adding a dependency from the current element (just duplicated) and this already existing element
							$dep = new Dependency($dependencyType,$media_to_duplicate['newID'],$duplicated[$element_completeID]['newID'],$dep_row[$dependencyType->getOrderingFieldname()],$dep_row['DepInfo'],$dep_row['Comment']);
							$dep->create();
							//createDependency($media_to_duplicate['newID'],$duplicated[$element_completeID]['newID'],$dependencyType->ID,$dep_row['Comment'],$dep_row['DepInfo'],$dep_row['Ordering']);
						}else if(isset($to_duplicate[$element_completeID])){
							// adding one parent in the future element
							$to_duplicate[$element_completeID]['parents'][$dependencyType->ID][$media_to_duplicate['newID']]=array('Comment'=>$dep_row['Comment'],'DepInfo'=>$dep_row['DepInfo'],'Ordering'=>$dep_row[$dependencyType->getOrderingFieldname()]);
						}else{
							// pushing the element in the duplication queue
							$to_duplicate[$element_completeID] = array('module'=>$dep_moduleInfo->name,'ID'=>$dep_row[$dependencyType->getTargetFieldname()],'depth'=>$media_to_duplicate['depth']+1);
							$to_duplicate[$element_completeID]['parents'][$dependencyType->ID][$media_to_duplicate['newID']]=array('Comment'=>$dep_row['Comment'],'DepInfo'=>$dep_row['DepInfo'],'Ordering'=>$dep_row[$dependencyType->getOrderingFieldname()]);
						}
					}
				}
			}
			
			
			
			// setting it in the duplicated list to be able to retreive any cycle and stop it
			$duplicated[$media_to_duplicate['module'].$media_to_duplicate['ID']] = $media_to_duplicate;
			// taking the next one to duplicate
			$media_to_duplicate = array_shift($to_duplicate);
			$i++;
			
		}
		$first = false;
	}
	
	$GLOBALS['restrict_language'] = $SAVE['restrict_language'];
	
	$update = false;
	
	// getting a string with all nodes of datas to update after duplication
	$elementNode = $xml->getElement($firstNodePath);
	$nodeDatas = $elementNode->getChildren();
	$nodes_for_update = '';
	foreach($nodeDatas as $node){
		if($node->nodename()!='WHERE'){ // not taking WHERE nodes which indicate which nodes to duplicate (when no ID)
			$nodes_for_update.=$node->toString();
		}
	}
	
	if($nodes_for_update){
		
		$update_xml_string = '<UPDATE><'.$firstNode.' ID="'.$IDs_string.'">'.$nodes_for_update.'</'.$firstNode.'></UPDATE>';
		
		$update_xml = new XML($update_xml_string);
		if($update_xml->loaded){
			$update = true;
			$query_result = updateQuery($name,$update_xml,'UPDATE','/UPDATE[1]',$firstNode,'/UPDATE[1]/*[1]');
		}else{
			debug_log('Error when loading XML '.$update_xml->getLastError());
		}
		
	}
	if(!$update){
		$now = $GLOBALS["sushee_today"];
		$msg_content = '<'.$firstNode.' ID="'.$IDs_string.'"><INFO>';
		$msg_content.='<CREATIONDATE>'.$now.'</CREATIONDATE><MODIFICATIONDATE>'.$now.'</MODIFICATIONDATE>';
		$msg_content.='</INFO></'.$firstNode.'>';
		$query_result = generateMsgXML(0,$msg_content,0,$IDs_string,$name,$now,$now);
	}
	
	return $query_result;
}
?>