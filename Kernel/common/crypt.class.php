<?php

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");

class Crypt extends SusheeObject{
	var $algo;
	var $key;
	function Crypt($algo=false){
		$this->algo = $algo;
	}
	
	function setAlgo($algo){
		$this->algo = $algo;
	}
	
	function setKey($key){
		$this->key = $key;
	}
	
	function execute($string){
		$key = $this->key;
		switch($this->algo){
			case 'NECTIL_XOR_encryption': return $this->encrypt_XOR($string);
			case 'BLOWFISH':
				require_once(dirname(__FILE__).'/../common/Mail/blowfish.php');
				$blowfish = new Crypt_Blowfish($key);
				$encrypted_data = $blowfish->encrypt($string);
				return base64_encode($encrypted_data);
				break;
			case '':return $string;// no crypting
			default: return false;
		}
	}
	
	function encrypt_XOR($string) {
		$key = $this->key;
		$result = '';
		for($i=0; $i<strlen($string); $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % strlen($key))-1, 1);
			$char = chr(ord($char)+ord($keychar));
			$result.=$char;
		}
		return base64_encode($result);
	}
}

class Decrypt extends SusheeObject{
	var $algo;
	function Decrypt($algo=false){
		$this->algo = $algo;
	}
	
	function setAlgo($algo){
		$this->algo = $algo;
	}
	
	function setKey($key){
		$this->key = $key;
	}
	
	function execute($string){
		$key = $this->key;
		switch($this->algo){
			case 'NECTIL_XOR_encryption': return $this->decrypt_XOR($string);
			case 'BLOWFISH':
			require_once(dirname(__FILE__).'/../common/Mail/blowfish.php');
				$string = base64_decode($string);
				$blowfish = new Crypt_Blowfish($key);
				$decrypted_data = $blowfish->decrypt($string);
				return $decrypted_data;
				break;
			case '':return $string;// no crypting
			default: return false;
		}
	}
	
	function decrypt_XOR($string) {
		$key = $this->key;
		$result = '';
		$string = base64_decode($string);

		for($i=0; $i<strlen($string); $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % strlen($key))-1, 1);
			$char = chr(ord($char)-ord($keychar));
			$result.=$char;
		}
		return $result;
	}
}