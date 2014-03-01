<?php
include_once("common.php");
///
$GLOBALS['TranformationMaxWidth'] = 10000;
$GLOBALS['TranformationMaxHeight'] = 10000;
$GLOBALS['TranformationMaxSize'] = 10000;
///
$NQL = new NQL();
$NQL->addCommand('
<GET name="media">
	<MEDIA ID="'.$_GET['ID'].'"/>
	<RETURN depth="3">
		<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
		<DESCRIPTIONS/>
		<DEPENDENCIES>
			<DEPENDENCY type="mediaContent">
				<INFO><MEDIATYPE/><PAGETOCALL/><CREATIONDATE/></INFO>
				<DESCRIPTIONS/>
				<DEPENDENCIES>
					<DEPENDENCY type="mediaContent">
						<INFO><MEDIATYPE/></INFO>
						<DESCRIPTIONS/>
					</DEPENDENCY>
				</DEPENDENCIES>
			</DEPENDENCY>
		</DEPENDENCIES>
	</RETURN>
</GET>');
echo $NQL->transform("Press.xsl");
?>