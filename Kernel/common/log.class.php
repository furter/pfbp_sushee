<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/log.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/date.class.php");

// action types
define('UA_OP_MODIFY','m');
define('UA_OP_APPEND','a');
define('UA_OP_REMOVE','r');

// long versions of action types
define('UA_OP_MODIFY_LONG','modify');
define('UA_OP_APPEND_LONG','append');
define('UA_OP_REMOVE_LONG','remove');

// services
define('UA_SRV_INFO','INFO');
define('UA_SRV_DESC','DESC');
define('UA_SRV_CATEG','CATEG');
define('UA_SRV_DEP','DEP');
define('UA_SRV_COMM','COMM');
define('UA_SRV_OMNI','OMNI');

// long versions of services
define('UA_SRV_INFO_LONG','INFO');
define('UA_SRV_DESC_LONG','DESCRIPTION');
define('UA_SRV_CATEG_LONG','CATEGORY');
define('UA_SRV_DEP_LONG','DEPENDENCY');
define('UA_SRV_COMM_LONG','COMMENT');
define('UA_SRV_OMNI_LONG','OMNILINK');

// element action logs are divided in subdirectories, this defines how many elements in a same directory
define('EA_PAGINATE',100000);


class LogFolder extends Folder
{
	function LogFolder()
	{
		parent::Folder('/logsdev/');
	}
	
	function next()
	{
		$next = parent::next();
		while($next && $next->getExtension()!='log')
		{
			$next = parent::next();
		}

		if($next)
			return $next->casttoclass('LogFile');
		else
			return false;
	}
}

class UserActionLogFolder extends Folder
{
	function UserActionLogFolder()
	{
		parent::Folder('/logs/');
	}
	
	function next()
	{
		$next = parent::next();
		while($next && $next->getExtension()!='log' && $next->getExtension()!='gz')
		{
			$next = parent::next();
		}

		if($next)
			return $next->casttoclass('UserActionLogFile');
		else
			return false;
	}
}

class LogFile extends File
{
	var $maxSize = 1048576;

	function LogFile($path)
	{
		global $directoryRoot;
		if (!is_dir($directoryRoot."/logsdev/"))
		{
			// not using dedicated folder class, because this class uses LogFiles too
			makedir($directoryRoot."/logsdev/");	
		}

		parent::File('/logsdev/'.$path);

		// developer can rise the log max size to read it easier
		$configMaxSize = Sushee_Instance::getConfigParam('LogMaxSize');

		if($configMaxSize)
		{
			$this->setMaxSize($configMaxSize);
		}
	}
	
	function setMaxSize($size)
	{
		$this->maxSize = $size;
	}
	
	function getMaxSize()
	{
		return $this->maxSize;
	}
	
	function log($str)
	{
		if(is_object($str))
		{
			$str = $str->toString();
		}
		
		// log cleanup
		// virer <?xml version="1.0" encoding="utf-8"?

		$str.="\r\n";
		if($this->getMaxSize())
		{
			if($this->getSize() > $this->getMaxSize())
			{
				$this->save($str);
			}
			else
			{
				$this->append($str);
			}
		}
		else
		{
			$this->append($str);
		}
	}
	
}

class Sushee_GZippedLogFile extends File
{
	var $fp = null;

	function Sushee_GZippedLogFile($path)
	{
		parent::File($path);
	}

	function log($str)
	{
		if(is_object($str))
		{
			$str = $str->toString();
		}
		$this->append($str);
	}

	// file descriptor used for appending in a file
	function _openForAppend()
	{
		if($this->file_writer === false)
		{
			if($this->getExtension()=='gz')
			{
				$this->file_writer = @gzopen($this->getCompletePath(), 'ab9');
			}
			else
			{
				$this->file_writer = @fopen($this->getCompletePath(), 'a');
			}
		}
	}

	// appending at the end of the file
	function append($str){
		$this->_openForAppend();
		if($this->file_writer!==false){
			if($this->getExtension()=='gz'){
				gzwrite($this->file_writer, $str);
			}else{
				fwrite($this->file_writer, $str);
			}
			return true;
		}else{
			throw new SusheeException('Could not write log into file `'.$this->getPath().'`');
		}
		return false;
	}
	// file descriptor used for reading (in case of a plaintext file, it can be used to write also, but in the case of a compressed file, writing is write only and reading is read-only)
	function _openForRead(){
		if($this->fp === null){
			if($this->getExtension()=='gz'){
				$this->fp = @gzopen($this->getCompletePath(), 'rb9');
			}else{
				$this->fp = @fopen($this->getCompletePath(), 'r+');
			}
		}
		return $this->fp;
	}
	
	function _openForWrite(){
		if($this->fp === null){
			if($this->getExtension()=='gz'){
				// opening write only, will replace all the content
				$this->fp = @gzopen($this->getCompletePath(), 'wb9');
			}else{
				$this->fp = @fopen($this->getCompletePath(), 'w+');
			}
		}
		return $this->fp;
	}
	
	function close(){
		if($this->getExtension()=='gz'){
			gzclose($this->fp);
		}else{
			fclose($this->fp);
		}
		$this->fp = null;
	}
	
	// saving a whole new content in the file
	function save($str){
		if($this->getExtension()=='gz'){
			$fp = $this->_openForWrite();
			if($fp!==null){
				$this->goToOffset(0);
				$bytes = gzwrite($fp,$str);
			}
		}else{
			$fp = $this->_openForRead();
			if($fp!==null){
				$this->goToOffset(0);
				fwrite($fp,$str);
			}
		}
	}
	
	// lock and unlock are only important in the case of a complete file rewrite/replace
	// lock only works on plaintext files, NOT on compressed files
	function lock(){
		$fp = $this->_openForWrite();
		if($fp!==null){
			flock($fp,LOCK_EX);
		}
	}
	
	function unlock(){
		$fp = $this->_openForWrite();
		if($fp!==null){
			flock($fp,LOCK_UN);
		}
	}
	
	function goToOffset($offset){
		if($this->getExtension()=='gz')
			gzseek($this->fp,$offset);
		else
			fseek($this->fp,$offset);
	}
	// get current reading offset in the file
	function getOffset(){
		if($this->getExtension()=='gz')
			return gztell($this->fp);
		else
			return ftell($this->fp);
	}
	// read the file, get the next line
	function getNextLine(){
		$fp = $this->_openForRead();
		if($fp){
			if($this->getExtension()=='gz')
				$line_str = gzgets( $fp, 30000);
			else
				$line_str = fgets( $fp, 30000);
			if($line_str===false)
				return false;
			// we need to cut the line in cells ourself because the fgetcsv is not available with gz file
			$line = array();
			$comma_pos = 0;
			do{
				if($line_str[$comma_pos+1]=='"'){ // opening quote, thus skipping every comma between the quotes
					if($cell_index==10){
						$quot_ending = '"\r\n'; // last cell, followed by newline
					}else{
						$quot_ending = '",'; // not last cell, followed by the comma separator
					}
					$end_quot_pos = strpos($line_str,$quot_ending,$comma_pos+2);
					// if closing quote is not on this line, looking for it on next line
					while($end_quot_pos===false){
						$next_line = gzgets( $this->fp, 30000);
						if(!$next_line)
							break;
						$line_str.= $next_line;
						$end_quot_pos = strpos($line_str,$quot_ending,$comma_pos+2);
					}
					if($end_quot_pos)
						$next_comma_pos = strpos($line_str,',',$end_quot_pos);
					else
						$next_comma_pos = false;
				}else{
					$next_comma_pos = strpos($line_str,',',$comma_pos+1);
				}
				// if no more comma, going till the end of the line
				if(!$next_comma_pos){
					$next_comma_pos = strlen($line_str);
				}
				// first cell, comma pos doesnt include a comma, so taking from the start
				if(!$comma_pos)
					$cell = substr($line_str,$comma_pos,$next_comma_pos - $comma_pos);
				else
					$cell = substr($line_str,$comma_pos+1,$next_comma_pos - $comma_pos - 1);
				$cell = str_replace(array("\n","\r"),array('',''),$cell); // we correct and set double quotes instead of simple quotes inside the cell content, to be valid csv
				if($cell[0]=='"' && $cell[strlen($cell)-1]=='"'){
					$cell = substr($cell,1,-1);
				}
				$line[]=$cell;
				$cell_index++;
				$comma_pos = $next_comma_pos;
			}while($comma_pos < strlen($line_str));
			
			return $line;
		}else{
			return false;
		}
		
	}
}

class UserActionLogFile extends Sushee_GZippedLogFile{
	
	var $userID = false;
	
	function UserActionLogFile(){
		$user = new NectilUser();
		$this->userID = $user->getID();
		if(!$this->userID){
			$this->userID = 0;
		}
		global $directoryRoot;
		if (!is_dir($directoryRoot."/logs/")) // not using dedicated folder class, because this class uses LogFiles too
			makedir($directoryRoot."/logs/");
		$filename = date('Y-m').'-'.$this->userID.'.log';
		// if file does not exist, beginning to use compression
		if(!file_exists($directoryRoot.$subdirectory.$filename)){
			$filename.='.gz';
		}
		parent::File('/logs/'.$filename);
	}
	
	function log(/* UserActionLog or String */ $what_to_log){
		// object is the sushee object on which the action was operated
		$log_object = false;
		if(is_object($what_to_log)){
			$log_object = $what_to_log->getObject();
			$str = $what_to_log->toString();
		}elseif(is_string($what_to_log)){
			$str = $what_to_log;
		}
		
		$this->append($str);
		
		// commented because we need to wait until the port is done to enable this feature
		if(is_object($log_object)){
			// also logging by elements and by modules
			$logByElt = new Sushee_ElementActionLogFile($log_object->getType(),$log_object->getID());
			$logByElt->log($what_to_log);
			
			$logByModule = new Sushee_ModuleActionLogFile($log_object->getType());
			$logByModule->log($what_to_log);
		}
		
	}
	
	function isUserActionLogFile(){
		$name = $this->getName();
		$general_logs = array('debug.log','sql.log','query.log','response.log','errors.log');
		if( in_array($name,$general_logs) ){
			return false;
		}
		return true;
	}
}



class Sushee_ElementActionLogFile extends Sushee_GZippedLogFile{
	
	var $type = false;
	var $ID = false;
	
	function Sushee_ElementActionLogFile($type,$ID){
		$this->type = $type;
		$this->ID = $ID;
		// 
		global $directoryRoot;
		$subdirectory = "/logs/elements/".$this->type.'/';
		// dividing elements in subdirectories of EA_PAGINATE elements
		$low_limit = (int)(floor($ID / EA_PAGINATE)) * EA_PAGINATE;
		$high_limit = $low_limit + EA_PAGINATE;
		$subdirectory.=$low_limit.'-'.$high_limit;
		if (!is_dir($directoryRoot.$subdirectory)) // not using dedicated folder class, because this class uses LogFiles too
			makedir($directoryRoot.$subdirectory);
		parent::File($subdirectory.'/'.$this->ID.'.log.gz');
	}
	
	
	
}

class Sushee_ModuleActionLogFile extends Sushee_GZippedLogFile{
	
	var $type = false;
	
	function Sushee_ModuleActionLogFile($type){
		$this->type = $type;
		
		global $directoryRoot;
		$subdirectory = "/logs/modules/".$this->type.'/';
		if (!is_dir($directoryRoot.$subdirectory)) // not using dedicated folder class, because this class uses LogFiles too
			makedir($directoryRoot.$subdirectory);
		$filename = date('Y-m').'.log.gz';
		parent::File($subdirectory.$filename);
	}
	
}

class SusheeLogObject extends SusheeObject{
	
	var $separator  =',';
	var $newline  ="\r\n";
	var $enclosure = '"';
}

class SusheeLog extends SusheeLogObject{
	
	var $str;
	
	function SusheeLog($str,$xml_escape=true){
		if($xml_escape===true){
			$str = encode_to_xml($str);
		}
		$this->str = $str;
	}
	
	function toString(){
		$user = new NectilUser();
		$xmlHeader = "<\?xml version=\"1.0\" encoding=\"utf-8\"\?>";
		if(substr($this->str,0,strlen($xmlHeader)) == $xmlHeader){
			$this->str = substr($this->str,strlen($xmlHeader));
		}
		$str1 = preg_replace("/userid=\"([0-9])+\"|userID=\"([0-9])+\"/i","",$this->str);
			$str2 = str_replace(" < ","&lt;",$str1);
				$str3 = str_replace(" > ","&gt;",$str2);
			$str4 = str_replace("<<","<",$str3);
		$this->str = $str4; 
		return '<logdev date="'.date("Y-m-d H:i:s").'" userID="'.$user->getID().'">'.$this->str.'</logdev>';
	}
}

class UserActionLog extends SusheeLogObject{
	
	var $action;
	var $object=false;
	var $target=false;
	var $userID=false;
	var $date=false;
	
	function UserActionLog($action,$object,$target=false){
		$this->action = $action;
		$this->object = $object;
		$this->target = $target;
		$user = new NectilUser();
		$this->userID = $user->getID();
		if(!$this->userID){
			$this->userID = 0;
		}
		$this->date = $GLOBALS["sushee_today"];
	}
	
	function toString(){
		
		$line = $this->userID.$this->separator.$this->date;
		$line.=$this->separator.$this->action.$this->separator;
		if(is_object($this->object)){
			$line.=$this->object->toString();
		}
		if(is_object($this->target)){
			$line.=$this->separator;
			$line.=$this->target->toString();
		}
		$line.=$this->newline;
		return $line;
	}
	
	function getUserID(){
		return $this->userID;
	}
	
	function parse($line){
		$this->userID = $line[0];
		$this->date = $line[1];
		$this->action = $line[2];
		$this->object = new UserActionObject($line[3],$line[4]);
		if($line[6]==UA_SRV_DEP){
			$this->target = new UserActionDependency($line[5],$line[6],$line[7],$line[8],$line[9],$line[10]);
		}elseif($line[6]==UA_SRV_OMNI){
			$this->target = new Sushee_UserActionOmnilink($line[5],$line[6],$line[7],$line[8],$line[9],$line[10],$line[11]);
		}else{
			$this->target = new UserActionTarget($line[5],$line[6],$line[7],$line[8],$line[9]);
		}
	}
	
	function getInfoXML($return_profile=false){
		$xml=
			'<INFO>';
		if(!$return_profile || $return_profile->exists('USERID'))
			$xml.='<USERID>'.encode_to_xml($this->userID).'</USERID>';
		if(!$return_profile || $return_profile->exists('DATE'))
			$xml.='<DATE>'.encode_to_xml($this->date).'</DATE>';
		if(!$return_profile || $return_profile->exists('COMMAND'))
			$xml.='<COMMAND>'.encode_to_xml($this->action).'</COMMAND>';
			
		if($this->object)
			$xml.=$this->object->getXML($return_profile);
		if($this->target)
			$xml.=$this->target->getXML($return_profile);
		$xml.=	'</INFO>';
		return $xml;
	}
	
	function getXML($return_profile=false){
		$xml = '<LOG>';
		$xml.=		$this->getInfoXML($return_profile);
		$xml.= '</LOG>';
		return $xml;
	}
	
	function setAction($action){
		$this->action = $action;
	}
	
	function setObject($object){
		$this->object = $object;
	}
	
	function getObject(){
		return $this->object;
	}
	
	function getDate(){
		return new Date($this->date);
	}
	
	
	function setTarget($target){
		$this->target = $target;
	}
	
	function getTarget(){
		return $this->target;
	}
	
}

class UserActionObject extends SusheeLogObject{
	
	var $type;
	var $ID;
	
	function UserActionObject($type,$ID){
		$this->type = $type;
		$this->ID = trim($ID);
	}
	
	function getType(){
		return $this->type;
	}
	
	function getID(){
		return $this->ID;
	}
	
	function toString(){
		return $this->type.$this->separator.$this->ID;
	}
	
	function getXML($return_profile=false){
		$xml ='';
		if(!$return_profile || $return_profile->exists('MODULE'))
			$xml.='<MODULE>'.encode_to_xml($this->type).'</MODULE>';
		if(!$return_profile || $return_profile->exists('ELEMENTID'))
			$xml.='<ELEMENTID>'.encode_to_xml($this->ID).'</ELEMENTID>';
		return $xml;
	}
}

class UserActionTarget extends SusheeLogObject{
	
	var $operation;
	var $service;
	var $type = false;
	var $field = false;
	var $value = false;
	
	function UserActionTarget($operation,$service,$field=false,$value=false,$type=false){
		$this->operation = $operation;
		$this->service = $service;
		$this->type = $type;
		$this->field = $field;
		$this->value = $value;
	}
	
	function toString(){
		
		$line = $this->operation.$this->separator
				.$this->service.$this->separator;
		if($this->field || $this->value || $this->type){
			$line.=$this->field;
			if($this->value || $this->type){
				if(is_numeric($this->value))
					$line.=$this->separator.$this->value.$this->separator;
				else if($this->value){
					// removing newlines, replacing quotes by double instance of quote (to indicate it is inside the content)
					$line.=$this->separator.$this->enclosure.str_replace(array("\r","\n",'"'),array('','','""'),$this->value).$this->enclosure.$this->separator;
				}else
					$line.=$this->separator;
				$line.=$this->type;
			}
			
		}
		
		
		return $line;
	}
	
	function getXML($return_profile=false){
		$xml ='';
		switch($this->operation){
			case UA_OP_MODIFY:
				$operation_long = UA_OP_MODIFY_LONG;
				break;
			case UA_OP_APPEND:
				$operation_long = UA_OP_APPEND_LONG;
				break;
			case UA_OP_REMOVE:
				$operation_long = UA_OP_REMOVE_LONG;
				break;
		}
		if(!$return_profile || $return_profile->exists('OPERATION'))
			$xml.='<OPERATION>'.$operation_long.'</OPERATION>';
		switch($this->service){
			case UA_SRV_INFO:
				$service_long = UA_SRV_INFO_LONG;
				if(!$return_profile || $return_profile->exists('FIELD'))
					$service_description = '<FIELD>'.encode_to_xml($this->field).'</FIELD>';
				if(!$return_profile || $return_profile->exists('VALUE'))
					$service_description.= '<VALUE>'.encode_to_xml($this->value).'</VALUE>';
				break;
			case UA_SRV_DESC:
				$service_long = UA_SRV_DESC_LONG;
				if(!$return_profile || $return_profile->exists('FIELD'))
					$service_description = '<FIELD>'.encode_to_xml($this->field).'</FIELD>';
				if(!$return_profile || $return_profile->exists('VALUE'))
					$service_description.= '<VALUE>'.encode_to_xml($this->value).'</VALUE>';
				if(!$return_profile || $return_profile->exists('LANGUAGEID'))
					$service_description.= '<LANGUAGEID>'.encode_to_xml($this->type).'</LANGUAGEID>';
				break;
			case UA_SRV_CATEG:
				$service_long = UA_SRV_CATEG_LONG;
				$service_description = '<CATEGORYID>'.encode_to_xml($this->field).'</CATEGORYID>';
				break;
			case UA_SRV_COMM:
				$service_long = UA_SRV_COMM_LONG;
				if(!$return_profile || $return_profile->exists('FIELD'))
					$service_description = '<FIELD>'.encode_to_xml($this->field).'</FIELD>';
				if(!$return_profile || $return_profile->exists('VALUE'))
					$service_description.= '<VALUE>'.encode_to_xml($this->value).'</VALUE>';
				if(!$return_profile || $return_profile->exists('COMMENTID'))
					$service_description.= '<COMMENTID>'.encode_to_xml($this->type).'</COMMENTID>';
				break;
		}
		if(!$return_profile || $return_profile->exists('SERVICE'))
			$xml.='<SERVICE>'.$service_long.'</SERVICE>';
		$xml.=$service_description;
		return $xml;
	}
}

class UserActionDependency extends SusheeLogObject{
	var $operation;
	var $service;
	var $type;
	var $field = false;
	var $value = false;
	var $targetID;
	
	function UserActionDependency($operation,$service,$type,$targetID,$field=false,$value=false){
		$this->operation = $operation;
		$this->service = $service;
		$this->type = $type;
		$this->field = $field;
		$this->value = $value;
		$this->targetID = $targetID;
	}
	function toString(){
		
		$line = $this->operation.$this->separator
				.$this->service.$this->separator
				.$this->type.$this->separator
				.$this->targetID;
		if($this->field){
			$line.=$this->separator.$this->field.$this->separator;
			if(is_numeric($this->value))
				$line.=$this->value;
			else if($this->value)
				$line.=$this->enclosure.$this->value.$this->enclosure;
		}
			
		
		return $line;
	}
	
	function getXML($return_profile=false){
		$xml ='';
		switch($this->operation){
			case UA_OP_MODIFY:
				$operation_long = UA_OP_MODIFY_LONG;
				break;
			case UA_OP_APPEND:
				$operation_long = UA_OP_APPEND_LONG;
				break;
			case UA_OP_REMOVE:
				$operation_long = UA_OP_REMOVE_LONG;
				break;
		}
		if(!$return_profile || $return_profile->exists('OPERATION'))
			$xml.='<OPERATION>'.$operation_long.'</OPERATION>';
		if(!$return_profile || $return_profile->exists('SERVICE'))
			$xml.='<SERVICE>'.UA_SRV_DEP_LONG.'</SERVICE>';
		if(!$return_profile || $return_profile->exists('TYPE'))
			$xml.='<TYPE>'.encode_to_xml($this->type).'</TYPE>';
		if(!$return_profile || $return_profile->exists('TARGETMODULE')){
			$depType = depType($this->type);
			if($depType->loaded){
				$moduleInfo = $depType->getModuleTarget();
				$targetModuleName = $moduleInfo->getName();
			}
			$xml.='<TARGETMODULE>'.encode_to_xml($targetModuleName).'</TARGETMODULE>';
		}
		if(!$return_profile || $return_profile->exists('TARGETID'))
			$xml.='<TARGETID>'.encode_to_xml($this->targetID).'</TARGETID>';
		if($this->field){
			if(!$return_profile || $return_profile->exists('FIELD'))
				$xml.='<FIELD>'.encode_to_xml($this->field).'</FIELD>';
			if(!$return_profile || $return_profile->exists('VALUE'))
				$xml.='<VALUE>'.encode_to_xml(str_replace('""','"',$this->value)).'</VALUE>';
		}
		return $xml;
	}
}

class Sushee_UserActionOmnilink extends SusheeLogObject{
	var $operation;
	var $service;
	var $type;
	var $field = false;
	var $value = false;
	var $targetID;
	var $targetModule;
	
	function Sushee_UserActionOmnilink($operation,$service,$type,$targetModule,$targetID,$field=false,$value=false){
		$this->operation = $operation;
		$this->service = $service;
		$this->type = $type;
		$this->field = $field;
		$this->value = $value;
		$this->targetID = $targetID;
		$this->targetModule = $targetModule;
	}
	function toString(){
		
		$line = $this->operation.$this->separator
				.$this->service.$this->separator
				.$this->type.$this->separator
				.$this->targetModule.$this->separator
				.$this->targetID;
		if($this->field){
			$line.=$this->separator.$this->field.$this->separator;
			if(is_numeric($this->value))
				$line.=$this->value;
			else if($this->value)
				$line.=$this->enclosure.$this->value.$this->enclosure;
		}
			
		
		return $line;
	}
	
	function getXML($return_profile=false){
		$xml ='';
		switch($this->operation){
			case UA_OP_MODIFY:
				$operation_long = UA_OP_MODIFY_LONG;
				break;
			case UA_OP_APPEND:
				$operation_long = UA_OP_APPEND_LONG;
				break;
			case UA_OP_REMOVE:
				$operation_long = UA_OP_REMOVE_LONG;
				break;
		}
		if(!$return_profile || $return_profile->exists('OPERATION'))
			$xml.='<OPERATION>'.$operation_long.'</OPERATION>';
		if(!$return_profile || $return_profile->exists('SERVICE'))
			$xml.='<SERVICE>'.UA_SRV_OMNI_LONG.'</SERVICE>';
		if(!$return_profile || $return_profile->exists('TYPE'))
			$xml.='<TYPE>'.encode_to_xml($this->type).'</TYPE>';
		if(!$return_profile || $return_profile->exists('TARGETMODULE')){
			$xml.='<TARGETMODULE>'.encode_to_xml($this->targetModule).'</TARGETMODULE>';
		}
		if(!$return_profile || $return_profile->exists('TARGETID'))
			$xml.='<TARGETID>'.encode_to_xml($this->targetID).'</TARGETID>';
		if($this->field){
			if(!$return_profile || $return_profile->exists('FIELD'))
				$xml.='<FIELD>'.encode_to_xml($this->field).'</FIELD>';
			if(!$return_profile || $return_profile->exists('VALUE'))
				$xml.='<VALUE>'.encode_to_xml(str_replace('""','"',$this->value)).'</VALUE>';
		}
		return $xml;
	}
}

?>