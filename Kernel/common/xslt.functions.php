<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/xslt.functions.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

function sushee_xslt_regexp_test($subject, $pattern)
{
	return (preg_match($pattern,$subject) == 1);
}

function sushee_xslt_regexp_replace($subject, $pattern, $replacement)
{
	return preg_replace($pattern,$replacement,$subject);
}

function xsl_plaintext_link($string)
{
	// to simplfy word boundaries
	$string = ' '.$string.' ';
	
	// for xml usage, within xslt
	$string = encode_to_xml($string);
	$string = xsl_plaintext_linkURL($string);
	$string = xsl_plaintext_linkMail($string);
	$string = xsl_plaintext_linkTwitter($string);

	// cleanup word boundaries
	$string = trim($string);

	$dom = new domdocument;
	$dom->loadXML('<root>'.$string.'</root>');
	return $dom->documentElement;			
}

function xsl_plaintext_linkstring($string)
{
	// for string usage, within php
	$string = xsl_plaintext_linkURL($string);
	$string = xsl_plaintext_linkMail($string);
	$string = xsl_plaintext_linkTwitter($string);
	return $string;
}

function xsl_plaintext_linkURL($string)
{
	$string = preg_replace('/(?<!")(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^,"\s<>]*)(?!")/is' , '$1$2<a href="$3">$3</a>' , $string);
	$string = preg_replace('/(?<!")(^|[\n ])([\w]*?)((www|ftp)\.[^,"\s<>]*)(?!")/is' , '$1$2<a href="http://$3">$3</a>' , $string);
	return $string;
}

function xsl_plaintext_linkMail($string)
{
	return preg_replace('/(?<!\'|")\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b(?!\'|")/is' , '<a href="mailto:$1">$1</a>' , $string);
}

function xsl_plaintext_linkTwitter($string)
{
    return preg_replace('/(\W)@(\w+)/is' , '$1<a href="http://twitter.com/$2">@$2</a>' , $string);
}

// --- mails utilities ---

function xsl_mail_cleanUpCorrespondant($string)
{
	return preg_replace('/[\\\"\',]/','',$string);
}

function xsl_mail_getCorrespondantName($recipent)
{
	$string = xsl_mail_cleanUpCorrespondant($recipent);
	if (strpos($string , '<') !== false)
	{
		$array = explode('<',$string);
		$string = $array[0];
	}
	$string = trim($string);
	if ($string == '')
	{
		$string = preg_replace('/[<>]/','',$recipent);
	}
	if (strpos($string , '@') !== false)
	{
		$array = explode('@',$string);
		$string = $array[0] . ' (' . $array[1] . ')';
	}
	return $string;
}

function xsl_mail_getCorrespondantEmail($string)
{
	preg_match('/\b([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4})\b/si' , $string , $match);
	return $match[0];
}

function xsl_mail_cleanUpReFw($string)
{
	return trim(preg_replace('/(Re|Fw|Fwd|Tr|Aw|Wg|Vs|Vl|Sv|Vb|Rv|Res|Enc):/is' , '' , $string));
}
