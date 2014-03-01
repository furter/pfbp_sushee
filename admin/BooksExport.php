<?php
include_once("common.php");

$NQL = new NQL();

if (!isset($_GET['YEAR']) || $_GET['YEAR'] == "") {
	$_GET['YEAR'] = $last_year_prize;
}

$NQL->addCommand(
	'<SEARCH name="data">
		<MEDIA mediatype="Book">
			<DESCRIPTIONS>
				<DESCRIPTION><CUSTOM><YEAR>'.$_GET['YEAR'].'</YEAR></CUSTOM></DESCRIPTION>
			</DESCRIPTIONS>
			<CATEGORIES>
				<CATEGORY ID="23"/>
			</CATEGORIES>
		</MEDIA>
		<RETURN depth="2">
			<INFO/>
		    <DESCRIPTIONS>
		      	<DESCRIPTION languageID="all"/>
		    </DESCRIPTIONS>
			<DEPENDENCIES/>
		</RETURN>
		<SORT select="DESCRIPTIONS/DESCRIPTION/TITLE" order="ascending" data-type="text"/>
	</SEARCH>');

$NQL->addCommand('
	<GET name="mediatype">
		<MEDIATYPES>
			<UNIQUENAME>Book</UNIQUENAME>
		</MEDIATYPES>
	</GET>');

$NQL->addCommand($dependencies_media);
$NQL->addCommand($categories_media);
$NQL->addCommand($display_book_export);

$NQL->addCommand('
	<GET name="label_fre"><LABELS languageID="fre"/></GET>
	<GET name="label_dut"><LABELS languageID="dut"/></GET>
	<GET name="label_eng"><LABELS languageID="eng"/></GET>
');

$NQL->addCommand('
	<CONFIG static="true">
		<FORM name="BrowseBooks">
			<SELECT type="ENUM" path="YEAR" start="2008" end="'.$last_year_prize.'"/>
		</FORM>
	</CONFIG>
');

$NQL->addCommand('
	<GET name="media">
		<MEDIA ID="'.$_GET['ID'].'"/>
		<RETURN>
			<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
			<DESCRIPTIONS/>
		</RETURN>
	</GET>
	');
if ($_GET['prepare'] == 1 ) {
	echo $NQL->transform("BooksExportPrepare.xsl");
} else {
	echo $NQL->transform("BooksExport.xsl");
}
?>