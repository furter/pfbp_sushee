<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchDeptype.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/dependencies.class.php');
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");

class searchDepType extends RetrieveOperation{
	var $moduleOriginInfo = false;
	var $moduleTargetInfo = false;
	var $domain = false;
	var $temporal = false;
	var $ID = false;
	
	function parse(){
		// taking all deptypes starting from a given module
		$moduleOrigin = $this->firstNode->valueOf("/@from");
		if($moduleOrigin){
			$this->moduleOriginInfo = moduleInfo($moduleOrigin);
			if (!$this->moduleOriginInfo->loaded){
				$this->setError($this->moduleOriginInfo->getLastError());
				return false;
			}
		}
		// taking all deptypes arriving to a given module
		$moduleTarget = $this->firstNode->valueOf("/@to");
		if($moduleTarget){
			$this->moduleTargetInfo = moduleInfo($moduleTarget);
			if (!$this->moduleTargetInfo->loaded){
				$this->setError($this->moduleTargetInfo->getLastError());
				return false;
			}
		}
		
		$this->domain = $this->firstNode->valueOf("DOMAIN");
		$this->temporal = $this->firstNode->valueOf("TEMPORAL");
		// taking a deptype
		$this->ID = $this->firstNode->valueOf("@ID");
		return true;
	}
	
	
	
	function getCompleteDeps(){
		$depTypeSet = $this->getDeps();
		$made = array();
		// array to keep trace of which dependency we already have made : especially useful for two-way asymmetric dependency where we don't want to have the link twice
		while($depType = $depTypeSet->next()){
			if (!isset($made[$depType->ID])){
				$made[$depType->ID]=true;
				$entity_result="";
				$return_entity_result="";
				
				$entity_result.=$depType->getXML();
				
				$moduleOriginInfo = $depType->getModuleOrigin();
				$moduleTargetInfo = $depType->getModuleTarget();
				
				if ($depType->isUTurn()){
					// it's a two-way symetric dep
					$return_entity_result = $entity_result;
					$entity_result="<DEPENDENCYTYPE from='".$moduleOriginInfo->getName()."' to='".$moduleTargetInfo->getName()."' type='start' ID='".$depType->ID."'>".$entity_result."</DEPENDENCYTYPE>";
					$return_entity_result="<DEPENDENCYTYPE from='".$moduleTargetInfo->getName()."' to='".$moduleOriginInfo->getName()."' type='start' ID='".$depType->ID."'>".$return_entity_result."</DEPENDENCYTYPE>";
				}else if ($depType->returnIsDependency()){
					// it's a two-way asymetric dep
					$entity_result="<DEPENDENCYTYPE from='".$moduleOriginInfo->getName()."' to='".$moduleTargetInfo->getName()."' type='start' ID='".$depType->ID."'>".$entity_result."</DEPENDENCYTYPE>";
					$return_dependencyType = $depType->getReturnType();
					$return_entity_result.=$return_dependencyType->getXML();
					$return_entity_result="<DEPENDENCYTYPE from='".$moduleTargetInfo->getName()."' to='".$moduleOriginInfo->getName()."' type='start' ID='".$return_dependencyType->ID."'>".$return_entity_result."</DEPENDENCYTYPE>";
					$made[$return_dependencyType->ID]=true;
				}else{
					$entity_result="<DEPENDENCYTYPE from='".$moduleOriginInfo->getName()."' to='".$moduleTargetInfo->getName()."' type='start' ID='".$depType->ID."'>".$entity_result."</DEPENDENCYTYPE>";
				}
				$query_result.="<DEPENDENCYENTITY>".$entity_result.$return_entity_result."</DEPENDENCYENTITY>";
			}
		}
		return $query_result;
	}
	
	function getShortDeps(){
		$depTypeSet = $this->getDeps();
		$languageID = $this->firstNode->valueOf("@languageID");
		
		$request = new Sushee_Request();
		if(!$languageID && $request->isLanguageRestricted())
			$languageID = $request->getLanguage();
		while($depType = $depTypeSet->next()){
			$moduleTargetInfo = $depType->getModuleTarget();
			$moduleOriginInfo = $depType->getModuleOrigin();
			$query_result.='<DEPENDENCY_TYPE security="'.$depType->getSecurity().'" from="'.$moduleOriginInfo->getName().'" to="'.$moduleTargetInfo->getName().'">';
			$query_result.=$depType->getTypeXML();
			$query_result.=$depType->getTraductionXML($languageID);
			$query_result.=$depType->getAnnexFieldsXML();
			$query_result.='</DEPENDENCY_TYPE>';
		}
		return $query_result;
	}
	
	function getDeps(){
		if($this->ID){
			$deps = new Vector();
			$depType = depType($this->ID);
			if($depType->loaded){ // if depType exists
				$deps->add($this->ID,$depType);
			}
			return $deps;
		}elseif($this->moduleOriginInfo && $this->moduleTargetInfo){
			// from a module to another module
			$deps = new DependencyTypeSet($this->moduleOriginInfo->getID(),$this->moduleTargetInfo->getID());
			
		}elseif($this->moduleOriginInfo){
			// from a module
			$deps = new DependencyTypeSet($this->moduleOriginInfo->getID(),false);
			
		}elseif($this->moduleTargetInfo){
			// to a module
			$deps = new DependencyTypeSet(false,$this->moduleTargetInfo->getID());
			
		}else{
			// all deptypes
			$deps = new DependencyTypeSet(false,false);
			
		}
		
		if($this->domain)
			$deps->setDomain($domain);
		return $deps->getTypes();
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		
		if ($this->firstNode->nodeName()=='DEPENDENCYENTITY'){
			$xml.=$this->getCompleteDeps();
		}else{
			$xml.=$this->getShortDeps();
		}
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
	
	function getXML(){
		return $this->xml;
	}
}

?>
