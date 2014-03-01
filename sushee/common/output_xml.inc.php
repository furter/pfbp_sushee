<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/output_xml.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

if($GLOBALS["no_response_node"]===true)
{
	$strRet=$strResponse;
}
else
{
	// enclosing the results of the differents requests in a unique node
	// handling the namespaces necessary for the external objects

	require_once(dirname(__FILE__)."/../common/namespace.class.php");

	$namespaces = new NamespaceCollection();
	$namespaces_str = $namespaces->getXMLHeader();
	
	$sess = &$_SESSION[$GLOBALS["nectil_url"]];

	if($GLOBALS['no_variable_nectil_vars']!==true && !$GLOBALS["php_request"])
	{
		$strRet = SUSHEE_XML_HEADER.'<RESPONSE'.$namespaces_str.' userID="'.$sess['SESSIONuserID'].'" sessionID="'.session_id().'">'.$strResponse;
	}
	else
	{
		$strRet = SUSHEE_XML_HEADER.'<RESPONSE'.$namespaces_str.'>'.$strResponse;
	}

	// adding some stats
	if (!$GLOBALS["php_request"] || $_GET['stats'] === 'true' || $GLOBALS['sushee_stats'] === true)
	{
		$strRet.="<SQL_STATS><TOTALQUERIES>".($GLOBALS["EXECS"]+$GLOBALS["CACHED"])."</TOTALQUERIES><SearchQUERIES>".$GLOBALS["SearchQUERIES"]."</SearchQUERIES></SQL_STATS>";
		$strRet.="<TotalNectilElements>".$GLOBALS["TotalNectilElements"]."</TotalNectilElements>";
		$time_elapsed = getTimer('stats');
		$strRet.="<TIME>$time_elapsed</TIME>";
	}

	$strRet .= '</RESPONSE>';
	
	if ($output_xml!==FALSE)
	{
		xml_out($strRet);
	}
}