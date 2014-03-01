<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/group_preprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
// manage Favorites 
if ( $xml->nodeName($current_path)=="CREATE" || $xml->nodeName($current_path)=="UPDATE" ){
	if ($values["IsFavorite"]=="1"){
		if($values['OwnerID']==$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'] ){
			$specific_sql = "UPDATE `groups` SET `IsFavorite`=0 WHERE `OwnerID`=".$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'].";";
			$db_conn->Execute($specific_sql);
		}else
			$values["IsFavorite"]="0";
	}
}
return TRUE;
?>
