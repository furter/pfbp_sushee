<?php
include_once("common.php");

$NQL = new NQL(false);

/*
ID=3983
LASTNAME=Hambije
FIRSTNAME=Dominique 
DENOMINATION=Dominique Hambije
EMAIL1=dominique.hambye@chello.be
MOBILEPHONE=
PHONE1=02/538.00.07
ADDRESS=Rue du Canada 69
POSTALCODE=1190
CITY=Bruxelles
COUNTRYID=bel
*/
$languages = array('fre', 'eng', 'dut');

$înfo_data_to_ignore = ",cache,now,ID,CREATIONDATE,";
$description_data = ",TITLE,BIBLIO,";
$description_custom_data = ",YEAR,NUMBER,NUMBER2,REFUS,";
$categories_data = ",concours,book_theme,";

$description = "";
$description_custom = "";
$categories = '';
foreach ($_GET as $key => $value) {
	if ( !(strpos($înfo_data_to_ignore, $key) > -1) ) {
		if (strpos($description_custom_data, $key) > 0) {
			$description_custom .= "<".$key.">".encode_to_xml(stripcslashes($value))."</".$key.">";
		} else if ( strpos($categories_data, $key) > 0 ) {
//			$categories .= '<CATEGORIES operation="remove"><CATEGORY path="/media/livre/'.$key.'/"/></CATEGORIES>';
			$categories .= '<CATEGORIES operation="append"><CATEGORY ID="'.$value.'"/></CATEGORIES>';
		} else {
			$description .= "<".$key.">".encode_to_xml(stripcslashes($value))."</".$key.">";
		}
	}
}
if ($description_custom != '') {
	$description_custom = "<CUSTOM>".$description_custom.'</CUSTOM>';
}
$description = $description.$description_custom;
//
$description_full = "";
foreach ($languages as $value) {
	$description_full .= "<DESCRIPTION><LANGUAGEID>".$value."</LANGUAGEID>".$description."</DESCRIPTION>";
}
//
$NQL = new NQL(false);
$NQL->includeSuppParams(false);
$NQL->addCommand('
	<UPDATE>
		<MEDIA ID="'.$_GET['ID'].'">
			<DESCRIPTIONS>
				'.$description_full.'
			</DESCRIPTIONS>
			<CATEGORIES operation="remove">
				<CATEGORY fatherID="21"/>
				<CATEGORY fatherID="26"/>
			</CATEGORIES>
			'.$categories.'
		</MEDIA>
	</UPDATE>
');
echo $NQL->valueOf('/RESPONSE/MESSAGE/@errorCode');
//echo $NQL->getQuery();
?>