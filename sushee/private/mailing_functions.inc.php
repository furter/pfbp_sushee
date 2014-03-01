<?php

require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
require_once(dirname(__FILE__)."/../common/mail.class.php");

function resolveMailingRecipients($groupIDs=array())
{
	if (!is_array($groupIDs))
	return array();
	
	$IDs_array = $groupIDs;
	$handled_groupIDs = array();
	
	$size = sizeof($IDs_array);
	
	$moduleGroupInfo = moduleInfo('group');
	
	for($i=0;$i<$size;$i++){
		$ID = $IDs_array[$i];
		if ( !isset($handled_groupIDs[$ID]) ){ // this group was not yet handled
			// first retrieving the Alias and putting theme in the queue
			$groupAliasDep = depType('groupAlias');
			$alias_rs = getDependenciesFrom($moduleGroupInfo->ID,$ID,$groupAliasDep->ID);
			if ($alias_rs){
				while($row = $alias_rs->FetchRow()){
					if (!isset($handled_groupIDs[$row["ID"]])){// if it was not handled yet, adding it into the queue
						$IDs_array[]=$row["TargetID"];
						$size++;
					}
				}
			}
			// now getting all the members of the group
			$groupMemberDep = depType('groupMember');
			$member_rs = getDepTargetsInfo($moduleGroupInfo->ID,$ID,$groupMemberDep->ID);
			if ($member_rs){
				while($row = $member_rs->FetchRow()){
					$contact_infos[$row["ID"]]= $row;
				}
			}
		}
		// the group is handled
		$handled_groupIDs[$ID]=TRUE;
		//$query_result.=$ID.",";
	}
	return $contact_infos;
}
function resolveStreetMailingType($mailingType,$recipientType,$trans_contact_infos,&$rejected_contact_infos)
{
	if (!is_array($trans_contact_infos))
	return array();
	
	$familyDep = depType('contactFamily');
	$workDep = depType('contactWork');
	$moduleContactInfo = moduleInfo('contact');
	$final_contact_infos = array();
	if($rejected_contact_infos)
		$rejected_contact_infos = array();
	if ($mailingType=="pro"){
		foreach($trans_contact_infos as $ID => $contact) {
			if ($contact["ContactType"]=="PM" && trim($contact["Address"])!=""){
				$final_contact_infos[]=$contact;
			}else{
				$work_rs = getDepTargetsInfo($moduleContactInfo->ID,$ID,$workDep->ID);
				$got_one = false;
				if($work_rs){
					while($row = $work_rs->FetchRow()){
						// replacing by the adress of the working place if available
						if (trim($row["Address"])!="" && $row["ContactType"]=="PM"){
							$contact_copy = $contact;
							$contact_copy["Address"]=$row["Address"];
							$contact_copy["PostalCode"]=$row["PostalCode"];
							$contact_copy["City"]=$row["City"];
							$contact_copy["StateOrProvince"]=$row["StateOrProvince"];
							$contact_copy["CountryID"]=$row["CountryID"];
							// also add the name of the working place in the contact
							$contact_copy["Denomination"]=$row["Denomination"];
							$final_contact_infos[]= $contact_copy;
							$got_one = true;
						}
					}
				}
				if (trim($contact["Address"])!="" && !$got_one)
					$final_contact_infos[]=$contact;
				else if(!$got_one)
					$rejected_contact_infos[]=$contact;
			}
		}
	}else{
		foreach($trans_contact_infos as $ID => $contact) {
			if ($contact["ContactType"]=="PM" ){
				// it's a private mailing and we have a PM -> we must take the family deps
				$family_rs = getDepTargetsInfo($moduleContactInfo->ID,$ID,$familyDep->ID);
				$got_one = false;
				while($row = $family_rs->FetchRow()){
					if (trim($row["Address"])!=""){
						$contact_copy = $contact;
						$contact_copy["Address"]=$row["Address"];
						$contact_copy["PostalCode"]=$row["PostalCode"];
						$contact_copy["City"]=$row["City"];
						$contact_copy["StateOrProvince"]=$row["StateOrProvince"];
						$contact_copy["CountryID"]=$row["CountryID"];
						$final_contact_infos[]= $contact_copy;
						$got_one = true;
					}
				}
				if(!$got_one)
					$rejected_contact_infos[]=$contact;
			}else{
				if (trim($contact["Address"])!="")
					$final_contact_infos[]=$contact;
				else{
					$family_rs = getDepTargetsInfo($moduleContactInfo->ID,$ID,$familyDep->ID);
					$got_one = false;
					if($family_rs){
						while($row = $family_rs->FetchRow()){
							if (trim($row["Address"])!=""){
								$contact_copy = $contact;
								$contact_copy["Address"]=$row["Address"];
								$contact_copy["PostalCode"]=$row["PostalCode"];
								$contact_copy["City"]=$row["City"];
								$contact_copy["StateOrProvince"]=$row["StateOrProvince"];
								$contact_copy["CountryID"]=$row["CountryID"];
								$final_contact_infos[]= $contact_copy;
								$got_one = true;
							}
						}
					}
					if(!$got_one)
						$rejected_contact_infos[]=$contact;
				}
			}
		}
	}
	return $final_contact_infos;
}

function getMailingRecipients($mailingID,$type='not_sent',$number='all')
{
	$sql = 'SELECT * FROM `mailing_recipients` WHERE `Status`="'.$type.'" AND `MailingID`='.$mailingID;
	if($number!=='all')
		$sql.=' LIMIT 0,'.$number;
	$db_conn = db_connect();
	$rs = $db_conn->Execute($sql);
	return $rs;
}

function getMailingRecipient($mailingID,$viewingCode)
{
	$db_conn = db_connect();
	$sql = 'SELECT * FROM `mailing_recipients` WHERE `ViewingCode`="'.$viewingCode.'" AND `MailingID`='.$mailingID;
	$row = $db_conn->GetRow($sql);
	return $row;
}

function getMailingRecipientsCount($mailingID,$status=false)
{
	$db_conn = db_connect();
	$base_sql = 'SELECT COUNT(`ContactID`) AS total FROM `mailing_recipients` WHERE MailingID='.$mailingID.' ';
	if($status==='to_send')
		$base_sql.='AND (`Status`="sent" OR `Status`="not_sent")';
	else if($status!==false)
		$base_sql.='AND `Status`="'.$status.'"';
	
	$row = $db_conn->GetRow($base_sql);
	return $row['total'];
}

function getMailingRecipientsXML($rs)
{
	$query_result="";
	$query_result.='<RESULTS>';
	if(is_object($rs)){
		while($row = $rs->FetchRow()){
			$query_result.=generateMailingRecipientXML($row);
		}
	}
	return $query_result.'</RESULTS>';
}

function mailing_log($strRet)
{
	echo $strRet.'<br />'.PHP_EOL;
	$perm = 'a+';
	if(file_exists($GLOBALS["nectil_dir"]."/Files/mailing.log") && filesize($GLOBALS["nectil_dir"]."/Files/mailing.log")>5242880)
		$perm = 'w+';
	if ( (file_exists($GLOBALS["nectil_dir"]."/Files/mailing.log") && is_writable($GLOBALS["nectil_dir"]."/Files/mailing.log")) || (!file_exists($GLOBALS["nectil_dir"]."/Files/mailing.log") && is_writable($GLOBALS["nectil_dir"]."/Files/")) ){
		$file = fopen($GLOBALS["nectil_dir"]."/Files/mailing.log", $perm); // binary update mode
		fwrite($file, "\r\n---------------------------------------------------\r\n".date("Y-m-d H:i:s")." ".$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']."- ".$strRet);
		fclose($file);
		//chmod ($GLOBALS["nectil_dir"]."/Files/debug.log", 0777);
	}
}

function setMailingRecipientStatus($mailingID,$viewingCode,$status,$sendingDate=false,$rejectingDate=false,$step=false,$info_xml=false,$returned_mail=false,$resident_dbname='')
{
	mailing_log('start setMailingRecipientStatus ');
	if($sendingDate!==false)
		$dates=',`SendingDate`="'.$sendingDate.'" ';
	else if($rejectingDate!==false)
		$dates=',`RejectingDate`="'.$rejectingDate.'" ';
	if($step!==false)
		$step_update=',`Step`='.$step;
	
	$sql = 'UPDATE '.$resident_dbname.'`mailing_recipients` SET `Status`="'.$status.'"'.(($status==='sent')?',`UniqueSendingDate`="'.date('Y-m-d H:i:s').'"':'').' '.$dates.' '.$step_update.' '.$info_update.' '.$returned_update.' WHERE `ViewingCode`="'.$viewingCode.'" AND `MailingID`='.$mailingID.';';
	$db_conn = db_connect();
	$db_conn->Execute($sql);
	mailing_log('setMailingRecipientStatus '.$sql);
}

function setMailingRecipientHTMLInMailbox($mailingID,$viewingCode)
{
	$db_conn = db_connect();
	$sql = 'SELECT `ViewingDate` FROM `mailing_recipients` WHERE `Status`="sent" AND `ViewingCode`="'.$viewingCode.'" AND `MailingID`='.$mailingID.';';
	$row = $db_conn->GetRow($sql);
	if($row && $row['ViewingDate']!='0000-00-00 00:00:00')
		$viewingDate = $row['ViewingDate'];
	else
		$viewingDate = $GLOBALS['sushee_today'];
	$sql = 'UPDATE `mailing_recipients` SET `HTMLInMailbox`=1,`ViewingDate`="'.$viewingDate.'" WHERE `Status`="sent" AND `ViewingCode`="'.$viewingCode.'" AND `MailingID`='.$mailingID.';';
	$db_conn->Execute($sql);
}

function setMailingRecipientSeenOnWeb($mailingID,$viewingCode)
{
	$db_conn = db_connect();
	$sql = 'SELECT `ViewingDate` FROM `mailing_recipients` WHERE `Status`="sent" AND `ViewingCode`="'.$viewingCode.'" AND `MailingID`='.$mailingID.';';
	$row = $db_conn->GetRow($sql);
	if($row && $row['ViewingDate']!='0000-00-00 00:00:00')
		$viewingDate = $row['ViewingDate'];
	else
		$viewingDate = $GLOBALS['sushee_today'];
	$sql = 'UPDATE `mailing_recipients` SET `SeenOnWeb`=1,`ViewingDate`="'.$viewingDate.'" WHERE `Status`="sent" AND `ViewingCode`="'.$viewingCode.'" AND `MailingID`='.$mailingID.';';
	$db_conn->Execute($sql);
}

function generateMailingRecipientXML($recipient_row)
{
	$attributes='';
	if($recipient_row['Status']=='sent' || $recipient_row['Status']=='rejected')
		$attributes.=' sendingDate="'.$recipient_row['SendingDate'].'"';
	if($recipient_row['Status']=='rejected')
		$attributes.=' rejectingDate="'.$recipient_row['RejectingDate'].'"';
	if($recipient_row['Status']=='sent' && $recipient_row['SeenOnWeb']==1)
		$attributes.=' seenOnWeb="true"';
	if($recipient_row['Status']=='sent' && $recipient_row['HTMLInMailbox']==1)
		$attributes.=' HTMLInMailbox="true"';
	if($recipient_row['Mail2Web']>0){
		$attributes.=' Mail2Web="'.$recipient_row['Mail2Web'].'"';
		$attributes.=' Mail2WebFirstURL="'.encode_to_XML($recipient_row['Mail2WebFirstURL']).'"';
		if($recipient_row['Mail2WebMediaTitle']!='')
			$attributes.=' Mail2WebMediaTitle="'.$recipient_row['Mail2WebMediaTitle'].'"';
	}
	if($recipient_row['SeenOnWeb']==1 || $recipient_row['HTMLInMailbox']==1)
		$attributes.=' viewingDate="'.$recipient_row['ViewingDate'].'"';
	$query_result='<CONTACT ID="'.$recipient_row["ContactID"].'" step="'.$recipient_row['Step'].'" status="'.$recipient_row['Status'].'" viewing_code="'.$recipient_row["ViewingCode"].'" '.$attributes.'>';
	$moduleInfo = moduleInfo('contact');
	$contact_row = getInfo($moduleInfo,$recipient_row['ContactID']);
	$contact_row['Email1']=$recipient_row['Email'];
	$fields_array=$moduleInfo->getFieldsBySecurity("R");
	$query_result.=generateInfoXML($moduleInfo,$contact_row,$fields_array,FALSE);
	$query_result.='</CONTACT>';
	return $query_result;
}

function copyMailingRecipients($oldMailingID,$newMailingID)
{
	$sql = 'SELECT * FROM `mailing_recipients` WHERE `MailingID`='.$oldMailingID;
	$db_conn = db_connect();
	$rs = $db_conn->Execute($sql);
	while($row=$rs->FetchRow()){
		$row['MailingID']=$newMailingID;
		if($row['Status']=='sent' || $row['Status']=='rejected')
			$row['Status']='not_sent';
		$row['SeenOnWeb']=0;
		$row['HTMLInMailbox']=0;
		$row['SendingDate']=$row['RejectingDate']="0000-00-00 00:00:00";
		$insert_sql = $db_conn->GetInsertSQL($rs, $row);
		$db_conn->Execute($insert_sql);
	}
}

function generateMailingRecipient($mailing_info,$contact)
{
	$moduleContactInfo = moduleInfo('contact');
	$fields_array=$moduleContactInfo->getFieldsBySecurity("R");
	$regex =
			  '^'.
			  '[_a-z0-9-]+'.        /* One or more underscore, alphanumeric,
									   or hyphen characters. */
			  '(\.[_a-z0-9-]+)*'.  /* Followed by zero or more sets consisting
									   of a period and one or more underscore,
									   alphanumeric, or hyphen charactures. */
			  '@'.                  /* Followed by an "at" characture. */
			  '[a-z0-9-]+'.        /* Followed by one or more alphanumeric
									   or hyphen charactures. */
			  '(\.[a-z0-9-]{2,})+'. /* Followed by one or more sets consisting
									   of a period and two or more alphanumeric
									   or hyphen charactures. */
			  '$';
	// preparing the recipient
	$recipient = array();
	$recipient['MailingID']=$mailing_info['ID'];
	$recipient['ContactID']=$contact['ID'];
	if($contact['Email1'])
	$recipient['Email']=$contact['Email1'];
	else
	$recipient['Email']=$contact['Email2'];
	
	$recipient['Step']=1;

	$recipient['ViewingCode'] = md5($mailing_info['ID'].$contact['ID'].$contact['Email1']);

	$email_invalidity = $GLOBALS['EmailMaxValidity'];
	if (!$email_invalidity)
	{
		$email_invalidity = 2;
	}

	// can we send the email ?
	if (trim($recipient['Email']) != "" && eregi($regex, trim($recipient['Email'])) && $contact['EmailInvalid']<$email_invalidity && $contact['Privacy1']!=='1'){
		$recipient['Status']='not_sent';
	}else if($contact['Privacy1']==='1'){
		$recipient['Status']='unsuscribed'; // we won't take them anymore, this case shouldn't happen
	}else{
		if($contact['EmailInvalid']>=$email_invalidity)
		$recipient['Status']='invalid';
		else
		$recipient['Status']='trash';
		
	}
	// executing the insertion in the DB
	
	$pseudo_sql = 'SELECT * FROM `mailing_recipients` WHERE `MailingID`=-1';
	$db_conn = db_connect();
	$pseudo_rs = $db_conn->Execute($pseudo_sql);
	$insert_sql = $db_conn->GetInsertSQL($pseudo_rs, $recipient);
	mailing_log($insert_sql);
	$db_conn->Execute($insert_sql);
	
}

function generateMailingRecipients($mailing_info)
{
	$row = $mailing_info;
	$recip_dep = depType('mailingGroupRecipients');
	$contact_recip_dep = depType('mailingRecipients');
	$media_dep = depType('mailingMediaToSend');
	$moduleContactInfo = moduleInfo('contact');
	$moduleMailingInfo = moduleInfo('mailing');
	$fields_array=$moduleContactInfo->getFieldsBySecurity("R");
	
	if ($mailing_info['Status']==='sending' || $mailing_info['Status']==='sent' || $mailing_info['Status']==='closed' ){
		return false;
	}else{
		resetMailingRecipients($mailing_info['ID']);
		$db_conn = db_connect();
		$resolv_sql = 'UPDATE `'.$moduleMailingInfo->tableName.'` SET `Status`="resolving" WHERE ID='.$mailing_info['ID'].';';
		$db_conn->Execute($resolv_sql);
		
		if (isset($mailing_info['RecallOfMailing']) && $mailing_info['RecallOfMailing']!=0){
			copyMailingRecipients($mailing_info['RecallOfMailing'],$mailing_info['ID']);
			updateMailingCounts($mailing_info['ID']);
			return true;
		}else{
			$group_rs = getDependenciesFrom($moduleMailingInfo->ID,$row["ID"],$recip_dep->ID);
			$group_IDs = array();
			while($dep_row = $group_rs->FetchRow()){
				$group_IDs[]=$dep_row['TargetID'];
			}
			$contact_infos = resolveMailingRecipients($group_IDs);
			if($contact_recip_dep->loaded===true){
				
				$contact_recip_rs = getDepTargetsInfo($moduleMailingInfo->ID,$row["ID"],$contact_recip_dep->ID);
				
				if($contact_recip_rs){
					while($contact_row = $contact_recip_rs->FetchRow()){
						$contact_infos[$contact_row["ID"]]= $contact_row;
					}
				}
			}
			// contact from the group or the recipients dependency
			foreach($contact_infos as $contact){
				//if(!isset($excludes[$contact['ID']]))
				if(strpos($mailing_info['ExcludeRecipients'],"<CONTACT ID=\"".$contact['ID']."\"")===false)	
					generateMailingRecipient($mailing_info,$contact);
			}
			// contacts from the recipient search
			if(isset($mailing_info['RecipientsSearch']) && $mailing_info['RecipientsSearch']!=''){
				$search_xml = new XML($mailing_info['RecipientsSearch']);
				if($search_xml->loaded){
					require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
					require_once(dirname(__FILE__)."/../private/executeCustomCmd.inc.php");
					$contactSearch_sql= '';
					$search_xml->removeChild('/SEARCH/CONTACT[1]/WITH[1]',true);
					
					$only_valids = new XML(
						'<SEARCH>
							<CONTACT>
								<INFO>
									<PRIVACY op="!=">1</PRIVACY>
									<EMAIL1 op="!="/>
								</INFO>
								<INFO>
									<PRIVACY op="!=">1</PRIVACY>
									<EMAIL2 op="!="/>
								</INFO>
							</CONTACT>
						</SEARCH>');
					
					$merger = new Sushee_QueryMerger($only_valids->getFirstChild(),$search_xml->getFirstChild());
					$totalquery = $merger->execute();
					
					//xml_out($totalquery->toString());
					
					$search_rs = getResultSet($moduleContactInfo,$totalquery,'/SEARCH',$contactSearch_sql);
					if($search_rs){
						while( $contact = $search_rs->FetchRow() ){
							//if(!isset($excludes[$contact['ID']]))
							if(strpos($mailing_info['ExcludeRecipients'],"<CONTACT ID=\"".$contact['ID']."\"")===false && $contact['Privacy1']!=='1')
								generateMailingRecipient($mailing_info,$contact);
						}
					}
				}
			}
			
			updateMailingCounts($mailing_info['ID']);
			return true;
		}
	}
}

function updateMailingCounts($mailingID,$resident_dbname='')
{
	$db_conn = db_connect();
	$base_sql = 'SELECT COUNT(`ContactID`) AS total FROM '.$resident_dbname.'`mailing_recipients` WHERE MailingID='.$mailingID.' ';
	
	$sql = $base_sql;
	$row = $db_conn->GetRow($sql);
	$counts['NbrTotal']=$row['total'];
	
	$sql = $base_sql.'AND Status="sent";';// envoyé et pas de retour négatif
	$row = $db_conn->GetRow($sql);
	$counts['NbrSent']=$row['total'];
	
	$sql = $base_sql.'AND Status="not_sent";'; // pas encore envoyé, mais prêt à l'être
	$row = $db_conn->GetRow($sql);
	$counts['NbrNotSent']=$row['total'];
	
	$sql = $base_sql.'AND Status="erroneous";';// email envoyé et averé faux, pas d'adresse streetmail pour envoyer par courrier
	$row = $db_conn->GetRow($sql);
	$counts['NbrErroneous']=$row['total'];
	
	$sql = $base_sql.'AND Status="streetmail";';// pas d'adresse mail au départ
	$row = $db_conn->GetRow($sql);
	$counts['NbrStreetmail']=$row['total'];
	
	$sql = $base_sql.'AND Status="streetmail2";';// adresse mail au départ, mais averée fausse -> par courrier
	$row = $db_conn->GetRow($sql);
	$counts['NbrStreetmail2']=$row['total'];
	
	$sql = $base_sql.'AND Status="invalid";';// adresse mail invalide au départ et pas d'adresse courrier
	$row = $db_conn->GetRow($sql);
	$counts['NbrInvalid']=$row['total'];
	
	$sql = $base_sql.'AND Status="trash";';// pas d'adresse mail, pas d'adresse courrier
	$row = $db_conn->GetRow($sql);
	$counts['NbrTrash']=$row['total'];
	
	/*$sql = $base_sql.'AND Status="unsuscribed";';// pas d'adresse mail ou mail invalide, pas d'adresse courrier
	$row = $db_conn->GetRow($sql);
	$counts['NbrUnsuscribed']=$row['total'];*/
	
	$sql = $base_sql.'AND Status="no_content";';// pas de contenu pour eux, ils ne sont pas concerne par le mail
	$row = $db_conn->GetRow($sql);
	$counts['NbrNoContent']=$row['total'];
	
	$sql = $base_sql.'AND Status="over_quota";';// mail retourne car plus de place dans la mailbox
	$row = $db_conn->GetRow($sql);
	$counts['NbrOverQuota']=$row['total'];
	
	$sql = $base_sql.'AND SeenOnWeb=1;';
	//debug_log($sql);
	$row = $db_conn->GetRow($sql);
	$counts['NbrSeenOnWeb']=$row['total'];
	
	$sql = $base_sql.'AND HTMLInMailbox=1;';
	$row = $db_conn->GetRow($sql);
	$counts['NbrHTMLInMailbox']=$row['total'];
	
	$sql = $base_sql.'AND (SeenOnWeb=1 OR HTMLInMailbox=1 OR Mail2Web>0);';
	//debug_log($sql);
	$row = $db_conn->GetRow($sql);
	$counts['NbrSeen']=$row['total'];
	
	// updating in table
	$pseudo_sql = 'SELECT * FROM '.$resident_dbname.'`mailings` WHERE `ID`='.$mailingID;
	//debug_log($pseudo_sql);
	$pseudo_rs = $db_conn->Execute($pseudo_sql);
	$updateSQL = $db_conn->GetUpdateSQL($pseudo_rs, $counts);
	mailing_log($updateSQL);
	$db_conn->Execute($updateSQL);
}

function updateMailingNbrSeen($mailingID)
{
	$db_conn = db_connect();
	$count_sql = 'SELECT COUNT(`ContactID`) AS total FROM `mailing_recipients` WHERE `MailingID`=\''.$mailingID.'\' AND (SeenOnWeb=1 OR HTMLInMailbox=1 OR Mail2Web>1)';
	$count_row = $db_conn->GetRow($count_sql);
	if($count_row){
		$NbrSeen=$count_row['total'];
		$update_sql = 'UPDATE `mailings` SET `NbrSeen`=\''.$NbrSeen.'\' WHERE ID=\''.$mailingID.'\';';
		$db_conn->Execute($update_sql);
	}
}

function updateMailingNbrSeenOnWeb($mailingID)
{
	$db_conn = db_connect();
	$count_sql = 'SELECT COUNT(`ContactID`) AS total FROM `mailing_recipients` WHERE `MailingID`=\''.$mailingID.'\' AND `SeenOnWeb`=1';
	$count_row = $db_conn->GetRow($count_sql);
	if($count_row){
		$NbrSeen=$count_row['total'];
		$update_sql = 'UPDATE `mailings` SET `NbrSeenOnWeb`=\''.$NbrSeen.'\' WHERE ID=\''.$mailingID.'\';';
		$db_conn->Execute($update_sql);
	}
}

function updateMailingNbrMail2Web($mailingID)
{
	$db_conn = db_connect();
	$count_sql = 'SELECT COUNT(`ContactID`) AS total FROM `mailing_recipients` WHERE `MailingID`=\''.$mailingID.'\' AND `Mail2Web`>0';
	$count_row = $db_conn->GetRow($count_sql);
	if($count_row){
		$NbrSeen=$count_row['total'];
		$update_sql = 'UPDATE `mailings` SET `NbrMail2Web`=\''.$NbrSeen.'\' WHERE ID=\''.$mailingID.'\';';
		$db_conn->Execute($update_sql);
	}
}

function updateMailingNbrNbrHTMLInMailbox($mailingID)
{
	$db_conn = db_connect();
	$count_sql = 'SELECT COUNT(`ContactID`) AS total FROM `mailing_recipients` WHERE `MailingID`=\''.$mailingID.'\' AND `HTMLInMailbox`=1';
	$count_row = $db_conn->GetRow($count_sql);
	if($count_row){
		$NbrSeen=$count_row['total'];
		$update_sql = 'UPDATE `mailings` SET `NbrHTMLInMailbox`=\''.$NbrSeen.'\' WHERE ID=\''.$mailingID.'\';';
		$db_conn->Execute($update_sql);
	}
}

function updateContactValidity($contactID,$validity)
{
	$db_conn = db_connect();
	$contact_valid_sql = 'UPDATE `contacts` SET `EmailInvalid`='.$validity.' WHERE `ID`='.$contactID;
	$db_conn->Execute($contact_valid_sql);
}

function resetMailingRecipients($mailingID)
{
	if(is_numeric($mailingID))
	{
		$db_conn = db_connect();
		$del_sql = 'DELETE FROM `mailing_recipients` WHERE `MailingID`='.$mailingID.';';
		$db_conn->Execute($del_sql);
	}
}


function generateCompleteMailingXML($in_mailbox,$mailing_info,$contact,$viewing_code,&$title)
{
	// generic objects
	$db_conn=db_connect();
	$moduleContactInfo = moduleInfo('contact');
	$fields_array=$moduleContactInfo->getFieldsBySecurity("R");
	$mailingModuleInfo = moduleInfo('mailing');
	
	$contact_language = $contact["LanguageID"];
	$lg = $mailing_info['DefaultLanguage'];
	// is there descriptions in the language of the user ?
	$restrict_language = false;
	$sql = "SELECT `LanguageID` FROM `descriptions` WHERE `LanguageID`='$contact_language' AND `Status`='published' AND `ModuleTargetID`='".($mailingModuleInfo->ID)."' and `TargetID`=".$mailing_info['ID'].";";
	
	$row = $db_conn->GetRow($sql);
	if ($row){
		$lg = $contact_language;
		$priority_language = $contact_language;
	}else{
		// we want all languages included in the datas, so the programmer can choose which language to display (and eventually every languages)
		$sql = "SELECT `LanguageID` FROM `descriptions` WHERE `LanguageID`='shared' AND `Status`='published' AND `ModuleTargetID`='".($mailingModuleInfo->ID)."' and `TargetID`=".$mailing_info['ID'].";";

		$row = $db_conn->GetRow($sql);
		if($row){
			$lg = $contact_language;
			$priority_language = $contact_language;
		}else{
			$priority_language = $mailing_info['DefaultLanguage'];
		}
		
	}
	
	
	// getting the title to send it back to the caller
	$title_sql = 'SELECT `Title` FROM `descriptions` WHERE `LanguageID` IN ("'.$lg.'","shared") and `Status`="published" and `ModuleTargetID`='.($mailingModuleInfo->ID).' and `TargetID`='.$mailing_info['ID'].' LIMIT 1';
	sql_log($title_sql);
	$title_row = $db_conn->GetRow($title_sql);
	$title = $title_row['Title'];
	
	foreach($fields_array as $field){
		$n=strtoupper($field);
		$title = str_replace("[$n]",$contact[$field],$title);
	}
	// global params at the top of the resulting xml
	$nectil_vars['language']=$lg;
	if($GLOBALS['resident_name'])
		$nectil_vars['resident_name']=$GLOBALS['resident_name'];
	$nectil_vars['files_url']=$GLOBALS['files_url'];
	$nectil_vars['public_url']=$GLOBALS['Public_url'];
	$nectil_vars['kernel_url']=$GLOBALS['backoffice_url'];
	$nectil_vars['host']=$GLOBALS['nectil_url'];
	if(isset($GLOBALS['residentURL2']) && $GLOBALS['residentURL2']!=''){
		$residentURL2 = str_replace("\r\n","\n",$GLOBALS['residentURL2']);
		$url2s = explode("\n",$residentURL2);
		$url2 = $url2s[0];
		//debug_log("url2 is ".$url2);
		$nectil_vars['files_url']=$url2.'/Files';
		$nectil_vars['public_url']=$url2.'/Public/';
		$nectil_vars['kernel_url']=$url2.'/sushee/';
	}
	if($in_mailbox)
		$nectil_vars['in_mailbox']='true';
	else
		$nectil_vars['in_mailbox']='false';
	$nectil_vars['priority_language'] = $priority_language;
	$params_str='';
	$params_str.='<NECTIL>';
	foreach($nectil_vars as $param_name=>$param_value)
		$params_str.='<'.$param_name.'>'.encode_to_XML($param_value).'</'.$param_name.'>';
	$today = $GLOBALS['sushee_today'];
	$hour = substr($today,11,2);
	$minute = substr($today,14,2);
	$second = substr($today,17,2);
	$day = substr($today,8,2);
	$month = substr($today,5,2);
	$year = substr($today,0,4);
	$todayTime = mktime($hour,$minute,$second,$month,$day,$year);
	$params_str.='<today weekday="'.date('w',$todayTime).'">'.encode_to_XML($today).'</today>';
	$params_str.='</NECTIL>';
	
	$GLOBALS['take_unpublished'] = TRUE;
	$GLOBALS['NectilLanguage'] = $lg;
	
	if($mailing_info['Engine']=='grep' && $contact['ID']=='[ID]'){
		$contact_str = '<RESULTS  name="contact_info"><CONTACT depth="1" ID="'.$contact['ID'].'" viewing_code="'.$viewing_code.(($viewing_code)?'_'.$mailing_info["ID"]:'').'">'.generateInfoXML($moduleContactInfo,$contact,$fields_array).'</CONTACT></RESULTS>';
	}else{
		$contact_query = '<QUERY><GET name="contact_info"><CONTACT ID="'.$contact['ID'].'"/></GET></QUERY>';
		$contact_str = query($contact_query,false,$restrict_language,true,false,true,$priority_language);
		//adding viewingCode
		$search = '<CONTACT depth="1" ID="'.$contact['ID'].'"';
		$replace = '<CONTACT depth="1" ID="'.$contact['ID'].'" viewing_code="'.$viewing_code.(($viewing_code)?'_'.$mailing_info["ID"]:'').'"';
		$contact_str = str_replace($search,$replace,$contact_str);
	}
	
	
	
	// medias and mailing requests
	if(file_exists($GLOBALS["library_dir"].'mailing/templates/profile.xml')){
		$profile_xml = new XML($GLOBALS["library_dir"].'mailing/templates/profile.xml');
		if($profile_xml->loaded)
			$with_str = $profile_xml->toString('/WITH[1]','');
		else
			$with_str = '<WITH depth="3"><INFO /><DESCRIPTIONS /><CATEGORIES /><DEPENDENCIES /></WITH>';
	}else
		$with_str = '<WITH depth="3"><INFO /><DESCRIPTIONS /><CATEGORIES /><DEPENDENCIES /></WITH>';
	$medias_query = '<QUERY><GET refresh="daily" name="mailing_info"><MAILING ID="'.$mailing_info["ID"].'"><WITH profile="mailing_publication" depth="2"/></MAILING></GET><GETCHILDREN refresh="daily" name="media_info" type="mailingMediaToSend"><MAILING ID="'.$mailing_info["ID"].'">'.$with_str.'</MAILING></GETCHILDREN></QUERY>';
	$medias_str = query($medias_query,false,$restrict_language,true,false,true,$priority_language);
	// for backward compatibility
	$searches = array("name=\"mailing_info\"","name=\"media_info\"");
	$replaces = array("name=\"mailing_info\" mailingID=\"".$mailing_info["ID"]."\"","name=\"media_info\" mailingID=\"".$mailing_info["ID"]."\"");
	$medias_str = str_replace($searches,$replaces,$medias_str);
	// additional requests
	if(file_exists($GLOBALS["library_dir"].'mailing/templates/navigation.php')){
		$_GET['ID'] = $mailing_info["ID"];
		$_GET['contactID'] = $contact["ID"];
		$navigation_file = include($GLOBALS["library_dir"].'mailing/templates/navigation.php');
		$supp_request_str = query($navigation_file,false,$restrict_language,true,false,true,$priority_language);
	}
	else if(file_exists($GLOBALS["library_dir"].'mailing/templates/navigation.xml'))
		$supp_request_str = query($GLOBALS["library_dir"].'mailing/templates/navigation.xml',false,$restrict_language,true,false,true,$priority_language);
	else
		$supp_request_str = '';
	
	require_once(dirname(__FILE__)."/../common/namespace.class.php");
	$namespaces = new NamespaceCollection();
	$namespaces_str = $namespaces->getXMLHeader();
	$xml_str = '<?xml version="1.0"?><RESPONSE'.$namespaces_str.'>'.$params_str.$medias_str.$contact_str.$supp_request_str.'</RESPONSE>';
	// replacing the keywords
	
	foreach($fields_array as $field){
		$n=strtoupper($field);
		$xml_str = str_replace("[$n]",/*"<span class=\"$n\">".*/encode_to_xml($contact[$field])/*."</span>"*/,$xml_str);
	}
	return $xml_str;
}

function addMailingSpy($data,$mailingID,$in_mailbox,$viewing_code)
{
	if($viewing_code)
		$viewing_code.='_'.$mailingID;
	$spy = '<mailing resident="'.$GLOBALS['resident_name'].'" ID="'.$mailingID.'" in_mailbox="'.$in_mailbox.'" viewing_code="'.$viewing_code.'"></mailing><img class="mailing-check" alt=" " src="'.$GLOBALS["backoffice_url"].'private/bkg.php?in_mailbox='.$in_mailbox.'&amp;ID='.$mailingID.'&amp;viewing_code='.$viewing_code.'"/>';
	// first trying with the opening body tag
	$closing_body_tag = strpos($data,"<body>");
	if($closing_body_tag!==false){
		$data = substr_replace($data,$spy,$closing_body_tag+6,0);
	}else{
		// trying with the closing body tag
		$closing_body_tag = strpos($data,"</body>");
		if($closing_body_tag!==false){
			$data = substr_replace($data,$spy,$closing_body_tag,0);
		}
	}
	return $data;
}

function sendMailofMailing($mailing,$contact,$total_xml_str,$subject,$viewing_code=false,$html_str=false,$text_str=false)
{
	
	$params = array('backoffice_url'=>$GLOBALS['backoffice_url'],'in_mailbox'=>'true');
	$template_path = $GLOBALS['library_dir'].'mailing/templates/'.$mailing['Template'];
	
	if($mailing['Plaintext']!=1 && !$html_str){
		$html_str='';
		if (file_exists($template_path)){
			$html_str = real_transform($total_xml_str,$template_path,$params,true,false);
			if($html_str===false){
				debug_log("transformation failed");
				return false;
			}else if(trim($html_str)==='no_content'){
				return 'no_content';
			}
			
		}else{
			debug_log('Template '.$mailing['Template'].' doesnt exist');
			return false;
		}
	}
	if(!$text_str){
		$text_template_path = $GLOBALS['library_dir'].'mailing/templates/text.xsl';
		if(file_exists($text_template_path))
			$text_str = real_transform($total_xml_str,$text_template_path,$params,true,false);
		else
			debug_log('No text template !!!');
	}
	
	
	$mime = new ServerMail();
	$mime->setText(UnicodeEntities_To_utf8($text_str));

	if ($html_str)
	{
		if( $mailing['EmbedImages']=='1' )
		{
			$src_pos = 0;
			$end_src_pos = 0;
			$count = 0;
			$images_parts = array();
			while( ($src_pos = strpos($html_str,' src="',$end_src_pos))!==false){
				$end_src_pos = strpos($html_str,'"',$src_pos+7);
				$src_pos+=6;
				$src_img = substr($html_str,$src_pos,$end_src_pos-$src_pos);
				$final_src_img = decode_from_xml($src_img);
				$handle = @fopen($final_src_img, "r");
				$img_str = '';
				if($handle){
					while (!feof($handle)) {
						$buffer = fgets($handle, 4096);
						$img_str.=$buffer;
					}
				}else
					debug_log("image not found");
				if($img_str!=''){
					$img_id = md5($src_img);//'image'.$count;
					global $slash;
					$tmp_filename = $GLOBALS["directoryRoot"].$slash."tmp".$slash.$img_id;
					saveInFile($img_str,$tmp_filename);
					$size = @getimagesize($tmp_filename);
					if($size)
						$type = $size['mime'];
					else
						$type = 'application/octet-stream';
					if(!isset($images_parts[$img_id]))
						$images_parts[$img_id]=array('src'=>$src_img,'type'=>$type,'path'=>$tmp_filename,'id'=>$img_id);
					$len_src_img = strlen($src_img);
					$html_str = substr_replace ( $html_str, $img_id, $src_pos , $len_src_img);
					$decal = $len_src_img - strlen($img_id);
					$end_src_pos-=$decal;
				}
				$count++;
			}
			// adding the spy
			if($viewing_code!=false){
				$html_str = addMailingSpy($html_str,$mailing['ID'],'true',$viewing_code);
			}
			$mime->setHTML($html_str);
			foreach($images_parts as $img_part){
				$res_embed = $mime->addHTMLImage($img_part['path'], $img_part['type'], $img_part['id']);
				unlink($img_part['path']);
			}
		}else{
			if($viewing_code!=false){
				$html_str = addMailingSpy($html_str,$mailing['ID'],'true',$viewing_code);
			}
			$mime->setHTML($html_str);
		}
	}

	// attachments handling : searching for the CUSTOM mailingAttachments field (a directory where all files to attach are)
	
	$attach_xml = new XMLFastParser($total_xml_str);
	$PriorityLanguage = $attach_xml->valueOf('/RESPONSE/NECTIL/priority_language');
	if($PriorityLanguage)
	{
		$mailing_descriptions = $attach_xml->getElement('/RESPONSE/RESULTS/MAILING');///HELLO/value
		while($desc = $mailing_descriptions->getNode('DESCRIPTION'))
		{
			$lgID = $desc->getAttribute('languageID');
			if($desc->getElement('CUSTOM/MailingAttachments'))
			{
				if($lgID=='shared')
				{
					$shared_attached = $desc->valueOf('CUSTOM/MailingAttachments');
				}
				else if($lgID==$PriorityLanguage)
				{
					$attached = $desc->valueOf('CUSTOM/MailingAttachments');
				}
			}

		}

		if($attached)
		{
			$attachment_dir = $attached;
		}
		else
		{
			$attachment_dir = $shared_attached;
		}

		if($attachment_dir)
		{
			$attachment = $GLOBALS["directoryRoot"].$attachment_dir;
			if (is_dir($attachment) && $dir = @opendir($attachment))
			{
				if(substr($attachment,-1)!='/')
				{
					$attachment.='/';
				}

				while($file = readdir($dir))
				{
					$complete_file = $attachment.$file;
					if ($file != "." && $file != ".." && file_exists($complete_file))
					{
						$mime->addAttachment($complete_file);
					}
				}
			}
			else if(is_file($attachment))
			{
				$mime->addAttachment($attachment);
			}
		}
	}

	$from = $mime->formatMailAdress($mailing['SenderEmail'],$mailing['SenderName']);

	if(isset($GLOBALS['PopMailAddress']))
	{
		$real_from_email = $GLOBALS['PopMailAddress'];
		$real_from = $mime->formatMailAdress($GLOBALS['PopMailAddress'],'nectil');
	}
	else
	{
		$real_from_email = $GLOBALS['PopUsername'];
		$real_from = $mime->formatMailAdress($GLOBALS['PopUsername'],'nectil');
	}

	$mime->setSender($from);
	$mime->setBounce($real_from);

	// removing quotation marks because it's not supported by the MTAs
	$contact_firstname = str_replace('"','',$contact["FirstName"]);
	$contact_lastname = str_replace('"','',$contact["LastName"]);
	$contact_email = $contact["Email1"];
	$mime->addRecipient($mime->formatMailAdress($contact_email,$contact_firstname." ".$contact_lastname));
	$mime->setSubject(decode_from_xml($subject));
	$res = $mime->execute();
	return $res;
}