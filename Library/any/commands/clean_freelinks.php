<?php
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
if ( isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) ){
	$db_conn = db_connect();
	$sql = 'DELETE FROM `links` WHERE DependencyTypeID = 0;';
	$db_conn->Execute($sql);
}else
	echo "You're not logged";
?>
