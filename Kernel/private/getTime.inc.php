<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getTime.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/date.class.php');

/*

<GET>
	<TIME>date expression</TIME>
</GET>

date expressions can be : 

today
now
today+7 days
now+7 hours
today-1 months
this_month
etc ...

*/

class Sushee_getTime extends RetrieveOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		$value = $this->firstNode->valueOf();
		if(!$value){
			$value = 'now';
		}
		$converter = new DateTimeKeywordConverter($value,'=');
		$date = new Date($converter->execute());
		
		if( $this->firstNode->nodeName() == 'DATE' ){
			$time = $date->getDate();
		}else{
			$time = $date->getDateTime();
		}
		$nodeName = $this->firstNode->nodeName();
		
		$this->setXML('<RESULTS'.$this->getOperationAttributes().'><'.$nodeName.'>'.$time.'</'.$nodeName.'></RESULTS>');
		
		return true;
	}
	
}


?>