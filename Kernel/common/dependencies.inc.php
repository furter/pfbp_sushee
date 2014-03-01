<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/dependencies.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/*-------------------------------------------------------------
			DEPENDENCIES
-------------------------------------------------------------*/
require_once(dirname(__FILE__)."/../common/dependencies.class.php");


function getDepTargetsInfo($moduleOriginID=0,$originID,$dependencyTypeID,$time=FALSE,$startIndex=false,$number=false){
	
	$db_conn = db_connect();
    if ( $dependencyTypeID != 0 ){
		$dependencyType = depType($dependencyTypeID);
		
		$moduleTargetInfo = moduleInfo($dependencyType->ModuleTargetID);
		$originFieldname = $dependencyType->getOriginFieldname();
		if (is_numeric($originID))
			$origin_cond = "dep.`".$originFieldname."`=$originID";
		else if(is_array($originID)){
			$origin_cond = "dep.`".$originFieldname."`=".$originID[0];
			for($i=1;$i<sizeof($originID);$i++){
				$origin_cond.=" OR dep.`".$originFieldname."`=".$originID[$i];
			}
			$origin_cond='('.$origin_cond.')';
		}
        $sql = "SELECT DISTINCT element.*,dep.`Comment`,dep.`".$dependencyType->getOriginFieldname()."` FROM `".$moduleTargetInfo->tableName."` AS element,`".$dependencyType->getTableName()."` AS dep WHERE $origin_cond AND  dep.`DependencyTypeID`=".$dependencyType->getIDInDatabase()." AND element.`ID`=dep.`".$dependencyType->getTargetFieldname()."` AND element.`Activity`=1";
		// not returning unpublished
		if ($GLOBALS["php_request"] && $moduleTargetInfo->name=='media' && !($GLOBALS["take_unpublished"]===true))
			$sql.=' AND element.`Published`=1 ';
		$sql.=" ORDER BY dep.`".$dependencyType->getTargetFieldname()."`,dep.`".$dependencyType->getOrderingFieldname()."` ASC";
    }else{
        return FALSE;
	}
	if ($startIndex && $number)
		$sql.=' LIMIT '.$startIndex.','.$number;
	else if($number)
		$sql.=' LIMIT '.$number;
	sql_log($sql);
    $rs = $db_conn->Execute($sql);
	
	//echo "$sql<br/>";
    return $rs;
}
function getDependenciesFrom($moduleOriginID=0,$originID,$dependencyTypeID,$time=FALSE){
    $db_conn = db_connect();
    if ( $dependencyTypeID != 0 ){
		//if ($time!=FALSE){
			$dependencyType = depType($dependencyTypeID);
			$originFieldname = $dependencyType->getOriginFieldname();
			$moduleTargetInfo = moduleInfo($dependencyType->ModuleTargetID);
			if (is_numeric($originID))
				$origin_cond = "dep.`".$originFieldname."`=$originID";
			else if(is_array($originID)){
				$origin_cond = "dep.`".$originFieldname."`=".$originID[0];
				for($i=1;$i<sizeof($originID);$i++){
					$origin_cond.=" OR dep.`".$originFieldname."`=".$originID[$i];
				}
				$origin_cond='('.$origin_cond.')';
			}
			$sql = "SELECT dep.* FROM `".$dependencyType->getTableName()."` AS dep,`".$moduleTargetInfo->tableName."` AS element WHERE $origin_cond AND dep.`DependencyTypeID`=".$dependencyType->getIDInDatabase()." AND dep.`".$dependencyType->getTargetFieldname()."`=element.`ID` ";
			// not returning unpublished
			if ($GLOBALS["php_request"] && $moduleTargetInfo->name=='media' && !($GLOBALS["take_unpublished"]===true))
				$sql.=' AND element.`Published`=1 ';
			$sql.=" ORDER BY dep.`".$dependencyType->getTargetFieldname()."`,dep.`".$dependencyType->getOrderingFieldname()."` ASC";
		
	}else{
        return FALSE;
	}
	//sql_log($sql);
    $rs = $db_conn->Execute($sql);
	//echo "$sql<br/>";
    return $rs;
}
function getDependenciesTo($moduleTargetID=0,$targetID,$dependencyTypeID){
	$db_conn = db_connect();
	if ( $dependencyTypeID != 0 ){
		$depType = depType($dependencyTypeID);
		$sql = "SELECT * FROM `".$depType->getTableName()."` WHERE `".$depType->getTargetFieldname()."`='$targetID' AND `DependencyTypeID`='$dependencyTypeID';";
	}else
		return FALSE;
	//sql_log($sql);
	$rs = $db_conn->Execute($sql);
	return $rs;
}
function getDependencyTypesFrom($moduleOriginID,$db_conn=false){
	if (!$db_conn)
		$db_conn = db_connect();
	$sql = "SELECT * FROM `dependencytypes` WHERE `ModuleOriginID`=$moduleOriginID;";
	$rs = $db_conn->Execute($sql);
	return $rs;
}
function getDependencyTypesTo($moduleTargetID,$db_conn=false){
	if (!$db_conn)
		$db_conn = db_connect();
	$sql = "SELECT * FROM `dependencytypes` WHERE `ModuleTargetID`=$moduleTargetID;";
	$rs = $db_conn->Execute($sql);
	return $rs;
}
function getDependencyTypesFromTo($moduleOriginID,$moduleTargetID){
	$db_conn = db_connect();
	$sql = "SELECT * FROM `dependencytypes` WHERE `ModuleOriginID`=$moduleOriginID AND `ModuleTargetID`=$moduleTargetID;";
	$rs = $db_conn->Execute($sql);
	return $rs;
}

function getNotLockedDependencyTypes(&$moduleInfo,&$sql){
	$db_conn = db_connect();
	$moduleID = $moduleInfo->ID;
	$sql = "SELECT * FROM `dependencytypes` WHERE (`ModuleOriginID`=$moduleID OR `ModuleTargetID`=$moduleID) AND `IsLocked`!=1;";
	$rs = $db_conn->Execute($sql);
	return $rs;
}
function getLockedDependencyTypes(&$moduleInfo,&$sql){
	$db_conn = db_connect();
	$moduleID = $moduleInfo->ID;
	$sql = "SELECT * FROM `dependencytypes` WHERE `ModuleOriginID`=$moduleID AND `IsLocked`=1;";
	$rs = $db_conn->Execute($sql);
	return $rs;
}
function deleteModuleDependencyTypes($moduleID){
	$db_conn = db_connect();
	$sql = "";
	$moduleInfo = moduleInfo($moduleID);
	$rs = getNotLockedDependencyTypes($moduleInfo,$sql);
	while ($row = $rs->FetchRow()){
		$sql = "DELETE FROM `dependencytraductions` WHERE `DependencyTypeID`=".$row["ID"].";";
		$db_conn->Execute($sql);
	}
	$sql = "DELETE FROM `dependencytypes` WHERE (`ModuleOriginID`=$moduleID OR `ModuleTargetID`=$moduleID) AND `IsLocked`!=1;";
	$db_conn->Execute($sql);
	return $sql;
}

function existsDependency($moduleOriginID,$originID,$moduleTargetID,$targetID){
	$db_conn = db_connect();
	$depTypes = new DependencyTypeSet($moduleOriginID,$moduleTargetID);
	while($depType = $depTypes->next()){
		$sql = "SELECT * FROM `".$depType->getTablename()."` AS dep WHERE dep.`".$depType->getOriginFieldname()."`=$originID AND dep.`".$depType->getTargetFieldname()."`=$targetID AND dep.`DependencyTypeID`=".$depType->getIDInDatabase();
		$row = $db_conn->getRow($sql);
		if($row){
			return TRUE;
		}
	}
	return FALSE;
}
function generateDependenciesXML($rs){
	while($link = $rs->FetchRow()){
	  if ($link["DependencyTypeID"]!="0"){
		$dependencyType = depType($link["DependencyTypeID"]);
	  }
	  $moduleTargetInfo = moduleInfo($dependencyType->ModuleTargetID);
	  $links_str.="<".strtoupper($moduleTargetInfo->name)." ID=\"".$link["TargetID"]."\">";
	  $links_str.="<COMMENT>".encode_to_XML($link["Comment"])."</COMMENT>";
	  $links_str.="<DEPINFO>".$link["DepInfo"]."</DEPINFO>";
	  $links_str.="</".strtoupper($moduleTargetInfo->name).">";
  }
  return $links_str;
}
function deleteDependenciesFrom($moduleOriginID=0,$originID,$dependencyTypeID=0){
    $db_conn = db_connect();
    if ( $dependencyTypeID != 0 ){
		$depType = depType($dependencyTypeID);
        $sql = "SELECT *  FROM `".$depType->getTableName()."` WHERE `".$depType->getOriginFieldname()."`=$originID AND `DependencyTypeID`=".$depType->getIDInDatabase();
		sql_log($sql);
		$rs = $db_conn->Execute($sql);
		while($row = $rs->FetchRow()){
			$dep = new Dependency($depType,$row[$depType->getOriginFieldname()],$row[$depType->getTargetFieldname()],$row[$depType->getOrderingFieldname()],$row['DepInfo'],$row['Comment']);
			$dep->delete();
		}
	}else if ( $moduleOriginID != 0 ){
		$depTypes = new DependencyTypeSet($moduleOriginID);
		while ($depType = $depTypes->next()){
			$sql = "SELECT * FROM `".$depType->getTableName()."` WHERE `".$depType->getOriginFieldname()."`='$originID' AND `DependencyTypeID`=".$depType->getIDInDatabase();
			sql_log($sql);
			$rs = $db_conn->Execute($sql);
			if($rs){
				while($row = $rs->FetchRow()){
					$dep = new Dependency($depType,$row[$depType->getOriginFieldname()],$row[$depType->getTargetFieldname()],$row[$depType->getOrderingFieldname()],$row['DepInfo'],$row['Comment']);
					$dep->delete();
				}
			}
		}
    }else{
        return FALSE;
	}
    $db_conn->Execute($sql);
    return $sql;
}

function deleteDependenciesTo($moduleTargetID,$originID,$dependencyTypeID=0){
    $db_conn = db_connect();
    if ( $dependencyTypeID != 0 ){
		$depType = depType($dependencyTypeID);
        $sql = "SELECT * FROM `".$depType->getTableName()."` WHERE `".$depType->getTargetFieldname()."`=$originID AND `DependencyTypeID`=".$depType->getIDInDatabase();
		sql_log($sql);
		$rs = $db_conn->Execute($sql);
		while($row = $rs->FetchRow()){
			$dep = new Dependency($depType,$row[$depType->getOriginFieldname()],$row[$depType->getTargetFieldname()],$row[$depType->getOrderingFieldname()],$row['DepInfo'],$row['Comment']);
			$dep->delete();
		}
	}else if ( $moduleTargetID != 0 ){
		$depTypes = new DependencyTypeSet(false,$moduleTargetID);
		while ($depType = $depTypes->next()){
			$sql = "SELECT * FROM `".$depType->getTableName()."` WHERE `".$depType->getTargetFieldname()."`=$originID AND `DependencyTypeID`=".$depType->getIDInDatabase();
			sql_log($sql);
			$rs = $db_conn->Execute($sql);
			if($rs){
				while($row = $rs->FetchRow()){
					$dep = new Dependency($depType,$row[$depType->getOriginFieldname()],$row[$depType->getTargetFieldname()],$row[$depType->getOrderingFieldname()],$row['DepInfo'],$row['Comment']);
					$dep->delete();
				}
			}
		}
	}else{
        return FALSE;
	}
    
    return $sql;
}
function deleteModuleDependencies($moduleID){
	$db_conn = db_connect();
	// searching the deptypes starting from the module and getting to the module
	$sql = "SELECT * FROM `dependencytypes` WHERE `ModuleOriginID`=$moduleID OR `ModuleTargetID`=$moduleID;";
	$rs = $db_conn->Execute($sql);
	$total_sql = $sql."<br/>";
	while ($row = $rs->FetchRow()){
		$dependencyTypeID = $row['ID'];
		$depType = depType($dependencyTypeID);
		// handling the case where the deptype cannot be deleted
		if ($row['IsLocked']=='1'){
			$originModuleInfo = moduleInfo($row['ModuleOriginID']);
			$targetModuleInfo = moduleInfo($row['ModuleTargetID']);
			
			// Module is the target module of the deptype
			if ($targetModuleInfo->ID == $moduleID){
				$direction = $depType->getTargetFieldname();
				$moduleInfo = $targetModuleInfo;
			}else{
			// Module is the origin module of the deptype
				$direction = $depType->getOriginFieldname();
				$moduleInfo = $originModuleInfo;
			}
			
			// taking the elements not locked
			$sql = "SELECT dep.* FROM `".$depType->getTableName()."` AS dep LEFT  JOIN `".$moduleInfo->tableName."` AS element ON dep.$direction = element.`ID` WHERE dep.`DependencyTypeID` = ".$depType->getIDInDatabase();
			$sql.= " AND (element.`ID` IS NULL OR ( element.`IsLocked`=0  AND element.`ID`>1 ) );";
			$total_sql.= $sql;
			
			$dep_rs = $db_conn->Execute($sql);
			
			// if origin module is the same as the target module, we already took the dependencies where the target is an element of the module, but we should also take the deps where the origin is an element of the module
			if ($originModuleInfo->ID == $targetModuleInfo->ID){
				$sql2 = "SELECT `dep`.* FROM `".$depType->getTableName()."` AS dep LEFT  JOIN ".$moduleInfo->tableName." AS element ON dep.`".$depType->getOriginFieldname()."` = element.`ID` WHERE dep.`DependencyTypeID` = ".$depType->getIDInDatabase();
				$sql2.= " AND (element.`ID` IS NULL OR (element.`IsLocked`=0 AND element.`ID`>1));";
				$dep_rs2 = $db_conn->Execute($sql2);
			}else{
			// module are different
				$sql2 ="";
			}
			
			// verifying the request is valid and we have a valid result set
			if($dep_rs){
				$dep_row = $dep_rs->FetchRow();
				while ($dep_row ){
					
					// deleting in the dependencies table
					$del_sql = "DELETE FROM `".$depType->getTableName()."` WHERE `DependencyTypeID`=".$dep_row['DependencyTypeID']." AND `OriginID`=".$dep_row['OriginID']." AND `TargetID`=".$dep_row['TargetID'].";";
					$db_conn->Execute($del_sql);
					$total_sql.= $del_sql;
					
					// getting to the next row
					$dep_row = $dep_rs->FetchRow();
					
					// if not next row, but a second record set exists, taking now the rows from the second record set
					if (!$dep_row && $sql2){
						$total_sql.= $sql2;
						$dep_rs = $dep_rs2;
						$dep_row = $dep_rs->FetchRow();
						$sql2 = "";
					}
				}
			}
		}else{
			// normal deptype, can be deleted
			$sql = "DELETE FROM `".$depType->getTableName()."` WHERE `DependencyTypeID`=".$depType->getIDInDatabase();
			$db_conn->Execute($sql);
			$total_sql.= $sql;
		}
		$total_sql.="<br/>";
	}
    return $total_sql;
}

function getDepTargets($originID,$dependencyTypeID){
	
	$db_conn = db_connect();
	$dependencyType = depType($dependencyTypeID);
	$moduleTargetInfo = moduleInfo($dependencyType->ModuleTargetID);
	
	if (is_numeric($originID)){
		$origin_cond = "dep.`".$dependencyType->getOriginFieldname()."`=$originID";
	}else if(is_array($originID)){
		$origin_cond='(dep.`'.$dependencyType->getOriginFieldname().'` IN ('.implode(',',$originID).'))';
	}
	
	$sql = "SELECT DISTINCT element.*,dep.`Comment` AS DepComment,dep.`DepInfo`,dep.`".$dependencyType->getOriginFieldname()."` FROM `".$moduleTargetInfo->tableName."` AS element,`".$dependencyType->getTablename()."` AS dep WHERE $origin_cond AND  dep.`DependencyTypeID`='".$dependencyType->getIDInDatabase()."' AND element.`ID`=dep.`".$dependencyType->getTargetFieldname()."` AND element.`Activity`=1";
	
	if ($GLOBALS["php_request"] && $moduleTargetInfo->name=='media' && !($GLOBALS["take_unpublished"]===true)){
		$sql.=' AND element.`Published`=1 ';
	}
	$sql.=" ORDER BY dep.`".$dependencyType->getOriginFieldname()."`,dep.`DependencyTypeID`,dep.`".$dependencyType->getOrderingFieldname()."` ASC";
	
	sql_log($sql);
	$rs = $db_conn->Execute($sql);
	
	return $rs;
}
?>