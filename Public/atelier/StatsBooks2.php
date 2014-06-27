<?php
include_once("../../Kernel/common/common_functions.inc.php");
include_once('../../Kernel/common/nql.class.php');
///
$_GET['year'] = '53';
// $fonctions = array('graphistes' => 'BookToGraphist', 'editeurs' => 'BookToEditor', 'imprimeur' => 'BookToPrinter');
// $fonctionsInverses = array('graphistes' => 'GraphistToBook', 'editeurs' => 'EditorToBook', 'imprimeur' => 'PrinterToBook');
// $queries = '';
// ///
// $hainaut = array('6000/6599', '7000/7999');
// $wallonie = array('bw' => '1300/1499', 'liege' => '4000/4999', 'namur' => '5000/5999', 'hainaut' => $hainaut, 'lux' => '6600/6999');
// $bf = array('1500/1999', '3000/3499');
// $flandres = array('bf' => $bf, 'anvers' => '2000/2999', 'limbourg' => '3500/3999', 'westvla' => '8000/8999', 'oostvla' => '9000/9999');
// $belgium = array('bruxelles' => '1000/1299', 'wallonie' => $wallonie, 'flandres' => $flandres);
///
///
$nql = new NQL( false );
$nql->addCommand(
	'<SEARCH refresh="daily">
		<MEDIA mediatype="Book">
			<CATEGORIES>
				<CATEGORY ID="'.$_GET['year'].'"/>
			</CATEGORIES>
		</MEDIA>
		<RETURN>
			<DESCRIPTION><TITLE/></DESCRIPTION>
			<CATEGORIES/>
			<DEPENDENCIES>
				<INFO><DENOMINATION/><POSTALCODE/><FIRSTNAME/><LASTNAME/></INFO>
			</DEPENDENCIES>
		</RETURN>
	</SEARCH>');
$nql->execute();
$nql->xml_out();
