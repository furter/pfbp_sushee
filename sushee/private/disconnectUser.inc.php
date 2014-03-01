<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/disconnectUser.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/date.class.php');
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nectil_user.class.php');

class Sushee_disconnectUser extends NQLOperation{
	
	function parse(){
		if($this->firstNode->valueOf('@ID')){
			return true;
		}
		return false;
	}
	
	function operate(){
		$session_path = session_save_path();
		$recent_date = new Date();
		$recent_date->addDay(-7);
		$userID = $this->firstNode->valueOf('@ID');
		$sql = 'SELECT `SessionID` FROM `logins` WHERE `LastAction` > "'.$recent_date->toString().'" AND `UserID` = \''.$userID.'\'';
		$db_conn = db_connect();
		sql_log($sql);
		$rs = $db_conn->execute($sql);
		if($rs){
			while($row = $rs->fetchRow()){
				$sessionID = $row['SessionID'];
				$session_file_path = $session_path.'sess_'.$sessionID;
				if(file_exists($session_file_path)){
					$sessions[]=$sessionID;
					unlink($session_file_path);
					if(file_exists($session_file_path)){
						$this->setError('Could not delete session file '.$session_file_path);
						return false;
					}
				}
			}
		}
		if($userID == Sushee_User::getID()){
			logout();
		}
		$this->setSuccess('User `'.$userID.'` disconnected (sessions:'.implode(', ',$sessions).' in `'.$session_path.'`)');
		return true;
	}
	
}


?>