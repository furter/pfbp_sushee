<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/categories.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/*-------------------------------------------------------------
			CATEGORIES
-------------------------------------------------------------*/
function generateCategoriesXML($rs,$output='html',$languageID=false){
	// can accept both an array or a result set
	if (is_array($rs))
		$categ = $rs;
    else
   		$categ = $rs->FetchRow();
	
	while($categ){
		
		if ($GLOBALS["php_request"] || $GLOBALS["category_complete"]){
			if ($GLOBALS["restrict_language"])
				$categ_str.=generateCategoryXML($categ["CategoryID"],$GLOBALS["NectilLanguage"],$output);
			else if($languageID)
				$categ_str.=generateCategoryXML($categ["CategoryID"],$languageID,$output);
			else
				$categ_str.=generateCategoryXML($categ["CategoryID"],false,$output);
		}else
			$categ_str.="<CATEGORY ID='".$categ['CategoryID']."'/>";
		if (is_array($rs))
		    $categ = FALSE;
	    else
   		    $categ = $rs->FetchRow();
   }
   
   return $categ_str;
}
function generateCategoryXML($row_or_ID,$languageID=FALSE,$output='html',$depth=0,$incl_count=false){
	$db_conn = db_connect();
	if (is_array($row_or_ID))
		$row = $row_or_ID;
	else{
		$sql = 'SELECT * FROM categories WHERE Activity=1 AND ID='.$row_or_ID.';';
		$row = $db_conn->GetRow($sql);
	}
	if (!$row)
		return FALSE;
	if ($output=='indesign')
		$output_str="\n";
	else
		$output_str='';
	if($depth===false)
		$depth=false;
	$xmlID = $row['ID'].$languageID.$output.$depth;//generateID(array($row['ID'],$languageID,$output,$depth));
	if(isset($GLOBALS['categoryXML'][$xmlID])){
		return $GLOBALS['categoryXML'][$xmlID];
	}
		
	$moduleInfo = moduleInfo($row['ModuleID']);
	if($incl_count){
		$count_sql = 'SELECT COUNT(DISTINCT ModuleTargetID,TargetID) AS total FROM `categorylinks` WHERE `CategoryID`=\''.$row['ID'].'\'';
		$count_row = $db_conn->GetRow($count_sql);
	}
	$query_result.="<CATEGORY depth=\"".(substr_count($row['Path'],'/') - 2)."\" ".(($incl_count)?" totalElements='".$count_row['total']."'":"")." ID=\"".$row['ID']."\" path=\"".$row['Path']."\" fatherID=\"".$row['FatherID']."\" module=\"".$moduleInfo->name."\">";
	$query_result.='<UNIQUENAME>'.encode_to_xml($row['Denomination']).'</UNIQUENAME>'.$output_str;
	$query_result.='<ISASSIGNABLE>'.encode_to_xml($row['IsAssignable']).'</ISASSIGNABLE>'.$output_str;
	$sql = 'SELECT * FROM categories WHERE Activity=1 AND ID='.$row['FatherID'].';';
	$father_row = $db_conn->GetRow($sql);
	$query_result.='<FATHERNAME>'.encode_to_xml($father_row['Denomination']).'</FATHERNAME>'.$output_str;
	if ($languageID){
		$sql = 'SELECT * FROM categorytraductions WHERE LanguageID="'.$languageID.'" AND CategoryID='.$row['ID'].';';
		$trad_row = $db_conn->GetRow($sql);
		if ($trad_row)
		$query_result.='<LABEL languageID="'.$languageID.'">'.encode_to_xml($trad_row['Text']).'</LABEL>'.$output_str;
		else
		$query_result.='<LABEL>'.encode_to_xml($row['Denomination']).'</LABEL>'.$output_str;
	}else if(isset($GLOBALS["priority_language"]) && $GLOBALS["priority_language"]!==false){
		$sql = 'SELECT * FROM categorytraductions WHERE LanguageID="'.$GLOBALS["priority_language"].'" AND CategoryID='.$row['ID'].';';
		$trad_row = $db_conn->GetRow($sql);
		if ($trad_row)
			$query_result.='<LABEL languageID="'.$trad_row["LanguageID"].'">'.encode_to_xml($trad_row['Text']).'</LABEL>'.$output_str;
		$sql = 'SELECT * FROM categorytraductions WHERE CategoryID='.$row['ID'].' AND LanguageID!="'.$GLOBALS["priority_language"].'";';
		$trad_rs = $db_conn->Execute($sql);
		while($trad_row = $trad_rs->FetchRow()){
			$query_result.='<LABEL languageID="'.$trad_row["LanguageID"].'">'.encode_to_xml($trad_row['Text']).'</LABEL>'.$output_str;
		}
	}else{
		$sql = 'SELECT * FROM categorytraductions WHERE CategoryID='.$row['ID'].';';
		$trad_rs = $db_conn->Execute($sql);
		while($trad_row = $trad_rs->FetchRow()){
			$query_result.='<LABEL languageID="'.$trad_row["LanguageID"].'">'.encode_to_xml($trad_row['Text']).'</LABEL>'.$output_str;
		}
	}
	$query_result.='<DATA>'.$row['Data'].'</DATA>'.$output_str;
	if($depth==='all')
		$next_depth = 'all';
	else
		$next_depth = $depth-1;
	if($next_depth!==''  && $next_depth!==0 && ($next_depth >0 || $next_depth==='all')){
		$sql2 = 'SELECT * FROM categories WHERE Activity=1 AND FatherID='.$row['ID'].';';
		$rs2 = $db_conn->Execute($sql2);
		if ($rs2){
			while($row2 = $rs2->FetchRow()){
				$query_result.=generateCategoryXML($row2,$languageID,$output,$next_depth,$incl_count);
			}
		}
	}
	$query_result.="</CATEGORY>";
	$GLOBALS['categoryXML'][$xmlID] = $query_result;
	return $query_result;
}
function getCategories($moduleTargetID,$targetID){
	$db_conn = db_connect();
	if (is_numeric($targetID))
		$target_cond = "TargetID=$targetID";
	else if(is_array($targetID)){
		foreach($targetID as $ID=>$value){
			$target_cond.=$ID.',';
		}
		$target_cond='( TargetID IN ('.substr($target_cond,0,-1).') )';
	}
	$sql = "SELECT * FROM categorylinks WHERE ModuleTargetID=$moduleTargetID AND $target_cond;";
	sql_log($sql);
	$rs = $db_conn->Execute($sql);
	
	return $rs;
}
function putInCategories(&$xml,$current_path,$IDs_array,&$moduleInfo,$elem_values=false){
	if ($moduleInfo->getServiceSecurity('category',$elem_values)!=='W')
		return 'No right to handle categories on this module.';
	// if it's not already an array we make one with only an element
	if (!is_array($IDs_array) )
       $IDs_array = array($IDs_array);
	$query_result = "";
    $db_conn = db_connect();

	//  --- ACTION LOGGING --- 
	$action_log_file = new UserActionLogFile();
	

	if ( $xml->match($current_path."/CATEGORIES") ){
		$categories_array = $xml->match($current_path."/CATEGORIES");
		foreach($categories_array as $categories_path){
			// delete the previous categories for all the IDs concerned
			$operation = $xml->getData($categories_path."/@operation");
			if($operation!=='append' && $operation!=='remove'){
				foreach($IDs_array as $ID){
					removeFromCategories($moduleInfo->ID,$ID);
				}
			}
			$category_array = $xml->match($categories_path."/CATEGORY");
			// looping on the chosen categories
			foreach($category_array as $category_path){
				$categoryID=$xml->getData($category_path."/@ID");
				$categ_name=$xml->getData($category_path."/@name");
				$categoryPath = $xml->getData($category_path."/@path");
				$categoryFatherID = $xml->getData($category_path."/@fatherID");
				$categoryIDs = array();
				if($categoryPath && !$categoryID){
					$categ_collect = 'SELECT `ID` FROM `categories` WHERE `Path` LIKE "'.encodequote($categoryPath).'%";';
					$rs = $db_conn->Execute($categ_collect);
					if($rs){
						while($row = $rs->fetchRow()){
							$categoryIDs[] = $row['ID'];
						}
					}
				}
				if($categ_name && !$categoryID){
					$categ_collect = 'SELECT `ID` FROM `categories` WHERE `Denomination` = "'.$categ_name.'";';
					$categ_row = $db_conn->GetRow($categ_collect);
					if($categ_row)
						$categoryID = $categ_row['ID'];
				}
				
				if($categoryID)
					$categoryIDs[] = $categoryID;
				if($categoryFatherID && !$categoryID){
					$categ_collect = 'SELECT `ID` FROM `categories` WHERE `FatherID` = "'.encodequote($categoryFatherID).'";';
					$rs = $db_conn->Execute($categ_collect);
					if($rs){
						while($row = $rs->fetchRow()){
							$categoryIDs[] = $row['ID'];
						}
					}
					
				}
				// categoryID must be set of course
				if (sizeof($categoryIDs)>0){
					foreach($IDs_array as $ID){
						$action_object = new UserActionObject($moduleInfo->getName(),$ID);
						if($operation==='remove'){
							foreach($categoryIDs as $categoryID){
								//  --- ACTION LOGGING --- 
								$action_target = new UserActionTarget(UA_OP_REMOVE,UA_SRV_CATEG,$categoryID);
								$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
								$action_log_file->log( $action_log );
								//  --- END LOGGING ---
								
								$categ_sql= removeFromCategory($moduleInfo->ID,$ID,$categoryID);
							}
						}else{
							foreach($categoryIDs as $categoryID){
								//  --- ACTION LOGGING --- 
								$action_target = new UserActionTarget(UA_OP_APPEND,UA_SRV_CATEG,$categoryID);
								$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
								$action_log_file->log( $action_log );
								//  --- END LOGGING ---
								
								$categ_sql= putInCategory($moduleInfo->ID,$ID,$categoryID);
							}
						}
					}
					$sql=$categ_sql;// for illustration we take the sql for the last element
				}
				$query_result.=$sql;
			}
		}

	}
	return $query_result;
}
function putInCategory($moduleTargetID,$ID,$categoryID){
	$db_conn = db_connect();
	$sql = "INSERT INTO categorylinks(ModuleTargetID,TargetID,CategoryID) VALUES($moduleTargetID,$ID,$categoryID);";
	$db_conn->Execute($sql);
	return $sql;
}
function removeFromCategories($moduleTargetID,$ID){
	$db_conn = db_connect();
	$sql = "DELETE FROM categorylinks WHERE ModuleTargetID=$moduleTargetID AND TargetID=$ID;";
	$db_conn->Execute($sql);
	return $sql;
}
function removeFromCategory($moduleTargetID,$ID,$categoryID){
	$db_conn = db_connect();
	$sql = "DELETE FROM categorylinks WHERE ModuleTargetID=$moduleTargetID AND TargetID=$ID AND CategoryID=$categoryID;";
	$db_conn->Execute($sql);
	return $sql;
}
function deleteModuleCategoryLinks($moduleID){
	$db_conn = db_connect();
	$moduleInfo = moduleInfo($moduleID);
	$sql = "";
	$rs = $db_conn->Execute("SELECT * FROM categorylinks");
	$total_sql.=$sql;
	
	while ( $row = $rs->FetchRow() ){
		$del_sql = "DELETE FROM categorylinks WHERE ModuleTargetID=".$row['ModuleTargetID']." AND TargetID=".$row['TargetID'].";";
		$db_conn->Execute($del_sql);
		$total_sql.=$del_sql;
	}
	return $total_sql;
}
function resolveCategPath($path){
	$db_conn = db_connect();
	$path_array = explode('/',$path);
	// we begin on 2 because there must be a leading slash and the first node is the module
	$fatherID=0;
	if($path_array[1]!='generic'){
		$moduleInfo = moduleInfo($path_array[1]);
		if (!$moduleInfo->loaded)
			return false;
		$moduleID=$moduleInfo->ID;
	}else{
		$moduleID=0;
	}
	for($i=2;$i<sizeof($path_array);$i++){
		if( $i==(sizeof($path_array)-1) && $path_array[$i]=='') // ending slash
			return $fatherID;
		$sql = 'SELECT * FROM categories WHERE Activity=1 AND ModuleID='.$moduleID.' AND FatherID='.$fatherID.' ';
		$startBrack = strpos($path_array[$i],'[');
		if ($startBrack!==FALSE)
			$endBrack = strpos($path_array[$i],']',$startBrack);
		if (/*$path_array[$i]=='*[1]'*/$startBrack!==FALSE && $endBrack!==FALSE){
			$startIndex = substr($path_array[$i],$startBrack+1,$endBrack-$startBrack-1);//'0';
			if (!is_numeric($startIndex))
				$startIndex = '1';
			//echo $startIndex.'-';
			$sql.= ' LIMIT '.($startIndex-1).',1';
		}else
			$sql.=' AND Denomination="'.$path_array[$i].'";';
		//$sql = 'SELECT * FROM categories WHERE Activity=1 AND ModuleID='.$moduleInfo->ID.' AND FatherID='.$fatherID.' AND Denomination="'.$path_array[$i].'";';
		$row = $db_conn->GetRow($sql);
		//debug_log($sql);
		if ($row)
			$fatherID=$row['ID'];
		else 
			return false;
	}
	//debug_log("fatherid is ".$fatherID);
	return $fatherID;
}
?>