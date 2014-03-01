<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createWeblogYearlyCheckCron.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
	require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
	require_once(dirname(__FILE__)."/../common/nql.class.php");

	$nql=new NQL();
	$nql->addCommand('
		<SEARCH>
			<CRON>
				<INFO>
					<DENOMINATION>yearlyCheck</DENOMINATION>
					<DOMAIN>weblog</DOMAIN>
				</INFO>
			</CRON>
		</SEARCH>
	');
	$nql->execute();
	if($nql->getElement('/RESPONSE/RESULTS/CRON')){
		die('Cron already exists');
	}else{
		$nql->addCommand('
			<CREATE>
				<CRON>
					<INFO>
						<DENOMINATION>yearlyCheck</DENOMINATION>
						<DOMAIN>weblog</DOMAIN>
						<TYPE>url</TYPE>
					    <DAY>31,</DAY>
					    <MONTH>12,</MONTH>
					    <MINUTE>00,</MINUTE>
					    <HOUR>23,</HOUR>
					    <COMMAND>'.$GLOBALS["nectil_url"].'/sushee/private/createWeblogTable.php</COMMAND>
					    <STATUS>pending</STATUS>
					</INFO>
				</CRON>
			</CREATE>
		');
		$nql->execute();
		die('Cron created');
	}
?>