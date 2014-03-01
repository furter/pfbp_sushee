<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/file/image_preview.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once("../common/common_functions.inc.php");
$path = $_GET['path'];
$width = $_GET['width'];
$height = $_GET['height'];

if (!empty($path))
{
	imageTransform('
	<IMAGE path="'.$_GET['path'].'" refresh="monthly">
		<choose>
			<horizontal>
				<resize width="'.$width.'"/>
			</horizontal>
			<vertical>
				<resize height="'.$height.'"/>
			</vertical>
	   </choose>
		<convert format="jpg"/>
	</IMAGE>
	',true);
}

?>