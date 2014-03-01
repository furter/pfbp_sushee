<?php
include_once('common.php');
$_GET['languagefromphp'] = getlanguage();
$NQL = new NQL( false );
$NQL->addCommand('<SEARCH name="published_languages" refresh="daily">
	<LANGUAGES profile="Media"/>
</SEARCH>');
$NQL->addCommand('<GET><LANGUAGES /></GET>');
$NQL->xml_out();
?>