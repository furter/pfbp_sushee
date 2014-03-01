<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/descriptions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/*-------------------------------------------------------------
			DESCRIPTIONS
-------------------------------------------------------------*/
function getDescriptionProfileArray($name="full"){
	if(is_array($name)){
		$profile_array = array();
		$base_array = array("ID","CreationDate","ModificationDate","LanguageID","Status","CreatorID","ModifierID","URL","Header","Title",'Custom',"Body","Summary","Signature","Biblio","Copyright");
		foreach($base_array as $val){
			if( in_array(strtoupper($val),$name) )
				$profile_array[]=$val;
		}
		return $profile_array;
	}else{
		if($name==='versioning')
			return array('ID','LanguageID','Status','CreationDate','ModificationDate','CreatorID','ModifierID','Title');
		if($name==='title')
			return array('Title','LanguageID','Status','Custom');
		if ($name==='minimal')
			return array("Title","Header","Body","URL","Summary","Custom"/*,"Copyright"*/);
		if($name==='templateCSV')
			return array("Title","Status","Header","Body","URL","Signature","Biblio","Copyright","Custom","Summary");
		if ($name==='content')
			$base_array = array("URL","Title",'Custom',"Header","Body");
		else
			$base_array = array("ID","LanguageID","Status","CreatorID","ModifierID","URL","Header","Title",'Custom',"Body");
		if ($name=='label')
			return $base_array;
		$base_array = array_merge($base_array,array("Summary"));
		if ($name=='summary')
			return $base_array;
		// profile="edition" or "full"
		$base_array = array_merge($base_array,array("CreationDate","ModificationDate","Signature","Biblio","Copyright"));
		return $base_array;
	}
}

function isFileUsed($path)
{
	$is_used = isFileInDescription($path);
	if ($is_used)
	{
		$moduleInfo = moduleInfo($is_used['ModuleTargetID']);
		$row = getInfo($moduleInfo,$is_used['TargetID']);
		$denom = $row['Denomination'];
	}
	else
	{
		$row = isFileInContact($path);
		$moduleInfo = moduleInfo('contact');
		$denom = $row['Denomination'].' '.$row['FirstName'].' '.$row['LastName'];	
	}

	if ($row)
	{
		return array('moduleInfo'=>$moduleInfo,'element'=>$row,'denomination'=>$denom);
	}
	else
	{
		return false;
	}
}

function isFileInContact($path){
	$iso_path = $path;
	$path = iso_To_UnicodeEntities($path);
	$db_conn = db_connect();
	$fields_to_check = array('Preview');
	//$sql = "SELECT * FROM descriptions WHERE Files LIKE \"%$path%\" OR Files LIKE \"%$iso_path%\";";
	$sql = "SELECT * FROM `contacts` WHERE ";
	$first = TRUE;
	foreach($fields_to_check as $field){
		if(!$first)$sql.=" OR ";
		else $first=false;
		if ($path != $iso_path)
			$sql.="BINARY `$field` LIKE \"%$path%\" OR BINARY `$field` LIKE \"%$iso_path%\" ";
		else
			$sql.="BINARY `$field` LIKE \"%$path%\" ";
	}
	$row = $db_conn->getRow($sql);
	return $row;
}
function isFileInDescription($path){
	if (trim($path) == '')
		return false;
	$iso_path = $path;
	$path = iso_To_UnicodeEntities($path);
	$db_conn = db_connect();
	$fields_to_check = array('Title','Header','Body','Signature','Summary','Biblio','Copyright','URL');
	//$sql = "SELECT * FROM descriptions WHERE Files LIKE \"%$path%\" OR Files LIKE \"%$iso_path%\";";
	$sql = "SELECT * FROM descriptions WHERE Status LIKE \"Published\"  AND (";
	$first = TRUE;
	foreach($fields_to_check as $field){
		if(!$first)$sql.=" OR ";
		else $first=false;
		if ($path != $iso_path){
			$sql.="BINARY `$field` LIKE \"%>$path</%\" OR BINARY `$field` LIKE \"%>$iso_path</%\" ";
			$sql.="OR BINARY `$field` LIKE \"%../Files$path\\\"%\" OR BINARY `$field` LIKE \"%../Files$iso_path\\\"%\" ";
			$sql.="OR BINARY `$field` LIKE \"%[files_url]$path\\\"%\" OR BINARY `$field` LIKE \"%[files_url]$iso_path\\\"%\" ";
		}else
			$sql.="BINARY `$field` LIKE \"$path\" OR BINARY `$field` LIKE \"%>$path</%\" OR BINARY `$field` LIKE \"%../Files$path\\\"%\" OR BINARY `$field` LIKE \"%[files_url]$path\\\"%\" ";
	}
	$sql.=')';
	$row = $db_conn->getRow($sql);
	
	// si pas dans descriptions, on verifie dans descriptions_custom
	if(!$row){
		$sql = 'SELECT * FROM `descriptions_custom` WHERE `Status`="Published"  AND BINARY `Value` LIKE "%'.$path.'%"';
		$row = $db_conn->getRow($sql);
	}
	
	return $row;
}
function changeUsedFiles($old_path,$new_path){
	changeFileInDescription($old_path,$new_path);
	changeFileInContact($old_path,$new_path);
}
function changeFileInDescription($old_path,$new_path){
	$iso_old_path = $old_path;
	$old_path = iso_To_UnicodeEntities(encode_to_XML($old_path));
	$new_path = iso_To_UnicodeEntities(encode_to_XML($new_path));
	$db_conn = db_connect();
	$fields_to_check = array('Title','Header','Body','Signature','Summary','Biblio','Copyright','Custom');
	$sql = "SELECT * FROM descriptions WHERE ";
	$first = TRUE;
	foreach($fields_to_check as $field){
		if(!$first)$sql.=" OR ";
		else $first=false;
		if ($old_path != $iso_old_path)
			$sql.="BINARY `$field` LIKE \"%$old_path%\" OR BINARY `$field` LIKE \"%$iso_old_path%\" ";
		else
			$sql.="BINARY `$field` LIKE \"%$old_path%\" ";
	}
	$rs = $db_conn->Execute($sql);
	$fields_to_check[]='SearchText';
	if ($old_path!=$iso_old_path)
		$replace = array($old_path,$iso_old_path);
	else
		$replace = $old_path;
	while($row = $rs->FetchRow() ){
		$sql='UPDATE descriptions SET ';
		$first = TRUE;
		foreach($fields_to_check as $field){
			$new_value = str_replace($replace,$new_path,$row[$field]);
			if(!$first)$sql.=" , ";
			else $first=false;
			$sql.=" `$field`=\"".encodeQuote($new_value)."\" ";
		}
		$sql.=" WHERE ID=".$row["ID"].";";
		$db_conn->Execute($sql);
		// now also updating the modificationDate of the element
		$moduleInfo = moduleInfo($row["ModuleTargetID"]);
		$sql = 'UPDATE `'.$moduleInfo->tableName.'` SET `ModificationDate`="'.$GLOBALS['sushee_today'].'" WHERE `ID`='.$row['TargetID'].' ;';
		$db_conn->Execute($sql);
	}
	
	$custom_update_sql = 'UPDATE `descriptions_custom` SET `Value`=REPLACE(`Value`,"'.$old_path.'","'.$new_path.'") WHERE `Value` LIKE "%'.$old_path.'%"';
	$db_conn->Execute($custom_update_sql);
	
}
function changeFileInContact($old_path,$new_path){
	$old_path = iso_To_UnicodeEntities(encode_to_XML($old_path));
	$new_path = iso_To_UnicodeEntities(encode_to_XML($new_path));
	$db_conn = db_connect();
	$sql = "SELECT ID,Preview,SearchText FROM contacts WHERE BINARY Preview LIKE \"%$old_path%\" AND Activity=1 ;";
	$rs = $db_conn->Execute($sql);
	if($rs){
		while($row = $rs->FetchRow() ){
			$replace = $old_path;
			$new_files = str_replace($replace,$new_path,$row["Preview"]);
			$new_search = str_replace($replace,$new_path,$row["SearchText"]);
			$sql = "UPDATE contacts SET ModificationDate=\"".date("Y-m-d H:i:s")."\",Preview=\"".encodeQuote($new_files)."\",SearchText=\"".encodeQuote($new_search)."\" WHERE ID=".$row["ID"].";";
			$db_conn->Execute($sql);
		}
	}
}
function getDescriptions($moduleTargetID,$targetID,$languageID="",$profile='full',$status=''){
	$db_conn = db_connect();
	$specific_desc_version = false;
	if (is_numeric($targetID))
		$target_cond = "`TargetID`=$targetID";
	else if(is_array($targetID)){
		$target_cond = '';
		if(isset($_GET['version']) && isset($_GET['ID']) && isset($targetID[$_GET['ID']])){
			// asking for a specific version, must take it apart, because it may be as draft
			$specific_desc_version = true;
			unset($targetID[$_GET['ID']]);
		}
		if(sizeof($targetID)){
			foreach($targetID as $ID=>$value){
				$target_cond.=$ID.',';
			}
			$target_cond='( `TargetID` IN ('.substr($target_cond,0,-1).') )';
		}else
			$target_cond='( `TargetID` IN (-1) )';
	}
	if($GLOBALS["php_request"]){
		$sql = "SELECT `TargetID`,`".implode("`,`",getDescriptionProfileArray($profile))."` FROM `descriptions` WHERE ";
		$sql.= "(`Status`=\"published\" AND `ModuleTargetID`='$moduleTargetID' AND $target_cond )";
		if($specific_desc_version){
			$sql.=" OR (`ID`=".$_GET['version']." )";
		}
		$sql.=(($languageID!="")?' AND (`LanguageID` IN ("'.$languageID.'","shared") )':'').' ORDER BY `TargetID`,`LanguageID`;';
	}else{
		$status_cond = '';
		if($status=='' && $status!='all') // no specific status asked, we take all(because we need to fallback on unpublished if no published, on submitted if no unpublished, etc) except archived (taken only if status==all)
			$status_cond = '`Status`!="archived" AND ';
		$sql = "SELECT `TargetID`,`".implode("`,`",getDescriptionProfileArray($profile))."` FROM `descriptions` WHERE ".$status_cond."`ModuleTargetID`='$moduleTargetID' AND $target_cond ".(($languageID!="")?' AND (`LanguageID` IN ("'.$languageID.'","shared") )':'').' ORDER BY `TargetID`,`LanguageID`,FIELD(`Status`,\'published\',\'unpublished\',\'checked\',\'submitted\',\'draft\',\'archived\');';
	}
	//debug_log($sql);
	$rs = $db_conn->Execute($sql);
	return $rs;
}


function deleteDescriptions($moduleTargetID,$targetID){
	// in fact copy to descriptions History to keep all the steps
	// we use the adodb insertQuery autogeneration functionnality
	$db_conn = db_connect();
	// catch the old descriptions 
	$sql = "SELECT * FROM descriptions WHERE ModuleTargetID = $moduleTargetID AND TargetID = $targetID;";
	$recup_rs = $db_conn->Execute($sql);
	// pseudo request to have the table name and use the adoDb functionnality
	$sql = "SELECT * FROM descriptions_history WHERE ID = -1;";
	$pseudo_rs = $db_conn->Execute($sql);
	$sql = "";
	//copying to history table
	if($pseudo_rs){
		while($record = $recup_rs->FetchRow() ){
			$insertSQL = $db_conn->GetInsertSQL($pseudo_rs, $record);
			$db_conn->Execute($insertSQL);
			$query_result.=$insertSQL;
		}
	}
	//now delete in the original table
	$sql = "DELETE FROM descriptions WHERE ModuleTargetID=$moduleTargetID AND TargetID=$targetID;";
	$db_conn->Execute($sql);
	$query_result.=$sql;
	$sql = "SELECT * FROM descriptions_custom WHERE ModuleTargetID=$moduleTargetID AND TargetID=$targetID;";
	$recup_rs = $db_conn->Execute($sql);
	// pseudo request to have the table name and use the adoDb functionnality
	$sql = "SELECT * FROM descriptions_custom_history WHERE DescriptionID=-1;";
	$pseudo_rs = $db_conn->Execute($sql);
	$sql = "";
	//copying to history table
	if($pseudo_rs){
		while($record = $recup_rs->FetchRow() ){
			$insertSQL = $db_conn->GetInsertSQL($pseudo_rs, $record);
			$db_conn->Execute($insertSQL);
			$query_result.=$insertSQL;
		}
	}
	//now delete in the original table
	$sql = "DELETE FROM descriptions_custom WHERE ModuleTargetID=$moduleTargetID AND TargetID=$targetID;";
	$db_conn->Execute($sql);
	$query_result.=$sql;
	return $query_result;
}

function storeDescriptionsHistory($moduleTargetID,$targetID,$status,$languageID){
	// in fact copy to descriptions History to keep all the steps
	// we use the adodb insertQuery autogeneration functionnality
	
	$db_conn = db_connect();
	// catch the old descriptions 
	$sql = "SELECT * FROM descriptions WHERE ModuleTargetID='$moduleTargetID' AND TargetID='$targetID' AND Status=\"".$status."\" AND LanguageID=\"".$languageID."\";";
	//debug_log('storeDescriptionsHistory '.$sql);
	$recup_rs = $db_conn->Execute($sql);
	// pseudo request to have the table name and use the adoDb functionnality
	$sql = "SELECT * FROM descriptions_history WHERE ID=-1;";
	$pseudo_rs = $db_conn->Execute($sql);
	$sql = "";
	//copying to history table
	if($pseudo_rs){
		while($record = $recup_rs->FetchRow() ){
			unset($record['ID']);
			$insertSQL = $db_conn->GetInsertSQL($pseudo_rs, $record);
			$db_conn->Execute($insertSQL);
			$query_result.=$insertSQL;
		}
	}
}

function getNotLockedDescriptions(&$moduleInfo,&$sql){
	$db_conn = db_connect();
	$sql = "SELECT descriptions.* FROM descriptions LEFT  JOIN ".$moduleInfo->tableName." ON descriptions.TargetID = ".$moduleInfo->tableName.".ID WHERE descriptions.ModuleTargetID=".$moduleInfo->ID."  ";
	$sql.= " AND (".$moduleInfo->tableName.".ID IS NULL OR (".$moduleInfo->tableName.".IsLocked=0 AND ".$moduleInfo->tableName.".ID>1));";
	$rs = $db_conn->Execute($sql);
	return $rs;
}
function getLockedDescriptions(&$moduleInfo,&$sql){
	$db_conn = db_connect();
	$sql = "SELECT descriptions.* FROM descriptions LEFT  JOIN ".$moduleInfo->tableName." ON descriptions.TargetID = ".$moduleInfo->tableName.".ID WHERE descriptions.ModuleTargetID=".$moduleInfo->ID."  ";
	$sql.= " AND (".$moduleInfo->tableName.".IsLocked=1 OR ".$moduleInfo->tableName.".ID=1);";
	$rs = $db_conn->Execute($sql);
	return $rs;
}
function deleteModuleDescriptions($moduleID){
	$db_conn = db_connect();
	$moduleInfo = moduleInfo($moduleID);
	$sql = "";
	$rs = getNotLockedDescriptions($moduleInfo,$sql);
	$total_sql.=$sql;
	if($rs){
		while ( $row = $rs->FetchRow() ){
			$del_sql = "DELETE FROM descriptions WHERE ModuleTargetID=".$row['ModuleTargetID']." AND TargetID=".$row['TargetID'].";";
			$db_conn->Execute($del_sql);
			$total_sql.=$del_sql;
		}
	}
	return $total_sql;
}
?>