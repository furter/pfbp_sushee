<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/mail_postprocessing.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

if ($requestName=='UPDATE')
{
	if( ($values['Type']=='in' || $former_values['Type']=='in') && isset($values['Junk']) )
	{
		if( $former_values['Junk']!=$values['Junk'])
		{
			$moduleInfo = moduleInfo('mail');

			include_once(dirname(__FILE__).'/../common/automatic_classifier.class.php');
			$classifier = new automatic_classifier();

			// Marked as Junk or 'Not junk' by human interaction
			$values['JunkDetection']='human';
			$db_conn->Execute('UPDATE `'.$moduleInfo->tableName.'` SET `JunkDetection`="human" WHERE ID=\''.$IDs_array[0].'\';');
			$subject = $former_values['Subject'];
			$plaintext = $former_values['PlainText'];
			$from = $former_values['From'];
			$cc = $former_values['Cc'];
			$to = $former_values['To'];
			$total_mail_str = $from.$subject.$to.$cc.$plaintext;
			if($values['Junk']==1)
			{
				$former_category = 'mail_notspam';
				$category = 'mail_spam';
				$weight = 1;
			}
			else
			{
				$former_category = 'mail_spam';
				$category = 'mail_notspam';
				$weight = 2;
			}
		
			if($former_values['Junk']!=2 && $former_values['JunkDetection']!='computer')
			{
				$classifier->untrain($total_mail_str,$former_category,$weight);
			}

			$classifier->train($total_mail_str,$category,$weight);
			$classifier->update_categories_informations();
		}
	}
}

return true;