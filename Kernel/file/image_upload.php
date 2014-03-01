<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/image_upload.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_config.inc.php");
//require_once(dirname(__FILE__)."/../file/zip/pclzip.lib.php");
require_once(dirname(__FILE__)."/../common/image_functions.inc.php");

function WriteMainTitle($target){
	?>
	<div class="main_title">
		<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="100%" height="60" id="Titration" align="middle">
			<param name="allowScriptAccess" value="sameDomain">
			<param name="movie" value="../../OS/Titration.swf?title=Image Upload&amp;titleSize=30&amp;baselineSize=21&amp;baseline=In: <?php echo $target;?>&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4">
			<param name="quality" value="high">
			<param name="bgcolor" value="#ffffff">
			<param name="loop" value="false">
			<param name="menu" value="false">
			<embed loop="false" menu="false" src="../../OS/Titration.swf?title=Image Upload&amp;titleSize=30&amp;baselineSize=21&amp;baseline=In: <?php echo $target;?>&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4" quality="high" bgcolor="#ffffff" width="100%" height="60" name="Titration" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
		</object>
	</div>
	<?php
}

// to ensure compatibility with old php
if (isset($HTTP_POST_VARS['targetPath']) && !isset($_POST['targetPath'])){
	$_FILES = $HTTP_POST_FILES;
	$_POST = $HTTP_POST_VARS;
}

$targetPath=$_POST["targetPath"];
$overwrite=$_POST["overwrite"];
$unzip=$_POST["unzip"];

if (!isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'])){
    htmlErrorMsg("Upload error","Invalid session ID");
}

$thisFile="image_upload.php?".session_name()."=".session_id()."&rand=".rand(0,30000);
if (isset($_FILES['fichier'])){
    //check path validity
    if(!isset($targetPath) || $targetPath == ""){
        htmlErrorMsg("Upload error","Invalid target path");
    }
	
	if (!is_uploaded_file($_FILES['fichier']['tmp_name'][0]) || (isset($_FILES['fichier']['name'][1]) && $_FILES['fichier']['name'][1]!="" && !is_uploaded_file($_FILES['fichier']['tmp_name'][1]))  || (isset($_FILES['fichier']['name'][2]) && $_FILES['fichier']['name'][2]!="" && !is_uploaded_file($_FILES['fichier']['tmp_name'][2]))  || (isset($_FILES['fichier']['name'][3]) && $_FILES['fichier']['name'][3]!="" && !is_uploaded_file($_FILES['fichier']['tmp_name'][3])) )
		htmlErrorMsg("Upload error","One of the files you are trying to upload is too large : try to compress it and upload it again.");
		
    $targetPath = transformPath($targetPath);
    //check security for this target 
    $right =  getPathSecurityRight($targetPath);
    
    if($right !=="W" ){
        htmlErrorMsg("Upload error","Upload refused, you cannot write to this directory :".$targetPath);
    }
    
    $files=$_FILES['fichier'];
	$uploaded_files = array();
	$decompressed_files = array();
	$error_files = array();
	
    foreach ($files['name'] as $key=>$name) {
        //get file size
        $size=$files['size'][$key];
        
        if ($size) {
            // clean up file name
            $name = setFilename($name);
			
			$file_ok = true;
            //check if this file is accepted
            if(!uploadOK($name)){
				$error_files[]=array('name'=>$name,'error'=>"Scripts files not accepted");
				$file_ok = false;
            }
            if(!hidecheck($name)){
				$error_files[]=array('name'=>$name,'error'=>"This filename :".$name." is not accepted");
				$file_ok = false;
            }
			if($file_ok){
				$ext = getFileExt($name);
				if($ext=="zip" /*&& isset($unzip) && $unzip=="on" */){
					$location = $directoryRoot.$targetPath."/".$name;
					copy($files['tmp_name'][$key],$location);
					$archivedir=realpath($directoryRoot.$targetPath);
					$tmp_dir = realpath($directoryRoot."/tmp").'/'.date('YmdHis');
					makedir($tmp_dir);
					unzip($location,$tmp_dir);
					cleanFilenames_in($tmp_dir);
					$unzipped_images = copy_content_files_array($tmp_dir,$archivedir);
					if($unzipped_images != false)
						$imageOperation = array_merge($imageOperation,$unzipped_images);
					killDirectory($tmp_dir);
					$uploaded_files[]=$name;
					unlink($location);
				}else{
					$location = $directoryRoot.$targetPath."/".$name;
					copy($files['tmp_name'][$key],$location);
					//chmod ($location, 0777);
					chmod_Nectil($location);
					unlink($files['tmp_name'][$key]);
					
					$uploaded_files[]=$name;
					$imageOperation[]=$location;
				}
			}
			
        }
    }
	?>
			<html>
				<head>
					<title>Upload images</title>
					<link rel="stylesheet" type="text/css" href="file_upload.css"/>
				</head>
				<body>
					<div id="central_cell"><div id="content">
	<?php
	WriteMainTitle($targetPath);
	echo "<div class='file_uploads'>";
	
	if(sizeof($imageOperation)>0 && $_POST['operation']!='none'){
		foreach($imageOperation as $location){
			$result = imageTransformation($location,$_POST['operation']);
			if($result==false)
				$error_files[]=array('name'=>$location,'error'=>"This image :".$name." couldn't be transformed");
		}
	}
	if(sizeof($uploaded_files)>0){
		echo "<div class='h2'>Successfully uploaded file(s):</div><div class='fileset'>";
		foreach($uploaded_files as $upload){
			echo '<div class="filename">'.$upload.'</div>';
		}
		echo "</div>";
	}
	if(sizeof($error_files)>0){
		echo "<div class='h2_error'>Upload error(s):</div><div class='fileset'>";
		foreach($error_files as $upload){
			echo '<div class="error">'.$upload['name'].': '.$upload['error'].'</div>';
		}
		echo "</div>";
	}else{
		?>
		<script>setTimeout("window.close()",3000);</script>
		<?php
	}
			?>
			</div>
			<div id="close_button">You can close this window.</div>
			</div>
			</div>
    </body>
</html>
			<?php

}else{
	 $targetPath = transformPath($_GET["target"]);
    //check security for this target 
    $right =  getPathSecurityRight($targetPath);
    $ghost = '<div><OBJECT height="5" width="5" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" id="up" ALIGN="">
        	<PARAM NAME=movie VALUE="upload_slave.swf">
        	<PARAM NAME="quality" VALUE="low">
			<PARAM NAME="wmode" VALUE="transparent">
        	<EMBED width="5" height="5" wmode="transparent" src="upload_slave.swf" quality="low" NAME="up" ALIGN="" TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer"></EMBED>
        </OBJECT></div>';
		
    if($right !=="W" ){
        htmlErrorMsg("Upload error","Upload refused, you cannot write to this directory $targetPath".$ghost);
    }
?>
<html>
	<head>
		<title>Upload images</title>
		<link rel="stylesheet" type="text/css" href="file_upload.css"/>
	</head>
	<body>
		<div id="central_cell">
		<div id="content"><form onSubmit="document.getElementById('submit_button').disabled='disabled';document.getElementById('formulaire').style.display='none';document.getElementById('wait').style.display='block';return true;" name="myform" method="post" enctype="multipart/form-data" action="<?php echo $thisFile;?>">
			<input name="targetPath" type="hidden" value="<?php echo $_GET['target'];?>">
			<?php
			WriteMainTitle($targetPath);
			?>
			<div class="title">
				<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="100%" height="21" id="Titration" align="middle">
					<param name="allowScriptAccess" value="sameDomain">
					<param name="movie" value="../../OS/Titration.swf?title=1. Choose images&amp;titleSize=21&amp;baselineSize=21&amp;baseline=&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4">
					<param name="quality" value="high">
					<param name="bgcolor" value="#ffffff">
					<param name="loop" value="false">
					<param name="menu" value="false">
					<embed loop="false" menu="false" src="../../OS/Titration.swf?title=1. Choose images&amp;titleSize=21&amp;baselineSize=21&amp;baseline=&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4" quality="high" bgcolor="#ffffff" width="100%" height="21" name="Titration" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
				</object>
			</div>
            <div class="file_input"><input name="fichier[]" type="file"></div>
            <div class="file_input"><input name="fichier[]" type="file"></div>
            <div class="file_input"><input name="fichier[]" type="file"></div>
            <div class="file_input"><input name="fichier[]" type="file"></div>
			<div class="zip_image_remark">*You can also choose zip files : the zip will be automatically decompressed and if a transformation was chosen, it will be applied on all files inside the archive.</div>
			<div id="formulaire">
            <div class="title2">
				<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="100%" height="21" id="Titration" align="middle">
					<param name="allowScriptAccess" value="sameDomain">
					<param name="movie" value="../../OS/Titration.swf?title=2. Set options&amp;titleSize=21&amp;baselineSize=21&amp;baseline=&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4">
					<param name="quality" value="high">
					<param name="bgcolor" value="#ffffff">
					<param name="loop" value="false">
					<param name="menu" value="false">
					<embed loop="false" menu="false" src="../../OS/Titration.swf?title=2. Set options&amp;titleSize=21&amp;baselineSize=21&amp;baseline=&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4" quality="high" bgcolor="#ffffff" width="100%" height="21" name="Titration" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
				</object>
			</div>
			<fieldset>
				<legend>Transformations/variations of the images</legend>
					<?php
					$file_operations = $GLOBALS["library_dir"].'file/imageTransformations.xml';
					echo "<select name='operation'>";
					if ( file_exists($file_operations) ){
						echo "<option value='none'>None</option>";
						$xml = new XML($file_operations);
						if($xml->loaded){
							$operations = $xml->match('/imageTransformations/transformation');
							foreach($operations as $path){
								$name = $xml->getData($path.'/@name');
								if (isset($_GET['languageID'])){
									$traducted_name = $xml->getData($path."/label[@languageID='".$_GET['languageID']."'][1]");
									if(!$traducted_name)
										$traducted_name=$name;
								}else{
									$traducted_name = $xml->getData($path."/label[1]");
									if(!$traducted_name)
										$traducted_name=$name;
								}
								echo "<option value='$name'>$traducted_name</option>";
							}
						}
					}else{
						$traducted_name = 'No transformation available (not configured yet)';
						if($_GET['languageID']==='fre')
							$traducted_name = 'Pas de transformation disponible (cela doit etre configur&eacute;)';
						echo "<option value='none'>$traducted_name</option>";
					}	
					echo "</select>";
					?>
			</fieldset>
			<!--fieldset title="Use .zip archive to upload complete arborescence or multiple files in one.&#13;Auto unzip option will decompress the archive in the target folder.">
				<legend>Compression</legend>
           		<input name="unzip" type="checkbox" >Auto unzip file</input>
			</fieldset-->
			<div class="title3">
				<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="100%" height="21" id="Titration" align="middle">
					<param name="allowScriptAccess" value="sameDomain">
					<param name="movie" value="../../OS/Titration.swf?title=3. Upload Images&amp;titleSize=21&amp;baselineSize=21&amp;baseline=&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4">
					<param name="quality" value="high">
					<param name="bgcolor" value="#ffffff">
					<param name="loop" value="false">
					<param name="menu" value="false">
					<embed loop="false" menu="false" src="../../OS/Titration.swf?title=3. Upload Images&amp;titleSize=21&amp;baselineSize=21&amp;baseline=&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4" quality="high" bgcolor="#ffffff" width="100%" height="21" name="Titration" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
				</object>
			</div>
			</div>
			<div id="wait" style="display:none;">
				<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="200" height="200" id="flash_wait" align="middle">
					<param name="allowScriptAccess" value="sameDomain">
					<param name="movie" value="wait.swf">
					<param name="quality" value="high">
					<param name="bgcolor" value="#ffffff">
					<param name="loop" value="false">
					<param name="menu" value="false">
					<embed loop="false" menu="false" src="wait.swf" quality="high" bgcolor="#ffffff" width="200" height="200" name="wait" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
				</object>
			</div>
			<div class="submit"><input id="submit_button" type="submit" value="Upload"></div>
			
			
            <?php echo $ghost;?>
	
		</form></div>
		</div>
    </body>
</html>	 
<?php
}

?>
