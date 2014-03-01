<?php
include_once("../../Kernel/common/common_functions.inc.php");
include_once('../../Kernel/common/nql.class.php');

$nql = new NQL(false);
$nql->addCommand(
	'<SEARCH name="contacts" refresh="daily">
		<CONTACT>
		<!--
			<CATEGORIES>
				<CATEGORY ID="'.$_GET['cID'].'"/>
			</CATEGORIES>
		-->
		</CONTACT>
		<RETURN>
			<INFO>
				<CONTACTTYPE/>
				<DENOMINATION/>
				<FIRSTNAME/>
				<LASTNAME/>				
			</INFO>
		</RETURN>
		<PAGINATE display="5000" page="1"/>
	</SEARCH>');

$nql->execute();

$reponse = $nql->transformToText('SearchContacts_JSON.xsl');
echo $reponse;

?>