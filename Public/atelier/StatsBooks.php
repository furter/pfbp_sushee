<?php
include_once("../../Kernel/common/common_functions.inc.php");
include_once('../../Kernel/common/nql.class.php');
///
$fonctions = array('graphistes' => 'BookToGraphist', 'editeurs' => 'BookToEditor', 'imprimeur' => 'BookToPrinter');
$fonctionsInverses = array('graphistes' => 'GraphistToBook', 'editeurs' => 'EditorToBook', 'imprimeur' => 'PrinterToBook');
$queries = '';
///
$hainaut = array('6000/6599', '7000/7999');
$wallonie = array('bw' => '1300/1499', 'liege' => '4000/4999', 'namur' => '5000/5999', 'hainaut' => $hainaut, 'lux' => '6600/6999');
$bf = array('1500/1999', '3000/3499');
$flandres = array('bf' => $bf, 'anvers' => '2000/2999', 'limbourg' => '3500/3999', 'westvla' => '8000/8999', 'oostvla' => '9000/9999');
$belgium = array('bruxelles' => '1000/1299', 'wallonie' => $wallonie, 'flandres' => $flandres, 'etranger' => 'etranger');
///
///
function countParticipants($codes = '', $fonction = '', $region = '')
{
	global $fonctions, $fonctionsInverses, $belgium, $queries;
	$q = '';
	
	$info = '<COUNTRYID>bel</COUNTRYID><POSTALCODE operator="between">'.$codes.'</POSTALCODE>';
	if ($codes == 'etranger') {
		$info = '<COUNTRYID operator="!=">bel</COUNTRYID>';
	}
	$cats = '<CATEGORIES><CATEGORY ID="'.$_GET['year'].'" /></CATEGORIES>';
	
	if ($codes != '' && $fonction != '') {
		$q = '
			<COUNT objet="contact" name="'.$fonction.'" region="'.$region.'" code="'.$codes.'" refresh="daily">
				<CONTACT>
					<INFO>'.$info.'</INFO>
					<DEPENDENCIES>
						<DEPENDENCY type="'.$fonctionsInverses[ $fonction ].'">
							<MEDIA mediatype="Book">
								'.$cats.'
							</MEDIA>
						</DEPENDENCY>
					</DEPENDENCIES>
				</CONTACT>
			</COUNT>
			<COUNT objet="livre" name="'.$fonction.'" region="'.$region.'" code="'.$codes.'">
				<MEDIA mediatype="Book">
					<DEPENDENCIES>
						<DEPENDENCY type="'.$fonctions[ $fonction ].'">
							<CONTACT>
								<INFO>'.$info.'</INFO>											
							</CONTACT>
						</DEPENDENCY>
					</DEPENDENCIES>
					'.$cats.'
				</MEDIA>
			</COUNT>';
	}
	$queries .= $q;
}
function countEachParticipants($codes='', $region='')
{
	global $fonctions;
	foreach ($fonctions as $key => $value) {
		countParticipants($codes=$codes, $fonction=$key, $region=$region);
	}
}
///
///
$nql = new NQL( false );
$nql->addCommand('<GET><CATEGORIES ID="37"/><RETURN depth="all"/></GET>');
if ( isset( $_GET['year'] ) && $_GET['year'] != '' ) {
	foreach ($belgium as $region => $r_code) {
		if ( is_array( $r_code ) ) {
			foreach ($r_code as $province => $p_code) {
				if ( is_array( $p_code ) ) {
					foreach ($p_code as $code) {
						countEachParticipants( $code, $region );
					}
				} else {
					countEachParticipants( $p_code, $region );
				}
			}
		} else {
			countEachParticipants( $r_code, $region );
		}
	}
	$nql->addCommand( $queries );
}
$nql->execute();
/////////
echo '<form method="get">
<div class="select_container">
	<label>Année du prix</label>
	<select name="year" onchange="alert()" id="year">
		<option value="">Sélectionnez une année</option>';
$prizecats = $nql->getElements('/RESPONSE/RESULTS/CATEGORY/CATEGORY');
foreach ($prizecats as $cat) {
	$cID = $cat->valueOf('@ID');
	echo '<option value="'.$cID.'" ';
	if ( $cID == $_GET['year']) {
		echo 'selected="selected"';
	}
	echo '>'.$cat->valueOf('LABEL').'</option>';
}
echo '</select><input type="submit" value="->"/></div>';
////
if ( isset( $_GET['year'] ) && $_GET['year'] != '' ) {
	echo '<h1>Nombre de participants par région et par fonction</h1>';
	echo '<table><tr><th>Province</th><th>Graphiste</th><th>Editeurs</th><th>Imprimeurs</th></tr>';
	foreach ($belgium as $region => $value) {
		echo '<tr><td>'.$region.'</td>';
		foreach ($fonctions as $fonction => $value) {
			$elements = $nql->getElements('/RESPONSE/RESULTS[@objet="contact" and @region="'.$region.'" and @name="'.$fonction.'"]');
			$count = 0;
			foreach ($elements as $e) {
				$count += $e->valueOf('@hits');
			}
			echo '<td>'.$count.'</td>';
		}
		echo '</tr>';
	}
	echo '</table>';
	///
	echo '<h1>Nombre de livre ayant un participant par région et par fonction</h1>';
	echo '<table><tr><th>Province</th><th>Graphiste</th><th>Editeurs</th><th>Imprimeurs</th></tr>';
	foreach ($belgium as $region => $value) {
		echo '<tr><td>'.$region.'</td>';
		foreach ($fonctions as $fonction => $value) {
			$elements = $nql->getElements('/RESPONSE/RESULTS[@objet="livre" and @region="'.$region.'" and @name="'.$fonction.'"]');
			$count = 0;
			foreach ($elements as $e) {
				$count += $e->valueOf('@hits');
			}
			echo '<td>'.$count.'</td>';
		}
		echo '</tr>';
	}
	echo '</table>';

}
