<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/webaccount/apis/api_example.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../oauth_webaccount.class.php');

class MyAPI extends Sushee_OAuthWebAccount{
	
	/* Skeleton of an API implementation :
	The following methods are the methods we advise you to implement.
	You can of course define new ones and give them any name you want.
	<CALL><WEBACCOUNT> will call the method named after the first node inside <WEBACCOUNT>, preceded by an underscore.
	*/
	
	//-----------------
	// OAuth API params
	//-----------------
	var $consumerKey = '';
	var $consumerSecret = '';
	
	var $requestTokenURL = '';
	var $accessTokenURL = '';
	var $authorizeURL = '';
	
	//--------------
	// API methods
	//--------------
	function _getProfile($node){
		/* implementation of 
			<CALL> 
				<WEBACCOUNT> 
					<GETPROFILE/>
				</WEBACCOUNT>
			</CALL>
		*/
	}
	
	function _getFeed($node){
		/* implementation of 
			<CALL> 
				<WEBACCOUNT> 
					<GETFEED/>
				</WEBACCOUNT>
			</CALL>
		*/
	}
	
	function _getFriends($node){
		/* implementation of 
			<CALL> 
				<WEBACCOUNT> 
					<GETFRIENDS/>
				</WEBACCOUNT>
			</CALL>
		*/
	}
	
	function _postMessage($node){
		/* implementation of 
			<CALL> 
				<WEBACCOUNT> 
					<POSTMESSAGE>
						<TEXT>...</TEXT>
					</POSTMESSAGE>
				</WEBACCOUNT>
			</CALL>
		*/
	}
	
	function _connectUser($node){
		/* implementation of 
			<CALL> 
				<WEBACCOUNT> 
					<CONNECTUSER>...</CONNECTUSER>
				</WEBACCOUNT>
			</CALL>
		*/
	}
	function _searchUser($node){
		/* implementation of 
			<CALL> 
				<WEBACCOUNT> 
					<SEARCHUSER>...</SEARCHUSER>
				</WEBACCOUNT>
			</CALL>
		*/
	}
	
	
	function _myMethod($node){
		/* implementation of 
			<CALL> 
				<WEBACCOUNT> 
					<MYMETHOD>...</MYMETHOD>
				</WEBACCOUNT>
			</CALL>
		*/
	}
	
}