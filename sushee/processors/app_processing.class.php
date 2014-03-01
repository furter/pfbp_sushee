<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/app_processing.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

class sushee_CREATE_APP_processor{
	
	function preprocess(){
		return true;
	}
	
	function postprocess($data){
		$shell = new Sushee_Shell();
		
		$user = new Sushee_User();
		$keyring = $user->getKeyring();
		if($keyring){
			$keyringID = $keyring->getID();
		}else{
			$keyringID = 2; // admin keyring
		}
		
		$shell->addCommand(
			'<CREATE disable-processors="true">
				<APPLICATIONKEY>
					<INFO>
						<APPLICATIONTOID>'.$data->getID().'</APPLICATIONTOID>
						<DENOMINATION>Access to '.$data->getValue('DENOMINATION').'</DENOMINATION>
					</INFO>
					<DEPENDENCIES>
						<DEPENDENCY type="keyringAppKeys" mode="reverse">
							<KEYRING ID="'.$keyringID.'"/>
						</DEPENDENCY>
					</DEPENDENCIES>
				</APPLICATIONKEY>
			</CREATE>');
		
		$shell->execute();
		return true;
	}
	
}

class sushee_DELETE_APP_processor{
	function preprocess($data){
		return true;
	}
	
	function postprocess($data){
		$shell = new Sushee_Shell();
		$shell->addCommand(
			'<KILL disable-processors="true"><!-- to avoid processors that would do the same job ? -->
				<APPLICATIONKEY>
					<WHERE>
						<INFO>
							<APPLICATIONTOID>'.$data->getID().'</APPLICATIONTOID>
						</INFO>
					</WHERE>
				</APPLICATIONKEY>
			</KILL>');
		
		$shell->execute();
		return true;
	}
	
}

class sushee_KILL_APP_processor extends sushee_DELETE_APP_processor{}


?>