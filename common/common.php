<?php
include_once("../sushee/common/common_functions.inc.php");
include_once('../sushee/common/nql.class.php');
//
//class NQL extends Sushee_Shell{}
// ressources globales
include_once('php/common_queries.php');
//
// variables utiles
$_GET['now'] = date('Y-m-d H:i:s');
//
// désactivation de xml=true 
if($_GET['debug'] != 'spec') {
	$GLOBALS['xmlVisible']='false';
} else {
	$_GET['xml'] = 'true';
}//
?>