<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateMedialanguages.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function updateMedialanguages($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$db_conn = db_connect();
	
	$current_path = $current_path."/*[1]";
	
	
	// first removing the currently published languages
	$sql = 'DELETE FROM medialanguages;';
	$db_conn->Execute($sql);
	
	$languages_array = $xml->match($current_path."/LANGUAGE");
	$priority = 2;
	foreach($languages_array as $language_path){
		$ID = $xml->getData($language_path.'/@ID');
		$published = $xml->getData($language_path.'/PUBLISHED');
		$default = $xml->getData($language_path.'/@defaultLanguage');
		if($default==='true')
			$this_priority = 1;
		else
			$this_priority = $priority;
		$sql = 'INSERT INTO medialanguages(languageID,priority,published) VALUES("'.$ID.'",'.$this_priority.','.$published.');';
		$db_conn->Execute($sql);
		$priority++;
	}
	return generateMsgXML(0,"Published languages have been updated",0,'',$name);
}
?>
