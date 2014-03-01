<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/csv.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/file.class.php');

class Sushee_CSVOutput extends SusheeObject
{
	var $columnIndexes = array();
	var $allColumns = true;
	var $paging = false;
	var $page = 1;
	var $first_line = false;
	var $last_line = false;
	
	function enableColumn($i)
	{
		if(!$this->returnColumn($i))
		{
			$this->columnIndexes[] = $i;
		}
	}

	function returnColumn($i)
	{
		return $this->allColumns || in_array($i,$this->columnIndexes);
	}

	function enableAllColumns($boolean = true)
	{
		$this->allColumns = $boolean;
	}

	function _computeLimitsLine()
	{
		if($this->getPage() && $this->getPaging())
		{
			$this->last_line = $this->getPage() * $this->getPaging(); 
			$this->first_line = $this->last_line - $this->getPaging();
		}
	}

	function enablePaging($paging = 10)
	{
		$this->paging = $paging;
		$this->_computeLimitsLine();
	}

	function returnPage($page)
	{
		$this->page = $page;
		$this->_computeLimitsLine();
	}

	function getPaging()
	{
		return $this->paging;
	}

	function getPage()
	{
		return $this->page;
	}

	function returnLine($i)
	{
		if(!$this->getPaging())
		{
			return true;
		}

		if($i <= $this->last_line && $i>$this->first_line)
		{
			return true;
		}
		return false;
	}

	function isPageFinished($i)
	{
		// if the lines included in the page are now finished
		if(!$this->getPaging())
		{
			// no pagination, we include every line
			return false;
		}

		if($i > $this->last_line)
		{
			// line is past last line
			return true;
		}

		return false;
	}
}

class Sushee_CSV extends File
{
	var $separator = ';';
	var $enclosure = '"';
	var $rowscount = false;
	var $columnscount = false;
	var $fp = false;
	var $hasHeader = true;
	
	function Sushee_CSV($path)
	{
		File::File($path);
		ini_set('auto_detect_line_endings','1');
		$this->_getSeparator();
	}
	
	function _getSeparator()
	{
		$fp = $this->open();
		$buffer = fgets($fp);
		$this->close();
		$explode_virgule = explode(',',$buffer);
		$explode_pointvirgule = explode(';',$buffer);
		if(sizeof($explode_virgule)>1)
			$separator = ',';
		else if(sizeof($explode_pointvirgule)>1)
			$separator = ';';
		else
			$separator = ','; // one column case
		$this->separator = $separator;
	}
	
	function setSeparator($separator)
	{
		$this->separator = $separator;
	}
	
	function hasHeader($boolean=true)
	{
		$this->hasHeader = $boolean;
	}

	function getStatsXML()
	{
		$xml.='<STATS>';
		$xml.= 	'<ROWS>'.$this->getRowsCount().'</ROWS>';
		$xml.= 	'<COLUMNS>'.$this->getColumnsCount().'</COLUMNS>';
		$xml.='</STATS>';
		return $xml;
	}

	function open()
	{
		$this->fp = @fopen($this->getCompletePath(),'r');
		return $this->fp;
	}

	function close()
	{
		if($this->fp)
		{
			fclose($this->fp);
		}
	}

	function getNextLine()
	{
		if(!$this->fp)
		{
			$this->open();
		}

		if($this->fp)
		{
			// fgetcsv doesn't manage well utf-8, using PHP.NET custom function
			// $line = fgetcsv( $this->fp, 30000 , $this->separator , $this->enclosure );
			$raw_row = fgets($this->fp, 30000);
			$line = $this->getLineFromString($raw_row);
			return $line;
		}
		else
		{
			return false;
		}
	}

	// fgetcsv doesn't manage well utf-8, using PHP.NET custom function
	function getLineFromString(&$string)
	{
		$CSV_SEPARATOR = $this->separator;
		$CSV_ENCLOSURE = $this->enclosure;
		$CSV_LINEBREAK = "\n";
		$o = array();

		$cnt = strlen($string);
		$esc = false;
		$escesc = false;
		$num = 0;
		$i = 0;
		while ($i < $cnt)
		{
			$s = $string[$i];

			if ($s == $CSV_LINEBREAK)
			{
				if ($esc)
				{
					$o[$num] .= $s;
				}
				else
				{
					$i++;
					break;
				}
			}
			elseif ($s == $CSV_SEPARATOR)
			{
				if ($esc)
				{
					$o[$num] .= $s;
				}
				else
				{
					$num++;
					$esc = false;
					$escesc = false;
				}
			}
			elseif ($s == $CSV_ENCLOSURE)
			{
				if ($escesc)
				{
					$o[$num] .= $CSV_ENCLOSURE;
					$escesc = false;
				}
				
				if ($esc)
				{
					$esc = false;
					$escesc = true;
				}
				else
				{
					$esc = true;
					$escesc = false;
				}
			}
			else
			{
				if ($escesc)
				{
					$o[$num] .= $CSV_ENCLOSURE;
					$escesc = false;
				}

				$o[$num] .= $s;
			}
			$i++;
		}

		return $o;
	}
	
	function getDefaultOutput()
	{
		$output = new Sushee_CSVOutput();
		$output->enableAllColumns();
		return $output;
	}
	
	function getColumnsXML($output = false)
	{
		if(!$output)
		{
			$output = $this->getDefaultOutput();
		}
		
		$xml.='<COLUMNS>';
		$fp = $this->open();
		if($fp)
		{
			$line = $this->getNextLine();
			if(is_array($line))
			{
				$i = 1;
				foreach($line as $column)
				{
					if($output->returnColumn($i))
					{
						$xml.='<COLUMN i="'.$i.'">'.encode_to_xml($column).'</COLUMN>';
					}
					$i++;
				}
			}
			$this->close();
		}
		$xml.='</COLUMNS>';
		return $xml;
	}
	
	function getRowsXML($output = false)
	{
		if(!$output)
		{
			$output = $this->getDefaultOutput();
		}

		$xml.= '<ROWS>';
		$fp	= $this->open();
		if($fp)
		{
			$rowscount = 0;

			if($this->hasHeader)
			{
				// skipping header
				$line = $this->getNextLine();
			}

			$cells = $this->getColumnsCount();
			$xml .= '<C>'.$cells.'</C>';

			while ($line = $this->getNextLine())
			{
				$rowscount++;

				// if($output->returnLine($rowscount))
				// {
					$xml .= '<ROW>';

					for ($i = 0 ; $i < $cells ; $i++)
					{
						$cell = trim($line[$i]);
						if($output->returnColumn($i))
						{
							if ($cell)
							{
								$xml .= '<CELL i="'. ($i+1) .'">'. encode_to_xml($cell) .'</CELL>';
							}
							else
							{
								$xml .= '<CELL i="'. ($i+1).'" />';
							}
						}
					}
					$xml .= '</ROW>';
				// }

				if($output->isPageFinished($rowscount))
				{
					break;
				}
			}

			$this->close();
		}
		$xml .= '</ROWS>';
		return $xml;
	}

	function getColumnsCount()
	{
		if(!$this->columnscount)
		{
			$fp = $this->open();
			if($fp)
			{
				$line = $this->getNextLine();
				if($line)
				{
					$this->columnscount = sizeof($line);
				}
				$this->close();
			}
		}
		return $this->columnscount;
	}

	function getRowsCount()
	{
		if(!$this->rowscount)
		{
			$fp = $this->open();
			if($fp)
			{
				// first row does not count, this is the header
				$rowscount = -1;
				while($line = $this->getNextLine())
				{
					$rowscount++;
				}
				$this->rowscount = $rowscount;
				$this->close();
			}
		}
		return $this->rowscount;
	}
}