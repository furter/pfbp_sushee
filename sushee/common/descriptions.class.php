<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/descriptions.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../common/console.class.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/filter.class.php");

define('NECTIL_MULTI_LNG',2);
define('NECTIL_UNI_LNG',3);
define('NECTIL_FILE',4);
define('NECTIL_DIRECTORY',8);
define('NECTIL_DATETIME',5);
define('NECTIL_STRING',6);
define('NECTIL_XHTML',7);
define('NECTIL_OS',8);
define('NECTIL_PUBLIC',9);

define('NECTIL_WORKFLOW_FORWARD',10);
define('NECTIL_WORKFLOW_NOOP',11);

class descriptionProfileManager extends SusheeObject{
	
	var $profiles;
	var $key;
	
	function descriptionProfileManager($key='depth'){
		$this->profiles = new Vector();
		$this->key = $key;
	}
	
	function setProfile($key,$profile){
		$this->profiles->add($key,$profile);
	}
	
	function getProfile($element){
		$key = $element[$this->key];
		if($this->profiles->exists($key)){
			$profile = $this->profiles->getElement($key);
		}else{
			$profile = $this->profiles->getElement('all');
		}
		return $profile;
	}
	function setKey($key){
		$this->key = $key;
	}
	
	function getLanguageMode(){
		$this->profiles->reset();
		$lgMode = NECTIL_UNI_LNG;
		while($profile = $this->profiles->next()){
			if($profile->getLanguageMode()==NECTIL_MULTI_LNG)
				$lgMode = NECTIL_MULTI_LNG;
		}
		return $lgMode;
	}
	
	function getAccessMode(){
		$this->profiles->reset();
		$accessMode = NECTIL_PUBLIC;
		while($profile = $this->profiles->next()){
			if($profile->getAccessMode()==NECTIL_OS)
				$accessMode = NECTIL_OS;
		}
		return $accessMode;
	}
	
	function getStatusList(){
		$this->profiles->reset();
		$statusList = array();
		while($profile = $this->profiles->next()){
			$statusList = array_merge($statusList,$profile->getStatusList());
		}
		return $statusList;
	}
	
	function getLanguage(){
		$this->profiles->reset();
		$profile = $this->profiles->next();
		return $profile->getLanguage();
	}
	
	function getLanguageAvailability(){
		$this->profiles->reset();
		$avail = false;
		while($profile = $this->profiles->next()){
			if($profile->getLanguageAvailability()==true)
				$avail = true;
		}
		return $avail;
	}

}

class descriptionsOutputManager extends SusheeObject{
	
	var $ModuleTargetID;
	var $elementIDs;
	var $elementDescriptions;
	//var $profile;
	var $isLoaded;
	var $profileManager;
	
	function descriptionsOutputManager(/* int */ $ModuleTargetID , /* array */ $elementIDs){
		$this->isLoaded = false;
		$this->elementIDs = $elementIDs;
		$this->ModuleTargetID = $ModuleTargetID;
		$profile =&new DescriptionProfile();
		$this->profileManager =&new DescriptionProfileManager();
		$this->profileManager->setProfile('all',$profile);
		$this->elementDescriptions =&new Vector();
	}
	
	function setProfileManager(/* object ProfileManager */ $profileManager){
		$this->profileManager = $profileManager;
	}
	
	
	function _commasIDs($elements){
		if(sizeof($elements)){
			foreach($elements as $ID=>$value){
				$target_cond.=$ID.',';
			}
			$target_cond=substr($target_cond,0,-1);
		}else
			$target_cond='-1';
		return $target_cond;
	}
	
	function load(){
		if(!$this->isLoaded){
			$this->isLoaded = true;
			$descriptionsIDs = array();
			$db_conn = db_connect();
			// attaching a descriptionSet foreach element
			foreach($this->elementIDs as $elementID=>$element){
				$descriptionSet = &new descriptionSet($elementID);
				$this->elementDescriptions->add($elementID,$descriptionSet);
				$profile = &$this->profileManager->getProfile($element);
				$descriptionSet->setProfile($profile);
			}
			/*  Returned descriptions */
			$sql = 'SELECT * FROM `descriptions` WHERE ';
			$lgMode = $this->profileManager->getLanguageMode();
			$accessMode = $this->profileManager->getAccessMode();
			$statusList = $this->profileManager->getStatusList();
			if($lgMode==NECTIL_UNI_LNG){
				$profile_lg = $this->profileManager->getLanguage();
				$sql.= ' `LanguageID` IN ("shared","'.$profile_lg.'") AND ';
			}
			/*if($accessMode==NECTIL_PUBLIC)
				$sql.=' `Status` = "published" AND ';*/
			$sql.=' `Status` IN ("'.implode('","',array_keys($statusList)).'") AND ';
			$sql.=' `ModuleTargetID` ='.$this->ModuleTargetID.' AND `TargetID` IN ('.$this->_commasIDs($this->elementIDs).')';
			$sql.=' ORDER BY `TargetID`,';
			$sql.='`LanguageID`';
			$sql.=',FIELD(`Status`,\'published\',\'unpublished\',\'checked\',\'submitted\',\'draft\',\'archived\');';
			sql_log($sql);
			$rs = $db_conn->Execute($sql);
			if($rs){
				while($row = $rs->FetchRow()){
					$elementID = $row['TargetID'];
					$descriptionSet = &$this->elementDescriptions->getElement($elementID);
					$element = $this->elementIDs[$elementID];
					$profile = &$this->profileManager->getProfile($element);
					
					$lgMode = $profile->getLanguageMode();
					// discarding description if in UNILINGUAL mode and language of desc is different of the language asked
					if( !($profile->getName()!='edition' && $row['Status']=='archived')){
						if( ( $lgMode==NECTIL_UNI_LNG && $row['LanguageID'] == $profile->getLanguage() ) || $lgMode!=NECTIL_UNI_LNG || $row['LanguageID'] =='shared' ){
							$accessMode = $profile->getAccessMode();
							$statusList = $profile->getStatusList();
							//if( ($accessMode==NECTIL_PUBLIC && $row['Status'] == 'published') || $accessMode!=NECTIL_PUBLIC ){
							if( isset($statusList[$row['Status']]) ){
								$descriptionSet->add(new Description($row['ID'],$row['LanguageID'],$row['Status'],$row['ModuleTargetID'],$row['TargetID']));
								$description = &$descriptionSet->getDescription($row['ID']);
								if($description)
									$description->setNativeFields($row);
								//$description->setProfile($profile);
								// pushing th ID of the description in an array to grab the customs
								$descriptionsIDs[]=$row['ID'];
							}
						}
					}
				}
			}else{
				$this->logError($sql);
				return;
			}
			if(sizeof($descriptionsIDs)>0){
				$sql = 'SELECT `descriptionID`,`Name`,`Value`,`TargetID` FROM `descriptions_custom` WHERE `descriptionID` IN ('.implode(',',$descriptionsIDs).')';
				sql_log($sql);
				$rs = $db_conn->Execute($sql);
				if($rs){
					while($row = $rs->FetchRow()){
						$elementID = $row['TargetID'];
						$descriptionID = $row['descriptionID'];
						$descriptionSet = &$this->elementDescriptions->getElement($elementID);
						if($descriptionSet){
							$description = &$descriptionSet->getDescription($descriptionID);
							if($description){
								$description->setCustomField($row['Name'],$row['Value']);
							}
						}
					}
				}else{
					$this->logError($sql);
					return;
				}
			}
			/* Request to see if there are descriptions in other languages */
			if($this->profileManager->getLanguageAvailability()==true){
				$sql = 'SELECT `ID`,`TargetID`,`LanguageID`,`Status` FROM `descriptions` WHERE';
				$sql.=' `Status` IN ("'.implode('","',array_keys($statusList)).'") AND ';
				$sql.=' `ModuleTargetID` ='.$this->ModuleTargetID.' AND `TargetID` IN ('.$this->_commasIDs($this->elementIDs).') AND `LanguageID` != "shared" GROUP BY TargetID, LanguageID';
				sql_log($sql);
				$rs = $db_conn->Execute($sql);
				if($rs){
					while($row = $rs->FetchRow()){
						$elementID = $row['TargetID'];
						$descriptionID = $row['ID'];
						$descriptionSet = &$this->elementDescriptions->getElement($elementID);
						if(!$descriptionSet->getDescription($descriptionID)){
							$descriptionSet->add(new MiniDescription($row['ID'],$row['LanguageID'],$row['Status'],$this->ModuleTargetID,$row['TargetID']));
						}
					}
				}else{
					$this->logError($sql);
					return;
				}
			}
			// if a version= is present in the url, we replace the descriptions for this element by the specific version (its a draft version)
			if(isset($_GET['version']) && is_numeric($_GET['version']) && $_GET['version']!=''){
				$draft_preview = new Description($_GET['version']);
				$res = $draft_preview->load();
				if($res){ // version exists
					$elementID = $draft_preview->TargetID;
					if($this->elementDescriptions->exists($elementID)){
						$descriptionSet = &$this->elementDescriptions->getElement($elementID);
						$descriptionSet->resetDescriptions(); // to have only the draft
						$descriptionSet->add($draft_preview);
					}
				}else
					$this->logError('This description doesn\'t exist');
				
			}
		}
	}
	
	function &getDescriptionSet(/* int */ $elementID){
		$this->load();
		if($this->elementDescriptions->exists($elementID))
			return $this->elementDescriptions->getElement($elementID);
		else
			return false;
	}
	function &getDescriptionSets(){
		$this->load();
		return $this->elementDescriptions;
	}
	function getFiles(){
		$used_files = array();
		$this->elementDescriptions->reset();
		while($descriptionSet = &$this->elementDescriptions->next()){
			$used_files=array_merge($used_files,$descriptionSet->getFiles());
		}
		return $used_files;
	}
}

class DescriptionSet extends SusheeObject{
	var $elementID;
	var $ModuleTargetID;
	var $descriptions;
	var $descriptionsByLanguage;
	var $descriptionByStatus;
	var $profile;

	
	function DescriptionSet(/* int */ $elementID){
		$this->elementID = $elementID;
		$this->descriptions = &new Vector();
		$this->descriptionsByLanguage = &new Matrix();
	}
	
	function resetDescriptions(){
		$this->descriptions = &new Vector();
	}
	
	function add(/* object Description */ &$description){
		$this->ModuleTargetID = $description->ModuleTargetID;
		
		if($description->LanguageID && $description->LanguageID!='shared'){
			$this->descriptionsByLanguage->add($description->LanguageID,false,$description);
		}
		if($description->ID and $description->className()=='description' /* can also be minidescription */){
			$description->setProfile($this->profile);
			$this->descriptions->add($description->ID,$description);
		}
	}
	function &getDescription(/* int */ $ID){
		return $this->descriptions->getElement($ID);
	}
	function setProfile(/* string or array or object DescriptionProfile */ $profile){
		if(is_object($profile)){
			$this->profile = $profile;
		}else
			$this->profile = new DescriptionProfile($profile);
	}
	function getXML(){
		$xml = '';
		if(is_object($this->profile) && $this->profile->getLanguageAvailability()==true){
			$xml.='<AVAILABLE>';
			while($descriptionRow = &$this->descriptionsByLanguage->next()){
				$description = $descriptionRow->next();
				$xml.='<LANGUAGEID>'.$description->LanguageID.'</LANGUAGEID>';
			}
			$xml.='</AVAILABLE>';
		}
		if(is_object($this->profile) && $this->profile->getPriorLanguage()){
			$desc_prior = &$this->descriptionsByLanguage->getElement($this->profile->getPriorLanguage(),0);
			if($desc_prior){
				// taking the first description in the prior language
				$xml.=$desc_prior->getXML();
			}
			// displaying the other language
			$this->descriptions->reset();
			while($description = $this->descriptions->next()){
				if($description->LanguageID!=$GLOBALS["priority_language"])
				 $xml.=$description->getXML();
			}
		}else{
			if(is_object($this->profile) && $this->profile->getName()=='edition'){
				$count_desc_for_each_langs = array();
				$count_valids_for_each_langs = array();
				while($description = $this->descriptions->next()){
					if($description->Status!='archived')
						$count_valids_for_each_langs[$description->LanguageID]++;
					$count_desc_for_each_langs[$description->LanguageID]++;
				}
				$this->descriptions->reset();
				while($description = $this->descriptions->next()){
				 	if( !($description->Status=='archived' && $count_desc_for_each_langs[$description->LanguageID]==1) &&
						!($description->Status=='published') &&
						!($description->Status!='published' && $description->Status!='archived' && $count_valids_for_each_langs[$description->LanguageID]==1)
						)
						$description->setProfile('versioning');
				}
			}
			$this->descriptions->reset();
			$count_desc_for_each_langs = array();
			while($description = $this->descriptions->next()){
				// only one description by language if in public website
				if(!($count_desc_for_each_langs[$description->LanguageID]>0 && is_object($this->profile) && $this->profile->getAccessMode()==NECTIL_PUBLIC))
					$xml.=$description->getXML();
				$count_desc_for_each_langs[$description->LanguageID]++;
			}
		}
		return $xml;
	}
	function getFiles(){
		$used_files = array();
		$this->descriptions->reset();
		while($description = $this->descriptions->next()){
			$used_files=array_merge($used_files,$description->getFiles());
		}
		return $used_files;
	}
	function getInDesign(){}
}
class MiniDescription extends SusheeObject{
	var $ID;
	var $LanguageID;
	var $Status;
	var $ModuleTargetID;
	var $TargetID;
	function MiniDescription($ID,$LanguageID,$Status,$ModuleTargetID,$TargetID){
		$this->ID = $ID;
		$this->LanguageID = $LanguageID;
		$this->Status = $Status;
		$this->ModuleTargetID = $ModuleTargetID;
		$this->TargetID = $TargetID;
	}
	function getXML(){
		return '';
	}
}

class Description extends SusheeObject{
	var $ID;
	var $LanguageID;
	var $Status;
	var $ModuleTargetID;
	var $TargetID;
	var $native_fieldnames = array("LanguageID","Status","CreatorID","ModifierID","URL","Header","Title","FriendlyTitle","Body","CreationDate","ModificationDate","Signature","Biblio","Copyright","Summary");
	var $native_fields = array();
	var $custom_fields = array();
	var $profile;
	var $filterSet;
	
	function Description($ID,$LanguageID=false,$Status=false,$ModuleTargetID=false,$TargetID=false){
		$this->ID = $ID;
		$this->LanguageID = $LanguageID;
		$this->Status = $Status;
		$this->ModuleTargetID = $ModuleTargetID;
		$this->TargetID = $TargetID;
		$this->profile = new DescriptionProfile();
		$this->initFilterSet();
	}
	function load(){
		$sql = 'SELECT * FROM `descriptions` WHERE `ID`='.$this->ID;
		$db_conn = db_connect();
		$native = $db_conn->GetRow($sql);
		if(!$native)
			return false;
		$this->LanguageID = $native['LanguageID'];
		$this->Status = $native['Status'];
		$this->ModuleTargetID = $native['ModuleTargetID'];
		$this->TargetID = $native['TargetID'];
		$this->setNativeFields($native);
		$sql = 'SELECT * FROM `descriptions_custom` WHERE `DescriptionID`='.$this->ID;
		$custom_rs = $db_conn->Execute($sql);
		while($custom_row = $custom_rs->FetchRow()){
			$this->setCustomField($custom_row['Name'],$custom_row['Value']);
		}
		return true;
	}
	
	function setFields($native_fields,$custom_fields=array()){
		$this->setNativeFields($native_fields);
		$this->setCustomFields($custom_fields);
	} 
	function getCustomFieldType($name){
		$value = $this->custom_fields[$name];
		return $this->getFieldType($value);
	}
	function getNativeFieldType($name){
		$value = $this->native_fields[$name];
		return $this->getFieldType($value);
	}
	function getFieldType($value){
		global $directoryRoot;
		if($value[0]=='/' && file_exists("$directoryRoot$value") && is_readable("$directoryRoot$value")){
			if(is_dir("$directoryRoot$value"))
				return NECTIL_DIRECTORY;
			else
				return NECTIL_FILE;
		}
		else if(substr($value,0,5)=='<CSS>')
			return NECTIL_XHTML;
		else if(
			(strlen($value)==19 && $value[4]=='-' && $value[7]=='-' && $value[10]==' ' && $value[13]==':' && $value[16]==':')
			||
			(strlen($value)==10 && $value[4]=='-' && $value[7]=='-')
			)
			return NECTIL_DATETIME;
		else
			return NECTIL_STRING;
	}
	
	function getInDesign(){
		$xml ='';
		$xml.='<DESCRIPTION ID="'.$this->ID.'">';
		$xml.='</DESCRIPTION>';
		return $xml;
	}
	
	function getXML(){
		$xml = '';
		if($this->profile->getDestination()=='indesign')
			return $this->getInDesign();
		$xml.='<DESCRIPTION languageID="'.$this->LanguageID.'" ID="'.$this->ID.'"';
		if($this->profile->getName()=='versioning')
		  	$xml.=' profile="'.$this->profile->getName().'"';
		$xml.='>';
		$profile_array = $this->profile->getProfileArray();
		foreach($profile_array as $name){
			if($name!='Custom'){
				$value = $this->native_fields[$name];
				$fieldname = strtoupper($name);
				$xml.='<'.$fieldname;
				$attr = $this->getNativeFieldAttr($name);
				foreach($attr as $attr_name=>$attr_value){
					$xml.=' '.$attr_name.'="'.$attr_value.'"';
				}
				$xml.='>';
				$xml.=$this->getXMLNativeFieldValue($name);
				$xml.='</'.$fieldname.'>';
			}
		}
		if($this->profile->isCustom()){
			$xml.='<CUSTOM>';
			foreach($this->custom_fields as $name=>$value){
				if($this->profile->isCustomField($name)){
					$fieldname = $name;
					$xml.='<'.$fieldname;
					$attr = $this->getCustomFieldAttr($name);
					foreach($attr as $attr_name=>$attr_value){
						$xml.=' '.$attr_name.'="'.$attr_value.'"';
					}
					$xml.='>';
					$value = $this->getXMLCustomFieldValue($name);
					$xml.=$value;
					$xml.='</'.$fieldname.'>';
					$type = $this->getCustomFieldType($name);
					if($type==NECTIL_DIRECTORY && $this->profile->getAccessMode()==NECTIL_PUBLIC){
						global $directoryRoot;
						$list = published_filesList($directoryRoot.$value);
						$xml.=$list;
					}
				}
			}
			$xml.='</CUSTOM>';
		}
		$xml.='</DESCRIPTION>';
		return $xml;
	}
	function getXMLNativeFieldValue($name){
		$value = $this->native_fields[$name];
		$value = $this->executeFilter($value);
		return $value;
	}
	function getXMLCustomFieldValue($name){
		if($this->profile->getDestination()=='indesign' && $this->getCustomFieldType($name)==NECTIL_FILE){
			return '';
		}
		$value = $this->custom_fields[$name];
		$value = $this->executeFilter($value);
		return $value;
	}
	function getFiles(){
		$used_files = array();
		$cssFilesFilter = new CSSFilesFilter();
		foreach($this->native_fields as $name=>$value){
			if($this->getNativeFieldType($name)==NECTIL_HTML)
				$used_files = array_merge($used_files,$cssFilesFilter->execute($value));
		}
		foreach($this->custom_fields as $name=>$value){
			$type = $this->getCustomFieldType($name);
			if($type==NECTIL_HTML)
				$used_files = array_merge($used_files,$cssFilesFilter->execute($value));
			if($type==NECTIL_FILE || $type==NECTIL_DIRECTORY)
				$used_files[]=$value;
		}
		return $used_files;
	}
	
	function executeFilter($value){
		$value = $this->filterSet->execute($value);
		return $value;
	}
	function getNativeFieldAttr($name){
		$type = $this->getNativeFieldType($name);
		switch($type){
			case NECTIL_DATETIME:
				return $this->getNativeDateAttr($name);
				break;
			default: return array();
		}
	}
	
	function getCustomFieldAttr($name){
		$type = $this->getCustomFieldType($name);
		switch($type){
			case NECTIL_FILE:
				return $this->getCustomFileAttr($name);
				break;
			case NECTIL_DATETIME:
				return $this->getCustomDateAttr($name);
				break;
			default: return array();
		}
	}
	
	function getNativeDateAttr($name){
		$value = $this->native_fields[$name];
		$attr = array();
		if($this->profile && $this->profile->getWeekdaysAvailability()){
			require_once(dirname(__FILE__)."/../common/date.class.php");
			$date = new Date($value);
			if($date->isValid())
				$attr['weekday']=$date->getWeekday();
		}
		return $attr;
	}
	function getCustomDateAttr($name){
		$value = $this->custom_fields[$name];
		$attr = array();
		if($this->profile && $this->profile->getWeekdaysAvailability()){
			require_once(dirname(__FILE__)."/../common/date.class.php");
			$date = new Date($value);
			if($date->isValid())
				$attr['weekday']=$date->getWeekday();
		}
		return $attr;
	}
	
	function getCustomFileAttr($name){
		$value = $this->custom_fields[$name];
		global $directoryRoot;
		$filepath = $directoryRoot.$value;
		$attr = array();
		$ext = getFileExt($value);
		$shortname = getFilenameWithoutExt(BaseFilename($value));
		$attr['name']=$shortname;
		if($ext){
			$attr['ext']='.'.$ext;
		}
		
		switch(strtolower($ext)){
			case 'jpg':
			case 'jpeg':
			case 'gif':
			case 'png':
			case 'bmp':
			case 'jpe':
			case 'swf':
				$attr['size']=setsize(filesize($filepath));// readable size
				if(filesize("$filepath")<4096000){
					$size = @getimagesize("$filepath");
					if ($size){
						$attr['height']=$size[1];
						$attr['width']=$size[0];
					}
				}
				break;
			case 'mp3':
			case 'mp4':
			case 'mpg':
			case 'mpeg':
			case 'mpe':
			case 'avi':
			case 'mov':
			case 'flv':
				if(filesize("$filepath")<4096000){
					require_once(dirname(__FILE__)."/../common/movie.class.php");
					$movie = new Sushee_Movie($value);
					$length = $movie->getLength();
					$attr['min'] = $length->getMinutes();
					$attr['sec'] = $length->getSeconds();
				}
			case 'pdf':
			case 'exe':
			case 'xls':
			case 'zip':
			case 'ppt':
			case 'csv':
				break;
		}
		$bytes = filesize($filepath);
		$attr['bytes']=$bytes;// raw size
		$attr['size']=setsize($bytes);// readable size
		if($this->profile->getDestination()=='indesign'){
			$attr['href'] = 'file://Files'.$value;
		}
		return $attr;
	}
	
	function setCustomFields($custom_fields){
		$this->custom_fields = array();
		foreach($custom_fields as $name=>$value){
			$this->custom_fields[$name]=$value;
		}
	}
	function setCustomField($name,$value){
		$this->custom_fields[$name]=$value;
	}
	function setNativeFields($native_fields){
		foreach($this->native_fieldnames as $name){
			if(isset($native_fields[$name]))
				$this->setNativeField($name,$native_fields[$name]);
		}
	}
	function setNativeField($name,$value){
		$this->native_fields[$name]=$value;
	}
	function setStatus($status){
		$this->status = $status;
	}
	
	function setProfile(/* string or array or object DescriptionProfile */ $profile){
		if(is_object($profile))
			$this->profile = $profile;
		else
			$this->profile = new DescriptionProfile($profile);
		$this->initFilterSet();
	}
	
	function initFilterSet(){
		$this->filterSet = new FilterSet();
		if($this->profile->getAccessMode()==NECTIL_PUBLIC){
			$this->filterSet->add(new NectilTableFilter());
			$this->filterSet->add(new FilesURLFilter($this->profile->getDestination()));
		}
		if($this->profile->getDestination()=='indesign'){
			$this->filterSet->add(new InDesignCSSFilter());
		}
	}
	
}

class InDesignCSSFilter{
	var $xml;
	function execute(/* String */ $value){
		$value = str_replace('</p>',"</p>\n",$value);
		$value = str_replace('<br/>',"&#10;",$value);
		$this->xml = new XML($value);
		if($this->xml->loaded){
			$this->recurse('/CSS[1]');
			$this->xml->reindexNodeTree();
			$value = $this->xml->toString('/','');
		}
		return $value;
	}
	
	function recurse($path){
		$xml = $this->xml;
		$children = $xml->match($path."/node()");
		$parent_path = $path;
		for($j=sizeof($children)-1;$j>=0;$j--){
			$path = $children[$j];
			if (!strpos($path,"text()")){
				$node = &$xml->getNode($path);
				$class = $xml->getData($path.'/@class');
				$this->recurse($path);

				$orig_filename = $xml->getData($path);
				if ($class!=''){
					$node['name']=$class;
					$xml->removeAttribute($path,'class');
				}
			}
		}
	}
}

class CSSFilesFilter extends SimpleFilter{
	function execute(/* String */ $value){
		$used_files = array();
		$file_url_pos = strpos($value,'src="[files_url]');
		while($file_url_pos!==false){
			$file_path_end = strpos($value,'"',$file_url_pos+16);
			if($file_path_end){
				$file = substr($value,$file_url_pos+16,$file_path_end-$file_url_pos-16);
				if($output=='indesign')
					$value = substr_replace($value,'href="file://Files',$file_url_pos,16);
				$file = transformPath($file);
				global $directoryRoot;
				if (file_exists("$directoryRoot$file")){
					$used_files[]=$file;
				}
			}
			$file_url_pos = strpos($value,'src="[files_url]',$file_url_pos+16);
		}
		return $used_files;
	}
}

class FilterSet{
	var $vector;
	function FilterSet(){
		$this->vector = array();
	}
	function add(/* Filter Object */$filter){
		$this->vector[]=$filter;
	}
	function execute(/* String */ $value){
		foreach($this->vector as $filter){
			$value = $filter->execute($value);
		}
		return $value;
	}
}

class FilesURLFilter extends SimpleFilter{
	var $destination;
	
	function FilesURLFilter(/* String */ $destination){
		$this->destination = $destination;
	}
	
	function execute(/* String */ $value){
		switch(strtolower($this->destination)){
			case 'pdf':
			case 'html':
			case 'htm':
				$value = str_replace('[files_url]',$GLOBALS["files_url"],$value);
				break;
			case 'xml':
			default:
		}
		return $value;
	}
}

class NectilTableFilter extends SimpleFilter{
	function cleanNumberFormat($text){
		$format_attr_end = 0;
		while($format_attr_start = strpos($text,' ss:Format="',$format_attr_end)){
			$format_attr_start+=12;
			//echo $format_attr_start;
			$format_attr_end = strpos($text,'"',$format_attr_start+1);
			if($format_attr_end){
				//echo $format_attr_end;
				$number_format = substr($text,$format_attr_start,$format_attr_end-$format_attr_start);
				$former_nf_len = strlen($number_format);
				// replacing currencies by a simpler form
				$number_format = preg_replace(
					'/\[\$'.
					'([^\[-]*)'.
					'[^\[]*'.
					'\]'.
					'/i','\1',$number_format);
				// replacing backslash char by simple char
				$number_format = str_replace("\\","",$number_format);
				$number_format = str_replace("&quot;","",$number_format);
				//echo '*'.$number_format.'*';
				$new_nf_len = strlen($number_format);
				//echo '*'.$text.'*';
				$text = substr_replace($text,$number_format,$format_attr_start,$former_nf_len);
				//echo '*'.$text.'*';
				$format_attr_end-=$former_nf_len;
				$format_attr_end+=$new_nf_len;
			}
		}
		return $text;
	}
	function execute(/* String */ $value){
		$nectil_table_url_pos = strpos($value,'<nectil_table ');
		while($nectil_table_url_pos!==false){
			$html_table_found = false; 
			$file_path_start = strpos($value,'href="',$nectil_table_url_pos+14);
			if($file_path_start){
				$file_path_end = strpos($value,'"',$file_path_start+6);
				if($file_path_end){
					$filepath = substr($value,$file_path_start+6,$file_path_end-$file_path_start-6);
					$filepath = str_replace('[files_url]','',$filepath);
					$nectil_table_url_end = strpos($value,'</nectil_table>');
					if($nectil_table_url_end){
						$nectil_table_url_end+=15;
						$nectil_table_len = $nectil_table_url_end-$nectil_table_url_pos;
					}
					require_once(dirname(__FILE__)."/../common/file.class.php");
					$file = new File($filepath);
					if($file->exists() && $nectil_table_url_end){
						$ext = $file->getExtension();
						
						switch($ext){
							case 'htm': // REAL HTML FILES
							case 'html': // REAL HTML FILES
								$html = $file->toString();
								$body_start = stripos($html,'<body');
								if($body_start){
									$body_start = strpos($html,'>',$body_start);
									if($body_start){
										$body_start++;
										$body_end = stripos($html,'</body',$body_start);
										if($body_end){
											$body_content = substr($html,$body_start,$body_end-$body_start);// content between <body> and </body>
											$body_content = utf8_To_UnicodeEntities($body_content);
											$body_content = encode_to_xml($body_content);
											$body_content = str_replace(array("\r","\n"),'',$body_content); // removing end of lines, otherwise they would be replaced by <br/>
											$html_table = '[html]'.$body_content.'[/html]';
											$html_table_len = strlen($html_table);
											$html_table_found = true;
											$value = substr_replace($value,$html_table,$nectil_table_url_pos,$nectil_table_len);
										}
									}
								}
								break;
							case 'xml': // EXCEL SPREADSHEET
							default:
								$xml = $file->toString();
								$xml = $this->cleanNumberFormat(str_replace('&nbsp;','&#160;',$xml)); // transforming number format in a XSL compatible number format and removing nsbp which are not true XML entities
								$template = realpath(dirname(__FILE__).'/../templates/excel_to_html.xsl');
								$more_params = array();
								// looking for a width
								$width_start = strpos($value,'width="',$nectil_table_url_pos+14);
								if($width_start){
									$width_start+=7;
									$width_end = strpos($value,'"',$width_start+1);
									$width = substr($value,$width_start,$width_end-$width_start);
									if($width)
										$more_params['width']=$width;
								}
								//looking for a class
								$class_start = strpos($value,'class="',$nectil_table_url_pos+14);
								if($class_start){
									$class_start+=7;
									$class_end = strpos($value,'"',$class_start+1);
									$class = substr($value,$class_start,$class_end-$class_start);
									if($class)
										$more_params['class']=$class;
								}
								$transform_config = array('xml'=>$xml,'template'=>$template,'more_params'=>$more_params,'html_on_error'=>false,'use_libxslt'=>true);
								$html_table = nectil_xslt_transform($transform_config);
								
								if($html_table){
									$html_table = utf8_To_UnicodeEntities($html_table);
									$html_table_len = strlen($html_table);
									$value = substr_replace($value,$html_table,$nectil_table_url_pos,$nectil_table_len);
									$html_table_found = true;
								}
						}
					}
				}
			}
			if(!$html_table_found) // strange but well maybe xml is malformed
				$nectil_table_url_pos = strpos($value,'<nectil_table ',$nectil_table_url_pos+14);
			else{
				$nectil_table_url_pos+=$html_table_len;
				$nectil_table_url_pos-=$nectil_table_len;
				$nectil_table_url_pos = strpos($value,'<nectil_table ',$nectil_table_url_pos);
			}
		}
		return $value;
	}
}

class DescriptionProfile extends SusheeObject{
	var $name;
	var $profile_array;
	var $custom_profile_array;
	var $languages;
	var $prior_language;
	var $accessMode = NECTIL_OS;
	var $languageMode = NECTIL_MULTI_LNG;
	var $destination = 'html';
	var $languageAvailability = false;
	var $weekdaysAvailability = false;
	var $statusMode = false;
	
	function setLanguage(/* string */ $lg){
		$this->languages = array();
		$this->languages[0] = $lg;
	}
	function setLanguageAvailability(/* boolean */ $availability){
		if($availability===true)
			$this->languageAvailability = true;
		else
			$this->languageAvailability = false;
	}
	function getLanguageAvailability(){
		return $this->languageAvailability;
	}
	function setWeekdaysAvailability(/* boolean */ $availability){
		if($availability===true)
			$this->weekdaysAvailability = true;
		else
			$this->weekdaysAvailability = false;
	}
	function getWeekdaysAvailability(){
		return $this->weekdaysAvailability;
	}
	function setAccessMode(/* string */ $accessMode){// OS or Public
		$this->accessMode = $accessMode;
	}
	function setLanguageMode(/* string */ $languageMode){// MULTI or UNILINGUAL
		$this->languageMode = $languageMode;
	}
	
	function setStatusMode(/* string */$statusMode){
		$this->statusMode = $statusMode;
	}
	
	function getStatusList(){
		$complete_status = array('published'=>true,'unpublished'=>true,'checked'=>true,'submitted'=>true,'draft'=>true,'archived'=>true);
		if($this->statusMode){
			if($this->statusMode=='all'){
				return $complete_status;
			}else{
				return array($this->statusMode=>true);
			}
		}else{
			if($this->accessMode==NECTIL_OS){
				return $complete_status;
			}else{
				return array('published'=>true);
			}
		}
	}
	
	function setPriorLanguage(/* string */ $lg){
		
		$this->prior_language = $lg;
	}
	function getPriorLanguage(){
		if($this->prior_language)
			return $this->prior_language;
		else
			return false;
	}
	
	function setDestination(/* string */ $destination){
		$this->destination = $destination;
	}
	function getDestination(){
		return $this->destination;
	}
	
	function getAccessMode(){
		return $this->accessMode;
	}
	
	function getLanguageMode(){
		return $this->languageMode;
	}
	/*function getLanguages(){
		return $this->languages;
	}*/
	function getLanguage(){
		return $this->languages[0];
	}
	function getProfileArray(){
		return $this->profile_array;
	}
	function isNativeField($name){
		if(in_array($name,$this->profile_array))
			return true;
		else
			return false;
	}
	function isCustomField(/* string */ $name){
		if( (in_array('Custom',$this->profile_array) && $this->custom_profile_array == false) || in_array($name,$this->custom_profile_array))
			return true;
		else
			return false;
	}
	function isCustom(){
		if(in_array('Custom',$this->profile_array))
			return true;
		else
			return false;
	}
	function getName(){
		if(is_string($this->name))
			return $this->name;
		else
			return 'user_customized';
	}
	
	function reset(){
		$this->profile_array = array();
		$this->custom_profile_array = false;
	}
	
	function getFields(){
		return array('ID'=>'ID',
						'LANGUAGEID'=>'LanguageID',
						'STATUS'=>'Status',
						'CREATORID'=>'CreatorID',
						'MODIFIERID'=>'ModifierID',
						'URL'=>'URL',
						'HEADER'=>'Header',
						'TITLE'=>'Title',
						'FRIENDLYTITLE'=>'FriendlyTitle',
						'CUSTOM'=>'Custom',
						'BODY'=>'Body',
						'CREATIONDATE'=>'CreationDate',
						'MODIFICATIONDATE'=>'ModificationDate',
						'SIGNATURE'=>'Signature',
						'BIBLIO'=>'Biblio',
						'SUMMARY'=>'Summary',
						'COPYRIGHT'=>'Copyright');
	}
	
	function canonicalize($name){
		$corres = $this->getFields();
		return $corres[$name];
	}
	
	function activateNativeField($name){
		$name = $this->canonicalize($name);
		if($name && !in_array($name,$this->profile_array))
			$this->profile_array[]=$name;
	}
	
	function activateCustomField($name){
		if(!is_array($this->custom_profile_array))
			$this->custom_profile_array = array();
		if(!in_array($name,$this->custom_profile_array))
			$this->custom_profile_array[]= $name;
	}
	
	function DescriptionProfile($profile='complete'){
		$this->custom_profile_array = false;
		if(!$profile)
			$profile = 'complete';
		if(is_string($profile)){
			$this->name = $profile;
			switch($profile){
				case 'versioning':
					$this->profile_array = array(/*'ID',*/'LanguageID','Status','CreationDate','ModificationDate','CreatorID','ModifierID','Title');
					break;
				case 'title':
					$this->profile_array = array('Title','LanguageID','Status','Custom');
					break;
				case 'minimal':
					$this->profile_array = array("Title","Header","Body","URL","Summary","Custom");
					break;
				case 'templateCSV':
					$this->profile_array = array("Title","Status","Header","Body","URL","Signature","Biblio","Copyright","Custom","Summary");
					break;
				case 'content':
					$this->profile_array = array("URL","Title",'Custom',"Header","Body","CreationDate","ModificationDate","Signature","Biblio","Copyright");
					break;
				case 'label':
					$this->profile_array = array(/*"ID",*/"LanguageID","Status","CreatorID","ModifierID","URL","Header","Title",'Custom',"Body");
					break;
				case 'summary':
					$this->profile_array = array(/*"ID",*/"LanguageID","Status","CreatorID","ModifierID","URL","Header","Title",'Custom',"Body","Summary");
					break;
				default:
					$this->profile_array = array(/*"ID",*/"LanguageID","Status","CreatorID","ModifierID","URL","Header","Title","FriendlyTitle",'Custom',"Body","CreationDate","ModificationDate","Signature","Biblio","Summary","Copyright");
					
			}
			return $this->profile_array;
		}else if(is_array($profile)){
			$profile_array = array();
			$base_array = array("ID","CreationDate","ModificationDate","LanguageID","Status","CreatorID","ModifierID","URL","Header","Title","FriendlyTitle",'Custom',"Body","Summary","Signature","Biblio","Copyright");
			foreach($base_array as $val){
				if( in_array(strtoupper($val),$profile) )
					$profile_array[]=$val;
			}
			$this->profile_array = $profile_array;
			return $this->profile_array;
		}else
			return false;
	}
}

class DescriptionsFactory extends SusheeObject{
	var $xmlNode;
	var $elementID;
	var $ModuleID;
	var $elementValues; // for virtual security
	var $console;
	
	function DescriptionsFactory($ModuleID,$xmlNode,$elementID,$elementValues=array()){
		$this->console = new XMLConsole();
		
		$this->ModuleID = $ModuleID;
		$this->moduleInfo = moduleInfo($ModuleID);
		
		$this->xmlNode = $xmlNode;
		$this->elementID = $elementID;
		$this->elementValues = $elementValues;
	}
	
	function setElementID($elementID){
		$this->elementID = $elementID;
	}
	
	function getXML(){
		return $this->console->getXML();
	}
	
	function execute(){
		$moduleInfo = $this->moduleInfo;
		$elem_values = $this->elementValues;
		$serviceSecurity = $moduleInfo->getServiceSecurity('description',$elem_values);
		if ( $moduleInfo->getServiceSecurityLevel('description','R') >= $moduleInfo->getServiceSecurityLevel('description',$serviceSecurity) ){
			$this->console->addMessage('<DESCRIPTIONS>Not authorized to write descriptions</DESCRIPTIONS>');
			return '';
		}
		if($this->xmlNode->getElements('./DESCRIPTIONS')){
			$this->console->addMessage('<DESCRIPTIONS>');
			$descNodes = $this->xmlNode->getElements('./DESCRIPTIONS/DESCRIPTION');
			$must_forward = NECTIL_WORKFLOW_NOOP;
			foreach($descNodes as $node){
				$descFactory = new DescriptionFactory($this->ModuleID,$node,$this->elementID);
				$descFactory->execute();
				$this->console->addMessage($descFactory->getXML());
				if($descFactory->getWorkflowStatus()==NECTIL_WORKFLOW_FORWARD)
					$must_forward = NECTIL_WORKFLOW_FORWARD;
			}
			$this->console->addMessage('</DESCRIPTIONS>');
			if($must_forward == NECTIL_WORKFLOW_FORWARD){
				$this->WorkflowForward();
			}
		}
		
	}
	
	function WorkflowForward(){
		$moduleInfo = $this->moduleInfo;
		$elem_values = $this->elementValues;
		$forwards = $moduleInfo->getWorkflowForwards($elem_values);
		if( is_array($forwards) ){
			$subject = 'Workflow : ';
			if($moduleInfo->name=='media' && isset($elem_values['MediaType']))
				$subject.=$elem_values['MediaType'];
			else
				$subject.=$moduleInfo->name;
			if(!isset($GLOBALS['workflowSender']))
				$sender_mail = 'workflow@nectil.com';
			else
				$sender_mail = $GLOBALS['workflowSender'];
				
			if(isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'])){
				$visitorNQL = 
				'<GET name="editor">
					<CONTACT ID="'.$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'].'"/>
					<RETURN>
						<INFO/>
					</RETURN>
				</GET>';
			}
				
			$NQL = 
			'<QUERY>
				<GET name="element">
					<'.strtoupper($moduleInfo->name).' ID="'.$this->elementID.'"/>
					<RETURN>
						<INFO/>
						<DESCRIPTIONS/>
						<CATEGORIES/>
					</RETURN>
				</GET>
				'.$visitorNQL.'
			</QUERY>';
			//$xml = flash_query($NQL/*,false*/);
			$xml = request($NQL,false,true,false,false,false,false,false,false);
			$template = realpath(dirname(__FILE__).'/../templates/workflow.xsl');
			$transform_config = array('xml'=>$xml,'template'=>$template,'more_params'=>array(),'html_on_error'=>false,'use_libxslt'=>true);
			$html = nectil_xslt_transform($transform_config);
			foreach($forwards as $forward){
				sendHtmlMail($sender_mail,$sender_mail,$forward,$forward,$subject,$html);
			}
		}
	}
}

class DescriptionFactory extends SusheeObject{
	var $xmlNode;
	var $elementID;
	var $ModuleID;
	var $console;
	function DescriptionFactory($ModuleID,$xmlNode,$elementID,$elementValues=array()){
		$this->console = new XMLConsole();
		$this->sysconsole = new LogConsole();
		$this->ModuleID = $ModuleID;
		$this->moduleInfo = moduleInfo($ModuleID);
		
		$this->xmlNode = $xmlNode;
		$this->elementID = $elementID;
		$this->elementValues = $elementValues;
	}
	
	function getXML(){
		return $this->console->getXML();
	}
	
	function parseNativeFields(){
		$node = $this->xmlNode;
		$desc_values = array();
		$descProfile = new DescriptionProfile();
		$descFields = $descProfile->getFields();
		// generic treatment
		foreach($descFields as $xml_fieldname=>$fieldname){
			if($fieldname!='ID' && $fieldname!='Custom' && $fieldname!='ModificationDate' && $fieldname!='CreatorID' && $fieldname!='ModifierID' && $fieldname!='CreationDate'){
				if($node->exists($xml_fieldname)){
					$desc_values[$fieldname]=$node->copyOf($xml_fieldname."[1]/*");
					if (!$desc_values[$fieldname])
						$desc_values[$fieldname]=$node->valueOf($xml_fieldname."[1]");
					$desc_values[$fieldname]=removeBadStyling($desc_values[$fieldname]);
				}
			}
		}
		if($node->exists("TITLE[1]")){
			$desc_values["Title"]=$node->valueOf("TITLE[1]");
			$desc_values["Title"]=str_replace(array("\r","\n"),'',$desc_values["Title"]); // no newline authorized in title
		}
		
		$desc_values["Status"]=$node->valueOf("STATUS[1]");
		if(!$desc_values['Status'])
			$desc_values['Status']='published';
		$creatorID = $node->valueOf("CREATORID[1]");
		if(is_numeric($creatorID))
			$desc_values["CreatorID"]=$creatorID;
		$modifierID = $node->valueOf("MODIFIERID[1]");
		if(is_numeric($modifierID))
			$desc_values["ModifierID"]=$modifierID;
		return $desc_values;
	}
	
	function execute(){
		$now = $GLOBALS["sushee_today"];
		$db_conn = db_connect();
		$ID = $this->elementID;
		$moduleInfo = $this->moduleInfo;
		$elem_values = $this->elementValues;
		$desc_values = array();
		$node = $this->xmlNode;
		$customs = array();
		$former_customs = array();
		$former_natives = array();
		$natives = array();
		$searchtext_natives = array();
		$SearchTxt_array = array();
		$filterSet = new FilterSet();
		// a filter to copy the files in /media
		$importFilter = new MediaFilesImportFilter();
		$files_searchtext = new FilesFulltextFilter();
		$filterSet->add( $importFilter );
		$searchText_exludes = array('CreatorID','Custom','ID','ModifierID','CreationDate','ModificationDate','ModuleTargetID','Activity','IsLocked','Status','SearchText','TargetID','LanguageID');
		$desc_values["LanguageID"]=$node->valueOf("LANGUAGEID[1]");
		if ($desc_values["LanguageID"]===FALSE)
			$desc_values["LanguageID"]=$node->valueOf("@languageID");
		if(isset($GLOBALS['NectilLanguage']) && $desc_values["LanguageID"]===FALSE)
			$desc_values["LanguageID"] = $GLOBALS['NectilLanguage'];
		if ($desc_values["LanguageID"]!==FALSE){ // we skip if there is not at least the languageID
			if($moduleInfo->checkDescriptionLanguage($desc_values["LanguageID"],$elem_values)){
				$desc_values["CreationDate"]=$now;
				$desc_values["ModificationDate"]=$now;
				$desc_values["ModuleTargetID"]=$moduleInfo->ID;
				$desc_values['TargetID']=$ID;
				//-----------------------------------------------------------------------
				// PARSING CONTENT IN XML
				//-----------------------------------------------------------------------
				$desc_ID = $node->valueOf("@ID");
				if($desc_ID!==false && is_numeric($desc_ID))
					$desc_values["ID"]=$desc_ID;
				else
					$desc_ID = $node->valueOf("ID");
				if($desc_ID!==false && is_numeric($desc_ID))
					$desc_values["ID"]=$desc_ID;
				$natives = $this->parseNativeFields();
				$desc_values = array_merge($desc_values,$natives);
				$custom_subfields = $node->getElements('CUSTOM/*');
				foreach($custom_subfields as $fieldnode){
					if($fieldnode->exists('./*'))
						$fieldvalue = $fieldnode->copyOf('./*');
					else
						$fieldvalue = $fieldnode->valueOf();
					$fieldname = $fieldnode->nodeName();
					$customs[$fieldname]=$fieldvalue;
				}
				//-----------------------------------------------------------------------
				// GETTING BACK FORMER CONTENT TO HANDLE OPERATORS AND CONSTITUTE FUTURE SEARCHTEXT
				//-----------------------------------------------------------------------
				$desc_catch_sql = '';
				if(isset($desc_values['ID'])){
					$desc_catch_sql = 'SELECT * FROM `descriptions` WHERE `ID`='.$desc_values['ID'];
				}else if($desc_values['Status']=='published'){
					// taking the published description for this language
					$desc_catch_sql = 'SELECT * FROM `descriptions` WHERE `ModuleTargetID`='.$moduleInfo->ID.' AND `Status`="'.$desc_values['Status'].'" AND `TargetID`='.$ID.' AND `LanguageID`="'.$desc_values["LanguageID"].'" ORDER BY FIELD(`Status`,\'published\',\'unpublished\',\'checked\',\'submitted\',\'draft\',\'archived\') LIMIT 0,1';
				}
				if($desc_catch_sql){
					sql_log($desc_catch_sql);
					$former_desc_rs = $db_conn->Execute($desc_catch_sql);
					$former_desc_row = $former_desc_rs->FetchRow();
					if($former_desc_row){
						$desc_values['ID'] = $former_desc_row['ID'];
						foreach($former_desc_row as $h=>$value){
							$former_natives[$h] = $former_desc_row[$h];
						}
						$desc_custom_catch_sql = 'SELECT `Name`,`Value` FROM `descriptions_custom` WHERE `DescriptionID`='.$desc_values['ID'];
						sql_log($desc_custom_catch_sql);
						$former_desc_custom_rs = $db_conn->Execute($desc_custom_catch_sql);
						while($desc_custom_row = $former_desc_custom_rs->FetchRow()){
							$fieldname = $desc_custom_row['Name'];
							$fieldvalue = $desc_custom_row['Value'];
							$former_customs[$fieldname]=$fieldvalue;
						}
					}else
						unset($former_desc_row);
				}
				//-----------------------------------------------------------------------
				// HANDLING EVENTUAL OPERATOR
				//-----------------------------------------------------------------------
				$merged_natives = $former_natives;
				foreach($desc_values as $fieldname=>$fieldvalue){
					$field_node = $node->getElement(strtoupper($fieldname));
					if($field_node && $field_node->getxSusheeOperator()){
						$operator = $field_node->getxSusheeOperator();
						$desc_values[$fieldname] = handleFieldOperator($operator,$former_natives[$fieldname],$desc_values[$fieldname]);
					}
					$merged_natives[$fieldname]=$desc_values[$fieldname];
				}
				$merged_customs = $former_customs;
				foreach($customs as $fieldname=>$fieldvalue){
					$field_node = $node->getElement("CUSTOM[1]/".$fieldname);
					if($field_node->getxSusheeOperator()){
						$operator = $field_node->getxSusheeOperator();
						$customs[$fieldname] = handleFieldOperator($operator,$former_customs[$fieldname],$customs[$fieldname]);
					}
					$merged_customs[$fieldname]=$customs[$fieldname];
				}
				//-----------------------------------------------------------------------
				// IMPORTING FILES
				//-----------------------------------------------------------------------
				foreach($desc_values as $fieldname=>$fieldvalue){
					$desc_values[$fieldname] = $filterSet->execute($fieldvalue);
				}
				foreach($customs as $fieldname=>$fieldvalue){
					$customs[$fieldname] = $filterSet->execute($fieldvalue);
				}
				//-----------------------------------------------------------------------
				// INCLUDING EVENUTAL FILES IN SEARCHTEXT
				//-----------------------------------------------------------------------
				foreach($merged_natives as $fieldname=>$fieldvalue){
					$files_searchtext->push($fieldvalue);
				}
				foreach($merged_customs as $fieldname=>$fieldvalue){
					$files_searchtext->push($fieldvalue);
				}
				foreach($merged_natives as $fieldname=>$fieldvalue){
					if(!in_array($fieldname,$searchText_exludes) && !is_numeric($fieldname) && $fieldvalue)
						$searchtext_natives[$fieldname]=$fieldvalue;
				}
				$SearchTxt_array['Natives'] = implode(' ',$searchtext_natives);
				$SearchTxt_array['Custom'] = implode(' ',$merged_customs);
				$SearchTxt_array['Files'] = $files_searchtext->execute();
				$SearchTxt = implode(' ',$SearchTxt_array);
				$SearchTxt = str_replace(array('<CSS>','</CSS>',"\t"),'',$SearchTxt);
				$desc_values['SearchText']=strtolower(removeaccents(decode_from_XML($SearchTxt)));
				if(strlen($desc_values['SearchText'])>524000)
					$desc_values['SearchText'] = substr($desc_values['SearchText'],0,524000);
				//-----------------------------------------------------------------------
				// MANAGING THE STATUS
				//-----------------------------------------------------------------------
				if($desc_values['Status']=='published'){
					$archive_sql = 'UPDATE `descriptions` SET `Status`="archived" WHERE `Status`="published" AND `LanguageID`="'.$desc_values["LanguageID"].'" AND `ModuleTargetID`='.$desc_values["ModuleTargetID"].' AND `TargetID`=\''.$desc_values['TargetID'].'\''.(($former_desc_row)?' AND `ID`!='.$desc_values['ID']:'').';';
					sql_log($archive_sql);
					$db_conn->Execute($archive_sql);
					$archive_sql = 'UPDATE `descriptions_custom` SET `Status`="archived" WHERE `Status`="published" AND `LanguageID`="'.$desc_values["LanguageID"].'" AND `ModuleTargetID`='.$desc_values["ModuleTargetID"].' AND `TargetID`=\''.$desc_values['TargetID'].'\''.(($former_desc_row)?' AND `DescriptionID`!='.$desc_values['ID']:'').';';
					sql_log($archive_sql);
					$db_conn->Execute($archive_sql);
					require_once(dirname(__FILE__)."/../common/descriptions.inc.php");
					storeDescriptionsHistory($desc_values["ModuleTargetID"],$ID,'published',$desc_values["LanguageID"]);
				}else if($desc_values['Status']=='checked' || $desc_values['Status']=='submitted'){
					$this->must_forward = true;
					if($former_desc_row && $former_desc_row['Status']==$desc_values['Status'] && !$this->must_forward){
						$this->must_forward = false;
					}
				}
				//-----------------------------------------------------------------------
				// UPDATING OR INSERTING THE DESCRIPTION
				//-----------------------------------------------------------------------
				$action_log_file = new UserActionLogFile();
				$action_object = new UserActionObject($moduleInfo->getName(),$ID);
				$user_action_filter = array('SearchText','ModificationDate','ModifierID','TargetID','ModuleTargetID','LanguageID','CreationDate','CreatorID');
				
				if(!isset($desc_values["ModifierID"]) && Sushee_User::getID()){
					$desc_values["ModifierID"] = Sushee_User::getID();
				}
				
				if($former_desc_row){
					unset($desc_values['CreationDate']);
					$fields_values = "";
					
					$first = true;
					
					foreach($desc_values as $field=>$content){
						if($content !== $former_desc_row[$field]){
							//  --- ACTION LOGGING --- 
							if(!in_array($field,$user_action_filter)){
								$action_target = new UserActionTarget(UA_OP_MODIFY,UA_SRV_DESC,$field,$content,$desc_values["LanguageID"]);
								$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
								$action_log_file->log( $action_log );
							}
							//  --- END LOGGING --- 
							if(!$first){
								$fields_values.=',';
							}
							$fields_values.="`".$field."`=\"".encodeQuote($content)."\"";
							$first = false;
							
						}
					}
					$IDs_condition = " WHERE ID=".$desc_values['ID'];
					$sql = "UPDATE `descriptions` SET $fields_values $IDs_condition;";
					
					
				}else{
					if(!isset($desc_values["CreatorID"]) && Sushee_User::getID()){
						$desc_values["CreatorID"] = Sushee_User::getID();
					}
					
					$sql = "SELECT * FROM `descriptions` WHERE `ID`=-1;";
					$pseudo_rs = $db_conn->Execute($sql);
					$sql = $db_conn->GetInsertSQL($pseudo_rs, $desc_values);
					//  --- ACTION LOGGING --- 
					foreach($desc_values as $field=>$content){
						if($content && !in_array($field,$user_action_filter)){
							$action_target = new UserActionTarget(UA_OP_MODIFY,UA_SRV_DESC,$field,$content,$desc_values["LanguageID"]);
							$action_log = new UserActionLog('UPDATE', $action_object , $action_target );
							$action_log_file->log( $action_log );
						}
					}
					//  --- END LOGGING --- 
				}
				
				sql_log($sql);
				$db_conn->Execute($sql);
				if(!$former_desc_row)
					$desc_values['ID'] = $db_conn->Insert_Id();
				if(!$desc_values['ID'])
					$this->logError($db_conn->ErrorMsg());
				else{
					//-----------------------------------------------------------------------
					// PUTTING THE CUSTOM FIELDS IN A SEPARATE TABLE
					//-----------------------------------------------------------------------
					foreach($customs as $fieldname=>$fieldvalue){
						$custom_field_sql = 
						'REPLACE INTO `descriptions_custom`	(`DescriptionID`,`Name`,`Value`,`ModuleTargetID`,`TargetID`,`Status`,`LanguageID`)VALUES
															(\''.$desc_values['ID'].'\',
															"'.$fieldname.'",
															"'.encodeQuote($fieldvalue).'",
															\''.$desc_values["ModuleTargetID"].'\',
															\''.$desc_values['TargetID'].'\',
															"'.$desc_values['Status'].'",
															"'.$desc_values["LanguageID"].'")';
						sql_log($custom_field_sql);
						$db_conn->Execute($custom_field_sql);
					}
					
				}
				
				$this->console->addMessage('<DESCRIPTION ID="'.$desc_values['ID'].'"/>');
			}else{
				$desc_ID = $node->valueOf("@ID");
				if($desc_ID)
					$this->console->addMessage('<DESCRIPTION ID="'.$desc_ID.'"/>');
				else
					$this->console->addMessage('<DESCRIPTION dropped="true"/>');
			}
		}
		
	}
	
	function getWorkflowStatus(){
		if($this->must_forward)
			return NECTIL_WORKFLOW_FORWARD;
		else
			return NECTIL_WORKFLOW_NOOP;
	}
}


?>