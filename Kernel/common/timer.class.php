<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/timer.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/*
Class handling a global timer, allowing to measure sushee performance
*/

function getmicrotime(){ 
	list($usec,$sec) = explode(' ',microtime());
	return ((float)$usec + (float)$sec);
}

// ---------------
// Allows to measure the time spent for the whole sushee session
// ---------------

class Sushee_Timer
{
	static function start()
	{
		$GLOBALS["time_start"] = getmicrotime();
		$GLOBALS["steps"] = 0;
		$GLOBALS["last_step"] = $GLOBALS["time_start"];
	}

	static function lap($message)
	{
		$mtime = getmicrotime();
		$lap = round( ($mtime - $GLOBALS["last_step"]) , 3) . ' / ' . round( ($mtime - $GLOBALS["time_start"]) , 3) .' total';
		if ($message)
		{
			$lap = $message.': '.$lap;
		}
		$GLOBALS["tracker"][]= $lap;
		$GLOBALS["last_step"] = $mtime;
		$GLOBALS["steps"]++;
		return $mtime - $GLOBALS["time_start"];
	}

	static function toString()
	{
		return implode("\r\n",$GLOBALS["tracker"]);
	}

	static function toXML()
	{
		return '<track>'.implode("</track><track>",$GLOBALS["tracker"]).'</track>';
	}

	static function toHTML()
	{
		return '<ul class="Sushee_Timer"><li>'.implode("</li><li>",$GLOBALS["tracker"]).'</li></ul>';
	}
}

// ---------------
// Allows to measure the time spent on a specific task during a sushee process (designated by taskName)
// ---------------

class Sushee_TaskTimer
{
	var $taskName;
	
	function Sushee_TaskTimer($taskName)
	{
		$this->taskName = $taskName;
	}

 	function start()
	{
		$GLOBALS['timers'][$this->taskName]['start'] = getmicrotime();
	}

	function stop()
	{
		$GLOBALS['timers'][$this->taskName]['end'] = getmicrotime();
		$durationTillStart = $GLOBALS['timers'][$this->taskName]['end'] - $GLOBALS['timers'][$this->taskName]['start'];
		$GLOBALS['timers'][$this->taskName]['duration']+= $durationTillStart;
	}
	
	function getDuration()
	{
		return $GLOBALS['timers'][$this->taskName]['duration'];
	}
}

class Sushee_TaskTimers
{
	static function toString()
	{
		$str = '';
		if(is_array($GLOBALS['timers']))
		{
			foreach($GLOBALS['timers'] as $taskName=>$taskDatas)
			{
				$str.= $taskName.' took '.$taskDatas['duration']."\r\n";
			}
		}
		return $str;
	}
}

function getTimer($message=FALSE)
{
	// verbose parameter DEPRECATED
	return Sushee_Timer::lap($message);
}