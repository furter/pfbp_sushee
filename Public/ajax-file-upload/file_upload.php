<?php
//TODO avec Jonathan
error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
include_once('../../Kernel/common/file.class.php');
function clean_filename($name)
{
$clean_name = basename($name);
$clean_name = removeaccents($clean_name);
$clean_name = strtolower($clean_name);
$clean_name = preg_replace('/[^a-z0-9\._]/', '_', $clean_name);
return $clean_name;
}

try 
{
	if (!isset($_FILES['uploadfile'])){
		throw new Exception('There is no file found to upload');
	}

	$folderpath = '/Files/media/test';
	# .trim(strtolower(URLParam::fetch('folder')),'/').'/'.date('YmdHis').'-'.$_SESSION['user'];

	$folder = new Folder($folderpath);
	//TODO check access to folder
	$folder->create();
	if (!$folder->exists())
	{
		throw new Exception('Upload failed: cannot create folder '.$folderpath.'!');
	}

	$uploader = new FilesUploader();
	$uploader->setTarget($folder);
	$uploader->addFile($_FILES['uploadfile']);


	if ($uploader->execute() == false)
	{
	    $file_infos = '"'.$folderpath.'/'.$_FILES['uploadfile']['name'];
	    $file_infos .= '" ('.$_FILES[$input_name]['type'];
	    $file_infos .= ' - '.$_FILES[$input_name]['size'].' bytes)';
	    throw new Exception('Upload failed: '.$file_infos);
	}

	$files = $uploader->getFiles();           
	if (!is_object($files[0]))
	{
	    throw new Exception('Unknown error');
	}
	$files[0]->rename(clean_filename($files[0]->getName()));
	die(json_encode(array(
		'status'  => 'success',
		'message' => '',
		'path'    => $files[0]->getPath(),
		'name'    => $files[0]->getName()
	)));
}
catch (Exception $e) 
{
//	$outputFormat = URLParam::fetch('output', 'json');
	$outputFormat = 'aaa';
//	$e->display($outputFormat);
}
catch (Exception $e) {
	echo $e->getMessage();
}


?>