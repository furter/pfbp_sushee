<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/webaccount/oauth_webaccount.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/* Load OAuth lib. You can find it at http://oauth.net */
require_once(dirname(__FILE__).'/oauth_utils.class.php');
require_once(dirname(__FILE__).'/webaccount.class.php');
require_once(dirname(__FILE__).'/../common/url.class.php');
require_once(dirname(__FILE__).'/../common/exception.class.php');

class Sushee_OAuthWebAccount extends Sushee_WebAccount{
	
	var $oAuthParams = false; // we send oAuth as headers when GET method. You can force using GET parameters to send oauth using sendOAuthAsParams()
	var $consumerKey;
	var $consumerSecret;
	var $requestTokenURL;
	var $accessTokenURL;
	var $authorizeURL;
	var $api;
	
	function Sushee_OAuthWebAccount($values){
		parent::Sushee_WebAccount($values);
		
		// loading API, necessary to assing $this->api and use it later : authorization wont work without it
		$this->getWebAPI();
		
		// In state waiting, token in database are empty or deprecated
		if($this->getField('Authorization_State') != 'waiting'){
			$oauth_token = $this->getField('Token');
			$oauth_token_secret = $this->getField('Token_Secret');
		}
		
		
		// init
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
	    $this->consumer = new OAuthConsumer($this->consumerKey, $this->consumerSecret);
	
	    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
	      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
	    } else {
	      $this->token = NULL;
	    }
	
	}
	
	function authorize(){
		// edition : will redirect to the service authorization page if in edition state (--> passing to requested state)
		// registered : will ask for permanent token if in registered state (-->passing to authorized state)
		// will skip if already authorized
		
		// account properties 
		$this->loadFields();
		$current_state = $this->getField('Authorization_State');
		
		// already authorized
		if($current_state == 'authorized'){
			;
		}else if($current_state == 'requested' && $_GET['oauth_token']){
			$this->setField('Authorization_State','registered');
			$access_token = $this->getAccessToken($this->accessTokenURL,$_REQUEST['oauth_verifier']);
			
			if(200 == $this->httpCode){
				// saving permanent access tokens in database webaccount
				$this->setField('Token',$access_token["oauth_token"]);
			    $this->setField('Token_Secret',$access_token["oauth_token_secret"]);
			    $this->setField('Authorization_State','authorized');
				$this->update();
			}else{
				$this->setError('Could not get permanent access tokens');
				return false;
			}
		}else{
			// not authenticated yet, getting the initializing tokens and redirecting to the Twitter authentication page
			$this_url = $GLOBALS['SusheeProtocol'].$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$this->token = NULL; // reinitializing tokens, they might have been asked before but are not valid anymore and should not sign the current request
			$request_token = $this->getRequestToken($this->requestTokenURL,$this_url);
			if(!$request_token["oauth_token"]){
				$this->setError('Could not get token to start transaction with the service');
				errors_log('Could not get token to start transaction with the service : '.$this->getResponse());
				return false;
			}
			
			$this->setField('Token',$request_token["oauth_token"]);
		    $this->setField('Token_Secret',$request_token["oauth_token_secret"]);
			$this->setField('Authorization_State','requested');
			$this->update();
			$authorizeCompleteURL = $this->authorizeURL.'?oauth_token='.$request_token["oauth_token"].'&oauth_callback='.urlencode($this_url);
			debug_log($authorizeCompleteURL);
			//redirect($authorizeCompleteURL);
			header ('Location: '.$authorizeCompleteURL);
			header ('Expires: 0');
			header("Connection: close");
			exit();
		}
		
		return true;
	}
	
	function getSignableParams(){
		// beacause signing may differ of what we are really sending through curl (cf DropBox)
		$signable_parameters = array();
		foreach($this->params as $key=>$value){
			$signable_parameters[$key] = $value;
		}
		foreach($this->files as $key=>$path){
			// but having a file in the signature is not possible, we only sign the filename
			$file = new File($path);
			if($file->exists()){
				$signable_parameters[$key] = $file->getName();
			}else{
				throw new SusheeException('File `'.$path.'` doesnt exist');
			}
		}
		
		return $signable_parameters;
	}
	
	function sendOAuthAsParams(){
		$this->oAuthParams = true;
	}
	
	function request(){
		
		$url = $this->getURL();
		$method = $this->getMethod();
		$params = $this->getParams();
		$files = $this->getFiles();
		$headers = $this->getHeaders();
		$body = $this->getBody();
		
		debug_log('OAuth signed '.$method.' request '.get_class($this));
		
		// Body is not url form encoded but a file or a XML body
		if($body){
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url);
			$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		}else{
			// Array of parameters
			$signable_parameters = $this->getSignableParams();
			
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $signable_parameters);
			$request->sign_request($this->sha1_method, $this->consumer, $this->token);
			
			//$body = $request->to_postdata();
		}
		
		$url = $request->get_normalized_http_url();
		
	    if( ($method == 'get' || $body) && !$this->oAuthParams){
			debug_log('Sending OAuth authorization as http headers');
			//adding the auth parameters in headers
			// if there is a body cause we cannot send them in post, as post body is a XML string
			$auth_header = $request->to_header();
			// receiving it as a string, we need to re-separe it
			$explosion = explode(':',$auth_header);
			$headers[$explosion[0]] = $explosion[1];
		}else{
			debug_log('Sending OAuth headers as post parameters');
			// adding oAuth params in post params
			$params = $request->get_parameters();
		}
		
		
		// calling the generic sushee URL Caller with the headers and parameters completed with oAuth
		$urlHandler = new URL($url);
		$urlHandler->setMethod($method);
		
		foreach($headers as $key=>$value){
			$urlHandler->addHeader($key,$value);
		}
		
		foreach($params as $key=>$value){
			if($key != 'oauth_verifier')
				$urlHandler->addParam($key,$value);
		}
		
		foreach($files as $key=>$path){
			$urlHandler->addFile($key,$path);
		}
		
		if($body){
			$urlHandler->setBody($body);
		}
		
		$response = $urlHandler->execute();
		$this->httpCode = $urlHandler->getHttpCode();
		$this->contentType = $urlHandler->getOutputContentType();
		
		return $response;
	}
	
	function getRequestToken($request_token_url=NULL,$oauth_callback = NULL) {
		$this->setURL($request_token_url);
		$this->setMethod('get');
		$this->sendOAuthAsParams();
		
		if (!empty($oauth_callback)) {
			$this->addParam('oauth_callback',$oauth_callback);
		}
	    $response = $this->request();
		debug_log('response '.$response);
	    $token = OAuthUtil::parse_parameters($response);
	    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
	    return $token;
	  }
	
	function getAccessToken($access_token_url,$oauth_verifier = FALSE) {
		$this->setURL($access_token_url);
		$this->setMethod('GET');
		if (!empty($oauth_verifier)) {
			$this->addParam('oauth_verifier',$oauth_verifier);
		}
		$response = $this->request();
		debug_log('response '.$response);
	    $token = OAuthUtil::parse_parameters($response);
	    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
	    return $token;
	  }
}
