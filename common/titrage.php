<?php
include_once("../Kernel/common/common_functions.inc.php");
$color = $_GET['color'];
$font = $_GET['font'];
$size = $_GET['size'];
$caps = $_GET['caps'];

if ($color=='') {
$color="000000";
}
if ($font=='' or $font == "bold") {
	$font="GothaHTFBol.ttf";
} else if ($font == "book") {
	$font="GothaHTFBoo.ttf";
} else {
	$font ="GothaHTFLig.ttf";
}
if ($size=='') {
$size="20";
}
if ($caps==true) {
$titre = stripcslashes(encode_to_xml(strtoupper($_GET['title'])));
} else {
$titre = stripcslashes(encode_to_xml($_GET['title']));
}

createText('<text color="#'.$color.'" size="'.$size.'" format="png" background-color="transparent" font="'.$font.'" leading="0" >'.$titre.'</text>');
?>