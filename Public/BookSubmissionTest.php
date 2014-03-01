<?php
include_once("common.php");
// if user come from mail
$userQuery = "";
if (isset( $_GET['uID'] ) && $_GET['uID'] != "") {
	$_GET['uID'] = substr($_GET['uID'], -4);
	$userQuery = '<GET name="user"><CONTACT ID="'.$_GET['uID'].'"/><RETURN><INFO/></RETURN></GET>';
}
//
$NQL = new NQL();
//$NQL->addCommand(get_media($_GET['ID']), 'media','3');
$NQL->addCommand('
<GET name="media">
	<MEDIA ID="'.$_GET['ID'].'"/>
	<RETURN depth="3">
		<INFO><MEDIATYPE/><PAGETOCALL/><TEMPLATE/></INFO>
		<DESCRIPTIONS/>
		<DEPENDENCIES>
			<DEPENDENCY type="mediaContent">
				<INFO><MEDIATYPE/><PAGETOCALL/><TEMPLATE/></INFO>
				<DESCRIPTIONS/>
				<DEPENDENCIES>
					<DEPENDENCY type="mediaContent">
						<INFO><MEDIATYPE/></INFO>
					</DEPENDENCY>
				</DEPENDENCIES>				
			</DEPENDENCY>
		</DEPENDENCIES>
	</RETURN>
</GET>');
$NQL->addCommand('<GET><LIST name="contributorType"/></GET>');
$NQL->addCommand('<GET name="book_theme"><CATEGORIES ID="26" /><RETURN depth="2" /></GET>');
$NQL->addCommand($userQuery);
//
echo $NQL->transform("BookSubmissionTest.xsl");
?>