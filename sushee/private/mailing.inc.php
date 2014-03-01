<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/mailing.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function resolveRecipientType($mailingType,$recipientType,$contact_infos){
	if (!is_array($contact_infos))
	return array();
	$moduleContactInfo = moduleInfo('contact');
	$trans_contact_infos = array();
	$familyDep = depType('contactFamily');
	$workDep = depType('contactWork');
	if ($recipientType == "people"){
		while (list($ID, $contact) = each($contact_infos)) {
			if ($mailingType=="pro"){
				// if it's a moral person (industry, entreprise), we take its employees (dep contactWork)
				if ($contact["ContactType"]=="PM"){
					$workers_rs = getDepTargetsInfo($moduleContactInfo->ID,$ID,$workDep->ID);
					while($row = $workers_rs->FetchRow()){
						if ( !isset($trans_contact_infos[$row["ID"]]) && $contact["ContactType"]=="PP" )
							$trans_contact_infos[$row["ID"]]= $row;
					}
				}else
					$trans_contact_infos[$ID]=$contact_infos[$ID];
			}else{ // private mailingType
				// if it's a moral person, we take the family
				if ($contact["ContactType"]=="PM"){
					$workers_rs = getDepTargetsInfo($moduleContactInfo->ID,$ID,$familyDep->ID);
					while($row = $workers_rs->FetchRow()){
						if ( !isset($trans_contact_infos[$row["ID"]]) && $contact["ContactType"]=="PP" )
							$trans_contact_infos[$row["ID"]]= $row;
					}
				}else
					$trans_contact_infos[$ID]=$contact_infos[$ID];
			}
		}
	}else if($recipientType == "company"){
		while (list($ID, $contact) = each($contact_infos)) {
			if ($mailingType=="pro"){
				// if it's a moral person (industry, entreprise), we take its employees (dep contactWork)
				if ($contact["ContactType"]=="PP"){
					$workers_rs = getDepTargetsInfo($moduleContactInfo->ID,$ID,$workDep->ID);
					while($row = $workers_rs->FetchRow()){
						if ( !isset($trans_contact_infos[$row["ID"]]) && $contact["ContactType"]=="PM" )
							$trans_contact_infos[$row["ID"]]= $row;
					}
				}else
					$trans_contact_infos[$ID]=$contact_infos[$ID];
			}else{ // private mailingType
				// if it's a moral person, we take the family
				if ($contact["ContactType"]=="PP"){
					$workers_rs = getDepTargetsInfo($moduleContactInfo->ID,$ID,$familyDep->ID);
					while($row = $workers_rs->FetchRow()){
						if ( !isset($trans_contact_infos[$row["ID"]]) && $contact["ContactType"]=="PM" )
							$trans_contact_infos[$row["ID"]]= $row;
					}
				}else
					$trans_contact_infos[$ID]=$contact_infos[$ID];
			}
		}
	}else{
		$trans_contact_infos=$contact_infos;
	}
	return $trans_contact_infos;
}

function searchMailingGroup($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php"); 
	
	$moduleGroupInfo = moduleInfo('group');
	$moduleContactInfo = moduleInfo('contact');
	$moduleMediaInfo = moduleInfo('media');
	
	if ($moduleGroupInfo->getActionSecurity("SEARCH")!="R" || $moduleContactInfo->getActionSecurity("SEARCH")!="R")
		return generateMsgXML(3,"You don't have the authorizations on the 2 modules Contact and Group, necessary to handle your request.");
	
	// getting the ID(s) of the group that we must handle
	$IDs = $IDs_string = $xml->getData($firstNodePath.'/@groupID');
	if ($IDs_string==FALSE){
		$query_result = generateMsgXML(0,'No ID were set -> no get has been processed.');
		return $query_result;
	}
	$IDs_array = explode(",",$IDs_string);
	// getting the ID(s) of the media we must handle
	$mediaID = $xml->getData($firstNodePath.'/@mediaID');
	
	// getting the parameters of the mailing
	$mailboxType = $xml->getData($firstNodePath.'/@mailboxType');
	if ($mailboxType==FALSE)
		$mailboxType = "email";
	$mailingType = $xml->getData($firstNodePath.'/@mailingType');
	if ($mailingType==FALSE)
		$mailingType = "pro";
	$recipientType = $xml->getData($firstNodePath.'/@recipientType');
	if ($recipientType==FALSE)
		$recipientType = "straight";
		
	$handled_groupIDs = array();
	
	$contact_infos = array();
	$trans_contact_infos = array();
	$final_contact_infos = array();
	
	$size = sizeof($IDs_array);
	
	if ($name)
		$attributes.=" name='$name'";
	$external_file = $xml->getData($current_path.'/@fromFile');
	if($external_file)
		$attributes.=" fromFile='".$external_file."'";
	$query_result='<RESULTS'.$attributes.'>';
	if ($mailboxType == "streetmail"){
		// for the group chosen and their alias, retrieving their members
		
		$contact_infos = resolveMailingRecipients($IDs_array);
		// now if we want people/company only, we must take the dependencies
		$trans_contact_infos = resolveRecipientType($mailingType,$recipientType,$contact_infos);
		// now retrieving the used adress of the contacts ( if it's a professional mailing we must use the adress of the work where the contact works
		$rejected = array();
		$final_contact_infos = resolveStreetMailingType($mailingType,$recipientType,$trans_contact_infos,$rejected);
	}
	// retrieving the media and its descriptions in the language of the user
	
	$fields_array=$moduleContactInfo->getFieldsBySecurity("R");
	foreach($final_contact_infos as $contact){
		$query_result.='<CONTACT ID="'.$contact["ID"].'">';
		$query_result.=generateInfoXML($moduleContactInfo,$contact,$fields_array,FALSE);
		$query_result.='</CONTACT>';
	}
	$query_result.="</RESULTS>";
	return $query_result;
}
?>
