<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createCSV.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__)."/../common/file.class.php");

class createCSV extends RetrieveOperation{
	
	var $filename;
	var $template;
	var $separator = ';';

	function parse(){
		global $slash;
		
		$short_template = $this->firstNode->valueOf('/@template');
		if(!$short_template)
		{
			$short_template = '/'.Sushee_dirname.'/templates/generic_csv.xsl';
		}
		$template = $GLOBALS["nectil_dir"].$short_template;
		if(!file_exists($template))
		{
			$template = getcwd().$slash.$short_template;
		}
		if(!file_exists($template))
		{
			$this->setError("The template $short_template indicated doesn't exist");
			return false;
		}
		$this->template = $template;

		$separator = $this->firstNode->valueOf('/@separator');
		if($separator!==false)
		{
			$this->separator = $separator;
		}

		$filename = $this->firstNode->valueOf('/@filename');
		if($filename!==false)
		{
			$this->filename = $filename;
		}

		return true;
	}
	
	function operate(){
		$origin_xml = $this->firstNode->copyOf('/QUERY');
		
		$GLOBALS["category_complete"] = true;
		$result = $this->adapted_query($origin_xml);
		$GLOBALS['use_libxslt']=true;
		$csv_str = transform_to_text($result,$this->template,array('separator'=>$this->separator));

		if($csv_str === false)
		{
			$this->setError("Generation of CSV failed");
			return false;
		}
		else
		{
			if ($this->filename)
			{
				$filepath =  '/tmp/'.$this->filename.'.csv';
				$tmpfile = new File($filepath);
			}
			else
			{
				$tmpfile = new TempFile();
				$tmpfile->setExtension('csv');
			}
			
			if ($this->firstNode->valueOf('/@encoding') == 'utf-8')
			{
				$tmpfile->save($csv_str);
			}
			else
			{
	 			$multiletter_search = array("&#8212;","&#179;","&#178;","&#176;","&#180;","&#187;","&#171;","&#169;","&#8221;","&#8220;","&#160;","&#8211;","&#8216;","&#8217;","&#339;","&#230;","&#8230;","&#8364;","&#8226;","&#367;","&#269;","&#345;","&#253;","&#382;","&#283;","&#353;","&#337;");
	 			$multiletter_replace = array("-","3","2","o","'","\"\"","\"","c","\"\"","\"\""," ","-","'","'","oe","ae","...","euro","*","u","c","r","y","z","e","s","o");
	 
	 			for($i=0;$i<sizeof($multiletter_search);$i++){
	 				$multiletter_search[$i] = UnicodeEntities_To_utf8($multiletter_search[$i]);
	 			}
	 			$csv_str = str_replace($multiletter_search,$multiletter_replace,$csv_str);

				$tmpfile->save(utf8_decode($csv_str));
				//$tmpfile->save($csv_str);
			}

			$xml = '';
			$attributes = $this->getOperationAttributes();
			$xml.='<RESULTS'.$attributes.'>';
			$xml.=	'<CSV>'.encode_to_xml($tmpfile->getPath()).'</CSV>';
			$xml.='</RESULTS>';
			$this->setXML($xml);
			
			return true;
		}
		
	}
	
	function adapted_query($stringofXML){
		if($GLOBALS['php_request']==true)
			$result = query($stringofXML,false);
		else
			$result = flash_query($stringofXML);
		return $result;
	}
}
?>
