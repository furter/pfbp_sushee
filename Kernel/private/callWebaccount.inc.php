<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/callWebaccount.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/classcaller.class.php');
require_once(dirname(__FILE__).'/../webaccount/webaccount.class.php');

/*

<CALL>
   <WEBACCOUNT [ID="...,...,..."]>
      <actionName></actionName> 
   </WEBACCOUNT>
</CALL>

actionName is the name of the method called in the class managing the webAPI

*/

class Sushee_callWebAccount extends RetrieveOperation
{
	function operate()
	{
		$actionNode = $this->firstNode->getElement('/*[1]');
		if(!$actionNode)
		{
			$this->setError('No action defined in the request');
			return false;
		}
		$actionName = $actionNode->nodename();
		
		$shell = new Sushee_Shell();
		
		if($this->firstNode->valueOf('@ID'))
		{
			$crits = ' ID="'.$this->firstNode->valueOf('@ID').'"';
		}
		
		if($this->firstNode->valueOf('@api'))
		{
			$crits.= ' api="'.$this->firstNode->valueOf('@api').'"';
		}
		
		if(Sushee_Request::isSecured())
		{
			$ownerCrits = '
				<INFO>
					<OWNERID>visitor</OWNERID>
				</INFO>
				<INFO>
					<OWNERID op="!=">visitor</OWNERID>
					<PUBLIC>W</PUBLIC>
				</INFO>';
		}
		else
		{
			$ownerCrits = '';
		}
		
		// getting the webaccounts
		$shell->addCommand(
			'<SEARCH>
				<WEBACCOUNT'.$crits.'>
					'.$ownerCrits.'
				</WEBACCOUNT>
				<RETURN>
					<NOTHING/>
				</RETURN>
			</SEARCH>');

		$shell->execute();

		$accountNodes = $shell->getElements('/RESPONSE/RESULTS/WEBACCOUNT');

		$xml = '<RESULTS'.$this->getOperationAttributes().'>';
		foreach($accountNodes as $node)
		{
			$account = new Sushee_WebAccount($node->valueOf('@ID'));

			// getting the API
			$api = $account->getWebAPI();
			if(!$api)
			{
				$xml.='<WEBACCOUNT ID="'.$account->getID().'">';
				$xml.= 	$this->generateErrorXML('Unknown API in WEBAPI list');
			}
			else
			{
				$xml.='<WEBACCOUNT ID="'.$account->getID().'" api="'.$api->getField('Denomination').'">';
				
				if($account->isAuthorized())
				{
					// a classfile and a classname should be defined in the webAPI to handle the requests to the webaccounts of this API
					$webapiClassFile = $api->getField('ClassFile');
					$webapiClassName = $api->getField('ClassName');

					try
					{
						// calling the method of the class asked by the user (first node inside <CALL><WEBACCOUNT>)
						$caller = new sushee_PHPClassCaller($webapiClassFile,$webapiClassName,'_'.$actionName,$actionNode); // using underscore before at the beginning of the method to prevent API methods to override generic webaccount methods

						// giving the ID of the webaccount to the constructor
						$caller->setConstructorData($account->getID());

						// calling the method '$actionNode'
						$res =  $caller->execute();

						// Call failed : wrong configuration of the webapi, method missing, etc
						if(!$res)
						{
							$xml.= $this->generateErrorXML($caller->getError());
						}

						$response = $caller->getResponse();

						// ensuring that output is valid to include in final XML
						// if its XML, we have to cut the header
						$responseXML = new XML($response);
						if($responseXML->loaded)
						{
							// second argument is the XML header : we force it to be empty
							$response = $responseXML->toString('/','');
						}
						else
						{
							$response = encode_to_xml($response);
						}
						
						$xml.= $response;
					}
					catch(Exception $e)
					{
						$xml.= $this->generateErrorXML($e->getMessage(),$e->getCode());
					}
				}
				else
				{
					if($account->getValue('Authorization_State') == 'disabled')
					{
						$xml.= $this->generateErrorXML('WEBACCOUNT temporarily disabled. Re-enable it to use it.');
					}
					else
					{
						$xml.= $this->generateErrorXML('WEBACCOUNT not yet authorized by WEBAPI provider. Use <AUTHORIZE><WEBACCOUNT ID="..."/></AUTHORIZE> to launch the authorization procedure.');
					}
				}
			}
			$xml.='</WEBACCOUNT>';
		}
		$xml.='</RESULTS>';

		$this->setXML($xml);
		return true;
	}
	
	function generateErrorXML($msg,$error_code = 0)
	{
		$attributes = $this->getOperationAttributes();
		return "<MESSAGE".$attributes." msgType=\"1\" errorCode=\"$error_code\">".encode_to_xml($msg)."</MESSAGE>";
	}
}