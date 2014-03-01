<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/import_vcard.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function generateVCardCreateXML($infos,$def_countryID='und'){
	$fields = array();
	$group_IDs = array();
	foreach($infos as $info){
		$labels = $info['labels'];
		$content = $info['content'];
		/*if ($labels[0]=='adr')
			debug_log($content);*/
		$content = explode(';',$content);
		switch($labels[0]){
			  case 'fn':
			  	$fields['Denomination']=$content[0];
			  	break;
			  case 'n':
				$fields['FirstName']=$content[1];
				$fields['LastName']=$content[0];
				if($fields['FirstName']!='' || $fields['LastName']!='')
				$fields['ContactType']='PP';
			  	break;
			  case 'tel':
			  	if(in_array('fax',$labels['type']))
					$fields['Fax']=$content[0];
			  	else if (in_array('home',$labels['type']))
					$fields['Phone2']=$content[0];
				else if(in_array('work',$labels['type']))
					$fields['Phone1']=$content[0];
				else if(in_array('cell',$labels['type']))
					$fields['MobilePhone']=$content[0];
				else if(!isset($fields['Phone1']))
					$fields['Phone1']=$content[0];
				else if(!isset($fields['Phone2']))
					$fields['Phone2']=$content[0];
			  	break;
			  case 'email':
			    if (!isset($fields['Email1']))
			    	$fields['Email1']=$content[0];
				else if(in_array('pref',$labels['type'])){
					$fields['Email2']=$fields['Email1'];
					$fields['Email1']=$content[0];
				}
				else if (!isset($fields['Email2']))
					$fields['Email2']=$content[0];
			  	break;
			  case 'org':
			  	if($fields['ContactType']==='PP')
					$fields['Notes'].="organisation:".$content[0]."\n";
				else
			    	$fields['Denomination']=$content[0];
				
				if(!isset($fields['ContactType']))
					$fields['ContactType']='PM';
			  	break;
			  case 'adr':
				  if (!isset($fields['Address'])){
					$fields['Address']=$content[2];
					$fields['City']=$content[3];
					$fields['PostalCode']=$content[5];
					// country
					$content[6] = trim($content[6]);
					if ($content[6]!=''){
						$lower = strtolower(utf8_To_UnicodeEntities($content[6]));
						$sql = 'SELECT ID FROM `countries` WHERE `ID`="'.$lower.'" OR `ISOAlpha2`="'.$lower.'" OR LOWER(`eng`)="'.$lower.'" OR LOWER(`fre`)="'.$lower.'" OR LOWER(`universal`)="'.$lower.'";';
						$db_conn = db_connect(TRUE);
						//debug_log($sql);
						$row = $db_conn->GetRow($sql);
						if ($row)
							$fields['CountryID']=$row['ID'];
						$db_conn = db_connect();
					}
				  }
				  break;
			  case 'note':
			  	$fields['Notes'].=$content[0];
			  	break;
			  case 'url':
			    $fields['WebSite']=$content[0];
			  	break;
			  case 'title':
			  	$fields['Purpose']=$content[0];
			  	break;
			  case 'bday':
			    $fields['BirthDay']=$content[0];
			  	break;
			  case 'photo':
			  	break;
			  case 'category':
			  case 'categories':
			  case 'x-palm-category1':
			  	// creating the groups if not existing, else getting back their IDs
				$moduleInfo = moduleInfo('group');
				$categs = explode(',',$content[0]);
				$db_conn = db_connect();
				foreach($categs as $categ){
					$sql = 'SELECT ID FROM `'.$moduleInfo->tableName.'` WHERE LOWER(`Denomination`)="'.strtolower($categ).'";';
					$row = $db_conn->GetRow($sql);
					if ($row){
						$group_IDs[$row['ID']]=TRUE;
					}else{
						$group_IDs[$categ]=$categ;
					}
				}
			  	break;
			  default:;
		  }
	}
	// post-treatment
	if ( (!isset($fieds['LastName']) || $fieds['LastName']=='') && !isset($fields['ContactType']))
		$fields['ContactType']='PM';
	if (!isset($fields['ContactType']) ){
		$fields['ContactType']='PP';
	}
	if (!isset($fields['CountryID'])){
		$fields['CountryID']=$def_countryID;
	}else{
		$country = getCountryInfo($fields['CountryID']);
		$lgs_country = explode(',',$country['LanguageID']);
		$fields['LanguageID']=$lgs_country[0];
	}
	if (!isset($fields['LanguageID'])){
		$fields['LanguageID']='und';
	}
	if ($fields['ContactType']=='PM' && $fields['FirstName']==$fields['Denomination'])
		 $fields['FirstName']='';
	$output = '';
	$existing = false;
	$moduleInfo = moduleInfo('contact');
	if(!$moduleInfo->getActionSecurity("CREATE",$fields)){
		return '';
	}
	
	if(isset($fields['Email1'])){
		
		$db_conn = db_connect();
		$test_email_sql = "SELECT * FROM `contacts` WHERE Email1=\"".$fields["Email1"]."\" AND Activity=1";
		//debug_log($test_email_sql);
		if ($existing_row = $db_conn->getRow($test_email_sql) ){
			$existing = true;
			// removing fields that are not empty 
			foreach($existing_row as $key=>$value){
				if($value!=''){
					unset($fields[$key]);
				}
			}
		}
	}
	if($existing)
		$output.='<UPDATE><CONTACT ID="'.$existing_row['ID'].'"><INFO>';
	else
		$output.='<CREATE><CONTACT><INFO>';
	foreach($fields as $key=>$value){
		$n = strtoupper($key);
		$value = str_replace("\\n","\n",$value);
		$value = str_replace("\\,",",",$value);
		if(!is_utf8($value)){
			$value = utf8_encode($value);
		}
		$output.='<'.$n.'>'.encode_to_xml($value).'</'.$n.'>';
	}
	$output.='</INFO>';
	$output.='<DEPENDENCIES><DEPENDENCY type="groupMember" mode="reverse">';
	foreach($group_IDs as $ID=>$value){
		if (is_numeric($ID))
			$output.='<GROUP ID="'.$ID.'"/>';
		else
			$output.='<GROUP><INFO><DENOMINATION>'.$value.'</DENOMINATION></INFO></GROUP>';
	}
	$output.='</DEPENDENCY></DEPENDENCIES>';
	if($existing)
		$output.='</CONTACT></UPDATE>';
	else
		$output.='</CONTACT></CREATE>';
	return $output;
}

function importVCard($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	
	require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	require_once(dirname(__FILE__)."/../file/file_config.inc.php");
	
	$moduleInfo = moduleInfo('contact');
	if ($moduleInfo->loaded==FALSE){
		$query_result = generateMsgXML(1,"The informations about the module couldn't be found.",0,'',$name);
		return $query_result;
	}
	/*if(!$moduleInfo->getActionSecurity("CREATE")){
		$query_result = generateMsgXML(3,"You're not authorized to create elements in this module.",0,'',$name);
		return $query_result;
	}*/
	// checking the file permissions
	$target = $xml->getData($firstNodePath."/@source");
	$def_countryID = $xml->getData($firstNodePath."/@defaultCountryID");
	if(!$def_countryID)
		$def_countryID='und';
	$debug_mode = $xml->getData($firstNodePath."/@debug");
	$target = transformPath(unhtmlentities($target));
	$right =  getPathSecurityRight($target);
	if ($right===0)
		return generateMsgXML(3,"You're not authorized to handle this file.",0,'',$name);
	global $directoryRoot;
	$import_path = $directoryRoot.$target;
	
	
	
	if (!file_exists($import_path))
		return generateMsgXML(1,"The file doesn't exist.".$import_path,0,'',$name);
		
	$input = file_in_string($import_path);
	if (trim($input)=='')
		return generateMsgXML(1,"The file is empty.",0,'',$name);
	//$input = quoted_printable_decode($input);
	// replacing all end-of-line possible encoding by a standard one
	$input = str_replace(array("\r\n","\r"),"\n",$input);
	
	$line = strtok($input,"\n");
	$preceding_unfinished = false;
	$infos = array();
	$contacts = array();
	//-------------------
	//     PARSING : decoding and separating the info (structural)
	//-------------------
	while ($line) {
	  //echo "$line<br />";
	  //debug_log($line);
	  if (strtolower($line)=='begin:vcard'){
		  if ($preceding_unfinished){
			$contacts[]=$infos;
			$preceding_unfinished = true;
			$infos = array();
		  }
	  }else if (strtolower($line)=='end:vcard'){
		  $contacts[]=$infos;
		  $preceding_unfinished = false;
		  $infos = array();
	  }else{
		  
		  $parts = explode(':',$line,2);
		  if (sizeof($parts)>1){ // skipping if structure is not correct
			  $labels_str = $parts[0];
			  $labels_str = strtolower($labels_str);
			  $labels = explode(';',$labels_str);
			  if (strpos($labels[0],'.')!==FALSE)
				$labels[0] = substr($labels[0],strpos($labels[0],'.')+1);
			  
			  $new_labels = array($labels[0],'type'=>array());
			  for($i=1;$i<sizeof($labels);$i++){
				  $defs = explode('=',$labels[$i],2);
				  if ($defs[0]=='quoted-printable')
						$parts[1] = quoted_printable_decode($parts[1]);
				  if (sizeof($defs)>0){
					  $new_labels[$defs[0]][] = $defs[1];
				  }else{  
					   $new_labels['type'][] = $defs[0];
				  }
			  }
			  $content_str = $parts[1];
			  //$content = explode(';',$content_str);
			  $infos[]=array('labels'=>$new_labels,'content'=>$content_str);
		  }else{
			  //concat to the preceding item
			  if (sizeof($infos)>0){
				  $prec = $infos[sizeof($infos)-1]['content'];
				  $infos[sizeof($infos)-1]['content']=$prec.$line;
			  }
		  }
	  }
	  $line = strtok("\n");
	}
	$creations_nb = 0;
	$failures = 0;
	$index = 1;
	$contact_IDs = array();
	foreach($contacts as $contact){
		$output = '<?xml version="1.0"?><QUERY>';
		$output.=generateVCardCreateXML($contact,$def_countryID);
		debug_log($output);
		$output.='</QUERY>';
		if($debug_mode!=='true'){
			$message = query($output,FALSE,FALSE,FALSE);
			$xml = new XML($message);
			if ($xml->loaded){
				$ID = $xml->getData('/RESPONSE/MESSAGE[1]/@elementID');
				$msgType = $xml->getData('/RESPONSE/MESSAGE[1]/@msgType');
				if (is_numeric($ID) && $msgType=='0'){
					$creations_nb++;
					$contact_IDs[]=$ID;
				}else{
					$msg = $xml->getData('/RESPONSE/MESSAGE[1]');
					if ($remarks!='')
						$remarks.="\n";
					$remarks.=$index.". ".$msg;
					debug_log('Failed: '.$output.' because '.$msg);
					$failures++;
				}
			}
		}else
			$xml_output.=$output;
		$index++;
	}
	//$output.='</QUERY>';
	//echo "--------------------------------";
	if ($creations_nb==0)
		return generateMsgXML(1,"No new contacts created.\n".$remarks.encode_to_xml($xml_output),0,implode(',',$contact_IDs),$name,'','','imports="0" failures="'.$failures.'"');
	if ($creations_nb!=($index-1) )
		$intro=($index-1-$creations_nb)." were skipped : see reasons below \n";
	$query_result = generateMsgXML(0,$creations_nb." new contacts created/updated.\n".$intro.$remarks,0,implode(',',$contact_IDs),$name,'','','imports="'.$creations_nb.'" failures="'.$failures.'"');
	debug_log('Import vcard '.$query_result);
	return $query_result;
}
?>
