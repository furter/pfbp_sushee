<?php
include_once("common.php");

$NQL = new NQL();

$NQL->addCommand('<GET><CATEGORIES path="/contact/contact_details"/><RETURN depth="all"/></GET>');
// to put in navigation
$NQL->addCommand('<GET><COUNTRIES/></GET>');
$NQL->addCommand('<GET><LABELS/></GET>');

if ($_GET['BrowseContacts'] == 1) {
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
				$contact_categories .= '<CATEGORY and_group="categories"  ID="'.$value.'"/>';
			} else if (strpos($var_dependencies, $to_find) > -1) {
				$contact_dep = '<DEPENDENCY type="DancerToMusic"><MEDIA ID="'.$_GET['d_music'].'"/></DEPENDENCY>';
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
			<RETURN depth="2">
				<INFO/>
				<DEPENDENCIES>
					<DEPENDENCY type="EditorToBook"/>
					<DEPENDENCY type="GraphistToBook"/>
					<DEPENDENCY type="PhotographToBook"/>
					<DEPENDENCY type="IllustratorToBook"/>
					<DEPENDENCY type="BinderToBook"/>
					<DEPENDENCY type="PrinterToBook"/>
					<DEPENDENCY type="PeopleContactToBook">
						<INFO><MEDIATYPE/></INFO>
						<DESCRIPTION><TITLE/></DESCRIPTION>
					</DEPENDENCY>
				</DEPENDENCIES>
				<CATEGORIES/>
			</RETURN>
			<!--SORT select="'.stripcslashes($_GET['sort']).'" order="'.$_GET['order'].'" data-type="'.$_GET['type'].'"/-->
		</SEARCH>');
}

	
$NQL->addCommand('
	<DISPLAY static="true" module="CONTACT">
		<DATA service="INFO" path="LASTNAME"/>
		<DATA service="INFO" path="FIRSTNAME"/>
		<DATA service="INFO" path="DENOMINATION"/>
		<DATA service="CATEGORY" path="professionel"/>
		<GROUP class="contact">
			<DATA service="INFO" path="EMAIL1"/>			
			<DATA service="INFO" path="MOBILE"/>
			<DATA service="INFO" path="PHONE1"/>
			<DATA service="INFO" path="ADDRESS"/>
			<DATA service="INFO" path="POSTALCODE"/>
			<DATA service="INFO" path="CITY"/>
			<DATA service="INFO" path="COUNTRY"/>
		</GROUP>
		<GROUP class="books">
			<DATA service="DEPENDENCY" type="EditorToBook"/>
			<DATA service="DEPENDENCY" type="GraphistToBook"/>
			<DATA service="DEPENDENCY" type="PhotographToBook"/>
			<DATA service="DEPENDENCY" type="IllustratorToBook"/>
			<DATA service="DEPENDENCY" type="BinderToBook"/>
			<DATA service="DEPENDENCY" type="PrinterToBook"/>
			<DATA service="DEPENDENCY" type="PeopleContactToBook"/>
		</GROUP>
	</DISPLAY>
');


$NQL->addCommand('
	<CONFIG static="true">
		<FORM name="BrowseContacts">
			<SELECT type="CATEGORY" path="professionel"/>
			<SELECT type="COUNTRY" path="COUNTRYID"/>
		</FORM>
	</CONFIG>
');

echo $NQL->transform("Contacts.xsl");
?>