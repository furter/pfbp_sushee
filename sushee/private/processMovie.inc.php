<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/processMovie.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/movie.class.php');
class processMovie extends RetrieveOperation{
	var $source;
	function parse(){
		$source_path = $this->firstNode->valueOf('PATH');
		$this->source = new Sushee_Movie($source_path);
		
		if(!$this->source->exists()){
			$this->setError("The source movie doesn't exist.");
			return false;
		}
		
		return true;
	}
	
	function operate(){
		$outputNodes = $this->firstNode->getElements('OUTPUT/*');
		$targets = array();
		foreach($outputNodes as $outputNode){
			$nodeName = $outputNode->nodeName();
			$target_path = $outputNode->valueOf('PATH');
			switch($nodeName){
				case 'MOVIE':
					$effects = new Sushee_MovieEffects();
					$effects->setSource($this->source);
					$target = new Movie($target_path);
					$effects->setTarget($target);
					$effectsNodes = $outputNode->getElements('EFFECTS/*');
					$effects->parseXMLNodes($effectsNodes);
					$effects->execute();
					break;
				case 'IMAGE':
					$effects = new Sushee_ImageMovieEffects();
					$effects->setSource($this->source);
					$target = new Image($target_path);
					$effects->setTarget($target);
					$effectsNodes = $outputNode->getElements('EFFECTS/*');
					$effects->parseXMLNodes($effectsNodes);
					$effects->execute();
					break;
				default:
			}
			if(!$target->exists()){
				$this->setError('Problem generating file `'.$target->getPath().'` : '.$effects->getMessage());
				return false;
			}else{
				$targets[]=$target;
			}
		}
		$xml = '<RESULTS'.$this->getOperationAttributes().'>';
		foreach($targets as $target){
			$xml.=$target->getXML();
		}
		$xml.= '</RESULTS>';
		$this->setXML($xml);
		return true;
	}
}

?>