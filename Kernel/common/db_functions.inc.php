<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/db_functions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../adodb_mini/adodb.inc.php");
	
$ADODB_COUNTRECS = false;

function CountExecs($db, $sql, $inputarray)
{
	global $EXECS;
	if (!is_array($inputarray)) $EXECS++;
	# handle 2-dimensional input arrays
	else if (is_array(reset($inputarray))) $EXECS += sizeof($inputarray);
	else $EXECS++;
	
}

function db_connect($common=FALSE)
{
	$db_host = $GLOBALS['db_host'];
	$db_login = $GLOBALS['db_login'];
	$db_password = $GLOBALS['db_password'];
	$db_name = $GLOBALS['db_name'];
	
    //common connection if generic backoffice
	if ($common===TRUE)
	{
		// if we don't have a connection yet
		if ( !isset($GLOBALS["common_conn"]) )
		{
			$conn = &ADONewConnection('mysql');
			$conn->NConnect($db_host,$db_login,$db_password,$GLOBALS["generic_backoffice_db"]);
			// to have accented characters returned in UTF8
			$conn->execute("SET NAMES utf8");
			$GLOBALS["common_conn"]=$conn;
		}
		else
		{
			// we return the connection defined earlier
			$conn = $GLOBALS["common_conn"];
		}
	}
	else
	{
		// if we don't have a connection yet
		if ( !isset($GLOBALS["conn"]) )
		{
			$conn = &ADONewConnection('mysql');
			$conn->NConnect($db_host,$db_login,$db_password,$db_name);
			
			// to have accented characters returned in UTF8
			$conn->execute("SET NAMES utf8");
			$GLOBALS["conn"] = $conn;
		}
		else
		{
			// we return the connection defined earlier
			$conn = $GLOBALS["conn"];
		} 
	}
	
	$conn->fnExecute = 'CountExecs';
	return $conn;
}

function specific_db_connect($db_name)
{
	if (!function_exists('mysql_connect'))
	{
		return false;
	}

	$db_host = $GLOBALS['db_host'];
	$db_login = $GLOBALS['db_login'];
	$db_password = $GLOBALS['db_password'];
	$conn = &ADONewConnection('mysql');
	$conn->NConnect($db_host,$db_login,$db_password,$db_name);
	$conn->fnExecute = 'CountExecs';
	$conn->fnCacheExecute = 'CountCachedExecs';
	return $conn;
}