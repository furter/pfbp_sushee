<?php
include('common.php');

imageTransform(
	'<IMAGE path="'.$_GET['path'].'">
		<resize width="'.$_GET['width'].'" height="'.$_GET['height'].'"/>
	</IMAGE>');

?>