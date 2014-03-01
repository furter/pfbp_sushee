<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/file.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");

class Sushee_MimeTypeSolver extends SusheeObject{
	
	var $types = array(
		"pdf"=>"application/pdf",
		"csv"=>"text/csv",
		"exe"=>"application/octet-stream",
		"zip"=>"application/zip",
		"doc"=>"application/msword",
		"xls"=>"application/vnd.ms-excel",
		"ppt"=>"application/vnd.ms-powerpoint",
		"gif"=>"image/gif",
		"png"=>"image/png",
		"jpeg"=>"image/jpeg",
		"jpg"=>"image/jpeg",
		"mp3"=>"audio/mpeg",
		"wav"=>"audio/x-wav",
		"mpeg"=>"video/mpeg",
		"mpg"=>"video/mpeg",
		"mpe"=>"video/mpeg",
		"mov"=>"video/quicktime",
		"avi"=>"video/x-msvideo"
	);
	
	function getExtension($mimetype){
		foreach($this->types as $key=>$value){
			if($value==$mimetype){
				return $key;
			}
		}
		return false;
	}
	
	function getMimeType($extension){
		$extension = strtolower($extension);
		return $this->types[$extension];
	}
	
}

class File extends SusheeObject{
	var $path;
	var $file_writer = false;
	var $fp = null;
	
	function File($nectil_path){
		if(substr($nectil_path,0,strlen(getFilesRoot()))==getFilesRoot())
			$nectil_path = substr($nectil_path,strlen(getFilesRoot()));
		if(is_dir(getFilesRoot().$nectil_path) && substr($nectil_path,-1)!='/')
			$nectil_path.='/';
		$this->path = $nectil_path;
	}
	
	function getXML(){
		$type = '';
		return getFileXML($this->getCompletePath(),$type);
	}
	
	function getSecurity(){
		return getPathSecurityRight($this->getPath());
	}
	
	function isForbidden(){
		// dangerous files : should not be uploaded
		// PHP files
		$extension = $this->getExtension();
		$forbidden_extensions = array('php','php4','php5','php3');
		if(in_array($extension,$forbidden_extensions)){
			return true;
		}
		// Apache files
		if($this->getName()=='.htaccess' || $this->getName()=='.htpasswd'){
			return true;
		}
		
		return false;
		
	}
	
	function getExtension(){
		return getFileExt($this->path);
	}
	
	function setExtension($ext){
		$current_extension = $this->getExtension();
		if($current_extension){
			$new_path = substr($this->path,0,-(strlen($current_extension))).$ext;
		}else{
			$new_path = $this->path.'.'.$ext;
		}
		if($this->exists()){
			$this->copy(new File($new_path));
			$this->delete();
			$this->path = $new_path;
		}else{
			$this->path = $new_path;
		}
	}
	
	function exists(){
		$existence = file_exists($this->getCompletePath());
		return $existence;
	}
	
	function isSymlink(){
		$complete_path = $this->getCompletePath();
		if(substr($complete_path,-1)=='/'){
			$complete_path = substr($complete_path,0,-1);
		}
		return is_link($complete_path);
	}
	
	function getName(){
		return basefilename($this->path);
	}
	
	function getShortName(){
		return getFilenameWithoutExt($this->getName());
	}
	
	function isDirectory(){
		return is_dir(getFilesRoot().$this->path);
	}
	
	function isFolder(){
		return $this->isDirectory();
	}

	function getPath(){
		return $this->path;
	}

	function getCompletePath(){
		global $slash;
		$complete_path = getFilesRoot().$this->getPath();
		$complete_path = str_replace(array('\\','/'),$slash,$complete_path);
		return $complete_path;
	}
	
	function output(){
		header("Content-Type: ".$this->getMimeType() );
		header("Content-Length: ".$this->getSize());
		@readfile($this->getCompletePath());
	}
	
	function getSize(){
		return @filesize($this->getCompletePath());
	}
	
	function getReadableSize(){
		return setsize($this->getSize());
	}
	
	function forceDownload($download_name=false){
		fileDownload($this->getCompletePath(),$download_name);
	}
	
	function getMimeType(){
		$resolver = new Sushee_MimeTypeSolver();
		$ext = $this->getExtension();
		$ext = strtolower($ext);
		$ctype = $resolver->getMimeType($ext);
		if(!$ctype){
			$ctype="application/force-download";
		}
		return $ctype;
	}
	
	function copy($destination){
		if(!$destination)
			return false;
		if(!is_object($destination)){
			if(is_string($destination)){
				$destination = new File($destination);
			}else
				return false;
		}
		if($this->exists()){
			if($destination->isDirectory() && !$this->isDirectory()){
				$destination_file = $destination->getChild($this->getName());
				
				@copy($this->getCompletePath(),$destination_file->getCompletePath());
			}else{
				
				@copy($this->getCompletePath(),$destination->getCompletePath());
			}
		}
			
		if($destination->exists()){
			if($destination_file)
				return $destination_file;
			return $destination;
		}else{
			return false;
		}	
	}

	function copyContent($destination){
		if(!$destination)
			return false;
		if(!is_object($destination)){
			if(is_string($destination)){
				$destination = new File($destination);
			}else
				return false;
		}
		if($this->exists()){
			if(!$destination->isDirectory() || !$this->isDirectory()){
				return false;
			}else{
				$this_as_a_dir = new Folder($this->getPath());
				while($file = $this_as_a_dir->next()){
					$file->copy($destination);
				}
			}
			
		}
	}
	
	function toString(){
		return file_in_string($this->getCompletePath());
	}
	
	
	function getParent(){
		$parent_path = dirname($this->getCompletePath());
		$parent = new File($parent_path);
		if($parent->exists()){
			return $parent;
		}else{
			return false;
		}
	}
	
	function getChild($name){
		if($this->isDirectory()){
			$child = new File($this->getPath().$name);
			if($child->exists() && $child->isDirectory()){
				return new Folder($child->getPath());
			}else
				return $child;
		}else{
			return false;
		}
	}
	
	function delete(){
		if($this->getPath()!='' && $this->getPath()!='/'){
			if($this->exists()){
				if($this->isDirectory()){
					killDirectory($this->getCompletePath());
				}else{
					$this->unlink();
				}
			}
		}else{
			errors_log('Forbidden to delete recursively '.$this->getCompletePath());
		}
		
		return $this->exists();
	}
	
	function unlink(){
		global $slash;
		//die('unlink '.$this->getCompletePath());
		if($this->exists()){
			$path = $this->getCompletePath();
			// if its a symlink to a directory we cut the slash at the end, because if not, we would receive an error saying that the directory is not empty
			if(substr($path,-1)==$slash){
				$path = substr($path,0,-1);
			}
			@unlink($path);
		}
	}
	
	function getUrl(){
		return $GLOBALS["files_url"].$this->getPath();
	}
	
	function create(){
		if(!$this->exists()){
			if(!$this->isDirectory()){
				saveInFile('',$this->getCompletePath());
			}else{
				makedir($this->getCompletePath());
			}
		}
		
		return $this->exists();
	}
	
	function createDirectory($name){
		if($this->isDirectory()){
			$dir = new Folder($this->getPath().$name);
			if(!$dir->exists()){
				$dir->create();
			}
			return $dir;
		}else{
			return false;
		}
		
	}
	
	function createFile($name){
		if($this->isDirectory()){
			$file = new File($this->getPath().$name);
			if(!$file->exists()){
				$file->create();
			}
			return $file;
		}else{
			return false;
		}
		
	}
	
	function save($str){
		if(!$this->isDirectory()){
			saveInFile($str,$this->getCompletePath());
		}
	}
	
	function compress(){
		$source = $this->getCompletePath();
		$parent = $this->getParent();
		if(!$parent)
			return false;
		$target = $parent->getCompletePath().$this->getName().'.zip';
		$zipped = zip($source,$target);
		if(!$zipped){
			return false;
		}else{
			$className = get_class($this);
			return new $className($target);
		}
	}
	//------------
	// blockedExtensions : a list of file extensions we dont want to be unizpped if they are in an archive. Replaces the official sushee list (which contains PHP, apache files) if set
	//------------
	function uncompress($blockedExtensions = false){
		$source = $this->getCompletePath();
		$parent = $this->getParent();
		if(!$parent){
			return false;
		}
		$target = $parent->getCompletePath();
		$unzipped = unzip($source,$target,$blockedExtensions);
		return $unzipped;
	}
	
	function isWritable(){
		return is_writable($this->getCompletePath());
	}
	
	function isWorkDirectory(){
		$path = $this->getPath();
		if($path=='/tmp/' || $path=='/cache/' || $path=='/pdf/'){
			return true;
		}
		return false;
	}
	
	function append($str){
		$perm = 'a+';
		if($this->file_writer == false){
			$this->file_writer = fopen($this->getCompletePath(), $perm); // binary update mode
		}
		if($this->file_writer!==false){
			fwrite($this->file_writer, $str);
			//fclose($file);
			return true;
		}
		return false;
	}
	
	function isOld(){
		$folder = $this->getParent();
		if($folder){
			$max_age = false;
			$max_age_default = 2678400; // one month
			$max_age_oneday = 86400; // one day
			switch($folder->getPath()){
				case '/images/':
					if(isset($GLOBALS["ImageMaxAge"])){
						$max_age = $GLOBALS["ImageMaxAge"];
					}else{
						$max_age = $max_age_default;
					}
					break;
				case '/tmp/':
					if(isset($GLOBALS["TempMaxAge"])){
						$max_age = $GLOBALS["TempMaxAge"];
					}else{
						$max_age = $max_age_oneday;
					}
					break;
				case '/pdf/':
					if(isset($GLOBALS["PDFMaxAge"])){
						$max_age = $GLOBALS["PDFMaxAge"];
					}else{
						$max_age = $max_age_default;
					}
					break;
				case '/cache/':
				case '/cache/xsushee/':
				case '/cache/html/':
					if(isset($GLOBALS["CacheMaxAge"])){
						$max_age = $GLOBALS["CacheMaxAge"];
					}else{
						$max_age = $max_age_default;
					}
					break;
				case '/confirm/':
					if(isset($GLOBALS["ConfirmMaxAge"])){
						$max_age = $GLOBALS["ConfirmMaxAge"];
					}else{
						$max_age = $max_age_default;
					}
					break;
				default:
					$max_age = false;
			}
			if($max_age!=false){
				$path = $this->getCompletePath();
				$fmtime = filemtime($path);
				if(!$fmtime){
					return false;
				}
				$time = time();
				$last_mod = ($time-$fmtime);
				if($last_mod > $max_age){
					return true;
				}
			}
		}
		return false;
	}
	
	function getModificationTime(){
		$path = $this->getCompletePath();
		$fmtime = filemtime($path);
		return $fmtime;
	}
	
	function rename($newName){
		$parent = $this->getParent();
		if($parent){
			$old_path = $this->path;
			
			$new_path = $parent->getCompletePath().$newName;
			if($this->isDirectory()){
				$new_path.='/';
			}
			rename($this->getCompletePath(),$new_path);
			
			$this->path = $parent->getPath().$newName;
			if($this->exists()){
				if($this->isFolder() && substr($this->path,-1)!='/'){
					$this->path.='/';
				}
				return true;
			}
			// if rename failed, we return to the old situation
			$this->path = $old_path;
			return false;
		}
		
	}
	
	function _openForRead(){
		if($this->fp === null){
			if(is_writable($this->getCompletePath())){
				$this->fp = @fopen($this->getCompletePath(), 'rb+');
			}else{
				$this->fp = @fopen($this->getCompletePath(), 'rb');
			}
			
		}
		return $this->fp;
	}
	
	function readBytes($bytes){
		$fp = $this->_openForRead();
		if($fp!==null){
			return fread($fp,$bytes);
		}else{
			return false;
		}
	}
	
	function goToOffset($offset){
		$fp = $this->_openForRead();
		fseek($fp,$offset);
	}
	// get current reading offset in the file
	function getOffset(){
		$fp = $this->_openForRead();
		return ftell($fp);
	}
}

class Folder extends File{
	var $dir_reader = false;
	
	function Folder($path){
		parent::File($path);
	}
	
	function create(){
		//$this->logFunction('create');
		if(!$this->exists()){
			global $slash;
			$last_char = substr($this->path,-1);
			if($last_char!='\\' && $last_char!='/')
				$this->path = $this->path.$slash;
			makedir($this->getCompletePath());
		}
		return $this->exists();
	}
	
	function next(){
		return $this->getNextChildren();
	}
	
	function getNextFile(){
		return $this->getNextChildren();
	}
	
	function reset(){
		$this->dir_reader=false;
	}
	
	function getNextChildren(){
		//$this->logFunction('getNextChildren');
		if($this->dir_reader===false){
			$this->dir_reader = @opendir($this->getCompletePath());
		}
		if($this->dir_reader){
			while (false !== ($filename = readdir($this->dir_reader))){
				if($filename != "." && $filename != ".."){
					$path = $this->getPath().$filename;
					$file = new File($path);
					if($file->isDirectory()){
						return new Folder($path);
					}else{
						return $file;
					}
				}
			}
		}
		return false;
	}

	function copy($destination,$include_invisible=false,$do_not_copy=array()){
		if(!is_object($destination)){
			if(is_string($destination)){
				$className = get_class($this);
				if(substr($destination,0,1)!='/'){ // short name for copy, only the name of the directory and not the whole path
					$parent = $this->getParent();
					if(!$parent)
						return false;
					$destination = $parent->getPath().$destination;
				}
				$destination = new $className($destination);
			}else
				return false;
		}
		if($this->exists() && is_object($destination)){
			if($destination->exists()){
				$dirCopy = $destination->createDirectory($this->getName());
			}else{
				$dirCopy = $destination;
				$destination->create();
			}
			if($dirCopy){
				$this->reset();
				while($child = $this->getNextChildren()){
					$childname = $child->getName();
					// prevent copying svn/system stuff
					if ( ($include_invisible === true || $childname[0] != '.') && !in_array($childname,$do_not_copy))
						$child->copy($dirCopy,$include_invisible,$do_not_copy);
				}
			}
		}
		if($destination->exists())
			return $destination;
		else{
			$this->log('Problem with the copy !!!');
			return false;
		}
	}

	function countItems(){
		if($this->exists())
		{
			$filecount = count(glob($this->getCompletePath().'*'));
		}
		else
		{
			$filecount = 0;
		}
		return $filecount;
	}
	
	function countFiles(){
		return $this->countItems();
	}
	
}

class Sushee_File extends File{}

class TempFile extends File{
	
	var $uniquename;
	var $extension;
	var $tmp_dir = '/tmp/';
	
	function TempFile(){
		$this->uniquename = date('YmdHis') . str_replace(array('.',' '),'-',microtime());
		$this->buildPath();
	}
	
	function buildPath(){
		if($this->extension)
			$this->path = $this->tmp_dir.$this->uniquename.'.'.$this->extension;
		else
			$this->path = $this->tmp_dir.$this->uniquename;
	}
	
	function setExtension($extension){
		$this->extension = $extension;
		$this->buildPath();
	}
	
	function compress(){
		//$this->logFunction('compress');
		$source = $this->getCompletePath();
		$parent = $this->getParent();
		if(!$parent)
			return false;
		$target = $parent->getCompletePath().$this->getName().'.zip';
		$zipped = zip($source,$target);
		if(!$zipped){
			return false;
		}else{
			// returns a file, because TempFile doesnt receive a path in its constructor, it autogenerates its own path
			return new File($target);
		}
	}
}

class TempDirectory extends TempFile{
	
	var $dir_reader = false;
	
	function create(){
		makedir($this->getCompletePath());
	}
	
	function buildPath(){
		$this->path = $this->tmp_dir.$this->uniquename.'/';
	}
	
	function delete(){
		if($this->exists())
			killDirectory($this->getCompletePath());
	}
	
	function compress(){
		$archive = parent::compress();
		if($archive){
			// removing the slash
			return new File(substr($this->getPath(),0,-1).'.zip');
		}
		return $archive;
	}
	
	function getNextChildren(){
		
		if($this->dir_reader===false){
			$this->dir_reader = @opendir($this->getCompletePath());
		}
		if($this->dir_reader){
			while (false !== ($filename = readdir($this->dir_reader))){
				if($filename != "." && $filename != ".."){
					$path = $this->getPath().$filename;
					$file = new File($path);
					if($file->isDirectory()){
						return new Folder($path);
					}else{
						return $file;
					}
				}
			}
		}
		return false;
	}
}

class TempFolder extends TempDirectory{}

class KernelFile extends File{
	
	function KernelFile($kernel_path){
		// allow long notation
		if(substr($kernel_path,0,strlen($GLOBALS["nectil_dir"]))==$GLOBALS["nectil_dir"])
			$kernel_path = substr($kernel_path,strlen($GLOBALS["nectil_dir"]));
		if($kernel_path[0]!='/')
			$kernel_path = '/'.$kernel_path;
		if(is_dir($GLOBALS["nectil_dir"].$kernel_path) && substr($kernel_path,-1)!='/')
			$kernel_path.='/';
		$this->path = $kernel_path;
	}
	
	function getCompletePath(){
		return $GLOBALS["nectil_dir"].$this->path;
	}
	
	function getChild($name){
		//$this->logFunction('getChild, '.$this->getCompletePath());
		if($this->isDirectory()){
			$child = new KernelFile($this->getPath().$name);
			if($child && $child->exists() && $child->isDirectory()){
				$child = new KernelFolder($this->getPath().$name.'/');
			}
			return $child;
		}else{
			//$this->logFunction('KernelFile.getChild, not a directory');
			return false;
		}
	}
	
	function isDirectory(){
		return is_dir($this->getCompletePath());
	}
	
	function getParent(){
		$parent_path = dirname($this->getCompletePath());
		$parent = new KernelFolder($parent_path);
		if($parent->exists()){
			return $parent;
		}else{
			return false;
		}
	}
}


class KernelFolder extends Folder
{
	function KernelFolder($kernel_path)
	{
		// allow long notation
		if(substr($kernel_path,0,strlen($GLOBALS["nectil_dir"]))==$GLOBALS["nectil_dir"])
			$kernel_path = substr($kernel_path,strlen($GLOBALS["nectil_dir"]));
		if($kernel_path[0]!='/')
			$kernel_path = '/'.$kernel_path;
		if(substr($kernel_path,-1)!='/')
			$kernel_path.='/';
		$this->path = $kernel_path;
	}

	function getParent()
	{
		$parent_path = dirname($this->getCompletePath());
		$parent = new KernelFolder($parent_path);
		if($parent->exists())
		{
			return $parent;
		}
		else
		{
			return false;
		}
	}

	function createFile($name)
	{
		if($this->isDirectory())
		{
			$file = new KernelFile($this->getPath().$name);
			if(!$file->exists())
			{
				$file->create();
			}
			return $file;
		}
		else
		{
			return false;
		}
	}

	function isDirectory()
	{
		return is_dir($this->getCompletePath());
	}

	function getCompletePath()
	{
		return $GLOBALS["nectil_dir"].$this->path;
	}

	function getNextChildren()
	{
		//$this->logFunction('getNextChildren');
		if($this->dir_reader===false)
		{
			$this->dir_reader = @opendir($this->getCompletePath());
		}
		if($this->dir_reader)
		{
			while (false !== ($filename = readdir($this->dir_reader)))
			{
				if($filename != "." && $filename != "..")
				{
					$path = $this->getPath().$filename;
					$file = new KernelFile($path);
					if($file->isDirectory())
					{
						return new KernelFolder($path);
					}
					else
					{
						return $file;
					}
				}
			}
		}
	}

	function createDirectory($name)
	{
		if($this->isDirectory())
		{
			$dir = new KernelFolder($this->getPath().$name);
			if(!$dir->exists())
			{
				$dir->create();
			}
			return $dir;
		}
		else
		{
			return false;
		}
	}

	function create()
	{
		if(!$this->exists())
		{
			makedir($this->getCompletePath());
		}
	}
	
	function getChild($name)
	{
		if($this->isDirectory())
		{
			$child = new KernelFile($this->getPath().$name);
			if($child && $child->exists() && $child->isDirectory())
			{
				$child = new KernelFolder($this->getPath().$name.'/');
			}
			return $child;
		}
		else
		{
			return false;
		}
	}

	function copyContent($destination)
	{
		if(!$destination)
			return false;
		if(!is_object($destination)){
			if(is_string($destination)){
				$destination = new File($destination);
			}else
				return false;
		}
		if($this->exists())
		{
			if(!$destination->isDirectory() || !$this->isDirectory())
			{
				return false;
			}
			else
			{
				while($file = $this->getNextChildren())
				{
					if($file->getName() != '.svn')
					{
						// excluding svn files, because they would provoke strange behaviours
						$file->copy($destination);
					}
				}
			}
		}
	}
}

class ZipFile extends File{
	
	var $files = array();
	var $isCompressed = false;
	
	function ZipFile($nectil_path=false){
		if(!$nectil_path)
			$nectil_path = '/tmp/'.date('YmdHis').'.zip';
		File::File($nectil_path);
	}
	
	function add($nectil_file){
		$this->files[] = $nectil_file;
	}
	
	function compress(){
		if(!$this->isCompressed){
			$tmpDir = new TempDirectory();
			$tmpDir->create();
			foreach($this->files as $file){
				$filecopy = false;
				if($file->finalName!='')
					$filecopypath = $tmpDir->getPath().$file->finalName;
				else
					$filecopypath = $tmpDir->getPath().$file->getName();
				$this->log('copying object '.$file->className());
				if($file->className()=='file'){
					$filecopy = new File($filecopypath);
				}else if($file->className()=='folder'){
					$filecopy = new Folder($filecopypath);
				}else if($file->className()=='kernelfolder'){
					$filecopy = new KernelFolder($filecopypath);
				}else if($file->className()=='kernelfile'){
					$filecopy = new KernelFile($filecopypath);
				}
				if($filecopy)
					$file->copy($filecopy);
			}

			$parent = $this->getParent();
			if(!$parent){
				$tmpDir->delete();
				return false;
			}
			$source = $tmpDir->getCompletePath();
			$target = $parent->getCompletePath().$this->getName();
			$zipped = zip($source,$target);
			if($zipped){
				$tmpDir->delete();
				$this->isCompressed = true;
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
		
	}
	
}

class Sushee_FileEffects extends SusheeObject{
	
	var $effects;
	var $source;
	var $target;
	var $status;
	var $message;
	
	function FileEffects(){
		$this->effects = array();
		$this->status = false;
	}
	
	function add($effect){
		if(is_object($effect)){
			$this->effects[]=$effect;
		}else
			return false;
	}
	
	function getStatus(){
		return $this->status;
	}
	
	function getMessage(){
		return $this->message;
	}
	
	function execute(){
		$current_file = $this->source;
		$ok = true;
		foreach($this->effects as $effect){
			$tmp_file = new TempFile();
			$tmp_file->setExtension($current_file->getExtension());
			$effect->setSource($current_file);
			$effect->setTarget($tmp_file);
			$effect->execute();
			if($effect->getStatus()===false){
				$ok = false;
				break;
			}
			$current_file = $tmp_file;
		}
		if(!$ok){
			$this->status = false;
			$this->message = $effect->getMessage();
		}else{
			$current_file->copy($this->target);
			$this->status = true;
		}
			
		
	}
	
	function setSource(&$file){
		$this->source = &$file;
	}
	
	function setTarget(&$file){
		$this->target = &$file;
	}
}

class FilesUploader extends SusheeObject{
      var $target = null;
      var $post_files = array();
      var $uploaded_files = array();
      var $error_files = array();

      function setTarget($target) // folder object
      {
          $this->target = $target;
      }

      function addFile($post_file, $file_key='')
      {
          if (empty($file_key))
          {
              $this->post_files[]= $post_file;
          }
          else
          {
              $this->post_files[$file_key]= $post_file;
          }
      }

      function getFiles()
      {
          return $this->uploaded_files;
      }

		function getFile($key){
			return $this->uploaded_files[$key];
		}

      function addErrorFile($file_name, $error_msg, $file_key='')
      {
          $this->log($error_msg);
          $infos = array('name'=>$file_name, 'error'=>$error_msg);

          if (empty($file_key))
          {
              $this->error_files[] = $infos;
          }
          else
          {
              $this->error_files[$file_key] = $infos;
          }
      }

      function getErrorFiles()
      {
          return $this->error_files;
      }

      function execute()
      {
          $this->log('Upload started');
          if ($this->target == null)
          {
              $this->log('No target folder to upload');
              return false;
          }
          if (empty($this->post_files))
          {
              $this->log('No files to upload');
              return true;
          }

          $targetPath = $this->target->getPath();

          $upload_success = true;
          foreach ($this->post_files as $file_key => $one_file)
          {
              //get file size
              $size = $one_file['size'];
              $name = $one_file['name'];

              if ($size)
              {
                  $name = setFilename($name);

                  if (!uploadOK($name)) //check if this file is accepted
                  {
                      $this->addErrorFile($name, "scripts files not accepted: '$name'", $file_key);
                      $upload_success = false;
                      continue;
                  }
                  if (!hidecheck($name))
                  {
                      $this->addErrorFile($name, "this filename is not accepted: '$name'", $file_key);
                      $upload_success = false;
                      continue;
                  }

                  $this->log($targetPath);
                  $nectil_path = $targetPath.$name;
                  $new_file = new File($nectil_path);
                  $location = $new_file->getCompletePath();

                  $this->log('Copying '.$one_file['tmp_name'].' to '.$location);

                  @copy($one_file['tmp_name'], $location);

                  if ($new_file->exists())
                  {
                      chmod_Nectil($location);
                      unlink($one_file['tmp_name']);

                      if (empty($file_key))
                      {
                          $this->uploaded_files[] = $new_file;
                      }
                      else
                      {
                          $this->uploaded_files[$file_key] = $new_file;
                      }
                  }
                  else
                  {
                      $this->addErrorFile($name, "file copy error: '$name'", $file_key);
                      $upload_success = false;
                  }
              }
              else
              {
                  $this->addErrorFile($name, "file size error: '$name'", $file_key);
                  $upload_success = false;
              }
          }
		return $upload_success;
      }
  }

class SQLFile extends KernelFile{
	var $path;
	function SQLFile($path){
		$this->path = $path;
	}

	function execute($database){
		if(!is_object($database)){
			$this->log('Databse invalid');
			return false;
		}
		$sql_pieces = $this->split();
		if(is_array($sql_pieces)){
			foreach($sql_pieces as $sql_instruction){
				$database->execute($sql_instruction);
			}
		}else
			$this->log('SQL file ('.$this->getCompletePath().') is empty');
	}

	function toString(){
		if($this->exists()){
			return file_in_string($this->getCompletePath());
		}else{
			return false;
		}
	}

	function split(){
		$release = 32330;
		$sql = $this->toString();
		if(!$sql){
			return false;
		}
		$sql          = trim($sql);
		$sql_len      = strlen($sql);
		$char         = '';
		$string_start = '';
		$in_string    = FALSE;
		$time0        = time();

		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];

			// We are in a string, check for not escaped end of strings except for
			// backquotes that can't be escaped
			if ($in_string) {
				for (;;) {
					$i         = strpos($sql, $string_start, $i);
					// No end of string found -> add the current substring to the
					// returned array
					if (!$i) {
						$ret[] = $sql;
						return $ret;
					}
					// Backquotes or no backslashes before quotes: it's indeed the
					// end of the string -> exit the loop
					else if ($string_start == '`' || $sql[$i-1] != '\\') {
						$string_start      = '';
						$in_string         = FALSE;
						break;
					}
					// one or more Backslashes before the presumed end of string...
					else {
						// ... first checks for escaped backslashes
						$j                     = 2;
						$escaped_backslash     = FALSE;
						while ($i-$j > 0 && $sql[$i-$j] == '\\') {
							$escaped_backslash = !$escaped_backslash;
							$j++;
						}
						// ... if escaped backslashes: it's really the end of the
						// string -> exit the loop
						if ($escaped_backslash) {
							$string_start  = '';
							$in_string     = FALSE;
							break;
						}
						// ... else loop
						else {
							$i++;
						}
					} // end if...elseif...else
				} // end for
			} // end if (in string)

			// We are not in a string, first check for delimiter...
			else if ($char == ';') {
				// if delimiter found, add the parsed part to the returned array
				$ret[]      = substr($sql, 0, $i);
				$sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len    = strlen($sql);
				if ($sql_len) {
					$i      = -1;
				} else {
					// The submited statement(s) end(s) here
					return $ret;
				}
			} // end else if (is delimiter)

			// ... then check for start of a string,...
			else if (($char == '"') || ($char == '\'') || ($char == '`')) {
				$in_string    = TRUE;
				$string_start = $char;
			} // end else if (is start of string)

			// ... for start of a comment (and remove this comment if found)...
			else if ($char == '#'
					 || ($char == ' ' && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--')) {
				// starting position of the comment depends on the comment type
				$start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
				// if no "\n" exits in the remaining string, checks for "\r"
				// (Mac eol style)
				$end_of_comment   = (strpos(' ' . $sql, "\012", $i+2))
								  ? strpos(' ' . $sql, "\012", $i+2)
								  : strpos(' ' . $sql, "\015", $i+2);
				if (!$end_of_comment) {
					// no eol found after '#', add the parsed part to the returned
					// array if required and exit
					if ($start_of_comment > 0) {
						$ret[]    = trim(substr($sql, 0, $start_of_comment));
					}
					return TRUE;
				} else {
					$sql          = substr($sql, 0, $start_of_comment)
								  . ltrim(substr($sql, $end_of_comment));
					$sql_len      = strlen($sql);
					$i--;
				} // end if...else
			} // end else if (is comment)

			// ... and finally disactivate the "/*!...*/" syntax if MySQL < 3.22.07
			else if ($release < 32270
					 && ($char == '!' && $i > 1  && $sql[$i-2] . $sql[$i-1] == '/*')) {
				$sql[$i] = ' ';
			} // end else if

			// loic1: send a fake header each 30 sec. to bypass browser timeout
			$time1     = time();
			if ($time1 >= $time0 + 30) {
				$time0 = $time1;
				header('X-pmaPing: Pong');
			} // end if
		} // end for

		// add any rest to the returned array
		if (!empty($sql) && ereg('[^[:space:]]+', $sql)) {
			$ret[] = $sql;
		}
		return $ret;
	}
}