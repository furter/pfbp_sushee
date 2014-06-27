<?php
include_once("common.php");

$book_basic_status = '
	<CATEGORY or_group="statut" ID="22"/>
	<CATEGORY or_group="statut" ID="23"/>
	<CATEGORY or_group="statut" ID="57"/>
';

$preNQL = new NQL( false );
$preNQL->addCommand('<SEARCH><CATEGORY fatherID="37"/><RETURN totalElements="true"/></SEARCH>');
$preNQL->execute();
$cats = $preNQL->getElements('/RESPONSE/RESULTS/CATEGORY');
$year_categories = '';
foreach ($cats as $cat) {
	if ($cat->valueOf('@totalElements') > 0) {
		$year_categories .= '<CATEGORY or_group="year" ID="'.$cat->valueOf('@ID').'"/>';
	}
}
//echo '<!--'.$year_categories.'-->';

//$year_categories = '<CATEGORY or_group="year" ID="38"/><CATEGORY or_group="year" ID="39"/><CATEGORY or_group="year" ID="40"/>';
// 2010 -> <CATEGORY or_group="statut" ID="40"/>

$var_media_categories = ",concours,book_theme,";
$var_media_info = "";
$var_media_description = "";
$var_media_description_custom = ",YEAR,NUMBER,";
$var_dependencies = "";
$var_not_use = ",ID,BrowseBooks,cache,language,page,sort,now,";

$NQL = new NQL();

if (isset($_GET['sort']) and $_GET['sort'] != '') {
	$sort = stripcslashes($_GET['sort']);
	if (strpos($var_media_description_custom, $_GET['sort']) > 0) {
		$sort = "CUSTOM/".$sort;
	}
	$sort_node = '<SORT select="DESCRIPTIONS/DESCRIPTION/'.$sort.'" order="'.$_GET['order'].'" data-type="'.$_GET['type'].'"/>';
}

if ($_GET['browse'] == 1) {
	// récupération des dépendances pour les labels
	//$NQL->addCommand($dependencies_contact);
	//
	$media_info = "";
	$media_categories = "";
	$media_dep = "";
	$media_description = "";
	$media_description_custom = "";
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
	if ($media_categories == "") {
		$media_categories = $book_basic_status;
	}
	$NQL->addCommand(
		'<SEARCH name="data" cache="daily">
			<MEDIA mediatype="Book">
				<INFO>'.$media_info.'</INFO>
				<DESCRIPTIONS>
					<DESCRIPTION>'.$media_description.'</DESCRIPTION>
				</DESCRIPTIONS>
				<CATEGORIES>'.$media_categories.'</CATEGORIES>
			</MEDIA>
			<PAGINATE display="500" page="1"/>
			'.$return_book.$sort_node.'
		</SEARCH>');
} else {
	$NQL->addCommand(
		'<SEARCH name="data" cache="daily">
			<MEDIA mediatype="Book">								
				<CATEGORIES>'.$book_basic_status.$year_categories.'</CATEGORIES>
			</MEDIA>
			<RETURN depth="2">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTIONS/>
				<DEPENDENCIES>
					<DEPENDENCY type="BookToGraphist">
						<INFO>
							<CONTACTTYPE/>
							<FIRSTNAME/>
							<LASTNAME/>
							<DENOMINATION/>
						</INFO>						
					</DEPENDENCY>
					<DEPENDENCY type="BookToEditor">
						<INFO>
							<CONTACTTYPE/>
							<FIRSTNAME/>
							<LASTNAME/>
							<DENOMINATION/>
						</INFO>						
					</DEPENDENCY>
					<DEPENDENCY type="BookToPrinter">
						<INFO>
							<CONTACTTYPE/>
							<FIRSTNAME/>
							<LASTNAME/>
							<DENOMINATION/>
						</INFO>
					</DEPENDENCY>
				</DEPENDENCIES>
				<CATEGORIES/>					
			</RETURN>
			<PAGINATE display="500" page="1"/>
		</SEARCH>');
}
//les champs vers les contacts pour la recherche
$search_contact = array("EditorToBook", "GraphistToBook", "PrinterToBook");
$search_contact_query = "";
$search_contact_config = "";
foreach ($search_contact as $value) {
	$search_contact_query .= '
		<SEARCH name="'.$value.'" cache="daily">
			<CONTACT>
				<DEPENDENCIES>
					<DEPENDENCY type="'.$value.'" operator="exists">
						<MEDIA mediatype="Book">
							<CATEGORIES>'.$book_basic_status.$year_categories.'</CATEGORIES>
						</MEDIA>
					</DEPENDENCY>
				</DEPENDENCIES>
			</CONTACT>
			<RETURN>
				<INFO><CONTACTTYPE/><FIRSTNAME/><LASTNAME/><DENOMINATION/></INFO>
			</RETURN>
			<SORT select="INFO/DENOMINATION" order="ascending" />
		</SEARCH>';
	$search_contact_config .= '<SELECT type="DEPENDENCY" dep_type="'.$value.'"/>';
}
$NQL->addCommand($search_contact_query);
$NQL->addCommand('
	<CONFIG static="true">
		<FORM name="BrowseBooks">
			<!--SELECT type="ENUM" path="YEAR" start="2008" end="'.$last_year_prize.'"/-->
			<SELECT type="CATEGORY" path="prize_years"/>
			<SELECT type="CATEGORY" path="concours"/>
			<SELECT type="CATEGORY" path="book_theme"/>
			'.$search_contact_config.'
		</FORM>
	</CONFIG>
');


$NQL->addCommand('
	<GET name="media" cache="daily">
		<MEDIA ID="'.$_GET['ID'].'"/>
		<RETURN>
			<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
			<DESCRIPTIONS/>
		</RETURN>
	</GET>
	');
$NQL->execute();
echo $NQL->transform("Books.xsl");
?>