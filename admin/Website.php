<?php
include_once("common.php");
if (!isset($_GET['ID']) or $_GET['ID'] == "") {
	$_GET['ID'] = $websiteID;
}

$NQL = new NQL();
$NQL->addCommand('
	<GET name="media" refresh="daily">
		<MEDIA ID="'.$_GET['ID'].'"/>
		<RETURN depth="2">
			<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
			<DESCRIPTIONS/>
			<DEPENDENCIES>
				<DEPENDENCY type="mediaContent">
					<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
					<DESCRIPTION/>
				</DEPENDENCY>
			</DEPENDENCIES>
		</RETURN>
	</GET>');
echo $NQL->transform("Website.xsl");
?>