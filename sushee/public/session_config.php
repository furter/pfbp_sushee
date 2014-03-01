<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/public/session_config.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once(dirname(__FILE__)."/../common/common_functions.inc.php");
$sess = &$_SESSION[$GLOBALS["nectil_url"]];
/*if(!isset($sess['edition']))
	$sess['edition']=false;
if(!isset($sess['cache']))
	$sess['cache']=true;
if(!isset($sess['xml']))
	$sess['xml']=false;
if(!isset($sess['all_languages']))
	$sess['all_languages']=false;*/
if (sizeof($_POST)>0){
	if (isset($_POST['edition'])){
		if($sess['edition']!=$_POST['edition'])
			$message.="Edition mode changed : <strong>".$_POST['edition']."</strong>.";
		$sess['edition']=$_POST['edition'];
	}
	if (isset($_POST['cache'])){
		if($sess['cache']!=$_POST['cache'])
			$message.="Cache mode changed : <strong>".$_POST['cache']."</strong>.";
		$sess['cache']=$_POST['cache'];
	}
	if (isset($_POST['xml'])){
		if($sess['xml']!=$_POST['xml'])
			$message.="XML mode changed : <strong>".$_POST['xml']."</strong>.";
		$sess['xml']=$_POST['xml'];
	}
	if (isset($_POST['all_languages'])){
		if($sess['all_languages']!=$_POST['all_languages'])
			$message.="Languages mode changed : <strong>".$_POST['all_languages']."</strong>.";
		$sess['all_languages']=$_POST['all_languages'];
	}
	$reload = true;
}
?>
<html>
	<head>
		<title>Configuration</title>
		<style>
		body{color:white;background-color:#426c85;padding:0px;margin:0px;font-size:11px;font-family:Lucida Grande, Lucida Sans Unicode, Lucida Sans, Lucida, Helvetica, Arial;}
		select{font-size:11px;font-family:Lucida Grande, Lucida Sans Unicode, Lucida Sans, Lucida, Helvetica, Arial;}
		span.label{padding-right:5px;}
		span.option{padding-right:8px;}
		strong{color:orangered;}
		#optionLine{padding-bottom:2px;border-bottom:1px solid white;}
		.message{margin-top:2px;position:relative;}
		#media_config{position:absolute;top:2px;right:2px;color:white;}
		.reload_button{position:absolute;top:2px;right:2px;}
		.reload_button input{font-size:11px;}
		</style>
	</head>
	<body>
			<form name="form1" action="session_config.php" method="post">
				<div id="optionLine">
					<!--span class="label">Unpublished languages</span><span class="option"><select name="all_languages" onChange="document.form1.submit();"><option value="false">invisible</option><option value="true" <?php echo ($sess['all_languages']==='true')?'selected="selected"':'' ?>>visible</option></select></span-->
					<span class="label">Cache</span><span class="option"><select name="cache" onChange="document.form1.submit();"><option value="true">true</option><option <?php echo ($sess['cache']==='false')?'selected="selected"':'' ?> value="false">false</option><option <?php echo ($sess['cache']==='refresh')?'selected="selected"':'' ?> value="refresh">Refresh</option></select></span>
					<span class="label">XML Debug</span><span class="option"><select name="xml" onChange="document.form1.submit();"><option value="false">false</option><option value="true" <?php echo ($sess['xml']==='true')?'selected="selected"':'' ?>>true</option></select></span>
					<span class="label">Edition buttons</span><span class="option"><select name="edition" onChange="document.form1.submit();"><option value="false">invisible</option><option <?php echo ($sess['edition']==='true')?'selected="selected"':'' ?> value="true">visible</option></select></span>
					<span class="label">GoTo</span><span class="option"><input name="url" type="text" size="20"/><input type="submit" value="Go"/></span>
					<span class="reload_button"><input type="button" value="Reload Page" onClick="parent.frames[1].location.reload();"/></span>
				</div>
				<div style="display:none;"><input type="submit" value="Sauver"/></div>
			
			<div class="message">
				<?php echo $message;?>
				<a id="media_config" href="configuration.php" target="_blank">see media config</a>
			</div>
			</form>
			<?php if($_POST['url']!=''){?>
				<script>
				parent.frames[1].location.href = "<?php echo $_POST['url'];?>";
				</script>
			<?php }else if($reload){?>
				<script>
				parent.frames[1].location.reload();
				</script>
			<?php } ?>
			
	</body>
</html>
