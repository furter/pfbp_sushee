<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updatePrefs.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function updatePrefs($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	$domain = $xml->getData($firstNodePath.'/@domain');
	$OwnerID = $xml->getData($firstNodePath.'/@OwnerID');
	if($OwnerID)
		$userID = $OwnerID; 
	else if(isset($_SESSION[$GLOBALS['nectil_url']]['SESSIONuserID']))
		$userID = $_SESSION[$GLOBALS['nectil_url']]['SESSIONuserID'];
	else
		$userID = 1;// template if not logged
	if (!$domain)
		return $query_result = generateMsgXML(1,"You haven't put a domain for the prefs you want to update.",0,'',$name);
	$sql = 'SELECT * FROM `prefs` WHERE `OwnerID`=\''.$userID.'\' AND `Domain`="'.encodeQuote(decode_from_XML($domain)).'";';
	$row = $db_conn->GetRow($sql);
	$xml_value = $xml->toString($firstNodePath.'/*','');
	if($row){
		$update_sql = 'UPDATE `prefs` SET `Value`="'.encodeQuote($xml_value).'" WHERE `OwnerID`=\''.$userID.'\' AND `Domain`="'.encodeQuote(decode_from_XML($domain)).'";';
	}else{
		$update_sql = 'INSERT INTO `prefs`(`OwnerID`,`Domain`,`Value`) VALUES (\''.$userID.'\',"'.encodeQuote(decode_from_XML($domain)).'","'.encodeQuote($xml_value).'");';
	}
	
	$success = $db_conn->Execute($update_sql);
	if (!$success){
		return generateMsgXML(1,"Creation failed.*$update_sql*",0,'',$name);
	}else
		return generateMsgXML(0,"Update successful",0,'',$name);
}
?>
