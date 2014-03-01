<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/public/confirm.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");

if(isset($_GET['ID']))
{
	global $directoryRoot;
	$confirm_dir = $directoryRoot.$slash."confirm";
	$subquery_file = $confirm_dir.$slash.'subquery_'.$_GET['ID'].'.xml';
	if (!file_exists($subquery_file))
	{
		die('Error: no confirmation was asked for this reference or the confirmation was already done.');
	}
	query(file_in_string($subquery_file),false);
	unlink($subquery_file);
	die('Success: we have registered your confirmation.');
}
else
{
	die('Error: the address must be incomplete.');
}
