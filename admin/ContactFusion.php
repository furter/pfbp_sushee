<?php
include_once("common.php");
$imported_fields = array('ID', 'CONTACTTYPE', 'PASSWORD', 'FIRSTNAME', 'LASTNAME', 'DENOMINATION', 'CLIENTCODE', 'EMAIL1', 'EMAILINVALID', 'TITLE', 'ADDRESS', 'POSTALCODE', 'CITY', 'STATEORPROVINCE', 'COUNTRYID', 'PHONE2', 'MOBILEPHONE', 'FAX', 'LANGUAGEID', 'PHONE1', 'EMAIL2', 'WEBSITE', 'GENDER', 'BIRTHDAY', 'PLACEOFBIRTH', 'NATIONALNUMBER', 'IDCARDNUMBER', 'SISNUMBER', 'DIGITALINFO1', 'DIGITALINFO2', 'VAT', 'RC', 'BANK1', 'BANK1IBAN', 'BANK1INFO', 'BANK2', 'BANK2IBAN', 'BANK2INFO', 'NOTES', 'PRIVACY1', 'PRIVACY2', 'PURPOSE', 'BILLINGRATE', 'PREFS', 'PREVIEW');
$unimported_fields = array('CREATIONDATE', 'MODIFICATIONDATE', 'ACTIVITY', 'ISLOCKED', 'CREATORID', 'OWNERID', 'GROUPID', 'MODIFIERID', 'ID');
//
$dependency_types = array('contactFamily', 'contactWork', 'PeopleContactToBook', 'EditorToBook', 'PrinterToBook', 'GraphistToBook', 'BinderToBook', 'PhotographToBook', 'IllustratorToBook', 'AuthorToBook', 'OtherToBook', 'TranslatorToBook');
//
$contact_dependency_types = array('contactFamily', 'contactWork');
$media_dependency_types = array('PeopleContactToBook', 'EditorToBook', 'PrinterToBook', 'GraphistToBook', 'BinderToBook', 'PhotographToBook', 'IllustratorToBook', 'AuthorToBook', 'OtherToBook', 'TranslatorToBook');
//
//$_GET['fromID']
//$_GET['toID'
//
function contactToArray($NQL, $name)
{
	global $imported_fields;
	global $media_dependency_types;
	global $contact_dependency_types;
	//
	$array = array();
	$contactInfo = $NQL->getElement('/RESPONSE/RESULTS[@name="'.$name.'"]/CONTACT[1]');
	foreach ($imported_fields as $field) {
		$path = 'INFO/'.$field;
		//echo $field.': '.$contactInfo->valueOf($path);
		$array[$field] = $contactInfo->valueOf($path);
	}
	// dependencies
	foreach ($contact_dependency_types as $type) {
		$array[$type] = "";
		$contacts = $contactInfo->getElements('DEPENDENCIES/DEPENDENCY[@type="'.$type.'"]/MEDIA');
		foreach ($contacts as $contact) {
			$array[$type] .= '<MEDIA ID="'.$contact->valueOf('@ID').'"/>';
		}
	}
	foreach ($media_dependency_types as $type) {
		$array[$type] = "";
		$medias = $contactInfo->getElements('DEPENDENCIES/DEPENDENCY[@type="'.$type.'"]/MEDIA');
		foreach ($medias as $media) {
			$array[$type] .= '<MEDIA ID="'.$media->valueOf('@ID').'"/>';
		}
	}
	//categories
	$array["categories"] = "";
	$categories = $contactInfo->getElements('CATEGORIES/CATEGORY');
	foreach ($categories as $category) {
		$array["categories"] .= '<CATEGORY ID="'.$category->valueOf('@ID').'"/>';
	}
	return $array;
}

//
if (isset($_GET['fromID']) && isset($_GET['toID']) && $_GET['fromID'] != '' && $_GET['toID'] != '') {
	// query
	$NQL = new NQL(false);
	$NQL->addCommand(
		'<GET name="from">
			<CONTACT ID="'.$_GET['fromID'].'"/>
			<RETURN depth="2">
				<INFO/>
				<CATEGORIES/>
				<DEPENDENCIES>
					<INFO get="false"/>
				</DEPENDENCIES>
			</RETURN>
		</GET>');
	$NQL->addCommand(
		'<GET name="to">
			<CONTACT ID="'.$_GET['toID'].'"/>
			<RETURN depth="2">
				<INFO/>
				<CATEGORIES/>
				<DEPENDENCIES>
					<INFO get="false"/>
				</DEPENDENCIES>
			</RETURN>
		</GET>');
	$NQL->execute();
	//from
	$from = contactToArray($NQL, 'from');
	//to
	$to = contactToArray($NQL, 'to');
	// back up -> notes	
	$to_notes = $to["NOTES"].'
-------------
UTILISATEUR FUSIONNE
-------------';
	foreach ($from as $key => $value) {
		if ($value != "") {
			$value = str_replace('<', '', $value);
			$value = str_replace('/>', ', ', $value);
			$to_notes .= '
'.$key.': '.$value;
		}
	}
	// info from -> to
	$updateInfo = "";
	$previewContact = "";
	foreach ($to as $key => $value) {
		if (in_array($key, $imported_fields)) {
			if ($value == "" && $from[$key] != '') {
				// on importe
				$updateInfo .= "<$key>$from[$key]</$key>";
				$preview .= '<br/>'.$key.': '.$from[$key];
			} else if ($value != "") {
				$preview .= '<br/>'.$key.': '.$value;
			}
		}
	}
	// dependances from + to
	$updateDependencies = "";
	foreach ($dependency_types as $type) {
		$updateDependencies .= '<DEPENDENCY type="'.$type.'">'.$from[$type].$to[$type].'</DEPENDENCY>';
		$preview .= '<br/>'.$type.': '.$from[$type].$to[$type];
	}
	// categories from + to
	$updateCategories = $from["categories"].$to["categories"];
	$preview .= '<br/>'.$type.': '.$from["categories"].$to["categories"];
	//kill from
	$killFrom = '<KILL><CONTACT ID="'.$from['ID'].'"/></KILL>';
	//update to
	$updateTo = '
	<UPDATE>
		<CONTACT ID="'.$to['ID'].'">
			<INFO>
				'.$updateInfo.'
				<NOTES>'.$to_notes.'</NOTES>
			</INFO>
			<DEPENDENCIES operation="append">
				'.$updateDependencies.'
			</DEPENDENCIES>
			<CATEGORIES operation="append">
				'.$updateCategories.'
			</CATEGORIES>
		</CONTACT>
	</UPDATE>
	';
	//
	if ($_GET['confirm'] == 1) {
		$NQL->addCommand($killFrom);
		$NQL->addCommand($updateTo);
		$NQL->execute();
		echo '<h3>Fusion effectuée</h3>';
		echo '
		<form name="fusion" action="ContactFusion.php" method="get">
			<label for="fromID">ID du contact source</label>
			<input type="text" name="fromID" id="fromID"/>
			<br/>
			<label for="toID">ID du contact destination</label>
			<input type="text" name="toID" id="toID"/>
			<br/>
			<input type="submit" value="fusionner"/>
		</form>';
	} else {
		echo '<h3>Voulez-vous vraiment fusionner les contacts<br/>
		'.$from['ID'].' - '.$from['FIRSNTAME'].' '.$from['LASTNAME'].' '.$from['DENOMINATION'].'<br/>
		et<br/>
		'.$to['ID'].' - '.$to['FIRSNTAME'].' '.$to['LASTNAME'].' '.$to['DENOMINATION'].'<br/>?</h3>';
		echo '<h4>Voici le contact après fusion</h4>';
		$preview = str_replace('<', '', $preview);
		$preview = str_replace('/>', ', ', $preview);
		$preview = str_replace('br, ', '<br/>', $preview);
		echo "<p>$preview</p>";
		echo '<a href="ContactFusion.php?fromID='.$from['ID'].'&toID='.$to['ID'].'&confirm=1">Fusionner les contacts</a><br/>';
		echo '<a href="ContactFusion.php">Ne pas fusionner</a>';
	}
} else {
	echo '
	<form name="fusion" action="ContactFusion.php" method="get">
		<label for="fromID">ID du contact source</label>
		<input type="text" name="fromID" id="fromID"/>
		<br/>
		<label for="toID">ID du contact destination</label>
		<input type="text" name="toID" id="toID"/>
		<br/>
		<input type="submit" value="fusionner"/>
	</form>';
}


?>