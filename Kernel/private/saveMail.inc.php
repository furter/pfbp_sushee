<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/saveMail.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");

function saveMail($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath)
{
	global $slash;
	global $directoryRoot;
	
	$db_conn = db_connect();
	$sql="";
	$now = date('YmdHis');
	$moduleInfo = moduleInfo('mail');
	$rs = getResultSet($moduleInfo,$xml,$current_path,$sql);
	$tmp_prefix = $slash.'tmp'.$slash.'saveMail'.$now;
	$tmp_dir = $tmp_prefix.$slash;
	$tmp_zip = $tmp_prefix.'.zip';
	$mails_dir = $tmp_dir.'mails'.$slash;
	makeDir($directoryRoot.$mails_dir);
	$i = 0;
	while($row = $rs->FetchRow())
	{
		$mail_name = $row['ReceivingDate'].'-'.generate_utf8($row['Subject']);
		$mail_name = str_replace(":","_",$mail_name);
		$mail_name = str_replace("/","_",$mail_name);
		$mail_name = str_replace("\\","_",$mail_name);
		$mail_name = str_replace(" ","-",$mail_name);

		$mail_dir = $mails_dir.$mail_name.$slash;
		makeDir($directoryRoot.$mail_dir);

		$plaintext = "\xEF\xBB\xBF";
		
		$plaintext .= 'From: ' . generate_utf8($row['From']) . "\n";
		$plaintext .= 'Date: ' . $row['ReceivingDate'] . "\n";
		$plaintext .= 'To: ' . generate_utf8($row['To']) . "\n";
		if ($row['Cc'] != '')
			$plaintext .= 'Cc: ' . generate_utf8($row['Cc']) . "\n";
		if ($row['Bcc'] != '')
			$plaintext .= 'Bcc: ' . generate_utf8($row['Bcc']) . "\n";
		$plaintext .= 'Subject: ' . generate_utf8($row['Subject']) . "\n";
		if ($row['Attachments'] != '')
			$plaintext .= 'Attachments: ' . $row['Attachments'] . "\n \n \n";		

		$plaintext .= generate_utf8($row['PlainText']);

		// uniformizing newlines to unix standard
		$plaintext = str_replace(array("\r\n","\r"),"\n",$plaintext);

		// converting to windows spec of newline (te be readable in notepad)
		$plaintext = str_replace("\n","\r\n",$plaintext);

		saveInFile($plaintext,$directoryRoot.$mail_dir.$mail_name.'.txt');

		// copying all attachments in the temp directory
		$orig_maildir = $row['Folder'];
		if ($row['Folder'] && file_exists($directoryRoot.$orig_maildir))
		{
			copy_content($directoryRoot.$orig_maildir,$directoryRoot.$mail_dir);
		}
		$i++;
	}

	// zipping the whole directory
	if ($i<=1)
	{
		// only one mail -> taking the subdir
		zip($directoryRoot.$mail_dir,$directoryRoot.$tmp_zip);
	}
	else
	{
		zip($directoryRoot.$mails_dir,$directoryRoot.$tmp_zip);
	}

	killDirectory($directoryRoot.$tmp_dir);

	if ($name)
	{
		$attributes.=" name='$name'";
	}

	$external_file = $xml->getData($current_path.'/@fromFile');
	
	if ($external_file)
	{
		$attributes.=" fromFile='".$external_file."'";
	}

	$query_result='<RESULTS'.$attributes.'>';
	$query_result.= '<FILE>'.$tmp_zip.'</FILE></RESULTS>';
	return $query_result;
}

