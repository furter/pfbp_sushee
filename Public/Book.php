<?php
include_once("common.php");
$NQL = new NQL();
$NQL->addCommand('
	<GET name="media">
		<MEDIA ID="'.$_GET['ID'].'"/>
		'.$return_book.'
	</GET>
');
$NQL->addCommand('
	<DISPLAY static="true" module="MEDIA" mediatype="Book" mode="book_basic_info" tag="ul" childtag="li">
		<GROUP class="PFBproperties" tag="ul" childtag="li">
			<DATA service="CATEGORY" path="concours"/>
			<DATA service="DESCRIPTION" path="YEAR"/>
		</GROUP>
		<DATA service="DEPENDENCY" type="BookToGraphist"/>
		<DATA service="DEPENDENCY" type="BookToEditor"/>
		<DATA service="DEPENDENCY" type="BookToPrinter"/>
		<DATA service="CATEGORY" path="book_theme"/>
	</DISPLAY>');
$NQL->addCommand('
	<GET><LIST name="book_inside_navigation"/></GET>
');
$NQL->addCommand($display_book_technique_file);
echo $NQL->transform("Book.xsl");
?>