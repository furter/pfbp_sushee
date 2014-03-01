<?php
include_once("common.php");

$NQL = new NQL(false);
$NQL->addCommand($display_contact);
$NQL->addCommand($dependencies_contact);
$NQL->addCommand($countries);
$NQL->addCommand($labels);

$NQL->addCommand('
	<GET name="contact">
		<CONTACT ID="'.$_GET['ID'].'"/>
		'.$return_contact.'
	</GET>
');


echo $NQL->transform("Contact.xsl");
?>