<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/swf/flvplayer.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
	$file = $_GET['file'];
	
	echo '
	<html style="height:100%;width:100%;">
		<head>
			<meta http-equiv=Content-Type content="text/html;  charset=utf-8" />
			<script type="text/javascript" src="../js/swfobject1.js"></script>
			<title>Video Player</title>
			<style type="text/css">
				*{margin:0;padding:0;}
			</style>
		</head>
		<body style="height:100%;width:100%;margin:0;padding:0;overflow:hidden;">
			<div id="videoplayer" style="width:100%;height:100%">
				<div style="padding:20px;">
					<p>
						<a href="http://www.adobe.com/go/getflashplayer">
							<img alt="" src="get_flash_player.gif" style="border:0;"/>
						</a>
					</p>
				</div>
			</div>
			<script type="text/javascript" id="init" name="init">
				var so = new SWFObject("flvplayer.swf", "player", "100%", "100%", "8", "#0");
				so.addParam("FlashVars", "file='.$file.'");
				so.addParam("menu", "false");
				so.addParam("quality", "best");
				so.addParam("scale", "noscale");
				so.addParam("swliveconnect", "true");
				so.addParam("allowScriptAccess", "always");
				var swf = so.write("videoplayer");
			</script>
		</body>
	</html>
	';
	
?>


