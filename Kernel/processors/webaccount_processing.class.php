<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/processors/webaccount_processing.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

class sushee_UPDATE_WEBACCOUNT_processor{
	
	function preprocess($data){
		
		// when revoking a webaccount, we need to erase the tokens and tokens secret, or the account would still be usable (if not revoked by the provider)
		if($data->getValue('AUTHORIZATION_STATE') == 'revoked' ){
			$data->setValue('TOKEN','');
			$data->setValue('TOKEN_SECRET','');
		}
		
		return true;
	}
	
	function postprocess($data){
		return true;
	}
	
}
