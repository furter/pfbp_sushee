<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/unsuscribeContact.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php");
function unsuscribeContact($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$viewing_code = $xml->getData($firstNodePath.'/@viewing_code');
	if(!$viewing_code)
		return generateMsgXML(1,"No viewing code in the request.",0,0,$name);
	if(strlen($viewing_code)>32){
		$mailingID = substr($viewing_code,33);
		$viewing_code = substr($viewing_code,0,32);
	}else
		return generateMsgXML(1,"Viewing code invalid.",0,0,$name);
	$db_conn = db_connect();
	$recip = getMailingRecipient($mailingID,$viewing_code);
	if(!$recip){
		return generateMsgXML(1,"Viewing code invalid.",0,0,$name);
	}
	$contactModuleInfo = moduleInfo('contact');
	$contact = getInfo($contactModuleInfo,$recip['ContactID']);
	if($contact['Privacy1']!=1){
		$sql = 'UPDATE `contacts` SET `Privacy1`=1 WHERE `ID`='.$recip['ContactID'];
		$db_conn->Execute($sql);
		$sql ='UPDATE `mailings` SET `NbrUnsuscribed`=`NbrUnsuscribed`+1 WHERE `ID`='.$mailingID;
		$db_conn->Execute($sql);
		setMailingRecipientStatus($mailingID,$viewing_code,"unsuscribed");
	}
	
	return generateMsgXML(0,"Unsuscription successfull.",0,0,$name);
}

?>