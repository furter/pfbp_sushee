<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/downloadCSV.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
require_once(dirname(__FILE__)."/../private/checkLogged.inc.php");
require_once(dirname(__FILE__)."/../common/descriptions.inc.php");
require_once(dirname(__FILE__)."/../common/comments.inc.php");

session_write_close();
global $separator;
global $enclosure;
// general CSV props
if(isset($_GET['separator']))
	$separator = $_GET['separator'];
else
	$separator = ';';
if($separator==='tab')
	$separator="\t"; // also works with separator = %09
if(isset($_GET['enclosure']))
	$enclosure = $_GET['enclosure'];
else
	$enclosure = '"';
$eol = "\r\n";
$ext = "csv";
include_once(dirname(__FILE__)."/../common/phpSniff.class.php");
$client =& new phpSniff();

$user_agent=$client->property('ua');
$IP=$client->property('ip');
$browser=$client->property('long_name');
$platform=$client->property('platform');

if($platform==='mac'){
	$eol = "\r";
	$ext = "txt";
}
	
if ( isset($_GET['module']) ){
	$moduleInfo = moduleInfo($_GET['module']);
	if(!$moduleInfo->loaded)
		xml_out("<?xml version='1.0'?><RESPONSE>".generateMsgXML(1,"The module isn't correct.")."</RESPONSE>");
	$mod_name = strtoupper($moduleInfo->name);
	if(isset($_GET['ID']) /*&& is_numeric($_GET['ID'])*/ && $_GET['ID']!=='*')
	$query = '<QUERY><GET><'.$mod_name.' ID="'.$_GET['ID'].'"></'.$mod_name.'></GET></QUERY>';
	else if(isset($_GET['ID']) && $_GET['ID']==='*')
	$query = '<QUERY><SEARCH><'.$mod_name.'></'.$mod_name.'></SEARCH></QUERY>';
	else
	$query = false;
}else if( isset($_GET['mediatype']) ){
	$moduleInfo = moduleInfo('media');
	$query = '<QUERY><SEARCH><MEDIA mediatype="'.$_GET['mediatype'].'"></MEDIA></SEARCH></QUERY>';
}else
	xml_out("<?xml version='1.0'?><RESPONSE>".generateMsgXML(1,"No module was given.")."</RESPONSE>");

$db_conn = db_connect();
if($_GET['debug']!=='true')
setDownloadHeaders($moduleInfo->name.".".$ext);
// Excel config 
// separator ;
// enclosure on
// encoding iso

// Outlook config
// separator ,
// enclosure on
// encoding iso
$descriptions = false;
if($_GET['encoding']==='utf8')
	$encoding = 'utf8';
else
	$encoding = 'iso';

$info = true;
if($_GET['info']==='false')
	$info = false;
if(isset($_GET['descriptions'])){
	$descriptions = true;
	$desc_fields = getDescriptionProfileArray('templateCSV');
	// getting the published languages
	if($_GET['descriptions']==='true'){
		$sql = 'SELECT * FROM `medialanguages`;';
		$medialg_rs = $db_conn->Execute($sql);
		$media_languages = array();
		while($row = $medialg_rs->FetchRow()){
			$media_languages[]=$row['languageID'];
		}
	}else{
		$media_languages=explode(',',$_GET['descriptions']);
	}
}
if($_GET['categories']==='true'){
	$categories = true;
}
if($_GET['comments']==='true'){
	$comments = true;
	$comments_profile_array = array("ID","Type","Title","Body","Checked");
}
function addDescriptionColumns($media_languages,$desc_fields,$first_sep=true){
	global $separator;
	global $enclosure;
	$first = true;
	foreach($desc_fields as $n){
		$i = 1;
		foreach($media_languages as $lgID){
			$n=strtoupper($n);
			if( ($first==true && $first_sep) || $first==false)
			echo $separator;
			echo $enclosure."DESCRIPTIONS.DESCRIPTION[@languageID='".$lgID."'].".$n.$enclosure;
			$i++;
			$first = false;
		}
	}
}
function addCategoriesColumns($categ_nbr,$first_sep=true){
	global $separator;
	global $enclosure;
	$first = true;
	for($i=1;$i<=$categ_nbr;$i++){
		if( ($first==true && $first_sep) || $first==false)
		echo $separator;
		echo $enclosure."CATEGORIES.CATEGORY[".$i."].@path".$enclosure;
		$first = false;
	}
}
function addCommentsColumns($comment_nbr,$first_sep=true,$mode='creation'){
	global $separator;
	global $enclosure;
	$first = true;
	if($mode=='creation')
	$profile_array = array(/*"ID","CreationDate","ModificationDate",*/"Type","Title","Body","Checked");
	else
	$profile_array = array("ID",/*"CreationDate","ModificationDate",*/"Type","Title","Body","Checked");
	for($i=1;$i<=$comment_nbr;$i++){
		foreach($profile_array as $n){
			$n=strtoupper($n);
			if( ($first==true && $first_sep) || $first==false)
				echo $separator;
			echo $enclosure."COMMENTS.COMMENT[".$i."].".$n.$enclosure;
			$first = false;
		}
	}
}
if($query){ // a query
	$search_xml = new XML($query);
	$current_path = '/QUERY[1]/*[1]';
	$sql = '';
	$rs = getResultSet($moduleInfo,$search_xml,$current_path,$sql);
	$fields_array = $moduleInfo->getFieldsBySecurity('R');
	$fields_nbr = sizeof($fields_array);
	// the ID column
	echo $enclosure.'ID'.$enclosure;
	if($info){
		for($i=0;$i<$fields_nbr;$i++){
			$n=$fields_array[$i];
			if(!isset($moduleInfo->forbiddenFields[$n]) ){
				$n=strtoupper($n);
				if($n!='ID' && !($moduleInfo->name=='contact' && $n=='PASSWORD')){
					echo $separator;
					echo $enclosure.'INFO.'.$n.$enclosure;
				}
			}
		}
	}
	if($descriptions)
		addDescriptionColumns($media_languages,$desc_fields);
	if($categories || $comments){
		// first finding the max categories to put the right number of columns
		$max_categ = 1;
		$max_comment = 1;
		while($row = $rs->FetchRow()){
			$ID = $row['ID'];
			if($categories){
				$count_sql = 'SELECT COUNT(*) AS total FROM `categorylinks` WHERE `ModuleTargetID`=\''.$moduleInfo->ID.'\' AND `TargetID`=\''.$ID.'\';';
				$count_row = $db_conn->getRow($count_sql);
				if($count_row['total']>$max_categ)
					$max_categ = $count_row['total'];
			}
			if($comments){
				$count_sql = 'SELECT COUNT(*) AS total FROM `comments` WHERE `ModuleTargetID`=\''.$moduleInfo->ID.'\' AND `TargetID`=\''.$ID.'\';';
				$count_row = $db_conn->getRow($count_sql);
				if($count_row['total']>$max_comment)
					$max_comment = $count_row['total'];
			}
		}
		if($categories)
			addCategoriesColumns($max_categ);
		if($comments){
			addCommentsColumns($max_comment,true,'update');
		}
	}
	echo $eol;
	$rs->MoveFirst();
	
	while($row = $rs->FetchRow()){
		$first = true;
		echo $enclosure.$row['ID'].$enclosure;
		if($info){
			for($i=0;$i<$fields_nbr;$i++){
				$n=$fields_array[$i];
				if($n!='ID' && !($moduleInfo->name=='contact' && $n=='Password') && !isset($moduleInfo->forbiddenFields[$n]) ){
					echo $separator;
					$data = str_replace('"',"\"\"",UnicodeEntities_To_utf8($row[$n]));
					if($encoding=='iso' && is_utf8($data))
						$data = utf8_decode($data);
					if($platform==='mac')
						$data = str_replace(array("\r\n","\r","\n")," ",$data);
					echo $enclosure.$data.$enclosure;
				}
			}
		}
		if($descriptions){
			foreach($desc_fields as $n){
				$i = 1;
				foreach($media_languages as $lgID){
					/*$n=strtoupper($n);*/
					$desc_rs = getDescriptions($moduleInfo->ID,$row['ID'],$lgID);
					$desc_row = $desc_rs->FetchRow();
					$data = UnicodeEntities_To_utf8($desc_row[$n]);
					echo $separator;
					if($encoding=='iso' && is_utf8($data))
						$data = utf8_decode($data);
					echo $enclosure.str_replace('"','""',decode_from_xml($data)).$enclosure;
					$i++;
				}
			}
		}
		if($categories){
			$categ_sql = 'SELECT * FROM `categorylinks` AS lk LEFT JOIN `categories` AS cats ON lk.`CategoryID` = cats.`ID` WHERE lk.`TargetID` = \''.$row['ID'].'\' AND lk.`ModuleTargetID`=\''.$moduleInfo->ID.'\';';
			$categ_count = 0;
			$categ_rs = $db_conn->Execute($categ_sql);
			while($categ_row = $categ_rs->FetchRow()){
				$data =  $categ_row['Path'];
				echo $separator;
				if($encoding=='iso' && is_utf8($data))
					$data = utf8_decode($data);
				echo $enclosure.$data.$enclosure;
				$categ_count++;
			}
			while($categ_count<$max_categ){
				echo $separator;
				echo $enclosure.$enclosure;
				$categ_count++;
			}
		}
		if($comments){
			$comm_rs = getComments($moduleInfo->ID,$row['ID']);
			
			while($comm_row = $comm_rs->FetchRow()){
				foreach($comments_profile_array as $n){
					$data = UnicodeEntities_To_utf8($comm_row[$n]);
					echo $separator;
					if($encoding=='iso' && is_utf8($data))
						$data = utf8_decode($data);
					echo $enclosure.$data.$enclosure;
				}
			}
		}
		echo $eol;
	}
}else{ // the template
	$fields_array = $moduleInfo->getFieldsBySecurity('W');
	$fields_nbr = sizeof($fields_array);
	$first = true;
	if($info){
		for($i=0;$i<$fields_nbr;$i++){
			$n=$fields_array[$i];
			$n=strtoupper($n);
			if($first==false) echo $separator;
			else $first = false;
			echo $enclosure.'INFO.'.$n.$enclosure;
		}
	}
	if($descriptions){
		addDescriptionColumns($media_languages,$desc_fields,$info==true);
	}
	if($categories){
		$categ_nbr = 2;
		addCategoriesColumns($categ_nbr,($info==true || $descriptions==true));
	}
	/*if($comments){
		$comment_nbr = 2;
		addCommentsColumns($comment_nbr,($info==true || $descriptions==true || $categories==true),'creation');
	}*/
}
?>
