<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createArchive.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/file.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');

/*
<CREATE>
   <ARCHIVE include-files="true/false" languageID="eng" source-folder="/Public/" include-library="true/false">
      <QUERY>
         <SEARCH></SEARCH>
      </QUERY>
   </ARCHIVE>
</CREATE>


*/

class createArchive extends RetrieveOperation{
	
	var $includeFiles = true;
	var $includeLibrary = false;
	var $sourceFolder = false;
	var $languageID = false;
	var $queryNode = false;
	
	function parse(){
		
		$this->queryNode = $this->firstNode->getElement('QUERY');
		
		
		$sourceFolderPath = $this->firstNode->valueOf('@source-folder');
		if($sourceFolderPath){
			$this->sourceFolder = new KernelFile($sourceFolderPath);
			if(!$this->sourceFolder->exists())
				$this->sourceFolder = false;
		}
		
		$languageID = $this->firstNode->valueOf('@languageID');
		if($languageID == 'all')
			$languageID = false;
		$this->languageID = $languageID;
		
		
		$includeFiles = $this->firstNode->valueOf('@include-files');
		if($includeFiles==='false')
			$includeFiles = false;
		else
			$includeFiles = true;
		$this->includeFiles = $includeFiles;
		
		$this->includeLibrary = $this->firstNode->valueOf('@include-library');
		if($this->includeLibrary==='true')
			$this->includeLibrary = true;
		else
			$this->includeLibrary = false;
		
		return true;
	}
	
	function operate(){
		$tmpDir = new TempDirectory();
		$tmpDir->create();
		if($this->sourceFolder){
			$rootDir = $tmpDir->createDirectory($this->sourceFolder->getName());
			$dataDir = $rootDir->createDirectory('data');
			$zipDir = $rootDir;
		}else{
			$rootDir = &$tmpDir;
			$dataDir = $rootDir->createDirectory('data');
			$zipDir = &$dataDir;
		}
		
		if($this->includeFiles){
			$filesDir = $dataDir->createDirectory('Files');
		}
		$nql = new NQL(false);
		$nql->includeSuppParams(false);
		$nql->addCommand(
			'<GET>
				<LANGUAGES profile="Media"/>
			</GET>');
		$nql->execute();
		
		if($this->languageID){
			$lgStr = '<?xml version="1.0" encoding="utf-8"?><RESPONSE><RESULTS>'.$nql->copyOf("/RESPONSE/RESULTS/LANGUAGE[@ID='".$this->languageID."']").'</RESULTS></RESPONSE>';
			$lgNodes = $nql->getElements("/RESPONSE/RESULTS/LANGUAGE[@ID='".$this->languageID."']");
		}else{
			$lgStr = $nql->getResponse();
			$lgNodes = $nql->getElements("/RESPONSE/RESULTS/LANGUAGE");
			
			$lgFile = $dataDir->createFile('languages.xml');
			$lgFile->save($lgStr);
		}
		
		
		$nql->reset();
		if($this->queryNode){
			if($this->includeFiles){
				$used_files = array();
				$GLOBALS['used_Files'] = array();
			}
			foreach($lgNodes as $lgNode){
				$lgID = $lgNode->valueOf('@ID');

				$nql->setLanguage($lgID);
				
				$dataFile = $dataDir->createFile('data_'.$lgID.'.xml');
				// user already paginating, we can leave the request as it is
				if($this->queryNode->exists('PAGINATE')){
					$nql->addCommands(
						$this->queryNode->getElements('./*')
						);
					$nql->execute();

					
					$queryResponse = $nql->getResponse();
					$dataFile->save($queryResponse);
				}else{
					// forcing the pagination to be sure the execution will succeed
					$page = 1;
					$searchNodes = $this->queryNode->getElements('*');
					foreach($searchNodes as $searchNode){
						$searchNode->appendChild('<PAGINATE display="1000" page="'.$page.'"/>');
					}
					
					$namespaces = new NamespaceCollection();
					$namespaces_str = $namespaces->getXMLHeader();
					$dataFile->append(SUSHEE_XML_HEADER.'<RESPONSE'.$namespaces_str.'>');
					// executing the request foreach page until no more results
					do{
						$nql->addCommands(
							$this->queryNode->getElements('./*')
							);
						$nql->execute();
						$queryResponse = $nql->copyOf('/RESPONSE/RESULTS');
						$dataFile->append($queryResponse);
						
						// going one page further
						$page++;
						foreach($searchNodes as $searchNode){
							$searchNode->getElement('PAGINATE')->setAttribute('page',$page);
						}
					}while($nql->exists('/RESPONSE/RESULTS/*'));
					$dataFile->append('</RESPONSE>');
				}
				
				
				$nql->reset();
				if($this->includeFiles){
					$used_files = array_merge($used_files,$GLOBALS['used_Files']);
				}
			}
		}
		if($this->includeFiles){
			// copying all files in the archive directory
			$skipped_files = array();
			$this->copy_published_content(getFilesRoot(),$filesDir->getCompletePath(),$used_files,$skipped_files);
			if(sizeof($skipped_files)>0){
				$html = '<html><body><h1>Missing files (too large to be zipped) : download them separately</h1><ul>';
				foreach($skipped_files as $short_path){
					$html.='<li><a href="'.$GLOBALS["backoffice_url"].'file/file_download.php?target='.$short_path.'">'.BaseFilename($short_path).'</a> to download separately in directory <strong>data/Files'.getShortPath(dirname($directoryRoot.$short_path)).'</strong></li>';
				}
				$html.= '</ul></body></html>';
				$missingTextFile = $dataDir->createFile('missing_files.html');
				$missingTextFile->save($html);
			}
		}
		if($this->includeLibrary){
			$libMediaCSSFileOrig = new KernelFile('/Library/media/css/default.css');
			if ($libMediaCSSFileOrig->exists()){
				$libDir = $dataDir->createDirectory('Library');
				$libMediaDir = $libDir->createDirectory('media');
				$libMediaCssDir = $libMediaDir->createDirectory('css');
				$libMediaCssFileCopy = $libMediaCssDir->createFile('default.css');
				$libMediaCSSFileOrig->copy($libMediaCssFileCopy);
			}
		}
		
			
		if ($this->sourceFolder && $this->sourceFolder->exists()){
			copy_content($this->sourceFolder->getCompletePath(),$rootDir->getCompletePath());
		}
			
		
		$zipFile = $zipDir->compress();
		if(!$zipFile){
			$this->setError('Problem with the compression of the package');
			return false;
		}
		$zipDir->delete();
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		$xml.= 		'<ARCHIVE'.((sizeof($skipped_files)>0)?' missing_files="true"':'').'>'.$zipFile->getPath().'</ARCHIVE>';
		$xml.='</RESULTS>';
		$this->setXML($xml);
		return true;
	}
	
	function copy_published_content($source_dir,$target_dir,&$published_files,&$skipped_files){
		$source_dir = realpath($source_dir)."/";
		$target_dir = realpath($target_dir)."/";
		if (!file_exists($source_dir) || !file_exists($target_dir))
			return FALSE;
		if ($dir = @opendir($source_dir)) {
			$short_dir_path = getShortPath($source_dir);
			while($file = readdir($dir)) {
				$isFileVisible = true;
				if($file == "." || $file == ".." )
					$isFileVisible = false;
				if ($isFileVisible){
					if (is_dir($source_dir.$file)){
						$copy = false;
						// checking at least one of the published files is in the dir
						$short_dir = getShortPath($source_dir.$file);
						$short_dir_sz = strlen($short_dir);
						foreach($published_files as $pub_file){
							if ($short_dir==substr($pub_file,0,$short_dir_sz)){
								$copy=true;
								break;
							}
						}
						if ($copy){
							makeDir($target_dir.$file);
							$this->copy_published_content($source_dir.$file,$target_dir.$file,$published_files,$skipped_files);
						}
					}else{
						$short_path = getShortPath($source_dir.$file);
						if ( in_array ($short_path, $published_files) || in_array ($short_dir_path, $published_files)){
							if(filesize($source_dir.$file)>10240000)
								$skipped_files[]=$short_path;
							else
								copy($source_dir.$file,$target_dir.$file);
						}
					}
				}
			}
		}else
			return FALSE;
	}
}

?>
