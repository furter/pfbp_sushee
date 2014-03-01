<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/webaccount/apis/dropbox.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../oauth_webaccount.class.php');

class Sushee_DropBox extends Sushee_OAuthWebAccount
{
	//-----------------
	// OAuth API params
	//-----------------

	var $consumerKey = '2k14gw0jn0nn33c';
	var $consumerSecret = '4qll9r0oyszl6x7';
	var $root = 'dropbox'; // 'sandbox'

	var $requestTokenURL = 'https://www.dropbox.com/1/oauth/request_token';
	var $accessTokenURL = 'https://www.dropbox.com/1/oauth/access_token';
	var $authorizeURL = 'https://www.dropbox.com/1/oauth/authorize';

	//--------------
	// API endpoints
	//--------------

	const API_URL     = 'https://api.dropbox.com/1/';
	const CONTENT_URL = 'https://api-content.dropbox.com/1/';

	//--------------
	// API methods
	//--------------

	function _getProfile($node)
	{
		$this->setURL(self::API_URL . 'account/info');

		$response = $this->request();

		$xml = $this->json2xml($response);
		return $xml;
	}
	
	function _listFiles($node)
	{
		$path = 'metadata/' . $this->root  . '/' . $this->encodePath($node->valueOf('PATH'));

		$this->setURL( self::API_URL . $path);

		$response = $this->request();

		$xml = $this->json2xml($response);
		return $xml;
	}
	
	function _downloadFile($node)
	{
		$path = $node->valueOf('PATH');
		$target = $node->valueOf('TARGET');
		
		if(!$path){
			throw new Sushee_WebAccountException('Missing argument PATH or TARGET');
		}
		if(!$target){
			$target = '/tmp/';
			$lastSlash = strrpos($path,'/');
			if($lastSlash !== false){
				$name = substr($path,$lastSlash+1);
			}else{
				$name = 'noname';
			}
			$target.=$name;
		}
		
		// calling dropbox
		if(substr($path,0,1)=='/'){
			$path = substr($path,1);
		}
		
		$this->setURL('https://api-content.dropbox.com/0/files/dropbox/'.$path.'');
		
		$response = $this->request();
		
		$file = new File($target);
		
		$file->save($response);
		if(!$file->exists()){
			throw new Sushee_WebAccountException('File `'.$target.'` could not be saved');
		}
		
		return $file->getXML();
	}
	
	function _uploadFile($node){
		$path = $node->valueOf('PATH');
		$target = $node->valueOf('TARGET');
		
		$file = new File($path);
		
		if(substr($target,0,1)=='/'){
			$target = substr($target,1);
		}
		
		$this->setMethod('post');
		$this->setURL('http://api-content.dropbox.com/0/files/dropbox/'.$target);
		$this->addFile('file',$file->getPath());
		
		$response = $this->request();
		
		$xml = $this->json2xml($response);
		return $xml;
	}
	
	function _getFileInfo($node){
		if($node->valueOf('PATH') == '/'){
			$str = '';
		}else{
			$xml = new XML($this->_listFiles($node));

			// standard notation for handling by the generic File panel
			$path = $xml->valueOf('/root/path');
			$str = '<FILE>'
						.'<INFO>'
							.'<NAME>'.substr($path,strrpos($path,'/')+1).'</NAME>'
							.'<PATH>'.$path.'</PATH>'
							.'<SIZE>'.$xml->valueOf('/root/size').'</SIZE>'
						.'</INFO>'
					.'</FILE>';
		}

		return $str;
	}
	
	function _saveFile($node){
		return $this->_downloadFile($node);
	}

	/**
	 * Trim the path of forward slashes and replace
	 * consecutive forward slashes with a single slash
	 * @param string $path The path to normalise
	 * @return string
	 */
	private function normalisePath($path)
	{
		$path = preg_replace('#/+#', '/', trim($path, '/'));
		return $path;
	}
	
	/**
	 * Encode the path, then replace encoded slashes
	 * with literal forward slash characters
	 * @param string $path The path to encode
	 * @return string
	 */
	private function encodePath($path)
	{
		$path = $this->normalisePath($path);
		$path = str_replace('%2F', '/', rawurlencode($path));
		return $path;
	}	
}