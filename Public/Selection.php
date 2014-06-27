<?php
include_once("common.php");
// récupérations des catégories concernées par la sélection
$preNQL = new NQL(false);
$preNQL->addCommand('
	<GET><MEDIA ID="'.$_GET['ID'].'"/><RETURN depth="1"><CATEGORIES/></RETURN></GET>
');
$preNQL->execute();
$categories_node = $preNQL->getElements('/RESPONSE/RESULTS/MEDIA/CATEGORIES/CATEGORY');
$categories = "";
foreach ($categories_node as $category) {
	$categories .= '<CATEGORY and_group="statut" ID="'.$category->getAttribute('ID').'"/>';
}
//
if ($categories != "") {
	$selection_queries = '
		<SEARCH name="data">
			<MEDIA mediatype="Book">
				<CATEGORIES>'.$categories.'</CATEGORIES>
			</MEDIA>
			'.$return_book.'
		</SEARCH>';
} else {
	$selection_queries = '
		<GETCHILDREN name="data" type="mediaContent">
			<MEDIA ID="'.$_GET['ID'].'"/>
			'.$return_book.'
		</GETCHILDREN>
	';
	
}
//
$NQL = new NQL();
if ( $_GET['fromos'] == 'true') {
	$NQL->includeUnpublished( true );
}
$NQL->addCommand(get_media($_GET['ID']), 'media','1');
$NQL->addCommand($selection_queries);
echo $NQL->transform("Selection.xsl");
?>