<?php
include_once("common.php");

if ( !isset( $_GET['page']) || $_GET['page'] == '') {
	$_GET['page'] = 1;
}


$var_media_categories = ",concours,book_theme,prize_years,";
$var_media_info = "";
$var_media_description = ",BIBLIO";
$var_media_description_custom = ",YEAR,NUMBER,";
$var_dependencies = "";
$var_not_use = ",ID,BrowseBooks,cache,language,page,sort,now,";

$NQL = new NQL();

if (isset($_GET['sort']) and $_GET['sort'] != '') {
	$sort = stripcslashes($_GET['sort']);
	if (strpos($var_media_description_custom, $_GET['sort']) > 0) {
		$sort = "CUSTOM/".$sort;
	}
	if ($_GET['sort'] == 'TITLE_length') {
		$sort = 'TITLE';
	}
	$sort_node = '<SORT select="DESCRIPTIONS/DESCRIPTION/'.$sort.'" order="'.$order.'" data-type="'.$_GET['type'].'"/>';
}

if ($_GET['search']) {
	$NQL->addCommand(
		'<SEARCH name="data" refresh="daily">
			<MEDIA mediatype="Book">
				<DESCRIPTIONS>
					<DESCRIPTION>'.encode_to_xml(stripcslashes($_GET['search'])).'</DESCRIPTION>
				</DESCRIPTIONS>
			</MEDIA>
			'.$return_book.$sort_node.'<PAGINATE display="20" page="'.$_GET['page'].'"/>
		</SEARCH>');
} else if ($_GET['browse'] == 1) {
	// récupération des dépendances pour les labels
	$NQL->addCommand($dependencies_contact);
	//
	$contact_info = "";
	$contact_categories = "";
	$contact_dep = "";
	///////
	foreach ($_GET as $key => $value) {
		if ($value != "") {
			$to_find = ",".$key.",";
			//
			if (strpos($var_media_categories, $to_find) > -1) {
				$media_categories .= '<CATEGORY and_group="categories"  path="'.$value.'"/>';
			} else if (strpos($var_dependencies, $to_find) > -1) {
				$media_dep = '<DEPENDENCY type=""><MEDIA ID=""/></DEPENDENCY>';
			} else if (strpos($var_media_info, $to_find) > -1) {
				$media_info .= "<".$key." operator='='>".$value."</".$key.">";
			} else if (strpos($var_media_description, $to_find) > -1) {
				$media_description .= "<".$key." operator='='>".$value."</".$key.">";
			} else if (strpos($var_media_description_custom, $to_find) > -1) {
				$media_description_custom .= "<".$key." operator='='>".$value."</".$key.">";
			}
			
		}
	}
	if ($media_description_custom != "") {
		$media_description .= '<CUSTOM>'.$media_description_custom."</CUSTOM>";
	}
	$NQL->addCommand(
		'<SEARCH name="data" refresh="daily">
			<MEDIA mediatype="Book">
				<INFO>'.$media_info.'</INFO>
				<DESCRIPTIONS>
					<DESCRIPTION>'.$media_description.'</DESCRIPTION>
				</DESCRIPTIONS>
				<CATEGORIES>'.$media_categories.'</CATEGORIES>
			</MEDIA>
			'.$return_book.$sort_node.'<PAGINATE display="20" page="'.$_GET['page'].'"/>
		</SEARCH>');
}	

$NQL->addCommand($display_book);
$NQL->addCommand($dependencies_media);
$NQL->addCommand($categories_media);

$NQL->addCommand('
	<CONFIG static="true">
		<FORM name="BrowseBooks">
			<SELECT type="ENUM" path="YEAR" start="2008" end="'.$last_year_prize.'"/>
			<SELECT type="CATEGORY" path="concours"/>
			<SELECT type="CATEGORY" path="prize_years"/>
			<SELECT type="CATEGORY" path="book_theme"/>
		</FORM>
	</CONFIG>
');

$NQL->addCommand('
	<GET name="media" refresh="monthly">
		<MEDIA ID="'.$_GET['ID'].'"/>
		<RETURN>
			<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
			<DESCRIPTIONS/>
		</RETURN>
	</GET>
	');

echo $NQL->transform("Books.xsl");
?>