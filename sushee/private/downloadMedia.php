<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/downloadMedia.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");

require_once(dirname(__FILE__)."/../private/checkLogged.inc.php");
session_write_close();
if(isset($GLOBALS['residentPublished']) && $GLOBALS['residentPublished']==0)
	die('Your resident is not published, therefore Media export is disabled.');
if (isset($_GET['ID']) || isset($_GET['mediatype'])){
	if (!function_exists('saveInFile')){
		function saveInFile($msg,$filename){ 
			// open file
			$fd = fopen($filename, "w+");
			// write string
			fwrite($fd,$msg);
			// close file
			fclose($fd);
		}
	}
	if (!function_exists('copy_content')){
		function copy_content($source_dir,$target_dir){
			if ($source_dir=='' || $target_dir=='')
				return FALSE;
			$source_dir = realpath($source_dir)."/";
			$target_dir = realpath($target_dir)."/";
			if (!file_exists($source_dir) || !file_exists($target_dir))
				return FALSE;
			if ($dir = @opendir($source_dir)) {
				while($file = readdir($dir)) {
					//echo $file."<br/>";
					$isFileVisible = true;
					if($file == "." || $file == ".." )
						$isFileVisible = false;
					if ($isFileVisible){
						if (is_dir($source_dir.$file)){
							makeDir($target_dir.$file);
							copy_content($source_dir.$file,$target_dir.$file);
						}else
							copy($source_dir.$file,$target_dir.$file);
					}
				}
			}else
				return FALSE;
		}
	}
	function copy_published_content($source_dir,$target_dir,&$published_files){
		$source_dir = realpath($source_dir)."/";
		$target_dir = realpath($target_dir)."/";
		if (!file_exists($source_dir) || !file_exists($target_dir))
			return FALSE;
		if ($dir = @opendir($source_dir)) {
			while($file = readdir($dir)) {
				$isFileVisible = true;
				if($file == "." || $file == ".." )
					$isFileVisible = false;
				if ($isFileVisible){
					if (is_dir($source_dir.$file)){
						$copy = false;
						// checking at least one of the published files is in the dir
						$short_dir = getShortPath($source_dir.$file);
						$short_dir_sz = strlen($short_dir);
						foreach($published_files as $pub_file){
							if ($short_dir==substr($pub_file,0,$short_dir_sz)){
								$copy=true;
								break;
							}
						}
						if ($copy){
							makeDir($target_dir.$file);
							copy_published_content($source_dir.$file,$target_dir.$file,$published_files);
						}
					}else{
						if ( in_array (getShortPath($source_dir.$file), $published_files))
							copy($source_dir.$file,$target_dir.$file);
					}
				}
			}
		}else
			return FALSE;
	}
	
	if (isset($_GET['ID'])){
		$sql = 'SELECT Mediatype FROM `medias` WHERE ID='.$_GET['ID'].';';
		$db_conn = db_connect();
		$row = $db_conn->GetRow($sql);
	}
	if (isset($_GET['ID']) && $row['Mediatype']!=''){
		$suffix = strtolower($row['Mediatype']);
		$mediatype=$row['Mediatype'];
	}else if(isset($_GET['mediatype'])){
		$suffix = strtolower($_GET['mediatype']);
		$mediatype=$_GET['mediatype'];
	}else{
		$suffix = 'media';
		$mediatype = $suffix;
	}
	
	$tmp_dir = realpath($directoryRoot."/tmp").'/'.date('YmdHis');
	if (isset($_GET['data']) && $_GET['data']==='1'){
		$data_only = true;
		$data_dir = $tmp_dir."/data";
		$zip_dir = $data_dir;
	}else{
		$data_only = false;
		$slideshow_dir = $tmp_dir.'/'.$mediatype/*"/Slideshow"*/;
		$data_dir = $slideshow_dir."/data";
		$zip_dir = $slideshow_dir;
	}
	$files_dir = $data_dir."/Files";
	$css_dir = $data_dir."/Library/media/css";
	makeDir($data_dir); // creates /Slideshow if necessary
	if ($_GET['files']!=='false')
		makeDir($files_dir);
	makeDir($css_dir);
	$result1 = query('<QUERY><GET><LANGUAGES profile="Media"/></GET></QUERY>',FALSE,FALSE,FALSE,FALSE);
	saveInFile($result1,$data_dir."/languages.xml");
	$languages_xml = new XML($result1);
	$lg_array = $languages_xml->match('/RESPONSE/RESULTS/LANGUAGE');
	if (isset($_GET['languageID'])){
		foreach($lg_array as $path){
			$xxx = $languages_xml->getData($path.'/@ID');
			if ($xxx!=$_GET['languageID'])
				$languages_xml->removeChild($path);
		}
		$lg_array = $languages_xml->match('/RESPONSE/RESULTS/LANGUAGE');
		saveInFile($languages_xml->toString(),$data_dir."/languages.xml");
	}
	$GLOBALS['used_Files']=array();
	if (isset($_GET['output']))
		$output = ' output="'.$_GET['output'].'" ';
	if (!isset($_GET['depth']))
		$_GET['depth']='all';
	
	// determining what is to be included
	$with='';
	if ($_GET['info']==='false')
		$with.='<INFO get="false"/>';
	else
		$with.='<INFO get="true"><MEDIATYPE/><DENOMINATION/><TIMEIN/><TIMEOUT/><EVENTSTART/><EVENTEND/></INFO>';
	if ($_GET['comments']==='true')
		$with.='<COMMENTS get="true"/>';
	if ($_GET['dependencies']==='false')
		$with.='<DEPENDENCIES get="false"/>';
	if ($_GET['categories']==='true')
		$with.='<CATEGORIES get="true"/>';
	if (isset($_GET['ID'])){
		$opening_search_query = '<GET name="media" refresh="live"><MEDIA ID="'.$_GET['ID'].'">';
		$closing_search_query = '</MEDIA></GET>';
		/*$denom_sql = 'SELECT Denomination FROM medias WHERE ID='.$_GET['ID'];
		$db_conn = db_connect();
		$media_row = $db_conn->GetRow($denom_sql);
		$final_name = substr($media_row['Denomination'],0,36);*/
		$final_name = 'media'.$_GET['ID'];
	}else if(isset($_GET['mediatype'])){
		$opening_search_query = '<SEARCH name="media" refresh="live"><MEDIA mediatype="'.$_GET['mediatype'].'">';
		$closing_search_query = '</MEDIA></SEARCH>';
		$final_name = $_GET['mediatype'];
	}
	if($_GET['output']=='indesign')
		$desc_profile='content';
	else
		$desc_profile='full';
	if ($_GET['onefile']!=='true'){
		$include_file = $GLOBALS["Public_dir"].$mediatype.'/configuration/includes.nql';
		if(file_exists($include_file))
			$include_command = '<INCLUDE file="../../Public/'.$mediatype.'/configuration/includes.nql"/>';
		foreach($lg_array as $path){
			$xxx = $languages_xml->getData($path.'/@ID');
			$resultxxx = query('<QUERY languageID="'.$xxx.'">'.$include_command.$opening_search_query.'<WITH depth="'.$_GET['depth'].'"><DESCRIPTIONS '.$output.' profile="'.$desc_profile.'"/>'.$with.'</WITH>'.$closing_search_query.'</QUERY>',false,true,false,false);
			saveInFile($resultxxx,$data_dir."/".$suffix."_$xxx.xml");
		}
	}else if ($_GET['onefile']==='true'){
		$query = '<QUERY>'.$opening_search_query.'<WITH depth="'.$_GET['depth'].'"><DESCRIPTIONS '.$output.' profile="'.$desc_profile.'"/>'.$with.'</WITH>'.$closing_search_query.'</QUERY>';
		//debug_log($query);
		$result = query($query,FALSE,FALSE,FALSE,FALSE);
		saveInFile($result,$data_dir."/".$suffix."_multi.xml");
	}
	global $directoryRoot;
	if ($_GET['files']!=='false')
		copy_published_content($directoryRoot,$files_dir,$GLOBALS['used_Files']);
	if (file_exists($GLOBALS["library_dir"]."media/css/".$suffix.".css"))
		copy($GLOBALS["library_dir"]."media/css/".$suffix.".css",$css_dir."/".$suffix.".css");
	if (file_exists($GLOBALS["library_dir"]."media/css/default.css"))
		copy($GLOBALS["library_dir"]."media/css/default.css",$css_dir."/default.css");
	if (!$data_only){
		if (file_exists($GLOBALS["nectil_dir"]."/Public/$mediatype/"))
			copy_content($GLOBALS["nectil_dir"]."/Public/$mediatype/",$slideshow_dir);
	}
	$zip_location = $tmp_dir."/media.zip";
	zip($zip_dir,$zip_location);
	setDownloadHeaders($final_name.".zip");//,filesize($zip_location)
	$fp = fopen($zip_location, 'rb');
	while (!feof ($fp)) {
		print(fread($fp, 1024*8));
		flush();
	}
	fclose ($fp);
	killDirectory($tmp_dir);
}else
	xml_out("<?xml version='1.0'?><RESPONSE>".generateMsgXML(1,"No media ID was given.")."</RESPONSE>");

?>
