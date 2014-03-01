<?php
if($_GET['language']=='fre')
	$link = 'http://www.macromedia.com/go/d65_flplayer_fr_fr/';
else if($_GET['language']=='ger')
	$link = 'http://www.macromedia.com/go/d65_flplayer_de/';
else if($_GET['language']=='dut')
	$link = 'http://www.macromedia.com/go/d65_flplayer_nl/';
else if($_GET['language']=='spa')
	$link = 'http://www.macromedia.com/go/d65_flplayer_es_es/';
else
	$link = 'http://www.macromedia.com/go/getflashplayer/';
$title = 'Nectil OS';
$subtitle = 'Pour un fonctionnement optimal de Nectil, veuillez mettre à jour votre plugin flash';
?>
<html>
	<head>
		<title>getFlashPlayer</title>
		<style>
		body{background-color:#426c85;padding:0px;margin:0px;}
		#central_cell{
			position:absolute;background-color:white;width:480px;height:172px;
			background-image:url(bkg_update.gif);
			margin:-86px 0px 0px -240px;
			top: 50%; 
			left: 50%;
			text-align: left;
		}
		#title{margin-top:21px;margin-bottom:21px;margin-left:17px;}
		a#getflash{margin-left:17px;}
		</style>
	</head>
	<body>
		<div id="central_cell">
			<div id="title">
				<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="100%" height="78" id="Titration" align="middle">
					<param name="allowScriptAccess" value="sameDomain">
					<param name="movie" value="Titration.swf?title=<?php echo $title?>&amp;titleSize=30&amp;baselineSize=21&amp;baseline=<?php echo $subtitle?>&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4">
					<param name="quality" value="high">
					<param name="bgcolor" value="#ffffff">
	
					<param name="loop" value="false">
					<param name="menu" value="false">
					<embed loop="false" menu="false" src="Titration.swf?title=<?php echo $title?>&amp;titleSize=30&amp;baselineSize=21&amp;baseline=<?php echo $subtitle?>&amp;vAlign=center&amp;hAlign=top&amp;bkg_color=0xffffff&amp;textSeparator=4" quality="high" bgcolor="#ffffff" width="100%" height="78" name="Titration" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
				</object>
			  </div>
			<a id="getflash" href="<?php echo $link?>"><img border="0" alt="" src="get_flash_player.gif"/></a>
		</div>
	</body>
</html>
