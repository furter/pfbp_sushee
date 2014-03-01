<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/authorizeWebaccount.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../webaccount/webaccount.class.php');
require_once(dirname(__FILE__).'/../common/classcaller.class.php');

/*

<AUTHORIZE>
   <WEBACCOUNT ID="..."/>
</AUTHORIZE>

*/

class Sushee_authorizeWebAccount extends NQLOperation
{
	function parse()
	{
		$ID = $this->firstNode->valueOf('@ID');
		if(!$ID)
		{
			$this->setError('No ID given in the request');
			return false;
		}
		
		$account = new Sushee_WebAccount($ID);
		
		if(!$account->exists())
		{
			$this->setError('Account(ID:`'.$ID.'`) doesn\'t exist');
			return false;
		}

		$this->setElementID($ID);
		return true;
	}

	function operate()
	{
		$account = new Sushee_WebAccount($this->getElementID());
		$api = $account->getWebAPI();
		if(!$api)
		{
			$this->setError('Unknown API in WEBAPI list');
			return false;
		}
		else
		{
			// a classfile and a classname should be defined in the webAPI to handle the requests to the webaccounts of this API
			$webapiClassFile = $api->getField('ClassFile');
			$webapiClassName = $api->getField('ClassName');

			try
			{
				// calling the method authorize
				// using underscore before at the beginning of the method to prevent API methods to override generic webaccount methods
				$caller = new sushee_PHPClassCaller($webapiClassFile,$webapiClassName,'authorize');

				// giving the ID of the webaccount to the constructor
				$caller->setConstructorData($account->getID());

				// calling the method in the class provided by the webAPI
				$res =  $caller->execute();

				// Call failed : wrong configuration of the webapi, method missing, etc
				if(!$res)
				{
					$this->setError($caller->getError());
					return false;
				}
				$response = $caller->getResponse();
				
				// execution of the authorize method has changed the authorization state
				$account->reloadFields();
				if($account->isAuthorized())
				{
					$this->setSuccess('Account authorized');
					return true;
				}
				else
				{
					$this->setError($response);
					return false;
				}
			}
			catch(Exception $e)
			{
				$this->setError($e->getMessage(),$e->getCode());
				return false;
			}
		}
	}
}