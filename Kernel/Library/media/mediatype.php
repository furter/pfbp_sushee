<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/media/mediatype.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once("../sushee/common/nql.class.php");

$depth = 2;
$ID = $_GET['ID'];

if(isset($_GET['depth']))
	$depth = $_GET['depth'];

$NQL = new NQL();

$NQL->addCommand(
	'<GET name="media">
		<MEDIA ID="'.$ID.'"/>
		<RETURN depth="'.$depth.'">
			<INFO creator-info="true" />
			<DESCRIPTIONS/>
		</RETURN>
	</GET>');

$NQL->execute();

echo $NQL->transform("mediatype.xsl");
?>