<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/importApp.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/url.class.php');

/*
 Exports a xSushee file containing all the commands to create or update one or multiple modules on a distant sushee installation

<IMPORT>
	<APP [href="http://..."] [path="..."] />
</IMPORT>
*/

class sushee_importApp extends NQLOperation
{
	
	var $appURL;
	var $appPath;
	
	function parse()
	{
		// check there is an href attribute to retrieve the archive of the application to install
		if(!$this->firstNode->getAttribute('href') && !$this->firstNode->getAttribute('path'))
		{
			$this->setError('No attribute path or href : this attribute indicates where to find the application archive');
			return false;
		}
		$this->appURL = $this->firstNode->getAttribute('href');
		$this->appPath = $this->firstNode->getAttribute('path');
		return true;
	}
	
	function operate()
	{
		// check this is sushee is ready to receive an app installation
		if(!$this->prepareSusheeForAppInstallation())
		{
			return false;
		}
		
		// a shell to proceed the import requests and another to check the datas before importing
		$importShell = new Sushee_Shell();
		$checksShell = new Sushee_Shell();
		
		// enabling exception to be sure any error will stop the installation
		$importShell->enableException();
		$checksShell->enableException();
		
		// logfile : we save all the operations to keep a trace
		$logFile = new TempFile();
		$logFile->setExtension('xml');
		$logFile->append('<QUERY>');
		
		// the directory where we will unzip the future app
		$workingDir = new TempDirectory();
		$workingDir->create();
		//----------------------
		// RETRIEVING THE PACKAGE TO INSTALL
		//----------------------
		if($this->appURL)
		{
			// retrieve the archive of the application at the url given in the XML
			$urlHandler = new URL($this->appURL);
			$distantInstallZip = $urlHandler->execute();

			if(!$distantInstallZip)
			{
				$this->setError('App href `'.$this->appURL.'` returned empty file');
				return false;
			}

			// saving the archive in local
			$zipFile = $workingDir->createFile('app.zip');
			$zipFile->setExtension('zip');
			$zipFile->save($distantInstallZip);
			
		}
		else if($this->appPath)
		{
			
			$appLocalFile = new File($this->appPath);
			if(!$appLocalFile->exists())
			{
				$this->setError('The file doesnt exist');
				return false;
			}

			// copying the file in the working directory to unzip it
			$zipFile = $appLocalFile->copy($workingDir);
			$zipFile->rename('app.zip');
		}
		
		// uncompressing, but allowing PHP files, exceptionnaly as they are necessary to the app
		$blockedExtensions = $GLOBALS['BlockedExt'];
		
		// modifiying the array of blocked extensions, only allowing PHP extension (to keep .exe, .sh blocked)
		unset($blockedExtensions[array_search('php',$blockedExtensions)]);
		$zipFile->uncompress($blockedExtensions);
		
		// searching the content of the zip in the working directory
		while($appInstallDir = $workingDir->getNextChildren())
		{
			if($appInstallDir->isDirectory())
			{
				// found what should be the dir exported and containing the app dir and the install.xml
				break;
			}
		}
		
		if(!$appInstallDir || !$appInstallDir->isDirectory())
		{
			$this->setError('Archive didnt contain the application');
			return false;
		}
		
		// retrieving the app files
		$appDir = $appInstallDir->getChild('app');
		if(!$appDir || !$appDir->isDirectory())
		{
			$this->setError('Archive didnt contain the application files');
			return false;
		}
		
		// retrieving the install.xml containing the xsushee commands to install the app
		$installXMLFile = $appInstallDir->getChild('install.xml');
		if(!$installXMLFile || !$installXMLFile->exists())
		{
			$this->setError('Archive didnt contain the install.xml file');
			return false;
		}
		$installXMLString = $installXMLFile->toString();
		$installXML = new XML($installXMLString);
		
		//----------------------
		// EXECUTING THE INSTALL.XML FILE WITH XSUSHEE COMMANDS
		//----------------------
		try{
			$commandNodes = $installXML->getElements('/QUERY/*');
			// foreach command
			foreach($commandNodes as $commandNode){
				$commandName = $commandNode->nodename();
				$targetNode = $commandNode->getfirstchild();
				$targetName = $targetNode->nodename();
				// if it is a CREATE
				if($commandName == 'CREATE'){
					$localID = false;
					// leave request as it is ?
					$localRequest = $commandNode->copyOf('.');

					// we have to UPDATE if the element was already imported before
					// at a first import these newly created elements are saved in a table with the correspondency between the original ID and the localID
					// this way, we know if the element was imported and if it needs to be updated

					// for APP, MODULE, FIELD and NAMESPACE, we dont use originalID table, but we verify if these are already present (they are unique)
					// this also allows to update app that were not imported with the importer
					if($targetName == 'APP'){
						// an app is determined by its URL and its namespace
						// checking if this app already exists
						$appURL = $targetNode->valueOf('INFO/URL');
						$appNamespace = $targetNode->valueOf('INFO/NAMESPACE');
						$timestamp = $targetNode->valueOf('INFO/TIMESTAMP');
						$appVersion = $targetNode->valueOf('INFO/VERSION');

						$checksShell->addCommand(
							'<SEARCH>
								<APP url="'.$appURL.'" namespace="'.$appNamespace.'"/>
								<RETURN>
									<INFO>
										<TIMESTAMP/>
									</INFO>
								</RETURN>
							</SEARCH>');

						$localID = $checksShell->valueOf('/RESPONSE/RESULTS/APP/@ID');
						
						// checking the app installed is not already installed in a NEWER version
						$localTimestamp = $checksShell->valueOf('/RESPONSE/RESULTS/APP/INFO/TIMESTAMP');
						if($localTimestamp >= $timestamp){
							$this->setError('Version installed is NEWER ('.$localTimestamp.') than the one you are trying to install ('.$timestamp.')');
							return false;
						}

					}else if($targetName == 'NAMESPACE'){
						// a namespace is determined by its denomination
						// checking if this namespace already exists
						$namespace = $targetNode->valueOf('INFO/NAMESPACE');

						$checksShell->addCommand(
							'<SEARCH>
								<NAMESPACE namespace="'.$namespace.'"/>
								<RETURN><NOTHING/></RETURN>
							</SEARCH>');

						$localID = $checksShell->valueOf('/RESPONSE/RESULTS/NAMESPACE/@ID');

					}else if($targetName == 'MODULE'){
						// a module is determined by its denomination
						// checking if this module already exists
						$denomination = $targetNode->valueOf('INFO/DENOMINATION');

						$checksShell->addCommand(
							'<SEARCH>
								<MODULE denomination="'.$denomination.'"/>
								<RETURN><NOTHING/></RETURN>
							</SEARCH>');

						$localID = $checksShell->valueOf('/RESPONSE/RESULTS/MODULE/@ID');

					}else if($targetName == 'FIELD'){
						// a field is determined by its denomination and module
						// checking if this field already exists
						$module = $targetNode->valueOf('INFO/MODULE');
						$denomination = $targetNode->valueOf('INFO/DENOMINATION');

						// for the field we dont do it in xsushee, because the field might not have been registered in database and only exists on the table
						// so we check directly on the table
						$moduleInfo = moduleInfo($module);
						if($moduleInfo->loaded){
							$field = $moduleInfo->getField($denomination);
							if($field && $field->exists()){
								// if the field is not registered yet (in fields database) we register it
								$field->register();
								$localID = $field->getRegistrationID();
							}

						}


					}
					$originalID = $targetNode->valueOf('@originalID');
					// verifying if this element was imported before in the correspondency table
					if(!$localID && $originalID){
						// check if the originalID matches an element already present in the database (we have a table of matching originalID and localID)
						$localID = $this->retrieveLocalID($targetName,$targetNode->valueOf('@originalID'));

					}
					if($localID){
						$targetNode->setAttribute('ID',$localID);
						$localRequest = '<UPDATE>'.$targetNode->copyOf('.').'</UPDATE>';
					}

					// execute the request
					$logFile->append($localRequest);
					$importShell->addCommand($localRequest);
					$importShell->execute();

					// we save the imported element in the correspondency table
					if(!$localID && $originalID){
						$localID = $importShell->valueOf('/RESPONSE/MESSAGE/@elementID');
						$registerElement = 
						'<CREATE>
							<OFFICITY:APP_IMPORTED_ELEMENT>
								<INFO>
									<MODULE>'.$targetName.'</MODULE>
									<ORIGINALID>'.$originalID.'</ORIGINALID>
									<LOCALID>'.$localID.'</LOCALID>
								</INFO>
							</OFFICITY:APP_IMPORTED_ELEMENT>
						</CREATE>';

						$logFile->append($registerElement);
						$importShell->addCommand($registerElement);
						$importShell->execute();
					}

				}else{
					// if its an UPDATE
					// change every originalID and replace it by the localID
					$originalElementNodes = $commandNode->getElements('//*[@originalID]');
					foreach($originalElementNodes as $originalElementNode){
						$nodeName = $originalElementNode->nodename();
						
						if($nodeName == 'CATEGORY'){
							// categories
							$localID = $this->retrieveLocalCategoryID($originalElementNode);
						}else{
							// module elements
							$localID = $this->retrieveLocalID($nodeName,$originalElementNode->valueOf('@originalID'));
						}
						
						if($localID){
							$originalElementNode->setAttribute('ID',$localID);
						}
					}
					$localRequest = $commandNode->copyOf('.');

					$logFile->append($localRequest);
					$importShell->addCommand($localRequest);
					$importShell->execute();
				}
			}
		}catch(Exception $e){
			$this->setError('App installation failed : '.$e->getMessage());
			return false;
		}
		
		//----------------------
		// INSTALLING APPS IN EVERY ENVIRONMENT
		//----------------------
		$checksShell->addCommand(
			'<SEARCH>
				<OFFICITY:ENVIRONMENT published="1"></OFFICITY:ENVIRONMENT>
				<RETURN>
					<INFO>
						<FOLDER/>
					</INFO>
				</RETURN>
			</SEARCH>');
		$checksShell->execute();
		$environmentsNodes = $checksShell->getElements('/RESPONSE/RESULTS/OFFICITY:ENVIRONMENT');
		
		// sushee request object gives information about the general request
		foreach($environmentsNodes as $environmentNode){
			$environmentFolderValue = $environmentNode->valueOf('INFO/FOLDER');
			if($environmentFolderValue){
				
				$environmentFolder = new KernelFolder('/'.$environmentFolderValue.'/');
				
				if($environmentFolder->exists()){
					
					// building the app directory path from the information we've got from the install.xml file
					$appFolder = new KernelFolder('/'.$environmentFolderValue.'/'.$appNamespace.'/'.$appURL.'/');
					
					if(!$appFolder->exists()){
						// trying to create the app directory
						$environmentFolder->createDirectory($appNamespace);
						
						$namespaceFolder = new KernelFolder('/'.$environmentFolderValue.'/'.$appNamespace.'/');
						
						if($namespaceFolder->exists()){
							$namespaceFolder->createDirectory($appURL);
						}
					}
					
					if($appFolder->exists()){
						$appParentFolder = $appFolder->getParent();
						// first renaming and backupping the existing app directory
						$res = $appFolder->rename($appURL.'-'.date('YmdHis'));
						
						if(!$res){
							$this->setError('Could not make a backup of the current version. Try to make a backup and copy the app files by yourself. Datas have been installed.');
							return false;
						}
						
						
						$appFolder->compress();
						$appFolder->delete();
						
						// creating the directory for the new version
						$appParentFolder->createDirectory($appURL);
						$newAppFolder = new KernelFolder('/'.$environmentFolderValue.'/'.$appNamespace.'/'.$appURL.'/');
						if(!$newAppFolder->exists()){
							$this->setError('Could not install the files. Data is installed : try copying the files yourself');
							return false;
						}
						
						// replacing by the new version
						$appDir->copyContent($newAppFolder);
						
						if(!$newAppFolder->getNextChildren()){
							$this->setError('Could not install the files. Data is installed : try copying the files yourself');
							return false;
						}
						
					}else{
						$this->setError('Could not create the app directory. Check the permissions or try copying the files yourself');
						return false;
					}
				}
			}
		}
		
		$logFile->append('</QUERY>');
		$this->setSuccess('`'.$appNamespace.'/'.$appURL.'` version '.$appVersion.' installed');
		
		
		return true;
	}
	
	function retrieveLocalID($moduleName,$originalID){
		$shell = new Sushee_Shell();
		
		$shell->addCommand(
			'<SEARCH>
				<OFFICITY:APP_IMPORTED_ELEMENT originalID="'.$originalID.'" module="'.$moduleName.'"/>
				<RETURN>
					<INFO>
						<LOCALID/>
					</INFO>
				</RETURN>
			</SEARCH>');
		// if it is, change the CREATE into an UPDATE AND add the ID of the element inside this Database
		$localElement = $shell->getElement('/RESPONSE/RESULTS/*[1]');
		if($localElement){
			$localID = $localElement->valueOf('INFO/LOCALID');
			return $localID;
		}
		return false; // no match : the element was not imported yet
	}
	
	function retrieveLocalCategoryID($node){
		$shell = new Sushee_Shell();
		
		// properties of the imported category
		$uniqueName = $node->valueOf('UNIQUENAME');
		$moduleName = $node->valueOf('ancestor::CATEGORIES/@module');
		
		// retrieving the category with the same unique name in local sushee
		$shell->addCommand('<GET><CATEGORY name="'.$uniqueName.'"/></GET>');
		
		// checking its in the right module, if not, its not the same category and we should create a new one with the same name, but postfixed with a number to distinguish (it will be done by sushee automatically)
		$categoryFound = $shell->getElement('/RESPONSE/RESULTS/CATEGORY');
		if($categoryFound && $categoryFound->valueOf('@module') == $moduleName){
			return $categoryFound->valueOf('@ID');
		}
		
		return false;
	}
	
	function prepareSusheeForAppInstallation(){
		// checking the module officity:app_imported_element is present. If not create it automatically
		// this module is necessary to register installed elements and know what distant element they were (for future upgrades)
		
		$moduleInfo = moduleInfo('officity:app_imported_element');
		if(!$moduleInfo->loaded){
			$shell = new Sushee_Shell();
			
			// creating the module and its basic fields necessary
			$shell->addCommand(
				'<CREATE>
					<MODULE>
						<INFO>
							<DENOMINATION>officity:app_imported_element</DENOMINATION>
							<TABLENAME>officity:app_imported_elements</TABLENAME>
						</INFO>
					</MODULE>
				</CREATE>');
				
			$shell->addCommand(
				'<CREATE>
					<FIELD>
						<INFO>
							<MODULE>officity:app_imported_element</MODULE>
							<DENOMINATION>Module</DENOMINATION>
							<TYPE>text</TYPE>
						</INFO>	
					</FIELD>
				</CREATE>');
			
			$shell->addCommand(
				'<CREATE>
					<FIELD>
						<INFO>
							<MODULE>officity:app_imported_element</MODULE>
							<DENOMINATION>OriginalID</DENOMINATION>
							<TYPE>number</TYPE>
						</INFO>	
					</FIELD>
				</CREATE>');
				
			$shell->addCommand(
				'<CREATE>
					<FIELD>
						<INFO>
							<MODULE>officity:app_imported_element</MODULE>
							<DENOMINATION>LocalID</DENOMINATION>
							<TYPE>number</TYPE>
						</INFO>	
					</FIELD>
				</CREATE>');
			
			$shell->execute();
			
			$moduleInfo = moduleInfo('officity:app_imported_element');
			if(!$moduleInfo->loaded){
				$this->setError('App installation needs the OFFICITY:APP_IMPORTED_ELEMENT module to work. Automatic installation of this module failed, try asking your administrator.');
				return false;
			}
		}
		return true;
	}
}