<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/getdescendant.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/dependencies.class.php');

class getDescendant extends RetrieveOperation{
	
	function parse(){
		$moduleInfo = moduleInfo($this->firstNode->nodeName());
		if ($moduleInfo->loaded==FALSE){
			$this->setError("The informations about the module `".$this->firstNode->nodeName()."` couldn't be found.");
			return false;
		}
		return true;
	}
	
	function operate(){
		// initializing variables
		$moduleInfo = moduleInfo($this->firstNode->nodeName());
		$current_path = $this->operationNode->getPath();
		$where_sql = "";
		$xml = $this->operationNode->xml;
		
		// removing the with, because it can impact the getResultSet function, but must only be used by the search lower
		$nql_with = $xml->toString($firstNodePath.'/WITH[1]','');
		$xml->removeChild($firstNodePath.'/WITH[1]');
		
		require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
		$where_rs = getResultSet($moduleInfo,$xml,$current_path,$where_sql);
		
		if (is_string($where_rs))
			return $where_rs;
		if (!$where_rs){
			$this->setError(encode_to_xml($db_conn->ErrorMsg()).encode_to_xml($where_sql));
			return false;
		}
		
		$IDs_string="";
		$first = true;
		$descendants = array();
		$descendants_ok = array();
		$depTypes = new DependencyTypeSet($moduleInfo->getID());
		// taking the roots (results of the first search) and save their children. Once saved, the children will be handled recursively to find all descendants 
		while($search_row = $where_rs->FetchRow()){
			$depTypes->reset();
			while($dependencyType = $depTypes->next()){
				$deps_rs = getDependenciesFrom($moduleInfo->ID,$search_row['ID'],$dependencyType->getID());
				while($dep_row = $deps_rs->FetchRow()){
					// staying inside the same module
					if($dependencyType->ModuleTargetID==$moduleInfo->ID){
						$descendants['elt'.$dep_row['TargetID']]=$dep_row['TargetID'];
					}
				}
			}
			
		}
		$elementID = array_shift($descendants);
		//the children are handled recursively to find all descendants
		while($elementID){
			$depTypes->reset();
			while($dependencyType = $depTypes->next()){
				$deps_rs = getDependenciesFrom($moduleInfo->ID,$elementID,$dependencyType->getID());
				while($dep_row = $deps_rs->FetchRow()){
					// staying inside the same module
					if($dependencyType->ModuleTargetID==$moduleInfo->ID && !isset($descendants_ok['elt'.$dep_row['TargetID']])){
						if(!isset($descendants[$dep_row['TargetID']]))
							$descendants['elt'.$dep_row['TargetID']]=$dep_row['TargetID'];
					}
				}
			}
			
			$descendants_ok['elt'.$elementID]=$elementID;

			$elementID = array_shift($descendants);
		}
		$IDs_string = implode(',',$descendants_ok);

		$moduleName = strtoupper($moduleInfo->getName());
		// copying the RETURN and SORT in the SEARCH request (all nodes of position > 1)
		$new_query = '<SEARCH><'.$moduleName.'><INFO><ID operator="IN">'.$IDs_string.'</ID></INFO>'.$nql_with.'</'.$moduleName.'>'.$xml->toString($current_path.'/*[position() > 1]').'</SEARCH>';
		
		$new_query_xml = new XML($new_query);
		
		include_once dirname(__FILE__)."/../private/search.inc.php";
		$nqlOp = new searchElement($name,new XMLNode($new_query_xml,'/SEARCH[1]'));
		$query_result = $nqlOp->execute();

		$this->setXML($query_result);
		return true;
	}
}

?>
