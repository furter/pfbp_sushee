<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchModule.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/datas_structure.class.php');
require_once(dirname(__FILE__).'/../private/search.inc.php');


/*
Encapsulating SEARCH MODULE because there was a former shorter notation, and we want to keep compatibility
*/
class searchModule extends RetrieveOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		// old notation: compatibility
		if($this->firstNode->valueOf('DENOMINATION')){
			// denomination of the module is given
			$moduleName = $this->firstNode->valueOf('DENOMINATION'); // former notation (ensuring backward compatibility)
			$moduleInfo = moduleInfo($moduleName);
			if($moduleInfo->loaded){
				// for the SearchElement class to understand what it must return
				$this->firstNode->setAttribute('ID',$moduleInfo->getID());
			}
		}
		// forcing the denomination to be EQUAL and not LIKE, to have exact match (only if no operator yet)
		if($this->firstNode->valueOf('INFO/DENOMINATION')){
			$denomNode = $this->firstNode->getElement('INFO/DENOMINATION');
			if(!$denomNode->getxSusheeOperator()){
				$denomNode->setAttribute('op','=');
			}
		}

		$search = new SearchElement($this->getName(),$this->operationNode);
		$res = $search->execute();
		if($res===true){
			$this->setXML($search->getXML());
			return true;
		}else if(is_bool($res)){
			$this->setMsg($search->getMsg());
			return false;
		}else{
			$this->setMsg($res);
			return false;
		}
	}
	
}

?>