<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/showMailing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php");


if(strlen($_GET['viewing_code'])>32){
	if(!$_GET["ID"]) // correcting a bug where the ID was not sent in the url
		$_GET['ID'] = substr($_GET['viewing_code'],33);
	$_GET['viewing_code'] = substr($_GET['viewing_code'],0,32);
	
		
}
$moduleInfo = moduleInfo('mailing',NULL);


$row = getInfo($moduleInfo,$_GET["ID"]);
$params = array("backoffice_url"=>$GLOBALS["backoffice_url"],"in_mailbox"=>'false');
if ($row){
	
	if($_GET['viewing_code']==='')
		unset($_GET['viewing_code']);
	
	if( !isset($_GET['viewing_code']) && isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) ){
		$_GET['cache']='refresh';
		
		// take an xml with the infos of the person logged
		$userID = $_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
		$contactModuleInfo = moduleInfo('contact');
		$contact_row = getInfo($contactModuleInfo,$userID);
		//$contact_recip = false;
		$contact_ok = true;
		$do_not_save = true;
	}else if(isset($_GET['viewing_code'])){
		$contact_recip = getMailingRecipient($_GET["ID"],$_GET['viewing_code']);
		$moduleContactInfo = moduleInfo('contact');
		$contact_row = getInfo($moduleContactInfo,$contact_recip['ContactID']);
		//$contact_str = generateMailingRecipientXML($contact_recip,$contact_row);
		
		$contact_ok = true;
		$do_not_save=false;
	}
	
	if ($contact_ok){
		$title = "";
		if(isset($_GET['forceLanguageID']))
			$contact_row["LanguageID"] = $_GET['forceLanguageID'];
		//$xml_str = generateCompleteMailingXML2(false,$row,$contact_row,$_GET['viewing_code'],$contact_str,$title);
		$xml_str = generateCompleteMailingXML(false,$row,$contact_row,$_GET['viewing_code'],/*$contact_recip,*/$title);
		$template_path = $GLOBALS["library_dir"].'mailing/templates/'.$row['Template'];
		if (file_exists($template_path)){
			
			$html_str = real_transform($xml_str,"file://".$template_path,$params);
			echo addMailingSpy($html_str,$_GET['ID'],'false',$_GET['viewing_code']);
		}
		if($_GET['owner']!='false' && $do_not_save!=true && $_GET['viewing_code']!=""){
			setMailingRecipientStatus($_GET["ID"],$_GET['viewing_code'],"sent");
			setMailingRecipientSeenOnWeb($_GET["ID"],$_GET['viewing_code']);
			updateMailingNbrSeenOnWeb($_GET["ID"]);
			updateMailingNbrSeen($_GET["ID"]);
			updateContactValidity($contact_row['ID'],0);
		}
	}else
		echo "You indicated no valid viewing code. You were not in the recipients of the mailing.";
}else
	echo "This mailing doesn't exist.";
?>
