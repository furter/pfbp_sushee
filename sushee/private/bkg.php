<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/bkg.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php");

if($_GET['viewing_code'] && strlen($_GET['viewing_code'])>32){
	$_GET['viewing_code'] = substr($_GET['viewing_code'],0,32);
	
	$db_conn = db_connect();
	// a firstr try on our backoffice
	$moduleInfo = moduleInfo('mailing',NULL);
	if ( $_GET['in_mailbox']!=='false' ){
		//$row = getInfo($moduleInfo,$_GET["ID"]);
		if ($_GET["ID"] && is_numeric($_GET["ID"])){
			setMailingRecipientHTMLInMailbox($_GET["ID"],$_GET['viewing_code']);
			//updateMailingCounts($_GET["ID"]);
			$base_sql = 'SELECT COUNT(`ContactID`) AS total FROM `mailing_recipients` WHERE MailingID=\''.$_GET["ID"].'\' ';
			$sql = $base_sql.'AND HTMLInMailbox=1;';
			$row = $db_conn->GetRow($sql);
			if($row){
				$NbrHTMLInMailbox=$row['total'];
				$update_sql = 'UPDATE mailings SET NbrHTMLInMailbox=\''.$NbrHTMLInMailbox.'\' WHERE ID=\''.$_GET["ID"].'\';';
				$db_conn->Execute($update_sql);
			}
		}
	}
}
// outputting a gif image
header("Content-type: image/gif");
$fn=fopen("./bkg.gif","r");
fpassthru($fn);
?>
