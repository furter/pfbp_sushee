<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_request.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");

function filerequest($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath)
{
	$action = $xml->getData($current_path.'/@action');


	switch ($action)
	{
		case 'delete':
		case 'recdelete':
			$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/*[1]")));

			include_once dirname(__FILE__)."/../file/file_delete.inc.php";
			$query_result = file_delete($name,$action,$target);
			break;
		
		case 'move':
			$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/SOURCE[1]")));
			$target2 = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/TARGET[1]")));

			include_once dirname(__FILE__)."/../file/file_move.inc.php";
			$query_result = file_move($name,$action,$target,$target2);
			break;
		
		case 'copy':
			$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/SOURCE[1]")));
			$target2 = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/TARGET[1]")));

			include_once dirname(__FILE__)."/../file/file_move.inc.php";
			$query_result = file_move($name,$action,$target,$target2);
			break;

		case 'rename':
			$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/SOURCE[1]")));
			$target2 = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/TARGET[1]")));

			include_once dirname(__FILE__)."/../file/file_rename.inc.php";
			$query_result = file_rename($name,$action,$target,$target2);
			break;
		
		case 'new':
		case 'mkdir':
			$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/TARGET[1]")));
			if(!$target)
			{
				$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/SOURCE[1]")));
			}

			include_once dirname(__FILE__)."/../file/file_mkdir.inc.php";
			$query_result = file_mkdir($name,$action,$target);
			break;

		case 'zip':
			$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/*[1]")));

			include_once dirname(__FILE__)."/../file/file_zip.inc.php";
			$query_result = file_zip($name,$action,$target);
			break;
		
		case 'unzip':
			$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/*[1]")));

			include_once dirname(__FILE__)."/../file/file_unzip.inc.php";
			$query_result = file_unzip($name,$action,$target);
			break;
	
		case 'transform':
			$source = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/SOURCE[1]")));
			$target = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path."/TARGET[1]")));

			include_once dirname(__FILE__)."/../file/image_transform.inc.php";
			$query_result = image_transform($name,$xml,$current_path,$action,$source,$target);
			break;
		
		case 'list':
			$path = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path)));
			$query_result = filesList($name,$path);
			break;
		
		case 'deploy':
			$path = UnicodeEntities_To_utf8(decode_from_XML($xml->getData($current_path)));
			$query_result = getTree($name,$path);
			break;

		default:
			$str = "File action error: action: $action doesn't exist";
			return generateMsgXML(1,$str,0,'',$name);
	}

	return $query_result;
}