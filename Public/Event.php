<?php
include_once("../Kernel/common/nql.class.php");

$depth = 2;
$ID = $_GET['ID'];

if(isset($_GET['depth']))
	$depth = $_GET['depth'];

$NQL = new NQL();

$NQL->addCommand(
	'<GET name="media">
		<MEDIA ID="'.$ID.'"/>
		<RETURN depth="'.$depth.'">
			<INFO creator-info="true" />
			<DESCRIPTIONS/>
		</RETURN>
	</GET>');

$NQL->execute();

echo $NQL->transform("Event.xsl");
?>