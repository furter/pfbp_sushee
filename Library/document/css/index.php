<?php
error_reporting ( FATAL | ERROR );
// trying at different levels to be sure to get it
include_once("../common/common_functions.inc.php");
include_once("../Kernel/common/common_functions.inc.php");
include_once("./Kernel/common/common_functions.inc.php");
include_once("../../../Kernel/common/common_functions.inc.php");
include_once("../../Kernel/common/common_functions.inc.php");
include_once("../../common/common_functions.inc.php");
include_once("../../../common/common_functions.inc.php");

echo transform(dir_xml(getcwd()),$GLOBALS["backoffice_dir"]."/private/dir.xsl");

?>
