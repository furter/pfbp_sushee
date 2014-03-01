<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createXLIFF.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
require_once(dirname(__FILE__)."/../common/descriptions.inc.php");

function copy_translated_content($source_dir,$target_dir,&$published_files,$rename_to,&$skipped_files,$skip_big_files = true){
	$source_dir = realpath($source_dir)."/";
	$target_dir = realpath($target_dir)."/";
	if (!file_exists($source_dir) || !file_exists($target_dir))
		return FALSE;
	if ($dir = @opendir($source_dir)) {
		$short_dir_path = getShortPath($source_dir);
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
						copy_translated_content($source_dir.$file,$target_dir.$file,$published_files,$rename_to,$skipped_files);
					}
				}else{
					$short_path = getShortPath($source_dir.$file);
					if ( in_array ($short_path, $published_files) || in_array ($short_dir_path, $published_files)){
						if(filesize($source_dir.$file)>10240000 && $skip_big_files)
							$skipped_files[]=$short_path;
						else{
							if(isset($rename_to[$short_path]))
								$new_file = $rename_to[$short_path];
							else
								$new_file = $file;
							debug_log('copying '.$source_dir.$file.' to '.$target_dir.$new_file);
							copy($source_dir.$file,$target_dir.$new_file);
						}
					}
				}
			}
		}
	}else
		return FALSE;
}

function createXLIFF($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	
	$search_nodes = $xml->match($firstNodePath.'/QUERY/SEARCH | '.$firstNodePath.'/QUERY/GET');
	$languageID = $xml->getData($firstNodePath.'/@languageID');
	if(!$languageID){
		$languageID = $xml->getData($firstNodePath.'/@destination');
		if(!$languageID)
			return generateMsgXML(1,"No language provided for translation",0,false,$name);
	}
	$db_conn = db_connect();
	$source = $xml->getData($firstNodePath.'/@source');
	if(!$source){
		$sql = "SELECT languageID FROM medialanguages WHERE priority=1;";
		$row = $db_conn->GetRow($sql);
		$defLgID = $row['languageID'];
	}else
		$defLgID = $source;
		
	$include_preview = $xml->getData($firstNodePath.'/@include-preview');
	if($include_preview=='false')
		$preview_xliff = false;
	else
		$preview_xliff = true;
	$config['preview_xliff'] = $preview_xliff;
	$includeFiles = $xml->getData($firstNodePath.'/@include-files');
	if($includeFiles==='false')
		$includeFiles = false;
	else
		$includeFiles = true;
	$includeLibrary = $xml->getData($firstNodePath.'/@include-library');
	if($includeLibrary==='false')
		$includeLibrary = false;
	else
		$includeLibrary = true;
	$copy_original = $xml->getData($firstNodePath.'/@copy-original');
	if($copy_original==='false')
		$copy_original = false;
	else
		$copy_original = true;
	$sourceFolder = $xml->getData($firstNodePath.'/@source-folder');
	global $directoryRoot;
	//preparing the folder structure
	$now = date('YmdHis');
	//$now = 'xliff';
	$tmp_dir = realpath($directoryRoot."/tmp").'/'.$now;
	if(!$sourceFolder){
		$root_dir = $tmp_dir."/Archive";
		$data_dir = $tmp_dir."/Archive/data";
		$zip_dir = $data_dir;
	}else{
		$sourceDirname = basename($GLOBALS["nectil_dir"].$sourceFolder);
		$root_dir = $tmp_dir."/$sourceDirname";
		$data_dir = $tmp_dir."/$sourceDirname/data";
		$zip_dir = $tmp_dir."/$sourceDirname";
	}
	$files_dir = $data_dir."/Files";
	$css_dir = $data_dir."/Library/media/css";
	makeDir($data_dir);
	if($includeFiles){
		makeDir($files_dir);
		$used_Files = array();
		$rename_to = array();
	}
	if($includeLibrary)
		makeDir($css_dir);
	$prefix = 'data';
	
	$result1 = query('<QUERY><GET><LANGUAGES profile="Media"/></GET></QUERY>',FALSE,FALSE,FALSE,FALSE);
	$languages_xml = new XML($result1);
	$lg_array = $languages_xml->match('/RESPONSE/RESULTS/LANGUAGE');
	for($i=sizeof($lg_array)-1;$i>=0;$i--){
		$path = $lg_array[$i];
		$xxx = $languages_xml->getData($path.'/@ID');
		if ($xxx!=$languageID){
			$languages_xml->removeChild($path);
		}
	}
	if($preview_xliff)
		saveInFile($languages_xml->toString(),$data_dir."/languages.xml");
	
	$row = getLanguageInfo($defLgID);
	$config_lg['srcLg']=$row['ISO1'];
	$config_lg['srcLgID']=$languageID;
	$row = getLanguageInfo($languageID);
	$config_lg['destLg']=$row['ISO1'];
	if($config_lg['destLg']!=$config_lg['srcLg'])
		$config_lg['clone_files']= true;
	else
		$config_lg['clone_files']= false;
	$sep = '_';
	$config_lg['sep']=$sep;
	$config_lg['eol']="\r\n";
	$config_lg['copy_original']=$copy_original;
	$elements = array();
	if($preview_xliff){
		//$result_sets = array();
		$depths = array();
	}
	//simulating a request from the website
	$GLOBALS["php_request"] = true;
	foreach($search_nodes as $search_path){
		$searchFirstNode = $xml->nodeName($search_path.'/*[1]');
		if($searchFirstNode=='LABELS'){
			$sql = 'SELECT * FROM `labels` WHERE LanguageID="'.$defLgID.'";';
			$rs = $db_conn->Execute($sql);
			if ($rs){
				while($row = $rs->FetchRow()){
					$transUnitID='label'.$row['Denomination'];
					$target = '';
					if($config_lg['copy_original'])
						$target = $row['Text'];
					$xliff_body.='<trans-unit id="'.$transUnitID.'"><source xml:lang="'.$config_lg['srcLg'].'">'.$row['Text'].'</source><target state="new" xml:lang="'.$config_lg['destLg'].'">'.$target.'</target></trans-unit>';
					$original.='<UPDATE><LABEL languageID="'.$languageID.'" name="'.$row['Denomination'].'">%%%'.$transUnitID.'%%%'.$config_lg['eol'].'</LABEL></UPDATE>';
				}
			}
		}elseif($searchFirstNode=='CATEGORY' || $searchFirstNode=='CATEGORIES'){
			// using XSL to generate the xlf and skl
			// 2 transformations : one for future NQL requests and one for the XLIFF file
			$NQL = 
			'<QUERY>
				<XLIFF-CONFIG static="true">
					<source-language ID="'.$defLgID.'" ISO1="'.$config_lg['srcLg'].'"/>
					<destination-language ID="'.$languageID.'" ISO1="'.$config_lg['destLg'].'"/>
				</XLIFF-CONFIG>
				'.$xml->toString($search_path,'').'
			</QUERY>';
			$result = request($NQL,false,true,false,false,false,false,false,false);
			$template = realpath(dirname(__FILE__).'/../templates/categories_to_xlf.xsl');
			$transform_config = array('xml'=>$result,'template'=>$template,'more_params'=>array(),'html_on_error'=>false,'use_libxslt'=>true);
			$xliff_body.=nectil_xslt_transform($transform_config);
			$template = realpath(dirname(__FILE__).'/../templates/categories_to_skl.xsl');
			$transform_config['template'] = $template;
			$original.=nectil_xslt_transform($transform_config);
		}else{
			$searchFirstNodePath = $search_path.'/*[1]';
			$moduleName  = $searchFirstNode;
			$moduleInfo = moduleInfo($moduleName);
			$possible_depth = $xml->getData($searchFirstNodePath.'/WITH[1]/@depth');
			if (is_numeric($possible_depth))
				$depth = (int)$possible_depth;
			else if($possible_depth=='all')
				$depth = 'all';
			else 
				$depth = 1;
			$sql="";
			
			$config = array('depth'=>$depth,'moduleInfo'=>$moduleInfo,'search'=>$search_path);
			
			$rs = getResultSet($moduleInfo,$xml,$search_path,$sql);
			if($preview_xliff){
				//$result_sets[$search_path]=&$rs;
				$depths[$search_path]=$depth;
			}
			if($rs && !is_string($rs)){
				while($row = $rs->FetchRow()){
					findXLIFFelements($elements,$row,$config);
				}
			}else
				return generateMsgXML(1,"One of the queries is invalid",0,false,$name);
		}
	}
	$profile_array = getDescriptionProfileArray('templateCSV');
	$upperProfileArray = array();
	$profile_size = count($profile_array);
	foreach($profile_array as $value){
		$upperProfileArray[]=strtoupper($value);
	}
	
	$index = 1;
	global $directoryRoot;
	
	foreach($elements as $key=>$prefs){
		$ID = $prefs['ID'];
		$moduleTargetID = $prefs['ModuleTargetID'];
		$parts = explode('_',$key);
		$moduleName=strtoupper($parts[0]);
		$sql = "SELECT TargetID,".implode(",",$profile_array)." FROM descriptions WHERE Status=\"published\" AND ModuleTargetID=$moduleTargetID AND TargetID='".$ID."' AND LanguageID=\"".$defLgID."\" ";
		$desc = $db_conn->GetRow($sql);
		
		if($desc){
			$element_desc='<DESCRIPTION><LANGUAGEID>'.$languageID.'</LANGUAGEID>';
			$this_xliff_body='';
			for($i=0;$i<$profile_size;$i++){
				$n=$profile_array[$i];
				$value=$desc[$n];
				$config_lg['context']=$n;
				$n=$upperProfileArray[$i];
				if($value && $n!='STATUS'){
					$new_value = '';
					$body = '';
					if($n!='CUSTOM'){
						if(substr($value,0,5)=='<CSS>'){
							$css_xml = new XML($value);
							$result_array = CSStoXLIFF($css_xml,'/CSS',$ID.$sep.($i+1),$config_lg,$used_Files,$rename_to);
							$new_value = $result_array['original'];
							$body.= $result_array['xliff'];
						}else if(is_numeric($value)){
							$new_value = $value;
						}else{
							$result_array = PlaintexttoXLIFF($value,$ID.$sep.($i+1),$config_lg);
							$new_value = $result_array['original'];
							$body.= $result_array['xliff'];
						}
					}else{
						$custom_xml = new XML('<CUSTOM>'.$value.'</CUSTOM>');
						$childs = $custom_xml->match("/CUSTOM/*");
						$j=0;
						foreach($childs as $child_path){
							$j++;
							$childnodeName = $custom_xml->nodeName($child_path);
							$firstchildNodename = $custom_xml->nodeName($child_path.'/*[1]');
							$custom_value = $custom_xml->getData($child_path);
							if(substr($custom_value,0,1)=='/' && file_exists($directoryRoot.$custom_value)){
								$ext = getFileExt($custom_value);
								$images_ext = array('jpg','jpeg','gif','png','bmp','jpe','swf');
								$size = false;
								$size_attributes = '';
								if(in_array ( strtolower($ext), $images_ext) && filesize("$directoryRoot$custom_value")<2048000){
									$size = @getimagesize("$directoryRoot$custom_value");
									if($size){
										$height = $size[1];
										$width = $size[0];
										$size_attributes = ' width="'.$width.'" height="'.$height.'"';
									}
								}
								$basic_name = getFilenameWithoutExt($custom_value);
								$orig_file = $custom_value;
								if($config_lg['clone_files']){
									if($basic_name == $custom_value){
										$custom_value = $basic_name.'_'.$languageID;
										$suffix = 0;
										// if the file already exists and has not the same size, we suffix it to avoid overwrite
										while(file_exists("$directoryRoot$custom_value") && filesize("$directoryRoot$custom_value")!=filesize("$directoryRoot$orig_file")){
											$suffix++;
											$custom_value = $basic_name.'_'.$languageID.$suffix;
										}
									}else{
										$custom_value = $basic_name.'_'.$languageID.'.'.$ext;
										// if the file already exists and has not the same size, we suffix it to avoid overwrite
										$suffix = 0;
										while(file_exists("$directoryRoot$custom_value") && filesize("$directoryRoot$custom_value")!=filesize("$directoryRoot$orig_file")){
											$suffix++;
											$custom_value = $basic_name.'_'.$languageID.$suffix.'.'.$ext;
										}
									}
								}
								$custom_field_attributes  = AttributesToString($custom_xml,$child_path,array('height','width'));
								if($custom_field_attributes)
									$custom_field_attributes = ' '.$custom_field_attributes;
								$new_value.= '<'.$childnodeName.$size_attributes.$custom_field_attributes.'>'.$custom_value.'</'.$childnodeName.'>';
								if($includeFiles){
									$used_Files[]=$orig_file;
									$rename_to[$orig_file]=BaseFilename($custom_value);
								}
							}else if(is_numeric($custom_value)){
								$new_value.= '<'.$childnodeName.'>'.$custom_value.'</'.$childnodeName.'>';
							}else{
								$config_lg['context']=$childnodeName;
								if($firstchildNodename=='CSS'){
									$result_array = CSStoXLIFF($custom_xml,$child_path.'/CSS',$ID.$sep.($i+1).$sep.$j,$config_lg,$used_Files,$rename_to);
									$new_value.= '<'.$childnodeName.'>'.$result_array['original'].'</'.$childnodeName.'>';
									$body.= $result_array['xliff'];
								
								}else if($custom_value){
									
									$result_array = PlaintexttoXLIFF($custom_value,$ID.$sep.($i+1).$sep.$j,$config_lg);
									$new_value.= '<'.$childnodeName.'>'.$result_array['original'].'</'.$childnodeName.'>';
									$body.= $result_array['xliff'];
								}else
									$new_value.= '<'.$childnodeName.'></'.$childnodeName.'>';
							}
						}
					}
					$this_xliff_body.=$body;
					$element_desc.='<'.$n.'>'.$new_value.'</'.$n.'>';
				}
				if(!$value && $n!='STATUS')
					$element_desc.='<'.$n.'></'.$n.'>';
			}
			$element_desc.='</DESCRIPTION>';
			if($preview_xliff){
				$elements_from_search_for_depth[$prefs['search']][$prefs['depth']][$key]['descriptions_xml']=$element_desc;
			}
			$element='<'.$moduleName.' ID="'.$ID.'"><DESCRIPTIONS>'.$element_desc.'</DESCRIPTIONS></'.$moduleName.'>';
			$original.='<UPDATE>'.$element.'</UPDATE>';
			$xliff_body.=$this_xliff_body;
		}else if($preview_xliff){
			$elements_from_search_for_depth[$prefs['search']][$prefs['depth']][$key]['descriptions_xml']='';
		}
	}
	if($preview_xliff){
		foreach($search_nodes as $search_path){
			$requestName = $xml->nodeName($search_path);
			$searchFirstNode = $xml->nodeName($search_path.'/*[1]');
			$subquery_name = $xml->getData($search_path.'/@name');
			$subquery_attributes = '';
			if ($subquery_name)
				$subquery_attributes.=" name='$subquery_name'";
			$preview.='<RESULTS '.$subquery_attributes.'>';
			if($searchFirstNode=='LABELS'){
				$sql = 'SELECT * FROM `labels` WHERE LanguageID="'.$defLgID.'";';
				$label_rs = $db_conn->Execute($sql);
				if ($label_rs){
					while($row = $label_rs->FetchRow()){
						$transUnitID='label'.$row['Denomination'];
						$preview.='<LABEL name="'.$row['Denomination'].'">%%%'.$transUnitID.'%%%'.$config_lg['eol'].'</LABEL>';
					}
				}
			}else{
				$searchFirstNodePath = $search_path.'/*[1]';
				$moduleName  = $searchFirstNode;
				$moduleInfo = moduleInfo($moduleName);
				$sql="";
				$preview_rs = getResultSet($moduleInfo,$xml,$search_path,$sql);
				if($preview_rs){
					$profile = array('profile_xml'=>$xml,'profile_path'=>$searchFirstNodePath.'/WITH[1]');
					if (  !$xml->match($searchFirstNodePath.'/WITH[1]') || (!$xml->match($searchFirstNodePath.'/WITH[1]/@profile') && !$xml->match($searchFirstNodePath.'/WITH[1]/*[1]') )  ){
						$profile = array('profile_name'=>'publication');
					}
					$depth = $depths[$search_path];
					$preview.=generateXMLOutput($preview_rs,$moduleInfo,$profile,$depth,$elements_from_search_for_depth[$search_path]);
				}
			}
			$preview.='</RESULTS>';
		}
	}
	$GLOBALS["php_request"] = false;
	if($includeFiles){
		$skipped_files = array();
		// copying the files to translate in the zip
		$skip_big_files = true;
		copy_translated_content($directoryRoot,$files_dir,$used_Files,$rename_to,$skipped_files);
		
	}
	if($config_lg['clone_files']){
		// copying the files to translate on the server ('cause not all users will take the files and reupload them)
		$skip_big_files = false;
		$skipped_files_bidon = array();
		copy_translated_content($directoryRoot,$directoryRoot,$used_Files,$rename_to,$skipped_files_bidon,$skip_big_files);
	}
	if(sizeof($skipped_files)>0){
		$html = '<html><body><h1>Missing files (too large to be zipped) : download them separately</h1><ul>';
		foreach($skipped_files as $short_path){
			$html.='<li><a href="'.$GLOBALS["backoffice_url"].'file/file_download.php?target='.$short_path.'">'.BaseFilename($short_path).'</a> to download separately in directory <strong>data/Files'.getShortPath(dirname($directoryRoot.$short_path)).'</strong></li>';
		}
		$html.= '</ul></body></html>';
		saveInFile($html,$data_dir.'/missing_files.html');
	}
	if($includeLibrary){
		if (file_exists($GLOBALS["library_dir"]."media/css/default.css"))
			copy($GLOBALS["library_dir"]."media/css/default.css",$css_dir."/default.css");
	}
	if ($sourceFolder && file_exists($GLOBALS["nectil_dir"].$sourceFolder))
		copy_content($GLOBALS["nectil_dir"].$sourceFolder,$root_dir);
	$xliff ='<?xml version="1.0" encoding="utf-8"?>';
	$xliff.='<!DOCTYPE xliff PUBLIC "-//XLIFF//DTD XLIFF//EN" "http://www.oasis-open.org/committees/xliff/documents/xliff.dtd">';
	$xliff.='<xliff version="1.0"><file original="data_'.$languageID.'.xml" datatype="xml" source-language="'.$config_lg['srcLg'].'" target-language="'.$config_lg['destLg'].'">';
	global $slash;
	if($preview_xliff){
		$final_skl = '<RESPONSE>'.$preview.'</RESPONSE>';
		$shortname = 'data_'.$languageID.'.skl';
		$path = $data_dir.$slash.$shortname;
		saveInFile('<?xml version="1.0" encoding="utf-8"?><QUERY>'.$original.'</QUERY>',$path);
	}else{
		$final_skl = '<QUERY>'.$original.'</QUERY>';
	}
	$xliff.='<header><skl><internal-file><![CDATA[<?xml version="1.0" encoding="utf-8"?>'.$final_skl.']]></internal-file></skl><prop-group name="encoding"><prop prop-type="encoding">utf-8</prop></prop-group></header>';
	$xliff.='<body>'.$xliff_body.'</body>';
	$xliff.='</file></xliff>';
	$shortname = 'data_'.$languageID.'.xlf';
	
	$path = $data_dir.$slash.$shortname;
	
	saveInFile($xliff,$path);
	// fake xml file, for the user to know which filename to use
	if($preview_xliff){
		$shortname = 'data_'.$languageID.'.xml';
		$path = $data_dir.$slash.$shortname;
		saveInFile('',$path);
	}
	//debug_log($xliff);
	$zip_location = realpath($directoryRoot."/tmp")."/".$now.".zip";
	
	zip($zip_dir,$zip_location);
	killDirectory($tmp_dir);
	$filename = $zip_location;
	if(substr($filename,0,strlen($GLOBALS["directoryRoot"]))==$GLOBALS["directoryRoot"] )
		$filename = substr($filename,strlen($GLOBALS["directoryRoot"]));
	if ($name)
		$attributes.=" name='$name'";
	$external_file = $xml->getData($current_path.'/@fromFile');
	if($external_file)
		$attributes.=" fromFile='".$external_file."'";
	$query_result='<RESULTS'.$attributes.'>';
	$query_result.= '<XLIFF>'.$filename.'</XLIFF></RESULTS>';
	return $query_result;
}

function PlaintexttoXLIFF($text,$ID,$config){
	
	$transUnitID=$ID;
	$original.='%%%'.$transUnitID.'%%%'.$config['eol'];
	$target = '';
	if($config['copy_original'])
		$target = $text;
	$xliff.='<trans-unit id="'.$transUnitID.'"><source xml:lang="'.$config['srcLg'].'">'.$text.'</source><target state="new" xml:lang="'.$config['destLg'].'">'.$target.'</target><note from="Nectil">'.$config['context'].'</note></trans-unit>';
	return array('original'=>$original,'xliff'=>$xliff);
}

function CSStoXLIFF(&$xml,$path,$ID,$config,&$used_Files,&$rename_to){
	$eol="\n";
	global $directoryRoot;
	$j = 0;
	$childs = $xml->match($path."/* | ".$path."//li");
	foreach($childs as $child_path){
		$j++;
		$transUnitID=$ID.$config['sep'].$j;
		$childnode = $xml->getNode($child_path);
		$childnodeName = $xml->nodeName($child_path);
		$attributes_array = $xml->match($child_path.'/attribute::*');
		$attributes = AttributesToString($xml,$child_path);
		
		if($childnodeName=='ul'){
			;
		}else{
			$grandchilds_match = $child_path."/*";
			$grandchilds = $xml->match($grandchilds_match);
			$i = 0;
			$xliff_part = '';
			foreach($grandchilds as $grandchild_path){
				$this_node = $xml->getNode($grandchild_path);
				$xliff_part.=$childnode['textParts'][$i];
				$childnode['textParts'][$i]=''; // cleaning
				$i++;
				
				$grandchildnodeName = $xml->nodeName($grandchild_path);
				$grandchildvalue = $xml->getData($grandchild_path);
				
				$inlineID = $transUnitID.$config['sep'].$i;
				switch($grandchildnodeName){
					case 'a':
						$href = $xml->getData($grandchild_path.'/@href');
						if(strpos($href,'[files_url]')!==false){
							$short_path = str_replace('[files_url]','',$href);
							$used_Files[]=$short_path;
							$ext = getFileExt($short_path);
							$basic_name = getFilenameWithoutExt($short_path);
							if($config['clone_files']){
								if($basic_name == $short_path){
									$new_href = $short_path.'_'.$config['srcLgID'];
									// if the file already exists and has not the same size, we suffix it to avoid overwrite
									$suffix = 0;
									while(file_exists("$directoryRoot$new_href") && filesize("$directoryRoot$new_href")!=filesize("$directoryRoot$short_path")){
										$suffix++;
										$new_href = $short_path.'_'.$config['srcLgID'].$suffix;
									}
								}else{
									$new_href = $basic_name.'_'.$config['srcLgID'].'.'.$ext;
									// if the file already exists and has not the same size, we suffix it to avoid overwrite
									$suffix = 0;
									while(file_exists("$directoryRoot$new_href") && filesize("$directoryRoot$new_href")!=filesize("$directoryRoot$short_path")){
										$suffix++;
										$new_href = $short_path.'_'.$config['srcLgID'].$suffix.'.'.$ext;
									}
								}
							}else
								$new_href = $href;
							$rename_to[$short_path]=BaseFilename($new_href);
							$new_href = '[files_url]'.$new_href;
							$xml->setAttribute($grandchild_path, 'href', $new_href);
						}
					case 'nectil_url':
						$xliff_part.='<ph ctype="link" id="'.$inlineID.'">&lt;'.$grandchildnodeName.encode_to_xml(AttributesToString($xml,$grandchild_path)).'&gt;</ph>'.$grandchildvalue.'<ph id="'.$inlineID.'">&lt;/'.$grandchildnodeName.'&gt;</ph>';
						break;
					case 'br':
						$xliff_part.='<ph ctype="lb" id="'.$inlineID.'">&lt;br/&gt;</ph>';
						break;
					case 'img':
						$alt = $xml->getData($grandchild_path.'/@alt');
						$src = $xml->getData($grandchild_path.'/@src');
						$short_path = str_replace('[files_url]','',$src);
						$used_Files[]=$short_path;
						$ext = getFileExt($short_path);
						$basic_name = getFilenameWithoutExt($short_path);
						if($config['clone_files']){
							if($basic_name == $short_path){
								$new_src = $short_path.'_'.$config['srcLgID'];
								// if the file already exists and has not the same size, we suffix it to avoid overwrite
								$suffix = 0;
								while(file_exists("$directoryRoot$new_src") && filesize("$directoryRoot$new_src")!=filesize("$directoryRoot$short_path")){
									$suffix++;
									$new_src = $short_path.'_'.$config['srcLgID'].$suffix;
								}
							}else{
								$new_src = $basic_name.'_'.$config['srcLgID'].'.'.$ext;
								$suffix = 0;
								while(file_exists("$directoryRoot$new_src") && filesize("$directoryRoot$new_src")!=filesize("$directoryRoot$short_path")){
									$suffix++;
									$new_src = $basic_name.'_'.$config['srcLgID'].$suffix.'.'.$ext;
								}
							}
						}else
							$new_src = $src;
						$rename_to[$short_path]=BaseFilename($new_src);
						$new_src = '[files_url]'.$new_src;
						$xml->setAttribute($grandchild_path, 'src', $new_src);
						$xliff_part.='<ph ctype="image" id="'.$inlineID.'">&lt;img alt="</ph>'.$alt.'<ph ctype="image" id="'.$inlineID.'">"'.encode_to_xml(AttributesToString($xml,$grandchild_path,array('alt','title'))).'/&gt;</ph>';
						break;
					default:
						$ctype = 'x-font';
						if($grandchildnodeName=='strong')
							$ctype='bold';
						else if($grandchildnodeName=='emp')
							$ctype='italic';
						$xliff_part.='<ph ctype="'.$ctype.'" id="'.$inlineID.'">&lt;'.$grandchildnodeName.encode_to_xml(AttributesToString($xml,$grandchild_path)).'&gt;</ph>'.$grandchildvalue.'<ph id="'.$inlineID.'">&lt;/'.$grandchildnodeName.'&gt;</ph>';
				}
				
			}
			
			$xliff_part.=$childnode['textParts'][sizeof($childnode['textParts'])-1];
			if($xliff_part){
				$target = '';
				if($config['copy_original'])
					$target = $xliff_part;
				$xliff.='<trans-unit datatype="xml" xml:space="preserve" id="'.$transUnitID.'"><source xml:lang="'.$config['srcLg'].'">'.$xliff_part.'</source><target state="new" xml:lang="'.$config['destLg'].'">'.$target.'</target><note from="Nectil">'.$config['context'].'</note></trans-unit>';
				
				$childnode['textParts'][sizeof($childnode['textParts'])-1] = '';
				// removing all childs
				$xml->removeChild($child_path.'/*');
				$xml->insertData($child_path, '%%%'.$transUnitID.'%%%'.$config['eol']);
			}
		}
		
	}
	return array('original'=>$xml->toString($path,''),'xliff'=>$xliff);
}

function findXLIFFelements(&$elements,$element,$config,$actual_depth=0){
	$moduleInfo = $config['moduleInfo'];
	$moduleName = $moduleInfo->name;
	if(isset($elements[$moduleName.'_'.$element['ID']])){
		//debug_log('dropping '.$moduleName.'_'.$element['ID']);
		return;
	}
	$elements[$moduleName.'_'.$element['ID']] = array('ID'=>$element['ID'],'ModuleTargetID'=>$moduleInfo->ID,'depth'=>$actual_depth,'search'=>$config['search']);
	if(is_numeric($config['depth'])){
		$config['depth'] = $config['depth']-1;
	}else if($config['depth']==='all'){
		$config['depth'] = 'all';
	}else
		$config['depth'] = 0;
	
	if($config['depth']==='all' || $config['depth']>0){
		if($config['preview_xliff'])
			$elements[$moduleName.'_'.$element['ID']]['dependencies'] = array();
		//debug_log('taking its deps '.$moduleName.'_'.$element['ID']);
		if(!isset($depTypes_by_module[$moduleInfo->ID])){
			$depType_rs = getDependencyTypesFrom($moduleInfo->ID);// careful : must be more controlled
			while($row = $depType_rs->FetchRow()){
				$dependencyType = depType($row['ID']);
				if ($dependencyType->loaded){
					$depTypes_by_module[$moduleInfo->ID][$row['ID']]=array('depType'=>$dependencyType);
				}
			}
		}
		if (is_array($depTypes_by_module[$moduleInfo->ID])){
			foreach($depTypes_by_module[$moduleInfo->ID] as $dependencyType_and_profile){
				$dependencyType = $dependencyType_and_profile['depType'];
				$dep_rs = getDepTargets($element['ID'],$dependencyType->ID);
				if($config['preview_xliff'])
					$elements[$moduleName.'_'.$element['ID']]['dependencies'][$dependencyType->ID] = array();
				if(is_object($dep_rs) && $dep_rs->RecordCount()>0){
					while($row = $dep_rs->FetchRow()){
						$this_child = findXLIFFelements($elements,$row,$config,$actual_depth+1);
						if($config['preview_xliff'])
							$elements[$moduleName.'_'.$element['ID']]['dependencies'][$dependencyType->ID][] = $this_child;
					}
				}
			}
		}
	}
	return $elements[$moduleName.'_'.$element['ID']];
}
?>
