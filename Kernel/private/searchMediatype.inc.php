<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchMediatype.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function searchMediatype($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	
	$current_path = $firstNodePath;
	
	$languageID = $xml->getData($current_path."/@languageID");
	if(!$languageID)
		$languageID = $xml->getData($firstNodePath."/@languageID");
	if(!$languageID && $GLOBALS["php_request"])
		$languageID = $GLOBALS["NectilLanguage"];
	$profile = $xml->getData($current_path."/@profile");
	
	$defaultLGsql = 'SELECT * from `medialanguages` ORDER BY priority ASC;';
	$default = $db_conn->GetRow($defaultLGsql);
	if($default){
		$defaultLanguageID=$default['languageID'];
	}
	//$sql = "SELECT *,typ.ID AS mediaTypeID FROM mediatypes AS typ LEFT JOIN mediatypesconfig AS trad ON trad.LanguageID=\"".$languageID."\" AND typ.ID=trad.MediaTypeID LEFT JOIN descriptionsconfig AS descrip ON trad.DescriptionConfigID=descrip.ID ORDER BY typ.priority ASC;";
	$mediaModuleInfo = moduleInfo('media');
	$first = true;
	if($mediaModuleInfo->composite && sizeof($mediaModuleInfo->virtualIDs)>0){
		$first = false;
		$sql = 'SELECT * FROM `mediatypes` WHERE ';
		$element_names = array();
		foreach($mediaModuleInfo->virtualIDs as $key){
			$element_names[]='`MediaKind`="'.$key.'"';
		}
		$sql.="(".implode(" OR ",$element_names).")";
	}else{
		$sql = 'SELECT * FROM `mediatypes`';
	}
	$fields_array = array('UNIQUENAME'=>'MediaKind','ID'=>'ID','ISCOMPOSITE'=>'IsComposite','SELECT'=>'Select','ISPUBLI'=>'IsPubli','ISEVENT'=>'IsEvent','ISPAGETOCALL'=>'IsPageToCall','STRUCTURALTYPE'=>'StructuralType');
	
	foreach($fields_array as $nodeName=>$fieldname){
		$fields_criterias = $xml->match($firstNodePath.'/'.$nodeName.'[text()]');
		$possible_values = array();
		foreach($fields_criterias as $fields_criterias_path){
			$possible_values[]=$xml->getData($fields_criterias_path);
		}
		if($nodeName=='ID'){
			$ID_attribute = $xml->getData($firstNodePath.'/@ID');
			if($ID_attribute!==false)
				$possible_values[]=$ID_attribute;
		}
		if(sizeof($possible_values)>0){
			if($first)
				$sql.=' WHERE ';
			else
				$sql.=' AND ';
			$sql.='`'.$fieldname.'` IN (\''.implode('\',\'',$possible_values).'\') ';
			$first = false;
		}
	}
	//debug_log($sql);
	$rs = $db_conn->Execute($sql.' ORDER BY `Priority` ASC');
	if ($name)
		$attributes.=" name='$name'";
	$external_file = $xml->getData($current_path.'/@fromFile');
	if($external_file)
		$attributes.=" fromFile='".$external_file."'";
	$query_result='<RESULTS'.$attributes.'>';
	// array to keep trace of which dependency we already have made : especially useful for two-way asymmetric dependency where we don't want to have the link twice
	while($row = $rs->FetchRow()){
		$query_result.="<MEDIATYPE ID='".$row["ID"]."' type='".encode_to_XML($row["MediaKind"])."'>";
		$query_result.="<ID>".encode_to_XML($row["ID"])."</ID>";
		$query_result.="<UNIQUENAME>".encode_to_XML($row["MediaKind"])."</UNIQUENAME>";
		if ($profile!="mini"){
			$query_result.="<ICON>".encode_to_XML($row["Icon"])."</ICON>";
			$query_result.="<ISCOMPOSITE>".encode_to_XML($row["IsComposite"])."</ISCOMPOSITE>";
			$query_result.="<SELECT>".encode_to_XML($row["Select"])."</SELECT>";
			$query_result.="<ISPUBLI>".encode_to_XML($row["IsPubli"])."</ISPUBLI>";
			$query_result.="<ISEVENT>".encode_to_XML($row["IsEvent"])."</ISEVENT>";
			$query_result.="<ISTEMPLATE>".encode_to_XML($row["IsTemplate"])."</ISTEMPLATE>";
			$query_result.="<ISPAGETOCALL>".encode_to_XML($row["IsPageToCall"])."</ISPAGETOCALL>";
			$query_result.="<CSSFILE>".encode_to_XML($row["CssFile"])."</CSSFILE>";
			$query_result.="<STRUCTURALTYPE>".encode_to_XML($row["StructuralType"])."</STRUCTURALTYPE>";
			$query_result.="<CONFIG>";
			$sql1 = 'SELECT *,trad.LanguageID AS LgID,trad.Denomination AS denom FROM mediatypesconfig AS trad LEFT JOIN descriptionsconfig  AS descript ON trad.DescriptionConfigID=descript.ID WHERE trad.MediaTypeID='.$row['ID'];
			if ($languageID)
				$sql=$sql1.' AND trad.LanguageID="'.$languageID.'";';
			else
				$sql=$sql1;
			$rs2 = $db_conn->Execute($sql);
			//debug_log('sql1 '.$sql);
			if($languageID && is_object($rs2) && $rs2->RecordCount()==0 && $defaultLanguageID){
				$sql=$sql1.' AND trad.LanguageID="'.$defaultLanguageID.'";';
				$rs2 = $db_conn->Execute($sql);
			}
			//debug_log('sql2 '.$sql);
			if ($rs2){
				//debug_log('rs is ok');
				while($row2 = $rs2->FetchRow()){
					//debug_log('row '.$row["MediaKind"]);
					//debug_log('row '.$row2["denom"]);
					$query_result.='<'.$row2['LgID'].' languageID="'.$row2['LgID'].'">';
					if ($row2["denom"])
						$query_result.="<DENOMINATION>".encode_to_XML($row2["denom"])."</DENOMINATION>";
					else
						$query_result.="<DENOMINATION>".encode_to_XML($row["MediaKind"])."</DENOMINATION>";
						
					$query_result.="<DESCRIPTIONCONFIG".(($row2['Alingual']==1)?' alingual="1"':'').">".$row2["Config"]."</DESCRIPTIONCONFIG>";
					$query_result.='</'.$row2['LgID'].'>';
				}
			}
			$query_result.="</CONFIG>";
		}else{
			$sql1 = 'SELECT *,trad.LanguageID AS LgID,trad.Denomination AS denom FROM mediatypesconfig AS trad LEFT JOIN descriptionsconfig  AS descript ON trad.DescriptionConfigID=descript.ID WHERE trad.MediaTypeID='.$row['ID'];
			if ($languageID)
				$sql=$sql1.' AND trad.LanguageID="'.$languageID.'";';
			else
				$sql=$sql1;
			$rs2 = $db_conn->Execute($sql);
			//debug_log('sql1 '.$sql);
			if($languageID && is_object($rs2) && $rs2->RecordCount()==0 && $defaultLanguageID){
				$sql=$sql1.' AND trad.LanguageID="'.$defaultLanguageID.'";';
				$rs2 = $db_conn->Execute($sql);
			}
			//debug_log('sql2 '.$sql);
			if ($rs2){
				while($row2 = $rs2->FetchRow()){
					$query_result.='<LABEL languageID="'.$row2['LgID'].'">';
					if ($row2["denom"])
						$query_result.=encode_to_XML($row2["denom"]);
					else
						$query_result.=encode_to_XML($row["MediaKind"]);
					$query_result.='</LABEL>';
				}
			}
		}	
		if ($profile!="mini")
			$query_result.="<DEPENDENCIES>".$row["DepConfig"]."</DEPENDENCIES>";
		$query_result.="</MEDIATYPE>";
	}
	$query_result.="</RESULTS>";
	
	return $query_result;
}
//return generateMsgXML(0,"Dependency_entity creation not yet finished");
?>
