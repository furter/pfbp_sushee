<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metaSearch.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../private/metasearch_datatypes.inc.php");
require_once(dirname(__FILE__)."/../private/metasearch_infos.inc.php");
require_once(dirname(__FILE__)."/../private/metasearch_descendant.inc.php");
require_once(dirname(__FILE__)."/../private/metasearch_ancestor.inc.php");
require_once(dirname(__FILE__)."/../private/metasearch_categories.inc.php");
require_once(dirname(__FILE__)."/../private/metasearch_descriptions.inc.php");
require_once(dirname(__FILE__)."/../private/metasearch_comments.inc.php");
require_once(dirname(__FILE__)."/../private/metasearch_dependencies.inc.php");
require_once(dirname(__FILE__)."/../private/metasearch_omnilinks.inc.php");
require_once(dirname(__FILE__)."/../private/generateXMLOutput.php");

function element_SELECT_str($tableName,$element_name='element'){
	return "`".$tableName."` AS $element_name";
}

function AttributesToString(&$xml,$path,$excludes=array()){
	$attributes_array = $xml->match($path.'/attribute::*');
	$attributes = '';
	foreach($attributes_array as $attr_path){
		$attrName = $xml->nodeName($attr_path);
		if(!in_array($attrName,$excludes)){
			$attrValue = $xml->getData($attr_path);
			$attributes.=' '.$attrName.'="'.$attrValue.'"';
		}
	}
	return $attributes;
}

function canonicalizeNQL(&$xml,$modulePath)
{
	//-----------------------------------------
	// COMPATIBLITY NQL 1.0 & canonicalization
	//-----------------------------------------

	//copying SPECIFIC children in its own parent 
	$specific_array = $xml->match($modulePath.'/SPECIFIC/*');
	foreach($specific_array as $path){
		$node_str = $xml->toString($path,'');
		
		$xml->appendChild($modulePath, $node_str,false,false );
	}
	
	$xml->removeChild($modulePath.'/SPECIFIC',false);
	//die('coucou');
	$xml->reindexNodeTree();
	$specific_array = $xml->match($modulePath.'/WHERE/*');
	foreach($specific_array as $path){
		$node_str = $xml->toString($path,'');
		$xml->appendChild($modulePath, $node_str,false,false );
	}
	$xml->removeChild($modulePath.'/WHERE',false);
	$xml->reindexNodeTree();
	//copying direct DEPENDENCY in a DEPENDENCIES node
	$dep_array = $xml->match($modulePath.'/DEPENDENCY');
	foreach($dep_array as $path){
		$node_str = $xml->toString($path,'');
		$xml->appendChild($modulePath, '<DEPENDENCIES>'.$node_str.'</DEPENDENCIES>',false,false );
	}
	$xml->removeChild($modulePath.'/DEPENDENCY',false);
	//copying CATEGORIES children in its own parent 
	$categories_array = $xml->match($modulePath.'/CATEGORIES/*');
	foreach($categories_array as $path){
		$node_str = $xml->toString($path,'');
		$xml->appendChild($modulePath, $node_str,false,false );
	}
	$xml->removeChild($modulePath.'/CATEGORIES');
	// changing <INFO>text</INFO> in <INFO><FULLTEXT>text</FULLTEXT></INFO>
	$general_array = $xml->match($modulePath.'/INFO[not(*)]');
	foreach($general_array as $path){
		$node_str = $xml->getData($path,'');
		$xml->appendChild($modulePath, '<INFO><FULLTEXT>'.$node_str.'</FULLTEXT></INFO>',false,false );
	}
	$xml->removeChild($modulePath.'/INFO[not(*)]',false);
	// changing <DESCRIPTION>text</DESCRIPTION> in <DESCRIPTION><FULLTEXT>text</FULLTEXT></DESCRIPTION>
	$desc_array = $xml->match($modulePath.'/DESCRIPTIONS[not(*)] | '.$modulePath.'/DESCRIPTION[not(*)]');
	foreach($desc_array as $path){
		$node_str = $xml->getData($path,'');
		$xml->appendChild($modulePath, '<DESCRIPTION><FULLTEXT>'.$node_str.'</FULLTEXT></DESCRIPTION>',false,false );
	}
	$xml->removeChild($modulePath.'/DESCRIPTIONS[not(*)] | '.$modulePath.'/DESCRIPTION[not(*)]',false);
	// changing <DESCRIPTIONS><DESCRIPTION>...</DESCRIPTION></DESCRIPTIONS> in <DESCRIPTION>...</DESCRIPTION>
	$descriptions_desc_array = $xml->match($modulePath.'/DESCRIPTIONS/DESCRIPTION');
	foreach($descriptions_desc_array as $path){
		$node_str = $xml->toString($path,'');
		$xml->appendChild($modulePath, $node_str,false,false );
	}
	$xml->removeChild($modulePath.'/DESCRIPTIONS[DESCRIPTION]',false);
	// renaming COMMENTS in COMMENT
	$comments_array = $xml->match($modulePath."/COMMENTS[not(*) and (text()!='' or @*)]");
	foreach($comments_array as $path){
		$node_str = $xml->getData($path);
		$comments_attributes = '';//AttributesToString($xml,$path);
		$attributes_array = $xml->match($path.'/@*');
		foreach($attributes_array as $attribute_path){
			$n = strtoupper($xml->nodeName($attribute_path));
			$comments_attributes.="<$n operator='='>".$xml->getData($attribute_path)."</$n>";
		}
		$xml->appendChild($modulePath, '<COMMENT>'.$comments_attributes.'<FULLTEXT>'.$node_str.'</FULLTEXT></COMMENT>',false,false );
	}
	$xml->removeChild($modulePath."/COMMENTS[not(*) and (text()!='' or @*)]",false);
	// changing <COMMENTS><COMMENT>...</COMMENTS></COMMENT> in <COMMENT>...</COMMENT>
	$comments_desc_array = $xml->match($modulePath.'/COMMENTS/COMMENT');
	foreach($comments_desc_array as $path){
		$node_str = $xml->toString($path,'');
		$xml->appendChild($modulePath, $node_str,false,false );
	}
	$xml->removeChild($modulePath.'/COMMENTS',false);
	// copying attributes criterias in every info
	$attributes_array = $xml->getAttributes($modulePath);
	if ($attributes_array!=FALSE && is_array($attributes_array) && sizeof($attributes_array)>0){
		
		// if no specific node we add one
		if (!$xml->match($modulePath.'/INFO')){
			$xml->appendChild($modulePath,"<INFO/>");
		}
		$info_array = $xml->match($modulePath.'/INFO');
		foreach($info_array as $info_path){
			foreach($attributes_array as $attribute_name=>$attribute_value){
				$new_specific = "";
				$n = strtoupper($attribute_name);
				$new_specific.="<$n operator='='>";
				$new_specific.=$attribute_value;
				$new_specific.="</$n>";
				$xml->appendChild($info_path,$new_specific);
			}
		}
	}
	$xml->reindexNodeTree();
}

function getResultSet(&$moduleInfo,&$search_xml,$current_path=FALSE,&$sql){
	$xml = $search_xml;
	$name = $xml->getData($current_path.'/@name');
	$modulePath = $current_path.'/*[1]';
	if ($xml->nodeName($current_path)=="DEPENDENCY" || $xml->nodeName($current_path)=="DEPENDENCIES" || $xml->nodeName($current_path)=="WHERE")
		$modulePath = $current_path;
	$currentNodeName = $xml->nodeName($current_path);
	$moduleNodeName = $xml->nodeName($modulePath);
	if ( $current_path && $currentNodeName!=="GET" ){
		$need_to_distinct = false;
		$where_string=' WHERE (element.`Activity` = \'1\')';//.element_WHERE_str($moduleInfo->tableName);
		
		$children_bool = $xml->match($modulePath.'/*[1]'); // at least one children or we skip all the treatment
		if ($children_bool || $xml->match($modulePath.'/@*')){
			canonicalizeNQL($xml,$modulePath);
			//----------------------
			// NQL 2.0
			//----------------------
			$db_conn = db_connect();
			//----------------------
			// INFO
			//----------------------
			$info_string = tags_INFO($xml,$modulePath, $moduleInfo,'element');
			if ($info_string)
				$where_string.=' AND ('.$info_string.')';
			// FULLTEXT
			$general_array = $xml->match($modulePath.'/GENERAL'); // only text node -> fulltext search
			$general_string="";
			$first=true;
			foreach($general_array as $path){
			   $data = decode_from_XML($xml->getData($path));
			   $data = strtolower(removeaccents(trim($data)));
			   if($data != ""){
				   $valid_query=true;
				   if($data == '*'){
					   //nothing to do
				   }else{          
					  //ensure no OR for the first general
					  if($first != true)$general_string.=' OR ';
					  else $first=false;
					  
					  $general_string.='('.manage_string('SearchText',$data,'LIKE','element').")";
				   }
			   }
			}

			// update sql query
			if($first != true)
			{
			   $where_string.=' AND ('.$general_string.')';
			}
			//----------------------
			// DESCRIPTION
			//----------------------
			
			list($desc_targetIDs,$desc_exludeIDs) = getElementWithDescriptionMatching($xml,$modulePath,$moduleInfo);
			if(sizeof($desc_targetIDs)>0){
				$desc_string = '/* DESCRIPTION */ element.ID IN ('.implode(',',$desc_targetIDs).') ';
				$where_string.=' AND ('.$desc_string.')';
			}
			// FULLTEXT
			$description_array = $xml->match($modulePath.'/DESCRIPTION[not(*)]');
			
			$description_string="";
			$first=true;
			foreach($description_array as $path){
			   $data = decode_from_XML($xml->getData($path));
			   $data = strtolower(removeaccents(trim($data)));
			   if($data != ""){
				  //ensure no OR for the first general
				  if($first != true)$description_string.=' OR ';
				  else $first=false;
				  $descrip_languageID = $xml->getData($path.'/@languageID');
				  if($descrip_languageID)
					  $desc_lg_crit=' AND descrip.LanguageID="'.$descrip_languageID.'"';
				  else
			  		$desc_lg_crit = '';
				  $description_string.='('.manage_string('SearchText',$data,'nothing','descrip').$desc_lg_crit.")";
				  $description_criterion = TRUE;
			   }
			}
			//update sql query
			if($first != true){
			   $where_string.=' AND ('.$description_string.')';
			}
			if($description_criterion){
				$need_to_distinct = true;
				$descriptions_select_str = ",descriptions AS descrip ";
				$where_string.=' AND (descrip.ModuleTargetID='.$moduleInfo->ID.' AND descrip.TargetID=element.ID AND descrip.Status="published") ';
			}
			//----------------------
			// CATEGORY
			//----------------------
			$categ_targetIDs = getElementWithCategoriesMatching($xml,$modulePath,$moduleInfo);
			if($categ_targetIDs!==false && sizeof($categ_targetIDs)>0){
				$categ_string = '/* CATEGORY */ element.ID IN ('.implode(',',$categ_targetIDs).') ';
				$where_string.=' AND ('.$categ_string.')';
			}
			//----------------------
			// COMMENT
			//----------------------
			list($comments_targetIDs,$comments_exludeIDs) = getElementWithCommentsMatching($xml,$modulePath,$moduleInfo);
			if(sizeof($comments_targetIDs)>0){
				$comments_string = '/* COMMENT */ element.ID IN ('.implode(',',$comments_targetIDs).') ';
				$where_string.=' AND ('.$comments_string.')';
			}
			if(sizeof($comments_exludeIDs)>0){
				$comments_string = '/* NOT COMMENT */element.ID NOT IN ('.implode(',',$comments_exludeIDs).') ';
				$where_string.=' AND ('.$comments_string.')';
			}
			//----------------------
			// DESCENDANT
			//----------------------
			list($descendant_targetIDs,$descendant_excludeIDs) = getElementWithDescendantsMatching($xml,$modulePath,$moduleInfo);
			if(sizeof($descendant_targetIDs)>0){
				$descendants_string = '/* DESCENDANT */ element.ID IN ('.implode(',',$descendant_targetIDs).') ';
				$where_string.=' AND ('.$descendants_string.')';
			}
			if(sizeof($descendant_excludeIDs)>0){
				$descendants_string = '/* NOT DESCENDANT */ element.ID NOT IN ('.implode(',',$descendant_excludeIDs).') ';
				$where_string.=' AND ('.$descendants_string.')';
			}
			//----------------------
			// ANCESTOR
			//----------------------
			list($ancestors_targetIDs,$ancestors_excludeIDs) = getElementWithAncestorsMatching($xml,$modulePath,$moduleInfo);
			if(sizeof($ancestors_targetIDs)>0){
				$ancestors_string = '/* ANCESTOR */ element.ID IN ('.implode(',',$ancestors_targetIDs).') ';
				$where_string.=' AND ('.$ancestors_string.')';
			}
			if(sizeof($ancestors_excludeIDs)>0){
				$ancestors_string = '/* NOT ANCESTOR */ element.ID NOT IN ('.implode(',',$ancestors_excludeIDs).') ';
				$where_string.=' AND ('.$ancestors_string.')';
			}
			//----------------------------------------------------------------------------
			// RECIPIENT
			//----------------------------------------------------------------------------
			$recipient_path = $modulePath.'/RECIPIENT';
			$first = true.
			
			$recipient_array = $xml->match($recipient_path);
			foreach($recipient_array as $path){
				$mailingID= $xml->getData($path.'/@mailingID');
				if($mailingID){
					$left_join = ' LEFT JOIN mailing_recipients AS mr ON (mr.ContactID=element.ID) ';
					$type = $xml->getData($path.'/@type');
					if($type){
						if($type=='received')
							$type_cond=' AND mr.`Status`="sent" AND mr.`ViewingDate`!="0000-00-00 00:00:00" ';
						else if($type=='not_received')
							$type_cond=' AND mr.`ViewingDate`="0000-00-00 00:00:00" ';
						else if($type=='clicked')
							$type_cond=' AND mr.`Status`="sent" AND mr.`Mail2Web`!=0 ';
						else if($type=='not_clicked')
							$type_cond=' AND mr.`Status`="sent" AND mr.`ViewingDate`!="0000-00-00 00:00:00" AND mr.`Mail2Web`=0 ';
						else
							$type_cond=' AND mr.`Status`="'.$type.'" ';
					}
					if($first!=true){
						$recipient_string.='OR ';
						$need_to_distinct = true;
					}
					$recipient_string.='(mr.MailingID=\''.$mailingID.'\''.$type_cond.') ';
					$first = false;
				}
			}
			if($first != true){
			   $where_string.=' AND ('.$recipient_string.')';
			}
			//----------------------
			// <DEPENDENCIES>
			// 		...
			// </DEPENDENCIES>
			//----------------------
			$matcher = new Sushee_DependenciesMatch(new XMLNode($xml,$modulePath),$moduleInfo->getID());
			$dep_includedIDs = $matcher->getElementsIncluded();
			$dep_excludedIDs = $matcher->getElementsExcluded();
			if($dep_includedIDs && sizeof($dep_includedIDs)>0){
				$deps_string = '/* DEPENDENCIES */ element.ID IN ('.implode(',',$dep_includedIDs).') ';
				$where_string.=' AND ('.$deps_string.')';
			}
			if($dep_excludedIDs && sizeof($dep_excludedIDs)>0){
				$deps_string = '/* NOT DEPENDENCIES */ element.ID NOT IN ('.implode(',',$dep_excludedIDs).') ';
				$where_string.=' AND ('.$deps_string.')';
			}
			//----------------------
			// <OMNILINKS>
			// 		...
			// </OMNILINKS>
			//----------------------
			$matcher = new OmnilinksMatch(new XMLNode($xml,$modulePath),$moduleInfo->getID());
			$omni_includedIDs = $matcher->getElementsIncluded();
			$omni_excludedIDs = $matcher->getElementsExcluded();
			if($omni_includedIDs && sizeof($omni_includedIDs)>0){
				$omni_string = '/* OMNILINKS */ element.ID IN ('.implode(',',$omni_includedIDs).') ';
				$where_string.=' AND ('.$omni_string.')';
			}
			if($omni_excludedIDs && sizeof($omni_excludedIDs)>0){
				$omni_string = '/* NOT OMNILINKS */ element.ID NOT IN ('.implode(',',$omni_excludedIDs).') ';
				$where_string.=' AND ('.$omni_string.')';
			}
			// -----------------------------------------------------------------------
			// <RELATED> text inside an element depending of the one searched </RELATED>
			// -----------------------------------------------------------------------
			$dep_array = $xml->match($modulePath.'/RELATED');
			$first=true;
			$related_string="";
			$related_tables = array();
			$depTypes = new DependencyTypeSet($moduleInfo->getID());
			foreach($dep_array as $dep_path){
				$data = $xml->getData($dep_path);
				$first2=true;
				if ($data){
					$data = strtolower(removeaccents(decode_from_XML(trim($data))));
					
					$IDs = ''; // will contains the IDs of all elements having a dependency to an element with the text in the RELATED node
					$depTypes->reset();
					// looping in all dependecytpes for the module searched
					while($depType = $depTypes->next()){
						$moduleTargetInfo = $depType->getModuleTarget();
						
						// searching for elements having a dependency of the type and having fulltext matching the text in the RELATED node
						$related_sql = 'SELECT dep.`'.$depType->getOriginFieldname().'` FROM `'.$depType->getTablename().'` AS dep LEFT JOIN `'.$moduleTargetInfo->getTablename().'` AS related_element ON (dep.`'.$depType->getTargetFieldname().'` = related_element.`ID` AND dep.`DependencyTypeID` = \''.$depType->getIDInDatabase().'\')';
						
						// managing privacy of the elements
						$privacy_string = $moduleTargetInfo->getSQLSecurity("related_element");
						
						// managing fulltext
						$related_sql.=' WHERE ('.manage_string('SearchText',$data,'nothing','related_element').$privacy_string.')';
						// executing the request and composing a string of all ID, separated by commas
						$related_rs = $db_conn->execute($related_sql);
						while($related_row = $related_rs->fetchRow()){
							// getting the ID of the element at the other end of the dependency
							$IDs.=$related_row[$depType->getOriginFieldname()].',';
						}
						
					}
					// removing last comma
					$IDs = substr($IDs,0,-1);
					if ($IDs == '')
						$IDs = -1;
					$this_related_string = ' element.`ID` IN('.$IDs.') ';
					// if multiple RELATED nodes, need to allow the two conditions
					if($first != true){
						$related_string.=" OR ";
					}else {
						$first=false;
					}
					$related_string.=$this_related_string;
				}
			}
			if($first != true){
				$where_string.=' AND ('.$related_string.')';
			}
			
		}
		
		//----------------------------------------------------------------------------
		// SQL QUERY
		//----------------------------------------------------------------------------
		if($moduleInfo->getName()!='module' && $moduleInfo->getName()!='processor' ){ // because historically, module has the contact with ID=1, and no template, it was before module became itself a module
			$where_string.=' AND element.`ID`!=1 ';
		}
		if ($GLOBALS["php_request"] && $moduleInfo->name=='media' && !($GLOBALS["take_unpublished"]===true)){
			$where_string.=' AND element.`Published`=1 ';
			
		}
		if ($moduleInfo->name=='media' && ($xml->getData($modulePath.'/WITH/DESCRIPTIONS/@discard-element-if')==='absent' || $xml->getData($current_path.'/RETURN/DESCRIPTIONS/@discard-element-if')==='absent' || $xml->getData($current_path.'/RETURN/DESCRIPTIONS/DESCRIPTION/@discard-element-if')==='absent' ||  $xml->getData($current_path.'/RETURN/DESCRIPTION/@discard-element-if')==='absent') ){
			$where_string.=' AND desc_discarding.`ModuleTargetID`  =\'5\' AND desc_discarding.`Status` = "published" AND desc_discarding.`LanguageID` IN  ("shared","'.$GLOBALS["NectilLanguage"].'")';
			$left_join.=' LEFT JOIN `descriptions` AS desc_discarding ON ( element.`ID` = desc_discarding.`TargetID` ) ';
		}
		$where_string.=$moduleInfo->getSQLSecurity('element');
		
	}else{ // is a GET
		$IDs_string = $xml->getData($modulePath.'/@ID');
		if ($IDs_string==FALSE){
			if($moduleInfo->name=='contact'){
				$viewing_code = $xml->getData($modulePath.'/@viewing_code');
				if($viewing_code!=false){
					$mailingID = false;
					if(strlen($viewing_code)>32){
						$mailingID = substr($viewing_code,33);
						$viewing_code = substr($viewing_code,0,32);
					}
					$db_conn = db_connect();
					$recip_sql = 'SELECT * FROM `mailing_recipients` WHERE `Status`="sent" AND `ViewingCode`="'.$viewing_code.'"'.(($mailingID!==false)?' AND `MailingID`=\''.$mailingID.'\' ':'');
					$recipient = $db_conn->GetRow($recip_sql);
					if($recipient)
						$IDs_string = $recipient['ContactID'];
				}
			}
			if ($IDs_string==FALSE){
				throw new SusheeException('No ID was set in the request -> no get has been processed');
			}
		}
		$IDs_array = explode(",",$IDs_string);
		// see common/constants.inc.php to know the meaning of these activity status
		$IDs_condition = ' WHERE element.`Activity` IN (1,2,3) AND (';
		
		$elementIDs = false;
		$elementDenominations = false;
		foreach($IDs_array as $ID){
			// keyword ''visitor' is allowed in order to get back the contact of the connected user
			if($ID==='visitor' && $moduleInfo->name=='contact' && isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'])){
				$ID = $_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
			}
			if(is_numeric($ID)){
				if($elementIDs)
					$elementIDs.=',';
				$elementIDs.='\''.$ID.'\'';
			}else{
				if($elementDenominations)
					$elementDenominations.=',';
				$elementDenominations.='\''.$ID.'\'';
			}
		}
		if($elementIDs)
			$IDs_condition.='element.`ID` IN ('.$elementIDs.')';
		if($elementDenominations){
			if($elementIDs)
				$IDs_condition.=' OR ';
			$IDs_condition.='element.`Denomination` IN ('.$elementDenominations.')';
		}
		
		$where_string = $IDs_condition.')';
		$where_string.=$moduleInfo->getSQLSecurity('element','GET');
	}
	if($current_path && $currentNodeName=='GETCHILDREN'){
		
		$depType_name = $xml->getData($current_path.'/@type');
		if ($depType_name==FALSE){
			throw new SusheeException('No dependency type was set -> no GETCHILDREN has been processed. e.g. <GETCHILDREN type="..."><ELEMENT ID="...">');
		}
			
		$depType = depType($depType_name);
		
		$getchildren_select_str = ','.element_SELECT_str($moduleInfo->tableName);
		$moduleInfo = moduleInfo($depType->ModuleTargetID);
		
		if(!$depType->loaded){
			throw new SusheeException('This dependencyType does not exist -> no GETCHILDREN has been processed');
		}
		
		$dep_select_str=",`".$depType->getTablename()."` AS children";
		
		$IDs_condition.=' AND (children.`'.$depType->getOriginFieldname().'` = element.`ID` AND children.`DependencyTypeID`=\''.$depType->getIDinDatabase().'\' AND children.`'.$depType->getTargetFieldname().'` = dep_element.`ID`)';
		$where_string.= $IDs_condition;
		if ($GLOBALS["php_request"] && $moduleInfo->name=='media' && !($GLOBALS["take_unpublished"]===true))
			$where_string.=' AND dep_element.`Published`=1 ';
		
		$where_string.=$moduleInfo->getSQLSecurity('dep_element');
		
		$order_string=' ORDER BY children.`'.$depType->getOrderingFieldname().'` ASC ';
		$which_element = 'dep_element';
	}else if($current_path && $currentNodeName=='GETPARENT'){
		$depType_name = $xml->getData($current_path.'/@type');
		if ($depType_name==FALSE){
			throw new SusheeException('No dependencyType was set -> no GETPARENT has been processed e.g. <GETPARENT type="..."><ELEMENT ID="...">');
		}
			
		$depType = depType($depType_name);
		
		$getchildren_select_str = ','.element_SELECT_str($moduleInfo->tableName);
		$moduleInfo = moduleInfo($depType->ModuleOriginID);
		if(!$depType->loaded){
			throw new SusheeException('This dependencyType does not exist -> no getparent has been processed');
		}
		$dep_select_str=",`".$depType->getTablename()."` AS parents";
		
		$IDs_condition.=' AND (parents.`'.$depType->getTargetFieldname().'`=element.`ID` AND parents.`DependencyTypeID`=\''.$depType->ID.'\' AND parents.`'.$depType->getOriginFieldname().'`=dep_element.`ID`)';
		$where_string.= $IDs_condition;
		if ($GLOBALS["php_request"] && $moduleInfo->name=='media' && !($GLOBALS["take_unpublished"]===true))
			$where_string.=' AND dep_element.`Published`=1 ';
		
		$where_string.=$moduleInfo->getSQLSecurity('dep_element');
		
		
		$which_element = 'dep_element';
	}else if($current_path && $currentNodeName=='GETANCESTOR'){
		$i = 0;
		$deptypes = array();
		while($depType_name = $xml->getData($current_path.'/@type'.($i+1))){
			$depType = depType($depType_name);
			$deptypes[]=$depType;
			$i++;
		}
		
		$getchildren_select_str = ','.element_SELECT_str($moduleInfo->getTableName());
		$nb_dep = sizeof($deptypes);
		for($i=0;$i<($nb_dep-1);$i++){
			$depType = $deptypes[$i];
			
			$moduleInfo = moduleInfo($depType->ModuleOriginID);
			$dep_moduleTargetInfo = moduleInfo($depType->ModuleTargetID);
			
			$dep_select_str.=",`".$depType->getTablename()."` AS deps$i,`".$dep_moduleTargetInfo->getTableName()."` AS intermed_element$i";
			if($i==0){
				$linked_element = 'dep_element';
			}else{
				$linked_element = 'intermed_element'.($i-1);
			}
			$IDs_condition.=" AND (deps$i.`".$depType->getTargetFieldname()."` = intermed_element$i.`ID` AND deps$i.`DependencyTypeID` = '".$depType->getIDInDatabase()."' AND deps$i.`".$depType->getOriginFieldname()."` = $linked_element.`ID`)";
		}
		$depType = $deptypes[$i];
		//$moduleInfo = $depType->moduleOriginInfo;
		$moduleInfo = moduleInfo($depType->ModuleOriginID);
		$dep_select_str.=",`".$depType->getTablename()."` AS deps$i";
		if($i>=1){
			$IDs_condition.=" AND (deps$i.`".$depType->getTargetFieldname()."` = element.`ID` AND deps$i.`DependencyTypeID` = '".$depType->getIDInDatabase()."' AND deps$i.`".$depType->getOriginFieldname()."` = intermed_element".($i-1).".`ID`)";
		}else{
			$IDs_condition.=" AND (deps$i.`".$depType->getTargetFieldname()."` = element.`ID` AND deps$i.`DependencyTypeID` = '".$depType->getIDInDatabase()."' AND deps$i.`".$depType->getOriginFieldname()."` = dep_element.`ID`)";
		}
		
		$where_string.= $IDs_condition;
		if ($GLOBALS["php_request"] && $moduleInfo->getName()=='media' && !($GLOBALS["take_unpublished"]===true)){
			$where_string.=' AND dep_element.`Published`=1 ';
		}
		$where_string.=$moduleInfo->getSQLSecurity('dep_element');
		
		$which_element = 'dep_element';
	}else{
		$which_element = 'element';
	}
	
	if($need_to_distinct)
		$distinct = 'DISTINCT ';
	
	if(!$need_to_distinct && $which_element=='element'){
		$count_select_string = 'SELECT SUM(1) AS ct FROM '.element_SELECT_str($moduleInfo->getTableName(),$which_element).$left_join.$getchildren_select_str.$categ_select_str.$comments_select_str.$dep_select_str.$related_select_str.$descriptions_select_str.$deps_select_str.$properties_select_str;
	}else{
		$count_select_string = 'SELECT COUNT('.$distinct.$which_element.'.`ID`) AS ct FROM '.element_SELECT_str($moduleInfo->getTableName(),$which_element).$left_join.$getchildren_select_str.$categ_select_str.$comments_select_str.$dep_select_str.$related_select_str.$descriptions_select_str.$deps_select_str.$properties_select_str;
	}
	
	//manage limits and results by page
	$limit_string="";
	$profile_path = $modulePath.'/WITH[1]';
	$isProfile = $xml->match($profile_path);
	$random = false;
	$packet_notation = false;
	
	// WITH DEPRECATED NOTATION
	if ($isProfile){
		// slicing
		$startIndex = $xml->getData($profile_path.'/@startIndex');
		$number = $xml->getData($profile_path.'/@number');
		
		// paging 
		$byPage = $xml->getData($profile_path.'/@perPage');
		if(!$byPage)
			$byPage = $xml->getData($profile_path.'/@byPage');
		$page = $xml->getData($profile_path.'/@page');
		$pageCut = $xml->getData($profile_path.'/@pageCut');
		
		// sorting
		$sortOn = $xml->getData($profile_path.'/@sortOn');
		if(!$sortOn)
			$sortOn = $xml->getData($profile_path.'/@sort');
		if($sortOn)
			$sortDataType = $xml->getData($profile_path.'/@data-type');
		$order = $xml->getData($profile_path.'/@order');
		$random = $xml->getData($profile_path.'/@random');
	}
	
	// PAGINATE node
	$paginateNode = $xml->getElement($current_path.'/PAGINATE');
	if ($paginateNode){
		$byPage = $paginateNode->getData('@display');
		$page = $paginateNode->getData('@page');
		if(!$page)
			$page = 1;
		$packet_notation = true;
		$pageCut = 'ascending';
	}
	
	// SLICE node
	$sliceNode = $xml->getElement($current_path.'/SLICE');
	if($sliceNode){
		if($paginateNode){
			throw new SusheeException('<SLICE> and <PAGINATE> are not allowed in a same request, they are in conflict.');
		}
		
		$startIndex = $sliceNode->valueOf('@start');
		$endIndex = $sliceNode->valueOf('@end');
		
		// checking params are OK
		if($startIndex===false){
			throw new SusheeException('Start not defined for slicing of results. <SLICE start="..." end="..."/>');
		}
		if(!is_numeric($startIndex) || $startIndex == 0){
			throw new SusheeException('Start of slicing is not valid. <SLICE start="..." end="..."/>');
		}
		if($endIndex===false){
			throw new SusheeException('End not defined for slicing of results. <SLICE start="..." end="..."/>');
		}
		if(!is_numeric($endIndex) || $endIndex == 0){
			throw new SusheeException('End of slicing is not valid. <SLICE start="..." end="..."/>');
		}
		
		if($number < 0){
			$number = -($number);
		}
		$number = $endIndex - $startIndex +1;
	}
	
	// SORT node
	$sort_path = $current_path.'/SORT[1]';
	$isSort = $xml->match($sort_path);
	
	if ($isSort){
		$sortOn = $xml->getData($sort_path.'/@select');
		$sortDataType = $xml->getData($sort_path.'/@data-type');
		$order = $xml->getData($sort_path.'/@order');
		$pageCut = 'ascending';
		$packet_notation = true;
	}
	
	// RANDOM node : allows to get x elements randomly
	$random_path = $current_path.'/RANDOM[1]';
	$isRandom = $xml->match($random_path);
	if ($isRandom){
		$random = $xml->getData($random_path.'/@display');
	}
	
	$db_conn = db_connect();
	if($number!="" || $startIndex != ""){
	   if($startIndex != "" && $number!="")
		  $limit_string=' LIMIT '.($startIndex-1).','.$number;
		else if($number!="" && !$startIndex)
		  $limit_string=' LIMIT '.$number;
		else if($startIndex != "" && !$number){
		  $count_sql = $count_select_string.$where_string.' LIMIT 1';
		  $count_row = $db_conn->GetRow($count_sql);
		  $count = $count_row["ct"];
		  $limit_string=' LIMIT '.($startIndex-1).','.($count-$startIndex);
		}
	}else if ($byPage!=""){
		if($page === false)
			$page = 1;
		//$db_conn = db_connect();
		$count_sql = $count_select_string.$where_string.' LIMIT 1';
		$count_row = $db_conn->GetRow($count_sql);
		$count = $count_row["ct"];
		if($count===null)
			$count = 0;
		
		if (!$byPage || !is_numeric($byPage))
			$byPage=10;
		$totalPages = ceil($count/$byPage);
		if ($page=="first")
			$page =1;
		if ($pageCut == 'ascending'){
			if ($page==1)
				$limit_string=' LIMIT '.$byPage;
			else if ($page=="last"){
				$startIndex = floor($count/$byPage)*$byPage;
				if ($startIndex==$count)
					$startIndex-=$byPage;
				
				$limit_string=' LIMIT '.$startIndex.','.$byPage;
				$page = ceil($count/$byPage);
			}else{
				$startIndex =($page-1)*$byPage;
				$limit_string=' LIMIT '.$startIndex.','.$byPage;
			}
		}else{
			$pageCut = 'descending';
			if ($page=="last"){
				$startIndex = $count - $byPage;
				if ($startIndex<0)
					$startIndex = 0;
				$limit_string=' LIMIT '.$startIndex.','.$byPage;
				$page = ceil($count/$byPage);
				
			
			}else{
				
				$startIndex = $count - ($totalPages-$page+1)*$byPage;
				
				if ($startIndex<0){
					$limit_string=' LIMIT 0,'.($byPage+$startIndex);
					$startIndex = 0;
				}else
					$limit_string=' LIMIT '.$startIndex.','.$byPage;
			}
		}
		if($totalPages==0)
			$totalPages = 1; // we consider there is always one page even if there are no elements
		// if it's the last page, we replace the eventual number by "last"
		if (is_numeric($page) && ($page*$byPage)>=$count )
			$isLastPage = "true";
		else
			$isLastPage = "false";
	}else if($random!=''){
		$order_string=' ORDER BY RAND() ';
		if(!is_numeric($random))
			$random = 1;
		$limit_string=' LIMIT '.$random;
	}

	if (($order || $sortOn) && $random===false)
	{
		$sortNodes = $xml->getElements($current_path.'/SORT');
		if($xml->exists($modulePath.'/WITH[1]'))
		{
			$sortNodes[] = $xml->getElement($modulePath.'/WITH[1]');
		}
		
		$first = true;
		$firstDescSort = true;
		$firstDescCustomSort = true;
		
		foreach($sortNodes as $sortNode)
		{
			// getting the configuration of the sort
			// which field ?
			$sortOn = $sortNode->valueOf('@select');
			if(!$sortOn)
			{
				$sortOn = $sortNode->valueOf('@sort');
				if(!$sortOn)
				{
					$sortOn = $sortNode->valueOf('@sortOn');
				}
			}
			
			// to keep the original version, if error is happening
			$origSortOn = $sortOn;
			
			// ascending or descending
			$order = $sortNode->valueOf('@ordering');
			if(!$order)
			{
				$order = $sortNode->valueOf('@order');
			}

			// text or number ?
			$sortDataType = $sortNode->valueOf('@data-type');

			$field = true;
			if ($order =='descending')
				$order_suffix=" DESC";
			else
				$order_suffix=" ASC";

			if($sortOn)
			{
				if(!$first)
				{
					$order_fields.=',';
				}

				$sortOn = strtolower($sortOn);
				$particle = '';
				$sep = '.';

				$particle_pos = strrpos($sortOn,$sep);
				if($particle_pos===false)
				{
					$sep = '/';
					$particle_pos = strrpos($sortOn,$sep);
				}

				if($particle_pos!==false)
					$particle = substr($sortOn,0,$particle_pos);

				if($particle=='description' || $particle=='descriptions' || $particle=='descriptions'.$sep.'description')
				{
					$subfield = substr($sortOn,$particle_pos+1);
					$need_to_distinct = true;

					// joining to the descriptions table in order to sort
					if($firstDescSort)
					{
						$order_supp_table.= " LEFT JOIN `descriptions` AS descrip_ordering ON (
							descrip_ordering.`Status` = 'published'
							AND descrip_ordering.`LanguageID` = '".$GLOBALS["NectilLanguage"]."'
							AND descrip_ordering.`ModuleTargetID` = '".$moduleInfo->getID()."'
							AND descrip_ordering.`TargetID` = ".$which_element.".`ID`)";
					}

					$desc_fields = array("LANGUAGEID"=>"LanguageID","TITLE"=>"Title","HEADER"=>"Header","BODY"=>"Body","SIGNATURE"=>"Signature","SUMMARY"=>"Summary","BIBLIO"=>"Biblio","COPYRIGHT"=>"Copyright","CUSTOM"=>"Custom","URL"=>"URL","SEARCHTEXT"=>"SearchText");
					$order_fields .= 'descrip_ordering.' . $desc_fields[strtoupper($subfield)];

					// casting the field to number for numeric sort
					if($sortDataType=='number')
					{
						$order_fields.='+0';
					}

					$order_fields .= $order_suffix;

					// to prevent multiple joins of the description table
					$firstDescSort = false;
				}
				else if($particle=='descriptions'.$sep.'description'.$sep.'custom')
				{
					$subfield = substr($sortOn,$particle_pos+1);
					$need_to_distinct = true;
					
					// joining to the descriptions_custom table in order to sort
					if($firstDescCustomSort)
					{
						// previously AND descrip_custom_ordering.`LanguageID` IN ('shared','".$GLOBALS["NectilLanguage"]."')
						
						$order_supp_table.= " LEFT JOIN descriptions_custom AS descrip_custom_ordering ON (
							descrip_custom_ordering.`Name` = '".$subfield."'
							AND descrip_custom_ordering.`ModuleTargetID` = '".$moduleInfo->getID()."'
							AND descrip_custom_ordering.`Status`='published'
							AND descrip_custom_ordering.`LanguageID` = '".$GLOBALS["NectilLanguage"]."'
							AND descrip_custom_ordering.`TargetID` = ".$which_element.".`ID`)";
					}
					$order_fields.='descrip_custom_ordering.`Value`';
					
					// casting the field to number for numeric sort
					if($sortDataType=='number')
						$order_fields.='+0';
					$order_fields.=$order_suffix;
					
					// to prevent multiple joins of the descriptions_custom table
					$firstDescCustomSort = false;
					
				}
				else if($particle=='comment' || $particle=='comments' || $particle=='comments'.$sep.'comment')
				{
					$subfield = substr($sortOn,$particle_pos+1);
					$comments_fields = array('TITLE'=>'Title','HEADER'=>'Header','BODY'=>'Body','CREATIONDATE'=>'CreationDate','MODIFICATIONDATE'=>'ModificationDate');
					$comment_field = $comments_fields[strtoupper($subfield)];

					$db_conn->execute('DROP VIEW comments_ordering');
					if ($order =='descending')
						$operator='MAX';
					else
						$operator='MIN';
					$comments_sort_sql = 'CREATE VIEW comments_ordering AS SELECT '.$operator.'( `'.$comment_field.'` ) AS OrderingKey , `TargetID` FROM `comments` WHERE `ModuleTargetID`=\''.$moduleInfo->ID.'\' GROUP BY `TargetID`';
					
					$db_conn->execute($comments_sort_sql);

					$left_join	  .= ' LEFT JOIN comments_ordering ON ( '.$which_element.'.ID = comments_ordering.TargetID )';
					$where_string .= ' AND comments_ordering.TargetID IS NOT NULL';
					$order_fields .= 'comments_ordering.OrderingKey';

					$order_fields .= $order_suffix;
				}
				else
				{
					if($particle_pos!==false)
						$sortOn = substr($sortOn,$particle_pos+1);

					$field_realname = $moduleInfo->getFieldName($sortOn);

					if($field_realname)
					{
						$order_fields.=$which_element.'.`'.$field_realname.'`';
						if($sortDataType=='number')
							$order_string.='+0';
						$order_fields.=$order_suffix;
					}
					else
					{
						throw new SusheeException('`'.$origSortOn.'` is not an existing field : <SORT select="'.$origSortOn.'"/>');
					}
				}
			}
			else
			{
				$field_realname = 'ID';
				$order_fields .= $which_element . '.' . $field_realname;
				$order_fields .= $order_suffix;
			}
			// to know that we have to add a comma on in front of the next sorted field
			$first = false;
		}
		
		$order_string = 'ORDER BY '.$order_fields;
	}

	// only taking in SQL what is really necessary, what is returned to the user
	$returned_str = $which_element.'.*';
	$returned_info = $xml->getElements($current_path.'/RETURN/INFO/*');

	if ($returned_info)
	{
		$returned_str = '';
		foreach ($returned_info as $info_node)
		{
			$fieldname = $moduleInfo->getFieldName($info_node->nodeName());
			if($fieldname && $fieldname != 'ID')
			{
				// if fieldname exists, and ID is added automatically
				$returned_str .= $which_element.'.`'.$fieldname.'`,';
			}
		}

		$returned_str .= $which_element.'.`ID`';

		// if creator or modifier or owner of the element is asked, returned this field, or it wont work
		$return_infoNode = $xml->getElement($current_path.'/RETURN/INFO');
		if($return_infoNode)
		{
			if($return_infoNode->getAttribute('creator-info') || $return_infoNode->getAttribute('creator_info'))
			{
				$returned_str.=','.$which_element.'.`CreatorID`';
			}
			
			if($return_infoNode->getAttribute('modifier-info') || $return_infoNode->getAttribute('modifier_info'))
			{
				$returned_str.=','.$which_element.'.`ModifierID`';
			}
			
			if($return_infoNode->getAttribute('owner-info') || $return_infoNode->getAttribute('owner_info'))
			{
				$returned_str.=','.$which_element.'.`OwnerID`';
			}
		}
		
	}
	else if( $xml->exists($current_path.'/RETURN/NOTHING') )
	{
		$returned_str = $which_element.'.ID';
	}

	$selection = $distinct.$returned_str;

	$select_string='SELECT '.$selection.' FROM '.element_SELECT_str($moduleInfo->tableName,$which_element).$left_join.$getchildren_select_str.$categ_select_str.$comments_select_str.$dep_select_str.$related_select_str.$descriptions_select_str.$deps_select_str.$properties_select_str;

	if($currentNodeName=='COUNT')
	{
		// counting the rows
		$count_sql = $count_select_string.$where_string;
		$count_row = $db_conn->GetRow($count_sql);
		$count = $count_row["ct"];
		if($count=='')
			$count = 0;
	}

	// --- finally compose the entire SQL string ---
	$sql = $select_string . $order_supp_table . $where_string . $order_string . $limit_string;

	if($currentNodeName!=='COUNT')
		$rs = $db_conn->Execute($sql);

	$GLOBALS['SearchQUERIES']++;

	if ($page)
	{
		if($packet_notation)
		{
			$rs->packet = $page;
			$rs->last_packet = $isLastPage;
			$rs->total_packets = $totalPages;
			$rs->total_elements = $count;
		}
		else
		{
			$rs->result_page = $page;
			$rs->isLastPage = $isLastPage;
			$rs->totalPages = $totalPages;
			$rs->totalCount = $count;
			$rs->pageCut = $pageCut;
			$rs->byPage = $byPage;
			$rs->startIndex = $startIndex;
		}
	}
	else if($currentNodeName!='COUNT' && $rs)
	{
		$rs->total_elements = $rs->RecordCount();
	}

	if($currentNodeName=='COUNT')
	{
		$rs->totalCount = $count;
	}
	return $rs;
}