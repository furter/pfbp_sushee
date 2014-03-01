<?php
include_once("common.php");
$dependencies = array('BookToEditor' => "", 'BookToPrinter' => "", 'BookToBinder' => "", 'BookToAuthor' => "", 'BookToGraphist' => "", 'BookToIllustrator' => "", 'BookToPhotograph' => "", 'BookToTranslator' => "", 'BookToOther' => "");
$fields = array('EMAIL1', 'EMAIL2', 'FIRSTNAME', 'LASTNAME', 'contributorType', 'PHONE1', 'MOBILEPHONE', 'POSTALCODE', 'CITY', 'COUNTRYID', 'ADDRESS');
$languages = array('fre', 'dut', 'eng');
///// récupération du formulaire pour la catégorie de l'année du prix 
$catNQL = new NQL(false);
$catNQL->addCommand('<GET><MEDIA ID="'.$_POST['ID'].'"/><RETURN><CATEGORIES/></RETURN></GET>');
$catNQL->execute();
$yearCatID = $catNQL->valueOf('/RESPONSE/RESULTS/MEDIA/CATEGORIES/CATEGORY[1]/@ID');


///// détection des CONTACT existant
$total = $_POST['c_total'];
$check_mail = "";
for ($i=1; $i <= $total; $i++) {
	$name= 'c'.$i.'_EMAIL1';
	$email = $_POST[$name];
	$mails[$email] = 'c'.$i;
	if ($email != "") {
		$check_mail .= '
		<SEARCH name="c'.$i.'">
			<CONTACT email1="'.$email.'"/>
			<RETURN>
				<INFO get="false"/>
			</RETURN>
		</SEARCH>';
	}
}
$nql = new NQL(false);
$nql->includeSuppParams(false);
$nql->addCommand($check_mail);
$nql->execute();
$results = $nql->getElements('/RESPONSE/RESULTS');
$existingUsers = array();
foreach ($results as $result) {
	if ($result->exists('CONTACT')) {
		$name = $result->getAttribute('name');
		$cID = $result->valueOf('CONTACT[1]/@ID');
		$existingUsers[$name] = $cID;
	}
}
// add user from auto completion form
$usersToUpdate = "";
for ($i=1; $i <= $total; $i++) {
	$name= 'c'.$i;
	$field = $name.'_cID';
	$cID = $_POST[$field];
	if ($cID != "") {
		$existingUsers[$name] = $cID;
		// update des données
		$usersToUpdate .= '<CREATE name="'.$name.'"><CONTACT if-exist="fill"><INFO>';
		$current_user = "";
		foreach ($fields as $field) {
			$field_name = $name.'_'.$field;	
			if ($_POST[$field_name] != '' && $field != 'contributorType') {
				$current_user .= '<'.$field.'>'.encode_to_xml(stripcslashes($_POST[$field_name])).'</'.$field.'>';
			}	
		}
		$denomination_field = $name.'_DENOMINATION';
		$contacttype_field = $name.'_CONTACTTYPE';
		$c_type = $_POST[$contacttype_field];
		if ($c_type == 'PM') {
			$current_user .= '<DENOMINATION>'.$_POST[$denomination_field].'</DENOMINATION>';
		}
		$usersToUpdate .= $current_user;
		$usersToUpdate .= '<CONTACTTYPE>'.$c_type.'</CONTACTTYPE>';
		$usersToUpdate .= '</INFO>';
		//
		/*
		$firstname_field = $name.'_FIRSTNAME';
		$lastname_field = $name.'_LASTNAME';
		$email_field = $name.'_EMAIL1';
		$denomination_node = '<DENOMINATION>'.$_POST[$denomination_field].'</DENOMINATION>';
		$email_node = '<EMAIL1>'.$_POST[$email_field].'</EMAIL1>';
		if ($_POST[$denomination_field] != '' and $_POST[$firstname_field] != '' and $_POST[$lastname_field] != '') {
			$to_search = array($denomination_node, $email_node);
			$to_replace = array('', '');
			$user_in_dependency = str_replace($to_search, $to_replace, $current_user);
			$usersToUpdate .= '<DEPENDENCIES><DEPENDENCY type="contactWork"><CONTACT><INFO><CONTACTTYPE>PP</CONTACTTYPE>';
			$usersToUpdate .= $user_in_dependency;
			$usersToUpdate .= '</INFO></CONTACT></DEPENDENCY></DEPENDENCIES>';
		}
		*/
		$usersToUpdate .= '</CONTACT></CREATE>';
	}
}
if ($usersToUpdate != "") {
	$nql->addCommand($usersToUpdate);
	$nql->execute();
}

// si soumetteur vient depuis mail -> formulaire prérempli: mise à jour de ses données
if ($_POST['fromEmail'] != "") {
	$prefix = 'c1';
	$toUpdate = "";
	foreach ($fields as $field) {
		if ($field != 'EMAIL1' and $field != 'contributorType') {
			$field_name = $prefix.'_'.$field;
			$toUpdate .= '<'.$field.'>'.$_POST[$field_name].'</'.$field.'>';
		}
	}
	$nql->addCommand('
		<UPDATE>
			<CONTACT ID="'.$_POST['fromEmail'].'">			
				<INFO>
					'.$toUpdate.'
				</INFO>
			</CONTACT>
			<CATEGORIES operation="append">
				<CATEGORY ID="36"/>
			</CATEGORIES>
		</UPDATE>
	');
	$nql->execute();
}


// création des autres CONTACT
// detect if field DENOMINATION -> split PP --- dep pro ---> PM
$existingUsers_str = implode(',',$existingUsers);
$usersToCreate = "";
for ($i=1; $i <= $total; $i++) { 
	$name = 'c'.$i;
	if ( !$existingUsers[$name] ) {
		$usersToCreate .= '<CREATE name="'.$name.'"><CONTACT><INFO>';
		$current_user = "";
		foreach ($fields as $field) {
			$field_name = $name.'_'.$field;	
			if ($_POST[$field_name] != '' && $field != 'contributorType') {
				$current_user .= '<'.$field.'>'.encode_to_xml(stripcslashes($_POST[$field_name])).'</'.$field.'>';
			}	
		}
		$denomination_field = $name.'_DENOMINATION';
		$contacttype_field = $name.'_CONTACTTYPE';
		$c_type = $_POST[$contacttype_field];
		if ($c_type == 'PM') {
			$current_user .= '<DENOMINATION>'.$_POST[$denomination_field].'</DENOMINATION>';
		}
		$usersToCreate .= $current_user;
		$usersToCreate .= '<CONTACTTYPE>'.$c_type.'</CONTACTTYPE>';
		$usersToCreate .= '</INFO>';
		//
		//echo $current_user;
		//
		$firstname_field = $name.'_FIRSTNAME';
		$lastname_field = $name.'_LASTNAME';
		$email_field = $name.'_EMAIL1';
		$denomination_node = '<DENOMINATION>'.$_POST[$denomination_field].'</DENOMINATION>';
		$email_node = '<EMAIL1>'.$_POST[$email_field].'</EMAIL1>';
		if ($_POST[$denomination_field] != '' and $_POST[$firstname_field] != '' and $_POST[$lastname_field] != '') {
			// check if existe ?
			//
			$contactWork = new NQL( false );
			$contactWork->addCommand('<SEARCH><CONTACT firstname="'.$_POST[$firstname_field].'" lastname="'.$_POST[$lastname_field].'"/><RETURN><INFO get="false"/></RETURN></SEARCH>');
			$contactWork->execute();
			//
			$usersToCreate .= '<DEPENDENCIES><DEPENDENCY type="contactWork" operation="append">';
			//
			if ($contactWork->exists('/RESPONSE/RESULTS/CONTACT')) {
				$cwID = $contactWork->valueOf('/RESPONSE/RESULTS/CONTACT[1]/@ID');
				$usersToCreate .= '<CONTACT ID="'.$cwID.'"/>';
			} else {
				$to_search = array($denomination_node, $email_node);
				$to_replace = array('', '');
				$user_in_dependency = str_replace($to_search, $to_replace, $current_user);
				$usersToCreate .= '<CONTACT><INFO><CONTACTTYPE>PP</CONTACTTYPE>';
				$usersToCreate .= $user_in_dependency;
				$usersToCreate .= '</INFO></CONTACT>';
			}
			$usersToCreate .= '</DEPENDENCY></DEPENDENCIES>';
		}
		$usersToCreate .= '<CATEGORIES operation="append"><CATEGORY ID="36"/></CATEGORIES>';		
		$usersToCreate .= '</CONTACT></CREATE>';
	}
}

//echo $usersToCreate;
$nql->addCommand($usersToCreate);
$nql->execute();
$results = $nql->getElements('/RESPONSE/MESSAGE');
foreach ($results as $result) {
	if ($result->exists('CONTACT')) {
		$name = $result->getAttribute('name');
		$cID = $result->valueOf('CONTACT[1]/@ID');
		$existingUsers[$name] = $cID;
	}
}
// assignation des CONTACT aux bonnes dépendances
$book_others = "";
foreach ($existingUsers as $user => $uID) {
	$name = $user.'_contributorType';
	if ($_POST[ $name ]) {
		foreach ($_POST[$name] as $value) {
			$dependencies[$value] .= '<CONTACT ID="'.$uID.'"/>';
			if ($value == 'BookToOther') {
				$otherField = $user.'_BookOtherContributorType';
				$book_others .= '<p><strong>'.$uID.'</strong> <em>'.encode_to_xml(stripcslashes($_POST[$otherField])).'</em></p>';
			}
		}
	}
}

///// creation du livre
// récupérer le code disponible pour le livre
/// 2010-001-IDBK-IDC1
$nql->addCommand('
	<SEARCH>
		<MEDIA mediatype="Book">
			<CATEGORIES>
				<CATEGORY ID="'.$yearCatID.'"/>
			</CATEGORIES>
		</MEDIA>
		<SORT select="DESCRIPTIONS/DESCRIPTION/CUSTOM/NUMBER" order="descending" data-type="number"/>
		<PAGINATE page="1" display="1"/>
		<RETURN>
			<DESCRIPTIONS>
				<DESCRIPTION>
					<CUSTOM>
						<NUMBER/>
					</CUSTOM>
				</DESCRIPTION>
			</DESCRIPTIONS>
		</RETURN>
	</SEARCH>');
$nql->execute();
$book_number = $nql->valueOf('/RESPONSE/RESULTS/MEDIA/DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/NUMBER');
$book_number = $book_number + 1;
if ($book_number == "") {
	$book_number = 1;
}

$eventstart = $_POST['book_EVENTSTART'].'-01-01';
// categories + dependances
$book_info = '<INFO><MEDIATYPE>Book</MEDIATYPE><DENOMINATION>'.$_POST['book_TITLE'].'</DENOMINATION><EVENTSTART>'.$eventstart.'</EVENTSTART></INFO>';
//
$book_authors = "";
if (count($_POST['book_BOOK_AUTHORS_firstname']) > 0 and ($_POST['book_BOOK_AUTHORS_firstname'][0] != "" or $_POST['book_BOOK_AUTHORS_lastname'][0]) ) {
	$book_authors = '<CSS>';
	foreach ($_POST['book_BOOK_AUTHORS_firstname'] as $index=>$author) {
		$book_authors .= '<p>'.encode_to_xml(stripcslashes($author)).' '.$_POST['book_BOOK_AUTHORS_lastname'][$index].'</p>';
	}
	$book_authors .= '</CSS>';
}
if ($book_others != '') {
	$book_others = '<CSS>'.$book_others.'</CSS>';
}
//
$book_description = "
<TITLE>".encode_to_xml(stripcslashes($_POST['book_TITLE']))."</TITLE>
<HEADER>".encode_to_xml(stripcslashes($_POST['book_HEADER']))."</HEADER>
<BIBLIO>".encode_to_xml(stripcslashes($_POST['book_BIBLIO']))."</BIBLIO>
<CUSTOM>
	<BOOK_LEGAL_DEPOSIT>".encode_to_xml(stripcslashes($_POST['book_BOOK_LEGAL_DEPOSIT']))."</BOOK_LEGAL_DEPOSIT>
	<BOOK_ISBN>".encode_to_xml(stripcslashes($_POST['book_BOOK_ISBN']))."</BOOK_ISBN>
	<YEAR>".encode_to_xml(stripcslashes($_POST['book_EVENTSTART']))."</YEAR>
	<NUMBER>".$book_number."</NUMBER>
	<BOOK_AUTHORS>".$book_authors."</BOOK_AUTHORS>
	<BOOK_OTHERS>".$book_others."</BOOK_OTHERS>
</CUSTOM>
";
//
$book_descriptions = "<DESCRIPTIONS>";
foreach ($languages as $language) {
	$book_descriptions .= '<DESCRIPTION><LANGUAGEID>'.$language.'</LANGUAGEID>'.$book_description.'</DESCRIPTION>';
}
$book_descriptions .= "</DESCRIPTIONS>";
//
$book_categories = '<CATEGORIES><CATEGORY ID="'.$yearCatID.'"/><CATEGORY ID="'.$_POST['book_book_theme'].'"/></CATEGORIES>';
//
$book_dependencies = '<DEPENDENCIES>';
$book_dependencies .= '<DEPENDENCY type="BookToPeopleContact"><CONTACT ID="'.$existingUsers['c1'].'"/></DEPENDENCY>';
foreach ($dependencies as $key => $value) {
	$book_dependencies .= '<DEPENDENCY type="'.$key.'">'.$value.'</DEPENDENCY>';
}
$book_dependencies .= '</DEPENDENCIES>';
//
$book = '<CREATE><MEDIA>'.$book_info.$book_descriptions.$book_categories.$book_dependencies.'</MEDIA></CREATE>';
// echo $book;
$nql->addCommand($book);
$nql->execute();
$bID = $nql->valueOf('/RESPONSE/MESSAGE/MEDIA/@ID');
/*
while (strlen($book_number) < 3) {
	$book_number = "0".$book_number;
}
$code = "2010-".$book_number.'-'.$bID.'-'.$existingUsers['c1'];
*/
//
$confirmationMail = new NQL(false);
$confirmationMail->addCommand('<GET name="mail"><MEDIA ID="'.$_POST['mailID'].'"/><RETURN depth="1"><INFO><MEDIATYPE/></INFO><DESCRIPTIONS/></RETURN></GET>');
$confirmationMail->addCommand('
<SEARCH name="BookSubmission">
	<MEDIA mediatype="BookSubmission">
		<DESCENDANT>
			<MEDIA ID="'.$_POST['mailID'].'" />
		</DESCENDANT>
	</MEDIA>
	<RETURN>
		<INFO get="false" />
	</RETURN>
</SEARCH>');
$confirmationMail->addCommand('<GET name="book"><MEDIA ID="'.$bID.'"/><RETURN depth="1"><INFO><MEDIATYPE/></INFO><DESCRIPTIONS/></RETURN></GET>');
$confirmationMail->execute();
//
$subject = $confirmationMail->valueOf('/RESPONSE/RESULTS[@name="mail"]/MEDIA/DESCRIPTIONS/DESCRIPTION/HEADER')." - ".$_POST['book_TITLE'];
$email = $confirmationMail->transformToText('TransformEmail.xsl');
sendhtmlmail('Prix Fernand Baudin Prijs', 'info@prixfernandbaudinprijs.be', '', $_POST['c1_EMAIL1'], $subject, $email);
//
$return = $confirmationMail->copyOf('/RESPONSE/RESULTS[@name="mail"]/MEDIA/DESCRIPTIONS/DESCRIPTION/SUMMARY/CSS/*');
echo $return;
//
?>