<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/showRichText.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/nql.class.php");


class MailRichTextWHeadersPage extends SusheeObject{
	
	var $mail = false;
	var $ID = false;
	
	function MailRichTextWHeadersPage($ID){
		$this->ID = $ID;
	}
	
	function execute(){
		$user = new NectilUser();
		if($user->isAuthentified()){
			$nql = new NQL(false);
			$nql->addCommand(
				'<SEARCH name="mail-infos">
					<MAIL>
						<INFO>
							<ID>'.$this->ID.'</ID>
							<OWNERID>'.$user->getID().'</OWNERID>
						</INFO>
					</MAIL>
					<RETURN>
						<INFO>
							<TO/>
							<CC/>
							<FROM/>
							<RECEIVINGDATE/>
							<SUBJECT/>
							<PLAINTEXT/>
						</INFO>
					</RETURN>
				</SEARCH>');
			$nql->execute();
			echo $nql->transform(dirname(__FILE__).'/../templates/showRichText.xsl');
		}else{
			$this->log('User not authentified and trying to consult emails');
		}
	}
}

$page = new MailRichTextWHeadersPage($_GET['ID']);
$page->execute();

?>

