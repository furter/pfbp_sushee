<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/cleanDescConfig.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once("../common/common_functions.inc.php");

$db_conn = db_connect();

$sql = 'SELECT * FROM mediatypesconfig';
$rs = $db_conn->Execute($sql);
while($row = $rs->FetchRow()){
	$del_parts.=' AND ID!='.$row['DescriptionConfigID'];
}
$del_sql = 'DELETE FROM descriptionsconfig WHERE ModuleID=5'.$del_parts;
echo $del_sql;
if(isset($_GET['confirm']))
$db_conn->Execute($del_sql);
?>
