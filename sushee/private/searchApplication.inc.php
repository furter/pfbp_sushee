<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchApplication.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/application.class.php');

class searchApplication extends RetrieveOperation{
	
	var $applicationID = false;
	
	function parse(){
		$this->applicationID = $this->firstNode->valueOf('@ID');
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		if($this->applicationID){
			if($this->applicationID >= 1024){
				$app = new CustomApplication($this->applicationID);
			}else{
				$app = new OfficialApplication($this->applicationID);
			}
			$xml.=$app->getXML();
		}else{
			$list = new ApplicationCollection();

			while($app = $list->next()){
				$xml.=$app->getXML();
			}
		}
		
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
	
}

?>