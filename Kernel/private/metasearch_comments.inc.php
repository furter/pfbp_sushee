<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_comments.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function tags_COMMENT(&$xml,$nodes_array,$varName){
	$search_str="";
	$first = true;
	foreach($nodes_array as $path){
		$query_str= tag_COMMENT($xml,$path,$varName);
		if($query_str != ""){
			//ensure no "OR" for the first tag instance
			if($first != true) 
				$search_str.=" OR ";
			else
				$first=false;
			$search_str.="(".$query_str.")";
		}
	}
	return $search_str;
}



function tag_COMMENT(&$xml,$parentPath,$varName){
	$search_str="";
	$allowed_fields = array("TYPE"=>'Type',"CHECKED"=>'Checked',"FULLTEXT"=>'SearchText',"TITLE"=>"Title","BODY"=>"Body","CREATIONDATE"=>"CreationDate","MODIFICATIONDATE"=>"ModificationDate","CREATORID"=>"CreatorID");
	$field_types = array('Type'=>'C','Checked'=>'I','SearchText'=>'C','Title'=>'C','Body'=>'C','CreationDate'=>'D','ModificationDate'=>'D');
	$xml_array = $xml->match($parentPath."/*");
	
	// preparing a matrix with all the possible values for all the fields
	foreach($xml_array as $path){
		$n = $xml->nodeName($path);
		if (isset($allowed_fields[$n])){
			$data = $xml->getData($path);
			$fieldname = $allowed_fields[$n];
			if($n=='FULLTEXT')
				$data = decode_from_XML(strtolower(removeaccents(trim($data))));
			$fields[$fieldname][sizeof($fields[$fieldname])]=array("value"=>$data,"operator"=>$xml->getxSusheeOperator($path));
		}
	}
	$first = true;
	foreach($fields as $fieldname => $possible_values){
		$field_str="";
		$first2 = true;
		
		for($i=0;$i<sizeof($possible_values);$i++){
			$possible = $possible_values[$i];
			$fieldType = $field_types[$fieldname];
			$str = manageFieldType($fieldname,$fieldType,$possible['value'],$possible['operator'],$varName);
			if($str != ""){
				//ensure no "OR" for the first tag instance
				if($first2 != true) 
					$field_str.=" OR ";
				else
					$first2=false;
				$field_str.="(".$str.")";
			}
		}
		if($field_str != ""){
			//ensure no "OR" for the first tag instance
			if($first != true) 
				$search_str.=" AND ";
			else
				$first=false;
			$search_str.="(".$field_str.")";
		}
	}
	return $search_str;
}

function getElementWithCommentsMatching(&$xml,$element_path,$moduleInfo){
	$varName= 'cmt';
	$targetIDs = array();
	$excludeIDs = array();
	$db_conn = db_connect();
	$nodes_array = $xml->match($element_path."/COMMENT[not(@operator) or @operator='exists']");
	$query_string = tags_COMMENT($xml,$nodes_array,$varName);
	
	if($query_string || sizeof($nodes_array)){
		$collect_sql = 'SELECT DISTINCT `TargetID` FROM `comments` AS '.$varName.' WHERE '.$varName.'.`ModuleTargetID`=\''.$moduleInfo->ID.'\'';
		if($query_string){
			$collect_sql.=' AND '.$query_string;
		}
		sql_log($collect_sql);
		$collect_rs = $db_conn->Execute($collect_sql);
		while( $row = $collect_rs->FetchRow() ){
			$targetIDs[]=$row['TargetID'];
		}
		if(sizeof($targetIDs)==0)
			$targetIDs[]=-1;
	}
	$nodes_array = $xml->match($element_path."/COMMENT[@operator='not_exists' or @operator='not']");
	$query_string = tags_COMMENT($xml,$nodes_array,$varName);
	
	if($query_string){
		$collect_sql = 'SELECT  DISTINCT `TargetID` FROM `comments` AS '.$varName.' WHERE '.$varName.'.`ModuleTargetID`=\''.$moduleInfo->ID.'\' AND '.$query_string;
		sql_log($collect_sql);
		$collect_rs = $db_conn->Execute($collect_sql);
		while( $row = $collect_rs->FetchRow() ){
			$excludeIDs[]=$row['TargetID'];
		}
	}
	return array($targetIDs,$excludeIDs);
}

?>