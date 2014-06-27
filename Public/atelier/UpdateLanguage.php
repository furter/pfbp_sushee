<?php
include_once("../../Kernel/common/common_functions.inc.php");
include_once('../../Kernel/common/nql.class.php');

$nql = new NQL(false);
$nql->addCommand('
	<SEARCH>
		<LOG>
			<INFO>
				<DATE operator="GT">2012-01-21</DATE>
				<MODULE>contact</MODULE>
				<OPERATION>modify</OPERATION>
				<SERVICE>INFO</SERVICE>
				<FIELD>LanguageID</FIELD>
			</INFO>
		</LOG>
		<RETURN>
			<INFO></INFO>
		</RETURN>
		<SORT select="INFO/DATE" order="ascending"/>
		<PAGINATE display="1000" page="1"/>
	</SEARCH>');
$nql->execute();
////
$query = '';
$i = 1;
$logs = $nql->getElements('/RESPONSE/RESULTS/LOG');
foreach ($logs as $l) {
	$i++;
	$uID = $l->valueOf('INFO/ELEMENTID');
	$lang = $l->valueOf('INFO/VALUE');
	$query .= '<UPDATE><CONTACT ID="'.$uID.'"><INFO><LANGUAGEID>'.$lang.'</LANGUAGEID></INFO></CONTACT></UPDATE>';
}
echo '<h1>'.$i.'</h1>';
echo $query;

?>