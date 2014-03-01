<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/fields.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");

class FieldsCollection extends SusheeObject{
	
	var $fields = false;
	
	function FieldsCollection(){
		$this->fields = &new Vector();
	}
	
	function add(&$field){
		if(is_object($field))
			$this->fields->add($field->getName(),$field);
	}
	
	function reset(){
		if($this->fields)
			$this->fields->reset();
	}
	function &next(){
		return $this->fields->next();
	}
	
	function implode($separator=','){
		$this->fields->reset();
		$first = true;
		$str = '';
		while($field = $this->fields->next()){
			if(!$first){
				$str.=$separator;
			}
			$str.='`'.$field->getName().'`';
			$first = false;
		}
		return $str;
	}
}

class DBField extends SusheeObject{
	
	var $name = false;
	
	function DBField($name){
		$this->setName($name);
	}
	
	function setName($name){
		$this->name = $name;
	}
	
	function getName(){
		return $this->name;
	}
}