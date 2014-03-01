<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/filter.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
class MediaFilesImportFilter{
	function execute(/* String */ $value){
		if($value){
			if(substr($value,0,5)==='<CSS>'){
				$offset = 0;
				while( ( $startOfFileURL = strpos($value,'[files_url]',$offset))!==FALSE){
					$startOfFileURL+=11;
					$endOfFileURL = strpos($value,'"',$startOfFileURL);
					if($endOfFileURL!==FALSE){
						$FileURLlength = $endOfFileURL-$startOfFileURL;
						$file = substr($value, $startOfFileURL,$FileURLlength);
						$importName = $this->ImportFile($file);
						$value=substr($value,0,$startOfFileURL).encode_to_XML($importName).substr($value,$endOfFileURL);
					
					}else 
						break;
					$offset = $endOfFileURL;
				}
			}else{
				global $directoryRoot;
				if(substr($value,0,1)=='/' && file_exists($directoryRoot.$value))
					$value = $this->ImportFile($value);
			}
		}
		return $value;
	}
	function importFile($file){
		include_once(dirname(__FILE__)."/../file/file_config.inc.php");
		include_once(dirname(__FILE__)."/../file/file_functions.inc.php");
		global $directoryRoot;
		if(trim($file)=='' || trim($file)=='/')
			return false;
		$file = transformPath($file);
		if (file_exists($directoryRoot.$file) && substr($file,0,7)!='/media/'){
			$path_array = explode('/',$file);
			if(is_dir("$directoryRoot$file")){
				$filename = $path_array[count($path_array)-2];
				$is_dir = true;
			}else{
				$filename = $path_array[count($path_array)-1];
				$ext = getFileExt($filename);
				if($ext)
					$ext='.'.$ext;
				$is_dir = false;
				$filename = getFilenameWithoutExt($filename);
			}

			$count = "";
			$import_path = "$directoryRoot/media/imports/";
			if (!is_dir($directoryRoot."/media/imports"))
				makedir($directoryRoot."/media/imports");
			// we try to find a filename not yet used
			$orig_filesize = filesize("$directoryRoot$file");
			$file_exists = file_exists("$import_path$filename$count$ext");
			if ($file_exists && !$is_dir){
				$import_filesize = filesize("$import_path$filename$count$ext");
				$samefile = ($import_filesize==$orig_filesize);
			}else if($file_exists && $is_dir){
				$samefile = compare_content("$directoryRoot$file","$import_path$filename$count$ext");
			}

			while( $file_exists && !$samefile ){
				$count++;
				$file_exists = file_exists("$import_path$filename$count$ext");
				if ($file_exists && !$is_dir){
					$import_filesize = filesize("$import_path$filename$count$ext");
					$samefile = ($import_filesize==$orig_filesize);
				}else if($file_exists && $is_dir){
					$samefile = compare_content("$directoryRoot$file","$import_path$filename$count$ext");
				}
			}
			// if a file with the same name exists and has the same size, we keep that one : must be the same file
			if ($import_filesize!=$orig_filesize && !$is_dir){
				copy("$directoryRoot$file","$import_path$filename$count$ext");
			}
			if($is_dir){
				$samefile = compare_content("$directoryRoot$file","$import_path$filename$count$ext");
				if(!$samefile){
					makeDir("$import_path$filename$count$ext");
					copy_content("$directoryRoot$file","$import_path$filename$count$ext");
				}
				return "/media/imports/$filename$count$ext/";
			}
			return "/media/imports/$filename$count$ext";
		}else
			return $file;
	}
}

class SimpleFilter{
	function execute($value){}
}

class MultiFilter{
	
	function push($value){
	}
	
	function execute(){
	}
}

class FilesFilter extends MultiFilter{
	var $vector;
	
	function FilesFilter(){
		$this->vector = new Vector();
	}
	
	function push($value){
		if($value){
			if(substr($value,0,5)==='<CSS>'){
				$offset = 0;
				while( ( $startOfFileURL = strpos($value,'[files_url]',$offset))!==FALSE){
					$startOfFileURL+=11;
					$endOfFileURL = strpos($value,'"',$startOfFileURL);
					if($endOfFileURL!==FALSE){
						$FileURLlength = $endOfFileURL-$startOfFileURL;
						$file = substr($value, $startOfFileURL,$FileURLlength);
						$this->vector->add($file,$file); // an entry with the path to the file
					}else 
						break;
					$offset = $endOfFileURL;
				}
			}else{
				global $directoryRoot;
				if(file_exists($directoryRoot.$value))
					$this->vector->add($value,$value);
			}
		}
		return $this->vector;
	}
	
}

class FilesFulltextFilter extends FilesFilter{
	var $vector;
	
	function execute(){
		while($filepath = $this->vector->next()){
			$complete_filepath = $GLOBALS['directoryRoot'].$filepath;
			$ext = getFileExt($complete_filepath);
			$filecontent = '';
			switch($ext){
				case 'pdf':
					include_once(dirname(__FILE__)."/../common/pdf_to_text.class.php");
					$pdf_reader = new pdf_to_text($complete_filepath);
					if($pdf_reader->isLoaded){
						$filecontent=utf8_to_unicodeentities($pdf_reader->getString());
					}
					break;
				case 'xls':
					break;
				case 'doc':
					break;
				case 'txt':
					$filecontent = file_in_string($complete_filepath);
					break;
				case 'xml':
					$template = realpath(dirname(__FILE__).'/../templates/xml_to_fulltext.xsl');
					$xml = file_in_string($complete_filepath);
					$xml = str_replace('&nbsp;','&#160;',$xml); // in case its an excel file
					if(strpos($xml,'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"')!==false){
						$template = realpath(dirname(__FILE__).'/../templates/excel_to_fulltext.xsl');
					}
					$transform_config = array('xml'=>$xml,'template'=>$template,'more_params'=>array(),'html_on_error'=>false,'use_libxslt'=>true);
					$filecontent = utf8_to_unicodeentities(nectil_xslt_transform($transform_config));
					break;
				default:
			}
			$all_file_contents.=$filecontent;
		}
		return $all_file_contents;
	}
	
}

class Url2AnchorFilter extends SimpleFilter{
	function execute($text){
		// match protocol://address/path/file.extension?some=variable&another=asf%
		$pattern  = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
		   $callback = create_function('$matches', '
		       $url       = array_shift($matches);
		       $url_parts = parse_url($url);

		       $text = $url;
		       
				if(substr($url,0,4)=="www."){
					$url="http://".$url;
				}

		       return sprintf(\'<a rel="nofollow" href="%s">%s</a>\', $url, $text);
		   ');

		   return preg_replace_callback($pattern, $callback, $text);
	}
}


?>