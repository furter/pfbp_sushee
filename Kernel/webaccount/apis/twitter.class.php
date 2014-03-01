<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/webaccount/apis/twitter.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../oauth_webaccount.class.php');

class Sushee_Twitter extends Sushee_OAuthWebAccount{
	
	//-----------------
	// OAuth API params
	//-----------------
	var $consumerKey = 'q7Llo0Qvf2wXyGTiZUEvSA';
	var $consumerSecret = 'org5nLgFFGoE2p9GGWCAnKTAXeUWkoEvy5MAQoZqRc';
	
	var $requestTokenURL = 'https://api.twitter.com/oauth/request_token';
	var $accessTokenURL = 'https://api.twitter.com/oauth/access_token';
	var $authorizeURL = 'https://api.twitter.com/oauth/authorize';
	
	//--------------
	// API methods
	//--------------
	function _postMessage(/* XMLNode */ $node){
		
		$text = UnicodeEntities_To_utf8($node->valueOf('TEXT'));
		
		$this->setURL('https://api.twitter.com/1/statuses/update.xml');
		$this->setMethod('post');
		$this->addParam('status',decode_from_xml($text));
		
		$response = $this->request();
			
		return $response;
	}
	
	function _listUserMessage($node){
		
		$this->setURL('https://api.twitter.com/1/statuses/user_timeline.xml');
		
		$response = $this->request();
		
		return $this->twitterifyResponse($response);
	}
	
	function _getFeed($node){
		
		$this->setURL('https://api.twitter.com/1/statuses/home_timeline.xml');
		
		$response = $this->request();
		
		return $this->twitterifyResponse($response);
	}
	
	function _searchUser($node){
		$shell = new Sushee_Shell();
		$shell->enableEntities(false);
		
		$text = $node->valueOf();
		
		$this->setURL('https://api.twitter.com/1/users/search.xml');
		$this->addParam('q',decode_from_xml($text));
		
		$response = $this->request();
			
		return $this->twitterifyResponse($response);
	}
	
	function _connectUser($node){
		
		$userAlias = $node->valueOf();
		if(!$userAlias){
			throw new Sushee_WebaccountException('No User Alias given in the request');
		}
		
		$this->setURL('https://api.twitter.com/1/friendships/create.xml');
		$this->setMethod('post');
		$this->addParam('screen_name',decode_from_xml($userAlias));
		
		$response = $this->request();
			
		return $this->twitterifyResponse($response);
	}
	
	function _unconnectUser($node){
		
		$userAlias = $node->valueOf();
		if(!$userAlias){
			throw new Sushee_WebaccountException('No User Alias given in the request');
		}
		
		$this->setURL('https://api.twitter.com/1/friendships/destroy.xml');
		$this->setMethod('post');
		$this->addParam('screen_name',decode_from_xml($userAlias));
		
		$response = $this->request();
			
		return $this->twitterifyResponse($response);
	}
	
	function _getProfile($node){
		
		$this->setURL('https://api.twitter.com/1/account/verify_credentials.xml');
		
		$response = $this->request();
			
		return $this->twitterifyResponse($response);
	}
	
	function _getFriends($node){
		
		$this->setURL('https://api.twitter.com/1/friends/ids.xml');
		
		$response = $this->request();
		
		$xml = new XML($response);
		
		$ids = $xml->implode(',','/ids/id');
		
		$this->setURL('https://api.twitter.com/1/users/lookup.xml');
		$this->addParam('user_id',$ids);
		
		$response = $this->request();
		
		return $this->twitterifyResponse($response);
	}
	
	function _getFollowers($node){
		
		$this->setURL('https://api.twitter.com/1/followers/ids.xml');
		
		$response = $this->request();
		
		$xml = new XML($response);
		
		$ids = $xml->implode(',','/ids/id');
		
		$this->setURL('https://api.twitter.com/1/users/lookup.xml');
		$this->addParam('user_id',$ids);
		
		$response = $this->request();
		
		return $this->twitterifyResponse($response);
	}
	
	function twitterifyResponse($response){
		$xml = new XML($response);
		
		// transforming keywords, users in links
		$texts = $xml->getElements('//text');
	    foreach ($texts as $t)
	    {
	    	$tmp = $this->twitterifyText($t->valueOf('.'));
	    	$t->replaceValue($tmp);
	    }
		
		return $xml->toString();
	}
	
	function twitterifyText($ret) 
	{
		$ret = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a class=\"navigable\" href=\"\\2\">\\2</a>", $ret);
		$ret = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a class=\"navigable\" href=\"http://\\2\">\\2</a>", $ret);
		$ret = preg_replace("/@(\w+)/", "<a class=\"navigable\" href=\"http://twitter.com/\\1\">@\\1</a>", $ret);
		$ret = preg_replace("/#(\w+)/", "<a class=\"navigable\" href=\"http://search.twitter.com/search?q=#\\1\">#\\1</a>", $ret);
		return $ret;
	}
}


?>