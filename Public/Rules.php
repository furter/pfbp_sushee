<?php
include_once("common.php");
$NQL = new NQL();
$NQL->addCommand(get_media($_GET['ID']));
echo $NQL->transform("Rules.xsl");
?>