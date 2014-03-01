<?php
include_once("../Kernel/common/common_functions.inc.php");
include_once('../Kernel/common/file.class.php');

if (isset($_GET['path']) && $_GET['path'] != '') {
	if (substr_count($_GET['path'], '/') > 3 ) {
		$zip = new ZipFile();
		if ($_GET['type'] == 'folder') {
			$content = new Folder($_GET['path']);
		} else {
			$content = new File($_GET['path']);
		}
		$zip->add($content);
		$zip->compress();
		$zip->forceDownload();
		} else {
			echo '<h1>Error</h1><h3>The folder introduce is to big</h3>';
		}
	} else {
		echo '<h1>Error</h1><h3>Wrong path</h3>';
	}
?>