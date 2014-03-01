<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_datatypes.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

function manage_string($fieldName_str,$tagText_str,$operator,$varName){
    $search_str="";
    $separator=" ";
	$cast="";
	
	if(substr($operator,-7)==" number")
	{
		$cast="+0";
		$operator=substr($operator,0,-7);
	}
	if($operator=='=' && $tagText_str==='' && !$cast)
		return $search_str= "(".$varName.".`".$fieldName_str."` = ".'"" OR '.$varName.".`".$fieldName_str.'` IS NULL )';
	else if ($operator=='=')
		return $search_str= $varName.".`".$fieldName_str."`$cast = ".'"'.$tagText_str.'"';
	else if ($operator=='MD5' || $operator=='md5')
		return $search_str= 'MD5('.$varName.".`".$fieldName_str."`) = ".'"'.$tagText_str.'"';
	else if ($operator=='encrypt')
		return $search_str= $varName.".`".$fieldName_str."` = ".'"'.mysql_password($tagText_str).'"';
	else if ($operator=='!=' || $operator=='NE' || $operator =='<>' || $operator=='not')
	{
		if($tagText_str && !$cast)
		return $search_str= $varName.".`".$fieldName_str."` NOT LIKE ".'"'.$tagText_str.'"';
		else if($cast && $tagText_str)
		return $search_str= $varName.".`".$fieldName_str."`$cast != ".'"'.$tagText_str.'"';
		else
		return $search_str= $varName.".`".$fieldName_str."`$cast != ".'"'.$tagText_str.'"';
	}
	else if($operator=='not contains')
	{
		return $search_str= $varName.".`".$fieldName_str."` NOT LIKE ".'"%'.$tagText_str.'%"';
	}
	else if ($operator=="IN" || $operator=='in')
	{
		return $search_str.=$varName.".`".$fieldName_str."`$cast IN (\"".implode('","',explode(',',$tagText_str))."\")";
	}
	else if ($operator=="NOT IN" || $operator=='not in')
	{
		return $search_str.=$varName.".`".$fieldName_str."`$cast NOT IN (\"".implode('","',explode(',',$tagText_str))."\")";
	}
	else if ($operator=="MATCH" || $operator=="MATCHAGAINST" || $operator=="matchagainst")
	{
		$escape_small_words = '';
		$not_handlable_words = '';

		// -- remove sepcial characters --
		// minus and plus are reserved characters for the matchagainst operator (and they are not escapable, unless recompiling MySQL)
		$search  = array('@','#','&','.',',','/','+','=','?','"',"'",'%','_','-','£','^','¨',')','!','(','<','>');
		$replace = array(' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ');
		$tagText_str =  str_replace($search, $replace, $tagText_str);

		$words = explode(' ',$tagText_str);

		for( $i=0; $i<count($words); $i++ )
		{
			// defines the minimal length of the words contained in MYSQL fulltext indexes. If a word is smaller than this size, we donot include it in the search (default value in common/useful_vars.inc.php)
			if (strlen($words[$i]) >= $GLOBALS['MySQLFullTextMinLength']) 
			{
				$escape_small_words .= ''.removeaccents($words[$i]).' ';
			}
			else if(trim($words[$i]))
			{
				$not_handlable_words.= ''.removeaccents($words[$i]).' ';
			}
		}

		$escape_small_words = trim($escape_small_words);

		if($not_handlable_words && $fieldName_str != 'SearchText')
		{
			// do NOT add LIKE in the SearchText -> smaller words removed for optimization
			$search_str.= manage_string($fieldName_str,$not_handlable_words,'LIKE',$varName);
			if($escape_small_words)
			{
				$search_str.= ' AND ';
			}
		}

		if($escape_small_words)
		{
			$search_str.='MATCH(element.`'.$fieldName_str."`) AGAINST ('+".implode('* +',explode(' ',$escape_small_words))."*' IN BOOLEAN MODE)";
		}
		return $search_str;
	}
	else if($operator=='starts-with' || $operator=='start-with')
		return $search_str= "SUBSTRING(".$varName.".`".$fieldName_str."`$cast,1,".strlen($tagText_str).") = ".'"'.encodeQuote($tagText_str).'"';
	else if($operator=='!starts-with' || $operator=='!start-with')
		return $search_str= "SUBSTRING(".$varName.".`".$fieldName_str."`$cast,1,".strlen($tagText_str).") != ".'"'.encodeQuote($tagText_str).'"';
	else if($operator=='ends-with' || $operator=='end-with')
		return $search_str= $varName.".`".$fieldName_str."` LIKE ".'"%'.$tagText_str.'"';
	else if($operator=='!ends-with' || $operator=='!end-with')
		return $search_str= $varName.".`".$fieldName_str."` NOT LIKE ".'"%'.$tagText_str.'"';
	else if ($operator=="GT=")
        return $search_str.=$varName.".`".$fieldName_str."`$cast >= \"".encodeQuote($tagText_str)."\"";
	else if ($operator=="GT")
		return $search_str.=$varName.".`".$fieldName_str."`$cast > \"".encodeQuote($tagText_str)."\"";
	else if ($operator=="LT=")
		return $search_str.=$varName.".`".$fieldName_str."`$cast <= \"".encodeQuote($tagText_str)."\"";
	else if ($operator=="LT")
		return $search_str.=$varName.".`".$fieldName_str."`$cast < \"".encodeQuote($tagText_str)."\"";
	else if ($operator=="between")
	{
		$values = explode("/",$tagText_str);
		return $search_str.=$varName.".`".$fieldName_str."`$cast >= \"".encodeQuote($values[0])."\" AND ".$varName.".`".$fieldName_str."`$cast <= \"".encodeQuote($values[1])."\" ";
	}
	else if($operator=='match' || $operator=='matches')
	{
		$field_normalized = sql_removeaccents($varName,$fieldName_str);
		$search_str.=$field_normalized;
		$search_str.= " LIKE ".'"'.removeaccents($tagText_str).'"';
		return $search_str;
	}
		
	$guill_array = explode('"',$tagText_str);
	if(count($guill_array)==1)
	{
    	$search_array=explode($separator,$tagText_str);
		for( $i=0; $i<count($search_array); $i++ )
		{
			$search_array[$i] = trim($search_array[$i]);
		}
	}
	else
	{
		$pos = 0;
		$search_array = array();
		foreach($guill_array as $item){
			if( ($pos % 2) == 0 ){
				$sep_array = explode($separator,$item);
				foreach($sep_array as $sep_item){
					if(strlen(trim($sep_item))>0)
						$search_array[] = trim($sep_item);
				}
			}else if($item!=''){
				$search_array[]=$item;
			}
			$pos++;
		}
	}
    $firstTag=true;
    for( $i=0; $i<count($search_array); $i++ ){
		if($search_array[$i]!='' || $tagText_str==''){
			//ensure no "AND" for the first tag instance
			if($firstTag != true) 
				$search_str.=" AND ";
			else 
				$firstTag=false;
			
			$tempValue_str = mysql_escape_string(/*trim(*/$search_array[$i]/*)*/);
			if($operator!='LIKE'){
				$field_normalized = sql_removeaccents($varName,$fieldName_str);
			}else
				$field_normalized = $varName.".`".$fieldName_str."`";
			$search_str.=$field_normalized;
			$search_str.= " LIKE ".'"%'.removeaccents($tempValue_str).'%"';
			//$search_str.= $varName.".`".$fieldName_str."` LIKE ".'"%'.$tempValue_str.'%"';
		}
    }
    return $search_str;
}

function sql_removeaccents($varName,$fieldName_str){
	$search = array("&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#210;","&#211;","&#212;","&#213;","&#214;","&#216;","&#242;","&#243;","&#244;","&#245;","&#246;","&#248;","&#200;","&#201;","&#202;","&#203;","&#232;","&#233;","&#234;","&#235;","&#199;","&#231;","&#204;","&#205;","&#206;","&#207;","&#236;","&#237;","&#238;","&#239;","&#217;","&#218;","&#219;","&#220;","&#249;","&#250;","&#251;","&#252;","&#255;","&#209;","&#241;","&#8212;","&#179;","&#178;","&#176;","&#180;","&#187;","&#171;","&#169;","&#8221;","&#8220;","&#160;","&#8211;","&#8216;","&#8217;","&#339;","&#230;","&#8230;","&#8364;","&#8226;","&#367;","&#269;","&#345;","&#253;","&#382;","&#283;","&#353;","&#337;","&#128;","&#357;");
	$replace = array("a","a","a","a","a","a","a","a","a","a","a","a","o","o","o","o","o","o","o","o","o","o","o","o","e","e","e","e","e","e","e","e","c","c","i","i","i","i","i","i","i","i","u","u","u","u","u","u","u","u","y","n","n","-","3","2","o","'","\"","\"","c","\"","\""," ","-","'","'","oe","ae","...","euro","*","u","c","r","y","z","e","s","o","euro","t");	
	
	$field_normalized = $varName.".`".$fieldName_str."`";
	$entity_index = 0;
	foreach($search as $entity){
		$field_normalized = 'REPLACE('.$field_normalized.',"'.$entity.'","'.encodequote($replace[$entity_index]).'")';
		$entity_index++;
	}
	return $field_normalized;
}

function manage_date($fieldName_str,$tempValue_str,$operator,$varName){
    $search_str="";
	if($operator!='between')
		manage_timeKeywords($tempValue_str,$operator);
    switch($operator){
        case "GT=":
			$search_str.=$varName.".`".$fieldName_str."` >= '".$tempValue_str."' ";
        break;
        case "GT":
			 $search_str.=$varName.".`".$fieldName_str."` > '".$tempValue_str."' ";
        break;
        case "LT=":
			$search_str.=$varName.".`".$fieldName_str."` <= '".$tempValue_str."' ";
        break;
        case "LT":
			$search_str.=$varName.".`".$fieldName_str."` < '".$tempValue_str."' ";
        break;
		case "starts-with":
		case "start-with":
			$search_str= "SUBSTRING(".$varName.".`".$fieldName_str."`,1,".strlen($tempValue_str).") = ".'"'.encodeQuote($tempValue_str).'"';
			break;
		case "between":
			$values = explode("/",$tempValue_str);
			manage_timeKeywords($values[0],$operator);
			manage_timeKeywords($values[1],$operator);
			$search_str.=$varName.".`".$fieldName_str."` >= '".$values[0]."' AND ".$varName.".`".$fieldName_str."` <= '".$values[1]."' ";
		break;
		case "!=":
		case "NE":
		case "<>":
			$search_str.=$varName.".`".$fieldName_str."` != '".$tempValue_str."' ";
		break;
		case "LIKE":
            $search_str.=$varName.".`".$fieldName_str."` LIKE '%".$tempValue_str."%' ";
			break;
        /*case "=":
			$search_str.=$varName.".`".$fieldName_str."` = '".date("Y-m-d",strtotime($tempValue_str))."' ";
        break;*/
        default:
			//$search_str.=$varName.".`".$fieldName_str."` = '".date("Y-m-d",strtotime($tempValue_str))."' ";
			// substring because allows to search CreationDate = "2008-09-18" even if creationdate is datetime
			$search_str.= "SUBSTRING(".$varName.".`".$fieldName_str."`,1,".strlen($tempValue_str).") = ".'"'.encodeQuote($tempValue_str).'"';
        break;
    };
    return $search_str;
}
function manage_timeKeywords(&$str,&$operator){
	require_once(dirname(__FILE__)."/../common/date.class.php");
	$converter = new DateTimeKeywordConverter($str,$operator);
	$converter->execute();
	$str = $converter->getValue();
	$operator = $converter->getOperator();
}
function manage_timestamp($fieldName_str,$tempValue_str,$operator,$varName){
    $search_str="";
	if($operator!='between')
		manage_timeKeywords($tempValue_str,$operator);
   switch($operator){
        case "GT=":
            $search_str.="SUBSTRING(".$varName.".`".$fieldName_str."`,1,".strlen($tempValue_str).") >= '".$tempValue_str."' ";
        break;
        case "GT":
             $search_str.="SUBSTRING(".$varName.".`".$fieldName_str."`,1,".strlen($tempValue_str).") > '".$tempValue_str."' ";
        break;
        case "LT=":
            $search_str.="SUBSTRING(".$varName.".`".$fieldName_str."`,1,".strlen($tempValue_str).") <= '".$tempValue_str."' ";
        break;
        case "LT":
            $search_str.="SUBSTRING(".$varName.".`".$fieldName_str."`,1,".strlen($tempValue_str).") < '".$tempValue_str."' ";
        break;
		case "starts-with":
		case "start-with":
			$search_str= "SUBSTRING(".$varName.".`".$fieldName_str."`,1,".strlen($tempValue_str).") = ".'"'.encodeQuote($tempValue_str).'"';
			break;
		case "between":
			$values = explode("/",$tempValue_str);
			manage_timeKeywords($values[0],$operator);
			manage_timeKeywords($values[1],$operator);
			$search_str.=$varName.".`".$fieldName_str."` >= '".$values[0]."' AND ".$varName.".`".$fieldName_str."` <= '".$values[1]."' ";
		break;
		case "!=":
		case "NE":
		case "<>":
			$search_str.=$varName.".`".$fieldName_str."` != '".$tempValue_str."' ";
		break;
        case "LIKE":
            $search_str.=$varName.".`".$fieldName_str."` LIKE '".$tempValue_str."%' ";
        break;
        default:
            //$search_str.=$varName.".".$fieldName_str." = '".$tempValue_str."' ";
			// substring because allows to search CreationDate = "2008-09-18" even if creationdate is datetime
			$search_str= "SUBSTRING(".$varName.".`".$fieldName_str."`,1,".strlen($tempValue_str).") = ".'"'.encodeQuote($tempValue_str).'"';
        break;
    };
  
    return $search_str;
}


function manage_number($fieldName_str, $tempValue_str, $operator,$varName){
    $search_str="";
	
	if($tempValue_str==="visitor" && isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']) )
		$tempValue_str = $_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
	if($tempValue_str==='false')
		$tempValue_str = '0';
	else if($tempValue_str==='true')
		$tempValue_str = '1';
    switch($operator){
        case "GT=":
            $search_str.=$varName.".`".$fieldName_str."` >= '".$tempValue_str."'";
        break;
        case "GT":
             $search_str.=$varName.".`".$fieldName_str."` > '".$tempValue_str."'";
        break;
        case "LT=":
            $search_str.=$varName.".`".$fieldName_str."` <= '".$tempValue_str."'";
        break;
        case "LT":
            $search_str.=$varName.".`".$fieldName_str."` < '".$tempValue_str."'";
        break;
		case 'MD5':
		case 'md5':
			$search_str.= 'MD5('.$varName.".`".$fieldName_str."`) = ".'"'.$tempValue_str.'"';
			break;
		case "not in":
		case "NOT IN":
            $search_str.=$varName.".`".$fieldName_str."` NOT IN ('".implode('\',\'',explode(',',$tempValue_str))."')";
			break;
		case "in":
		case "IN":
			// if no values in the xml, setting an impossible value, this way sql returns nothing
			if(!$tempValue_str)
				$tempValue_str = '-1';
            $search_str.=$varName.".`".$fieldName_str."` IN ('".implode('\',\'',explode(',',$tempValue_str))."')";
        break;
		case "between":
			$values = explode("/",$tempValue_str);
			$search_str.=$varName.".`".$fieldName_str."` >= '".$values[0]."' AND ".$varName.".`".$fieldName_str."` <= '".$values[1]."' ";
		break;
		case "!=":
		case "NE":
		case "<>":
			$search_str.=$varName.".`".$fieldName_str."` != '".$tempValue_str."' ";
			break;
        default:
			if(strpos($tempValue_str,',')!==false){
				$search_str.=$varName.".`".$fieldName_str."` IN ('".implode('\',\'',explode(',',$tempValue_str))."')";
			}else{
				$search_str.=$varName.".`".$fieldName_str."` = '".$tempValue_str."'";
			}
            
        break;
    };
    return $search_str;

}

function manageFieldType($fieldname,$fieldType,$value,$operator,$varName){
	
	switch($fieldType){
		case "N"://Numbers
		case "I"://Integers
		case "R"://autoincrement
		case "L"://Logical field (boolean or bit-field
			$str= manage_number($fieldname,$value,$operator,$varName);
		break;
		case "C"://characters
		case "X"://big characters
		case "B"://blob
			$str= manage_string($fieldname,$value,$operator,$varName);
		break;
		case "D"://Date
			$str= manage_date($fieldname,$value,$operator,$varName);
		break;
		case "T"://timestamp
			 $str= manage_timestamp($fieldname,$value,$operator,$varName);
		break;
		default:
			 $str= $varName.".".$fieldname." = ".$value;
		break;
	};
	return $str;
}