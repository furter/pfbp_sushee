<?php
include_once("../sushee/common/common_functions.inc.php");

if( strpos($_SERVER['HTTP_USER_AGENT'],'iPhone') !== false ||  strpos($_SERVER['HTTP_USER_AGENT'],'iPod') !== false)
{
	header('HTTP/1.1 301 Moved Permanently');
	header('location: iphone/SpringBoard.php');
	header('Connection: close');
	exit();
}

global $slash;

$kernel_xml = '';
$kernel_custom = file_in_string($GLOBALS["library_dir"].'OS'.$slash.'kernel_config.xml');
if($kernel_custom===false)
	$kernel_custom = file_in_string($GLOBALS["nectil_dir"].$slash.'..'.$slash.'..'.$slash.'Library'.$slash.'OS'.$slash.'kernel_config.xml');
	
if($kernel_custom!==false)
{
	$kernel_custom = str_replace("\t",'',$kernel_custom);
	$kernel_custom = str_replace("\r",'',$kernel_custom);
	$kernel_custom = str_replace("\n",'',$kernel_custom);
	$kernel_custom = urlencode($kernel_custom);
	$kernel_xml = 'kernel_custom='.$kernel_custom;
}

$resident_xml = '';
$resident_custom_path = $GLOBALS["library_dir"].'OS'.$slash.'resident_config.xml';
$resident_custom = file_in_string($resident_custom_path);
if($resident_custom!==false)
{
	$resident_custom = str_replace("\t",'',$resident_custom);
	$resident_custom = str_replace("\r",'',$resident_custom);
	$resident_custom = str_replace("\n",'',$resident_custom);
	$resident_custom = urlencode($resident_custom);
	if($kernel_custom!==false)
		$resident_xml = '&resident_custom='.$resident_custom;
	else
		$resident_xml = 'resident_custom='.$resident_custom;
}

$navigation='&language='.$GLOBALS['NectilLanguage'];

if($_GET['goto'])
	$navigation.='&goto='.$_GET['goto'];
	
if($_GET['launch'])
	$navigation.='&launch='.$_GET['launch'];
	
if($_GET['babeler'])
	$navigation.='&babeler='.$_GET['babeler'];

if(islogged())
	$navigation.='&session='.session_id();

?>

<html>
	<head>
		<meta http-equiv=Content-Type content="text/html;  charset=ISO-8859-1" />
		<link rel="icon" type="image/x-icon" href="http://www.nectil.com/paperclip.ico" />
		<script type="text/javascript" src="swfobject.js"></script>
		<script type="text/javascript" src="utilities.js"></script>
		<script type="text/javascript" src="iphone/js/Tween.js"></script>
		<script type="text/javascript" src="shell.js"></script>
		<script type="text/javascript">
			resizeSafari();

			function setFlashFocus()
			{
				var movie = getFlashMovieObject('core');
				movie.focus();
			}

			function getFlashMovieObject(movieName)
			{
				if (window.document[movieName])
				{
					return window.document[movieName];
				}
				
				if (navigator.appName.indexOf("Microsoft Internet") == -1)
				{
					if (document.embeds && document.embeds[movieName])
						return document.embeds[movieName];
				}
				else
				{
					return document.getElementById(movieName);
				}
			}

			function setLogged(str)
			{
				isLogged = str;
				if (str == 'true')
				{
					window.onbeforeunload = confirmQuit;
					return "on Close security active";
				}
				else
				{
					window.onbeforeunload = null;
					return "on Close security removed";
				}
			}
			
			function confirmQuit(evt)
			{
				evt.returnValue = "You are about to leave Officity, do you want to continue?";
			}
			
			function initcleanup()
			{
				var $script = $id('init');
				$script.parentNode.removeChild($script);
			}

			setLogged('false');

		</script>
		<title><?php echo $GLOBALS['resident_name'].(($GLOBALS['resident_name'])?' - ':'');?>Officity</title>
		<style type="text/css">
			*{margin:0;padding:0;}
			html{height:100%;width:100%;}
			body{height:100%;width:100%;margin:0px;padding:0px;overflow:hidden;}
			iframe{background-color:#ffffff;}
			#officity{width:100%;height:100%}
		</style>
	</head>
	<body onload="initcleanup();">
		<div id="officity">
			<div style="padding:20px;">
				<p>
					Pour un fonctionnement optimal d'Officity, veuillez mettre a jour votre plugin flash <br/><br/>
					<a href="http://www.adobe.com/go/getflashplayer">
						<img alt="" src="get_flash_player.gif" style="border:0;"/>
					</a>
				</p>
			</div>
		</div>
		<script type="text/javascript" id="init" name="init">
			var so = new SWFObject("core.swf?version=20071213215208", "core", "100%", "100%", "8", "#f");
			so.addParam("FlashVars", "<?php echo $kernel_xml.$resident_xml.$navigation;?>");
			so.addParam("menu", "false");
			so.addParam("quality", "best");
			so.addParam("scale", "noscale");
			so.addParam("swliveconnect", "true");
			so.addParam("allowScriptAccess", "always");

			// -- firefox windows can't display multi-level flash without transparency --
			
// 			if (/Firefox[\/\s](\d+)\.(\d+)\.(\d+)/.test(navigator.userAgent))
// 			{	
// 				var major=new Number(RegExp.$1);
// 				var minor=new Number(RegExp.$2);
// 				var delta=new Number(RegExp.$3);
// 
// 				if (major > 2 && (minor > 0 || (minor == 0 && delta > 9)))
// 				{
// 					so.addParam("wmode", "transparent");
// 				}
// 			}

			var swf = so.write("officity");

			if (swf)
			{
				setFlashFocus();
			}

		</script>
		<div id="jsshell" name="jsshell">
			<iframe id="nectil_iframe" name="nectil_iframe" style="display:none;height:0px;width:0px;position:absolute;z-index:-1;">&#160;</iframe>
		</div>
	</body>
</html>
