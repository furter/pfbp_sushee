<?php

if ( $requestName == "CREATE" || $requestName == "UPDATE" )
{
	Sushee_Session::clearVariable('MAILACCOUNT-'.$IDs_array[0]);

	if( isset($values['Password']) && $values['Password'] != '')
	{
		if($requestName == "UPDATE")
		{
			$pass_sql = 'SELECT `Password` FROM `'.$moduleInfo->tableName.'` WHERE ID = \''.$IDs_array[0].'\';';
			$former_pass_row = $db_conn->GetRow($pass_sql);
			$former_password = $former_pass_row['Password'];
		}

		// New password, we must choose the best way to encode it
		require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
		$user = new NectilUser();

		if( ($requestName == "CREATE") || ($requestName == "UPDATE" &&  $values['Password'] != $former_password) && $user->getSessionPassword())
		{
			include_once(dirname(__FILE__)."/../common/crypt.class.php");

			if(!isset($values['Encryption']))
			{
				$values['Encryption'] = 'BLOWFISH';
			}

			$crypt = new Crypt();
			$crypt->setAlgo($values['Encryption']);

			$crypt->setKey( $user->getSessionPassword() );
			$values['Password'] = $crypt->execute($values['Password']);
		}
	}
}

return true;