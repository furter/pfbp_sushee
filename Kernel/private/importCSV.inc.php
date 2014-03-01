<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/importCSV.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/csv.class.php");

class importCSV extends RetrieveOperation{

	var $file = false;
	var $moduleInfo = false;
	var $columns = false;
	var $template = false;
	var $if_exists = 'replace';

	function parse(){
		$path = $this->firstNode->valueOf('@path');
		$this->file = new Sushee_CSV($path);
		if(!$this->file->exists()){
			$this->setError('File '.$path.' doesn\'t exist');
			return false;
		}
		$module = $this->firstNode->valueOf('@module');
		if(!$module){
			$this->setError("No module indicated for the import.");
			return false;
		}
			
		$moduleInfo = moduleInfo($module);
		if(!$moduleInfo->loaded){
			$this->setError("The informations about the module $module couldn't be found.");
			return false;
		}
		$this->moduleInfo = $moduleInfo;
			
		$columnsConfigNode = $this->firstNode->getElement('COLUMNS');	
		if($columnsConfigNode){
			$columnsNodesArray = $columnsConfigNode->getElements('COLUMN');
			$this->columns = array();
			foreach($columnsNodesArray as $columnNode){
				$this->columns[]=$columnNode->valueOf();
			}
		}
		
		$template = $this->firstNode->getElement('TEMPLATE');
		if($template){
			$mod_name = strtoupper($this->moduleInfo->name);
			$this->template = new XML('<'.$mod_name.'>'.$template->toString('./*').'</'.$mod_name.'>');
		}
		
		$if_exists = $this->firstNode->valueOf('@if-exists');
		if(!$if_exists)
			$if_exists = $this->firstNode->valueOf('@if_exist');
		if(!$if_exists)
			$if_exists = $this->firstNode->valueOf('@if-exist');
		if($if_exists!==false)
			$this->if_exists = $if_exists;
		return true;
	}
	
	function operate(){
		$first = true;
		$mod_name = strtoupper($this->moduleInfo->name);
	
		if($this->template)
			$base_xml = $this->template;
		else
			$base_xml = new XML('<'.$mod_name.'/>');

		$path_for_col = array();
		$attr_col = array();
		$ID_col = false;
		$bad_structure = false;
		$line = 0;

		// open the original CSV file
		$this->file->open();
		
		// the file that will contain the final NQL
		$nqlFile = new TempFile();
		$nqlFile->setExtension('nql');
		$nqlFile->append('<?xml version="1.0"?><QUERY>');

		while ($data = $this->file->getNextLine())
		{
			// empty values are not in array: get the last index + 1 (because index start at 0)
			$num = array_search(end($data),$data) + 1;
			
			if($first==true)
			{
				if($this->columns)
				{
					$data = $this->columns;
					$num = sizeof($data);
				}

				// we determine the config
				for ($i=0; $i < $num; $i++)
				{
					$col = trim($data[$i]);
					if ($col!='')
					{
						if ($col!='ID' && $col!='INFO.ID')
						{
							$nodes = explode(".",$col);
							$base_path = '/'.$mod_name.'[1]';
							$depth = 1;
							foreach($nodes as $nodename)
							{
								$indexed = false;
								$startBrack = strpos($nodename,'[');
								$attributes='';
								$attr_col[$i]=false;
								if ($startBrack!==FALSE)
									$endBrack = strpos($nodename,']',$startBrack);
								if ($startBrack!==FALSE && $endBrack!==FALSE){
									$index = substr($nodename,$startBrack+1,$endBrack-$startBrack-1);
									if (is_numeric($index)){
										$indexed = true;
									}else if(substr($index,0,1)==='@' ){ // identifying attribute --> will be useful for descriptions and maybe later dependencies
										$end_attr=strpos($index,"='");
										if($end_attr!==false){
											$attribute_name=substr($index,1,$end_attr-1);
											$end_value = strpos($index,"'",$end_attr+3);
											if($end_value!==false){
												$attribute_value=substr($index,$end_attr+2,$end_value - $end_attr-2);
												$attributes.=$attribute_name.'="'.$attribute_value.'" ';
											}
										}
									}
									$short_nodename = substr($nodename,0,$startBrack);
								}else
									$short_nodename = $nodename;
								if(!$base_xml->match($base_path.'/'.$nodename)){
									if(substr($short_nodename,0,1)=='@'){
										$attribute_name = substr($short_nodename,1);
										$base_xml->setAttribute($base_path,$attribute_name,"");
										$indexed=true;
										$attr_col[$i]=$attribute_name;
									}else{
										$base_xml->appendChild($base_path,'<'.$short_nodename.' '.$attributes.'/>');
									}
								}
								if($indexed)
								$base_path.='/'.$nodename;
								else
								$base_path.='/'.$nodename.'[1]';
								$depth++;
							}
							$path_for_col[$i]=$base_path;
							
						}else{
							$ID_col = $i; 
						}
					}
				}
				if(sizeof($path_for_col)===0){
					$bad_structure = true;
					break;
				}
				if(!$base_xml->match('/*[1]/*')){
					$bad_structure = true;
					break;
				}
				$col_number = sizeof($path_for_col);
				if($ID_col!==false)$col_number++;
				$first=false;
				$this->log("Kernel/private/importCSV.inc.php : model ".$base_xml->toString());
				$base_xml_str = $base_xml->toString();
				$base_xml = null;
				unset($base_xml);
			}
			else if($data!==null)
			{
				$query_xml = new XML($base_xml_str);

				if($ID_col!==false && $data[$ID_col]!='')
				{
					$query.='<UPDATE name="line'.$line.'">';
					$final_tag = '</UPDATE>';
					$query_xml->setAttribute('/'.$mod_name.'[1]','ID',$data[$ID_col]);
					$query_xml->setAttribute('/'.$mod_name.'[1]','if-exist',$this->if_exists);
				}
				else
				{
					$query.='<CREATE name="line'.$line.'" >';
					$final_tag = '</CREATE>';
					$query_xml->removeAttribute('/'.$mod_name.'[1]','ID');
					$query_xml->setAttribute('/'.$mod_name.'[1]','if-exist',$this->if_exists);
				}

				$err = 0;

				for ($i=0; $i < $num; $i++)
				{
					if( ($ID_col!==false && $i!==$ID_col) || $ID_col === false )
					{
						if(isset($path_for_col[$i]) && $path_for_col[$i]!='')
						{
							if(isUtf8($data[$i]))
							{
								$data_to_encode = utf8_To_UnicodeEntities($data[$i]);
							}
							else
							{
								$data_to_encode = iso_To_UnicodeEntities($data[$i]);
							}

							$data_to_encode = $this->clean_string_input($data_to_encode);

							if(substr($path_for_col[$i],0,17)=='INFO.DENOMINATION')
							{
								$data_to_encode = str_replace("\n",' ',$data_to_encode);
							}

							if($attr_col[$i]!==false)
							{
								$res = $query_xml->setAttribute($path_for_col[$i]."/..",$attr_col[$i],encode_to_xml($data_to_encode));
							}
							else if(substr($data_to_encode,0,5)=='<CSS>' || substr($path_for_col[$i],-6)=='CUSTOM' || substr($path_for_col[$i],-9)=='CUSTOM[1]')
							{
								$res = $query_xml->replaceData($path_for_col[$i], $data_to_encode);
							}
							else
							{
								if(!$data_to_encode && $query_xml->valueOf($path_for_col[$i])!='')
								{
									// do nothing, leaving the default value
								}
								else
								{
									$res = $query_xml->replaceData($path_for_col[$i], encode_to_xml($data_to_encode));
								}
							}

							if($res===false)
							{
								$this->log("Kernel/private/importCSV.inc.php : col ".$i." replaceData failed on ".$path_for_col[$i]." with data ".encode_to_xml($data_to_encode));
								$this->log($query_xml->getLastError());
								$err++;
								break;
							}
						}
					}
				}

				if($err>0)
				{
					$bad_structure = true;
					break;
				}

				$query.=$query_xml->toString('/','');
				$query.=$final_tag;
				$nqlFile->append($query);

				// freeing memory
				$query = null;
				$query_xml = null;
				$data = null;

				unset($query);
				unset($query_xml);
				unset($data);

				$query = '';
			}
			else
			{
				// do nothing, skipping empty line
			}
			$line++;
		}
		$nqlFile->append('</QUERY>');

		$xml = '';
		$xml.='<NQL>'.$nqlFile->getPath().'</NQL>';

		$this->xml = $xml;
		return true;
	}

	function clean_string_input($input)
	{
	   $search = array(
	       '/[\x60\x82\x91\x92\xb4\xb8]/i',            // single quotes
	       '/[\x84\x93\x94]/i',                        // double quotes
	       '/[\x85]/i',                                // ellipsis ...
	       '/[\x00-\x0c\x0b\x0c\x0e-\x1f\x7f-\x9f]/i'  // all other non-ascii
	   );
	   $replace = array(
	       '\'',
	       '"',
	       '...',
	       ''
	   );
	   return preg_replace($search,$replace,$input);
	}
}