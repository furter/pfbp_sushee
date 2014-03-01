<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/showOnlyRichText.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/mail.class.php");
require_once(dirname(__FILE__)."/../common/fields.class.php");


class MailOnlyRichTextPage extends SusheeObject{
	
	var $mail = false;
	var $ID = false;
	
	function MailOnlyRichTextPage($ID){
		$this->ID = $ID;
	}
	
	function getID(){
		return $this->ID;
	}
	
	function execute(){
		$user = new NectilUser();
		if($user->isAuthentified()){
			$mail = new Mail($this->ID);
			
			$fields_collection = new FieldsCollection();
			$fields_collection->add(new DBField('OwnerID'));
			$fields_collection->add(new DBField('Folder'));
			
			$mail->loadFields($fields_collection);
			if($mail->getField('OwnerID')==$user->getID()){
				$mailFolderPath = $mail->getField('Folder');
				$mailFolder = new Folder($mailFolderPath);
				$richtextFile = $mailFolder->getChild('mail.html');
				if($richtextFile){
					$richtext = $richtextFile->toString();
					$richtext = str_replace(array('[files_url]','[ID]'),array($GLOBALS["files_url"],$this->getID()),$richtext);
					echo $richtext;
				}
				
				return;
			}else{
				$this->log('User trying to consult emails that don\'t belong to him');
			}
		}else{
			$this->log('User not authentified and trying to consult emails');
		}
	}
}

$page = new MailOnlyRichTextPage($_GET['ID']);
$page->execute();

?>