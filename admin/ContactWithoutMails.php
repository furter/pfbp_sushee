<?php
include_once("common.php");

$NQL = new NQL(false);
$NQL->addCommand(
	'<SEARCH name="contacts" refresh="daily">
		<CONTACT>
			<INFO><EMAIL1 operator="="/></INFO>
			<CATEGORIES>
				<CATEGORY ID="46" or-group="prix"/>
				<CATEGORY ID="47" or-group="prix"/>
			</CATEGORIES>
		</CONTACT>
		<RETURN depth="1">
			<INFO/>
		</RETURN>
	</SEARCH>');

$NQL->execute();
$contacts = $NQL->getElements('/RESPONSE/RESULTS/CONTACT');
foreach ($contacts as $contact) {
	$cID = $contact->valueOf('@ID');
	$cType = $contact->valueOf('INFO/CONTACTTYPE');
	$firstname = $contact->valueOf('INFO/FIRSTNAME');
	$lastname = $contact->valueOf('INFO/LASTNAME');
	$denomination = $contact->valueOf('INFO/DENOMINATION');
	$search = $firstname." ".$lastname;
	if ($cType == 'PM') {
		$search = $denomination;
	}
	//
	$searchNQL = new NQL(false);
	$searchNQL->addCommand('
		<SEARCH>
			<CONTACT>
				<INFO>
					<ID operator="!=">'.$cID.'</ID>
					<FULLTEXT>'.$search.'</FULLTEXT>
				</INFO>
			</CONTACT>
			<RETURN><INFO/></RETURN>
		</SEARCH>');
	$searchNQL->execute();
	$foundContacts = $searchNQL->getElements('/RESPONSE/RESULTS/CONTACT');
	//
	echo '<p>';
	echo $cID.' - '.$fistname.' - '.$lastname.' - '.$denomination;
	//
	if ($searchNQL->exists('/RESPONSE/RESULTS/CONTACT')) {
		echo ' =====> FOUND';
	//
		foreach ($foundContacts as $foundContact) {
			echo '<br/>';
			$cID = $foundContact->valueOf('@ID');
			$cType = $foundContact->valueOf('INFO/CONTACTTYPE');
			$firstname = $foundContact->valueOf('INFO/FIRSTNAME');
			$lastname = $foundContact->valueOf('INFO/LASTNAME');
			$denomination = $foundContact->valueOf('INFO/DENOMINATION');
			$email = $foundContact->valueOf('INFO/EMAIL1');
			echo $cID.' - '.$fistname.' - '.$lastname.' - '.$denomination.' - '.$email;
		}
	} else {
		echo ' =====> NOT';
	}
	echo '</p>';
}

?>