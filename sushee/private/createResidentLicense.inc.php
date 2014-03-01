<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createResidentLicense.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');

class createResidentLicense extends NQLOperation{
	
	var $values = array();
	var $masterID = false;
	var $ownerInfo = false;
	var $dealerID = false;
	var $licenseID = false;
	
	function parse(){
		$infos_nodes = $this->firstNode->getElements('INFO/*');
		foreach($infos_nodes as $node){
			$nodename = $node->nodeName();
			switch($nodename){
				case 'DENOMINATION':
				case 'PASSWORD':
				case 'PRODUCT':
				case 'STARTUPDATE':
				case 'RENEWALDATE':
				case 'EXPIRATIONDATE':
				case 'URL':
				case 'DBNAME':
				case 'PUBLISHED':
					$this->values[$nodename] = $node->valueOf();
					break;
				default:
			}
		}
		
		$master = $this->firstNode->getElement("DEPENDENCIES/DEPENDENCY[@type='Masters']/LICENSE");
		if(!$master){
			$this->setError('Officity Server is not indicated');
			return false;
		}
		$masterInstallLoginMD5 = $master->valueOf('INFO/DENOMINATION');
		$masterInstallPasswordMD5 = $master->valueOf('INFO/PASSWORD');
		
		if(!$masterInstallLoginMD5){
			$this->setError('Officity Server not identified by its denomination MD5 Digest');
			return false;
		}
		if(!$masterInstallPasswordMD5){
			$this->setError('Officity Server not identified by its password MD5 Digest');
			return false;
		}
		
		$nql = new NQL(false);
		$nql->addCommand(
			'<SEARCH>
				<LICENSE>
					<INFO>
						<DENOMINATION operator="md5">'.encode_to_xml($masterInstallLoginMD5).'</DENOMINATION>
						<PASSWORD operator="md5">'.encode_to_xml($masterInstallPasswordMD5).'</PASSWORD>
					</INFO>
				</LICENSE>
				<RETURN>
					<DEPENDENCIES>
						<DEPENDENCY type="OwnerShip">
							<CONTACT>
								<NOTHING/>
							</CONTACT>
						</DEPENDENCY>
						<DEPENDENCY type="Dealership">
							<CONTACT>
								<NOTHING/>
							</CONTACT>
						</DEPENDENCY>
					</DEPENDENCIES>
				</RETURN>
			</SEARCH>');

		$nql->execute();
		$masterLicense = $nql->getElement('/RESPONSE/RESULTS/LICENSE');
		if(!$masterLicense){
			$this->setError('Officity Server not registered or not recognized');
			return false;
		}
		$this->masterID = $masterLicense->valueOf('@ID');
		
		$nql->reset();
		$nql->addCommand(
			'<SEARCH>
				<LICENSE>
					<INFO>
						<DENOMINATION>'.$this->values['DENOMINATION'].'</DENOMINATION>
					</INFO>
					<DEPENDENCIES>
						<DEPENDENCY type="Masters" mode="reverse">
							<LICENSE ID="'.$this->masterID.'"/>
						</DEPENDENCY>
					</DEPENDENCIES>
				</LICENSE>
			</SEARCH>');
		$nql->execute();
		$licenseID  = $nql->valueOf('/RESPONSE/RESULTS/LICENSE/@ID');
		if($licenseID){
			$this->licenseID = $licenseID;
		}
		
		$dealer = $masterLicense->getElement("DEPENDENCIES/DEPENDENCY[@type='Ownership']/CONTACT");
		if($dealer){
			$this->dealerID = $dealer->valueOf('@ID');
		}
		
		$owner = $this->firstNode->getElement("DEPENDENCIES/DEPENDENCY[@type='Ownership']/CONTACT");
		if($owner){
			$ownerInfo = $owner->getElement('INFO');
			if($ownerInfo){
				$this->ownerInfo = $ownerInfo;
			}
		}
		
		
		
		return true;
	}
	
	function operate(){
		$nql = new NQL(false);
		if($this->ownerInfo){
			
			$ownerDepNQL = 
				'<DEPENDENCY type="Ownership">
					<CONTACT if-exists="skip">
						'.$this->ownerInfo->toString().'
					</CONTACT>
				</DEPENDENCY>';
		}
		if($this->dealerID){
			$dealerDepNQL = 
				'<DEPENDENCY type="Dealership">
					<CONTACT ID="'.$this->dealerID.'"/>
				</DEPENDENCY>';
		}
		$datas =
			'<INFO>
				<PRODUCT>'.encode_to_xml($this->values['PRODUCT']).'</PRODUCT>
				<DENOMINATION>'.encode_to_xml($this->values['DENOMINATION']).'</DENOMINATION>
				<PASSWORD>'.encode_to_xml($this->values['PASSWORD']).'</PASSWORD>
				<STARTUPDATE>'.encode_to_xml($this->values['STARTUPDATE']).'</STARTUPDATE>
				<RENEWALDATE>'.encode_to_xml($this->values['RENEWALDATE']).'</RENEWALDATE>
				<EXPIRATIONDATE>'.encode_to_xml($this->values['EXPIRATIONDATE']).'</EXPIRATIONDATE>
				<DBNAME>'.encode_to_xml($this->values['DBNAME']).'</DBNAME>
				<URL>'.encode_to_xml($this->values['URL']).'</URL>
				<PUBLISHED>'.encode_to_xml($this->values['PUBLISHED']).'</PUBLISHED>
				<UPDATES>0</UPDATES>
				<TYPE>asp</TYPE>
			</INFO>
			<DEPENDENCIES>
				<DEPENDENCY type="Masters" mode="reverse">
					<LICENSE ID="'.$this->masterID.'"/>
				</DEPENDENCY>
				'.$ownerDepNQL.'
				'.$dealerDepNQL.'
			</DEPENDENCIES>';
		if($this->licenseID){
			$nql->addCommand(
				'<UPDATE>
					<LICENSE ID="'.$this->licenseID.'">
						'.$datas.'
					</LICENSE>
				</UPDATE>');
			$nql->execute();
			$this->setSuccess('License successfully updated');
		}else{
			$nql->addCommand(
				'<CREATE>
					<LICENSE>
						'.$datas.'
					</LICENSE>
				</CREATE>');
			$nql->execute();
			$this->setSuccess('License successfully created');
		}
		
		return true;
	}
}

?>