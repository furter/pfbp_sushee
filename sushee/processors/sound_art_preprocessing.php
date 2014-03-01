<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/sound_art_preprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
if ( $xml->nodeName($current_path)=="CREATE" ){
	if (!$former_values["AlbumID"] && !$values["AlbumID"] && $values['Type']=='album'){
		$albumid_sql = "SELECT MAX(AlbumID) AS maximum FROM `".$moduleInfo->tableName."` WHERE Type=\"album\" AND Activity=1;";  
		$row = $db_conn->GetRow($albumid_sql);
		$values['AlbumID']=$row['maximum']+1;
	}
}
return TRUE;
?>
