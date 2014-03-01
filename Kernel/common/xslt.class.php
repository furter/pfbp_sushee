<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/xslt.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/commandline.class.php");
require_once(dirname(__FILE__)."/../common/exception.class.php");
require_once(dirname(__FILE__)."/../common/xslt.functions.php");

class SusheeXSLTProcessor extends SusheeObject{
	
	var $params = array();
	
	function execute($xml){}
	
	function setTemplate($template){
		$this->template = $template;
	}
	
	function setParams($params){
		$this->params = $params;
	}
	
	function outputError($boolean){
		$this->output_error = $boolean;
	}
}

class SaxonXSLTProcessor extends SusheeXSLTProcessor
{
	function execute($xml)
	{
		global $slash;
		
		Sushee_Timer::lap('Start XSL (Saxon) transformation for: ' . $this->template);
		
		$template = $this->template;
		$html_on_error = $this->output_error;
		
		makeDir($GLOBALS["directoryRoot"].$slash."tmp");
		$tmpdir = $GLOBALS["directoryRoot"].$slash."tmp".$slash;
		$microtime = str_replace('.','', getmicrotime());
		$tmp_xml_file = $tmpdir.$microtime.'.xml';
		while(file_exists($tmp_xml_file)){
			$index++;
			$tmp_xml_file = $tmpdir.$microtime.'-'.$index.'.xml';
		}
		saveInFile($xml,$tmp_xml_file);
		
		$java = makeExecutableUsable($GLOBALS["javaExecutable"]);
		$saxon_jar = realpath(dirname(__FILE__).'/../common/saxon/saxon8.jar');
		$command = $java."  -jar \"".$saxon_jar."\" -novw -s \"".$tmp_xml_file."\" \"".$template."\"  2>&1 ";
		
		debug_log($command);
		
		$command = batchFile($command);
		
		$html_array = array();
		session_write_close();
		exec($command,$html_array,$res);
		$result_xsl = implode("\n",$html_array);
		
		//debug_log($html_array[0]);
		
		$html = str_replace('<br></br>','<br/>',$result_xsl);
		
		unlink($tmp_xml_file);
		if($html_on_error===false && $res>0)
		{
			return false;
		}
		else
		{
			Sushee_Timer::lap('End XSL (Saxon) transformation for: ' . $this->template);
			return $html;
		}
	}
}

class SablotronXSLTProcessor extends SusheeXSLTProcessor
{
	function execute($xml)
	{
		Sushee_Timer::lap('Start XSL (Sablotron) transformation for: ' . $this->template);
		
		$html_on_error = $this->output_error;
		$template = $this->template;
		$params = $this->params;
		$arguments = array('/_xml' => $xml);
		$xsltproc = xslt_create();
		
		if($xsltproc===false)
			return false;
		if($html_on_error===true)
		   xslt_set_error_handler($xsltproc, "xslt_error_handler");

		xslt_set_base ( $xsltproc, $fileBase );
		
		if (getServerOS()=='windows')
		   $template = "file://".$template;
		
		$html = xslt_process($xsltproc, 'arg:/_xml', "$template", NULL, $arguments,$params);
		
		if (empty($html))
		{
			if($html_on_error===true)
			{
				$xslt_error = xslt_error($xsltproc);
				xslt_free($xsltproc);
				die('XSLT processing error: '.$xslt_error );
			 }
			else return false;
		}
		xslt_free($xsltproc);
		
		Sushee_Timer::lap('End XSL (Sablotron) transformation for: ' . $this->template);
	}
}

class LibXSLTProcessor extends SusheeXSLTProcessor
{
	function execute($xml)
	{
		Sushee_Timer::lap('Start XSL (LibXSLT) transformation for: ' . $this->template);
		
		$html_on_error = $this->output_error;
		$more_params = $this->params;
		
		// preparing executable and params
		$xsltproc = "xsltproc";
		if(getServerOS()=='windows' && file_exists(dirname(__FILE__).'/../common/xsltproc/xsltproc.exe'))
			$xsltproc='"'.realpath(dirname(__FILE__).'/../common/xsltproc/xsltproc.exe').'"';
		if(isset($GLOBALS["xsltproc"]))
			$xsltproc = $GLOBALS["xsltproc"];
		$xsltproc_params = '';
		
		if(is_array($more_params) && sizeof($more_params)>0)
		{
			foreach($more_params as $param_name=>$param_value)
			{
				if($param_name && $param_value && !is_object($param_value))
				{
					$xsltproc_params.='--stringparam "'.encodeQuote($param_name).'" "'.encodeQuote($param_value).'" ';
				}
			}
		}
		
		global $slash;
		makeDir($GLOBALS["directoryRoot"].$slash."tmp");
		$tmpdir = $GLOBALS["directoryRoot"].$slash."tmp".$slash;
		$microtime = str_replace('.','', getmicrotime());
		$tmp_xml_file = $tmpdir.$microtime.'.xml';
		while(file_exists($tmp_xml_file))
		{
			$index++;
			$tmp_xml_file = $tmpdir.$microtime.'-'.$index.'.xml';
		}
		saveInFile($xml,$tmp_xml_file);
		$command = "$xsltproc ".$xsltproc_params." \"".$this->template."\" \"".$tmp_xml_file."\"  2>&1";
		
		$command = batchFile($command);
		$html_array = array();
		session_write_close();
		exec($command,$html_array,$res);
		session_start();
		$result_xsl = implode("\n",$html_array);
		
		unlink($tmp_xml_file);
		
		// managing errors
		if($res>0)
		{
			$line = false;
			$xsl_str = file_in_string($this->template);
			$xsl_str = str_replace("\r\n","\n",$xsl_str);
			$xsl_lines = explode("\n",$xsl_str);
			$line_pos = strpos($result_xsl,'line ');
			if($line_pos){
				$space_pos = strpos($result_xsl,' ',$line_pos+6);
				if(!$space_pos)
					$space_pos = strpos($result_xsl,"\n",$line_pos+6);
				if($space_pos)
					$line = substr($result_xsl,$line_pos+5,$space_pos-$line_pos-5);
			}
			if($line){
				$intro_msg = 'Error on line '.$line.' in file '.$this->template;
			}else{
				$intro_msg = 'Error in file '.$this->template;
			}
			$main_error_msg = str_replace('^','<br/>',encode_to_XML($result_xsl));
			$separator = "<br/>\r\n";
			$e = new SusheeXSLTException('<strong>'.$intro_msg.'</strong>'.$separator.$main_error_msg.' : '.$separator.$separator.$xsl_lines[$line-1].'<em>'.$separator.$xsl_lines[$line].'</em>'.$separator.$xsl_lines[$line+1]);
			throw $e;
		}
		else
		{
			$html= str_replace('<br></br>','<br/>',$result_xsl);

			Sushee_Timer::lap('End XSL (LibXSLT) transformation for: ' . $this->template);
			return $html;
		}
	}
}

class PHPXSLTProcessor extends SusheeXSLTProcessor
{
	function execute($xml)
	{
		Sushee_Timer::lap('Start XSL (PHP) transformation for: ' . $this->template);
		
		// Configuration du transformateur
		libxml_use_internal_errors(true);
		$xmldoc = new DOMDocument;
		$res = $xmldoc->loadXML($xml);		
		if ($res === false)
		{
			$this->handleXMLError();
		}

		$xsl = new DOMDocument;
		$res = $xsl->load($this->template);
		if ($res === false)
		{
			$this->handleXMLError();
		}

		$proc = new XSLTProcessor;
		$proc->registerPHPFunctions(); // allow xsl to use sushee functions
		$proc->importStyleSheet($xsl); // attachement des règles xsl

		foreach ($this->params as $key=>$value)
		{
			$proc->setParameter('', $key, $value);
		}

		$html = $proc->transformToXML($xmldoc);
		if ($html === false || libxml_get_errors())
		{
			$this->handleXMLError();
		}
		
		Sushee_Timer::lap('End XSL (PHP) transformation for: ' . $this->template);
		return $html;
	}
		
	protected function handleXMLError()
	{
		$err_msg = $this->formatXMLErrorMsg();
		throw new SusheeXSLTException($err_msg);
	}
	
	protected function formatXMLErrorMsg()
	{
		// xsl code
		$xsl_str = file_in_string($this->template);
		$xsl_str = str_replace("\r\n","\n",$xsl_str);
		$xsl_str = str_replace("\r","\n",$xsl_str);
		$xsl_lines = explode("\n",$xsl_str);
		
		$errors = libxml_get_errors();
		foreach ($errors as $err) {
			$err_level = '';
			switch ($err->level)
			{
				case LIBXML_ERR_WARNING:
					$err_level = 'XSL Warning';
					break;

				case LIBXML_ERR_FATAL:
					$err_level = 'XSL Fatal error';
					break;

				case LIBXML_ERR_ERROR:
					$err_level = 'XSL Error';
					break;

				default:	
					$err_level = 'XSL Undefined error';
					break;
			}
	        $file = (empty($err->file)) ? $this->template : $err->file;
			$details = (($err->line == 0) && ($err->column == 0)) ? '' : '<strong>line '.$err->line.'</strong> column '.$err->column;

			$err_msg.= '<strong>'.$err_level.'</strong> in '.$file.' '.$details.':<br /><em>'.$err->message.'</em><br/>';
			
			$err_msg.='<br/>';
	    }
		libxml_clear_errors();
		return $err_msg;
	}
}

class SusheeXSLTransformer extends SusheeObject
{
	var $processor;
	var $template;
	var $engine;
	var $output_error = true;

	function SusheeXSLTransformer(){}

	function setProcessor($processor)
	{
		$this->processor = $processor;
	}

	function setTemplate($template)
	{
		$this->template = $template;
	}

	function setEngine($engine)
	{
		$this->engine = $engine;
		
		if ($engine == 'sablotron')
		{
			$this->setprocessor(new SablotronXSLTProcessor());
		}
		else if($engine == 'saxon')
		{
			if (function_exists('exec'))
				$this->setprocessor(new SaxonXSLTProcessor());
			else
				throw new SusheeXSLTException('<strong>XSLT Engine Error: </strong><em>Saxon</em> cannot be executed (exec function not available)');
		}
		else if($engine == 'phpxslt')
		{
			if (extension_loaded('xsl'))
				$this->setprocessor(new PHPXSLTProcessor());
			else
				throw new SusheeXSLTException('<strong>XSLT Engine Error: </strong><em>PHP XSL Extension</em> not installed');
		}
		else if ($engine == 'libxslt')
		{
			if (function_exists('exec'))
				$this->setprocessor(new LibXSLTProcessor());
			else
				throw new SusheeXSLTException('<strong>XSLT Engine Error: </strong><em>libxslt</em> cannot be executed (exec function not available)');
		}
		else
		{
			throw new SusheeXSLTException('<strong>'.$intro_msg.'</strong>'.$separator.$main_error_msg.' : '.$separator.$separator.$xsl_lines[$line-1].'<em>'.$separator.$xsl_lines[$line].'</em>'.$separator.$xsl_lines[$line+1]);
		}
	}

	function execute($xml)
	{
		if(is_object($this->processor) && $this->template)
		{
			$this->processor->setParams($this->params);
			$this->processor->setTemplate($this->template);
			$this->processor->outputError($this->output_error);
			return $this->processor->execute($xml);
		}
	}
	
	function outputError($boolean)
	{
		$this->output_error = $boolean;
	}
	
	function setParams($params)
	{
		$this->params = $params;
	}
}