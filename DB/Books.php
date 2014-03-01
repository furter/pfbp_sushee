<?php
include_once("common.php");

$NQL = new NQL();
$NQL->addCommand(
	'<SEARCH name="data" cache="daily">
		<MEDIA mediatype="Book"/>
		<RETURN depth="2">
			<INFO><MEDIATYPE/></INFO>
			<DESCRIPTIONS/>
			<DEPENDENCIES>
				<INFO/>
			</DEPENDENCIES>
			<CATEGORIES/>
		</RETURN>
		<!--SORT select="'.stripcslashes($_GET['sort']).'" order="'.$_GET['order'].'" data-type="'.$_GET['type'].'"/-->
	</SEARCH>');
	
$NQL->addCommand('
	<DISPLAY static="true">
		<ELEMENT module="DESCRIPTION" node="TITLE"/>
		<GROUP class="PFBproperties">
			<ELEMENT module="DESCRIPTION" node="YEAR"/>
			<ELEMENT module="DESCRIPTION" node="NUMBER" data-type="number"/>
			<ELEMENT module="CATEGORY" fatherID="6"/>
			<ELEMENT module="CATEGORY" fatherID="21"/>
		</GROUP>
		<GROUP class="BOOKproperties"/>
		<GROUP class="people">
			<ELEMENT module="DEPENDENCY" type="BookToEditor"/>
			<ELEMENT module="DEPENDENCY" type="BookToGraphist"/>
			<ELEMENT module="DEPENDENCY" type="BookToPhotograph"/>
			<ELEMENT module="DEPENDENCY" type="BookToIllustrator"/>
			<ELEMENT module="DEPENDENCY" type="BookToBinder"/>
			<ELEMENT module="DEPENDENCY" type="BookToPrinter"/>
			<ELEMENT module="DEPENDENCY" type="BookToPeopleContact"/>
		</GROUP>
		<GROUPE class="inside"/>
		<GROUPE class="cover"/>
		<GROUPE class="questions"/>
	</DISPLAY>
');

$NQL->addCommand('
	<FORM static="true" name="search">
		<SELECT name="YEAR"/>
		<SELECT name="PRIZE"/>
	</FORM>
');

if ($_GET['version'] == 1) {
	echo $NQL->transform("Books.xsl");
} else {
	echo $NQL->transform("Books-ok-2010-01-10.xsl");
	}
?>