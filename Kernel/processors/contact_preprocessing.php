<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/contact_preprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
if ( $requestName=="CREATE" || $requestName=="UPDATE" ){
	// remove end-of-lines from the fields Firstname, Lastname, Denomination and Email1
	$end_of_lines_stripped_fields = array('FirstName','LastName','Denomination','Email1');
	foreach($end_of_lines_stripped_fields as $fieldname){
		if(isset($values[$fieldname])){
			$values[$fieldname] = str_replace(array("\r","\n"),'',$values[$fieldname]);
		}
	}
	
	// check email and client_code stays unique with this treatment
	//-------------------------------------------------------------
	// the number of contacts we must change/create
	$number_to_manage = sizeof($IDs_array);
	// if trying to create or update a unique contact, we must check the email doesn't exist yet
	$existence_check=$xml->getData($firstNodePath."/@existence-check"); // if we want to check unicity on another field than EMAIL1
	$if_exist=$xml->getData($firstNodePath."/@if_exist");
	if(!$if_exist)
		$if_exist=$xml->getData($firstNodePath."/@if-exist"); // three way to write if-exist are accepted
	if(!$if_exist)
		$if_exist=$xml->getData($firstNodePath."/@if-exists");
	if ($number_to_manage==1 && isset($values["Email1"]) && $values["Email1"]!="" ){
		
		$test_email_sql = "SELECT `ID` FROM `".$moduleInfo->tableName."` WHERE `Email1`=\"".$values["Email1"]."\" AND `Activity`=1";
		// sql_log($test_email_sql);
		// if it's an update it may be the same who has already this email
		if ($requestName=="UPDATE")
			$test_email_sql.=" AND `ID` != '".$IDs_array[0]."';";
		if ($row = $db_conn->getRow($test_email_sql) ){
			$xml->setAttribute($firstNodePath,'ID',$row['ID']);
			$this->elementID = $row['ID'];
			if($if_exist==='replace' || $if_exist==='fill'){
				include_once(dirname(__FILE__)."/../private/update.inc.php");
				return updateQuery($name,$xml,'UPDATE',$current_path,$firstNode,$firstNodePath);
			}else if($if_exist==='skip'){
				$query_result = generateMsgXML(0,"Email ".$values["Email1"]." already used (No error generated because skip was set).","2",$row['ID'],$name);
			}else{
				$query_result = generateMsgXML(1,"Email ".$values["Email1"]." already used.","2",$row['ID'],$name);
			}
			return $query_result;
		}
	}
	
	if ($existence_check && $requestName=="CREATE"){
		$existence_check_fieldname = $moduleInfo->getFieldName($existence_check);
		if($existence_check_fieldname){
			if(isset($values[$existence_check_fieldname])){
				$test_email_sql = "SELECT `ID` FROM `".$moduleInfo->tableName."` WHERE `".$existence_check_fieldname."`=\"".$values[$existence_check_fieldname]."\" AND `Activity`=1";
			
				if ($row = $db_conn->getRow($test_email_sql) ){
					if($if_exist==='replace' || $if_exist==='fill'){
						$xml->setAttribute($firstNodePath,'ID',$row['ID']);
						include_once dirname(__FILE__)."/../private/update.inc.php";
						return updateQuery($name,$xml,'UPDATE',$current_path,$firstNode,$firstNodePath);
					}else if($if_exist==='skip'){
						$query_result = generateMsgXML(0,$existence_check_fieldname." ".$values[$existence_check_fieldname]." already used (No error generated because skip was set).","2",$row['ID'],$name);
					}else{
						$query_result = generateMsgXML(1,$existence_check_fieldname." ".$values[$existence_check_fieldname]." already used.","2",$row['ID'],$name);
					}
					return $query_result;
				}
			}
		}else
			$query_result = generateMsgXML(1,$existence_check_fieldname." is not a valid fieldname.","2",'',$name);
	}
	// refusing multiple entries with the same email
	if ($number_to_manage>1 && isset($values["Email1"]) && $values["Email1"]!="" ){
		return $query_result = generateMsgXML(1,"No multiple contacts with the same email.");
	}
	// always allow to change his own password (or in website request we can assign passwords too)
	// seeing if we have a password in the xml and not in the array destined to create the sql query 
	if (!isset($values["Password"]) && $xml->match($current_path."/*[1]/INFO/PASSWORD") && $number_to_manage==1 && ($IDs_array[0]==$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'] || $GLOBALS["dev_request"]===TRUE) ){
		// putting the password in the array even if the security doesn't allow it because it's the user itself who changes his password
		$values["Password"]=$xml->getData($current_path."/*[1]/INFO[1]/PASSWORD[1]");
	}
	if(isset($values['Password']) && $number_to_manage==1){
		$pass_sql = 'SELECT `Password` FROM `'.$moduleInfo->tableName.'` WHERE `ID`=\''.$IDs_array[0].'\';';
		$former_pass_row = $db_conn->GetRow($pass_sql);
		$former_password = $former_pass_row['Password'];
		if($values['Password']!=$former_password){
			if($values['Password']!='' ){
				// must update any password saved in mailsaccounts
				require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
				$user = new NectilUser();
				$userID = $user->getID();
				if($userID==$IDs_array[0] && $user->getSessionPassword()!=''){ // only if the looged user is the mailaccount owner (if its not we cannot decrypt the mailsaccount passwords)
					
					$old_crypt_password = $user->getSessionPassword();
					$new_crypt_password = $user->setSessionPassword($values['Password']);
					$mailsaccount_sql = 'SELECT `ID`,`Encryption`,`Password` FROM `mailsaccounts` WHERE `OwnerID`=\''.$userID.'\' AND `Activity`=1';
					
					$mailsaccount_rs = $db_conn->Execute($mailsaccount_sql);
					include_once(dirname(__FILE__)."/../common/crypt.class.php");
					$crypt = new Crypt();
					$decrypt = new Decrypt();
					if($mailsaccount_rs){
						while($account = $mailsaccount_rs->FetchRow()){
							
							$decrypt->setAlgo($account['Encryption']);
							$decrypt->setKey($old_crypt_password);
							
							$crypt->setAlgo($account['Encryption']);
							$crypt->setKey($new_crypt_password);
							
							$former_account_password = $decrypt->execute($account['Password']);
							$account['Password'] = $crypt->execute($former_account_password);
							$mailsaccount_update = 'UPDATE `mailsaccounts` SET `Password`="'.$account['Password'].'",`ModificationDate`="'.$GLOBALS['sushee_today'].'" WHERE `ID`='.$account['ID'];
							$db_conn->Execute($mailsaccount_update);
						}
					}
				}
				$values['Password'] = mysql_password($values['Password']);
			}
		}else
			unset($values['Password']);
	}
}
if ( $requestName == "CREATE" ){
	$template = getInfo($moduleInfo,1);
	if(!isset($values['ContactType']) || $values['ContactType']=='')
		$return_values['ContactType'] = $values['ContactType'] = $template['ContactType'];
	if(!isset($values['LanguageID']) || $values['LanguageID']=='')
		$return_values['LanguageID'] = $values['LanguageID'] = $template['LanguageID'];
	if(!isset($values['CountryID']) || $values['CountryID']=='')
		$return_values['CountryID'] = $values['CountryID'] = $template['CountryID'];
}
return TRUE;
?>
