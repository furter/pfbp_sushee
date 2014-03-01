<?php
require_once(dirname(__FILE__)."/../../../Kernel/common/common_functions.inc.php");
if ( isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) ){
	$db_conn = db_connect();
	
	// deleting all dependencies for media
	$mediaModuleInfo = moduleInfo('media');
	echo "Deleting dependencies : <br/>";
	echo deleteModuleDependencies($mediaModuleInfo->ID)."<br/>";
	echo "Deleting dependencyTypes : <br/>";
	echo deleteModuleDependencyTypes($mediaModuleInfo->ID)."<br/>";
	echo "Deleting descriptions : <br/>";
	echo deleteModuleDescriptions($mediaModuleInfo->ID)."<br/>";
	echo "Deleting comments : <br/>";
	echo deleteModuleComments($mediaModuleInfo->ID)."<br/>";
	echo "Deleting freelinks : <br/>";
	echo deleteModuleFreelinks($mediaModuleInfo->ID)."<br/>";
	echo "Deleting elements : <br/>";
	echo resetModule($mediaModuleInfo)."<br/>";
	// now the mediaTypes
	echo "Deleting mediatypes : <br/>";
	$sql ="DELETE FROM mediatypes;";
	$db_conn->Execute($sql);
	$sql ="DELETE FROM mediatypesconfig;";
	$db_conn->Execute($sql);
	echo "Deleting descriptions configuration : <br/>";
	$sql ="DELETE FROM descriptionsconfig WHERE ModuleID=".$mediaModuleInfo->ID.";";
	$db_conn->Execute($sql);
	echo $sql."<br/>";
	$sql ="DELETE FROM descriptions_history;";
	$db_conn->Execute($sql);
	echo $sql."<br/>";
}else
	echo "You're not logged";
?>
