<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/media_processing.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

class sushee_friendlyTitleBuilder{
	
	function encode($str){
		$to_replace = array('.','&','?','#',' ','%','/',':',',',"'",'"',';','+');
		$replacment = '-';
		$str = decode_from_xml($str);
		$str = removeaccents($str);
		$str = strtolower($str);
		$str = trim($str);
		// replacing special chars by dashes
		$str = str_replace($to_replace,$replacment,$str);
		// removing duplicate dashes
		$str = preg_replace('{(-)\1+}','$1',$str);
		// removing last dash if at end
		if(substr($str,-1)=='-'){
			$str = substr($str,0,-1);
		}
		return $str;
	}
	
	function preprocess($data){
		$node = $data->getNode();
		if($node && $node->exists('*[1]/DESCRIPTIONS/DESCRIPTION/TITLE'))
		{
			$descriptions = $node->getElements('*[1]/DESCRIPTIONS/DESCRIPTION');
			$msg = '';
			foreach($descriptions as $description)
			{
				$title = $description->valueOf('TITLE');
				//$friendlytitle = $description->valueOf('FRIENDLYTITLE');
				if($title) //&& !$friendlytitle
				{
					$friendlytitle = $this->encode($title);
					$msg .= $friendlytitle. ' | ';
					if($description->exists('FRIENDLYTITLE'))
					{
						$friendlyNode = $description->getElement('FRIENDLYTITLE');
						$friendlyNode->setValue($friendlytitle);
					}
					else
					{
						$description->appendChild('<FRIENDLYTITLE>'.encode_to_xml($friendlytitle).'</FRIENDLYTITLE>');
					}
				}
			}
			return new SusheeProcessorMessage('Friendly title is `'.$msg.'`');
		}
		return new SusheeProcessorMessage('No new friendly title generated');
	}
	
	function postprocess($data){
		return true;
	}
}

class sushee_CREATE_MEDIA_processor extends sushee_friendlyTitleBuilder{
	
}

class sushee_UPDATE_MEDIA_processor extends sushee_friendlyTitleBuilder{
	
}


?>