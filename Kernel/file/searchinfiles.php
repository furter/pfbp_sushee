<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/searchinfiles.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/XML.class.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");
require_once(dirname(__FILE__)."/../common/get_xml.inc.php");

@set_time_limit(0);
session_write_close();

// checking the request is "signed" -> must have a userID
$userID = $xml->getData("/QUERY/@userID");
if ( $userID==FALSE ){
    die( xml_msg("1","0","0","XML request invalid."));
}
if ( !isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) ){
    die( xml_msg("1","0","0","You're not logged : your session must have expired."));
}

$searchElement = $xml->getData('/QUERY/SEARCH[1]');
if( $searchElement===FALSE ){
   die( xml_msg("1","-1","-1","XML request invalid : No SEARCH section"));
}
$target = decode_from_XML($xml->getData('/QUERY/SEARCH[1]/TARGET[1]'));
if( $target===FALSE )
	die( xml_msg("1","-1","-1","XML request invalid : No TARGET section"));
    
$general = decode_from_XML($xml->getData('/QUERY/SEARCH[1]/GENERAL[1]'));
if( $general===FALSE )
	die( xml_msg("1","-1","-1","XML request invalid : No GENERAL section"));

$typeRequest = decode_from_XML($xml->getData('/QUERY/SEARCH[1]/TYPE[1]'));
if( $typeRequest===FALSE )
	die( xml_msg("1","-1","-1","XML request invalid : No TYPE section:"));

//security and prerequisities
$target = transformPath(unhtmlentities($target));
$path_array= explode("/",$target);
// we call that function because it creates the directory if necessary
getPathSecurityRight($target);
$path=substr($target, 0, -1);

//$type  need to be used 

if($general  != "" ) {
	$matchArray=explode(' ',$general);
	$fileArray = array();
	findfile($path,$matchArray, $directoryRoot,$fileArray);
}

$fileList='';
$directoryList='';
/* Build the table rows which contain the file information */
if ( is_array($fileArray) && count($fileArray) > 0) {
       /* loop once for each name in the array */
       //$fc=0;
       foreach($fileArray as $file){
            $isFileVisible=true; 
            //if this file is hidden, do not show it
            if (!hidecheck($file)) { $isFileVisible=false; }
            
            // if there were no matches the file should not be hidden
            if($isFileVisible) {
                
				$file_info = getFileXML($directoryRoot.$file,$type);
                if (is_dir($directoryRoot.$file)) {
                    if($typeRequest != 'files'){
						$directoryList.=$file_info;
                    } 
                }else {
                   if($typeRequest !='directory'){
						$fileList.=$file_info;
                    }
                }
            }
       }
}


$strResponse='<TREE>';
	
$strResponse.=$fileList.$directoryList.'</TREE>';

//xml_out($strRet); 
//echo $strRet;
include_once(dirname(__FILE__)."/../common/output_xml.inc.php");
?>