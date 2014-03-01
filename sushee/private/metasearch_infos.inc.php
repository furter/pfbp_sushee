<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_infos.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function tag_AND(&$xml,$tagPath,&$moduleInfo,$varName,$noSecure_flag,$tableName=false){
    //global $xml;
    
    $search_str=""; //returned string
    $separator_str=" "; //separator character
    
    $fieldName_str = $xml->nodeName($tagPath);
    $tagText_str = $xml->getData($tagPath);
	if($tagText_str===false){
		$tagText_str = $xml->toString($tagPath.'/*');
	}
	$tagText_str = decode_from_xml($tagText_str);
    $operator = $xml->getxSusheeOperator($tagPath);
    $orig_fieldName_str = $fieldName_str;
	if(is_array($moduleInfo)){
		$fieldName_str = $moduleInfo[$orig_fieldName_str];
	}else if($fieldName_str=='FULLTEXT' || $fieldName_str=='SEARCHTEXT'){
		$fieldName_str= 'SearchText';
		$tagText_str = strtolower(removeaccents(trim($tagText_str)));
	}else{
    	$fieldName_str=$moduleInfo->getFieldName($fieldName_str);
		if($moduleInfo->name=='contact' && $fieldName_str == 'Password' && strlen($tagText_str)!=16 && $tagText_str)
			$tagText_str = mysql_password($tagText_str);
	}
    /////////////////////////
	// if an array is given instead of a moduleInfo, this array is a list of potential nodenames with their equivalent fields (for search in DEPINFO and COMMENT)
    if($noSecure_flag == true || is_array($moduleInfo) || $fieldName_str == 'SearchText' || $fieldName_str == 'Password')
        $securityFlag = 'R';
	else{
		$securityFlag = $moduleInfo->getFieldSecurity($orig_fieldName_str);
	}
    // get fieldType
	if(is_array($moduleInfo) && $tableName != false){
		$db_conn = db_connect();
		$field_array = $db_conn->MetaColumns($tableName);
		foreach($field_array as $field){
			if($field->name==$fieldName_str){
				$fieldType = $db_conn->MetaType($field->type);
				break;
			}
		}
	}else if($fieldName_str == 'SearchText'){
		$fieldType = 'C';
	}else{
    	$fieldType = $moduleInfo->getFieldType($fieldName_str);
	}
    //SECURITY CHECK
    if($fieldType && ($securityFlag == 'R' || $securityFlag == 'W') ){
        if($fieldName_str != "" && !($tagText_str === '' && !$operator)){
            $search_str = "";
            
			$search_str = manageFieldType($fieldName_str,$fieldType,$tagText_str,$operator,$varName);
        }
    }
    return $search_str;
}


function tags_INFO(&$xml,$parentPath,&$moduleInfo,$varName,$noSecure_flag = false){
   //GLOBAL $xml;
   
   $search_str="";
   $infos_array = $xml->match($parentPath."/INFO");
   
   $first = true;
   $groups_array = array();
   foreach($infos_array as $path){
      //create the SQL search for one info..
	  $groupName = $xml->getData($path."/@and_group");
	  if ($groupName){
		  // s'il y a un groupe on fait des AND sur tous les tags du meme groupe ( si ce groupe n'a pas encore ete traite )
		  $info_str="";
		  if (!isset($groups_array[$groupName])){
			  $same_group = $xml->match($parentPath."/INFO[@and_group='".$groupName."']");
			  $first2 = true;
			  foreach($same_group as $group_path){
				  $one_info_str = tag_INFO($xml,$group_path,$moduleInfo,$varName,$noSecure_flag);
				  if($first2 != true) 
				  	$info_str.=" AND ";
				  else
				  $first2=false;
				  $info_str.="(".$one_info_str.")";
			  }
			  // mark the group as already handled
			  $groups_array[$groupName]=true;
		  }
	  }else
      $info_str= tag_INFO($xml,$path,$moduleInfo,$varName,$noSecure_flag);
	  
      if($info_str != ""){
         //ensure no "OR" for the first tag instance
         if($first != true) 
            $search_str.=" OR ";
         else
            $first=false;
         $search_str.="(".$info_str.")";
      }
    }
    return $search_str;
}

function tag_INFO(&$xml,&$infopath,&$moduleInfo,&$varName,&$noSecure_flag,$tableName=false){
    $search_query="";
	// if an array is given instead of a moduleInfo, this array is a list of potential nodenames with their equivalent fields (for search in DEPINFO and COMMENT)
	if(is_array($moduleInfo)){
		$all_paths = "";
		$first_poss_node = true;
		foreach($moduleInfo as $possible_node=>$possible_field){
			if($first_poss_node!=true)
				$all_paths.=" | ";
			$all_paths.=$infopath."/".$possible_node;
			$first_poss_node = false;
		}
		$searchItem_array = $xml->match($all_paths);
	}else{
		$searchItem_array = $xml->match($infopath."/*");
    }
    //tant qu'il reste des element au noeud info
    $start=true;
    $check_array = array();
    
    foreach($searchItem_array as $path){
        //taking an element type (ex: FIRSTNAME)
        $tempTagName = $xml->nodeName($path);
		$groupName = $xml->getData($path.'/@and_group');
		if ($groupName===FALSE)
			$groupName="";
		$operator = $xml->getData($path.'/@operator');
		if ($operator===FALSE)
			$operator="=";
		
        //managing each instances of this type
        ////////////////////
       $string_tmp="";
       $firstTag=true;
       if(!isset($check_array[$path])){
		   
		   if ($operator==="="){
			   // taking only the other identic tags with the same operator and not grouped in a "and_group"
			   if ($groupName==""){
			   		$temp_array = $xml->match($infopath."/".$tempTagName."[not(@operator) or @operator='=' and not(@and_group)]");
			   }else{
			   		$temp_array = $xml->match($infopath."/".$tempTagName."[not(@operator) or @operator='=' and @and_group!='".$groupName."']");
					$temp_array[]=$path;
				}
		   }else{
			   if ($groupName==""){
				    $temp_array = $xml->match($infopath."/".$tempTagName."[@operator='".$operator."' and not(@and_group)]");
			   }else{
					$temp_array = $xml->match($infopath."/".$tempTagName."[@operator='".$operator."' and @and_group!='".$groupName."']");
					$temp_array[]=$path;
				}
			}
			
            foreach($temp_array as $finalPath){
               $search_temp = tag_AND($xml,$finalPath,$moduleInfo,$varName,$noSecure_flag,$tableName);
			   
               if($search_temp != ""){
                  //ensure no "OR" for the first tag instance
                  if($firstTag != true) $string_tmp.=" OR ";
                  else $firstTag=false;
                  $string_tmp.="(".$search_temp.")";
               }
			   $check_array[$finalPath]=true;
            }
       }
       
      if($string_tmp!=""){
        //ensure no "AND" for the first tag type instance
        if($start != true) $search_query.=" AND ";
        else $start=false;
        $search_query.="(".$string_tmp.")";
      }
    }
   return $search_query;
}
?>