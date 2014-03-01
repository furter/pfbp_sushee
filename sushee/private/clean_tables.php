<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/clean_tables.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/**
 * Tables Cleaner Class
 * 
 * Deletes rows flagged has "inactive" (Activity field set to '0') from specified modules.
 * 
 * Modules names are found in $_GET['module']. 
 * It's either '*' (all modules) or a comma separated list of names.
 * 
 * Examples :
 * '*'
 * 'contact'
 * 'contact,media'
 * 
 * @author	Julien <julien@nectil.com>
 * @since	2010-10-28
 * @version	0.1
 */
class TablesCleaner
{
	var $modulesToClean;
	
	function TablesCleaner()
	{
		if (!isset($_GET['module']) || !trim($_GET['module'])) {
			throw new Exception('Param module not found or has no value');
		}
		require_once dirname(__FILE__) . '/../common/common_functions.inc.php';
		require_once dirname(__FILE__) . '/../common/module.class.php';
		$this->init();	
	}
	
	function init()
	{		
		$this->modulesToClean = array();
		$paramModule = $_GET['module'];
		if ('*'==$paramModule) {
			$modules = new modules();
			while ($moduleInfo = $modules->next()) {
				$this->modulesToClean[] = $moduleInfo;
			}
		} else {
			$paramExploded = explode(',', $paramModule);
			foreach ($paramExploded as $moduleName) { /*if param has no comma, explode returns an array with one element*/
				if ($moduleName) {/*handles trailing/leading comma case : $_GET['module']='foo,bar,'*/
					$this->modulesToClean[] = moduleInfo($moduleName);
				}
			}
		}		
	}
	
	function execute()
	{
		$db = null;
		for ($i=0, $max=count($this->modulesToClean); $i<$max; $i++) {
			if ($this->modulesToClean[$i]->loaded) {/*module could have not been loaded because it's name was unknown*/
				if (!$db) {
					$db = db_connect();
				}
				$tableName = $this->modulesToClean[$i]->getTableName();				
				$db->Execute("DELETE FROM `{$tableName}` WHERE `Activity`='0'");
			}
		}
	}
}

try {
	$tablesCleaner = new TablesCleaner();
	$tablesCleaner->execute();
} catch(Exception $e) {	
	exit(1);/*non zero values indicates that the script terminated with error*/
}

?>