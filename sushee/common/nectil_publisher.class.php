<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/nectil_publisher.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__).'/../common/nql.class.php');

class NectilPublisher extends SusheeObject{
	
	var $ns;
	
	function setNamespace($ns){
		$this->ns = $ns;
	}
	
	function getNamespace(){
		return $this->ns;
	}
	
	function authenticate($login,$password){
		$nql = new NQL(false);
		$nql->addCommand(
			'<GET>
				<WEBSERVICE url="http://nectil.com/'.Sushee_dirname.'/private/request.php" method="get">
					<PARAMS>
						<PARAM name="NQL">
							<QUERY>
								<SEARCH>
									<NECTIL:PUBLISHER>
										<INFO>
											<LOGIN operator="=">'.$login.'</LOGIN>
											<PASSWORD operator="encrypt">'.$password.'</PASSWORD>
										</INFO>
									</NECTIL:PUBLISHER>
									<RETURN>
										<INFO>
											<URI/>
											<NAMESPACE/>
										</INFO>
									</RETURN>
								</SEARCH>
							</QUERY>
						</PARAM>
					</PARAMS>
				</WEBSERVICE>
			</GET>');
		$nql->execute();
		$publisherDataNode = $nql->getElement('/RESPONSE/RESULTS/WEBSERVICE/RESPONSE/RESULTS/NECTIL:PUBLISHER');
		if(!$publisherDataNode){
			return false;
		}
		$publisherURI = $publisherDataNode->valueOf('INFO/URI');
		$publisherNamespace = $publisherDataNode->valueOf('INFO/NAMESPACE');
		
		$namespace = new SusheeNamespace($publisherNamespace,$publisherURI);
		$this->setNameSpace($namespace);
		
		return true;
	}
}

?>