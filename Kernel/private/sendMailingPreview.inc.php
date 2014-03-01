<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/sendMailingPreview.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php");
require_once(dirname(__FILE__)."/../common/mimemail.class.php");
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");

function sendMailingPreview($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$recipientID = $xml->getData($firstNodePath.'/@to');
	$mailingID = $xml->getData($firstNodePath.'/@ID');
	if(!$mailingID)
		return generateMsgXML(1,"You indicated no mailing ID.",0,'',$name);
	if(!$recipientID && isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']))
		$recipientID = $_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
	if($recipientID){
		$moduleMailingInfo = moduleInfo('mailing');
		$moduleContactInfo = moduleInfo('contact');
		$mailing_row = getInfo($moduleMailingInfo,$mailingID);
		$contact = getInfo($moduleContactInfo,$recipientID);
		
		//$contact_path = "/RESPONSE/RESULTS/CONTACT[1]";
		if($mailing_row){
			
			$media_str ='';
			$_GET['cache']='refresh';
			$forceLanguageID = $xml->getData($firstNodePath.'/@forceLanguageID');
			if($forceLanguageID!==false)
				$contact["LanguageID"] = $forceLanguageID;
			$xml_str = generateCompleteMailingXML(true,$mailing_row,$contact,$viewing_code,&$media_str);
			$subject = $media_str." (Preview)";
			$res = sendMailofMailing($mailing_row,$contact,$xml_str,$subject);
			if($res)
				$query_result = generateMsgXML(0,"Preview sent.",0,'',$name);
			else
				$query_result = generateMsgXML(1,"Preview not sent.",0,'',$name);
			return $query_result;
		}else{
			return generateMsgXML(1,"The ID you indicated doesn't exist.",0,'',$name);
		}
	}else
		return generateMsgXML(1,"You indicated no valid recipient for the preview and we can't send it to you because you're not logged.",0,'',$name);
}
?>
