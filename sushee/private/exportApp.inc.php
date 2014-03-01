<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/exportApp.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/datas_structure.class.php');

/*

 Exports a xSushee file containing all the commands to create or update one or multiple modules on a distant sushee installation

<EXPORT>
	<APP ID=".." release-type="{major,minor,revision}">
		<QUERY>
			<SEARCH>...</SEARCH>
		</QUERY>
	</APP>
</EXPORT>

*/
class sushee_exportApp extends RetrieveOperation{
	
	var $exportedElements;
	var $installFile;
	var $installDir;
	var $appDir;
	var $releaseType; // {'major','minor','revision'}
	var $appFolder;
	
	function sushee_exportApp($name,$operationNode){
		parent::NQLOperation($name,$operationNode);
		
		$this->exportedElements = array();
	}
	
	function parse(){
		if(!$this->firstNode->valueOf('@ID')){
			$this->setError('No ID indicated for the app to export');
			return false;
		}else{
			$this->setElementID($this->firstNode->valueOf('@ID'));
		}
		
		$this->releaseType = $this->firstNode->valueOf('@release-type');
		if(!$this->releaseType){
			$this->releaseType = 'revision';
		}
		
		$this->appFolder = $this->firstNode->valueOf('@folder');
		if(!$this->appFolder){
			$this->appFolder = '/apps/';
		}
		
		return true;
	}
	
	function operate(){
		$appID = $this->getElementID();
		$this->installDir = new TempDirectory();
		$this->installDir->create();
		$this->appDir = $this->installDir->createDirectory('app');
		$this->installFile = $this->installDir->createFile('install.xml');
		$namespaces = new NamespaceCollection();
		$namespaces_str = $namespaces->getXMLHeader();
		$this->installFile->append(SUSHEE_XML_HEADER.'<QUERY '.$namespaces_str.'>');
		$currentRequest = new Sushee_Request();
		
		$shell = new Sushee_Shell();
		
		//----------------------
		// COLLECTING APP DATAS
		//----------------------
		// needing app URL to retrieve custom commands and processors for this app
		// needing app version to name the package
		$shell->addCommand(
			'<GET>
				<APP ID="'.$appID.'"/>
				<RETURN>
					<INFO>
						<URL/>
						<NAMESPACE/>
						<VERSION/>
					</INFO>
				</RETURN>
			</GET>');
		$appURL = $shell->valueOf('/RESPONSE/RESULTS/APP/INFO/URL');
		$appNamespace = $shell->valueOf('/RESPONSE/RESULTS/APP/INFO/NAMESPACE');
		$appVersion = $shell->valueOf('/RESPONSE/RESULTS/APP/INFO/VERSION');
		
		// copying the files of the official version of the app into the working directory
		$appFolder = new KernelFolder($this->appFolder.$appNamespace.'/'.$appURL.'/');
		if($appFolder->exists()){
			$appFolder->copyContent($this->appDir);
		}
		
		// computing the new app version
		if(!$appVersion){
			$appVersion = '0.0.0';
		}
		$appVersionParts = explode('.',$appVersion);
		$appMajor = (int)$appVersionParts[0];
		$appMinor = (int)$appVersionParts[1];
		$appRevision = (int)$appVersionParts[2];
		switch($this->releaseType){
			case 'major':
				$appMajor++;
				$appMinor = 0;
				$appRevision = 0;
				break;
			case 'minor':
				$appMinor++;
				$appRevision = 0;
				break;
			default:
				$appRevision++;
		}
		$appVersion = $appMajor.'.'.$appMinor.'.'.$appRevision;
		// renaming the working directory to have the name of the app with its version
		$this->installDir->rename($appNamespace.'-'.$appURL.'-'.$appVersion);
		
		$shell->addCommand(
			'<UPDATE>
				<APP ID="'.$appID.'">
					<INFO>
						<TIMESTAMP>'.$currentRequest->getDateSQL().'</TIMESTAMP>
						<VERSION>'.$appVersion.'</VERSION>
					</INFO>
				</APP>
			</UPDATE>');
		$shell->execute();
		
		// list of namespace included in the request (to avoid duplicate)
		$namespaces_included = array();
		
		// including the application namespace
		$this->getCreateElementInfo('<SEARCH><NAMESPACE namespace="'.$appNamespace.'"/></SEARCH>');
		$namespaces_included[$appNamespace] = true;
		
		$this->getCreateElementInfo('<GET><APP ID="'.$appID.'"/></GET>');
		
		// exporting the commands to create the labels
		if($appNamespace){
			$domain = $appNamespace.'/'.$appURL;
			
			$shell->addCommand(
				'<SEARCH name="labels">
					<LABEL languageID="all">
						<INFO>
							<DENOMINATION op="starts-with">'.$domain.'</DENOMINATION>
						</INFO>
					</LABEL>
				</SEARCH>');
				
			$labelsNodes = $shell->getElements('/RESPONSE/RESULTS/LABEL');
			foreach($labelsNodes as $labelNode){
				$this->installFile->append(
					'<CREATE><LABEL name="'.$labelNode->valueOf('@name').'" languageID="'.$labelNode->valueOf('@languageID').'">'.$labelNode->valueOf().'</LABEL></CREATE>');
			}
		}
		
		// exporting the sushee commands to create the custom commands
		$this->getCreateElementInfo('<SEARCH><CUSTOMCOMMAND><INFO><PATH>'.$this->appFolder.$appNamespace.'/'.$appURL.'</PATH></INFO></CUSTOMCOMMAND></SEARCH>');
		
		//----------------------
		// COLLECTING MODULE DATAS
		//----------------------
		$shell->addCommand(
			'<SEARCH>
				<MODULE app="'.$appURL.'" appNamespace="'.$appNamespace.'"/>
				<RETURN>
					<NOTHING/>
				</RETURN>
			</SEARCH>');
		$shell->execute();
		
		$resultModules = $shell->getElement('/RESPONSE/RESULTS');
		$moduleNodes = $resultModules->getElements('MODULE');
		$configNode = $this->firstNode;
		
		// determining which module to export
		$modulesToExport = new Vector();
		
		foreach($moduleNodes as $moduleNode){
			$moduleID = $moduleNode->getAttribute('ID');
			$moduleInfo = moduleInfo($moduleID);
			
			// first adding the parent modules (if module is an extension)
			if($moduleInfo->isExtension()){
				$parents = $moduleInfo->getParents();
				while($parentModuleInfo = $parents->next()){
					$modulesToExport->add($parentModuleInfo->getID(),moduleInfo($parentModuleInfo->getID()));
				}
			}
			
			// adding the current module
			$modulesToExport->add($moduleID,moduleInfo($moduleID));
		}
		
		while($moduleInfo = $modulesToExport->next()){
			$moduleID = $moduleInfo->getID();
			
			//----------------------
			// NAMESPACE
			//----------------------
			// exporting the commands to create the namespace
			$namespace = $moduleInfo->getNameSpace();
			if($namespace && !$namespaces_included[$namespace->getName()]){
				$namespaces_included[$namespace->getName()] = true;
				$this->getCreateElementInfo('<GET><NAMESPACE ID="'.$namespace->getID().'"/></GET>');
			}
			
			//----------------------
			// MODULE
			//----------------------
			// exporting the commands to create the modules
			// <CREATE><MODULE>....
			$this->getCreateElementInfo('<GET><MODULE ID="'.$moduleID.'"/></GET>');
			
			//----------------------
			// FIELDS
			//----------------------
			// exporting the commands to create all the fields (even if already existing : target system will manage to create only what is necessary and to upate if it has to be)
			// <CREATE><FIELD>.... 
			$fields = $moduleInfo->getFields();
			$fieldIDs = array();
			foreach($fields as $field){
				if(!$field->isSystem()){
					$field->register();
					if($moduleInfo->isExtension() && $field->isParentField()){
						; // not including it
					}else{
						$fieldIDs[] = $field->getRegistrationID();
					}
				}
			}
			$this->getCreateElementInfo('<GET><FIELD ID="'.implode(',',$fieldIDs).'"/></GET>');
			
			//----------------------
			// PROCESSOR and CUSTOM COMMANDS
			//----------------------
			// exporting the commands to create the processors
			// taking only the processors not limited to an env or inside the env exported
			$this->getCreateElementInfo(
					'<SEARCH>
						<PROCESSOR env="" moduleID="'.$moduleInfo->getID().'">
							<INFO>
								<PATH>'.$appNamespace.'/'.$appURL.'</PATH>
								<ENV op="="></ENV>
							</INFO>
							<INFO>
								<PATH>'.$appNamespace.'/'.$appURL.'</PATH>
								<ENV op="=">'.$this->appFolder.'</ENV>
							</INFO>
						</PROCESSOR>
					</SEARCH>');
			
			
			
			// exporting the virtual modules operating on the module 
			$this->getCreateElementInfo(
				'<SEARCH>
				   	<VIRTUALMODULE>
				      <INFO>
				         <VIEWXML op="contains">&lt;SEARCH &lt;'.$moduleInfo->getxSusheeName().'</VIEWXML>
				      </INFO>
				   </VIRTUALMODULE>
				</SEARCH>');
			
			
			//----------------------
			// OFFICITY:REPORT AND OFFICITY:FILTER
			//----------------------
			// exporting the sushee commands to create
			$this->getCreateElementInfo('<SEARCH><OFFICITY:REPORT module="'.$moduleInfo->getName().'" model="1"/></SEARCH>');
			$this->getCreateElementInfo('<SEARCH><OFFICITY:FILTER module="'.$moduleInfo->getName().'" default="1"/></SEARCH>');
			
			//----------------------
			// CATEGORIES
			//----------------------
			// exporting the commands to create the categories
			$shell->addCommand(
				'<GET>
				   <CATEGORIES path="/'.$moduleInfo->getName().'/"/>
				   <RETURN depth="all" languageID="all"/>
				</GET>');
			$shell->execute();
			
			// replacing IDs by originalIDs in all CATEGORY nodes
			$categoryNodes = $shell->getElements('/RESPONSE/RESULTS//CATEGORY');
			foreach($categoryNodes as $categoryNode){
				$ID = $categoryNode->valueOf('@ID');
				$categoryNode->removeAttribute('ID');
				$categoryNode->setAttribute('originalID',$ID);
			}
			
			$categories = $shell->copyOf('/RESPONSE/RESULTS/*');
			if($categories){
				$this->installFile->append(
					'<UPDATE><CATEGORIES module="'.$moduleInfo->getName().'">'.$categories.'</CATEGORIES></UPDATE>');
			}
			
			
			//----------------------
			// LIST and LABELS
			//----------------------
			// exporting the commands to create the lists
			if($namespace){
				$domain = $namespace->getName().'/'.$moduleInfo->getName();
			}else{
				$domain = 'sushee/'.$moduleInfo->getName();
			}
			$shell->addCommand(
				'<SEARCH><LIST domain="'.$domain.'" languageID="all"/></SEARCH>');
				
			$listsNodes = $shell->getElements('/RESPONSE/RESULTS/LIST');
			foreach($listsNodes as $listNode){
				$itemsNodes = $listNode->getElements('ITEM');
				foreach($itemsNodes as $itemNode){
					$items.='<ITEM label="'.$itemNode->valueOf('@label').'" value="'.$itemNode->valueOf('@value').'"/>';
				}
				$this->installFile->append(
					'<CREATE><LIST name="'.$listNode->valueOf('@name').'" domain="'.$listNode->valueOf('@domain').'" languageID="'.$listNode->valueOf('@languageID').'">'.$items.'</LIST></CREATE>');
			}
			
			
			
			//----------------------
			// ELEMENTS
			//----------------------
			// exporting the commands to import the elements
			if($configNode && $configNode->getElement('QUERY/*')){
				$searchNodes = $configNode->getElements('QUERY/*['.$moduleInfo->getxSusheeName().']'); // request on the module : <QUERY><SEARCH>...
				foreach($searchNodes as $searchNode){
					// taking a certain subset of elements
					$query = $searchNode->copyOf('.');
					// create the first minimal set of elements. Because OriginalID is unique, it will not create duplicates
					// <CREATE><element originalID="..."><INFO>...
					$this->getCreateElementInfo($query);
				}
				
			}
			
		}
		// exporting the commands to create the deptype between the modules exported and with the sushee native modules
		// <CREATE><DEPENDENCYENTITY> ...
		$modulesToExport->reset();
		while($moduleInfo = $modulesToExport->next()){
			$depTypes = array();
			$moduleID = $moduleInfo->getID();
			//----------------------
			// DEPENDENCYENTITY
			//----------------------
			// getting the deps from this module
			$shell->addCommand(
				'<GET>
				  <DEPENDENCYENTITY from="'.$moduleInfo->getName().'"/>
				</GET>');
			$shell->execute();
			
			$deps = $shell->getElements('/RESPONSE/RESULTS/DEPENDENCYENTITY');
			
			foreach($deps as $depNode){
				// checking the target module is installed too
				$to = $depNode->valueOf('/DEPENDENCYTYPE/@to');
				if($resultModules->exists('MODULE[INFO/DENOMINATION="'.$to.'"]')){
					$depNode->getElement('DEPENDENCYTYPE')->removeAttribute('ID');
					$this->installFile->append('<CREATE>'.$depNode->copyOf('.').'</CREATE>');
					
					// conserving the deptypes imported to import the deps of this type for the imported elements
					$depTypes[] = $depNode->valueOf('/DEPENDENCYTYPE/TYPE');
				}
			}
			// linking elements together : taking the dependencies between the elements imported
			if($configNode && $configNode->getElement('QUERY/*')){
				$searchNodes = $configNode->getElements('QUERY/*['.$moduleInfo->getxSusheeName().']'); // request on the module : <QUERY><SEARCH>...
				foreach($searchNodes as $searchNode){
					// taking a certain subset of elements
					$query = $searchNode->copyOf('.');
					// linking together the minimal set of elements. Because OriginalID is unique, it will not create duplicates
					// <UPDATE><element originalID="..."><DEPENDENCIES>...<element originalID="..."/>
					$this->getUpdateElementDep($query,$depTypes);
				}
			}
		}
		
		
		$this->installFile->append('</QUERY>');
		$appZip = $this->installDir->compress();
		$this->setXML('<RESULTS'.$this->getOperationAttributes().'>'.$appZip->getXML().'</RESULTS>');
		
		$this->installDir->delete();
		
		return true;
	}
	
	function getCreateElementInfo($xsusheeSearch){
		
		$xsushee = new XML($xsusheeSearch);
		$moduleName = $xsushee->getFirstchild()->getFirstchild()->nodename();
		$moduleInfo = moduleInfo($moduleName);
		$moduleID = $moduleInfo->getID();
		// selecting the field to export
		$fields = $moduleInfo->getFields();
		foreach($fields as $field){
			if(!$field->isSystem())
				$fields_return.='<'.$field->getxSusheeName().'/>';
		}
		
		// modifying the request to have only the datas fields
		$xsushee->getFirstchild()->removeChild('RETURN');
		$xsushee->getFirstchild()->appendChild(
			'<RETURN>
				<INFO>
					'.$fields_return.'
				</INFO>
				<DESCRIPTIONS languageID="all"/>
				<CATEGORIES/>
			</RETURN>');
		
		$shell = new Sushee_Shell();
		$shell->addCommand($xsushee->toString('/',''));
		$shell->execute();
		
		//composing the import request
		$elementNodes = $shell->getElements('/RESPONSE/RESULTS/*');
		foreach($elementNodes as $elementNode){
			$ID = $elementNode->valueOf('@ID');
			$uniqueID = $moduleID.'_'.$ID;
			// keeping trace of the element to know if we have to import its dependencies too
			$this->exportedElements[$uniqueID] = true;
			
			if($moduleName == 'PROCESSOR'){
				// we cannot export the moduleID like this, because the ID in the distant sushee is maybe different
				$moduleID = $elementNode->valueOf('INFO/MODULEID');
				$processorModuleName = moduleInfo($moduleID)->getName();
				$elementNode->removeChild('INFO/MODULEID');
				$elementNode->getElement('/INFO')->appendChild('<MODULE>'.$processorModuleName.'</MODULE>');
				
			}
			
			$this->installFile->append('<CREATE><'.$moduleName.' originalID="'.$ID.'">'.$elementNode->toString('/*').'</'.$moduleName.'></CREATE>');
		}
		
	}
	
	function getUpdateElementDep($xsusheeSearch,$depTypes){
		
		$xsushee = new XML($xsusheeSearch);
		$moduleName = $xsushee->getFirstchild()->getFirstchild()->nodename();
		$moduleInfo = moduleInfo($moduleName);
		
		// building the xsushee return with the deptypes imported
		$depTypesReturn = '';
		foreach($depTypes as $depTypeName){
			$depTypesReturn.='<DEPENDENCY type="'.$depTypeName.'"><NOTHING/></DEPENDENCY>';
		}
		
		
		$xsushee->getFirstchild()->removeChild('RETURN');
		$xsushee->getFirstchild()->appendChild(
			'<RETURN>
				<NOTHING/>
				<DEPENDENCIES>'.$depTypesReturn.'</DEPENDENCIES>
			</RETURN>');
		
		$shell = new Sushee_Shell();
		$shell->addCommand($xsushee->toString('/',''));
		$shell->execute();
		
		//composing the import request
		$elementNodes = $shell->getElements('/RESPONSE/RESULTS/*');
		foreach($elementNodes as $elementNode){
			$ID = $elementNode->valueOf('@ID');
			$importedDep = 0;
			
			$elementInDepsNodes = $elementNode->getElements('DEPENDENCIES/DEPENDENCY/*');
			$countElementInDeps = sizeof($elementInDepsNodes);
			// looping in the nodes backward to be able to remove a node in case the element in dep should not be exported
			for($i = $countElementInDeps - 1 ; $i >= 0 ; $i--){
				$elementInDepsNode = $elementInDepsNodes[$i];
				// checking the element in dep is in the exported elements, otherwise its not necessary to link it
				$depModuleName = $elementInDepsNode->nodename();
				$depModuleInfo = moduleInfo($depModuleName);
				$depEltID = $elementInDepsNode->getAttribute('ID');
				
				$uniqueID = $depModuleInfo->getID().'_'.$depEltID;
				
				if($this->exportedElements[$uniqueID]){
					// replacing attributes ID by originalID
					$elementInDepsNode->setAttribute('originalID',$depEltID);
					$elementInDepsNode->removeAttribute('ID');
					
					$importedDep++;
				}else{
					// removing the link
					$elementInDepsNode->remove();
				}
				
			}
			if($importedDep > 0 )
				$this->installFile->append('<UPDATE><'.$moduleName.' originalID="'.$ID.'">'.$elementNode->toString('/*').'</'.$moduleName.'></UPDATE>');
		}
	}
	
}



?>