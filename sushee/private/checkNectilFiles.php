<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/checkNectilFiles.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once("../common/common_functions.inc.php");

$base_array = array("ID","LanguageID","CreatorID","ModifierID","URL","Header","Title",'Custom',"Body","Summary","Signature","Biblio","Copyright");

$db_conn = db_connect();

$sql = 'SELECT * FROM descriptions_custom;';
$rs = $db_conn->Execute($sql);
global $directoryRoot;
$missing = array();
$ok = array();
$index_missing = 0;
$index_ok = 0;
while($row = $rs->FetchRow()){
	$filepath = $row['Value'];
	if (substr($filepath,0,1)=="/"){ // it's a file
		
		if (file_exists($directoryRoot.$filepath)){
			//echo $filepath." OK<br/>";
			$ok[$filepath]=array("descriptionid"=>$row['DescriptionID'],"field"=>'CUSTOM');
			$index_ok++;
		}else{
			//echo "<b>".$filepath." MISSING</b><br/>";
			$missing[$filepath]=array("descriptionid"=>$row['DescriptionID'],"title"=>$row["Title"],"height"=>$height,"width"=>$width,"field"=>'CUSTOM');
			$index_missing++;
		}
	}
}
$sql = 'SELECT * FROM descriptions;';
$rs = $db_conn->Execute($sql);
while($row = $rs->FetchRow()){
	// other fields
	foreach($base_array as $row_name){
		//echo "$row_name<br/>";
		$value = $row[$row_name];
		//echo $value."<br/>";
		$file_url_pos = strpos($value,'"[files_url]');
		while($file_url_pos!==false){
			$file_path_end = strpos($value,'"',$file_url_pos+12);
			if($file_path_end){
				$file = substr($value,$file_url_pos+12,$file_path_end-$file_url_pos-12);
				//$file = transformPath($file);
				if (file_exists("$directoryRoot$file")){
					//echo $file." OK<br/>";
					$ok[$filepath]=array("descriptionid"=>$row['ID'],"field"=>'CUSTOM');
					$index_ok++;
				}else{
					//echo "<b>".$file." MISSING</b><br/>";
					$missing[$file]=array("descriptionid"=>$row['ID'],"title"=>$row["Title"],"field"=>$row_name);
					$index_missing++;
				}
			}
			$file_url_pos = strpos($value,'"[files_url]',$file_url_pos+12);
		}
	}
	/*if($index > 5)
		break;*/
}
foreach($missing as $key=>$value){
	//$simple_filename = BaseFilename($key);
	if($key!='undefined'){
		echo '<b>'.$key.'</b>';
		if($_GET['more']=='true')
			echo " in field <em>".$value['field']."</em> \"-".$value['descriptionid']." ";
		echo "<br/>";
	}
}
echo "<br/>";
echo sizeof($missing)." MISSING<br/>";
echo sizeof($ok)." OK<br/>";

?>
