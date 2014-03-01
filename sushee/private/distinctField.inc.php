<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/distinctField.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__)."/../private/metaSearch.inc.php");

/*

<DISTINCT>
	<CONTACT>
		<INFO>
			<CONTACTTYPE/>
		</INFO>
		[<WHERE>
			...
		</WHERE>]
	</CONTACT>
</DISTINCT>

*/

class sushee_distinctField extends RetrieveOperation{
	
	function parse(){
		$moduleInfo = moduleInfo($this->firstNode->nodename());
		if(!$moduleInfo->loaded){
			$this->setError('Forbidden or unknown module name `'.$this->firstNode->nodename().'`');
			return false;
		}
		
		if(!$this->firstNode->exists('INFO/*')){
			$this->setError('Missing the field to distinct <INFO><fieldname/></INFO>');
			return false;
		}
		
		
		return true;
	}
	
	function operate(){
		$firstNode = $this->firstNode->nodename();
		$moduleInfo = moduleInfo($this->firstNode->nodename());
		
		// field to distinct
		$fieldname = $this->firstNode->getElement('INFO/*')->nodeName();
		$field = $moduleInfo->getField($fieldname);
		if(!$field){
			$this->setError('Field `'.$fieldname.'` doesnt exist in `'.$moduleInfo->getName().'`');
			return false;
		}
		
		// distinct on a selection of elements
		$where_node = $this->firstNode->getElement("WHERE[1]");
		$where_sql = '';
		if( $where_node ){
			
			
			// composing a smaller XML with only a SEARCH command with the content of the WHERE
			$small_xml = new XML(
				'<SEARCH><'.$firstNode.'>'.$where_node->toString().'</'.$firstNode.'><RETURN><NOTHING/></RETURN></SEARCH>');
			// we apply preprocessing to have the extension boolean added
			$preprocess = $moduleInfo->preProcess('SEARCH',false,$small_xml->getElement('/SEARCH'),$former_values,$new_values,$return_values);	
				
			$where_sql = "";
			$where_rs = getResultSet($moduleInfo,$small_xml,'/SEARCH[1]',$where_sql);
			// the SQL request in order to resolve the WHERE failed : we return the error to the user
			if (is_string($where_rs)){
				$this->setError($where_rs);
				return false;
			}
				
			if (!$where_rs){
				$this->setError($db_conn->ErrorMsg().$where_sql);
				return false;
			}
			
			// we have the elements IDs, checking all of them can be deleted (no locked or private elements)
			$IDs = '-1';
			while($search_row = $where_rs->FetchRow()){
				$IDs.=',';
				$IDs.=$search_row['ID'];
			}
			$where_sql = 'WHERE `ID` IN('.$IDs.')';
		}
		
		$sql = 'SELECT `'.$field->getName().'`,COUNT(*) as hits FROM `'.$moduleInfo->getTableName().'` '.$where_sql.' GROUP BY `'.$field->getName().'`';
		$db_conn = db_connect();
		$rs = $db_conn->execute($sql);
		if(!$rs){
			$this->setError($db_conn->ErrorMsg());
			return false;
		}
		$xml='<RESULTS '.$this->getOperationAttributes().'>';
		while($row = $rs->FetchRow()){
			$xml.=$field->encodeForNQL($row[$field->getName()],' hits="'.$row['hits'].'"');
		}
		$xml.='</RESULTS>';
		$this->setXML($xml);
		return true;
	}
	
}

?>