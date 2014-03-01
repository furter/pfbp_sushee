<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/registers.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");

//---------------------------
// class allowing to save elements heavy to compute/handle
//---------------------------

class Sushee_Register extends SusheeObject{

	var $name = false;
	var $vect = false;

	function Sushee_Register($name){
		if(isset($GLOBALS['register'][$name])){
			$this->vect = &$GLOBALS['register'][$name];
		}else{
			$vect = &new Vector();
			$this->vect = &$vect;
			$GLOBALS['register'][$name] = &$vect;
		}
	}

	function add($ID,&$element){
		$this->vect->add($ID,$element);
	}

	function &getElement($ID){
		$elt = &$this->vect->getElement($ID);
		return $elt;
	}

	function exists($ID){
		return $this->vect->exists($ID);
	}

	function &next(){
		$elt = &$this->vect->next();
		return $elt;
	}

	function reset(){
		$this->vect->reset();
	}
}

class Sushee_MailsAccountRegister extends Sushee_Register{

	function MailsAccountRegister(){
		Sushee_Register::Sushee_Register('MailsAccountRegister');
	}

	function &getElement($ID){
		if(!$this->vect->exists($ID)){
			$elt = &new MailsAccount(array('ID'=>$ID));
			$elt->loadFields();
			$this->add($ID,$elt);
			return $elt;
		}else{
			$elt = &$this->vect->getElement($ID);
			return $elt;
		}
	}
}