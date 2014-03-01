<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/static_version.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
function parse_params($url_param_str){
	
	if(!$url_param_str)
		return array();
	$params_array = explode('&',$url_param_str);
	$url_get = array();
	foreach($params_array as $param_str){
		$one_param_array = explode('=',$param_str);
		if($one_param_array[0]!='PHPSESSID')
			$url_get[$one_param_array[0]]=$one_param_array[1];
	}
	return $url_get;
}

function image_in_string($path){
	//if (file_exists($path)){
		$handle = @fopen($path, "rb");
		if($handle){
			while (!feof($handle)) {
				$buffer = fgets($handle, 4096);
				$str.=$buffer;
			}
		}
		return $str;
	/*}else 
		return false;*/
}

function dirUrl($url){
	$split = explode('?',$url);
	$url = $split[0];
	if(substr($url,-1,1)=='/')
		return $url;
	$last_slash_pos = strrpos($url,'/');
	if($last_slash_pos)
		return substr($url,0,$last_slash_pos+1);
	else
		return '';
}
function generateCorrectUrl($str){
	//$split= explode('-',$str);
	//$str = trim($split[sizeof($split)-1]);
	return str_replace(array('/',' ','"',"'",'+',',',':','.','%','#','&','(',')','?','!'),array('','','','','','','','','','','','','',''),removeaccents(decode_from_xml($str)));
}
function cleanParam($param){
	return str_replace('/','_',$param);
}

function checkUnicity($url,$path){
	$unique_paths = $GLOBALS['unique_paths'];
	if(!isset($unique_paths[$path])){
		$GLOBALS['unique_paths'][$path]=$url;
		return $path;
	}else {
		if($unique_paths[$path]==$url)
			return $path;
		$without_ext = getFilenameWithoutExt($path);
		$ext = getFileExt($path);
		$i = 1;
		while(isset($unique_paths[$without_ext.$i.'.'.$ext])){
			if($unique_paths[$without_ext.$i.'.'.$ext]==$url)
				break;
			$i++;
		}
		$GLOBALS['unique_paths'][$without_ext.$i.'.'.$ext]=$url;
		return $without_ext.$i.'.'.$ext;
	}
}
function cutToDirname($path){
	$array = explode('/',$path);
	array_pop($array);
	return implode('/',$array);
}
function force_output(){
	flush();
	ob_flush();
	usleep(50000);
}
function generateExceptPath($script_name,$get){
	//echo 'generateExceptPath '.$script_name.'<br>';
	global $slash;
	if($script_name)
		$path = $script_name;
	foreach($get as $key=>$value){
		if($key!='language')
			$after_path.=$key.cleanParam($value);
	}
	if(strlen($after_path)>32)
		$after_path = substr($after_path,0,32);
	$path.=$after_path;
	if(!$path)
		$path = 'index';
	if(isset($get['language'])){
		if($GLOBALS['ext_lg']){
			if($GLOBALS['languageEncoding']=='ISO1')
				$path.=$GLOBALS['ext_lg_sep'].$GLOBALS['ISO1'][$get['language']];
			else
				$path.=$GLOBALS['ext_lg_sep'].$get['language'];
		}
	}else{
		$path.=$GLOBALS['ext_lg_sep'].$GLOBALS['ext_lg'];
	}
	$path.=$GLOBALS['html_extension'];//'.html';
	return $path;
}
function canonicalParams($url){
	//echo 'link to '.$url.'<br/>';
	global $slash;
	$anchor_pos = strpos($url,'#');
	if($anchor_pos!==false){
		$split2 = explode('#',$url,2);
		$url = $split2[0];
		$anchor = '#'.$split2[1];
	}else
		$anchor = '';
	$split = explode('?',$url);
	//$split[1]=str_replace(array('parentID=','gdparentID=','gdancestor1='),array('ancestor1=','ancestor2=','ancestor2='),$split[1]);
	$get = parse_params($split[1]);
	ksort($get);
	$rget = array_reverse ($get,true);
	$script_compl_name = $split[0];
	
	/*if($ext=='php')
		$script_name = $without_ext;
	else
		$script_name = $script_compl_name;*/
	$script_name = BaseFilename($script_compl_name);
	$without_ext = getFilenameWithoutExt($script_name);
	$ext = getFileExt($script_name);
	$mediatype = BaseFilename($without_ext);
	$new_url = $script_compl_name;
	$parsed = false;
	if($GLOBALS['start_page']==$url){
		$new_url=$GLOBALS['start_page'];
		$path = 'index'.$GLOBALS['ext_lg_sep'].$GLOBALS['ext_lg'].$GLOBALS['html_extension'];
		$parsed = true;
	}else if(sizeof($get)>0){
		//$new_url.='?';
		// determining the filename of the page 
		$folder = '';
		if(!isset($get['language']))
			$get['language'] = $GLOBALS['current_lg'];
		$ok = false;
		if(isset($get['ID'])){
			//if(isset($get['language']))
			$this_lg=$get['language'];
			//else
				//$this_lg=$GLOBALS['current_lg'];
			$ID  = $get['ID'];
			$moduleInfo = moduleInfo('media');
			$media = getInfo($moduleInfo,$ID);
			if($media[$GLOBALS['naming']] && ($media['MediaType']==$mediatype || $media['PageToCall']==$script_name)){
				if(sizeof($get)==2 /* ID and language */){
					$path = generateCorrectUrl($media[$GLOBALS['naming']]).$slash;
					if($GLOBALS['languageEncoding']=='ISO1'){
						$lg_ext = $GLOBALS['ISO1'][$this_lg];
					}else
						$lg_ext = $this_lg;
					$path.= 'index_'.$lg_ext.$GLOBALS['html_extension'];//'.html';
				}else{
					$non_ancestor = 0;
					foreach($rget as $key=>$value){
						if(substr($key,0,8)=='ancestor'){
							$ancestor_media = getInfo($moduleInfo,$value);
							if($ancestor_media[$GLOBALS['naming']])
								$path.=generateCorrectUrl($ancestor_media[$GLOBALS['naming']]).$slash;
						}else $non_ancestor++;
						
					}
					$path.= generateCorrectUrl($media[$GLOBALS['naming']]);
					if($non_ancestor){
						/*foreach($get as $key=>$value){
							if(substr($key,0,8)!='ancestor' && $key!='ID' && $value!=''){
								$after_path.=$key.cleanParam($value);
							}
						}
						if($after_path)
							$path.='_'.$after_path;*/
					}else
						$path.='index';
					if($GLOBALS['ext_lg']){
						if($GLOBALS['languageEncoding']=='ISO1'){
							$lg_ext = $GLOBALS['ISO1'][$this_lg];
						}else
							$lg_ext = $this_lg;
					}else
						$lg_ext = '';
					$path.=$GLOBALS['ext_lg_sep'].$lg_ext.$GLOBALS['html_extension'];//'.html';
				}
				$ok = true;
			}
		}
		if(!$ok){
			//echo 'Exception '.$url.'!!!<br/>';
			$path = generateExceptPath($without_ext,$get);
		}
		$first = true;
		foreach($get as $key=>$value){
			//if($key!='language'){
				if($first==true) {$first = false;$new_url.='?';} else $new_url.='&';
				$new_url.=$key.'='.$value;
			//}
		}
	}else if($url == $GLOBALS["Public_url"] || $url==$GLOBALS["Public_url"].'index.php'){
		$new_url=$GLOBALS["Public_url"].'?language='.$GLOBALS['current_lg'];
		$path = 'index'.$GLOBALS['ext_lg_sep'].$GLOBALS['ext_lg'].$GLOBALS['html_extension'];//'.html';
		//$new_url = 'index_'.$GLOBALS['current_lg'].'.html';
		$parsed = true;
	}else{
		$simple_name = BaseFilename($without_ext);
		if(strpos($simple_name,'css')!==false || $ext=='css')
		$path=$simple_name.'.css';
		else{
		$path=$simple_name.$GLOBALS['ext_lg_sep'].$GLOBALS['ext_lg'].$GLOBALS['html_extension'];//'.html';
		$new_url.='?language='.$GLOBALS['current_lg'];
		}
	}
	$path = checkUnicity($new_url,$path);
	return array('anchor'=>$anchor,'path'=>$path,'parsed'=>$parsed,'orig_url'=>$url,'url'=>$new_url,'get'=>$get);
}

function killTmpDirectory($pathname) {
	if(!file_exists($pathname))
		return false;
	$files = getAllFiles($pathname); 
	$delete_dir = TRUE;
	foreach($files as $file) {
		if(is_dir($pathname.'/'.$file)) { 
			$res = killTmpDirectory($pathname.'/'.$file,$desc_check);
		}else {
			unlink($pathname.'/'.$file);
		}
	}
	if ($delete_dir){
		rmdir($pathname);
	}
	return $delete_dir;
}


function handleImage($start_exp,$end_exp,$src,$src_content,$particle_url,$particle,$dir_url,$images_dir,&$to_replace,&$replacements){
	//echo 'handling image '.$src_content.'<br>';
  if(substr($src_content,0,7)=='http://'){ // distant files or absolute published files, or static images in public dir
	  if(substr($src_content,0,strlen($GLOBALS["files_url"]))===$GLOBALS["files_url"]){
		  // must replace by a relative path
		  $short_path = substr($src_content,strlen($GLOBALS["files_url"]));
		  $short_path = str_replace('%20',' ',$short_path);
		  $to_replace[]=$src;
		  $replacements[]=$start_exp.$particle_url."Files".$short_path.$end_exp;
		  $dirname = cutToDirname($particle."Files".$short_path);
		  //echo 'must create dir '.$dirname.' to copy '.$short_path.' from '.$src_content.'<br>';
		  makeDir($dirname);
		  @copy(str_replace(' ','%20',$src_content),$particle."Files".$short_path);
	  }else if(substr($src_content,0,strlen($GLOBALS["Public_url"]))===$GLOBALS["Public_url"]){
		  //echo 'src_content is in public dir '.$src_content.'<br>';
		  ; // should copy image in static dir and replace by this occurence
		  $filename = BaseFilename($src_content);
		  $img_url = $particle_url.'images/'.$filename;
		  $image_path = $GLOBALS["Public_dir"].str_replace($GLOBALS["Public_url"],'',$src_content);
		  //echo "image ".$image_path." becomes ".$images_dir.$filename."<br>";
		  $complete_file = $images_dir.$filename;
		  $copy_res = false;
		  if(strpos($src_content,'.php?')===false)
		 	 $copy_res = copy($image_path,$complete_file);
		  else{
			//echo 'getting back the string of the image <br>';
		  	$img_str = image_in_string(str_replace(' ','%20',$src_content));
			$md5 = md5($src_content);
			$complete_file = $images_dir.$md5;
			$ext = '.gif';
			saveInFile($img_str,$complete_file.$ext);
			$images_info = getimagesize($complete_file.$ext);
			if($images_info[2]!=1){//1 = GIF, 2 = JPG, 3 = PNG
				if($images_info[2]==2)
					$new_ext = '.jpg';
				else
					$new_ext = '.png';
				rename($complete_file.$ext,$complete_file.$new_ext);
				$ext = $new_ext;
			}
			$img_url = $particle_url.'images/'.$md5.$ext;
			$copy_res = true;
		  }
		  if($copy_res){
			  chmod_Nectil($complete_file);
			  $to_replace[]=$src;
			  $replacements[]=$start_exp.$img_url.$end_exp;
		  }
	  }
	  
  }else if(substr($src_content,0,8)=='../Files'){ // published images
	  $to_replace[]=$src;
	  $replacements[]=$start_exp.$GLOBALS["files_url"].substr($src_content,8).$end_exp;
  }else{ // relative path to an image
	  // copy image in static directory and replace by a complete url 
	  $complete_url = $dir_url.$src_content;
	  $filename = BaseFilename($src_content);
	  $img_url = $particle_url.'images/'.$filename;
	  
	  $image_path = $GLOBALS["Public_dir"].str_replace($GLOBALS["Public_url"],'',$complete_url);
	  //echo "image ".$image_path." becomes ".$images_dir.$filename."<br>";
	  $copy_res = copy($image_path,$images_dir.$filename);
	  if(!$copy_res && strpos($filename,'.php')!==false){
		$img_str = image_in_string($complete_url);
		$md5 = md5($filename);
		$complete_file = $images_dir.$md5;
		saveInFile($img_str,$complete_file.'.gif');
		$img_url = $particle_url.'images/'.$md5.'.gif';
		$copy_res = true;
	  }
	  if($copy_res){
	  chmod_Nectil($images_dir.$filename);
	  $to_replace[]=$src;
	  $replacements[]=$start_exp.$img_url.$end_exp;
	  }
  }

}
	

function handleUrl($start_exp,$end_exp,$href,$particle_url,$particle,$dir_url,&$to_replace,&$replacements,&$parse_ok,&$still_to_parse,$validity_check){
	  $go_on = false;
	  if(substr($href,0,7)=='http://'){
		  if(substr($href,0,strlen($GLOBALS["Public_url"]))===$GLOBALS["Public_url"]){
			  $new_href = substr($href,strlen($GLOBALS["Public_url"]));
			  $go_on=true;
		  }
	  }else if($href && substr($href,0,11)!='javascript:' && substr($href,0,1)!='#' && substr($href,0,7)!='mailto:' && substr($href,0,8)!='../Files' && substr($href,0,strlen($GLOBALS["files_url"]))!=$GLOBALS["files_url"]){
		  $protocol_expl = explode(':',$href);
		  if(sizeof($protocol_expl)==1){ // cannot be another protocol than http:
			  $go_on= true;
			  $new_href = $href;
		  }
	  }
	  if(substr($href,0,strlen($GLOBALS["files_url"]))==$GLOBALS["files_url"]){
		  $short_path = substr($href,strlen($GLOBALS["files_url"]));
		  $to_replace[]=$start_exp.$href.$end_exp;
		  $replacements[]=$start_exp.$particle_url."Files".$short_path.$end_exp;
		  $dirname = cutToDirname($particle."Files".$short_path);
		  makeDir($dirname);
		  @copy($href,$particle."Files".$short_path);
	  }
	  if($go_on){
		  $new_href = decode_from_xml($new_href);
		  if(substr($new_href,0,1)=='/')
			  $new_url = "http://".$_SERVER['HTTP_HOST'].$new_href;
		  else
			$new_url = $dir_url.$new_href;
		  //echo 'dir of current url is '.dirUrl($url).'<br>';
		  $new_params = canonicalParams($new_url);
		  $canonical_url = $new_params['url'];
		  
		  $open = true;
		  if($validity_check){
			  $page_handle = @fopen($canonical_url,'r');
			  if($page_handle){
				  $open=true;
				  fclose($page_handle);
			  }else
				  $open = false;
		  }
		  if($open==true && $new_params['parsed']!==true && !isset($parse_ok[$GLOBALS['current_lg']][$canonical_url]) && $canonical_url!=$url && $canonical_url!=$GLOBALS["Public_url"] && !isset($still_to_parse[$GLOBALS['current_lg']][$canonical_url])){
			  //$new_params = $start_page_params;
			  //$new_params['url']=$new_url;
			  if($GLOBALS['progress']!=='false')
			  	echo $o.': '.$canonical_url.' -> '.$new_params['path'].'<br>';
			  /*else
			  	debug_log($o.': '.$canonical_url.' -> '.$new_params['path']);*/
			  $still_to_parse[$new_params['get']['language']][$canonical_url]=$new_params;
			  //$to_replace[$canonical_url]=$new_params;
			  
		  }
		  if($open){
			  $to_replace[]=$start_exp.$href.$end_exp;
			  $replacements[]=$start_exp.$particle_url.$new_params['path'].$new_params['anchor'].$end_exp;
		  }
	  }
}
function handleFlashUrl($start_exp,$end_exp,$href,$particle_url,$particle,$dir_url,&$to_replace,&$replacements,&$parse_ok,&$still_to_parse,$validity_check){
	$go_on = false;
	if(substr($href,0,7)=='http://'){
	  if(substr($href,0,strlen($GLOBALS["Public_url"]))===$GLOBALS["Public_url"]){
		  $new_href = substr($href,strlen($GLOBALS["Public_url"]));
		  $go_on=true;
	  }
	}
	if($go_on){
	  $new_href = decode_from_xml(urldecode($new_href));
	  $new_url = $dir_url.$new_href;
	  //echo 'dir of current url is '.dirUrl($url).'<br>';
	  $new_params = canonicalParams($new_url);
	  $canonical_url = $new_params['url'];
	  
	  $open = true;
	  if($validity_check){
		  $page_handle = @fopen($canonical_url,'r');
		  if($page_handle){
			  $open=true;
			  fclose($page_handle);
		  }else
			  $open = false;
	  }
	  if($open==true && $new_params['parsed']!==true && !isset($parse_ok[$GLOBALS['current_lg']][$canonical_url]) && $canonical_url!=$url && $canonical_url!=$GLOBALS["Public_url"] && !isset($still_to_parse[$GLOBALS['current_lg']][$canonical_url])){
		  if($GLOBALS['progress']!=='false')
		  	echo $o.': '.$canonical_url.' -> '.$new_params['path'].'<br>';
		  /*else
		  	debug_log($o.': '.$canonical_url.' -> '.$new_params['path']);*/
		  $still_to_parse[$new_params['get']['language']][$canonical_url]=$new_params;
	  }
	  if($open){
		  $to_replace[]=$start_exp.$href.$end_exp;
		  $replacements[]=$start_exp.urlencode($particle_url.$new_params['path'].$new_params['anchor']).$end_exp;
	  }
	}
}
function generateStaticVersion($params){
	$GLOBALS['languageEncoding']=$params['languageEncoding'];
	$GLOBALS['progress']=$params['progress'];
	if(!isset($params['language_extension']))
		$params['language_extension']=true;
	
	if (!$GLOBALS["generic_backoffice"] && $GLOBALS["NectilMasterURL"]!='officity.com' && $GLOBALS["NectilMasterURL"]!='nectil.com'){
		$GLOBALS["Public_url"]='http://localhost/Public/';
		$GLOBALS["files_url"]='http://localhost/Files';
		$GLOBALS["nectil_url"]='http://localhost';
	}
	if(!$params['naming'])
		$params['naming']='Denomination';
	$GLOBALS['naming']=$params['naming'];
	$GLOBALS['html_extension'] = '.htm';
	if(isset($params['html_extension']))
		$GLOBALS['html_extension'] = $params['html_extension'];
	$GLOBALS['unique_paths'] = array();
	global $slash;
	$tmp_dir = $GLOBALS["directoryRoot"].$slash.'tmp'.$slash.'static'.date('YmdHis').$slash;//date('YmdHis');
	if(file_exists($tmp_dir))
		killDirectory($tmp_dir);
	makeDir($tmp_dir);
	$images_dir = $tmp_dir.'images'.$slash;
	$files_dir = $tmp_dir.'Files'.$slash;
	makeDir($images_dir);
	chmod_Nectil($images_dir);
	
	makeDir($files_dir);
	chmod_Nectil($files_dir);
	//$language = 'fre';
	$first_page = true;
	$db_conn=db_connect();
	$parse_ok = array();
	$still_to_parse = array();
	
	
	//$completion = '/Files/tmp/static/';
	$completion = '/Static/';
	
	$particle = $GLOBALS["nectil_dir"].$completion;
	$particle_url = $GLOBALS["nectil_url"].$completion;
	
	if(file_exists($particle)){
	}else{
		die(' "Static" directory (where to put the static version, besides "Public") does not exist ('.$particle.')');
	}
	if(is_writable($particle) ){
	}else{
		die(' "Static" directory (where to put the static version, besides "Public") is not writable ('.$particle.')');
	}
	
	$languages = array();
	$ISO1 = array();
	
	if($params['language']!=''){
		$languages[]=$params['language'];
		if($params['languageEncoding']=='ISO1'){
			$sql = "SELECT ISO1 FROM `".$GLOBALS["generic_backoffice_db"]."`.languages WHERE ID=\"".$params['language']."\"";
			$rowLg = $db_conn->getRow($sql);
			$ISO1[$params['language']] = $rowLg['ISO1'];
		}
	}else{
		$sql = "SELECT languageID FROM medialanguages WHERE published=1 ORDER BY priority ASC";
		$rs = $db_conn->Execute($sql);
		
		while($row = $rs->FetchRow()){
			$languages[]=$row["languageID"];
			if($params['languageEncoding']=='ISO1'){
				$sql = "SELECT ISO1 FROM `".$GLOBALS["generic_backoffice_db"]."`.languages WHERE ID=\"".$row["languageID"]."\"";
				$rowLg = $db_conn->getRow($sql);
				$ISO1[$row["languageID"]] = $rowLg['ISO1'];
			}
		}
	}
	$GLOBALS['ISO1']=$ISO1;
	if($GLOBALS['progress']!=='false')
		echo '<h1>Static HTML version</h1>';
	foreach($languages as $lg){
		if($GLOBALS['progress']!=='false')
			echo '<h2>Handling language '.$lg.'</h2>';
		$GLOBALS['current_lg'] = $lg;
		if($params['language_extension']){
			$GLOBALS['ext_lg_sep']='_';
			if($GLOBALS['languageEncoding']=='ISO1'){
				$GLOBALS['ext_lg'] = $ISO1[$GLOBALS['current_lg']];
			}else
				$GLOBALS['ext_lg'] = $GLOBALS['current_lg'];
		}else{
			if($GLOBALS['progress']!=='false')
				echo '<h3>no language_extension</h3>';
			$GLOBALS['ext_lg_sep']='';
			$GLOBALS['ext_lg']='';
		}
		if(isset($params['start_url'])){
			$start_url = $params['start_url'];
			$start_page = $GLOBALS["Public_url"].$start_url.((strpos($start_url,'?')!==false)?'&':'?').'language='.$GLOBALS['current_lg'];
		}else{
			$start_page = $GLOBALS["Public_url"].'?language='.$GLOBALS['current_lg'];
		}
		$GLOBALS['start_page']=$start_page;
		//$start_page_params = array('parsed'=>false,'url'=>$start_page,'get'=>array(),'path'=>'index_'.$GLOBALS['ext_lg'].'.html');
		$start_page_params = canonicalParams($start_page);
		$still_to_parse[$GLOBALS['current_lg']][$start_page] =$start_page_params;
		$o = 0;
		force_output();
		while( (sizeof($still_to_parse[$GLOBALS['current_lg']])>0 || sizeof($still_to_parse[''])>0)  /*&& $o<300*/){
			if(sizeof($still_to_parse['']))
			$page = array_shift($still_to_parse['']);
			else
			$page = array_shift($still_to_parse[$GLOBALS['current_lg']]);
			$url = $page['url'];
			if($GLOBALS['progress']!=='false')
				echo '<strong>'.$o.': handling '.$url.'</strong><br>';
			else{
				debug_log($o.': handling '.$url.'');
				header('X-pmaPing: Pong');
			}
			$url_get = $page['get'];//parse_params($split[1]);
			
			
			$page_handle = @fopen($url,'r');
			$page_str = '';
			if($page_handle){
				while (!feof($page_handle)) {
					$buffer = fread($page_handle, 8*1024);
					$page_str.=$buffer;
				}
			}else{
				if($GLOBALS['progress']!=='false')
					echo '<em>'.$o.': '.$url.' couldn\'t be retrieved</em><br>';
			}
			if($page_str){
				$dir_url = dirUrl($url);
				// counting how much ".." we must do to climb back to root
				$up_number  = substr_count($page['path'], "/");
				//echo 'must climb '.$up_number.' levels in '.$page['path'].'<br>';
				$to_root = '';
				for($k=0;$k<$up_number;$k++)
					$to_root.='../';
				$matches = array();
				$start_exp = 'href="';
				$end_exp = '"';
				$to_replace = array();
				$replacements = array();
				$res = preg_match_all("/".$start_exp."([^\"]*)".$end_exp."/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]); $i++) {
				  $href = $matches[1][$i];
				  handleUrl($start_exp,$end_exp,$href,/*$particle_url*/$to_root,$tmp_dir,$dir_url,$to_replace,$replacements,$parse_ok,$still_to_parse,false);
				}
				$start_exp = '<option value="';
				$end_exp = '"';
				$res = preg_match_all("/".$start_exp."([^\"]+)".$end_exp."/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]); $i++) {
				  $href = $matches[1][$i];
				  handleUrl($start_exp,$end_exp,$href,/*$particle_url*/$to_root,$tmp_dir,$dir_url,$to_replace,$replacements,$parse_ok,$still_to_parse,true);
				}
				$start_exp = "window.open('";
				$end_exp = "'";
				$regexp = "/window.open\('([^']+)".$end_exp."/i";
				$res = preg_match_all($regexp,$page_str,$matches);
				for ($i=0; $i< count($matches[0]); $i++) {
				  $href = $matches[1][$i];
				  handleUrl($start_exp,$end_exp,$href,/*$particle_url*/$to_root,$tmp_dir,$dir_url,$to_replace,$replacements,$parse_ok,$still_to_parse,true);
				}
				// we can now replace in the page
				$page_str = str_replace($to_replace,$replacements,$page_str);
				
				// copying and replacing utility images
				// background-image:url, list-style-image:url,background:url, list-style: url, src="
				//$page_str = str_replace ( mixed search, mixed replace, mixed subject);
				//$start_exp = 'src="';
				$to_replace = array();
				$replacements = array();
				
				$start_exp = 'list-style-image:url(';
				$end_exp = ')';
				$res = preg_match_all("/list-style-image:url\(([^\)]*)\)/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$src = $matches[0][$i];$src_content = $matches[1][$i];
					handleImage($start_exp,$end_exp,$src,$src_content,/*$particle_url*/$to_root,$particle,$dir_url,$images_dir,$to_replace,$replacements);
				}
				$start_exp = 'src="';
				$end_exp = '"';
				$res = preg_match_all("/src=\"([^\"]*)\"/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$src = $matches[0][$i];$src_content = $matches[1][$i];
					handleImage($start_exp,$end_exp,$src,$src_content,/*$particle_url*/$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
				}
				$start_exp = 'background-image:url(';
				$end_exp = ')';
				$res = preg_match_all("/background-image: ?url\(([^\)]*)\)/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$src = $matches[0][$i];$src_content = $matches[1][$i];
					handleImage($start_exp,$end_exp,$src,$src_content,/*$particle_url*/$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
				}
				$start_exp = 'background:url(';
				$end_exp = ')';
				$res = preg_match_all("/background: ?url\(([^\)]*)\)/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$src = $matches[0][$i];$src_content = $matches[1][$i];
					handleImage($start_exp,$end_exp,$src,$src_content,/*$particle_url*/$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
				}
				$start_exp = 'new SWFObject("';
				$end_exp = '"';
				$res = preg_match_all("/new SWFObject\(\"([^\"]*)\"/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$src = $matches[0][$i];$src_content = $matches[1][$i];
					handleImage($start_exp,$end_exp,$src,$src_content,$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
				}
				$start_exp = 'QT_WriteOBJECT_XHTML("';
				$end_exp = '"';
				$res = preg_match_all("/QT_WriteOBJECT_XHTML\(\"([^\"]*)\"/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$src = $matches[0][$i];$src_content = $matches[1][$i];
					handleImage($start_exp,$end_exp,$src,$src_content,$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
				}
				$start_exp = 'QT_WriteOBJECT_XHTML(\'';
				$end_exp = '\'';
				$res = preg_match_all("/QT_WriteOBJECT_XHTML\('([^']*)'/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$src = $matches[0][$i];$src_content = $matches[1][$i];
					handleImage($start_exp,$end_exp,$src,$src_content,$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
				}
				$start_exp = '.src=\'';
				$end_exp = '\'';
				$res = preg_match_all("/onMouseOver=\"([^\"]*)\"/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$javascript_code = $matches[1][$i];
					$res2 = preg_match_all("/.src='([^\']*)'/i",$javascript_code,$matches2);
					for ($i2=0; $i2< count($matches2[0]) && $res2; $i2++) {
						$src = $matches2[0][$i2];$src_content = $matches2[1][$i2];
						handleImage($start_exp,$end_exp,$src,$src_content,$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
					}
				}
				$res = preg_match_all("/onMouseOut=\"([^\"]*)\"/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$javascript_code = $matches[1][$i];
					$res2 = preg_match_all("/.src='([^\']*)'/i",$javascript_code,$matches2);
					for ($i2=0; $i2< count($matches2[0]) && $res2; $i2++) {
						$src = $matches2[0][$i2];$src_content = $matches2[1][$i2];
						handleImage($start_exp,$end_exp,$src,$src_content,$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
					}
				}
				$start_exp = '';
				$end_exp = '';
				$res = preg_match_all("/\.addParam\(\"FlashVars\", *\"([^\"]*)\"/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$flashvars = $matches[1][$i];
					$res2 = preg_match_all("/".str_replace('/','\\/',$GLOBALS['files_url'])."\/[a-zA-Z0-9\/ _\.]*/i",$flashvars,$matches2);
					for ($i2=0; $i2< count($matches2[0]) && $res2; $i2++) {
						$src = $matches2[0][$i2];
						handleImage($start_exp,$end_exp,$src,$src,$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
					}
					$res2 = preg_match_all("/".str_replace('/','\\/',$GLOBALS['Public_url'])."[a-zA-Z0-9\/ _\.%=;]*/i",$flashvars,$matches2);
					for ($i2=0; $i2< count($matches2[0]) && $res2; $i2++) {
						$href = $matches2[0][$i2];
						handleFlashUrl($start_exp,$end_exp,$href,$to_root,$tmp_dir,$dir_url,$to_replace,$replacements,$parse_ok,$still_to_parse,false);
					}
				}
				$res = preg_match_all("/\.addParam\(\"FlashVars\", *\'([^\"]*)\'/i",$page_str,$matches);
				for ($i=0; $i< count($matches[0]) && $res; $i++) {
					$flashvars = $matches[1][$i];
					$res2 = preg_match_all("/".str_replace('/','\\/',$GLOBALS['files_url'])."\/[a-zA-Z0-9\/ _\.]*/i",$flashvars,$matches2);
					for ($i2=0; $i2< count($matches2[0]) && $res2; $i2++) {
						$src = $matches2[0][$i2];
						handleImage($start_exp,$end_exp,$src,$src,$to_root,$tmp_dir,$dir_url,$images_dir,$to_replace,$replacements);
					}
					$res2 = preg_match_all("/".str_replace('/','\\/',$GLOBALS['Public_url'])."[a-zA-Z0-9\/ _\.%=;]*/i",$flashvars,$matches2);
					for ($i2=0; $i2< count($matches2[0]) && $res2; $i2++) {
						$href = $matches2[0][$i2];
						handleFlashUrl($start_exp,$end_exp,$href,$to_root,$tmp_dir,$dir_url,$to_replace,$replacements,$parse_ok,$still_to_parse,false);
					}
				}
				$page_str = str_replace($to_replace,$replacements,$page_str);
				$dirname = dirUrl($page['path']);
				//echo 'must save as '.$particle.$page['path'].' in '.$particle.$dirname.'<br><br>';
				// prepare the directories if necessary
				if($dirname) 
					makeDir($tmp_dir.$dirname);
				saveInFile($page_str,$tmp_dir.$page['path']);
				if($first_page === true)
					saveInFile($page_str,$tmp_dir.'index'.$GLOBALS['html_extension']/*'.html'*/);
				$first_page = false;
			}
			$parse_ok[$GLOBALS['current_lg']][$page['url']]=$page;
			$o++;
			force_output();
			
		}
	}
	debug_log('finished static generation');
	if($params['zip']==="true"){
		if(file_exists($GLOBALS["directoryRoot"].$slash.'tmp'.$slash.'static.zip'))
			unlink($GLOBALS["directoryRoot"].$slash.'tmp'.$slash.'static.zip');
		zip($tmp_dir,$GLOBALS["directoryRoot"].$slash.'tmp'.$slash.'static.zip');
		if($GLOBALS['progress']!=='false')
			echo '<a href="../../Files/tmp/static.zip">Download static version</a>';
		else
			debug_log('../../Files/tmp/static.zip" to download static version');
		killTmpDirectory($tmp_dir);
		return $GLOBALS["directoryRoot"].$slash.'tmp'.$slash.'static.zip';
	}else{
		copy_content($tmp_dir,$particle);
		killTmpDirectory($tmp_dir);
		return false;
	}
	
	
}
?>
