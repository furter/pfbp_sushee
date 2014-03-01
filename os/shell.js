$appwindowindex = 999;
$appwindowregister = {};
$officitylogs = '';

function openappwindow($object)
{	
	$appwindowindex++;

	var $app = $id($object.target);
	if ($app != undefined)
	{
		$object.value = true;
		appwindowvisibility($object);
	}
	else
	{
		$app = document.createElement('iframe');
		$app.setAttribute('id',$object.target);
		$app.setAttribute('name',$object.target);
		$app.setAttribute('frameborder',0);
		$app.setAttribute('marginheight',0);
		$app.setAttribute('marginwidth',0);
		$app.style.position = 'absolute';
		$app.style.zIndex = $appwindowindex;
	 	$app.src = $object.url;

		$id('jsshell').appendChild($app);
	}

	return true;
}

function closeappwindow($object)
{
	var $app = $id($object.target);
	if ($app != undefined)
	{
		$app.parentNode.removeChild($app);
	}
}

function appwindowvisibility($object)
{
	var app = $id($object.target);
	var style = $style($object.target);

	if (!$object.value)
	{
		//disappear($object.target);
		//hide($object.target);

		if ($appwindowregister[$object.target] == undefined)
			$appwindowregister[$object.target] = {};

		$appwindowregister[$object.target].xbackup = style.left;
		style.left = '3000px';
	}
	else
	{
		//appear($object.target);
		//show($object.target);

		if (style.left == '3000px')
			style.left = $appwindowregister[$object.target].xbackup;
	}
}

function updateappwindow($object)
{
	var style = $style($object.target);
	if ($object.width != undefined && delPx(style.width) != $object.width)
		style.width = $object.width + '%';

	if ($object.height != undefined && delPx(style.height) != $object.height)
		style.height = $object.height + '%';

	if ($object.x != undefined && delPx(style.left) != $object.x)
		style.left = $object.x + '%';

	if ($object.y != undefined && delPx(style.top) != $object.y)
		style.top = $object.y + '%';
}

function officitylog($string)
{
	$officitylogs += $string + '\n';
	if (console != undefined)
	{
		console.log($string);
	}
}