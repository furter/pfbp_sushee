<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createPDF.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function createPDF($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	
	$origin_xml = $xml->toString($firstNodePath.'/QUERY');
	$short_template = $xml->getData($firstNodePath.'/@template');
	if(!$short_template)
		return generateMsgXML(1,"You didn't indicate any template",0,false,$name);
	$template = $GLOBALS["nectil_dir"].$short_template;
	if(!file_exists($template))
		return generateMsgXML(1,"The template $short_template indicated doesn't exist",0,false,$name);
	
	$result = query($origin_xml,FALSE,TRUE,TRUE);
	//die($result);
	$filename = transform_to_pdf($result,$template,false);
	
	if($filename === false)
		$query_result = generateMsgXML(1,"Generation of PDF failed for unknown reason",0,false,$name);
	else{
		if(substr($filename,0,strlen($GLOBALS["directoryRoot"]))==$GLOBALS["directoryRoot"] )
			$filename = substr($filename,strlen($GLOBALS["directoryRoot"]));
		if ($name)
			$attributes.=" name='$name'";
		$external_file = $xml->getData($current_path.'/@fromFile');
		if($external_file)
			$attributes.=" fromFile='".$external_file."'";
		$query_result='<RESULTS'.$attributes.'>';
		$query_result.= '<PDF>'.$filename.'</PDF></RESULTS>';
	}
	return $query_result;
}
?>
