<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/importXLIFF.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function importXLIFF($name,&$xml,$requestName,$current_path,$firstNode,$firstNodePath){
	$time0 = time();
	$path = $xml->getData($firstNodePath.'/@path');
	if(!$path)
		return generateMsgXML(1,"Missing a path to the file to import.",0,'',$name);
	global $directoryRoot;
	global $slash;
	$complete_path = $directoryRoot.$path;
	if(!file_exists($complete_path))
		return generateMsgXML(1,"This file doesn't exist.",0,'',$name);
	$ext = getFileExt($path);
	if($ext!='zip')
		return generateMsgXML(1,"This file is not a ZIP file.",0,'',$name);
	$now = date('YmdHis');
	$tmp_dir = realpath($directoryRoot."/tmp").'/'.$now;
	makeDir($tmp_dir);
	unzip($complete_path,$tmp_dir);
	$data_dir = $tmp_dir;
	if(file_exists($tmp_dir.$slash.'data'))
		$data_dir = $tmp_dir.$slash.'data';
	$updates = 0;
	foreach (glob($data_dir.$slash."*.xlf") as $filename) {
		//echo "$filename occupe " . filesize($filename) . " octets\n";
		$complete_xlf_path = $filename;
		$basename = getFilenameWithoutExt(BaseFilename($filename));
		$complete_skl_file = $data_dir.$slash.$basename.'.skl';
		$complete_nql_file = $data_dir.$slash.$basename.'.nql';
		$xlf_str = file_in_string($complete_xlf_path);
		$xlf_str = str_replace(array('<bpt ','<ept ','</bpt>','</ept>'),array('<ph ','<ph ','</ph>','</ph>'),$xlf_str);
		$pos_preview_skl = strpos($xlf_str,'<skl><internal-file>');
		$pos_end_preview_skl =  strpos($xlf_str,'</internal-file></skl>');
		if(file_exists($complete_skl_file)){
			$skl_str = file_in_string($complete_skl_file);
			// removing preview in xliff file, to handle smaller data
			if($pos_preview_skl && $pos_end_preview_skl)
				$xlf_str = substr_replace($xlf_str,'', $pos_preview_skl,$pos_end_preview_skl-$pos_preview_skl+22);
			saveInFile($xlf_str,$complete_xlf_path);
		}else{
			// taking the skeleton inside the xlf file
			$skl_str = substr($xlf_str,$pos_preview_skl+29,$pos_end_preview_skl-$pos_preview_skl-32);
		}
		
		// must find all <trans-unit
		$decal = 0;
		$transunit_pos = strpos($xlf_str,'<trans-unit ',$decal);
		$loops = 0;
		while($transunit_pos !==false){
			// working on a smaller text part to be more efficient
			$decal = $transunit_pos+12;
			$transunit_endpos = strpos($xlf_str,'</trans-unit>',$decal);
			if($transunit_endpos!==false){
				$transunit_str = substr($xlf_str,$transunit_pos,$transunit_endpos-$transunit_pos);
				//debug_log($transunit_str);
				//must find the id
				$decal_inside_transunit = 12;
				$transunit_id_pos = strpos($transunit_str,'id="',$decal_inside_transunit);
				if($transunit_id_pos){
					$transunit_id_endpos = strpos($transunit_str,'"',$transunit_id_pos+4);
					if($transunit_id_endpos!==false){
						$transunit_id = substr($transunit_str,$transunit_id_pos+4,$transunit_id_endpos-$transunit_id_pos-4);
						//debug_log("transunit id is ".$transunit_id);
						// must recompose the xml 
						$search_tag = '<target '; // $search_tag = '<target ';
						$end_tag = '</target>'; // $end_tag = '</target>';
						$target_pos = strpos($transunit_str,$search_tag,$transunit_id_endpos);
						if($target_pos!==false){
							$target_inside_pos = strpos($transunit_str,'>',$target_pos+8);
							$target_endpos = strpos($transunit_str,$end_tag,$target_pos+8);
							$replace_array = array();
							$replace_array[] = '%%%'.$transunit_id.'%%%'."\r\n";
							$replace_array[] = '%%%'.$transunit_id.'%%%'."\n";
							$replace_array[] = '%%%'.$transunit_id.'%%%'."\r";
							if($target_endpos!==false && $target_inside_pos!==false){
								$target_str = substr($transunit_str,$target_inside_pos+1,$target_endpos-$target_inside_pos-1);
								
								// must replace the <ph tags inside the target
								$decal_inside_target = 0;
								$ph_pos = strpos($target_str,'<ph ',$decal_inside_target);
								$loops_ph = 0;
								while($ph_pos !==false){
									$ph_inside_pos = strpos($target_str,'>',$ph_pos+4);
									$ph_endpos = strpos($target_str,'</ph>',$ph_pos+4);
									if($ph_endpos===false || $ph_inside_pos===false)
										break;
									
									$ph_tag = substr($target_str,$ph_pos,$ph_inside_pos-$ph_pos+1);
									$lg_ph_tag = strlen($ph_tag);
									$ph_content = substr($target_str,$ph_inside_pos+1,$ph_endpos-$ph_inside_pos-1);
									$ph_decoded = decode_from_xml($ph_content);
									// difference of length between the original xml and its replacement
									$lg_ph_content = strlen($ph_content);
									$diff_w_decoded = $lg_ph_content-strlen($ph_decoded);
									// removing opening ph tag
									$target_str = substr_replace($target_str,'',$ph_pos,$lg_ph_tag);
									// removing closing ph tag
									$target_str = substr_replace($target_str,'',$ph_endpos-$lg_ph_tag,5);
									// replacing ph content by its decoded replacement
									//debug_log($target_str.' pos:'.($ph_inside_pos+1-$lg_ph_tag));
									$target_str = substr_replace($target_str,$ph_decoded,$ph_inside_pos+1-$lg_ph_tag,$lg_ph_content);
									//debug_log('ph tag '.$ph_tag);
									//debug_log('ph content '.$ph_decoded);
									$decal_inside_target = $ph_endpos-$lg_ph_tag-5-$diff_w_decoded;
									$ph_pos = strpos($target_str,'<ph ',$decal_inside_target);
									$loops_ph++;
									if($loops_ph>200000)
										break;
								}
								
								debug_log('must replace '.$replace_array[0].' by '.$target_str);
								$skl_str = str_replace($replace_array,$target_str,$skl_str);

							}else{
								// must be a directly closing tag, without content, replacing the occurences by the empty string
								//debug_log('must replace '.$replace_str.' by the empty string');
								$skl_str = str_replace($replace_str,'',$skl_str);
							}
							$time1 = time();
							if($time1>$time0+30){
								header('X-pmaPing: Pong');
								$time0 = $time1;
							}
						}
					}
				}else
					debug_log('transunit without id');
				// finding the next transunit
				$decal = $transunit_endpos;
				$transunit_pos = strpos($xlf_str,'<trans-unit ',$decal);
				//break;
			}else
				break;
			$loops++;
			/*if($loops>200000)
				break;*/
		}
		saveInFile($skl_str,$complete_nql_file);
		
		// real nql handling
		$update_pos = strpos($skl_str,'<UPDATE');
		while($update_pos != false){
			$update_endpos = strpos($skl_str,'</UPDATE>',$update_pos+7);
			if($update_endpos===false)
				break;
			else{
				$update_str = substr($skl_str,$update_pos,$update_endpos-$update_pos+9);
				$updates++;
				//debug_log('update '.$updates);
				//debug_log($update_str);
				query('<QUERY>'.$update_str.'</QUERY>');
				$time1 = time();
				if($time1>$time0+30){
					header('X-pmaPing: Pong');
					$time0 = $time1;
				}
			}
			$update_pos = strpos($skl_str,'<UPDATE',$update_endpos);
		}
		
		
		break;
		
	}
	debug_log('NQL file executed');
	// copying the files
	if(file_exists($data_dir.'/Files/media/'))
		copy_content($data_dir.'/Files/media/',$directoryRoot."/media/");
	killDirectory($tmp_dir);
	if ($name)
		$attributes.=" name='$name'";
	$external_file = $xml->getData($current_path.'/@fromFile');
	if($external_file)
		$attributes.=" fromFile='".$external_file."'";
	debug_log('xliff import finished');
	$query_result='<RESULTS'.$attributes.' updates="'.$updates.'"></RESULTS>';
	return $query_result;
}
?>
