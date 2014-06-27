<?php
include_once("common.php");
$all_fields = array('CONTACTTYPE', 'EMAIL1', 'DENOMINATION', 'EMAIL2', 'FIRSTNAME', 'LASTNAME', 'PHONE1', 'MOBILEPHONE', 'POSTALCODE', 'CITY', 'COUNTRYID', 'ADDRESS');
$fields = array();
$fields['PP'] = array('CONTACTTYPE', 'EMAIL1', 'FIRSTNAME', 'LASTNAME', 'PHONE1', 'MOBILEPHONE', 'POSTALCODE', 'CITY', 'COUNTRYID', 'ADDRESS');
$fields['PM'] = array('CONTACTTYPE', 'EMAIL1', 'DENOMINATION', 'FIRSTNAME', 'LASTNAME', 'EMAIL2', 'PHONE1', 'MOBILEPHONE', 'POSTALCODE', 'CITY', 'COUNTRYID', 'ADDRESS');

if ($_GET['test'] == 1) {
	$_POST = $_GET;
}

if ($_POST['contact'] != '') {
	$NQL = new NQL(false);
	$NQL->includeSuppParams(false);
	if ( isset($_POST['cID']) && $_POST['cID'] != '' ) {
		$NQL->addCommand('
			<GET name="contact">
				<CONTACT ID="'.$_POST['cID'].'"/>
				<RETURN>
					<INFO/>
				</RETURN>
			</GET>
		');
	} else {
		$NQL->addCommand('
			<SEARCH name="contact">
				<CONTACT>
					<INFO><FULLTEXT>'.$_POST['contact'].'</FULLTEXT></INFO>
				</CONTACT>
				<RETURN>
					<INFO/>
				</RETURN>
			</SEARCH>
		');
		$NQL->addCommand('<GET><LABEL name="BookSubmission_checkContact_many"/></GET>');
		$NQL->addCommand('<GET><LABEL name="BookSubmission_contributorNotFound"/></GET>');
	}
	$NQL->execute();
	$total = $NQL->valueOf('/RESPONSE/RESULTS/@hits');
	$position = 1;
	if ($total > 1) {
		$many_contacts = $NQL->getElements('/RESPONSE/RESULTS/CONTACT');	
		foreach ($many_contacts as $key => $contact) {
			$denomination = entities_to_utf8(utf8_decode($contact->valueOf('INFO/DENOMINATION')));
			if ($denomination == $_POST['contact']) {
				$position =  $key + 1;
			}
		}
	}
	if ($NQL->exists('/RESPONSE/RESULTS/CONTACT') && $NQL->count('/RESPONSE/RESULTS/CONTACT') == 1) {
		$contacts = $NQL->getElements('/RESPONSE/RESULTS/CONTACT['.$position.']');	
		$to_fill = "";
		$filled = "";
		foreach ($contacts as $contact) {
			$cID = $contact->valueOf('@ID');
			$cType = $contact->valueOf('INFO/CONTACTTYPE');
			/*
			$denomination = entities_to_utf8( $contact->valueOf('INFO/DENOMINATION') );
			$firstname = entities_to_utf8( $contact->valueOf('INFO/FIRSTNAME') );
			$lastname = entities_to_utf8( $contact->valueOf('INFO/LASTNAME') );
			*/
			foreach ($fields[$cType] as $field) {
				$field_value = $contact->valueOf('INFO/'.$field);
				if ($field_value == "") {
					$to_fill .= $field.',';
				} else {
					if ($filled != "") {
						$filled .= ', ';
					}
					$filled .= '"'.$field.'": "'.$field_value.'"';
				}
			}
		}
		/*
		if (strlen($to_fill) > 0) {
			if (strpos($to_fill, 'MOBILEPHONE') > -1 and strpos($to_fill, 'PHONE1') > -1) {
				$to_return = '{"reponse": 2, "cID": '.$cID.', "toFill": "'.$to_fill.'", "cType": "'.$cType.'", '.$filled.'}';
			} else if ( strpos($to_fill, 'MOBILEPHONE') > -1) {
				$to_fill = str_replace('MOBILEPHONE,', '', $to_fill);
				if (strlen($to_fill) > 1) {
					$to_return =  '{"reponse": 2, "cID": '.$cID.', "toFill": "'.$to_fill.'", "cType": "'.$cType.'", '.$filled.'}';
				}
			} else if (strpos($to_fill, 'PHONE1') > -1) {
				$to_fill = str_replace('PHONE1,', '', $to_fill);
				if (strlen($to_fill) > 0) {
					$to_return =  '{"reponse": 2, "cID": '.$cID.', "toFill": "'.$to_fill.'", "cType": "'.$cType.'", '.$filled.'}';
				}
			}
		}
		*/
		/*
		if (strlen($to_fill) == 0) {
			$to_return =  '{"reponse": 1, "cID": '.$cID.', "cType": "'.$cType.'", '.$filled.'}';
		}*/
		$to_return =  '{"reponse": 1, "cID": '.$cID.', "cType": "'.$cType.'", '.$filled.'}';
	} else if ( $NQL->exists('/RESPONSE/RESULTS/CONTACT') && $NQL->count('/RESPONSE/RESULTS/CONTACT') > 1 ) {
		$contacts = $NQL->getElements('/RESPONSE/RESULTS/CONTACT');	
		// $contact_list = array();
		$contact_list = '<h5>'.$NQL->valueOf('/RESPONSE/RESULTS/LABEL[@name="BookSubmission_checkContact_many"]').'</h5>';
		$contact_list .= '<ul>';
		foreach ($contacts as $contact) {			
			$cID = $contact->valueOf('@ID');
			$cType = $contact->valueOf('INFO/CONTACTTYPE');
			$denomination = entities_to_utf8( $contact->valueOf('INFO/DENOMINATION') );
			$firstname = entities_to_utf8( $contact->valueOf('INFO/FIRSTNAME') );
			$lastname = entities_to_utf8( $contact->valueOf('INFO/LASTNAME') );
			if ($cType == 'PM') {
				$c_name = $denomination;
				if ($firstname != '' && $lastname != '') {
					$c_name .= " ($firstname $lastname)";
				}
			} else {
				$c_name = $firstname.' '.$lastname;
				if ($denomination != '') {
					$c_name .= " ($denomination)";
				}
			}
			/*
			$c_data = array('cID' => $cID, 'name'=> $c_name);
			array_push($contact_list, $c_data);			
			*/
			$contact_list .= '<li><a val="'.$cID.'">'.$c_name.'</a></li>';
		}
		$contact_list .= '</ul>';
		$contact_list .= '<p><a class="newInsert">'.$NQL->valueOf('/RESPONSE/RESULTS/LABEL[@name="BookSubmission_contributorNotFound"]').'</a></p>';
		$to_return =  '{"reponse": 2, "contacts": '.json_encode($contact_list).'}';
	} else {
		$to_return =  '{"reponse": 0}';
	}
	echo str_replace("&apos;", "’", $to_return);
}
?>