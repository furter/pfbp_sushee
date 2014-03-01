<?php
include_once("common.php");
/*
$var_categories = ",professionel,";
$var_info = ",COUNTRYID,LASTNAME,FIRSTNAME,DENOMINATION,EMAIL1,MOBILE,PHONE1,ADDRESS,POSTALCODE,CITY,COUNTRYID";
$var_dependencies = ",EditorToBook,GraphistToBook,PhotographToBook,IllustratorToBook,BinderToBook,PrinterToBook,";
*/

$var_categories = ",concours,book_theme,prize_years,";
$var_info = "";
$var_description = ",TITLE,";
$var_description_custom = ",YEAR,NUMBER,NUMBER2,";
$var_dependencies = ",BookToEditor,BookToEditor,BookToGraphist,BookToPhotograph,BookToIllustrator,BookToAuthor,BookToBinder,BookToPrinter,BookToPeopleContact,";
$var_not_use = ",ID,BrowseBooks,cache,language,page,sort,now,";


$search_info = "";
$search_categories = "";
$search_descriptions = "";
$search_description_custom = "";
foreach ($_GET as $key => $value) {
	if ($key != 'return') {
		if ( strpos($var_categories,$key) > 0 ) {
			if ($value != '') {
				$search_categories .= '<CATEGORY and_group="book" path="'.substr($value, 0, -1).'"/>';
			}
		} else if ( strpos($var_info,$key) > 0 ) {
			$search_info .= '<'.$key.'>'.$value.'</'.$key.'>';
		} else if ( strpos($var_description,$key) > 0 ) {
			$search_descriptions .= '<'.$key.'>'.$value.'</'.$key.'>';
		} else if ( strpos($var_description_custom,$key) > 0 ) {
			$search_description_custom .= '<'.$key.'>'.$value.'</'.$key.'>';
		}
	}
}
//
if ($search_description_custom != '') {
	$search_description_custom = '<CUSTOM>'.$search_description_custom.'</CUSTOM>';
}
$search_descriptions = '<DESCRIPTIONS><DESCRIPTION>'.$search_descriptions.$search_description_custom.'</DESCRIPTION></DESCRIPTIONS>';
////
$depth=1;
$return_info = "";
$return_description = "";
$return_categories = "";
$return_description_custom = "";
$return_dependencies = "";
//
foreach ($_GET['return'] as $value) {
	if ($value != '') {
		if ( strpos($var_categories,$value) > 0 ) {
			if ($value != '') {
				$return_categories .= '<CATEGORY uniquename="'.$value.'"/>';
			}
		} else if ( strpos($var_dependencies,$value) > 0 ) {
			$return_dependencies .= '<DEPENDENCY type="'.$value.'"><INFO><FIRSTNAME/><LASTNAME/><DENOMINATION/><CONTACTTYPE/></INFO></DEPENDENCY>';
		} else if ( strpos($var_info,$value) > 0 ) {
			$return_info .= '<'.$value.'/>';
		} else if ( strpos($var_description,$value) > 0 ) {
			$return_description .= '<'.$value.'/>';
		} else if ( strpos($var_description_custom,$value) > 0 ) {
			$return_description_custom .= '<'.$value.'/>';
		}
	}	
}
$return_info_ok = '<INFO get="false"/>';
if ($return_info != '') {
	$return_info_ok = '<INFO>'.$return_info.'</INFO>';
}
$return_categories_ok = '';
if ($return_categories != '') {
	$return_categories_ok = '<CATEGORIES>'.$return_categories.'</CATEGORIES>';
//	$return_categories_ok = '<CATEGORIES/>';
}
$return_dependencies_ok = '';
if ($return_dependencies != '') {
	$depth="2";
	$return_dependencies_ok = '<DEPENDENCIES>'.$return_dependencies.'</DEPENDENCIES>';
}
$return_description_ok = '';
if ($return_description !='' or $return_description_custom != '') {
	$return_description_ok = '<DESCRIPTIONS><DESCRIPTION>'.$return_description.'<CUSTOM>'.$return_description_custom.'</CUSTOM></DESCRIPTION></DESCRIPTIONS>';
}


$return_content = $return_info_ok.$return_description_ok.$return_categories_ok.$return_dependencies_ok;
$query = '
	<SEARCH name="data">
		<MEDIA mediatype="Book">
			<INFO>'.$search_info.'</INFO>
			<CATEGORIES>'.$search_categories.'</CATEGORIES>
			'.$search_descriptions.'
		</MEDIA>
		<RETURN depth="'.$depth.'">'.$return_content.'</RETURN>
	</SEARCH>
	<RETURN static="true">'.$return_content.'</RETURN>
	'.$dependencies_media.$display_book.$categories_media;


$NQL = new NQL(false);

if ($_GET['debug'] == 1) {
	$NQL->addCommand($query);
	$NQL->execute();
	$NQL->xml_out();
	die();	
	echo $NQL->transform('GenerateCSV.xsl');
} else {
	$NQL->addCommand('
		<CREATE name="my-category">
			<CSV template="GenerateCSV.xsl">
				<QUERY>'.$query.'</QUERY>
			</CSV>
		</CREATE>
	');
	$NQL->execute();
	$csv_path = $NQL->valueOf("/RESPONSE/RESULTS[@name='my-category']/CSV");
	filedownload('../Files/'.$csv_path);
}
?>