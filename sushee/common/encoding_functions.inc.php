<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/encoding_functions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

function mysql_password($passStr) {
    $nr=0x50305735;
	$nr2=0x12345671;
	$add=7;
	$charArr = preg_split("//", $passStr);

	    foreach ($charArr as $char) {
	              if (($char == '') || ($char == ' ') || ($char == '\t')) continue;
	              $charVal = ord($char);
	              $nr ^= ((($nr & 63) + $add) * $charVal) + ($nr << 8);
	              $nr &= 0x7fffffff;
	              $nr2 += ($nr2 << 8) ^ $nr;
	              $nr2 &= 0x7fffffff;
	              $add += $charVal;
	      }
	return sprintf("%08x%08x", $nr, $nr2);
}

function encode_for_vcard($str){
	require_once(dirname(__FILE__)."/../common/mimemail.class.php");
	$mime = new MimeMail();
	$str = str_replace('
',', ',$str);
	$str = str_replace('\r\n',', ',$str);
	$str = str_replace('\r',', ',$str);
	$str = str_replace('\n',', ',$str);
	return $mime->_quotedPrintableEncode(unhtmlentities(decode_from_xml($str)));
}

function bbdecode($str){
	$opening_bb = strpos($str, "[html]" );
	while($opening_bb!==FALSE){
		$closing_bb = strpos($str, "[/html]" ,$opening_bb );
		if ($closing_bb!==FALSE){
			$bb_code = substr($str,$opening_bb+6,$closing_bb-$opening_bb-6);
			$size = strlen($bb_code);
			$bb_code = decode_from_XML($bb_code);
			$str = substr_replace ($str,$bb_code,$opening_bb,$closing_bb-$opening_bb+7);
			$diff = 6+7+$size-strlen($bb_code);
		}else
			break;
		$opening_bb = strpos($str, "[html]",$closing_bb-$diff );
		//echo "bingo $size ".$opening_bb." ".substr($str,$closing_bb-$diff)."<br/>";
	}
	
	return $str;
}

function removeBadStyling($str){
	if(!$str)
		return $str;
	$opening_bb = strpos($str, "[html]" );
	while($opening_bb!==FALSE){
		$closing_bb = strpos($str, "[/html]" ,$opening_bb );
		if ($closing_bb!==FALSE){
			$bb_code = substr($str,$opening_bb,$closing_bb-$opening_bb);
			$size = strlen($bb_code);
			$search = array("'<[\/\!]*?[^<>]*?>'si"); /* "'<[\/\!]*?[^<>]*?>'si"*/
			$replace = array("");
			$bb_code = preg_replace($search,$replace,$bb_code);
			$str = substr_replace ($str,$bb_code,$opening_bb,$closing_bb-$opening_bb);
			$diff = $size-strlen($bb_code);
			//$diff=0;
		}else
			break;
		$opening_bb = strpos($str, "[html]",$closing_bb-$diff );
		//echo "bingo $size ".$opening_bb." ".substr($str,$closing_bb-$diff)."<br/>";
	}
	return $str;
}

function encodeQuote($str){
	// replacing " by \" to be " in the db
	// replacing \ by \\ to be \ in the db
    return str_replace(array("\\","\""),array("\\\\","\\\""),$str);
}

function encode_to_XML($str) {
	//$str = UnicodeEntities_To_utf8($str);
	// allows to grab the data from the db and to generate a valid xml
	//$str = str_replace("&", "&amp;", $str);
	//$str = preg_replace ( "/&&/","&amp;&amp;", $str);
	//$str = str_replace("&#xA", "", $str);
	//$str = preg_replace ( '/&(?=[^#])|&$/','&amp;', $str);
	$str = preg_replace ( '/&(?!#[a-zA-Z0-9]*;)|&$/','&amp;', $str); // also handles &#34 without ending ; which was not handled by above expression
	$str = str_replace("'", "&apos;", $str);
	$str = str_replace(">", "&gt;", $str); // because DB can contain XML
	$str = str_replace("<", "&lt;", $str);
	$str = str_replace("\"", "&quot;", $str);
	$str = str_replace("&#34;", "&quot;", $str);
	return $str;
}
function encode_for_DB($str){
	return encodeQuote(decode_from_XML($str));
}

function decode_from_XML($str) {
    // allows to decode the Flash XML and to put the data in the DB
	$str = str_replace("&amp;", "&", $str);
	$str = str_replace("&apos;", "'", $str);
	$str = str_replace("&gt;", ">", $str);
	$str = str_replace("&lt;", "<", $str);
	$str = str_replace("&quot;", "\"", $str);
	return $str;
}

function iso_To_UnicodeEntities($string, $quote_style=ENT_COMPAT)
{
   $trans = get_html_translation_table(HTML_ENTITIES, $quote_style);

   foreach ($trans as $key => $value){
	   if ($key!=">" && $key!="<" && $key!="&" && $key!='"' && $key!="'")
       $trans[$key] = '&#'.ord($key).';';
	   else
	   	unset($trans[$key]);
   }

   return strtr($string, $trans);
}

function utf8_To_UnicodeEntities($source) {
	// array used to figure what number to decrement from character order value
	// according to number of characters used to map unicode to ascii by utf-8

	static $_utfDelim;

	if (! isset($_utfDelim)) {
		$_utfDelim = '';
		for ($i = 192; $i < 256; $i++)
			$_utfDelim .= chr($i);
	}
	$decrement[4] = 240;
	$decrement[3] = 224;
	$decrement[2] = 192;
	$decrement[1] = 0;
	// the number of bits to shift each charNum by
	$shift[1][0] = 0;
	$shift[2][0] = 6;
	$shift[2][1] = 0;
	$shift[3][0] = 12;
	$shift[3][1] = 6;
	$shift[3][2] = 0;
	$shift[4][0] = 18;
	$shift[4][1] = 12;
	$shift[4][2] = 6;
	$shift[4][3] = 0;

	$pos = 0;
	$len = strlen ($source);
	$encodedString = '';
	while ($pos < $len) {
		$spanLen = strcspn($source, $_utfDelim, $pos);
		if ($spanLen == $len - $pos)
			return $encodedString . substr($source, $pos);
		$encodedString .= substr($source, $pos, $spanLen);
		$pos += $spanLen;
		$asciiPos = ord (substr ($source, $pos, 1));
		if (($asciiPos >= 240) && ($asciiPos <= 255)) {

			// 4 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 4);
			$pos += 4;
		}else if (($asciiPos >= 224) && ($asciiPos <= 239)) {
			// 3 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 3);
			$pos += 3;
		}else if (($asciiPos >= 192) && ($asciiPos <= 223)) {
			// 2 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 2);
			$pos += 2;
		}/*else {
			// 1 char (lower ascii)
			$thisLetter = substr ($source, $pos, 1);
			$pos += 1;
		}*/

		// process the string representing the letter to a unicode entity
		$thisLen = strlen ($thisLetter);
		$thisPos = 0;
		$decimalCode = 0;
		while ($thisPos < $thisLen) {
			$thisCharOrd = ord (substr ($thisLetter, $thisPos, 1));
			if ($thisPos == 0) {
				$charNum = intval ($thisCharOrd - $decrement[$thisLen]);
				$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
			}else {
				$charNum = intval ($thisCharOrd - 128);
				$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
			}
			$thisPos++;
		}

		if ($thisLen == 1)
		$encodedLetter = $thisLetter;//"&#". str_pad($decimalCode, 3, "0", STR_PAD_LEFT) . ';';
		else{
			//$encodedLetter = "&#". str_pad($decimalCode, 5, "0", STR_PAD_LEFT) . ';';
			$encodedLetter = "&#".$decimalCode.';';
		}

		$encodedString .= $encodedLetter;
	}
	return $encodedString;
}
function UnicodeEntities_To_utf8($source) {

	
	if (strstr($source,"&#")){
	$utf8Str = '';
	$entityArray = explode ("&#", $source);

	$size = count ($entityArray);
	//echo "size is ".$size;
	$entity_pos = 0;
	for ($i = 0; $i < $size; $i++) {

		$subStr = $entityArray[$i];

		$nonEntity = strstr ($subStr, ';');
		$nonEntity2 = (substr($source,$entity_pos,2)=="&#");
		//echo "***".substr($source,$entity_pos,2)."<br/>";
		$entity_pos = strpos($source,"&#",$entity_pos);

		if ($nonEntity !== false && $nonEntity2!=false ) {
			//echo "sub ".$subStr."<br/>";
			$unicode = intval (substr ($subStr, 0, (strpos ($subStr, ';') + 1)));
			//echo "uni ".$unicode."<br/>";
			// determine how many chars are needed to reprsent this unicode char

			if ($unicode < 128) {
				$utf8Substring = chr ($unicode);

			}else if ($unicode >= 128 && $unicode < 2048) {
				$binVal = str_pad (decbin ($unicode), 11, "0", STR_PAD_LEFT);
				$binPart1 = substr ($binVal, 0, 5);
				$binPart2 = substr ($binVal, 5);


				$char1 = chr (192 + bindec ($binPart1));
				$char2 = chr (128 + bindec ($binPart2));
				$utf8Substring = $char1 . $char2;

			}else if ($unicode >= 2048 && $unicode < 65536) {
				$binVal = str_pad (decbin ($unicode), 16, "0", STR_PAD_LEFT);
				$binPart1 = substr ($binVal, 0, 4);
				$binPart2 = substr ($binVal, 4, 6);
				$binPart3 = substr ($binVal, 10);


				$char1 = chr (224 + bindec ($binPart1));
				$char2 = chr (128 + bindec ($binPart2));
				$char3 = chr (128 + bindec ($binPart3));
				$utf8Substring = $char1 . $char2 . $char3;

			}else {
				$binVal = str_pad (decbin ($unicode), 21, "0", STR_PAD_LEFT);
				$binPart1 = substr ($binVal, 0, 3);
				$binPart2 = substr ($binVal, 3, 6);
				$binPart3 = substr ($binVal, 9, 6);
				$binPart4 = substr ($binVal, 15);


				$char1 = chr (240 + bindec ($binPart1));
				$char2 = chr (128 + bindec ($binPart2));
				$char3 = chr (128 + bindec ($binPart3));
				$char4 = chr (128 + bindec ($binPart4));
				$utf8Substring = $char1 . $char2 . $char3 . $char4;

			}

			if (strlen ($nonEntity) > 1)

			$nonEntity = substr ($nonEntity, 1); // chop the first char (';')

			else

			$nonEntity = '';

			$utf8Str .= $utf8Substring . $nonEntity;

		}else {

			$utf8Str .= $subStr;

		}

	}

	return $utf8Str;
	}else
		return $source;
}
function entities_to_utf8($str){
	return UnicodeEntities_To_utf8($str);
}

function unhtmlentities($string)  {
   $trans_tbl = get_html_translation_table (HTML_ENTITIES);
   $trans_tbl = array_flip ($trans_tbl);
   $ret = strtr ($string, $trans_tbl);
   return preg_replace('/&#(\d+);/me',
     "chr('\\1')",$ret);
}

function isCP1252($str) {
	return preg_match('/^([\x09\x0A\x0D\x20-\x7E\x80\x82-\x8C\x8E\x91-\x9C\x9E-\xFF])*$/', $str);
}

function isUTF8($str) {
	return preg_match('/^([\x09\x0A\x0D\x20-\x7E]|[\xC2][\xA0-\xBF]|[\xC3-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})*$/', substr($str,0,1000));
}


// Generate a UTF-8 encoded character from the code point
function utf8Char($codePoint){
	$char = '';
	if ($codePoint < 0){
		return false;
	} elseif ($codePoint <= 0x007f) {
		$char .= chr($codePoint);
	} elseif ($codePoint <= 0x07ff) {
		$char .= chr(0xc0 | ($codePoint >> 6));
		$char .= chr(0x80 | ($codePoint & 0x003f));
	} elseif ($codePoint == 0xFEFF) {
		// nop -- zap the BOM
	} elseif ($codePoint >= 0xD800 && $codePoint <= 0xDFFF) {
		// found a surrogate
		return false;
	} elseif($codePoint <= 0xffff) {
		$char .= chr(0xe0 | ($codePoint >> 12));
		$char .= chr(0x80 | (($codePoint >> 6) & 0x003f));
		$char .= chr(0x80 | ($codePoint & 0x003f));
	} elseif($codePoint <= 0x10ffff) {
		$char .= chr(0xf0 | ($codePoint >> 18));
		$char .= chr(0x80 | (($codePoint >> 12) & 0x3f));
		$char .= chr(0x80 | (($codePoint >> 6) & 0x3f));
		$char .= chr(0x80 | ($codePoint & 0x3f));
	} else { 
		// out of range
		return false;
	}
	return $char;
}

function utf8_3_bytes ($str) {
	$str_array = array(
		chr(195).chr(160) => chr(97 ).chr(204).chr(128),
		chr(195).chr(168) => chr(101).chr(204).chr(128),
		chr(195).chr(172) => chr(105).chr(204).chr(128),
		chr(195).chr(178) => chr(111).chr(204).chr(128),
		chr(195).chr(185) => chr(117).chr(204).chr(128),
		chr(196).chr(134) => chr(67 ).chr(204).chr(129),
		chr(195).chr(137) => chr(69 ).chr(204).chr(129),
		chr(197).chr(131) => chr(78 ).chr(204).chr(129),
		chr(195).chr(147) => chr(79 ).chr(204).chr(129),
		chr(197).chr(154) => chr(83 ).chr(204).chr(129),
		chr(197).chr(185) => chr(90 ).chr(204).chr(129),
		chr(195).chr(161) => chr(97 ).chr(204).chr(129),
		chr(196).chr(135) => chr(99 ).chr(204).chr(129),
		chr(195).chr(169) => chr(101).chr(204).chr(129),
		chr(195).chr(173) => chr(105).chr(204).chr(129),
		chr(197).chr(132) => chr(110).chr(204).chr(129),
		chr(195).chr(179) => chr(111).chr(204).chr(129),
		chr(197).chr(155) => chr(115).chr(204).chr(129),
		chr(195).chr(186) => chr(117).chr(204).chr(129),
		chr(195).chr(189) => chr(121).chr(204).chr(129),
		chr(197).chr(186) => chr(122).chr(204).chr(129),
		chr(195).chr(162) => chr(97 ).chr(204).chr(130),
		chr(195).chr(170) => chr(101).chr(204).chr(130),
		chr(195).chr(174) => chr(105).chr(204).chr(130),
		chr(195).chr(180) => chr(111).chr(204).chr(130),
		chr(195).chr(187) => chr(117).chr(204).chr(130),
		chr(195).chr(145) => chr(78 ).chr(204).chr(131),
		chr(195).chr(177) => chr(110).chr(204).chr(131),
		chr(196).chr(131) => chr(97 ).chr(204).chr(134),
		chr(196).chr(159) => chr(103).chr(204).chr(134),
		chr(196).chr(176) => chr(73 ).chr(204).chr(135),
		chr(197).chr(187) => chr(90 ).chr(204).chr(135),
		chr(197).chr(188) => chr(122).chr(204).chr(135),
		chr(195).chr(132) => chr(65 ).chr(204).chr(136),
		chr(195).chr(150) => chr(79 ).chr(204).chr(136),
		chr(195).chr(156) => chr(85 ).chr(204).chr(136),
		chr(195).chr(164) => chr(97 ).chr(204).chr(136),
		chr(195).chr(171) => chr(101).chr(204).chr(136),
		chr(195).chr(175) => chr(105).chr(204).chr(136),
		chr(195).chr(182) => chr(111).chr(204).chr(136),
		chr(195).chr(188) => chr(117).chr(204).chr(136),
		chr(195).chr(191) => chr(121).chr(204).chr(136),
		chr(195).chr(133) => chr(65 ).chr(204).chr(138),
		chr(197).chr(174) => chr(85 ).chr(204).chr(138),
		chr(195).chr(165) => chr(97 ).chr(204).chr(138),
		chr(197).chr(175) => chr(117).chr(204).chr(138),
		chr(197).chr(145) => chr(111).chr(204).chr(139),
		chr(197).chr(177) => chr(117).chr(204).chr(139),
		chr(196).chr(140) => chr(67 ).chr(204).chr(140),
		chr(197).chr(152) => chr(82 ).chr(204).chr(140),
		chr(197).chr(160) => chr(83 ).chr(204).chr(140),
		chr(197).chr(189) => chr(90 ).chr(204).chr(140),
		chr(196).chr(141) => chr(99 ).chr(204).chr(140),
		chr(196).chr(155) => chr(101).chr(204).chr(140),
		chr(197).chr(153) => chr(114).chr(204).chr(140),
		chr(197).chr(161) => chr(115).chr(204).chr(140),
		chr(197).chr(165) => chr(116).chr(204).chr(140),
		chr(197).chr(190) => chr(122).chr(204).chr(140),
		chr(195).chr(135) => chr(67 ).chr(204).chr(167),
		chr(195).chr(167) => chr(99 ).chr(204).chr(167),
		chr(197).chr(159) => chr(115).chr(204).chr(167),
		chr(197).chr(163) => chr(116).chr(204).chr(167),
		chr(196).chr(133) => chr(97 ).chr(204).chr(168),
		chr(196).chr(153) => chr(101).chr(204).chr(168),
		chr(197).chr(179) => chr(117).chr(204).chr(168)
	);
	return str_replace(array_keys($str_array),array_values($str_array),$str);
}

// Callback function for utf8FromCP1252()
function utf8FromCP1252Char($char) {
	$utf8CodePoint = array(
		128 => 0x20AC,
		129 => '',
		130 => 0x201A,
		131 => 0x0192,
		132 => 0x201E,
		133 => 0x2026,
		134 => 0x2020,
		135 => 0x2021,
		136 => 0x02C6,
		137 => 0x2030,
		138 => 0x0160,
		139 => 0x2039,
		140 => 0x0152,
		141 => '',
		142 => 0x017D,
		143 => '',
		144 => '',
		145 => 0x2018,
		146 => 0x2019,
		147 => 0x201C,
		148 => 0x201D,
		149 => 0x2022,
		150 => 0x2013,
		151 => 0x2014,
		152 => 0x02DC,
		153 => 0x2122,
		154 => 0x0161,
		155 => 0x203A,
		156 => 0x0153,
		157 => '',
		158 => 0x017E,
		159 => 0x0178);
	$cp1252CodePoint = ord($char);
	return utf8Char($utf8CodePoint[$cp1252CodePoint]);
}

// Convert the encoding of a string from Windows-1252 to UTF-8
function utf8FromCP1252($string) {
	/*if (isCP1252($string)) {*/
		$utf8String = utf8_encode($string);
		return preg_replace_callback('|\xC2([\x80\x82-\x8C\x8E\x91-\x9C\x9E\x9F])|', create_function('$s','return utf8FromCP1252Char($s[1]);'), $utf8String);
	/*} else {
		return '';
	}*/
}

function html_entities_to_utf8($text){
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	foreach($trans_tbl as $k => $v)
	{
	   $ttr[$v] = utf8_encode($k);
	}
	
	$text = strtr($text, $ttr);
	return $text;
}

function generatePlainTextFromHTML($html){
	$search = array ('@<script[^>]*?>.*?</script>@si', // Supprime le javascript
					 '@<style[^>]*?>.*?</style>@si', // Supprime les styles
					 '@<a[^>]*?href="([^"]*?)">(http://.*?)</a>@si', // Remplace les liens
					 '@<a[^>]*?href="([^"]*?)">(.*?)</a>@si', // Remplace les liens
					'@<br/?>@si', // remplace les <br> par des retours à la ligne
					//'@</p>@si', // remplace les </p> par des retours à la ligne
					 '@<[\/\!]*?[^<>]*?>@si',          // Supprime les balises HTML
					 //'@([\r\n])[\s]+@',                // Supprime les espaces
					 '@&(quot|#34);@i',                // Remplace les entités HTML
					 '@&(amp|#38);@i',
					 '@&(lt|#60);@i',
					 '@&(gt|#62);@i',
					 '@&(nbsp|#160);@i',
					 '@&(iexcl|#161);@i',
					 '@&(cent|#162);@i',
					 '@&(pound|#163);@i',
					 '@&(copy|#169);@i');                    

	$replace = array ('',
					 '',
					'\1',
					 '\2 &lt;\1&gt;',
					"\r\n",
					//"\r\n\r\n",
					 '',
					 //'\1',
					 '"',
					 '&',
					 '<',
					 '>',
				   ' ',
				   chr(161),
				   chr(162),
				   chr(163),
				   chr(169));
	return UnicodeEntities_To_utf8(html_entities_to_utf8(preg_replace($search, $replace,$html)));
}

function stripEmail($complete_to){
	if (getServerOS()!=='windows')
		return $complete_to;
	$tos = explode(',',$complete_to);
	for($i=0;$i<sizeof($tos);$i++){
		$to = $tos[$i];
		$pos_lt = strpos($to,'<');
		if($pos_lt!==false){
			$pos_gt = strpos($to,'>',$pos_lt);
			//$to = '"'.substr($to,0,$pos_lt).'"'.substr($to,$pos_lt);
			$to = substr($to,$pos_lt+1,$pos_gt-$pos_lt-1);
			$tos[$i]=$to;
		}
	}
	$complete_to = implode(',',$tos);
	//debug_log($complete_to);
	return $complete_to;
}

function CSStoPlain(&$xml,$path){
	$eol="\n";
	$li_white_space = ' ';
	$childs = $xml->match($path."/* | ".$path."//li");
	$k = 0;
	foreach($childs as $child_path){
		$childnode = $xml->getElement($child_path);
		$childnodeName = $xml->nodeName($child_path);
		
		$k++;
		if($childnodeName=='ul'){
			;
		}else{
			$grandchilds_match = $child_path."/node()";
			$grandchilds = $xml->match($grandchilds_match);
			$white_spaces='';
			if($childnodeName=='li'){
				$nb_parents = sizeof($xml->match($child_path."/ancestor::ul"));
				for($j=0;$j<$nb_parents;$j++)
					$white_spaces.= $li_white_space;
				$str.=$white_spaces;
				$str.='* ';
				$white_spaces.= '  '; // if <br>, we shift just after the * and not a the same height
			}
			foreach($grandchilds as $grandchild_path){
				$this_node = $xml->getElement($grandchild_path);
				
				$grandchildnodeName = $xml->nodeName($grandchild_path);
				
				$grandchildvalue = decode_from_xml($xml->getData($grandchild_path));
				switch($grandchildnodeName){
					case 'a':
						$str.=$grandchildvalue;
						$href = $xml->getData($grandchild_path.'/@href');
						$href = str_replace('[files_url]',$GLOBALS["files_url"],$href);
						if($href!=trim($grandchildvalue) && $href!='mailto:'.$grandchildvalue && $href!=$grandchildvalue.'/' && $grandchildvalue!=$href.'/' && $href!='http://'.$grandchildvalue.'/' && $href!='http://'.$grandchildvalue)
							$str.=' '.$href.' ';
						break;
					case 'img':
						$src = $xml->getData($grandchild_path.'/@src');
						$alt = $xml->getData($grandchild_path.'/@alt');
						if($alt){
							$str.=$alt;
						}else{
							$src = str_replace('[files_url]',$GLOBALS["files_url"],$src);
							$str.='<'.$src.'>';
						}
						
						break;
					case 'br':$str.=$eol.$white_spaces;break;
					case 'strong':$str.='*'.$grandchildvalue.'*';break;
					//text node
					default:$str.=$grandchildvalue;
				}
			}
			$str.=$eol;
		}
	}
	return $str;
}

function CSStoStyledQuote($styledtext)
{
	$eol="\n";
	$li_white_space = ' ';
	$xml = new XML($styledtext);
	$childs = $xml->match("/CSS/*");
	$current_i = 0;
	$quote_colors = array('FF0000','0000FF','00FF00');
	foreach($childs as $child_path)
	{
		$childnode = $xml->getNode($child_path);
		$childnodeName = $xml->nodeName($child_path);
		
		$node_content = decode_from_xml(UnicodeEntities_To_utf8($xml->getData($child_path)));
		if(substr($node_content,0,1)=='>')
		{
			$i = 0;
			while(substr($node_content,0,1)=='>')
			{
				$node_content = substr($node_content,1);
				$i++;
			}
			$prec_i = $current_i;
			$current_i = $i;
			if(trim($node_content)=='')
			{
				$node_content = '';
			}
			
			$xml->replaceData($child_path, $node_content);
			
			if($prec_i < $current_i)
			{
				for($j=$prec_i;$j<$current_i;$j++)
				{
					if(isset($quote_colors[$j]))
					{
						$quote_color = $quote_colors[$j];
					}
					else
					{
						$quote_color = $quote_colors[sizeof($quote_colors)-1];
					}
					$str.='<blockquote style="color:#'.$quote_color.';border-left:2px solid #'.$quote_color.';">';
				}
			}
			else if($prec_i > $current_i)
			{
				for($j=$current_i;$j<$prec_i;$j++)
				{
					$str.='</blockquote>';
				}
			}
			
			$str .= $xml->toString($child_path,'');
		}
		else
		{
			$prec_i = $current_i;
			$current_i = 0;
			for($j=$current_i;$j<$prec_i;$j++)
			{
				$str.='</blockquote>';
			}
			$str.=$xml->toString($child_path,'');
		}
		
	}
	return $str;
}