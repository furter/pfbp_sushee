<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/showFilePreview.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");
require_once(dirname(__FILE__)."/../common/image_functions.inc.php");
require_once(dirname(__FILE__)."/../private/checkLogged.inc.php");

session_write_close();

// sometimes set_time_limit is disabled for security reasons
// we dont want any warning about that (therefore we use @)
@set_time_limit(300);

function previewFallback()
{
	header("Content-type: image/jpg");
	$fn=fopen("./bkg.jpg","r");
	fpassthru($fn);
}

if (isset($_GET['target']))
{
	global $directoryRoot;
	$target = $_GET['target'];
	$basename = BaseFilename($target);
	$path = $directoryRoot.$target;
	if (file_exists($path) && is_readable($path))
	{
		$ext = getFileExt($basename);
		if ($ext=='png' || $ext=='gif' || $ext == 'svg' || $ext == 'tif' || $ext == 'tiff' || $ext == 'avi' || $ext == 'mpg' || $ext == 'mpeg' || $ext=='pdf' || $ext=='jpg' || $ext=='jpeg')
		{
			$filesize = filesize($path);
			if ($filesize<2048000)
			{
				$convert_xml = new XML('<TRANSFORMATION><convert format="jpg"/></TRANSFORMATION>');
				$resultFile = imageCreation($convert_xml,"/TRANSFORMATION[1]",$path);
			}
			else
			{
				debug_log("Image $path too heavy!".$filesize);
			}
		}
		else if ($ext=='pdf')
		{
			$filesize = filesize($path);
			if ($filesize<2048000)
			{
				$convert_xml = new XML('<TRANSFORMATION><convert format="jpg"/></TRANSFORMATION>');
				$resultFile = imageCreation($convert_xml,"/TRANSFORMATION[1]",$path);
			}
			else
			{
				debug_log("Image $path too heavy!".$filesize);
			}
		}
		else if ($ext=='txt' || $ext=='xml' || $ext=='nql' || $ext=='sql' || $ext=='csv' || $ext=='css' || $ext=='xsl' || $ext=='vcf' || $ext=='htm' || $ext=='html' || $ext=='css' || $ext=='as')
		{
			ini_set('auto_detect_line_endings','1');
			$handle = fopen($path, "r");
			
			$lines = 20;
			if (isset($_GET['maxlines']))
			{
				$lines = $_GET['maxlines'];
			}

			$i = 0;
			$str='<RESPONSE><FILECONTENT maxlines="'.$lines.'">';
			while (!feof($handle) && $i<$lines)
			{
				$buffer = fgets($handle, 4096);
				$str.=encode_to_XML($buffer);
				$i++;
			}
			
			if (is_utf8($str))
			{
				// entity before decoding ( to avoid to loose pure utf8 characters)
				$str = utf8_decode(utf8_To_UnicodeEntities($str));
			}

			$str .= '</FILECONTENT></RESPONSE>';
			
			// xml_out dies the script
			xml_out($str);
		}

		if ($resultFile && file_exists($resultFile) && filesize($resultFile)>0)
		{
			$filesize=filesize($resultFile);
			header("Pragma: public");
			header("Expires: 0"); // set expiration time
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Transfer-Encoding: Binary");
			header("Connection: close");
			header("Content-type: image/jpg");
			header("Content-length: ".$filesize);

			$fp = fopen($resultFile, 'rb');

			while (!feof ($fp))
			{
				print(fread($fp, 1024*8));
				flush();
			}
			fclose ($fp);
		}
		else
		{
			previewFallback();
		}
	}
	else
	{
		xml_out("<?xml version='1.0'?><RESPONSE>".generateMsgXML(1,"File $path doesn't exist.")."</RESPONSE>");
	}
}
else
{
	xml_out("<?xml version='1.0'?><RESPONSE>".generateMsgXML(1,"No target was given.")."</RESPONSE>");
}