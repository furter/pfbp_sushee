<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/SearchLogFile.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');
require_once(dirname(__FILE__).'/../common/file.class.php');
require_once(dirname(__FILE__).'/../common/logdev.class.php');
require_once(dirname(__FILE__).'/../common/date.class.php');

// parses a log file and separates the logs 
class LogDevParser extends SusheeObject{
	var $file;
	var $path;
	var $str;
	var $offset;
	var $strLen;

	function LogDevParser($file,$offset = 0){
		$this->file = $file;
		$this->path = $file->path;
		$this->str = $file->toString();
		$this->offset = $offset;
		$this->strLen = strlen($this->str);
	}

	function getNext(){
		$logEnd = strrpos($this->str,'</logdev>',$this->offset);
		if($logEnd != false){
			$this->offset = $logEnd - $this->strLen;
			$logStart = strrpos($this->str,'<logdev',$this->offset);
			$logInfo = substr($this->str,$logStart,$logEnd - $logStart);
			$tabInfo = explode("\"",$logInfo,5);
			$logDate = $tabInfo[1];
			$logId = $tabInfo[3];
			$content = ereg_replace("<logdev date=\"[0-9]{4}-[0-9]{2}-[0-9]{2} ([0-9]{2}:){2}[0-9]{2}\" (userID|userid)=\"[0-9]+\">","",$logInfo);
			$content = ereg_replace("\n","",$content);
			$this->offset = $logStart - $this->strLen;
			$newLog = new Logdev($this->path,$this->offset,$logDate,$logId,$content,NULL,NULL);
			return $newLog;
		}
		else{
			return false;
		}
			
	}
		
}

// determines if a log matches the user criterias (DATE,USERID,CONTENT)
class logDevValidator extends SusheeObject{
	var $ID;
	var $dateNodes;
	var $content;
	
	function logDevValidator($ID = NULL,$dateNodes = NULL,$content = NULL){
		$this->ID = $ID;
		$this->dateNodes = $dateNodes;
		$this->content = $content;
	}

	function isValid($log){
		$valid = true;

		if($this->ID!= NULL){
			if($this->ID != $log->getId())
				$valid = false;
		}
		
		if(is_array($this->dateNodes)){
			$DateTimeKeywordConverter = new DateTimeKeywordConverter(false,false);
			foreach($this->dateNodes as $node){
				$operator = $node->getAttribute('operator');
				$str = $node->valueOf();
				if($operator != "between"){
					$DateTimeKeywordConverter->setValue($str);
					$DateTimeKeywordConverter->setOperator($operator);
					$DateTimeKeywordConverter->execute();
					$operator = $DateTimeKeywordConverter->getOperator();
					$date = $DateTimeKeywordConverter->getValue();
					$dateLimit = strtotime($date);
				}else{
					$between_dates = explode('/',$str);
					
					$DateTimeKeywordConverter->setValue($between_dates[0]);
					$DateTimeKeywordConverter->setOperator('LT=');
					$DateTimeKeywordConverter->execute();
					$dates[0]=$DateTimeKeywordConverter->getValue();
					
					$DateTimeKeywordConverter->setValue($between_dates[1]);
					$DateTimeKeywordConverter->setOperator('GT=');
					$DateTimeKeywordConverter->execute();
					$dates[1]=$DateTimeKeywordConverter->getValue();
					
					
				}
			switch($operator){
				
				case 'LT' :				$temp = $log->getDate();
										$dateLog = strtotime($temp);
											if($dateLog >= $dateLimit)
												$valid = false;
										break;
							
									
									
				case'LT=' :				$temp = $log->getDate();
										$dateLog = strtotime($temp);
											if($dateLog > $dateLimit)
												$valid = false;
										break;					
									
									
							
				case 'GT':				$dateLog = strtotime($log->getDate());
									 		if($dateLog <= $dateLimit)
									 			$valid = false;
										break;
										
										
									 			
			case 'GT=':					$dateLog = strtotime($log->getDate());
									 		if($dateLog < $dateLimit)
									 			$valid = false;
										break;			
														
									
									
			case 'between':				//$dates = explode('/',$str,3);
										$dateInf = strtotime($dates[0]);
										$dateSup = strtotime($dates[1]);
										$logDate = strtotime($log->getDate());
											if($logDate < $dateInf || $logDate >$dateSup)
											$valid = false;
										break;
									
									
									
									
			default :					if(!ereg($date,$log->getDate()))
											$valid = false;
										break;		
			
															
			
			}//end switch
		}//end for each
	}// end if $dateNodes
	
	
	if($this->content != NULL){
			if(!eregi($this->content,$log->getInfo()))
				$valid = false;
	}
		return $valid;
	}//end function isValid	
	
}//end class logDevValidator	

	



class searchLogFile extends RetrieveOperation{

	var $path;


	function parse(){

		$this->path = $this->firstNode->valueOf('@path');
		return true;
	}


	function operate(){

		$hits = 0; // number of logs in the result
		$filesList = array();
		$logsArray = array();
		$prevailingLog = 0;
		$firstLog = NULL;
		$lastLog =NULL;
		$nbLogs = 0;
		$displayLines = $this->operationNode->valueOf('PAGINATE/@display'); // save PAGINATE attributes if exist
		$displayPage = $this->operationNode->valueOf('PAGINATE/@page');
		$selectID = $this->firstNode->valueOf('INFO/USERID'); // choose user ID for research           
		if($selectID=='visitor'){
			$selectID = Sushee_User::getID();
		}
		$dateNodes = $this->firstNode->getElements('INFO/DATE');
		$selectContent = $this->firstNode->valueOf('INFO/CONTENT');
		$attributes = $this->getOperationAttributes();
		$this->xml = '<RESULTS hits=';
		$path = $this->path;
		if($path == NULL){
			$path = "/logsdev/";
		}
		$folder = new Folder($path);
		$logValidator = new logDevValidator($selectID,$dateNodes,$selectContent);

		if(!$test = $folder->isDirectory()){
			$file = new File($folder->path);
			if ($file->getExtension() == log){
				$filesList[0] = $file;
			}
			
		}
		else{
			$i = 0;
				
			while($file = $folder->getNextFile()){    // use an array to memorize files to check.
				if ($file->getExtension() == log){
					$filesList[$i] = $file;
					$i++;
				}
			}
		}

	 if($filesList[0] != NULL){
	 	 ///////////////////////////////////////**** Logs insertion in the logs array ****/////////////////////////////
	 	 ////// Logs are ordered by date in the array. For every log to insert, we get their date and use the strtotime function which convert it and let it 
	 	 ///// to be compared with the date of the prevailing log ( if exists), to check if the new log has to be inserted before the prevailing log.
	 	 //// Else we compare it with the next log in the array , until to find a position to insert it or until the prevailing log is the last log. 
	 	 //// If the log array is empty the new log is immmediatly inserted.
	 	 //// When the LogDevParser function returns the value "false" , it means that the file do not contain log anymmore , so we go to the next file.
	 	  				
	 	for($i=0;$i< sizeOf($filesList);$i++){
	 		$file = $filesList[$i];
	 		$prevailingLog = $firstLog;
	 		$logSearch = new LogDevParser($file);
	 		$newLog = $logSearch->getNext();
	 		
	 		
	 			
	 		while ($newLog != false){     //save log data in the log array
	 			
	 			/// Verify if the log matches with the search
	 				
	 				if($logValidator->isValid($newLog)){
	 					$hits ++;
	 					$insert = false; //kind of boolean which inform if the log has been insert in the log array or not.
	 					$timestampDate = strtotime($newLog->getDate()); // convert date of the log to insert in secondes to compare it with the date of the prevailing log
	 					
	 				
	 					while($insert == false){
	 				
	 				
	 						if($firstLog == NULL){
	 							$newLog->setPrev('begin');
	 							$newLog->setNext('end');
	 							$logsArray[$nbLogs] = $newLog;
	 							$firstLog = $nbLogs;
	 							$lastLog = $nbLogs;
	 							$prevailingLog = $nbLogs;
	 							$nbLogs ++;
	 							$insert = true;

	 						}
	 						else{
	 							
	 							$timestampLog = strtotime($logsArray[$prevailingLog]->getDate());

	 								if($timestampDate >= $timestampLog){
	 							
	 									if($prevailingLog == $firstLog){ // if the insertion is done at the first place in the array , update the firstLog value
					 				
	 										$firstLog = $nbLogs;
	 										$newLog->setPrev('begin');
	 										$newLog->setNext($prevailingLog);
	 										$logsArray[$nbLogs] = $newLog;
	 										$tempLog = $logsArray[$prevailingLog];
	 										$tempLog->setPrev($nbLogs);
	 										$prevailingLog = $nbLogs;
	 										$insert = true;
	 										$nbLogs++;
	 									}else{
	 										// if the insertion is done in the middle of the array
	 										$newLog->setNext($prevailingLog);
	 										$prev = $logsArray[$prevailingLog]->getPrev();
	 										$newLog->setPrev($prev);
	 										$tempLog = $logsArray[$prev]; // update the attribute "next" of the log which precede the prevailing log
	 										$tempLog->setNext($nbLogs);
	 										$tempLog = $logsArray[$prevailingLog];// update the attribute "previous" of the prevailing log
	 										$tempLog->setPrev($nbLogs);
	 										$logsArray[$nbLogs] = $newLog;  // save the log in the log array
	 										$prevailingLog = $nbLogs; // update prevailingLog value
	 										$insert = true; // inform that the log has been insert
	 										$nbLogs++; // update logs count
	 								
	 									}
	 								}
	 								else{

	 									if($prevailingLog == $lastLog){ // if the insertion is done at the end of the log array
	 										$newLog->setPrev($prevailingLog);
	 										$newLog->setNext('end');
	 										$tempLog = $logsArray[$prevailingLog];
	 										$tempLog->setNext($nbLogs); // update the "next" attribute value of the prevailing log
	 										$logsArray[$nbLogs] = $newLog;  // save the new log in the log array
	 										$prevailingLog = $nbLogs; //update the prevailing log 's value
	 										$lastLog = $nbLogs; //update the lastLog  value
	 										$nbLogs++;	// update logs count
	 										$insert = true;	 // inform that the new log has been insert.
	 									}
	 									else{
	 										$next = $logsArray[$prevailingLog]->getNext(); // if there are logs yet, progress in the log array
	 										$prevailingLog = $next;
	 									}
	 								}//end if date
	 							}//end if first log
	 						}//end while insert
	 					}//end if valid	
	 					$newLog = $logSearch->getNext();
	 		}//end while file
	 	}//end for

	}//end if
	 		

	 	//////////////////////////////////////////////////////   Display  logs   ///////////////////////////////////
			$prevailingLog = $firstLog;
			$this->xml .= '"'.$hits.'">';
			
		if($logsArray[$firstLog] !== NULL){
				
			if($displayLines == NULL){
				for($i=0;$i<10;$i++){
					$this->xml .=  $logsArray[$prevailingLog]->getXML();
					$next = $logsArray[$prevailingLog]->getNext();
					$prevailingLog = $next;	
				}	
			}
			else{
				if($displayPage == NULL){
					$displayPage = 1;
				}
				for($i=0;$i<$displayLines*($displayPage - 1);$i++){ //select only log number wich is defined by the attribute 'displayLines'
					$next = $logsArray[$prevailingLog]->getNext();
					$prevailingLog = $next;
				}
				for($i= $displayLines*($displayPage - 1);$i <$displayLines*($displayPage);$i++){ // display selected logs
					$this->xml .=  $logsArray[$prevailingLog]->getXML();
					$next = $logsArray[$prevailingLog]->getNext();
					$prevailingLog = $next;
				}
			}
		}//end if
		

	 	
	 
	 $this->xml .= '</RESULTS>';
	 

	 return true;
	}
}

?>