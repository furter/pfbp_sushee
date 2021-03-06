<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/media_preprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

if ( $requestName=="UPDATE" || $requestName=="CREATE"){
	// we transform the textual mediatype in a mediatypeID
	if (isset($values["MediaTypeID"])){
		$mediatypeID=$values["MediaTypeID"];
		$sql = "SELECT * FROM mediatypes WHERE ID=\"".$mediatypeID."\";";
		$row = $db_conn->GetRow($sql);
		if ($row){
			// if there is a correspondence
			$values["MediaType"]=$row["MediaKind"];
		}
	}else if (isset($values["MediaType"])){
		$sql = "SELECT * FROM mediatypes WHERE  MediaKind=\"".$values["MediaType"]."\";";
		$row = $db_conn->GetRow($sql);
		if ($row)
			$values["MediaTypeID"]=$row["ID"];
	}
}
return TRUE;
?>
