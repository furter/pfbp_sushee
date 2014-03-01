<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/webaccount/apis/linkedin.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../oauth_webaccount.class.php');

class Sushee_LinkedIn extends Sushee_OAuthWebAccount{
	
	//-----------------
	// OAuth API params
	//-----------------
	var $consumerKey = '6Hxk9vV0YShDVp7-9A0MFYwEhc7FcdjnUVgWnyFioXhGhjC-iivlpRAB74EUR7vp';
	var $consumerSecret = '_-0XOHjk9cccMAhfFJ5Ams83VQZyo25OFSubMOoCPo3eH2wu5UeCJ6tJ_9qpe63I';
	
	var $requestTokenURL = 'https://api.linkedin.com/uas/oauth/requestToken';
	var $accessTokenURL = 'https://api.linkedin.com/uas/oauth/accessToken';
	var $authorizeURL = 'https://api.linkedin.com/uas/oauth/authorize';
	
	var $oAuthParams = false;
	
	function getRequestToken($request_token_url=NULL,$oauth_callback = NULL) {
		// for request token, forcing to send the authorization as params
		// LinkedIn is not consistent on this point, later in the process it will need to send as authorization headers
		$this->sendOAuthAsParams();
		
		return parent::getRequestToken($request_token_url,$oauth_callback);
	  }
	
	//--------------
	// API methods
	//--------------
	function _postMessage(/* XMLNode */ $node){
		
		$text = $node->valueOf('TEXT');
		$url = $node->valueOf('URL');
		$picture = $node->valueOf('PICTURE');
		$title = $node->valueOf('TITLE');
		
		$this->setURL('http://api.linkedin.com/v1/people/~/shares');
		$this->setBody(
			'<share>
	          <comment>'.$text.'</comment>
				<content>
	          		<submitted-url>'.$url.'</submitted-url>
	          		<submitted-image-url>'.$picture.'</submitted-image-url>
	          		<title>'.$title.'</title>
				</content>
	          <visibility>
	             <code>anyone</code>
	          </visibility>
	        </share>');
		$this->addHeader('Content-Type','text/xml;charset=utf-8');
		$this->setMethod('post');
		
		$response = $this->request();
		
		return $response;
	}
	
	function _listUserMessage($node){
		
		$this->setURL('http://api.linkedin.com/v1/people/~/network/updates?scope=self');
		$this->sendOAuthAsParams(); // forcing to send OAuth identification in GET params and not in headers, because here it DOESNT work another way
		$response = $this->request();
		
		return $response;
	}
	
	function _getFeed($node){
		$this->setURL('http://api.linkedin.com/v1/people/~/network/updates');
		$response = $this->request();
		
		return $response;
	}
	
	function _searchUser($node){
		$text = $node->valueOf();
		
		$this->setURL('http://api.linkedin.com/v1/people-search');
		$this->addParam('keywords',decode_from_xml($text));
		$response = $this->request();
		
		return $response;
	}
	
	function _connectUser($node){
		
		$userAlias = $node->valueOf();
		if(!$userAlias){
			throw new Sushee_WebaccountException('No User given in the request');
		}
		
		$this->setURL('http://api.linkedin.com/v1/people/id='.$userAlias.':(api-standard-profile-request)');
		
		$response = $this->request();
		
		$xml = new XML($response);
		$auth = $xml->valueOf('/person/api-standard-profile-request/headers/http-header/value');
		$explosion = explode(':',$auth);
		
		$authkey = $explosion[1];
		
		$this->setMethod('post');
		$this->setURL('http://api.linkedin.com/v1/people/~/mailbox');
		
		$this->setBody(
			'<mailbox-item>
			  <recipients>
			    <recipient>
			      <person path="/people/id='.$userAlias.'" />
			    </recipient>
			  </recipients>
			  <subject>Invitation to Connect</subject>
			  <body>Please join my professional network on LinkedIn.</body>
			  <item-content>
			    <invitation-request>
			      <connect-type>friend</connect-type>
			      <authorization>
			        <name>NAME_SEARCH</name>
			        <value>'.$authkey.'</value>
			      </authorization>
			    </invitation-request>
			  </item-content>
			</mailbox-item>');
		$this->addHeader('Content-Type','text/xml;charset=utf-8');
		
		$response = $this->request();
		
		return $response;
	}
	
	function _getProfile($node){
		
		$this->setURL('http://api.linkedin.com/v1/people/~:(id,first-name,last-name,industry,current-share,summary,specialties,honors,interests,positions,languages,skills,educations,phone-numbers,date-of-birth,main-address,picture-url,public-profile-url)');
		$this->addHeader('Content-Type','text/xml;charset=utf-8');
		
		$response = $this->request();
		
		return $response;
	}
	
	function _getFriends($node){
		
		$this->setURL('http://api.linkedin.com/v1/people/~/connections');
		
		$response = $this->request();
		
		return $response;
	}
}


?>