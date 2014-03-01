<?php
include_once("../sushee/common/common_functions.inc.php");

$resize = 'width="'.$_GET['width'].'"';
if (isset($_GET['height']) && $_GET['height'] != '') {
	$resize = 'height="'.$_GET['height'].'"';
}
$format = '.jpg';
if (strpos($_GET['path'], '.png') > 0) {
	$format = '.png';
}
imageTransform(
	'<IMAGE path="'.$_GET['path'].'">
		<resize '.$resize.'/>
		<convert format="'.$format.'" compression="87"/>
	</IMAGE>');

?>