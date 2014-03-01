<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/search.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");

class SearchElement extends RetrieveOperation{
	function parse(){
		return true;
	}

	function operate(){
		$requestName = $this->operationNode->getNodename();
		$xml = $this->operationNode->getDocument();
		$current_path = $this->operationNode->getPath();
		$modulePath = $this->firstNode->getPath();
		$moduleName  = $this->firstNode->getNodename();
		$moduleInfo = moduleInfo($moduleName);
		$db_conn = db_connect();

		if(!$moduleInfo || !$moduleInfo->loaded){
			// no moduleerror of the user probably
			$this->setError('Module `'.$moduleName.'` could not be loaded');
			return false;
		}
		if(!$moduleInfo->getActionSecurity("SEARCH")){ // same security for GET
			$this->setSecurityError("You're not authorized to search elements in this module (`".$moduleName."`).");
			return false;
		}
		$query_result = '';
		//-----------------------------------
		// PREPROCESS
		//-----------------------------------
		$moduleInfo->preProcess('SEARCH',false,new XMLNode($xml,$current_path),$former_values,$new_values,$return_values);
		//-----------------------------------
		// SQL
		//-----------------------------------
		$sql='';
		try{
			$rs = getResultSet($moduleInfo,$xml,$current_path,$sql);
		}catch(Exception $e){
			$this->setError($e->getMessage());
			return false;
		}

		if (is_string($rs))
			return $rs;
		if ((!$rs || get_class($rs)!='ADORecordSet_mysql') && $requestName!=='COUNT'){
			if(strlen($sql)<1024)
				$this->setError($db_conn->ErrorMsg().' '.$sql);
			else
				$this->setError($db_conn->ErrorMsg().'. Length of query:'.strlen($sql));
			return false;
		}else{

			if($requestName!=='COUNT'){
				require_once(dirname(__FILE__).'/../common/searchoutputmanager.class.php');
				$manager = new Sushee_SearchOutputManager();
				$manager->setModule($moduleInfo->ID);
				$manager->setResultSet($rs); // the SQL result set
				$manager->setOperationnode(new XMLNode($xml,$current_path));

				$query_result = $manager->getXML();
			}
		}

		$attributes = $this->getOperationAttributes();

		if ($rs->result_page){
			$attributes.=' page="'.$rs->result_page.'"';
			$attributes.=' isLastPage="'.$rs->isLastPage.'"';
			$attributes.=' totalPages="'.$rs->totalPages.'"';
			$attributes.=' totalCount="'.$rs->totalCount.'"';
		}
		if($rs->packet){
			$attributes.=' page="'.$rs->packet.'"';
			$attributes.=' pages="'.$rs->total_packets.'"';
			$attributes.=' last-page="'.$rs->last_packet.'"';
			$attributes.=' hits="'.$rs->total_elements.'"';
		}else if($requestName!='COUNT'){
			$attributes.=' hits="'.$rs->total_elements.'"';
		}
		if($requestName=='COUNT'){
			$attributes.=' totalCount="'.$rs->totalCount.'"';
			if(!$rs->packet)
				$attributes.=' hits="'.$rs->totalCount.'"';
		}
		// adding the SQL if user is asking
		$user = new NectilUser();
		$userID = $user->getID();
		if($this->operationNode->getElement('/RETURN/SQL')){
			$query_result.='<SQL>'.encode_to_xml($sql).'</SQL>';
		}

		$this->xml = '<RESULTS'.$attributes.'>'.$query_result.'</RESULTS>';

		return true;
	}
}