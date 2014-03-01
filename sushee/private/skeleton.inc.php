<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/skeleton.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/descriptions.inc.php");
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');

class Skeleton extends RetrieveOperation{
	
	function parse(){
		$moduleInfo = moduleInfo($this->firstNode->nodename());
		if ($moduleInfo->loaded==FALSE){
			$this->setError("The informations about the module couldn't be found.");
			return false;
		}
		return true;
	}
	
	function operate(){
		$moduleInfo = moduleInfo($this->firstNode->nodename());
		$xml = $this->firstNode->getDocument();
		$current_path = $this->operationNode->getPath();
		
		// executing the sql search
		$where_sql = "";
		require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
		
		$where_rs = getResultSet($moduleInfo,$xml,$current_path,$where_sql);
		if (is_string($where_rs)){
			$this->setError($where_rs);
			return false;
		}
		if (!$where_rs){
			$this->setError($db_conn->ErrorMsg().' '.$where_sql);
			return false;
		}
		
		$IDs_string="";
		$first = true;
		$descendants = array();
		$descendants_ok = array();
		$depth_limit = false;
		$desc_language = false;
		
		// profiling
		if($this->firstNode->exists('WITH')){
			$profileNode = $this->firstNode->getElement('WITH[1]');
		}else{
			$profileNode = $this->operationNode->getElement('RETURN[1]');
		}
		if($profileNode){
			$depth_limit = $profileNode->getData('@depth');
			
			$desc_language = $profileNode->getData('DESCRIPTIONS[1]/@languageID');
		}
		if($depth_limit===false)
			$depth_limit = 'all';
		if (!$desc_language)
			$desc_language='';
		if (isset($GLOBALS["NectilLanguage"]) && $GLOBALS['restrict_language'] && $desc_language=='')
			$desc_language = $GLOBALS["NectilLanguage"];
		if ($desc_language==='all')
			$desc_language='';
		
		// taking the roots and enumerate their children
		$query_result = '';
		while($search_row = $where_rs->FetchRow()){
			$query_result.=$this->operateElement($moduleInfo,$search_row['ID'],$search_row,1,$depth_limit,$desc_language);
		}
		$attributes = $this->getOperationAttributes();
		$query_result='<RESULTS'.$attributes.'>'.$query_result.'</RESULTS>';
		$this->setXML($query_result);
		return true;
	}
	
	function operateElement($moduleInfo,$elementID,$row,$depth=1,$depth_limit='all',$desc_language='',$element_ancestors=array()){
		$db_conn = db_connect();
		
		// Media specific conditions
		if($moduleInfo->name=='media'){
			if(!is_array($row)){
				$element_sql = 'SELECT `MediaType`,`Published`,`PageToCall` FROM `'.$moduleInfo->tableName.'` WHERE ID='.$elementID;
				$row = $db_conn->GetRow($element_sql);
			}
			if ($GLOBALS["php_request"] && !($GLOBALS["take_unpublished"]===true) && $row['Published']==0)
				return '';
			if($row['PageToCall']!='')
				$attributes.=' pagetocall="'.$row['PageToCall'].'"';
			if($row['Published']==0)
				$attributes.=' published="'.$row['Published'].'"';
			$attributes.=' mediatype="'.$row['MediaType'].'"';
		}
		
		// generating the output
		$moduleName = strtoupper($moduleInfo->name);
		$str = '<'.$moduleName.' ID="'.$elementID.'"'.$attributes.'>';
		$descs_rs = getDescriptions($moduleInfo->ID,$elementID,$desc_language,array('TITLE','LANGUAGEID'));
		while($desc_row = $descs_rs->FetchRow()){
			$desc_str.='<DESCRIPTION languageID="'.$desc_row['LanguageID'].'" title="'.str_replace(array('"',"\r","\n"),array('&quot;','',''),$desc_row['Title']).'"/>';
		}
		if($desc_str)
			$str.='<DESCRIPTIONS>'.$desc_str.'</DESCRIPTIONS>';

		if( ($depth<$depth_limit || $depth_limit==='all') && !in_array($elementID,$element_ancestors) ){
			$deps_str = '';
			// depTypes from the module to the same module
			$depTypes = new DependencyTypeSet($moduleInfo->getID(),$moduleInfo->getID());
			while($depType = $depTypes->next()){
				
				// taking the deps with this deptype
				$dep_sql = 'SELECT dep.* FROM `'.$depType->getTablename().'` AS dep WHERE `'.$depType->getOriginFieldname().'` = \''.$elementID.'\' AND dep.`DependencyTypeID` = '.$depType->getIDInDatabase().' ORDER BY dep.`'.$depType->getOrderingFieldname().'`';
				
				$deps_rs = $db_conn->Execute($dep_sql);

				$children_ancestors = array_merge($element_ancestors,array($elementID));
				$elt_str = '';
				while($dep_row = $deps_rs->FetchRow()){	$elt_str.=$this->operateElement($moduleInfo,$dep_row[$depType->getTargetFieldname()],false,$depth+1,$depth_limit,$desc_language,$children_ancestors);
				}
				if($elt_str){
					$deps_str.='<DEPENDENCY type="'.$depType->getName().'">';
					$deps_str.=$elt_str;
					$deps_str.='</DEPENDENCY>';
				}
			}
			if($deps_str){
				$str.='<DEPENDENCIES>';
				$str.=$deps_str;
				$str.='</DEPENDENCIES>';
			}


		}
		$str.= '</'.$moduleName.'>';
		return $str;
	}
}

?>
