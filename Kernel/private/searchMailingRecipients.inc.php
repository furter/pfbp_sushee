<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchMailingRecipients.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php");
//require_once(dirname(__FILE__)."/../private/check_mailing.inc.php");
function searchMailingRecipients($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	
	$mailingID=$xml->getData($firstNodePath."/@ID");
	if (!$mailingID)
		return generateMsgXML(1,"You indicated no mailing ID.",0,'',$name);
	//checkMailing($mailingID);
	
	$sql = 'FROM `mailing_recipients` WHERE `MailingID`='.$mailingID.' ';
	$db_conn = db_connect();
	$type = $xml->getData($firstNodePath."/@type");
	if ($type)
	{
		if ($type=='all')
			$sql.=' ';
		else if ($type=='received')
			$sql.=' AND `Status` IN ("sent","unsuscribed") AND `ViewingDate`!="0000-00-00 00:00:00" ';
		else if ($type=='not_received')
			$sql.=' AND `ViewingDate`="0000-00-00 00:00:00" ';
		else if ($type=='clicked')
			$sql.=' AND `Status` IN ("sent","unsuscribed") AND `Mail2Web` != 0 ';
		else if ($type=='not_clicked')
			$sql.=' AND `Status`="sent" AND `ViewingDate` != "0000-00-00 00:00:00" AND `Mail2Web`=0 ';
		else if ($type=='sent')
			$sql.=' AND `Status` IN ("sent","unsuscribed")';
		else
			$sql.=' AND `Status`="'.$type.'" ';
	}

	$order = $xml->getData($firstNodePath."/WITH[1]/@order");
	if ($order == 'descending')
	{
		$order = 'DESC';
	}
	else
	{
		$order = 'ASC';
	}
	
	$sql.= ' ORDER BY `Email` '.$order.' ';

	$page = $xml->getData($firstNodePath."/WITH[1]/@page");
	$byPage = $xml->getData($firstNodePath."/WITH[1]/@perPage");

	if ($page)
	{
		if ($page==1)
		{
			$limit_string=' LIMIT '.$byPage;
		}
		else
		{
			$startIndex =($page-1)*$byPage;
			$limit_string=' LIMIT '.$startIndex.','.$byPage;
		}
		$count_sql = 'SELECT COUNT(`ContactID`) AS total '.$sql;
		//debug_log($count_sql);
		$ct_row = $db_conn->GetRow($count_sql);
		$count = $ct_row['total'];//getMailingRecipientsCount($mailingID,$type);
		if (is_numeric($page) && ($page*$byPage)>=$count )
			$isLastPage = "true";
		else
			$isLastPage = "false";
		$totalPages = ceil($count/$byPage);
		$sql.=$limit_string;
	}

	$rs = $db_conn->Execute('SELECT * '.$sql);
	debug_log('SELECT * '.$sql);
	$attributes='';
	if ($page){
		$attributes.=' page="'.$page.'" ';
		$attributes.=' isLastPage="'.$isLastPage.'" ';
		$attributes.=' totalPages="'.$totalPages.'" ';
		$attributes.=' totalCount="'.$count.'" ';
	}
	if ($name)
		$attributes.=" name='$name'";
	$external_file = $xml->getData($current_path.'/@fromFile');
	if ($external_file)
		$attributes.=" fromFile='".$external_file."'";
	$query_result='<RESULTS '.$attributes.'>';
	while($recipient = $rs->FetchRow()){
		$query_result.=generateMailingRecipientXML($recipient);
	}
	$query_result.='</RESULTS>';
	return $query_result;
}
?>
