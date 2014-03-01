<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/file_functions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once(dirname(__FILE__)."/../file/file_config.inc.php");
include_once(dirname(__FILE__)."/../common/common_functions.inc.php");
include_once(dirname(__FILE__)."/../common/descriptions.inc.php");
$fileErrorMessage = "ok";

function cleanFilename($path){
	$path_array = explodePath($path);
	$filename_index=sizeof($path_array)-1;
	if ($path_array[$filename_index]=='')
		$filename_index--;
	$path_array[$filename_index]=removeaccents($path_array[$filename_index]);
	$path_array[$filename_index]= str_replace(array('&','?',' '),'',$path_array[$filename_index]);
	$path_array[$filename_index] = cleanstr($path_array[$filename_index]);
	return implode('/',$path_array);
}

function cleanstr($string){
   $len = strlen($string);
   for($a=0; $a<$len; $a++){
       $p = ord($string[$a]);
       # chr(32) is space, it is preserved..
       (($p > 64 && $p < 123) || $p == 32 || $p==46 || ($p > 47 && $p < 58)) ? $ret .= $string[$a] : $ret .= "";
   }
   return $ret;
}
function cleanFilenames_in($source_dir){
	//debug_log('cleanFilenames_in');
	$source_dir = realpath($source_dir)."/";
	if (!file_exists($source_dir))
		return FALSE;
	if ($dir = @opendir($source_dir)) {
		while($file = readdir($dir)) {
			$location_source = $source_dir.$file;
			if ($file != "." && $file != ".." ){
				if (is_dir($location_source))
					cleanFilenames_in($location_source);
				$clean_name = cleanFilename($location_source);
				if($location_source!=$clean_name){
					$renamed = rename($location_source,$clean_name);
					debug_log("renamed ".$location_source);
				}else
					debug_log("no rename necessary ".$location_source);
			}
		}
	}else
		return FALSE;
}

function transformPath($path){
	if(getServerOs()=='windows')
		return str_replace( '\\', '/', $path);
	return $path;
}
function reTransformPath($path){
	$path = str_replace( '\\', '/', $path);
	return $path;
}
function jumpOver($path){
	/*if ($path=="/contact/" || $path=="/contact")
		return true;*/
	return false;
}
function getVolumeInfo($short_path/* or name of the volume*/){
	if ( strpos($short_path,'/')!==FALSE ){
		$path_array= explode("/",$short_path);
		//extract volume
    	$volume=trim($path_array[1]);
	}else
		$volume=$short_path;
	/*
		volumeTypes : "direct"
					  "subdir"
		criterion : array 	-> moduleaccess (nécessite un accès au module)
							-> dep (nécessite une dépendance depuis le contact vers l'élement) --> seulement si type='sub'
							-> field (nécessite que l'élément ait la ou les caractéristiques demandées ) --> seulement si type='sub'
							-> personal -> subdir must be the ID of the contact --> seulement si type='sub'
	*/
	switch($volume){
		case 'file': return array('type'=>'direct');
		case 'media': return array('type'=>'direct','criterion'=>array('moduleaccess'=>true));
		case 'contact': return array('type'=>'subdir','criterion'=>array('moduleaccess'=>true,'personal'=>true));
		case 'mail': return array('type'=>'subdir','criterion'=>array('moduleaccess'=>true,'personal'=>true));
		case 'group': return array('type'=>'subdir','criterion'=>array('dep'=>true,'moduleaccess'=>true,'field'=>array('IsTeam'=>1)));
		default : return array('type'=>'direct','criterion'=>array('moduleaccess'=>true));
	}
}
function isVolume($short_path){
	$path_array= explode("/",$short_path);
	if (sizeof($path_array)==2 || (sizeof($path_array)==3 && $path_array[2]==""))
		return TRUE;
	else
		return FALSE;
}
//determine if a target path is valid !!!!
function getPathSecurityRight($path){
    global $directoryRoot;
    global $fileErrorMessage;
	//----------------------Preparation of the tree if not ready------------------------------//
	// verifying the existence of the base filesystem for file management
	if (!is_dir($directoryRoot."/file"))
		makedir($directoryRoot."/file");
	// creating a temporary directory
	if (!is_dir($directoryRoot."/tmp"))
		makedir($directoryRoot."/tmp");
	require_once(dirname(__FILE__).'/../common/nectil_user.class.php');
	$request = new Sushee_Request();
	$user = new Sushee_User();
	$userID = $user->getID();
	if($userID){
		$contact_dir = $directoryRoot."/contact/".$userID;
		$mail_dir = $directoryRoot."/mail/".$userID;
		if (!is_dir($contact_dir))
			makedir($contact_dir);
		if (!is_dir($mail_dir))
			makedir($mail_dir);
	}
	//----------------------BASIC Checks------------------------------//
	if( !$userID && !$request->isProjectRequest() ){
		errors_log("Access to this directory refused. User not authenticated.");
		return 0;
	}
	
    $real_path = substr($directoryRoot.$path, 0, -1);
	$path_array= explode("/",$path);
	
    //check if pathRequest is accepted
    if (ereg("\.\./", $path)) {
        errors_log("Hack attempt, sysOp notified!");
        return 0;
    }
    if(substr($path,0,1) != "/"){
        errors_log("Invalid Path, path must begin with /");
        return 0;
    }
    if(trim($path) == "/"){
        return "R";
    }
	if($request->isProjectRequest()===true && !$request->isSecured())
		return "W";
	//----------------------Complex Checks------------------------------//
    //extract volume
    $volume = trim($path_array[1]);
	if($volume == 'tmp' || $volume == 'pdf')
		return 'W';
		
	if ($volume=='cache' || $volume=='resident' || $volume=='mailsaccount' || $volume=='license' || $volume=='event' || $volume=='batch' || $volume=='taxon'){
		errors_log("Access to this directory refused. Module doesnt use files (arbitrary disabled).");
		return 0;
	}
	$volumeInfo = getVolumeInfo($volume);
	// public directory
	if ($volumeInfo['type']=='direct' && !isset($volumeInfo['criterion']) )
		return 'W';
	$moduleInfo = moduleInfo($volume);
	$contactModuleInfo = moduleInfo('contact');
	if (!$moduleInfo->loaded){
		errors_log("Access to this directory refused. Module doesnt exist.");
		return 0;
	}
	if ($volumeInfo['criterion']['moduleaccess']===true){
		if ( !$moduleInfo->loaded || !$moduleInfo->getActionSecurity("SEARCH")){
			errors_log("Access to this directory refused. No access to this module.");
			return 0;
		}
	}
	// asking for a volume
	// not direct volumes --> only subdirs are available for writing
	if ($volumeInfo['type']=='subdir' && isVolume($path)){
		if ($moduleInfo->getServiceSecurity('file')=="W")
			return "R";
		else{
			errors_log("Access to this directory refused. Not your own directory.");
			return 0;
		}
	}
	// direct volumes --> must have access to the module (+files for this module)
	
	if ($volumeInfo['type']=='direct' /*&& isVolume($path)*/){
		if ($moduleInfo->getServiceSecurity('file')==='0'){
			errors_log("Access to this directory refused. Not access to the file service in the modulekey.");
			return 0;
		}
		return $moduleInfo->getServiceSecurity('file');
	}
	// volume + subdirectory for volume of type 'subdir'
	//----------------------Specific Subdir Checks------------------------------//
	// criterias on values of the element itself
	if ($volumeInfo['type']=='subdir' && is_array($volumeInfo['criterion']['field'])){
		$row = getInfo($moduleInfo,$path_array[2]);
		foreach($volumeInfo['criterion']['field'] as $key=>$value){
			if($row[$key]!=$value){
				errors_log("Access to this directory refused. Element field `".$key."` is not `".$value."`.");
				return 0;
			}
		}
	}
	if ($volumeInfo['type']=='subdir' && $volumeInfo['criterion']['dep']===true){
		require_once(dirname(__FILE__)."/../common/dependencies.inc.php");
		if ( existsDependency($moduleInfo->ID,$path_array[2],$contactModuleInfo->ID,$userID) ){
			$element_path = '/'.$path_array[1].'/'.$path_array[2];
			if(!is_dir($directoryRoot.$element_path)){
				makedir($directoryRoot.$element_path);
			}
		}else{
			errors_log("Access to this directory refused. Contact has to have dependency with this element to access it.");
			return 0;
		}
	}
	if ($volumeInfo['type']=='subdir' && $volumeInfo['criterion']['personal']===true){
		// a directory by user in this volume
		if ($userID != $path_array[2]){
			errors_log("Access to this directory refused. There is a subdirectory by user, and its not the directory for the current user.");
			return 0;
		}
	}
	return 'W';
	
}
function getFileXML($path,&$type,$addtionnal_info=""){
	$fc = 0; // a bit dirty but I'll change it soon
	$filedata = stat($path); // get some info about the file
	$fileattrib[$fc][0] = basename($path); //filename
	if (is_dir($path))
		$fileattrib[$fc][1] ='';//dirsize($path);
	else
		$fileattrib[$fc][1] = $filedata[7]; // size in bytes
	$fileSizeText = setSize($fileattrib[$fc][1]); // size for Text
	if(is_dir($path))
		$fileSizeText = '';
	$fileattrib[$fc][2] = $filedata[9]; // time of last modification
	if (is_dir($path))
		$fileattrib[$fc][3]="directory";
	else
		$fileattrib[$fc][3]="file";
	//---------------------------------------------------
	$short_path = getShortPath($path);
	$alias = getAlias($short_path);
	if ($alias!="")
		$alias="<LABEL>".encode_to_xml($alias)."</LABEL>";
	if($fileattrib[$fc][3]=="file"){
		$preview = "";
		$images_ext = array('jpg','jpeg','gif','png','bmp','jpe','swf');
		$ext = strtolower(getFileExt($short_path));
		$size = false;
		if(in_array ( $ext, $images_ext) && filesize("$path")<2048000){
			$size = @getimagesize("$path");
		}
		$return="<FILE ".( (is_array($size))?'width="'.$size[0].'" height="'.$size[1].'" ':'')."creationDate=\"".date ("Y-m-d H:i:s",$filedata[10])."\" modificationDate=\"".date ("Y-m-d H:i:s",$filedata[9])."\" size=\"".$fileSizeText."\" realsize=\"".$fileattrib[$fc][1]."\" ext=\"".encode_to_xml($ext)."\"><INFO><NAME>".encode_to_xml($fileattrib[$fc][0])."</NAME><PATH>".encode_to_xml($short_path)."</PATH><URL>".$GLOBALS['files_url'].encode_to_xml($short_path)."</URL>$alias<SIZE>".$fileSizeText."</SIZE><REALSIZE>".$fileattrib[$fc][1]."</REALSIZE><MODIFICATIONDATE>".$fileattrib[$fc][2]."</MODIFICATIONDATE>".$preview."</INFO>$addtionnal_info</FILE>";
		$type="file";
	}else{
		$volumeType = '';
		if (isVolume($short_path)){
			$volumeInfo = getVolumeInfo($short_path);
			$volumeType = ' volumeType="'.$volumeInfo['type'].'" ';
		}
		global $slash;
		if(function_exists('glob'))
		$filecount = count(glob($path.$slash."*"));
		$return="<DIRECTORY items=\"".$filecount."\" creationDate=\"".date ("Y-m-d H:i:s",$filedata[10])."\" modificationDate=\"".date ("Y-m-d H:i:s",$filedata[9])."\" $volumeType size=\"".$fileSizeText."\" realsize=\"".$fileattrib[$fc][1]."\"><INFO><NAME>".encode_to_xml($fileattrib[$fc][0])."</NAME><PATH>".encode_to_xml($short_path)."</PATH>$alias<SIZE>".$fileSizeText."</SIZE><REALSIZE>".$fileattrib[$fc][1]."</REALSIZE><MODIFICATIONDATE>".$fileattrib[$fc][2]."</MODIFICATIONDATE></INFO>$addtionnal_info</DIRECTORY>";
		$type="dir";
	}
	$type=$fileattrib[$fc][3];
	return $return;
}
function getAlias($short_path){
	$path_array= explodePath($short_path);
    //extract volume
    $volume=trim($path_array[1]);
	$moduleInfo = moduleInfo($volume);
	if (sizeof($path_array)==4 && $path_array[3]==""){
		$volumeInfo = getVolumeInfo($volume);
		if ($volumeInfo['type']=='subdir'){
			$row = getInfo($moduleInfo,$path_array[2]);
			return $row["Denomination"];
		}else
			return '';
	}
	return '';
}
function getShortPath($path){
	global $directoryRoot;
	$filesPath = $directoryRoot;//realpath(dirname(__FILE__)."/../../Files/");
	$real_path = $path;
	if (is_dir($real_path) && substr($real_path,-1)!="/")
		$real_path.="/";
	if ( substr($real_path,0,strlen($filesPath))===$filesPath )
		$short_path = substr($real_path,strlen($filesPath));
	return retransformPath($short_path);
}
function copy_content($source_dir,$target_dir,$overwrite_existing=true){
	if ($source_dir=='' || $target_dir=='')
		return FALSE;
	if (!file_exists($source_dir) || !file_exists($target_dir))
		return FALSE;
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
					makeDir($target_dir.$file);
					copy_content($source_dir.$file,$target_dir.$file,$overwrite_existing);
				}else{
					if($overwrite_existing or !file_exists($target_dir.$file)){
						debug_log('copy '.$source_dir.$file.' to '.$target_dir.$file);
						@copy($source_dir.$file,$target_dir.$file);
					}
				}
				//chmod ($target_dir.$file, 0777);
				chmod_Nectil($target_dir.$file);
			}
		}
		return true;
	}else
		return FALSE;
}

function compare_content($source_dir,$target_dir){
	if ($source_dir=='' || $target_dir=='')
		return FALSE;
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
					$result = compare_content($source_dir.$file,$target_dir.$file);
					if($result===false)
						return $result;
				}else{
					//copy($source_dir.$file,$target_dir.$file);
					if(!file_exists($target_dir.$file))
						return false;
					if(filesize($source_dir.$file)!=filesize($target_dir.$file))
						return false;
				}
			}
		}
		return true;
	}else
		return FALSE;
}
function copy_content_files_array($source_dir,$target_dir){
	if ($source_dir=='' || $target_dir=='')
		return FALSE;
	$source_dir = realpath($source_dir)."/";
	$target_dir = realpath($target_dir)."/";
	if (!file_exists($source_dir) || !file_exists($target_dir))
		return FALSE;
	if ($dir = @opendir($source_dir)) {
		$files_copied = array();
		while($file = readdir($dir)) {
			$isFileVisible = true;
			if($file == "." || $file == ".." )
				$isFileVisible = false;
			if ($isFileVisible){
				if (is_dir($source_dir.$file)){
					makeDir($target_dir.$file);
					$subFiles_copied = copy_content($source_dir.$file,$target_dir.$file);
					$files_copied = array_merge($files_copied,$subFiles_copied);
				}else{
					copy($source_dir.$file,$target_dir.$file);
					$files_copied[]=$target_dir.$file;
				}
				//chmod ($target_dir.$file, 0777);
				chmod_Nectil($target_dir.$file);
			}
		}
		return $files_copied;
	}else
		return FALSE;
}
function explodePath($path){
	$exploded = array();
	$expl1 = explode("\\",$path);
	foreach($expl1 as $expl1_cut){
		$expl2_cut = explode('/',$expl1_cut);
		$exploded = array_merge($exploded,$expl2_cut);
	}
	return $exploded;
}

function zip($source_location,$target_location){
	$OS = getServerOS();
	global $slash;
	$source_location = realpath($source_location);
	if (is_dir($source_location) && substr($source_location,-1)!=$slash)
		$source_location.=$slash;
	$target_location = realpath(dirname($target_location)).$slash.basename($target_location);
	
	$source_array= explodePath($source_location);
	$last_dir = $source_array[count($source_array)-2];
	//-------------------------------------------------------------------------//
	// implementation with zip command //
	//-------------------------------------------------------------------------//
	$keep_currentdir = getcwd();
	if (is_dir($source_location)){
		if($OS=='windows'){ // first going to the right volume (c: , d: etc )
			$path_array = array_slice($source_array,1,count($source_array));
			$path = $slash.(implode($slash,$path_array));
			
			chdir($path."..");
			$command = "call \"".dirname(__FILE__)."\\7za.exe\" a -tzip \"".$target_location."\" -r \"".$last_dir."$slash*\"";
		}else{
			chdir($source_location."..");
			$command = "zip -r \"".$target_location."\" \"./".$last_dir."\";";
		}
	}else{
		$path_array = array_slice($source_array,0,sizeof($source_array)-1);
		$path = implode($slash,$path_array);
		$last_file = $source_array[count($source_array)-1];
		if($OS=='windows'){
			$path_array2 = array_slice($source_array,1,count($source_array)-2);
			$path2 = $slash.(implode($slash,$path_array2));
			
			chdir($path2);
			$command = "call \"".dirname(__FILE__)."\\7za.exe\" a -tzip \"".$target_location."\" \"".$last_file."\"";
		}else{
			chdir($path);
			$command = "zip \"$target_location\" \"$last_file\"";
		}
	}
	debug_log($command);
	$sys = shell_exec($command);
	debug_log($sys);
	if($keep_currentdir)
		chdir($keep_currentdir);
	//-------------------------------------------------------------------------//
	// implementation with php lib //
	//-------------------------------------------------------------------------//
	if (!file_exists($target_location)){ // trying in native php
		require_once(dirname(__FILE__)."/../file/zip/pclzip.lib.php");
		$archive = new PclZip($target_location);
		$v_list = $archive->create($source_location,PCLZIP_OPT_REMOVE_PATH, $source_location ,PCLZIP_OPT_ADD_PATH, $last_dir);
	}
	//-------------------------------------------------------------------------//
	if (file_exists($target_location)){
		chmod_Nectil($target_location);
		if ($batch_filename!='' && file_exists(dirname(__FILE__).$slash.$batch_filename))
			unlink($batch_filename);
		return true;
	}
	
}
function unzip($source_location,$target_location,$blockedExtensions = false){
	
	$source_location = realpath($source_location);
	$target_location = realpath($target_location);
	global $slash;
	
	//-------------------------------------------------------------------------//
	// implementation with zip shell command //
	//-------------------------------------------------------------------------//
	
	$source_array= explode("/",$source_location);
	$last_dir = $source_array[count($source_array)-2];
	
	// unauthorized extensions
	if(!$blockedExtensions)
		$BlockedExt = $GLOBALS['BlockedExt']; // official sushee list (PHP, apache files)
	else
		$BlockedExt = $blockedExtensions;
		
	if ( is_array($BlockedExt) && sizeof($BlockedExt) != 0 ){
		$exclude = "-x ";
		$exclude_win = "";
		foreach($BlockedExt as $ext){
			$exclude.="'*.".$ext."' ";
			$exclude_win.="-x!*.".$ext." ";
		}
	}
	
	$OS = getServerOS();
	
	if($OS == 'windows'){
		
		// on Windows using 7za included with sushee
		$command = 'call "'.dirname(__FILE__).'\7za.exe" x -y '.$exclude_win.' -o"'.$target_location.'" "'.$source_location.'"';
		$sys = shell_exec("$command");
		
	}else{
		
		// unzip command should be installed, it not included with sushee
		$command = 'unzip -o "'.$source_location.'" '.$exclude.' -d "'.$target_location.'"';
		$sys = shell_exec("$command");
		
	}
	
	if(file_exists($target_location.$slash.'__MACOSX')){
		
		killDirectory($target_location.$slash.'__MACOSX');
	}
	return true;
}

function simplify($dir){
	if (substr($dir,-1)=="/")
		return substr($dir,0,-1);
	else
		return $dir;
}
// calculate the size of files in $dir
//use a real directory entry 
function dirsize($dir) {
    $size = 0;//-1;
    if ($dh = @opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file != "." and $file != "..") {
                $path = $dir."/".$file;
                if (is_dir($path)) {
                    $size += dirsize("$path/");
                }
                elseif (is_file($path)) {
                    $size += filesize($path);
                }
            }
        }
        closedir($dh);
    }
    return $size;
}

// set a readeadble size unit measure 
function setsize($size) {
	
	$real_size = $size;
	$size = abs($size);
	if ($real_size!=$size)
		$negative=true;
    // Setup some common file size measurements.
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte
    
    /* If it's less than a kb we just return the size, otherwise we keep going until
    the size is in the appropriate measurement range. */
    if($size < $kb) {
        return $size." B";
    }
    else if($size < $mb) {
        $rounded=round($size/$kb,2)." KB";
    }
    else if($size < $gb) {
        $rounded=round($size/$mb,2)." MB";
    }
    else if($size < $tb) {
        $rounded=round($size/$gb,2)." GB";
    }
    else {
       $rounded=round($size/$tb,2)." TB";
    }
	if ($negative)
	return "-".$rounded;
	else
	return $rounded;
}



function getFileExt($filename){
    return substr(strtolower(strrchr($filename, '.')), 1);
}
function getFilenameWithoutExt($filename){
	return substr($filename,0,strrpos($filename,'.'));
}
function BaseFilename($filename){
	$pos = strrpos($filename,'/');
	$end = strlen($filename);
	if($pos){
		if($pos == ($end-1) ){
			$pos = strrpos(substr($filename,0,$pos-1),'/');
			$end-=2;
		}
		return substr($filename,$pos+1,$end-$pos);
	}else
		return $filename;
}
//remove all non accepted caracters
function setFilename($filename) {
    // clean up file name
	$filename = removeaccents(utf8_to_unicodeEntities($filename));
	$filename = str_replace(array(' ','%20'),'-',$filename);
    return ereg_replace("[^a-zA-Z0-9._-]", "", $filename );
}
 
// check if a file is hidden
//may give a file in a directory ;-)
function hidecheck($ckfilename) {
    global $HiddenFiles;
    $ckfilename=basename($ckfilename);
    $okay=true;
	if(substr($ckfilename,0,1)=='.')
		$okay = false;
	if (is_array($HiddenFiles) && $okay){
		foreach($HiddenFiles as $name) {
			// check the name is not the same as the hidden file name
			if($ckfilename == $name) {
				$okay = false;  //false if not okay
				break;
			}
		}
	}
    return $okay;
}

// check if a file is accepted (by extension)
//may give a file in a directory ;-)
function uploadOK($ckfilename) {
    global $BlockedExt;
    $extension= getFileExt(basename($ckfilename));
    
    $okay=true;
    foreach($BlockedExt as $name) {
        // check the extension is not a blocked extension
        if($extension == $name) {  
            $okay = false;  //false if not okay
            break;
        }
    }   
    return $okay;
}

// check if a target is a directory
function isDir($pathname) {
	return is_dir($pathname)? 1:0;
}

//create a directory path  hehe
function makedir($pathname) {
	global $slash;
	
	$pathname = str_replace(array('/',"\\"),$slash,$pathname);
	# allow create of multiple directories
	$dirs = explode($slash, $pathname);
	$root_dirs = explode($slash,$GLOBALS['directoryRoot']);
	// will not allow to create directory under the sushee directory
	$min_root_dir = sizeof($root_dirs)-2;
	$temp = $slash;
	# for every directory required
	for($a = 1;$a < sizeof($dirs);$a++) {
		# create new directory over previous directories
		if($dirs[$a]){
			$temp .= $dirs[$a].$slash;
			// not creating directory under sushee directory
			if($a > $min_root_dir){
				makeDirUtil($temp);
			}
		}
	}
}

//set full right to directory
function makeDirUtil($pathname) {
    global $directoryCHMOD;
	# does directory already exist ?
	if(!isDir($pathname)) {
		$old_umask = umask(0);
		$result = mkdir($pathname, $directoryCHMOD);
		umask($old_umask);
		if($result == 0) {
			$str = "Directory $pathname could not be created. Please contact your Nectil Administrator.";
			//die( xml_msg("1","-1","-1",$str.":".$path));
		}
	}
}

// revursively delete a directory and subdirs..
function killDirectory($pathname,$desc_check=FALSE) {
	$files = getAllFiles($pathname); 
	$delete_dir = TRUE;
	foreach($files as $file) {
		if(is_dir($pathname.'/'.$file)) { 
			$res = killDirectory($pathname.'/'.$file,$desc_check); 
			if(!$res)
				$delete_dir = false;
		}else {
			$is_used = FALSE;
			if ($desc_check)
				$is_used = isFileUsed(getShortPath($pathname.'/'.$file));
			if ($is_used)
				$delete_dir = FALSE;
			else {
				if(!unlink($pathname.'/'.$file)){
					die( xml_msg("1","-1","-1","Unabled to delete:".$pathname.'/'.$file.": permission denied, please contact your Nectil administrator"));
				}
			}
		}
	}
	if ($delete_dir){
		if(!rmdir($pathname)){
			die( xml_msg("1","-1","-1","Unabled to delete:".$pathname.": permission denied, please contact your Nectil administrator"));
		}
	}
	return $delete_dir;
}

//check if a directory is Empty
function isDirEmpty($directory) {

	$dir = opendir($directory);
	$res=true;
	
	$file = readdir($dir);
	while($file) {
		if($file != '.' && $file != '..'&& $file != false) {
			$res=false;
			break;
		}
		$file = readdir($dir);
	}

	closedir($dir);
	return $res;
	
}

//get all the files of a directory
function getAllFiles($directory) {
	$f = array();
	$dir = opendir($directory);
	$file = readdir($dir);
	while($file) {
		if($file != '.' && $file != '..') {
			array_push($f, $file);
		}
		$file = readdir($dir);
	}
	
	closedir($dir);
	
	return $f;
}

//returns TRUE if the haystack string contains all of the strings from needle array,
//else returns FALSE (case-insensitive)
function arrALListr($haystack, $needle){

    foreach($needle as $n){
        if (stristr($haystack , $n) == false ){
            return false;
        }
    }
    return true;
}


function findfile($location,$matchArray,$directoryRoot,&$matchedfiles) {
    if(!is_dir($directoryRoot.$location) || !is_array($matchArray)) {
       return false;
    }
 
    $all = opendir($directoryRoot.$location);
    
    while ($file = readdir($all)) {
	   if ($file != '..' && $file != '.'){
		   if (is_dir($directoryRoot.$location.'/'.$file) ) {
			  findfile($location.'/'.$file,$matchArray,$directoryRoot,$matchedfiles);
		   }
		   if (getPathSecurityRight($location.'/'.$file)!==0 ) {
			  if(arrALListr($file,$matchArray)){
				 //array_push($matchedfiles,$location.'/'.$file);
				 $matchedfiles[]=$location.'/'.$file;
			  }
		   }
	   }
    }
    closedir($all);
}

//stop program end send an error message
function htmlErrorMsg($title,$msg){
    $html = "<html><head><title>".$title."</title></head><body text=".'"black"'.">".$msg."</body></html>";
    die($html);
}
function filesList($queryname,$pathRequest){
	global $directoryRoot;
	$pathRequest = transformPath(unhtmlentities($pathRequest));
	if(substr($directoryRoot.$pathRequest,-1)=='/')
	{
		$path=substr($directoryRoot.$pathRequest, 0, -1);
	}
	else
	{
		$path=$directoryRoot.$pathRequest;
	}

	// we call that function because it creates the directory if necessary
	getPathSecurityRight($pathRequest);

	$fileList="";
	$directoryList="";
	/* Build the table rows which contain the file information */
	if ($dir = @opendir($path)) {
			/* loop once for each name in the directory */
			while($file = readdir($dir)) {
				$isFileVisible=true;
				
				// if the name is not a directory and the name is not the name of this program file
				if($file == "." || $file == ".." || $file == "$ThisFileName") {
					$isFileVisible = false;
				}
				$right=0;
				if($isFileVisible)
					$right =  getPathSecurityRight($pathRequest.$file);
				if ( $right===0 )
					$isFileVisible = false;
				//if this file is hidden, do net show it
				if (!hidecheck($file)) { $isFileVisible=false; }
				if($pathRequest=='/' && ($file=='tmp' || $file=='pdf'))
					$isFileVisible=false;
				// if there were no matches the file should not be hidden
				if($isFileVisible) {
					$type="";
					$file_info = getFileXML($path.'/'.$file,$type);
					if ($type=="directory")
						$directoryList.=$file_info;
					else
						$fileList.=$file_info;
				}
			}
	}else if(file_exists($path)){
		$file_info = getFileXML($path);
		$fileList.=$file_info;
	}else {  
		 //write an error message
		 return generateMsgXML(1,"Directory `".$path."` doesn't exist",0,'',$queryname);
		//die( xml_msg("1","-1","-1","Directory doesn't exist:".$GLOBALS["nectil_dir"]));
	}
	global $slash;
	global $HDDSpace;
	global $HDDTotal;
	global $freespace;
	$paths_to = '';
	if ($GLOBALS["php_request"] && $dir){
		$paths_to_array = explode('/',$pathRequest);
		$cur_path_to = '/';
		$paths_to.="<PATHS><PATH>/</PATH>";
		foreach($paths_to_array as $elem){
			if($elem){
				$cur_path_to.=$elem.'/';
				$paths_to.="<PATH>".encode_to_xml($cur_path_to)."</PATH>";
			}
		}
		$paths_to.="</PATHS>";
	}
	if(function_exists('glob'))
		$filecount = count(glob($path.$slash."*"));
	$strResponse ="<TREE name=\"$queryname\" items=\"".$filecount."\" maxspace=\"".$HDDSpace."\" actualspace=\"".$HDDTotal."\" freespace=\"".$freespace."\">".$paths_to.$fileList.$directoryList."</TREE>";
	return $strResponse;
}
function published_filesList($path){
	if ($dir = @opendir($path)) {
		$images_ext = array('jpg','jpeg','gif','png','bmp','jpe','swf');
		$list='<TREE path="'.encode_to_xml(getShortPath($path)).'" name="'.encode_to_xml(basename($path)).'">';
		while($file = readdir($dir)) {
			$isFileVisible=true;
			// if the name is not a directory and the name is not the name of this program file
			if($file == "." || $file == ".." || $file == "$ThisFileName") {
				$isFileVisible = false;
			}
			if (!hidecheck($file)) { $isFileVisible=false; }
			// if there were no matches the file should not be hidden
			if($isFileVisible) {
				if(is_dir($path.'/'.$file)){
				//published_filesList()
				}else{
					
					$ext = strtolower(getFileExt($file));
					if(in_array ( $ext, $images_ext))
						$size = @getimagesize($path.'/'.$file);
					$attributes='';
					if ($size){
						$attributes.='width="'.$size[0].'" ';
						$attributes.='height="'.$size[1].'" ';
					}
					$list.='<FILE path="'.encode_to_xml(getShortPath($path.'/'.$file)).'" '.$attributes.'name="'.encode_to_xml($file).'" ext="'.encode_to_xml($ext).'" shortname="'.encode_to_xml(getFilenameWithoutExt($file)).'"/>';
				}
			}
		}
		return $list."</TREE>";
	}else
		return false;
}

function createTree($actualPath,$arr,$level,$directoryRoot){
    if(count($arr) <= $level)
        return "";

	if ($level > 0)
	    $actualPath .= "/".$arr[$level];
	
    $level++;
    /* Build the table rows which contain the file information */
    $fileList="";
    $directoryList="";
	$path_array = array_slice($arr,0,$level);
	$path = implode("/",$path_array);
	$directoryRoot = $GLOBALS["directoryRoot"];
	if (file_exists($directoryRoot.$actualPath) && !is_dir($directoryRoot.$actualPath))
		return "";
    if($dir = @opendir($directoryRoot.$actualPath)) {
        /* loop once for each name in the directory */
        while($file = readdir($dir)) {
            $isFileVisible=true;
			// if the name is not a directory and the name is not the name of this program file
            if($file == "." || $file == ".." /*|| $file == "$ThisFileName"*/) {
                $isFileVisible = false;
            }
			$right =  getPathSecurityRight($path."/".$file);
			if ( $right===0 )
				$isFileVisible = false;
			//if this file is hidden, do net show it
            if (!hidecheck($file)) { $isFileVisible=false; }        
            // if there were no matches the file should not be hidden
            if($isFileVisible) {
				$type="";
				if ($file==$arr[$level])
					$deeper_info = createTree($actualPath,$arr,$level,$directoryRoot);
				else
					$deeper_info = "";
				//echo $path."/".$file." ".jumpOver($path."/".$file)."<br/>";
				$jump = jumpOver($path."/".$file);
				if ( $jump && $file==$arr[$level]){
					$file_info = $deeper_info;
				}else if($jump){
					$arr2 = $arr;
					$arr2[$level]=$file;
					$file_info = createTree($actualPath,$arr2,$level,$directoryRoot);
				}else{
					if ($deeper_info!="")
						$deeper_info="<TREE level='$level'>".$deeper_info."</TREE>";
					$file_info = getFileXML($directoryRoot.$actualPath.'/'.$file,$type,$deeper_info);
				}
				if ($type=="directory")
					$directoryList.=$file_info;
				else
					$fileList.=$file_info;
            }
        }
        return $fileList.$directoryList;
    }
}
function getTree($queryname,$pathRequest){
	$pathRequest = transformPath(unhtmlentities($pathRequest));
	global $directoryRoot;
	$path=substr($directoryRoot.$pathRequest, 0, -1);
	// we call that function because it creates the directory if necessary
	getPathSecurityRight($pathRequest);
	//security and prerequisities
	$path_array= explode("/",$pathRequest);
	
	global $slash;
	$strResponse.="<TREE name=\"$queryname\" items=\"".count(glob($path.$slash."*"))."\">";
	
	$strResponse.=createTree("",$path_array,0,$directoryRoot)."</TREE>";
	return $strResponse;
}

function file_upload_handle($targetPath,&$files,$options,&$uploaded_files,&$decompressed_files,&$error_files,$unzipped_files=false){
	global $directoryRoot;
	if(substr($targetPath,-1)=='/')
		$targetPath = substr($targetPath,0,-1);
	$overwrite=$options["overwrite"];
	$unzip=$options["unzip"];
	foreach ($files['name'] as $key=>$name) {
        //get file size
        $size=$files['size'][$key];
        
        if ($size) {
            // clean up file name
			
            $name = setFilename($name);
			
			$file_ok = true;
            //check if this file is accepted
            if(!uploadOK($name)){
                //htmlErrorMsg("Upload error","Scripts files not accepted");
				$error_files[]=array('name'=>$name,'error'=>"Scripts files not accepted");
				$file_ok = false;
            }
            if(!hidecheck($name)){
				$error_files[]=array('name'=>$name,'error'=>"This filename :".$name." is not accepted");
                //htmlErrorMsg("Upload error","This filename :".$name." is not accepted ");
				$file_ok = false;
            }
			if($file_ok){
				$location = $directoryRoot.$targetPath."/".$name;
				//debug_log($location);
				if(!isset($overwrite))
					$overwrite="";	
			
				//  overwrite 
				// rename_existing 
				// rename_uploaded
				//check if file exist	    
				if(file_exists($location)){
					if($overwrite=="overwrite"){
						unlink($location);//on efface le target
					}else if($overwrite=="rename_existing"){
						$temp_idx=0;
						while(file_exists($location.'_bkp'.$temp_idx)){
							$temp_idx++;
						}
						$newName=$location.'_bkp'.$temp_idx;
						$renamed = rename ($location, $newName);
						
					}else if($overwrite=="rename_uploaded"){
						$temp_idx=0;
						while(file_exists($location.'_new'.$temp_idx)){
							$temp_idx++;
						}
						$location=$location.'_new'.$temp_idx;
					}
				}
			
				copy($files['tmp_name'][$key],$location);
				//chmod ($location, 0777);
				chmod_Nectil($location);
				unlink($files['tmp_name'][$key]);
				
				$ext = getFileExt($name);
				if($ext=="zip" && isset($unzip) && $unzip=="on" ){
					$archivedir=realpath($directoryRoot.$targetPath);
					if ($overwrite=="rename_existing" || $overwrite=="rename_uploaded"){
						// first copying in a tmp directory to make the good treatment
						$tmp_dir = realpath($directoryRoot."/tmp").'/'.date('YmdHis');
						makedir($tmp_dir);
						unzip($location,$tmp_dir);
						function rename_existing($source_dir,$rename_dir,$particule='_new'){
							$source_dir = realpath($source_dir)."/";
							$rename_dir = realpath($rename_dir)."/";
							if (!file_exists($source_dir) || !file_exists($rename_dir))
								return FALSE;
							if ($dir = @opendir($rename_dir)) {
								while($file = readdir($dir)) {
									$location_source = $source_dir.$file;
									$location_rename = $rename_dir.$file;
									if ($file != "." && $file != ".." && file_exists($location_source)){
										$temp_idx=0;
										while(file_exists($location_source.$particule.$temp_idx)){
											$temp_idx++;
										}
										$newName=$location_rename.$particule.$temp_idx;
										$renamed = rename ($location_rename, $newName);
									}
								}
							}else
								return FALSE;
						}
						cleanFilenames_in($tmp_dir);
						if($overwrite=="rename_existing"){
							rename_existing($tmp_dir,$archivedir,'_bkp');
						}else if($overwrite=="rename_uploaded"){
							rename_existing($archivedir,$tmp_dir,'_new');
						}
						$unzipped_files = copy_content_files_array($tmp_dir,$archivedir);
						killDirectory($tmp_dir);
					}else{
						$tmp_dir = realpath($directoryRoot."/tmp").'/'.date('YmdHis');
						makedir($tmp_dir);
						unzip($location,$tmp_dir);
						cleanFilenames_in($tmp_dir);
						copy_content($tmp_dir,$archivedir);
						killDirectory($tmp_dir);
						//unzip($location,$archivedir);
					}
					// removing the zip
					unlink($location);
					$decompressed_files[]=$name;
					$uploaded_files[]=$name;
				}else{
					$uploaded_files[]=$name;
				}
			}
			
        }
    }
}
function file_upload_handle2($targetPath,&$files,$options,&$uploaded_files,&$decompressed_files,&$error_files/*,&$unzipped_files*/){
	global $directoryRoot;
	$overwrite=$options["overwrite"];
	$unzip=$options["unzip"];
	foreach ($files['name'] as $key=>$name) {
        //get file size
        $size=$files['size'][$key];
        
        if ($size) {
            // clean up file name
			
            $name = setFilename($name);
			
			$file_ok = true;
            //check if this file is accepted
            if(!uploadOK($name)){
                //htmlErrorMsg("Upload error","Scripts files not accepted");
				$error_files[]=array('name'=>$name,'error'=>"Scripts files not accepted");
				$file_ok = false;
            }
            if(!hidecheck($name)){
				$error_files[]=array('name'=>$name,'error'=>"This filename :".$name." is not accepted");
                //htmlErrorMsg("Upload error","This filename :".$name." is not accepted ");
				$file_ok = false;
            }
			if($file_ok){
				$location = $directoryRoot.$targetPath."/".$name;
				//debug_log($location);
				if(!isset($overwrite))
					$overwrite="";	
			
				//  overwrite 
				// rename_existing 
				// rename_uploaded
				//check if file exist	    
				if(file_exists($location)){
					if($overwrite=="overwrite"){
						unlink($location);//on efface le target
					}else if($overwrite=="rename_existing"){
						$temp_idx=0;
						while(file_exists($location.'_bkp'.$temp_idx)){
							$temp_idx++;
						}
						$newName=$location.'_bkp'.$temp_idx;
						$renamed = rename ($location, $newName);
						
					}else if($overwrite=="rename_uploaded"){
						$temp_idx=0;
						while(file_exists($location.'_new'.$temp_idx)){
							$temp_idx++;
						}
						$location=$location.'_new'.$temp_idx;
					}
				}
			
				copy($files['tmp_name'][$key],$location);
				//chmod ($location, 0777);
				chmod_Nectil($location);
				unlink($files['tmp_name'][$key]);
				
				$ext = getFileExt($name);
				if($ext=="zip" && isset($unzip) && $unzip=="on" ){
					$archivedir=realpath($directoryRoot.$targetPath);
					if ($overwrite=="rename_existing" || $overwrite=="rename_uploaded"){
						// first copying in a tmp directory to make the good treatment
						$tmp_dir = realpath($directoryRoot."/tmp").'/'.date('YmdHis');
						makedir($tmp_dir);
						unzip($location,$tmp_dir);
						function rename_existing($source_dir,$rename_dir,$particule='_new'){
							$source_dir = realpath($source_dir)."/";
							$rename_dir = realpath($rename_dir)."/";
							if (!file_exists($source_dir) || !file_exists($rename_dir))
								return FALSE;
							if ($dir = @opendir($rename_dir)) {
								while($file = readdir($dir)) {
									$location_source = $source_dir.$file;
									$location_rename = $rename_dir.$file;
									if ($file != "." && $file != ".." && file_exists($location_source)){
										$temp_idx=0;
										while(file_exists($location_source.$particule.$temp_idx)){
											$temp_idx++;
										}
										$newName=$location_rename.$particule.$temp_idx;
										$renamed = rename ($location_rename, $newName);
									}
								}
							}else
								return FALSE;
						}
						cleanFilenames_in($tmp_dir);
						if($overwrite=="rename_existing"){
							rename_existing($tmp_dir,$archivedir,'_bkp');
						}else if($overwrite=="rename_uploaded"){
							rename_existing($archivedir,$tmp_dir,'_new');
						}
						$unzipped_files = copy_content_files_array($tmp_dir,$archivedir);
						killDirectory($tmp_dir);
					}else{
						$tmp_dir = realpath($directoryRoot."/tmp").'/'.date('YmdHis');
						makedir($tmp_dir);
						unzip($location,$tmp_dir);
						cleanFilenames_in($tmp_dir);
						copy_content($tmp_dir,$archivedir);
						killDirectory($tmp_dir);
						//unzip($location,$archivedir);
					}
					// removing the zip
					unlink($location);
					$decompressed_files[]=$name;
					$uploaded_files[]=$name;
				}else{
					$uploaded_files[]=$name;
				}
			}
			
        }
    }
}
function fileUpload($location,$name=false){
    if(file_exists($location)){
		if(!$name)
        	$name=basename($location);
        $filesize=filesize($location);
        
        header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Cache-Control: public");
	    header("Content-Description: File Transfer");
		$ext = strtolower(getFileExt($name));
		switch( $ext ) {
		      case "pdf": $ctype="application/pdf"; break;
		      case "exe": $ctype="application/octet-stream"; break;
		      case "zip": $ctype="application/zip"; break;
		      case "doc": $ctype="application/msword"; break;
		      case "xls": $ctype="application/vnd.ms-excel"; break;
		      case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
		      case "gif": $ctype="image/gif"; break;
		      case "png": $ctype="image/png"; break;
		      case "jpeg":
		      case "jpg": $ctype="image/jpeg"; break;
		      case "mp3": $ctype="audio/mpeg"; break;
		      case "wav": $ctype="audio/x-wav"; break;
		      case "mpeg":
		      case "mpg":
		      case "mpe": $ctype="video/mpeg"; break;
		      case "mov": $ctype="video/quicktime"; break;
		      case "avi": $ctype="video/x-msvideo"; break;
		      default: $ctype="application/force-download";
		 }
        header("Content-Type: ".$ctype );
		header("Content-Disposition: attachment; filename=\"".encodeQuote($name)."\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".$filesize);
        

		@readfile($location);
    }else{
        htmlErrorMsg("Download error","Target file ".$location." doesn't exist");
    }
}
function fileDownload($location,$name=false){
	fileUpload($location,$name);
}

// copies the files from attachment to dest_dir. attachment can be a file or a directory
function get_mail_files_together($attachment,$dest_dir){
	$attachments_filenames = array();
	if (is_dir($attachment) && $dir = @opendir($attachment)) {
		//$values['Folder']=$values['Attachments'];
		
		
		if(substr($attachment,-1)!='/')
			$attachment.='/';
		while($file = readdir($dir)) {
			$complete_file = $attachment.$file;
			if ($file != "." && $file != ".." && file_exists($complete_file) && !is_dir($complete_file)){
				
				$attachments_filenames[]=$file;
				if($complete_file!=$GLOBALS["directoryRoot"].$dest_dir.$file){
					debug_log('copying '.$attachcomplete_filement.' to '.$GLOBALS["directoryRoot"].$dest_dir.$file);
					@copy($complete_file,$GLOBALS["directoryRoot"].$dest_dir.$file);
				}
			}
		}
		
		//
	}else if(is_file($attachment)){
		
		$basefilename = BaseFilename($attachment);
		$attachments_filenames[]=$basefilename;
		if($attachment!=$GLOBALS["directoryRoot"].$dest_dir.$basefilename){
			debug_log('copying '.$attachment.' to '.$GLOBALS["directoryRoot"].$dest_dir.$basefilename);
			@copy($attachment,$GLOBALS["directoryRoot"].$dest_dir.$basefilename);
		}
		//$values['Folder']=getShortPath(dirname($attachment));
	}else{
		errors_log($attachment.' doesnt exist to attach to mail');
	}
	return $attachments_filenames;
}