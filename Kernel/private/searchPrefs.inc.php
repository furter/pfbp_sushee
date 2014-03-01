<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchPrefs.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function searchPrefs($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	$domain = $xml->getData($firstNodePath.'/@domain');
	if(isset($_SESSION[$GLOBALS['nectil_url']]['SESSIONuserID']))
		$userID = $_SESSION[$GLOBALS['nectil_url']]['SESSIONuserID'];
	else
		$userID = 1; // template if not logged
	if (!$domain)
		return $query_result = generateMsgXML(1,"You haven't put a domain for the prefs you want to get.",0,'',$name);
	$sql = 'SELECT * FROM `prefs` WHERE (`OwnerID`=\''.$userID.'\' OR `OwnerID`=\'1\') AND `Domain`="'.encodeQuote(decode_from_XML($domain)).'" ORDER BY `OwnerID` DESC LIMIT 1;';
	$row = $db_conn->GetRow($sql);

	$attributes = '';
	if ($name)
		$attributes.=' name="'.$name.'"';
	$external_file = $xml->getData($current_path.'/@fromFile');
	if($external_file)
		$attributes.=' fromFile="'.$external_file.'"';
	$query_result='<RESULTS'.$attributes.'>';
	if($row){
		$query_result.='<PREFS domain="'.encode_to_xml($domain).'">';
		$query_result.=$row['Value'];
		$query_result.='</PREFS>';
	}
	$query_result.='</RESULTS>';
	return $query_result;
	
}
?>
