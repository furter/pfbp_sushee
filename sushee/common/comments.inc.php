<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/comments.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/*-------------------------------------------------------------
			COMMENT
-------------------------------------------------------------*/
function generateCommentsXML($rs,$output='html'){
	if (!$rs)
		return "";
	$profile_array = array("ID","CreationDate","ModificationDate","Type","Title","Body","Checked","IP","File");
	$profile_array_upper = array("ID","CREATIONDATE","MODIFICATIONDATE","TYPE","TITLE","BODY","CHECKED","IP","FILE");
	$moduleContactInfo = moduleInfo('contact');
	require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
	require_once(dirname(__FILE__)."/../common/filter.class.php");
	// taking the mini profile to have some information about the creator
	
	// fields authorized for reading
	$fields_array=$moduleContactInfo->getFieldsBySecurity('R');
	$row = $rs;
	$filter = new Url2AnchorFilter();
	
	//while($row = $rs->FetchRow()){
		$moduleInfo = moduleInfo($row["ModuleTargetID"]);
		$str.="<COMMENT ID='".$row["ID"]."' module='".$moduleInfo->name."' targetID='".$row["TargetID"]."'>";
		// we display the creator information
		$str.="<CONTACT ID='".$row["CreatorID"]."'>";
		if($row["CreatorID"]){
			$contact = getInfo($moduleContactInfo,$row["CreatorID"]);
			$str.=generateInfoXML($moduleContactInfo,$contact,$fields_array,false/*$creator_profile_array*/);
		}
		$str.="</CONTACT>";
		$profile_size = count($profile_array);
		$eol='';
		if ($output=='indesign')
			$eol="\n";
		for($i=0;$i<$profile_size;$i++){
			$n=$profile_array[$i];
			$value=$row[$n];
			//$n=strtoupper($n);
			if($n=='Body'){
				$value = $filter->execute($value);
			}
			$n=$profile_array_upper[$i];
			$str.='<'.$n.'>'.$value.'</'.$n.'>'.$eol;
		}
		$str.="</COMMENT>";
	//}
	//$str="<COMMENTS>$str</COMMENTS>";
	return $str;
}
function getComments($moduleTargetID,$targetID,$type=""){
	$db_conn = db_connect();
	if (is_numeric($targetID))
		$target_cond = "TargetID=$targetID";
	else if(is_array($targetID)){
		$first = true;
		foreach($targetID as $ID=>$value){
			if($first!=true) $target_cond.=" OR ";
			else $first=false;
			$target_cond.="TargetID=".$ID;
		}
		$target_cond='('.$target_cond.')';
	}
	$sql = "SELECT * FROM comments WHERE Activity=1 AND ModuleTargetID=$moduleTargetID AND $target_cond ".(($type!="")?' AND Type="'.$type.'"':'').' ORDER BY CreationDate;';
	$rs = $db_conn->Execute($sql);
	return $rs;
}
function getComment($ID){
	$db_conn = db_connect();
	$sql = "SELECT * FROM comments WHERE ID=$ID;";
	$row = $db_conn->GetRow($sql);
	return $row;
}
function deleteComments($moduleTargetID,$ID){
	$db_conn = db_connect();
	$sql = "DELETE FROM comments WHERE ModuleTargetID=$moduleTargetID AND TargetID=$ID;";
	$db_conn->Execute($sql);
	return $sql;
}
function deleteComment($ID){
	$db_conn = db_connect();
	$sql = "DELETE FROM comments WHERE ID=$ID;";
	$db_conn->Execute($sql);
	return $sql;
}
?>