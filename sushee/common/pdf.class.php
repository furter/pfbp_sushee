<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/pdf.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/commandline.class.php");

class PDFFile extends File{
	var $ID = false;
	
	function PDFFile($ID){
		$this->ID = $ID;
		// folder where we create and cache the pdf files
		$folder = new Folder('/pdf/');
		if(!$folder->exists()){
			$folder->create();
		}
	}
	
	function getPath(){
		return '/pdf/'.$this->ID.'.pdf';
	}
}

class XSLFoGenerator extends NectilObject{
	var $fo_file = false;
	
	function XSLFoGenerator(){
		
	}
	
	function getFile(){
		return $this->fo_file;
	}
	
	function execute($xml){
		$this->fo_file = new TempFile();
		$this->fo_file->setExtension('fo');
		
		$fo_str = real_transform($xml,$this->template,array(),false,true);
		if($fo_str===false){
			return false;
		}else{
			$this->fo_file->save($fo_str);
			return true;
		}
		
	}
	
	function setTemplate($template){
		$this->template = $template;
	}
}

class SusheePDFGenerator extends NectilObject{
	var $file = false;
	var $fo_file = false;
	var $xml = false;
	var $generator = false;
	var $cache = true;
	
	function SusheePDFGenerator(){
	}
	
	function setTemplate($template){
		$this->template = $template;
	}
	
	function setCacheMode($cache){
		$this->cache = $cache;
	}
	
	function execute($xml){
		// $this->logFunction('execute');
		$XSLFoGen = new XSLFoGenerator();
		$XSLFoGen->setTemplate($this->template);
		$res = $XSLFoGen->execute($xml);
		if(!$res){
			return false;
		}
		$this->fo_file = $fo_file = $XSLFoGen->getFile();
		if(!$fo_file){
			return false;
		}
		$fo_str = $this->fo_file->toString();
		
		$pdfID = md5($fo_str);
		$this->file = new PDFFile($pdfID);
		
		if($this->cache){
			if($this->file->exists()){
				return true;
			}
		}
		
		if($this->generator){
			$this->generator->setPDF($this->file);
			$this->generator->setFo($this->fo_file);
			
			return $this->generator->execute();
		}else{
			return false;
		}
	}

	function getFo(){
		return $this->fo_file;
	}
	
	function getFile(){
		return $this->file;
	}
	
	function setPDFGenerator($generator){
		$this->generator = $generator;
	}
}

class FopPDFGenerator extends NectilObject{
	function FopPDFGenerator(){
		
	}
	
	function execute(){
		// $this->logFunction('execute');
		
		$fopCmd = new FopCommandLine();
		
		if($fopCmd->isNewFopVersion())
		{
			$cfg = new FopNewConfigGenerator();
			$cfg->execute();
			$config_file = $cfg->getFile();
			if(!$config_file){
				return false;
			}
			
			$cmd = 'org.apache.fop.cli.Main -c "'.$config_file->getCompletePath().'"  -fo "'.$this->fo_file->getCompletePath().'" -pdf "'.$this->pdf_file->getCompletePath().'"';
		}
		else
		{
			$cfg = new FopConfigGenerator();
			$cfg->execute();
			$config_file = $cfg->getFile();
			if(!$config_file){
				return false;
			}
			
			$cmd = 'org.apache.fop.apps.Fop -c "'.$config_file->getCompletePath().'"  -fo "'.$this->fo_file->getCompletePath().'" -pdf "'.$this->pdf_file->getCompletePath().'"';
		}
		
		$fopCmd->setCommand($cmd);
		return $fopCmd->execute();
	}
	
	function setFo($fo_file){
		$this->fo_file = $fo_file;
	}
	
	function setPDF($pdf_file){
		$this->pdf_file = $pdf_file;
	}
	
	function getFile(){
		return $this->pdf_file;
	}
}

class sushee_RTFGenerator extends SusheeObject{
	
	var $file = false;
	var $fo_file = false;
	var $xml = false;
	var $cache = true;
	
	function setTemplate($template){
		$this->template = $template;
	}
	
	function setCacheMode($cache){
		$this->cache = $cache;
	}
	
	function execute($xml){
		$XSLFoGen = new XSLFoGenerator();
		$XSLFoGen->setTemplate($this->template);
		$res = $XSLFoGen->execute($xml);
		if(!$res){
			return false;
		}
		$this->fo_file = $fo_file = $XSLFoGen->getFile();
		if(!$fo_file){
			return false;
		}
		$fo_str = $this->fo_file->toString();
		
		// output file is a temporary RTF file
		$this->file = new TempFile();
		$this->file->setExtension('rtf');
		
		$this->generator = new sushee_FopRTFGenerator();
		$this->generator->setOutputFile($this->file);
		$this->generator->setFo($this->fo_file);
		
		return $this->generator->execute();
	}
	
	function getFo(){
		return $this->fo_file;
	}
	
	function getFile(){
		return $this->file;
	}
}

class sushee_FopRTFGenerator extends SusheeObject{
	function execute(){
		
		$fopCmd = new FopCommandLine();
		
		if($fopCmd->isNewFopVersion())
		{
			$cfg = new FopNewConfigGenerator();
			$cfg->execute();
			$config_file = $cfg->getFile();
			if(!$config_file){
				return false;
			}
			
			$cmd = 'org.apache.fop.cli.Main -c "'.$config_file->getCompletePath().'"  -fo "'.$this->fo_file->getCompletePath().'" -rtf "'.$this->file->getCompletePath().'"';
		}
		else
		{
			$cfg = new FopConfigGenerator();
			$cfg->execute();
			$config_file = $cfg->getFile();
			if(!$config_file){
				return false;
			}
			
			$cmd = 'org.apache.fop.apps.Fop -c "'.$config_file->getCompletePath().'"  -fo "'.$this->fo_file->getCompletePath().'" -rtf "'.$this->file->getCompletePath().'"';
		}
		
		$fopCmd->setCommand($cmd);
		return $fopCmd->execute();
	}
	
	function setFo($fo_file){
		$this->fo_file = $fo_file;
	}
	
	function setOutputFile($file){
		$this->file = $file;
	}
	
	function getFile(){
		return $this->file;
	}
}

class FopCommandLine extends NectilObject{
	
	var $command;
	var $javaCmd;
	var $new_fop_version;
	
	function FopCommandLine(){
		
		global $directoryRoot;
		global $slash;
		
		$this->javaCmd = $javaCmd = new Sushee_JavaCommandLine();
		
		$new_fop_version = false;
		$fop_dir = new KernelFolder('/fop/');
		if(!$fop_dir->exists()){
			$fop_dir = new KernelFolder('/'.Sushee_dirname.'/pdf_output/');
		}else{
			chdir($fop_dir->getCompletePath());
			$new_fop_version = true;
		}
		$javaCmd->addLibrary($fop_dir->getCompletePath()."build".$slash."fop.jar");
		$lib_dir = $fop_dir->getChild('lib');
		if($lib_dir){
			while($file = $lib_dir->getNextFile()){
				if($file->getExtension()=='jar'){
					$javaCmd->addLibrary($file->getCompletePath());
				}
			}
		}
		
		$this->new_fop_version = $new_fop_version;
	}
	
	function setCommand($command){
		$this->command = $command;
	}
	
	function execute(){
		// $this->logFunction('execute');
		
		$this->javaCmd->setCommand($this->command);
		
		return $this->javaCmd->execute();
	}
	
	function isNewFopVersion(){
		return $this->new_fop_version;
	}
}

class FopConfigGenerator extends NectilObject{
	
	var $file = false;
	
	function FopConfigGenerator(){
		
	}
	
	function execute(){
		// $this->logFunction('execute');
		global $slash;
		
		$fonts_dir = new KernelFolder('/Library/fonts/');
		$new_config_xml ='<?xml version="1.0"?><configuration><entry><key>baseDir</key><value>'.$fonts_dir->getCompletePath().'</value></entry><fonts>';
		if($fonts_dir->exists()){
			$metrics_dir = $fonts_dir->createDirectory('metrics');
			if(!$metrics_dir->exists())
				return false;
			while($file = $fonts_dir->getNextChildren()){
				$font_ext = strtolower($file->getExtension());
				if($font_ext=='ttf' || $font_ext=='pfm' ){
					$metrics_filename = str_replace(' ','',$file->getShortName()).'.xml';
					$metrics_file = $metrics_dir->getChild($metrics_filename);
					
					$font_mtime = filemtime($file->getCompletePath());
					$create = true;
					if($metrics_file->exists() && filemtime($metrics_file->getCompletePath())>$font_mtime)
						$create = false;
					if($create){
						$font_app = 'TTFReader';
						if($font_ext=='pfm')
							$font_app = 'PFMReader';

						$fopCmd = new FopCommandLine();
						$cmd = 'org.apache.fop.fonts.apps.'.$font_app.' "'.$file->getCompletePath().'" "'.$metrics_file->getCompletePath().'" ';

						$fopCmd->setCommand($cmd);
						$fopCmd->execute();
					}
					if($metrics_file->exists()){
						$embed_file = $file->getName();
						if($font_ext=='pfm'){
							$short_name = $file->getShortName();
							if(file_exists($fonts_dir->getCompletePath().$short_name.'.PFB'))
								$embed_file = $short_name.'.PFB';
							else
								$embed_file = $short_name.'.pfb';
						}
						$new_config_xml.='<font metrics-file="metrics'.$slash.$metrics_filename.'" kerning="yes" embed-file="'.$embed_file.'">';
						$without_ext = $file->getShortName();
						$new_config_xml.='<font-triplet name="'.$without_ext.'" style="normal" weight="normal"/>';
						$new_config_xml.='<font-triplet name="'.$without_ext.'" style="italic" weight="normal"/>';
						$new_config_xml.='<font-triplet name="'.$without_ext.'" style="normal" weight="bold"/>';
						$new_config_xml.='<font-triplet name="'.$without_ext.'" style="italic" weight="bold"/>';
						$new_config_xml.='</font>';
					}
				}
			}
		}
		$new_config_xml.='</fonts></configuration>';
		$config_file = $fonts_dir->getChild('userconfig.xml');
		$config_file->save($new_config_xml);
		$this->file = $config_file;
	}
	
	function getFile(){
		return $this->file;
	}
}


class FopNewConfigGenerator extends NectilObject{
	
	var $file = false;
	
	function FopConfigGenerator(){
		
	}

	function execute(){
		// $this->logFunction('execute');
		global $slash;
		
		$fonts_dir = new KernelFolder('/Library/fonts/');
		$fop_dir = new KernelFolder('/fop/');

		$new_config_xml ='<?xml version="1.0"?>
<fop version="1.0">
	<strict-validation>false</strict-validation>
	<base>file:'.$fop_dir->getCompletePath().'</base>
	<font-base>file:'.$fonts_dir->getCompletePath().'</font-base>
	<source-resolution>72</source-resolution>
	<target-resolution>72</target-resolution>
	<default-page-settings height="29.7cm" width="21cm"/>
	<renderers>
		<renderer mime="application/pdf">
			<fonts>
				';

		if($fonts_dir->exists())
		{
			$metrics_dir = $fonts_dir->createDirectory('metrics');
			if(!$metrics_dir->exists())
				return false;
			while($file = $fonts_dir->getNextChildren())
			{
				$font_ext = strtolower($file->getExtension());
				if($font_ext=='ttf' || $font_ext=='pfm' )
				{
					$metrics_filename = str_replace(' ','',$file->getShortName()).'.xml';
					$metrics_file = $metrics_dir->getChild($metrics_filename);

					$font_mtime = filemtime($file->getCompletePath());
					$create = true;
					if($metrics_file->exists() && filemtime($metrics_file->getCompletePath())>$font_mtime)
						$create = false;
					if($create)
					{
						$font_app = 'TTFReader';
						if($font_ext=='pfm')
							$font_app = 'PFMReader';

						$fopCmd = new FopCommandLine();
						$cmd = 'org.apache.fop.fonts.apps.'.$font_app.' "'.$file->getCompletePath().'" "'.$metrics_file->getCompletePath().'" ';

						$fopCmd->setCommand($cmd);
						$fopCmd->execute();
					}
					if($metrics_file->exists())
					{
						$embed_file = $file->getName();
						if($font_ext=='pfm'){
							$short_name = $file->getShortName();
							if(file_exists($fonts_dir->getCompletePath().$short_name.'.PFB'))
								$embed_file = $short_name.'.PFB';
							else
								$embed_file = $short_name.'.pfb';
						}
						$new_config_xml.='<font metrics-url="file:'.$fonts_dir->getCompletePath().'metrics'.$slash.$metrics_filename.'" kerning="yes" embed-url="file:'.$fonts_dir->getCompletePath().$embed_file.'">';
						$without_ext = $file->getShortName();
						$new_config_xml.='<font-triplet name="'.$without_ext.'" style="normal" weight="normal"/>';
						$new_config_xml.='<font-triplet name="'.$without_ext.'" style="italic" weight="normal"/>';
						$new_config_xml.='<font-triplet name="'.$without_ext.'" style="normal" weight="bold"/>';
						$new_config_xml.='<font-triplet name="'.$without_ext.'" style="italic" weight="bold"/>';
						$new_config_xml.='</font>';
					}
				}
			}
		}
		$new_config_xml.='</fonts></renderer></renderers></fop>';
		$config_file = $fonts_dir->getChild('userconfig.xml');
		$config_file->save($new_config_xml);
		$this->file = $config_file;
	}
	
	function getFile(){
		return $this->file;
	}
}

class IbexPDFGenerator extends NectilObject{
	
	function IbexPDFGenerator(){
		
	}
	
	function execute(){
		$cfg = new IbexConfigGenerator();
		$cfg->execute();
		
		$javaCmd = new Sushee_JavaCommandLine();
		$ibex_folder = new KernelFolder('/ibex/');
		chdir($ibex_folder->getCompletePath());
		$jar_file = $ibex_folder->getChild('ibex.jar');
		$javaCmd->addLibrary($jar_file->getCompletePath());
		
		$javaCmd->setCommand('ibex.Run -xml "'.$this->fo_file->getCompletePath().'" -pdf "'.$this->pdf_file->getCompletePath().'"');
		
		return $javaCmd->execute();
	}
	
	
	function setFo($fo_file){
		$this->fo_file = $fo_file;
	}
	
	function setPDF($pdf_file){
		$this->pdf_file = $pdf_file;
	}
	
	function getFile(){
		return $this->pdf_file;
	}
}

class IbexConfigGenerator extends NectilObject{

	function IbexConfigGenerator(){
		
	}
	
	function execute(){
		$ibex_folder = new KernelFolder('/ibex/');
		$config_file = $ibex_folder->getChild('ibexconfig.xml');
		
		$fonts_dir = new KernelFolder('/Library/fonts/');
		
		$new_config_xml ='<?xml version="1.0"?><ibexconfig>';
		if($fonts_dir->exists()){
			while($file = $fonts_dir->getNextChildren())
			{
				$fontfile = $file->getCompletePath();
				$ext = $file->getExtension();
				if ($ext == 'pfb')
				{
					$new_config_xml.='<font name="'.strtolower($file->getShortName()).'" file="'.$file->getShortName().'.pfb" pfm="'.$file->getShortName().'.pfm"/>';
				}
				else if ($ext == 'ttf')
				{
					$new_config_xml.='<font name="'.strtolower($file->getShortName()).'" file="'.$fontfile.'"/>';
				}
			}
		}
		$new_config_xml.='</ibexconfig>';
		
		$config_file->save($new_config_xml);
		$this->file = $config_file;
	}
	
	function getFile(){
		return $this->file;
	}
}