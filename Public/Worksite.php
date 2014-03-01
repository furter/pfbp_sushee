<?php
include_once("common.php");
if (!isset($_GET['ID']) or $_GET['ID'] == "") {
	$_GET['ID'] = $websiteID;
}

$NQL = new NQL();
$NQL->addCommand(get_media($_GET['ID']));
$NQL->addCommand('
	<SEARCH name="data">
		<MEDIA mediatype="Book">
			<DESCRIPTIONS>
				<DESCRIPTION>
					<CUSTOM>
						<VISUAL operator="!="></VISUAL>
					</CUSTOM>
				</DESCRIPTION>
			</DESCRIPTIONS>
			<CATEGORIES>
				<CATEGORY or_group="statut" name="laureat"/>
				<CATEGORY or_group="statut" name="nomine"/>
			</CATEGORIES>
		</MEDIA>
		'.$return_book.'
		<RANDOM display="1"/>		
	</SEARCH>
');
//$NQL->xml_out();
echo $NQL->transform("Worksite.xsl");
?>