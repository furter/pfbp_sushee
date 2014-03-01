<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/executeCustomCmd.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/classcaller.class.php');
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");
require_once(dirname(__FILE__)."/../private/search.inc.php");
require_once(dirname(__FILE__)."/../private/create.nql.php");
require_once(dirname(__FILE__)."/../common/nql.class.php");

class sushee_executeCustomCommand extends RetrieveOperation{
	
	var $operation;
	var $target;
	var $row;
	
	function parse(){
		
		
		$this->operation = $this->getOperationNode()->nodename();
		$firstchild = $this->getOperationNode()->getFirstchild();
		
		if($firstchild){
			$this->target = $firstchild->nodename();
		}else{
			$this->target = '';
		}
		
		return true;
	}
	
	function operate(){
		$operation = $this->operation;
		$target = $this->target;
		$db_conn = db_connect();
		
		// --------------------------
		// FIRST TRYING THE VIRTUAL MODULES
		// --------------------------
		if($operation=='SEARCH' || $operation=='GET' || $operation=='CREATE' ){
			$sql = 'SELECT element.`ViewXML`,element.`CreationTemplateXML` FROM `virtualmodules` AS element WHERE element.`Denomination` LIKE "'.encode_for_DB($target).'" AND element.`Activity` = 1';
			
			$view_row = $db_conn->getRow($sql);
			if($view_row){
				if( 
					($view_row['ViewXML'] && ($operation=='SEARCH' || $operation=='GET')) || 
					($view_row['CreationTemplateXML'] && ($operation=='CREATE'))
					){
					if($operation=='CREATE'){
						$query = new XML($view_row['CreationTemplateXML']);
					}else{
						$query = new XML($view_row['ViewXML']);
					}
						
					if($query->loaded){
						// merging the query of the view with the query of the user that may have added new criterions
						$merger = new Sushee_QueryMerger($query->getFirstChild(),$this->operationNode);
						$totalquery = $merger->execute();
						query_log($totalquery->toString());
						// executing the query
						$new_opNode = new XMLNode($totalquery,'/*[1]');
						
						if($operation=='CREATE'){
							$op = new createElement($this->getName(),$new_opNode);
						}else{
							$op = new searchElement($this->getName(),$new_opNode);
						}
						
						$res = $op->execute();
						
						if($res){
							$this->xml = $op->getXML();
							return true;
						}else{
							// view subrequests has returned an error
							$this->setError($op->getError());
							return false;
						}
					}else{
						$this->setError('VirtualModule `'.$moduleName.'` is erroneous : query is invalid XML');
						return false;
					}
					
				}else{
					$this->setError('VirtualModule `'.$moduleName.'` is incomplete : no query is defined');
					return false;
				}
			}
		}
		
		// --------------------------
		// NOW TRYING THE CUSTOM COMMANDS
		// --------------------------
		$sql = 'SELECT element.`Path`, element.`ClassName`, element.`Method` FROM `customcommands` AS element WHERE element.`Operation` LIKE "'.encode_for_db($operation).'" AND (element.`Target` LIKE "'.encode_for_db($target).'" OR element.`Target` = "*") AND element.`Activity` = 1';
		
		$this->row = $db_conn->getRow($sql);
		
		if(!$this->row){
			$this->setError('Operation `'.$operation.'` ON `'.$target.'` is not defined in xSushee.');
			return false;
		}
		
		$row = $this->row;
		$classcaller = new sushee_PHPClassCaller($row['Path'],$row['ClassName'],$row['Method'],$this->getOperationNode());
		
		$res = $classcaller->execute();
		if(!$res){
			$this->setError('Custom command failed : '.$classcaller->getError());
			return false;
		}
		
		// to avoid that custom command break the XML validity (malformed), we first try to parse it
		// if the XML is not parseable, we return an encoded version
		$response_xml = new XML('<RESULTS>'.$classcaller->getResponse().'</RESULTS>');
		if($response_xml->loaded){
			$this->setXML($classcaller->getResponse());
		}else{
			$this->setXML(encode_to_xml($classcaller->getResponse()));
		}
		
		return true;
	}
}


// takes two xSushee requests and merges them into one (for views treatment)
/*
<SEARCH>
	<...>
		<INFO>
			<FIELD1/>
		</INFO>
		<INFO>
			<FIELD2/>
		</INFO>
	</...>
</SEARCH>

and

<SEARCH>
	<...>
		<INFO>
			<FIELD3/>
		</INFO>
	</...>
</SEARCH>

becomes

<SEARCH>
	<...>
		<INFO>
			<FIELD1/><FIELD3/>
		</INFO>
		<INFO>
			<FIELD2/><FIELD3/>
		</INFO>
	</...>
</SEARCH>

*/
class Sushee_QueryMerger extends SusheeObject{
	
	var $source;
	var $destination;
	var $query_final;
	
	function Sushee_QueryMerger(/* XMLNode */ $query_source,/* XMLNode */ $query_destination){
		$this->source = $query_source;
		$this->destination = $query_destination;
		
	}
	
	function execute(){
		// first cleaning entering requests
		canonicalizeNQL($this->source->getDocument(),$this->source->getFirstChild()->getPath());
		canonicalizeNQL($this->destination->getDocument(),$this->destination->getFirstChild()->getPath());
		
		// creating a third XML with only the root of the request, to copy the merging into it
		$operation = $this->source->nodename();
		$source_elementNode = $this->source->getFirstChild();
		$element = $source_elementNode->nodename();
		$this->query_final = new XML($this->source->toString());
		
		$this->query_final->removeChild('/*[1]/*[1]/INFO');
		$final_operationNode = $this->query_final->getElement('/*[1]');
		$final_elementNode = $this->query_final->getElement('/*[1]/*[1]');
		
		// mixing the INFO nodes
		$this->handleInfoNode();
		
		// copying SORT, PAGINATE, etc
		// first removing the existing one
		if($final_operationNode->getElement('SORT') && $this->destination->getElement('SORT')){
			$final_operationNode->removeChild('/SORT');
		}
		if($final_operationNode->getElement('PAGINATE') && $this->destination->getElement('PAGINATE')){
			$final_operationNode->removeChild('/PAGINATE');
		}
		if($final_operationNode->getElement('RETURN') && $this->destination->getElement('RETURN')){
			$final_operationNode->removeChild('/RETURN');
		}
		
		$nodes_to_import = $this->destination->getElements('/*[position()>1]');
		foreach($nodes_to_import as $node){
			$final_operationNode->appendChild($node->toString());
		}
		
		// copying other crits than INFO : DESCRIPTION, CATGEORY, DEPENDENCY
		//$final_elementNode->appendChild($this->destination->getFirstChild()->copyOf('/*[name()!="INFO"]'));
		$other_children = $this->destination->getFirstChild()->getElements('/*[name()!="INFO"]');
		foreach($other_children as $node){
			$final_elementNode->appendChild($node->toString());
		}
		
		query_log($this->query_final->toString());
		return $this->query_final;
		return false;
	}
	
	function handleInfoNode(){
		$node = 'INFO';
		
		$source_elementNode = $this->source->getFirstChild();
		$destination_elementNode = $this->destination->getFirstChild();
		$final_elementNode = $this->query_final->getElement('/*[1]/*[1]');
		
		$xpath = '/'.$node;
		$source_nodes = $source_elementNode->getElements($xpath);
		$destination_nodes = $destination_elementNode->getElements($xpath);
		if(sizeof($destination_nodes)==0){
			$destination_nodes[] = $destination_elementNode->appendChild('<'.$node.'/>');
		}
		foreach($destination_nodes as $destination_node){
			foreach($source_nodes as $source_node){
				// creating a copy of the node into the final query
				$new_node = $final_elementNode->appendChild($destination_node->copyOf('.'));
				$source_node_children = $source_node->getElements('/*');
				foreach($source_node_children as $child){
					$new_node->appendChild($child->copyOf('.'));
				}
			}
		}
	}
}