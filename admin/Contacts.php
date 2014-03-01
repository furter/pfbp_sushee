<?php
include_once("common.php");

$NQL = new NQL();

$NQL->addCommand($categories_contact);

if ($_GET['search']) {
	$NQL->addCommand('
		<SEARCH name="data" cache="daily">
			<CONTACT>
				<INFO>'.encode_to_xml(stripcslashes($_GET['search'])).'</INFO>
			</CONTACT>
			'.$return_contact.'
			<PAGINATE display="5000" page="1"/>
			<SORT select="'.stripcslashes($_GET['sort']).'" order="'.$_GET['order'].'" data-type="'.$_GET['type'].'"/>
		</SEARCH>
	');
} else if ($_GET['browse'] == 1) {
	// récupération des dépendances pour les labels
	$NQL->addCommand($dependencies_contact);
	//
	$var_contact_categories = ",professionel,";
	$var_contact_info = ",COUNTRYID,";
	$var_dependencies = "";
	$var_not_use = ",ID,BrowseContacts,cache,language,page,sort,now,";
	//
	$contact_info = "";
	$contact_categories = "";
	$contact_dep = "";
	///////
	foreach ($_GET as $key => $value) {
		if ($value != "") {
			$to_find = ",".$key.",";
			//
			if (strpos($var_contact_categories, $to_find) > -1) {
				$contact_categories .= '<CATEGORY and_group="categories"  path="'.$value.'"/>';
			} else if (strpos($var_dependencies, $to_find) > -1) {
				$contact_dep = '<DEPENDENCY type=""><MEDIA ID=""/></DEPENDENCY>';
			} else if (strpos($var_contact_info, $to_find) > -1) {
				$contact_info .= "<".$key." operator='='>".$value."</".$key.">";
			}
		}
	}


	$NQL->addCommand(
		'<SEARCH name="data" cache="daily">
			<CONTACT>
				<INFO>'.$contact_info.'</INFO>
				<CATEGORIES>'.$contact_categories.'</CATEGORIES>
			</CONTACT>
			'.$return_contact.'
			<PAGINATE display="5000" page="1"/>
			<SORT select="'.stripcslashes($_GET['sort']).'" order="'.$_GET['order'].'" data-type="'.$_GET['type'].'"/>
		</SEARCH>');
}

$NQL->addCommand($display_contact);

$NQL->addCommand('
	<CONFIG static="true">
		<FORM name="BrowseContacts">
			<SELECT type="CATEGORY" path="professionel"/>
			<SELECT type="COUNTRY" path="COUNTRYID"/>
		</FORM>
	</CONFIG>
');

$NQL->addCommand('
	<GET name="media">
		<MEDIA ID="'.$_GET['ID'].'"/>
		<RETURN>
			<INFO>
				<MEDIATYPE/>
				<PAGETOCALL/>
			</INFO>
			<DESCRIPTIONS/>
		</RETURN>
	</GET>
');


echo $NQL->transform("Contacts.xsl");
?>