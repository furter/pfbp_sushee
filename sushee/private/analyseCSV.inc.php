<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/analyseCSV.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/csv.class.php');

class analyseCSV extends RetrieveOperation{
	var $file = false;
	var $return_stats = true;
	var $return_columns = true;
	var $return_rows = true;
	
	
	function parse(){
		$path = $this->firstNode->valueOf('@path');
		
		if(!$path){
			$this->setError('path parameter is empty');
			return false;
		}
		
		$this->file = new Sushee_CSV($path);
		
		// checking the files is parseable
		if(!$this->file->exists()){
			$this->setError('File '.$path.' doesn\'t exist');
			return false;
		}
		if($this->file->isDirectory()){
			$this->setError($path.' is a directory');
			return false;
		}
		
		
		
		$returnInfoNode = $this->operationNode->getElement('RETURN/INFO');
		if($returnInfoNode){
			if($returnInfoNode->getElement('STATS')){
				$this->return_stats = true;
			}else
				$this->return_stats = false;
			if($returnInfoNode->getElement('COLUMNS')){
				$this->return_columns = true;
			}else
				$this->return_columns = false;
			if($returnInfoNode->getElement('ROWS')){
				$this->return_rows = true;
			}else
				$this->return_rows = false;
		}
		$separator = $this->firstNode->valueOf('@separator');
		if($separator){
			$this->file->setSeparator($separator);
		}
		return true;
	}
	
	function operate(){
		// determining what is to be returned
		$output = new Sushee_CSVOutput();
		$columns = $this->operationNode->getElements('RETURN/INFO/COLUMNS/COLUMN');
		if(sizeof($columns)>0){
			$output->enableAllColumns(false);
			foreach($columns as $column){
				$index = $column->valueOf('@i');
				if($index){
					$output->enableColumn($index);
				}
			}
		}
		// only returning x lines (PAGINATE)
		if($this->operationNode->getElement('PAGINATE')){
			$output->enablepaging($this->operationNode->valueOf('PAGINATE/@display'));
			if($this->operationNode->valueOf('PAGINATE/@page'))
				$output->returnPage($this->operationNode->valueOf('PAGINATE/@page'));
		}
		
		
		// building the XML
		$xml = '';
		
		$xml.=	'<CSV path="'.$this->file->getPath().'" name="'.$this->file->getShortName().'" size="'.$this->file->getReadableSize().'"';
		if($this->file->getExtension()){
			$xml.=' ext=".'.$this->file->getExtension().'"';
		}
		$xml.='>';
		$xml.=		'<INFO>';
		//if($this->return_stats)
			$xml.=		$this->file->getStatsXML();
		if($this->return_columns)
			$xml.=		$this->file->getColumnsXML($output);
		if($this->return_rows)
			$xml.=		$this->file->getRowsXML($output);
		$xml.=		'</INFO>';
		$xml.=	'</CSV>';
		
		$hits = $this->file->getRowsCount();
		$attributes = $this->getOperationAttributes();
		if($output->getPaging()){
			$attributes.=' page="'.$output->getPage().'"';
			$attributes.=' pages="'.ceil($hits / $output->getPaging()).'"';
		}
		
		$xml='<RESULTS'.$attributes.' hits="'.$hits.'">'.$xml.'</RESULTS>';
		$this->xml = $xml;
		return true;
	}
	
}
?>