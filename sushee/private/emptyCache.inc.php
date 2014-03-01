<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/emptyCache.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/file.class.php');
require_once(dirname(__FILE__)."/../common/commandline.class.php");

/*
Command to clean the Files/cache folder from responses with certain elements
<EMPTY>
	<CACHE>
		<MODULE></MODULE>
		<ELEMENTID></ELEMENTID>
		<TOOL></TOOL>
		<REGEX></REGEX>
	</CACHE>
</EMPTY>
*/

/*class sushee_regexStringConverter extends SusheeObject{
	static function execute($str){
		$str = str_replace(array('.'),array('\.'),$str);
		return $str;
	}
}*/

class sushee_fileRegexFinder extends SusheeObject{
	
	var $regex = false;
	
	function sushee_fileRegexFinder($regex){
		$this->regex = $regex;
	}
	
	// returns a boolean telling if the regex was found
	function execute($file){
		$file_content = $file->toString();
		$pattern ='/'.$this->regex.'/i';
		// matching at least one of the regex
		if(preg_match($pattern, $file_content)){
			return true;
		}
		return false;
	}
}

define('SUSHEE_OPTIMAL_FREAD_BLOCK',16384);
define('SUSHEE_CACHE_HEADER',32);

class sushee_fileModuleFinder extends SusheeObject{
	
	var $element = false;
	
	function sushee_fileModuleFinder($element){
		$this->element = $element;
		$this->search_element = '<'.$this->element.' ';
	}
	
	function execute($file){
		// the number of characters we have to take from the previous buffer to be sure not to miss an overlapping occurence of the searched element
		$securityBufferZone = strlen($this->search_element);
		$endOfPreviousBuffer = '';
		$file->goToOffset(SUSHEE_CACHE_HEADER); // in front of every cache file there is 22 whitespaces and a 10 cifers code
		while($buffer = $file->readBytes(SUSHEE_OPTIMAL_FREAD_BLOCK)){
			$found = $this->executeOnBuffer($endOfPreviousBuffer.$buffer);
			if($found){
				return true;
			}
			$endOfPreviousBuffer = substr($buffer,-$securityBufferZone);
		}
		
		return false;
	}
	
	function executeOnBuffer($file_content){
		$element_pos = strpos($file_content,$this->search_element);
		//debug_log('checking '.$this->search_element.' in '.$file_content);
		if($element_pos){
			return true;
		}
		return false;
	}
	
}

class sushee_fileToolFinder extends sushee_fileModuleFinder{
	
	function sushee_fileToolFinder($element){
		$this->element = $element;
		$this->search_element = '<'.$this->element.'';
	}
	
}

class sushee_fileIDFinder extends SusheeObject{
	
	var $elementID = false;
	
	function sushee_fileIDFinder($ID){
		$this->elementID = $ID;
		// the exact string we have to look for
		$this->search_ID = ' ID="'.$this->elementID.'"';
	}
	
	function execute($file){
		// the number of characters we have to take from the previous buffer to be sure not to miss an overlapping occurence of the searched element
		$securityBufferZone = strlen($this->search_ID);
		$endOfPreviousBuffer = '';
		$file->goToOffset(SUSHEE_CACHE_HEADER); // in front of every cache file there is 22 whitespaces and a 10 cifers code
		while($buffer = $file->readBytes(SUSHEE_OPTIMAL_FREAD_BLOCK)){
			$found = $this->executeOnBuffer($endOfPreviousBuffer.$buffer);
			if($found){
				return true;
			}
			$endOfPreviousBuffer = substr($buffer,-$securityBufferZone);
		}
		
		return false;
	}
	
	function executeOnBuffer($file_content){
		$element_pos = strpos($file_content,$this->search_element);
		if($element_pos){
			return true;
		}
	}
	
}

class sushee_fileElementFinder extends SusheeObject{
	
	var $element = false;
	var $elementID = false;
	
	function sushee_fileElementFinder($element,$ID){
		$this->element = $element;
		$this->elementID = $ID;
		// the exact string we have to look for
		$this->search_ID = ' ID="'.$this->elementID.'"';
		$this->search_element = '<'.$this->element.' ';
	}
	
	function execute($file){
		// the number of characters we have to take from the previous buffer to be sure not to miss an overlapping occurence of the searched element
		$securityBufferZone = max(strlen($this->search_ID),strlen($this->search_element));
		$endOfPreviousBuffer = '';
		$file->goToOffset(SUSHEE_CACHE_HEADER); // in front of every cache file there is 22 whitespaces and a 10 cifers code
		while($buffer = $file->readBytes(SUSHEE_OPTIMAL_FREAD_BLOCK)){
			$found = $this->executeOnBuffer($file,$endOfPreviousBuffer.$buffer);
			if($found){
				return true;
			}
			$endOfPreviousBuffer = substr($buffer,-$securityBufferZone);
		}
		
		return false;
	}
	
	function executeOnBuffer($file,$file_content){
		// position of the previous element found (to restart the search after it)
		$previous_pos = 0;
		while($element_pos = strpos($file_content,$this->search_element,$previous_pos)){
			if(!$this->elementID){
				return true;
			}else{
				$end_tag_pos = strpos($file_content,'>',$element_pos);
				// not finding the end of the tag, meaning we are at the end of the buffer
				if($end_tag_pos===false){
					$file_content.=$file->readBytes(SUSHEE_OPTIMAL_FREAD_BLOCK);
				}
				$end_tag_pos = strpos($file_content,'>',$element_pos);
				if($end_tag_pos){
					$tag = substr($file_content,$element_pos,$end_tag_pos - $element_pos);
					$ID_pos = strpos($tag,$this->search_ID);
					if($ID_pos!==false){
						return true;
					}
					$previous_pos = $end_tag_pos;
				}else{
					$previous_pos = $element_pos + strlen($this->search_element);
				}
			}
			
		}
		return false;
	}
	
}

class sushee_emptyCache extends NQLOperation{
	function parse(){
		return true;
	}
	
	function operate(){
		$finder_array = array();
		/// giving a module, an elemenID or a regex
		$module = $this->firstNode->valueOf('MODULE');
		$module = strtoupper($module);
		$elementID = (int) $this->firstNode->valueOf('ELEMENTID');
		$given_regex = $this->firstNode->valueOf('REGEX');
		$tool = $this->firstNode->valueOf('TOOL');
		$tool = strtoupper($tool);
		
		$cmd = new Sushee_BackgroundProcess('emptycache');
		$unixCmd = Sushee_instance::getConfigValue('phpExecutable').' "'.dirname(__FILE__).'/../private/emptyCache.php" '.escapeshellarg($module).' '.escapeshellarg($elementID).' '.escapeshellarg($given_regex).' '.escapeshellarg($tool).' ';
		
		$cmd->setCommand($unixCmd);
		$cmd->execute();
		
		$this->setSuccess('Cache cleaning launched : '.$unixCmd);
		
		return true;
	}
}

class sushee_emptyCache_asynchronous extends SusheeObject{
	
	function sushee_emptyCache_asynchronous(){
		
	}
	
	function setModule($module){
		$this->module = $module;
	}
	
	function setRegex($regex){
		$this->regex = $regex;
	}
	
	function setElementID($elementID){
		$this->elementID = $elementID;
	}
	
	function setTool($tool){
		$this->tool = $tool;
	}
	
	function execute(){
		$module = $this->module;
		$given_regex = $this->regex;
		$elementID = $this->elementID;
		$tool = $this->tool;
		
		if($tool){
			// tool name
			$finder = new sushee_fileToolFinder($tool);
		}else if($module && !$elementID){
			// <MODULENAME
			$finder = new sushee_fileModuleFinder($module);
		}else if(!$module && $elementID){
			// ...ID=""...
			$finder = new sushee_fileIDFinder($elementID);
		}else if($module && $elementID){
			// <MODULENAME ... ID=""
			$finder = new sushee_fileElementFinder($module,$elementID);
		}else if($given_regex){
			$finder = new sushee_fileRegexFinder($given_regex);
		}

		// no crits given by user, cleaning all
		$clean_all = (!$module && !$elementID && !$given_regex);

		$deleted_files = 0;

		// iterating in all cache files
		$cache_folder = new Folder('/cache/xsushee/');
		$query_cache_prefix = 'cache_'.md5('subquery').'_';
		$query_cache_prefix_len = strlen($query_cache_prefix);
		while($file = $cache_folder->getNextFile()){
			// its a cache file ?
			if(substr($file->getName(),0,$query_cache_prefix_len)==$query_cache_prefix){ // NQL cache only
				$file_delete = false;
				if($clean_all){
					$file_delete = true;
				}else{
					$file_delete = $finder->execute($file);
				}
				// if file is to delete, because matches the condition
				if($file_delete){
					$file->delete();
					$deleted_files++;
				}
			}
			
		}
	}
}