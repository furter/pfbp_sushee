<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/tryQuery.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Sushee_tryCommands extends RetrieveOperation{
	
	function parse(){
		
		return true;
	}
	
	function operate(){
		$shell = new Sushee_Shell();
		$commands = $this->firstNode->getElements('/*');
		$stop = false;
		
		$xml = '<RESULTS';
		$xml.= $this->getOperationAttributes();
		$xml.='><SUCCESS>';
		
		foreach($commands as $node){
			$shell->reset();
			$shell->addCommand($node->toString());
			$shell->execute();
			
			switch($node->nodeName()){
				case 'SEARCH':
				case 'GET':
				case 'COUNT':
					// checking the count of elements
					if($shell->valueOf('/RESPONSE/RESULTS/@hits') == 0){
						$stop = true;
					}
					break;
				default:
					// verifying we didnt get any error message
					if($shell->exists('/RESPONSE/MESSAGE[@msgType != 0]')){
						$stop = true;
					}
			}
			$cmd_result=$shell->copyOf('/RESPONSE/*[name() != "NECTIL" and name() != "URL"]');
			if($stop == true){
				$xml.='</SUCCESS><ERROR>'.$cmd_result.'</ERROR>';
				break;
			}else{
				$xml.=$cmd_result;
			}
		}
		if(!$stop){
			$xml.='</SUCCESS>';
		}else{
			// trying the fallback if operation failed
			if($this->getOperationNode()->exists('FALLBACK')){
				$fallback = $this->getOperationNode()->copyOf('FALLBACK/*');
				$shell->reset();
				$shell->addCommand($fallback);
				
				$shell->execute();
				$cmd_result=$shell->copyOf('/RESPONSE/*[name() != "NECTIL" and name() != "URL"]');
				$xml.='<FALLBACK>'.$cmd_result.'</FALLBACK>';
			}
			
		}
		$xml.='</RESULTS>';
		
		$this->setXML($xml);
		
		return true;
	}
	
}


?>