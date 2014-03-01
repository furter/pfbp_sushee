<?php
include_once("common.php");
if (isset($_GET['cID']) && $_GET['cID'] != '') {
	$NQL= new NQL(false);
	$NQL->addCommand(get_media(3528));
	$NQL->addCommand('
		<UPDATE>
			<CONTACT ID="'.$_GET['cID'].'">
				<CATEGORIES operation="remove">
					<CATEGORY ID="36"/>
				</CATEGORIES>
				<CATEGORIES operation="append">
					<CATEGORY ID="52"/>
				</CATEGORIES>
			</CONTACT>
		</UPDATE>
	');
	$NQL->execute();
	echo $NQL->transform('NewsletterUnsubscribe.xsl');
}


?>