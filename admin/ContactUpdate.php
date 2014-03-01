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
$înfo_data_to_ignore = ",cache,now,ID,";

$info = "";
foreach ($_GET as $key => $value) {
	if ( !(strpos($înfo_data_to_ignore, $key) > -1) ) {
		$info .= "<".$key.">".encode_to_xml(stripcslashes($value))."</".$key.">";
	}
}

$NQL = new NQL(false);
$NQL->includeSuppParams(false);
$NQL->addCommand('
<UPDATE>
		<CONTACT ID="'.$_GET['ID'].'">
			<INFO>
				'.$info.'
			</INFO>
		</CONTACT>
	</UPDATE>
');
echo $NQL->valueOf('/RESPONSE/MESSAGE/@errorCode');
?>