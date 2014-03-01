<?php
include_once("../common/common.php");
include_once("common_queries.php");
//
$websiteID=3500;
$last_year_prize = date('Y') - 1;
//
if (isset($_GET['mode']) && $_GET['mode'] == 'press' ) {
	$_SESSION['press'] = 1;
}
if (isset($_SESSION['press']) && $_SESSION['press'] == 1) {
	$_GET['press'] = 1;
}
?>