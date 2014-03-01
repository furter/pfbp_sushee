<?php
include_once("../Kernel/common/common_functions.inc.php");
include_once('../Kernel/common/nql.class.php');
//
include_once('common_queries.php');
//
//$_GET['now'] = date('Y-m-d');
$_GET['language'] = 'fre';
$last_year_prize = date('Y');
$websiteID = 3495;
//
if (isset($_GET['type']) and $_GET['type'] == "") {
	$GET['type'] = 'text';
}

?>