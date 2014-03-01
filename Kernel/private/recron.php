<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/recron.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
error_reporting(E_ERROR | E_WARNING | E_PARSE);

function generateCronFile()
{
	$OS = getServerOS();
	if ($OS=='windows')
	{
		makeDir(dirname(__FILE__)."/../../Files/tasks/");
		$cronJob_bat = dirname(__FILE__)."/../../Files/tasks/cronJob.bat";
		$fd = fopen($cronJob_bat, "w+");
		fwrite($fd,'"'.$GLOBALS["phpExecutable"].'" "'.$GLOBALS["nectil_dir"].'\\'.Sushee_dirname.'\private\cronJob.php"');
		fwrite($fd,"\n");
		fclose($fd);
		if(file_exists($cronJob_bat))
		{
			$cronJob_clean = 'schtasks /delete /tn cronJob /f 2>&1';
			$cronJob_command = 'schtasks /create /sc minute /mo 1 /tn cronJob /tr \""'.realpath($cronJob_bat).'"\" 2>&1';
			debug_log(shell_exec($cronJob_clean));
			debug_log($cronJob_command);
			debug_log(shell_exec($cronJob_command));
		}
		$dailyTask_bat = dirname(__FILE__)."/../../Files/tasks/dailyTask.bat";
		$fd = fopen($dailyTask_bat, "w+");
		if(file_exists($dailyTask_bat))
		{
			fwrite($fd,'"'.$GLOBALS["phpExecutable"].'" "'.$GLOBALS["nectil_dir"].'\\'.Sushee_dirname.'\private\tasks.php"');
			fwrite($fd,"\n");
			fclose($fd);
			$dailyTask_clean = 'schtasks /delete /tn dailyTask /f 2>&1';
			$dailyTask_command = 'schtasks /create /sc daily /mo 1 /st 03:30:00 /tn dailyTask /tr \""'.realpath($dailyTask_bat).'"\" 2>&1';
			debug_log(shell_exec($dailyTask_clean));
			debug_log($dailyTask_command);
			debug_log(shell_exec($dailyTask_command));
		}
	}
	else
	{
		makeDir(dirname(__FILE__)."/../../Files/tasks/");
		$fd = fopen(dirname(__FILE__)."/../../Files/tasks/my_task", "w+");

		// for the primary Kernel
		fwrite($fd,"* * * * * ".$GLOBALS["phpExecutable"]." ".$GLOBALS["nectil_dir"]."/".Sushee_dirname."/private/cronJob.php");
		fwrite($fd,"\n30 3 * * * ".$GLOBALS["phpExecutable"]." ".$GLOBALS["nectil_dir"]."/".Sushee_dirname."/private/tasks.php");
		fwrite($fd,"\n");
		fclose($fd);
		debug_log(shell_exec('crontab '.dirname(__FILE__)."/../../Files/tasks/my_task 2>&1"));
	}
}

generateCronFile();