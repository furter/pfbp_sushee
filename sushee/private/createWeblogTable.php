<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createWeblogTable.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nql.class.php");
require_once(dirname(__FILE__)."/../common/date.class.php");

$today=new Date(date('Y-m-d'));
$db_conn = db_connect();
$sql="SELECT * FROM logs_web_years WHERE year=".$today->getYear().";";
$table = $db_conn->getRow($sql);

if(!$table){
	$sql="
	INSERT INTO logs_web_years (year) VALUES (".$today->getYear().");";
	debug_log($sql);
	$db_conn->Execute($sql);

	$sql="
	CREATE TABLE IF NOT EXISTS logs_web_".$today->getYear()." (
		month tinyint(1) NOT NULL DEFAULT '0',
		day tinyint(1) NOT NULL DEFAULT '0',
		hours tinyint(1) NOT NULL DEFAULT '0',
		minutes tinyint(1) NOT NULL DEFAULT '0',
		seconds tinyint(1) NOT NULL DEFAULT '0',
		VisitID bigint(20) NOT NULL DEFAULT '0',
		URL bigint(20) NOT NULL DEFAULT '0' REFERENCES referer_page(ID),
		elementID bigint(20) NOT NULL DEFAULT '0',
		ViewingCode varchar(250) NOT NULL DEFAULT '',
		LanguageID varchar(10) NOT NULL DEFAULT '',
		referrerHost bigint(20) NOT NULL DEFAULT '0' REFERENCES referer(ID),
		referrerPage bigint(20) NOT NULL DEFAULT '0' REFERENCES referer_page(ID),
		isSearch tinyint(1) NOT NULL DEFAULT '0',
		keyword text NOT NULL DEFAULT '',
		referrerCode varchar(255) NOT NULL DEFAULT '',
		bannerCode varchar(255) NOT NULL DEFAULT '',
		KEY(month, day),
		KEY(VisitID)
	) TYPE=MyISAM;";
	$db_conn->Execute($sql);
	echo "table logs_web_".$today->getYear()." created<br/>";
}else{
	echo "table logs_web_".$today->getYear()." already exist<br/>";
}

$today->addYear(1);

$sql="SELECT * FROM logs_web_years WHERE year=".$today->getYear().";";
$table = $db_conn->getRow($sql);
if(!$table){
	$sql="
	INSERT INTO logs_web_years (year) VALUES (".$today->getYear().");";
	debug_log($sql);
	$db_conn->Execute($sql);

	$sql="
	CREATE TABLE IF NOT EXISTS logs_web_".$today->getYear()." (
		month tinyint(1) NOT NULL DEFAULT '0',
		day tinyint(1) NOT NULL DEFAULT '0',
		hours tinyint(1) NOT NULL DEFAULT '0',
		minutes tinyint(1) NOT NULL DEFAULT '0',
		seconds tinyint(1) NOT NULL DEFAULT '0',
		VisitID bigint(20) NOT NULL DEFAULT '0',
		URL bigint(20) NOT NULL DEFAULT '0' REFERENCES referer_page(ID),
		elementID bigint(20) NOT NULL DEFAULT '0',
		ViewingCode varchar(250) NOT NULL DEFAULT '',
		LanguageID varchar(10) NOT NULL DEFAULT '',
		referrerHost bigint(20) NOT NULL DEFAULT '0' REFERENCES referer(ID),
		referrerPage bigint(20) NOT NULL DEFAULT '0' REFERENCES referer_page(ID),
		isSearch tinyint(1) NOT NULL DEFAULT '0',
		keyword text NOT NULL DEFAULT '',
		referrerCode varchar(255) NOT NULL DEFAULT '',
		bannerCode varchar(255) NOT NULL DEFAULT '',
		KEY(month, day),
		KEY(VisitID)
	) TYPE=MyISAM;";
	$db_conn->Execute($sql);
	echo "table logs_web_".$today->getYear()." created<br/>";
}else{
	echo "table logs_web_".$today->getYear()." already exist<br/>";
}

?>