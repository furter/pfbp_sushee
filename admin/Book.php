<?php
include_once("common.php");

$NQL = new NQL(false);
$NQL->addCommand($display_book);
$NQL->addCommand($dependencies_media);
$NQL->addCommand($categories_media);
$NQL->addCommand($countries);
$NQL->addCommand($labels);

$NQL->addCommand('
	<GET name="media">
		<MEDIA ID="'.$_GET['ID'].'"/>
		'.$return_book.'
	</GET>
');


echo $NQL->transform("Book.xsl");
?>