<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/license_postprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

// $former_values['value_name'];
// $values['value_name'];
// $return_values['value_name'];

if($former_values['Serial'] == ''  && $former_values['Denomination'] != '')
{
	$serial = md5($former_values['ID'].$former_values['Denomination']);
	$db_conn->Execute('UPDATE `licenses` SET `Serial` = "'.$serial.'" WHERE ID=\''.$IDs_array[0].'\';');
	debug_log('new license: '.$serial);
}

return TRUE;
?>