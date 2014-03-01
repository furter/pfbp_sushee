<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/url.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");

class URL extends SusheeObject{
	var $url;
	var $output;
	var $method='get';
	var $params = array();
	var $files = array();
	var $headers = array();
	var $body = false; // XML Body

	function URL($url){
		$this->url = $url;
	}
	
	function getFilename(){
		$pos_last_slash = strrpos($this->url,'/');
		if($pos_last_slash!==false){
			return substr($this->url,$pos_last_slash+1);
		}else{
			return false;
		}
	}
	
	function setMethod($method){
		if(strtolower($method)=='get'){
			$this->method = 'get';
			return true;
		}else if(strtolower($method)=='post'){
			$this->method = 'post';
			return true;
		}
	}
	
	function getMethod(){
		return $this->method;
	}
	
	function setBody($body){
		$this->body = $body;
	}
	
	function addParam($name,$value){
		$this->params[$name]=$value;
	}
	
	function addFile($key,$path){
		$this->files[$key] = $path;
	}
	
	function addHeader($name,$value){
		$this->headers[$name] = $value;
	}
	
	function getParamString(){
		//XML Body
		if($this->body){
			return $this->body;
		}
		// POST params
		$separator = '';
		$params_str = '';
		foreach($this->params as $name=>$value){
			$params_str.=$separator;
			$params_str.=$name.'='.urlencode($value);
			$separator = '&';
		}
		if($params_str && $this->method == 'get' && strpos($this->url,'?')===false){
			return '?'.$params_str;
		}
		return $params_str;
	}

	function execute(){
		$response = false;
		
		$params_str = $this->getParamString();
		
		if(extension_loaded('curl')){
			$curl = curl_init();

			// Setup headers - I used the same headers from Firefox version 2.0.0.6
			// below was split up because php.net said the line was too long. :/
			if(!$this->headers['Accept']){
				$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
				$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
			}
			$header[] = "Cache-Control: max-age=0";
			$header[] = "Connection: keep-alive";
			$header[] = "Keep-Alive: 300";
			$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
			$header[] = "Accept-Language: en-us,en;q=0.5";
			$header[] = "Pragma: "; // browsers keep this blank.
			foreach($this->headers as $name=>$value){
				$header[] = $name.':'.$value;
			}
			$header[] = 'Expect:'; // forcing it to be empty, because it can be problematic if cUrl adds it by itself with auto '100-continue', which is not supported by all webservers
			
			curl_setopt($curl, CURLOPT_URL, $this->url);
			curl_setopt($curl, CURLOPT_USERAGENT, 'Sushee (+http://www.sushee.eu)');
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_REFERER, 'http://www.sushee.eu');
			curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
			curl_setopt($curl, CURLOPT_AUTOREFERER, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_TIMEOUT, 900);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl, CURLOPT_FAILONERROR, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, 'saveHeader'));
			curl_setopt($curl, CURLOPT_HEADER, FALSE);
		    curl_setopt($curl, CURLINFO_HEADER_OUT, true);
			
			if($this->method == 'post'){
				curl_setopt($curl, CURLOPT_POST,1);
				if($this->body){
					debug_log('calling URL '.$this->url.' with body '.$this->body);
					curl_setopt($curl,CURLOPT_POSTFIELDS,$this->body);
				}else{
					//debug_log('calling URL '.$this->url.' with post '.$params_str);
					if(sizeof($this->files) > 0 ){
						// application/mutipart-form-data
						$multiparts = array();
						foreach($this->params as $key=>$value){
							$multiparts[$key] = $value;
						}
						foreach($this->files as $key=>$path){
							$file = new File($path);
							if($file->exists()){
								if(strpos(phpversion(),'5.2') !== false){
									$multiparts[$key] = '@'.$file->getCompletePath();
								}else{
									$multiparts[$key] = '@'.$file->getCompletePath().';filename='.$file->getName(); // forcing the filename to be sent, because an oauth signature might have been computed
								}
								
							}else{
								throw new SusheeException('File `'.$path.'` doesnt exist');
							}
							
						}
						
						curl_setopt($curl,CURLOPT_POSTFIELDS,$multiparts);
					}else{
						// application/x-www-form-urlencoded
						curl_setopt($curl,CURLOPT_POSTFIELDS,$params_str);
					}
					
				}
			}else if($params_str){
				debug_log('calling URL '.$this->url.$params_str);
				curl_setopt($curl, CURLOPT_URL, $this->url.$params_str);
			}
			
			$response = curl_exec($curl);
			
			//debug_log('SENT HEADER '.curl_getinfo($curl,CURLINFO_HEADER_OUT));
			//debug_log('response '.$response);

			if($response===false)
				$this->log(curl_error($curl));
				
			$this->outputInfos = curl_getinfo($curl);
			$this->httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			curl_close($curl);
		}else{
			$this->log($this->url.$params_str);
			$fp = @fopen($this->url.$params_str,'r');
			if($fp){
				while (!feof ($fp)) {
					$buffer = fgets($fp, 64);
					$response.=$buffer;
				  }
				  fclose ($fp);
			}else{
				return false;
			}
		}

		$this->output = $response;
		return $response;
	}

	// used by curl
	function saveHeader($ch, $header) {
		$i = strpos($header, ':');
		if (!empty($i)) {
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}
		return strlen($header);
	}
	
	function getOutputHeader($key){
		return $this->http_header[$key];
	}

	function getOutput(){
		return $this->output;
	}
	
	function getOutputContentType(){
		return $this->outputInfos['content_type'];
	}
	
	function getHttpCode(){
		return $this->httpCode;
	}
}