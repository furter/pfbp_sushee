<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/searchLog.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/log.class.php');
require_once(dirname(__FILE__).'/../common/datas_structure.class.php');
require_once(dirname(__FILE__).'/../common/commandline.class.php');
require_once(dirname(__FILE__).'/../common/csv.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');

/*

Command Syntax : 

<SEARCH>
	<LOG>
		<INFO>
			[<USERID operator="{=,IN,starts-with,contains,GT=,LT=,GT,LT,NOT IN,NEQ,!=}"/>]
			[<DATE operator=""/>]
			[<COMMAND operator=""/>]
			[<MODULE operator=""/>]
			[<ELEMENTID operator=""/>]
			[<OPERATION operator="">{append,remove,modify}</OPERATION>]
			[<SERVICE operator="">{INFO,DEP,COMM,CATEG,DESC,OMNI}</SERVICE>]
			[<FIELD operator=""/>] 								* only if <SERVICE>INFO,DESC,DEP,COMM</SERVICE>
			[<CATEGORYID operator=""/>] 						* only if <SERVICE>CATEG</SERVICE>
			[<VALUE operator=""/>] 								* only if <SERVICE>INFO,DESC,DEP,COMM</SERVICE>
			[<LANGUAGEID operator=""/>] 						* only if <SERVICE>DESC</SERVICE>
			[<COMMENTID'operator=""/>] 							* only if <SERVICE>COMM</SERVICE>
			[<TYPE operator=""/>] 								* only if <SERVICE>DEP</SERVICE>
		</INFO>
	</LOG>
	<RETURN>
		[<INFO>
			[<USERID/>]
			[<DATE/>]
			[<COMMAND/>]
			[<MODULE/>]
			[<ELEMENTID/>]
			[<OPERATION/>]
			[<SERVICE/>]
			[<FIELD/>]
			[<CATEGORYID/>]
			[<VALUE/>]
			[<LANGUAGEID/>]
			[<COMMENTID'/>]
			[<TYPE/>]
		</INFO>]
		[<USER>
			[<INFO/>]
			[<DESCRIPTIONS/>]
			[<CATEGORIES/>]
		</USER>]
	</RETURN>
	[<PAGINATE display="" page=""/>]
</SEARCH>

*/

// defines a set of criterias on user actions
class UserActionProfile extends Vector{
	function UserLogProfile(){
		parent::Vector();
	}
	
	function add($column){
		$column->setProfile($this);
		parent::add($column->getName(),$column);
	}
}
// defines a column of a user action
class UserActionColumn extends SusheeObject{
	var $name;
	var $value;
	var $operator = '=';
	var $profile = false;// the profile is the set of criterions in which this column is
	
	function UserActionColumn($name,$value){
		$this->setName($name);
		$this->setValue($value);
	}
	
	function setProfile($profile){
		$this->profile = $profile;
	}
	
	function getProfile(){
		return $this->profile;
	}
	
	function getIndex(){
		$profile = $this->getProfile();
		$service = false;
		if($profile){
			if($profile->exists('SERVICE')){
				$service_column = $profile->getElement('SERVICE');
				$service = $service_column->getValue();
			}
		}
		switch($this->getName()){
			case 'USERID':
				return 1;
			case 'DATE':
				return 2;
			case 'COMMAND':
				return 3;
			case 'MODULE':
				return 4;
			case 'ELEMENTID':
				return 5;
			case 'OPERATION':
				return 6;
			case 'SERVICE':
				return 7;
			case 'FIELD':
				if($service && $service==UA_SRV_DEP){
					return 10;
				}
				return 8;
			case 'CATEGORYID':
				return 8;
			case 'VALUE':
				if($service && $service==UA_SRV_DEP){
					return 11;
				}
				return 9;
			case 'LANGUAGEID':
			case 'COMMENTID':
				return 9;
			case 'TYPE':
				// 8TH COLUMN is TYPE for Omnilinks and deps
				if($service && ($service==UA_SRV_DEP || $service==UA_SRV_OMNI)){
					return 8;
				}
				return 10;
			case 'TARGETID':
				return 9;
			case 'TARGETMODULE':
				if($service && $service==UA_SRV_DEP ){
					return true;// only returning true for profiling but doesnt match a specific column, because we dont save this information but only the type
				}
				if($service && $service==UA_SRV_OMNI){
					return 8;
				}
				return true;
			default:
				return false;
		}
	}
	
	function getName(){
		return $this->name;
	}
	
	function setName($name){
		$this->name = $name;
	}
	
	function getValue(){
		if($this->getName()=='SERVICE'){
			switch($this->value){
				case UA_SRV_INFO_LONG:
					return UA_SRV_INFO;
				case UA_SRV_DESC_LONG:
					return UA_SRV_DESC;
				case UA_SRV_CATEG_LONG:
					return UA_SRV_CATEG;
				case UA_SRV_DEP_LONG:
					return UA_SRV_DEP;
				case UA_SRV_COMM_LONG:
					return UA_SRV_COMM;
				case UA_SRV_OMNI_LONG:
					return UA_SRV_OMNI;
			}
		}
		if($this->getName()=='OPERATION'){
			switch($this->value){
				case UA_OP_MODIFY_LONG:
					return UA_OP_MODIFY;
				case UA_OP_APPEND_LONG:
					return UA_OP_APPEND;
				case UA_OP_REMOVE_LONG:
					return UA_OP_REMOVE;
			}
		}
		if($this->getName()=='MODULE'){
			return strtolower($this->value);
		}
		if(
			($this->getName()=='ELEMENTID' || $this->getName()=='TARGETID' || $this->getName()=='USERID')
			&&
			$this->value == 'visitor'
			){
				return Sushee_User::getID();
			}
		return $this->value;
	}
	
	function setValue($value){
		$this->value = $value;
	}
	
	function exists(){
		if($this->getIndex()===false)
			return false;
		return true;
	}
	
	function getOperator(){
		return $this->operator;
	}
	
	function setOperator($operator){
		$this->operator = $operator;
	}
	
	function getEnclosure(){
		if($this->getName()=='VALUE' && !is_numeric($this->getValue())){ // string values are enclosed with quotes, numeric values are not
			return '\\"';
		}
		else 
			return '';
	}
}

class DateComparisonValueGenerator extends SusheeObject{
	
	var $operator;
	
	function setOperator($operator){
		$this->operator = $operator;
	}
	
	function execute($value){
		// comparison value is a complete date value, that allows string comparison
		// 2009-01-01 --> 2009-01-01 00:00:00
		switch($this->operator){
			case 'GT':
			case 'LT=':
				$month = 12;
				$day = 31;
				$hour = 23;
				$min_sec = 59;
				break;
			case 'GT=':
			case 'LT':
				$month = '01';
				$day = '01';
				$hour = '00';
				$min_sec = '00';
				break;
		}
		$comparison_value=$value;
		if(strlen($value) == 10){
			$comparison_value=$value.' '.$hour.':'.$min_sec.':'.$min_sec;
		}else if(strlen($value) == 4){
			$comparison_value=$value.'-'.$month.'-'.$day.' '.$hour.':'.$min_sec.':'.$min_sec;
		}else if(strlen($value) == 7){
			$comparison_value=$value.'-'.$day.' '.$hour.':'.$min_sec.':'.$min_sec;
		}
		return $comparison_value;
	}
	
}

class Sushee_DateLogFilesFilter extends SusheeObject{
	
	var $year_and_month = false;
	
	function Sushee_DateLogFilesFilter($date_column){
		if($date_column){
			$value = $date_column->getValue();
			$operator = $date_column->getOperator();
			if(!$operator || $operator=='starts-with' || $operator=='='){
				$this->year_and_month = substr($value,0,7); // extracting the year and month of asked date
				$this->year_and_month_lgth = strlen($this->year_and_month);
			}
		}
	}
	
	function execute($file){
		if(!$this->year_and_month){
			return true;
		}
		if(!$file){
			return false;
		}
		$filename = $file->getShortName();
		// if filename doesnt start with same year and month as asked, not including it in the search
		$file_start = substr($filename,0,$this->year_and_month_lgth);
		if( $file_start != $this->year_and_month ){
			return false;
		}
		return true;
	}
	
}

class Sushee_SearchLogFilesFilter extends SusheeObject{
	
	var $profile;
	
	function Sushee_SearchLogFilesFilter($profile){
		$this->profile = $profile;
	}
	
	function execute(){
		
		$files_to_parse = new Vector();
		
		$date_column = $this->profile->getElement('DATE');
		$user_column = $this->profile->getElement('USERID');
		$eltID_column = $this->profile->getElement('ELEMENTID');
		$module_column = $this->profile->getElement('MODULE');
		$year_and_month = false;
		$userid = false;
		$date_filter = new Sushee_DateLogFilesFilter($date_column);
		
		if($module_column){
			// looking which modules are asked
			$operator = strtolower($module_column->getOperator());
			$module = $module_column->getValue();
			
			if($operator=='in'){
				$possible_modules = explode(',',$module);
			}else if($operator=='not in'){
				$impossible_modules = explode(',',$module);
			}else{
				$possible_modules[] = $module; // only one element
			}
		}
		if($eltID_column){
			// looking which ID are asked
			$operator = strtolower($eltID_column->getOperator());
			$elementID = $eltID_column->getValue();
			
			if($operator=='in'){
				$possible_element_IDs = explode(',',$elementID);
			}else if($operator=='not in'){
				$impossible_element_IDs = explode(',',$elementID);
			}else if($operator=='!='){
				$impossible_element_IDs[] = $elementID;
			}else{
				$possible_element_IDs[] = $elementID; // only one element
			}
		}
		
		//---------------------
		// DETERMINING WHICH FILE NEEDS TO BE CONSULTED
		//---------------------
		if($eltID_column && $module_column){
			// if given element ID and module, consulting log files in /logs/elements/
			$log_folder = new Vector();
			
			// looking in the logs sorted by modules, and elementIDs
			$to_consult_modules_folder = array();
			$modules_folder = new Folder('/logs/elements/');
			// a list of modules to include
			if(is_array($possible_modules)){
				// possible modules are modules asked by the user
				foreach($possible_modules as $modulename){
					$module_folder = $modules_folder->getChild($modulename);
					if($module_folder && $module_folder->exists()){
						$to_consult_modules_folder[]=$module_folder;
					}
				}
			// a list of modules to exclude
			}else if(is_array($impossible_modules)){
				while($module_folder = $modules_folder->next()){
					if(!in_array($modules_folder->getShortname(),$impossible_modules)){
						$to_consult_modules_folder[]=$module_folder;
					}
				}
			}
			
			foreach($to_consult_modules_folder as $module_folder){
				
				// descending in folder
				// here elements are divided in subfolder of 100000 elements
				// we have a list of IDs for the elements to include
				if(is_array($possible_element_IDs)){
					foreach($possible_element_IDs as $ID){
						// determining in which pack the element should be
						$low_limit = (int)(floor($ID / EA_PAGINATE)) * EA_PAGINATE;
						$high_limit = $low_limit + EA_PAGINATE;
						$packname=$low_limit.'-'.$high_limit;
						
						$pack_folder = $module_folder->getChild($packname);
						// looking in the subfolder if a logfile for the element exists
						if($pack_folder && $pack_folder->exists()){
							// plaintext version (before new triple saving system)
							$element_logfile = $pack_folder->getChild($ID.'.log');
							if($element_logfile && $element_logfile->exists()){
								// adding in the files to consult
								$files_to_parse->add($element_logfile->getPath(),$element_logfile->duplicate()); // duplicating otherwise, next element stacked will override this one (thanks PHP!)
							}
							// compressed version
							$element_logfile = $pack_folder->getChild($ID.'.log.gz');
							if($element_logfile && $element_logfile->exists()){
								// adding in the files to consult
								$files_to_parse->add($element_logfile->getPath(),$element_logfile->duplicate()); // duplicating otherwise, next element stacked will override this one (thanks PHP!)
							}
						}
					}
				// we have a list of elements to exclude : better to use the module file and to exclude only the lines for the elements excluded
				}else if(is_array($impossible_element_IDs)){
					$modulename = $module_folder->getName();
					// taking the corresponding folder where logs are saved by month (and not by elements)
					$module_folder_bydate = new Folder('/logs/modules/'.$modulename.'/');
					while($month_file = $module_folder_bydate->next()){
						// checking the file is in the right time interval
						$include = $date_filter->execute($month_file);
						if($include==true){
							$files_to_parse->add( $month_file->getPath(),$month_file->duplicate() ); // duplicating otherwise, next element stacked will override this one (thanks PHP!)
						}
					}
				}
			}
			
			
		}else if($module_column){
			// if given modules, consulting log files in /logs/modules/
			
			$modules_folder = new Folder('/logs/modules/');
			if(is_array($possible_modules)){
				// possible modules are modules asked by the user
				foreach($possible_modules as $modulename){
					$module_folder = $modules_folder->getChild($modulename);
					if($module_folder && $module_folder->exists()){
						$to_consult_modules_folder[]=$module_folder;
					}
				}
			// a list of modules to exclude
			}else if(is_array($impossible_modules)){
				while($module_folder = $modules_folder->next()){
					if(!in_array($modules_folder->getShortname(),$impossible_modules)){
						$to_consult_modules_folder[]=$module_folder;
					}
				}
			}
			// now that we have the list of  module folders to consult, stacking the month logfiles
			foreach($to_consult_modules_folder as $module_folder){
				while($month_file = $module_folder->next()){
					// checking the file is in the right time interval
					$include = $date_filter->execute($month_file);
					if($include==true){
						$files_to_parse->add( $month_file->getPath(),$month_file->duplicate() ); // duplicating otherwise, next element stacked will override this one (thanks PHP!)
					}
				}
			}
		}else{
			// given date or userID, consulting /logs/
			$log_folder = new UserActionLogFolder();
			$possible_user_IDs = false; // will contain the possible userIDs. This way, if a userid is given, we only consult one file
			$impossible_user_IDs = false; // will contain the excluded userIDs. This way, if a userid is given, we dont consult these files
			if($user_column){
				$operator = strtolower($user_column->getOperator());
				$userid = $user_column->getValue();
				
				if($operator=='in'){
					$possible_user_IDs = explode(',',$userid);
				}else if($operator=='not in'){
					$impossible_user_IDs = explode(',',$userid);
				}else{
					$possible_user_IDs[] = $userid;
				}
			}
			while($file = $log_folder->next()){
				if($file->isUserActionLogFile()){
					$include = true;
					$filename = $file->getShortName();
					// if file is gzipped, its name ends with .log.gz, and shortname includes the extension .log, so cutting this part
					if(substr($filename,-4)=='.log'){
						$filename = substr($filename,0,-4);
					}
					// checking the file is in the right time interval
					$include = $date_filter->execute($file);
					// if criterion on userid, only taking the file corresponding (userID=1857 filename=2009-05-1857 )
					if(is_array($possible_user_IDs)){
						$explosion = explode('-',$filename);
						$filename_userid = $explosion[2];
						if(!in_array($filename_userid, $possible_user_IDs)){
							$include = false;
						}
					}else if(is_array($impossible_user_IDs)){
						$explosion = explode('-',$filename);
						$filename_userid = $explosion[2];
						if(!in_array($filename_userid, $impossible_user_IDs)){
							$include = true;
						}else{
							$include = false;
						}
					}
					if($include==true){
						$files_to_parse->add( $file->getPath(),$file->casttoclass('UserActionLogFile') );
					}
				}
			}
		}
		return $files_to_parse;
	}
}


class searchLog extends RetrieveOperation{
	
	var $profile = false; // set of fields of the user action
	var $paginate_display = false;
	var $paginate_page = false;
	var $return = false; // what to return in the result
	var $userReturn = false; // a XML node, telling what to return of the user who did the action
	
	function parse(){
		
		// parsing criterions
		$infoNodes = $this->firstNode->getElements('INFO/*');
		$this->profile = new UserActionProfile();
		foreach($infoNodes as $node){
			$column = new UserActionColumn($node->nodeName(),$node->valueOf());
			if($column->exists()){ // if is a valid column of a user action, adding it in the set of criterions
				$operator = $node->getxSusheeOperator();
				if($operator){
					$column->setOperator($operator);
				}
				$this->profile->add($column);
			}
		}
		
		// parsing PAGINATE
		$paginateNode = $this->operationNode->getElement('PAGINATE');
		if($paginateNode){
			$this->paginate_display = $paginateNode->getAttribute('display');
			$this->paginate_page = $paginateNode->getAttribute('page');
			if(!$this->paginate_page)
				$this->paginate_page = 1;
			if(!$this->paginate_display)
				$this->paginate_display = 50;
		}else{
			$this->paginate_page = 1;
			$this->paginate_display = 50;
		}
		
		// parsing RETURN
		$returnNode = $this->operationNode->getElement('RETURN');
		if($returnNode){
			$infoNodes = $returnNode->getElements('INFO/*');
			if(count($infoNodes) > 0){
				$this->return = new UserActionProfile();
				foreach($infoNodes as $node){
					$column = new UserActionColumn($node->nodeName());
					if($column->exists()){ // if is a valid column of a user action, adding it in the set of criterions
						$this->return->add($column);
					}
				}
			}
			// also allowing to get the user who did the action. e.g. display its firstname and lastname
			$userReturn = $returnNode->getElement('USER');
			if($userReturn){
				$this->userReturn = $userReturn;
			}
		}
		
		return true;
	}
	
	function operate(){
		//---------------------
		// DETERMINING WHICH FILE NEEDS TO BE CONSULTED
		//---------------------
		// collecting the files we have to parse to seek the operations
		// we should discard the file that don't match the given user or the given date
		// if no user or no date were given, we take every file
		
		$files_filter = new Sushee_SearchLogFilesFilter($this->profile);
		$files_to_parse = $files_filter->execute();
		
		//---------------------
		// EXECUTING THE UNIX TOOL AWK TO FIND THE LINES MATCHING OUR CRITERIONS (PROFILE)
		//---------------------
		$separator = ',';
		$awk_cmd = 'awk -F\''.$separator.'\' -v OFS=\''.$separator.'\' ';
		$search_cmd = $awk_cmd;
		$search_cmd.='\'';
		// building the pattern to match by AWK : columns with their values
		$first = true;
		$pattern = '';
		$date_formatter = new DateComparisonValueGenerator();
		while($column = $this->profile->next()){
			$isDate = ($column->getName()=='DATE');
			if(!$first)
				$pattern.= ' && ';
			$operator = $column->getOperator();
			$awk_variable = '$'.$column->getIndex();
			$value = encodeQuote($column->getValue());
			$enclosure = $column->getEnclosure();
			// we need to cast to numbers if operator of comparison
			$cast ='+0';
			if($isDate){
				// do not cast if field is date, because it's a string
				$cast ='';
			}
			
			$comparison_value = $value;
			if($isDate && $operator != 'between'){
				// comparison value is a complete date value, that allows string comparison
				// 2009-01-01 --> 2009-01-01 00:00:00
				$date_formatter->setOperator($operator);
				$comparison_value = $date_formatter->execute($value);
			}
			switch($operator){
				case 'between':
					// with the between operator, two values are encoded in the same node, only separated by a slash
					$explosion = explode('/',$value);
					
					$date_formatter->setOperator('GT=');
					$explosion[0] = $date_formatter->execute($explosion[0]);
					$pattern.=$awk_variable.$cast.' >= "'.$explosion[0].'"';
					
					$date_formatter->setOperator('LT=');
					$explosion[1] = $date_formatter->execute($explosion[1]);
					$pattern.=' && '.$awk_variable.$cast.' <= "'.$explosion[1].'"';
					break;
				case 'GT':
					$pattern.=$awk_variable.$cast.' > "'.$comparison_value.'"';
					break;
				case 'GT=':
					$pattern.=$awk_variable.$cast.' >= "'.$comparison_value.'"';
					break;
				case 'LT':
					$pattern.=$awk_variable.$cast.' < "'.$comparison_value.'"';
					break;
				case 'LT=':
					$pattern.=$awk_variable.$cast.' <= "'.$comparison_value.'"';
					break;
				case 'IN':
				case 'in':
					$explosion = explode(',',$value);
					$pattern.='(';
					$first_option = true;
					foreach($explosion as $value){
						if(!$first_option)
							$pattern.=' || ';
						$pattern.=$awk_variable.' == "'.$enclosure.$value.$enclosure.'"';
						$first_option = false;
					}
					$pattern.=')';
					break;
				case 'NOT IN':
				case 'not in':
					$explosion = explode(',',$value);
					$pattern.='(';
					$first_option = true;
					foreach($explosion as $value){
						if(!$first_option)
							$pattern.=' && ';
						$pattern.=$awk_variable.' != "'.$enclosure.$value.$enclosure.'"';
						$first_option = false;
					}
					$pattern.=')';
					break;
				case 'LIKE':
				case 'contains':
					$pattern.=$awk_variable.' ~ /^'.$enclosure.'.*'.$value.'.*'.$enclosure.'$/';
					break;
				case 'starts-with':
					$pattern.=$awk_variable.' ~ /^'.$enclosure.$value.'.*'.$enclosure.'$/';
					break;
				case '!=':
				case 'NEQ':
					$pattern.=$awk_variable.' != "'.$enclosure.$value.$enclosure.'"';
					break;
				default:
					if($isDate && strlen($value) < 19){
						$pattern.=$awk_variable.' ~ /^'.$enclosure.$value.'.*'.$enclosure.'$/';
					}else{
						//$pattern.=$awk_variable.' ~ /'.$enclosure.$value.$enclosure.'/';
						$pattern.=$awk_variable.' == "'.$enclosure.$value.$enclosure.'"';
					}
					
			}
			
			$first = false;
		}
		if(!$pattern){ // no pattern, thus matching every line with an empty regular expression (the slash slash indicates a regular expression)
			$pattern=' $0 ~ //';
		}
		
		$search_cmd.=$pattern;
		$search_cmd.='{print $2'.$separator.'$0}\''; // printing the line on output when found a match, putting the date in front to allow sorting
		// using gunzip to get all the files uncompressed if necessary, and outputted to awk
		$gunzip_cmd = 'gunzip -cf';
		while($file = $files_to_parse->next()){
			$gunzip_cmd.=' "'.$file->getCompletePath().'"';
		}
		$search_cmd = $gunzip_cmd.' | '.$search_cmd;
		$cmd = $search_cmd;
		// using Unix sort executable to sort the results (piping the result of awk into it)
		$cmd.=' | sort -r ';
		//---------------------
		// PAGINATION
		//---------------------
		if($this->paginate_page){
			$paginate_cmd=' | '.$awk_cmd.' \''; // piping in a second awk command that will only cut the result after sorting
			$upper_limit = $this->paginate_page * $this->paginate_display;
			$lower_limit = $upper_limit - $this->paginate_display;
			// NR is the ordinal number of the current record in AWK
			$paginate_cmd.='NR > '.$lower_limit.' && NR <= '.$upper_limit;
			$paginate_cmd.='\'';
			$cmd.=$paginate_cmd;
			
			// when pagination, also need record counts (hits)
			// executing the same search, but piping it into 'wc' to have the record count
			$count_cmd = $search_cmd.' | wc -l';
			$count_cmd_executer = new commandLine($count_cmd);
			$count = trim($count_cmd_executer->execute());
			
			// also counting the number of pages (counts divided by pagesize)
			$pages = ceil($count / $this->paginate_display);
		}
		//---------------------
		// SAVING THE RESULT IN A TEMPORARY FILE, AVOIDING PHP MEMORY OVERLOAD
		//---------------------
		$tmp_file = new TempFile();
		$tmp_file->setExtension('csv');
		$cmd.=' > '.$tmp_file->getCompletePath();
		// executing awk on unix command line
		$cmd_executer = new commandLine($cmd);
		$cmd_result = $cmd_executer->execute();
		
		$xml = '';
		$attributes = $this->getOperationAttributes();
		if($this->paginate_page){
			$attributes.=' hits="'.$count.'" page="'.$this->paginate_page.'" pages="'.$pages.'"';
		}
		$xml.='<RESULTS'.$attributes.'>';
		// parsing csv
		$csv = $tmp_file->casttoclass('Sushee_CSV');
		$csv->hasHeader(false); // file has no column names on a separate line
		$csv->setSeparator($separator);
		
		// if user details are needed, we use a vector to cache the results, because often the contacts will be the same again and again
		if($this->userReturn){
			$usersCache = new Vector();
			$return = $this->userReturn->toString('./*');
			if($return){
				$return = 
				'<RETURN>
					'.$return.';
				</RETURN>';
			}else{
				$return = '<RETURN depth="1"><INFO><FIRSTNAME/><LASTNAME/><EMAIL1/></INFO></RETURN>';
			}
		}
		//---------------------
		// COMPOSING THE XML STARTING FROM THE CSV GENERATED BY AWK
		//---------------------
		while($line = $csv->getNextLine()){
			$action = new UserActionLog();
			// skipping first column of the file, which is the date, put in front using awk to sort the actions descendingly
			array_shift($line);
			// taking the csv line and giving it to parse
			$action->parse($line);
			$xml.= '<LOG>';
			$xml.=$action->getInfoXML($this->return);
			// getting the detail of the user who did the action
			if($this->userReturn){
				$action_userID = $action->getUserID();
				$xml.='<USER>';
				
				if($usersCache->exists($action_userID)){ // if user already displayed, reusing the same
					$result = $usersCache->getElement($action_userID);
					$xml.= $result->getXML();
				}else{
					
					$nql = new MiniNQL(
						'<GET>
							<CONTACT ID="'.$action_userID.'"/>
							'.$return.'
						</GET>');
					$nqlResult = $nql->execute();
					
					$fastxml = new XMLFastParser($nqlResult);
					$nqlResult = $fastxml->getNodeContent('RESULTS');
					
					// we use an intermediary UserNQLResult object because our vector class doesnt support well string memorization
					$result = &new UserNQLResult($nqlResult);
					$usersCache->add($action_userID,$result);
					$xml.=$nqlResult;
				}
				$xml.='</USER>';
			}
			$xml.= '</LOG>';
		}
		
		//$xml.=$cmd_result;
		$xml.='</RESULTS>';
		$this->xml = $xml;
		return true;
	}
}

class UserNQLResult extends SusheeObject{
	var $xml;
	
	function UserNQLResult($xml){
		$this->xml = $xml;
	}
	
	function getXML(){
		return $this->xml;
	}
}
?>