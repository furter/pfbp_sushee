<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/export_vcard.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
require_once(dirname(__FILE__)."/../common/mimemail.class.php");

if ( !isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) && ($_GET['contact']==='all' || isset($_GET['group']) ) ){
	echo "You're not logged";
	die();
}
$module= 'contact';
if ($_GET['contact'])
	$contactID=explode(',',$_GET['contact']);
if ($_GET['group'])
	$groupID=explode(',',$_GET['group']);

if(isset($_GET['contact']) && !isset($_GET['depOf']) && !isset($_GET['inGroup']) && !isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'])){
	echo "You're not logged";
	die();
}
if ($_GET['contact'] && !isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) ){
	$groupModuleInfo = moduleInfo('group');
	$contactModuleInfo = moduleInfo('contact');
	foreach($contactID as $oneID){
		$bool = false;
		if(isset($_GET['depOf']))
			$bool = existsDependency($contactModuleInfo->ID,$_GET['depOf'],$contactModuleInfo->ID,$_GET['contact']);
		else if(isset($_GET['inGroup']))
			$bool = existsDependency($groupModuleInfo->ID,$_GET['inGroup'],$contactModuleInfo->ID,$_GET['contact']);
		if($bool==false){
			echo "Not authorized";
			die();
		}
	}
}

function generateVCard($row){
	$eol = "\r\n";
	$path = "/RESPONSE[1]/RESULTS[1]/CONTACT[1]";
	$path_org = $path."/DEPENDENCIES/DEPENDENCY[@type='contactWork']/CONTACT[1]";
	
	
	$moduleInfo = moduleInfo('contact');
	require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
	$xml_str = '<?xml version="1.0"?><RESPONSE><RESULTS>'.generateXMLOutput($row/*$this_rs*/,$moduleInfo,array('profile_name'=>'complete'),2).'</RESULTS></RESPONSE>';
	$xml = new XML($xml_str);
	$contact_str="BEGIN:VCARD$eol";
	$contact_str.="VERSION:2.1$eol";
	$country = getCountryInfo($xml->getData($path."/INFO/COUNTRYID"));
	$country2 = getCountryInfo($xml->getData($path_org."/INFO/COUNTRYID"));
	$contact_pers="";
	if ($xml->getData($path."/INFO/CONTACTTYPE")=="PP"){
		$title = (($xml->getData($path."/INFO/TITLE"))?encode_for_vcard($xml->getData($path."/INFO/TITLE"))." ":"");
		$contact_str.="FN;ENCODING=QUOTED-PRINTABLE:".$title.encode_for_vcard($xml->getData($path."/INFO/FIRSTNAME"))." ".encode_for_vcard($xml->getData($path."/INFO/LASTNAME")).$eol;
		$contact_str.="N;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/LASTNAME")).";".encode_for_vcard($xml->getData($path."/INFO/FIRSTNAME")).$eol;
		$contact_str.="NICKNAME;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/DENOMINATION")).$eol;
		$contact_str.="ADR;Home;ENCODING=QUOTED-PRINTABLE:;".encode_for_vcard($xml->getData($path."/INFO/ADDRESS")).";;".encode_for_vcard($xml->getData($path."/INFO/CITY")).";;".encode_for_vcard($xml->getData($path."/INFO/POSTALCODE")).";".encode_for_vcard($country["eng"]).$eol;
		$contact_str.="ORG;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path_org."/INFO/DENOMINATION")).$eol;
		$contact_str.="ADR;Work;ENCODING=QUOTED-PRINTABLE:;".encode_for_vcard($xml->getData($path_org."/INFO/ADDRESS")).";;".encode_for_vcard($xml->getData($path_org."/INFO/CITY")).";;".encode_for_vcard($xml->getData($path_org."/INFO/POSTALCODE")).";".encode_for_vcard($country2["eng"]).$eol;
		$contact_str.="URL;Work:".encode_for_vcard($xml->getData($path_org."/INFO/WEBSITE")).$eol;
		if ($xml->getData($path."/INFO/PHONE1"))
			$contact_str.="TEL;Work;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/PHONE1")).$eol;
		else
			$contact_str.="TEL;Work;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path_org."/INFO/PHONE1")).$eol;
	}else{
		$contact_str.="FN;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/DENOMINATION")).(($xml->getData($path."/INFO/TITLE"))?" ".encode_for_vcard($xml->getData($path."/INFO/TITLE")):"").$eol;
		if($xml->getData($path."/INFO/FIRSTNAME"))
		   $contact_pers = encode_for_vcard($xml->getData($path."/INFO/FIRSTNAME"))." ".encode_for_vcard($xml->getData($path."/INFO/LASTNAME")).$eol;
		$contact_str.="ADR;Work;ENCODING=QUOTED-PRINTABLE:;".encode_for_vcard($xml->getData($path."/INFO/ADDRESS")).";;".encode_for_vcard($xml->getData($path."/INFO/CITY")).";;".encode_for_vcard($xml->getData($path."/INFO/POSTALCODE")).";".encode_for_vcard($country["eng"]).$eol;
		$contact_str.="ORG;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/DENOMINATION")).$eol;
	}
	$contact_str.="EMAIL;Internet;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/EMAIL1")).$eol;
	$contact_str.="TITLE;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/PURPOSE")).$eol;
	$contact_str.="TEL;Home;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/PHONE2")).$eol;
	$contact_str.="TEL;Cell;ENCODING=QUOTED-PRINTABLE:".encode_for_vcard($xml->getData($path."/INFO/MOBILEPHONE")).$eol;
	$contact_str.="NOTE;ENCODING=QUOTED-PRINTABLE:".$contact_pers.encode_for_vcard($xml->getData($path."/INFO/NOTES")).$eol;
	$contact_str.="URL:".encode_for_vcard($xml->getData($path."/INFO/WEBSITE")).$eol;
	$contact_str.="BDAY:".encode_for_vcard($xml->getData($path."/INFO/BIRTHDAY")).$eol;
	$contact_str.="REV:".str_replace(" ","T",encode_for_vcard($xml->getData($path."/INFO/MODIFICATIONDATE"))).$eol;
	$contact_str.="UID:".encode_for_vcard($xml->getData($path."/INFO/ID")).$eol;
	$contact_str.="CATEGORY:".$categories.$eol;
	$gender = $xml->getData($path."/INFO/GENDER");
	if ($gender=="F")
		$contact_str.="X-WAB-GENDER:1".$eol;
	else
		$contact_str.="X-WAB-GENDER:2".$eol;
	$contact_str.="PRODID:NECTIL".$eol;
	$contact_str.="END:VCARD".$eol;
	return $contact_str;
}

function resolveGroup($groupIDs=array()){
	if (!is_array($groupIDs))
	return array();
	
	$IDs_array = $groupIDs;
	$handled_groupIDs = array();
	
	$moduleGroupInfo = moduleInfo('group');
	
	$size = sizeof($IDs_array);
	
	for($i=0;$i<$size;$i++){
		$ID = $IDs_array[$i];
		if ( !isset($handled_groupIDs[$ID]) ){ // this group was not yet handled
			// first retrieving the Alias and putting theme in the queue
			$groupAliasDep = depType('groupAlias');
			$alias_rs = getDependenciesFrom($moduleGroupInfo->ID,$ID,$groupAliasDep->ID);
			if (is_object($alias_rs)){
				while($row = $alias_rs->FetchRow()){
					if (!isset($handled_groupIDs[$row["ID"]])){// if it was not handled yet, adding it into the queue
						$IDs_array[]=$row["TargetID"];
						$size++;
					}
				}
			}
			// now getting all the members of the group
			$groupMemberDep = depType('groupMember');
			
			$member_rs = getDependenciesFrom($moduleGroupInfo->ID,$ID,$groupMemberDep->ID);
			if ($member_rs){
				while($row = $member_rs->FetchRow()){
					$contact_infos[$row["TargetID"]]= $row["TargetID"];
				}
			}
		}
		// the group is handled
		$handled_groupIDs[$ID]=TRUE;
		//$query_result.=$ID.",";
	}
	return $contact_infos;
}
if ($_GET['contact']!='all'){
	if (is_array($groupID) && sizeof($groupID)>0)
		$inGroup = resolveGroup($groupID);
	else
		$inGroup = array();
	$inContact = array();
	if (is_array($contactID) && sizeof($contactID)>0){
		//echo "found_something for contact ".$contactID[0]." ".$contactID[1];
		foreach($contactID as $ID){
			$inContact[$ID]=$ID;
		}
	}
}
if (sizeof($inContact)>0 && sizeof($inGroup)>0){
	$ID=array_merge($inGroup,$inContact);
	//echo "debugging : both specified.";
}else if(sizeof($inContact)>0){
	$ID=array_merge(array(),$inContact);
	//echo "debugging : only a contact specified.";
}else if (sizeof($inGroup)>0){
	$ID=array_merge(array(),$inGroup);
	//echo "debugging : only a group specified.";
}else
	$ID=FALSE;
	
$output = 'vcf';
$filename= date("Y-m-d_H:i:s").'.'.$output;

$db_conn = db_connect();
$moduleInfo = moduleInfo($module);
$moduleName = $moduleInfo->name;
if (is_numeric($ID) || is_array($ID) )
	$rs = getInfos($moduleInfo,$ID);
else if($_GET['contact']==='all'){
	$sql = "SELECT * FROM ".$moduleInfo->tableName." WHERE ID!=1 AND Activity!=0;";
	$rs = $db_conn->Execute($sql);
}
else
	die();
//$fields_array=$moduleInfo->getFieldsBySecurity('R');
setDownloadHeaders($filename);


if ($rs){
	while($row = $rs->FetchRow()){
		
		$contact_str = generateVCard($row);
		echo $contact_str;
	}
}
?>
