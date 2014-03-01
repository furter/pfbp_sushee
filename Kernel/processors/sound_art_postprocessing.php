<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/sound_art_postprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
if ( $requestName=="CREATE" /*|| $xml->nodeName($current_path)=="UPDATE"*/){
	$suppl_info.='AlbumID="'.$values['AlbumID'].'"';
}
if ( $requestName=="CREATE" || $requestName=="UPDATE"){
	// ajouter categorie KUR(30) et BAB(29) sur les dependances de ce type
	
	$babs = $xml->match($firstNodePath."/DEPENDENCIES/DEPENDENCY[@type='sound_artBabbeleer']/CONTACT");
	$kurs = $xml->match($firstNodePath."/DEPENDENCIES/DEPENDENCY[@type='sound_artKurieuzeneus']/CONTACT");
	foreach($babs as $babs_path){
		$babID = $xml->getData($babs_path."/@ID");
		if($babID){
			$bab_categ_insert = "INSERT INTO `categorylinks` ( `CategoryID` , `ModuleTargetID` , `TargetID` ) VALUES ('29', '1', '$babID');";
			$db_conn->Execute($bab_categ_insert);
		}
	}
	foreach($kurs as $kurs_path){
		$kurID = $xml->getData($kurs_path."/@ID");
		if($kurID){
			$kur_categ_insert = "INSERT INTO `categorylinks` ( `CategoryID` , `ModuleTargetID` , `TargetID` ) VALUES ('30', '1', '$kurID');";
			$db_conn->Execute($kur_categ_insert);
		}
	}
}
return TRUE;
?>
