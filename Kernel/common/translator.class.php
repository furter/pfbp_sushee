<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/translator.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/url.class.php");
require_once(dirname(__FILE__)."/../common/commandline.class.php");
require_once(dirname(__FILE__)."/../common/json.class.php");

class sushee_translator extends SusheeObject{
	
	var $orig_lg = false;
	var $target_lg = false;
	
	function setOriginLanguage($lg){
		if(is_object($lg)){
			$this->orig_lg = $lg;
		}else{
			$this->orig_lg = new sushee_language($lg);
		}
	}

	function setTargetLanguage($lg){
		if(is_object($lg)){
			$this->target_lg = $lg;
		}else{
			$this->target_lg = new sushee_language($lg);
		}
	}

	function execute($word){
		$url = new URL('http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q='.urlencode($word).'&langpair='.$this->orig_lg->getISO1().'|'.$this->target_lg->getISO1());
		$response = $url->execute();

		$json_obj = sushee_json::decode($response);

		$word_translation = $json_obj->responseData->translatedText;
		//$word_translation = $response; // for debugging

		return $word_translation;
	}

	function getClassicLanguages(){
		$lgs = array('fre');
		$sushee_lgs = array();
		foreach($lgs as $lg){
			$sushee_lgs[] = new sushee_language($lg);
		}
		return $sushee_lgs;
	}
}

class sushee_language extends SusheeObject{
	
	var $ID   = false;
	var $ISO1 = false;
	
	function getID(){
		return $this->ID;
	}
	
	function setID($ID){
		$this->ID   = $ID;
		$this->ISO1 = false;
	}
	
	function sushee_language($lg){
		//$this->ID = $lg;
		$this->setID($lg);
	}
	
	function getISO1(){
		if ($this->ISO1){
			return $this->ISO1;
		}
		$sql = 'SELECT `ISO1` FROM `languages` WHERE `ID`="'.encode_for_DB($this->ID).'"';
		$db_conn = db_connect(true);
		
		$row = $db_conn->getRow($sql);
		if(!$row){
			return false;
		}
		
		$this->ISO1 = $row['ISO1'];			
		return $row['ISO1'];
	}
	
	/**
	 * Checks if language is valid
	 * 
	 * @param 	string $lg
	 * @return 	boolean
	 * @author	julien@nectil.com
	 * @since	March 14, 2011
	 */
	function isValid($lg){
		$lang = new Sushee_Language($lg);
		return !!$lang->getISO1();
	}
}