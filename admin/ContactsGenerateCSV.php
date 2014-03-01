<?php
include_once("common.php");

//professionel=2&COUNTRYID=bel&return[]=LASTNAME&return[]=FIRSTNAME&return[]=DENOMINATION&return[]=professionel

$var_categories = ",professionel,";
$var_info = ",COUNTRYID,LASTNAME,FIRSTNAME,DENOMINATION,EMAIL1,MOBILE,PHONE1,ADDRESS,POSTALCODE,CITY,COUNTRYID";
$var_dependencies = ",EditorToBook,GraphistToBook,PhotographToBook,IllustratorToBook,BinderToBook,PrinterToBook,";

$search_info = "";
$search_categories = "";
foreach ($_GET as $key => $value) {
	if ($key != 'return') {
		if ( strpos($var_categories,$key) > 0 ) {
			$search_categories .= '<CATEGORY path="'.$value.'"/>';
		} else if ( strpos($var_info,$key) > 0 ) {
			$search_info .= '<'.$key.'>'.$value.'</'.$key.'>';
		}
	}
}

$depth=1;
$return_info = "";
$return_categories = "";
$return_dependencies = "";
foreach ($_GET['return'] as $value) {
	if ( strpos($var_categories,$value) > 0 ) {
		$return_categories = '<CATEGORY uniquename="'.$value.'"/>';
	} else if ( strpos($var_dependencies,$value) > 0 ) {
		$return_dependencies .= '<DEPENDENCY type="'.$value.'"><DESCRIPTION><TITLE/></DESCRIPTION></DEPENDENCY>';
	} else if ( strpos($var_info,$value) > 0 ) {
		$return_info .= '<'.$value.'/>';
	}

}
$return_info_ok = '<INFO get="false"/>';
if ($return_info != '') {
	$return_info_ok = '<INFO>'.$return_info.'</INFO>';
}
$return_categories_ok = '';
if ($return_categories != '') {
	$return_categories_ok = '<CATEGORIES>'.$return_categories.'</CATEGORIES>';
}
$return_dependencies_ok = '';
if ($return_dependencies != '') {
	$depth="2";
	$return_dependencies_ok = '<DEPENDENCIES>'.$return_dependencies.'</DEPENDENCIES>';
}
$return_content = $return_info_ok.$return_categories_ok.$return_dependencies_ok;
$query = '
	<SEARCH name="data">
		<CONTACT>
			<INFO>'.$search_info.'</INFO>
			<CATEGORIES>'.$search_categories.'</CATEGORIES>
		</CONTACT>
		<RETURN depth="'.$depth.'">'.$return_content.'</RETURN>
	</SEARCH>
	<RETURN static="true">'.$return_content.'</RETURN>
	'.$dependencies_contact.$display_contact.$categories_contact;


$NQL = new NQL(false);

if ($_GET['debug'] == 1) {
	$NQL->addCommand($query);
	$NQL->execute();
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