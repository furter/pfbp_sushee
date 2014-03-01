<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/datas_structure.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
class Vector extends SusheeObject{
	var $vector;
	var $name = false;
	function Vector(){
		$this->vector = array();
	}
	function add($ID,&$element){
		$this->vector['elt'.$ID]=&$element;
	}
	function remove($ID){
		if(isset($this->vector['elt'.$ID])){
			unset($this->vector['elt'.$ID]);
			return true;
		}else
			return false;
	}
	function reset(){
		reset($this->vector);
	}
	function &next(){
		if($this->size()==0)
			return false;
		$elt = &$this->vector[key($this->vector)];
		next($this->vector);
		return $elt;
	}
	function &first(){
		$elt = &$this->vector[0];
		return $elt;
	}
	function &getElement($ID){
		if(isset($this->vector['elt'.$ID])){
			$elt = &$this->vector['elt'.$ID];
			return $elt;
		}else
			return false;
	}
	function size(){
		return sizeof($this->vector);
	}
	function exists($ID){
		if(isset($this->vector['elt'.$ID]))
			return true;
		else
			return false;
	}
	function implode($separator=','){
		$implosion = '';
		reset($this->vector);
		$first = true;
		do{
			if($first!=true)
				$implosion.= $separator;
			else
				$first = false;
			$key = key($this->vector);
			$key = substr($key,3);
			if(is_numeric($key))
				$implosion.= $key;
			else
				$implosion.= '"'.$key.'"';
		}while(next($this->vector));
		return $implosion;
	}
	
	function getXML(){
		$this->reset();
		$xml = '';
		
		
		while($elt= &$this->next()){
			$xml.=$elt->getXML();
		}
		if($this->getName()){
			$stringnode = new StringXMLNode($this->getName());
			return $stringnode->getOpeningTag().$xml.$stringnode->getClosingTag();
		}else
			return $xml;
	}
	
	function setName($name){
		$this->name = $name;
	}
	
	function getName(){
		return $this->name;
	}
}

class Matrix extends SusheeObject{
	var $matrix;
	function Matrix(){
		$this->matrix = &new Vector();
	}
	function add($rowID,$colID,&$element){
		if($this->matrix->exists($rowID)){
			$row = &$this->matrix->getElement($rowID);
		}else{
			$this->matrix->add($rowID,new Vector());
			$row = &$this->matrix->getElement($rowID);
		}
		if($colID===false){
			$size = $row->size();
			if($size==0)
				$colID = 0;
			else
				$colID = $size+1;
		}
		$row->add($colID,$element);
	}
	function &getRow($rowID){
		if($this->matrix->exists($rowID)){
			$row = &$this->matrix->getElement($rowID);
			return $row;
		}else {
			return false;
		}
	}
	function &getElement($rowID,$colID){
		if($this->matrix->exists($rowID)){
			$row = &$this->matrix->getElement($rowID);
			return $row->getElement($colID);
		}else
			return false;
	}
	
	function &next(){
		$elt = &$this->matrix->next();
		return $elt;
	}
	
}

class Stack extends SusheeObject{
	
	var $stack;
	
	function Stack(){
		$this->stack = array();
	}
	
	function &getCurrent(){
		if(sizeof($this->stack)==0)
			return false;
		$elt = &$this->stack[sizeof($this->stack)-1];
		return $elt;
	}
	
	function push(&$elt){
		$this->stack[] = &$elt;
		return true;
	}
	
	function &pop(){
		$elt = &array_pop($this->stack);
		return $elt;
	}
}

?>