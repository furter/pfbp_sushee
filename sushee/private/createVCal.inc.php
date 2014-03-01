<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createVCal.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/file.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');

class createVCal extends RetrieveOperation{
	
	var $queryNode;
	
	function parse(){
		$this->queryNode = $this->firstNode->getElement('QUERY');
		return true;
	}
	
	function operate(){
		$nql = new NQL();
		$nql->addCommands(
			$this->queryNode->getElements('./*')
			);
		$nql->addCommand(
			'<SEARCH>
				<PREFS domain="Calendars"/>
			</SEARCH>');
		$nql->addCommand(
			'<GET name="visitor">
				<CONTACT ID="visitor"/>
				<RETURN><INFO><EMAIL1/></INFO></RETURN>
			</GET>');
		$templateFile = new KernelFile('/sushee/templates/vcalendar.xsl');
		$vcal_str = $nql->transformToText($templateFile);
		
		$vcalFile = new TempFile();
		$vcalFile->setExtension('ics');
		
		$vcalFile->save($vcal_str);
		
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		$xml.=		'<VCALENDAR>'.$vcalFile->getPath().'</VCALENDAR>';
		$xml.='</RESULTS>';
		$this->setXML($xml);
		//$this->setXML($vcal_str);
		//$this->setXML($nql->getResponse());
		
		return true;
	}
}

?>