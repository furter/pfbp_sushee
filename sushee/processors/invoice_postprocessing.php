<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/invoice_postprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
$was_not_set = ($former_values['IDdoc'] == '');

if (
	($was_not_set && $former_values['Status'] != 'draft' && $values['Status'] != 'draft') ||
	($was_not_set && $former_values['Status'] == 'draft' && (isset($values['Status']) && $values['Status'] == 'sent'))
	)
{
	$year = date("Y");

	$type = $values['Type'];
	if (!isset($type))
	 	$type = $former_values['Type'];

	$countSQL = "
		SELECT count(ID) AS 'total'
		FROM invoices
		WHERE 
			SUBSTRING(IssueDate,1,4) = '".$year."' AND
			Activity != '0' AND
			IDdoc != '' AND
			Type = '".$type."'";

	$row = $db_conn->getRow($countSQL);

	$count = $row['total'] + 1;
	$countPow = $count; 
	$countString = '';

	while($countPow < 1000)
	{
		$countString .= '0';
		$countPow *= 10;
	};

	$count = $countString.$count;	
	$IDdoc = $type.$year.$count;
	
	debug_log('$IDdoc : '.$IDdoc);
	
	$db_conn->Execute('UPDATE `invoices` SET `IDdoc`="'.$IDdoc.'" WHERE ID=\''.$IDs_array[0].'\';');
}

return TRUE;
?>
