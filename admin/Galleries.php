<?php
include_once("common.php");

$NQL = new NQL();
$NQL->addCommand(
	'<GET name="media" refresh="daily">
		<MEDIA ID="'.$_GET['ID'].'"/>
		<RETURN depth="2">
			<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
			<DESCRIPTIONS/>
			<DEPENDENCIES>
				<DEPENDENCY type="mediaContent">
					<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
					<DESCRIPTIONS/>
				</DEPENDENCY>
			</DEPENDENCIES>
		</RETURN>
	</GET>');

echo $NQL->transform("Galleries.xsl");
?>