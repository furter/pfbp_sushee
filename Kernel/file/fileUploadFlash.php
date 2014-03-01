<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/fileUploadFlash.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/image.class.php");

class FlashFilesUploadPage extends SusheeObject{
	var $files = array();
	
	function upload(){
		if (isset($_FILES)){
			$targetPath=$_GET["target"];
			if($targetPath){
				$dir = new Folder($targetPath);
				if(!$dir->exists()){
					if($_GET['makedir']==='true'){
						$dir->create();
					}else{
						$this->error('Target directory doesn\'t exist');
					}
				}
			}else{
				$this->error('No target directory indicated');
			}
			$upload = new FilesUploader();
			$upload->setTarget($dir);
			$files = array();
			
			
			foreach($_FILES as $key=>$one_file){
				$upload->addFile($one_file);
			}
			
			$upload->execute();
			$this->files = $upload->getFiles();

		}
	}
	
	function transformImages(){
		if($_GET['scenario']){
			$imagesTrans = new MassImageTransformer();
			$imagesTrans->setImageEffect(new ImageScenario($_GET['scenario']));
			foreach($this->files as $one_file){
				$imagesTrans->addFile($one_file);
			}
			$imagesTrans->execute();
		}
	}
	
	function execute(){
		$this->upload();
		$this->transformImages();
		$this->success();
	}
	
	function success(){
		die('res=1');
	}
	
	function error($msg){
		$this->log($msg);
		die('res=0');
	}
}

$flashUpload = new FlashFilesUploadPage();
$flashUpload->execute();

?>