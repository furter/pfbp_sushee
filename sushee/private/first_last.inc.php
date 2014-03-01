<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/first_last.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
	require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
	require_once(dirname(__FILE__).'/../common/nql.class.php');
	
	class sushee_first_Last_element extends RetrieveOperation
	{
		public function execute()
		{
			$operationNode = $this->operationNode;
			
			if($operationNode->nodeName()=='FIRST'){
				$order = 'ascending';
			}else{
				$order = 'descending';
			}
			
			$root = $operationNode->getElement('.');
			$root->removeChild('SORT');
			$root->removeChild('PAGINATE');
			$shell = new Sushee_Shell(false);
			$xsushee = '
				<SEARCH '.$this->getOperationAttributes().'>
					'.$root->copyOf('./*').'
					<SORT select="INFO/ID" order="'.$order.'" />
					<PAGINATE display="1" page="1" />
				</SEARCH>
			';
			$shell->addCommand($xsushee);
			return $shell->copyOf('/RESPONSE/RESULTS');
		}
	}