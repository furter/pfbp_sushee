<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/metasearch_descriptions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

function tags_DESCRIPTION(&$xml,$parentPath,$varName)
{
	$search_str = '';
	$desc_array = $xml->match($parentPath."/DESCRIPTION");
	$first = true;

	foreach($desc_array as $path)
	{
		$desc_str= tag_DESCRIPTION($xml,$path,$varName);
		if($desc_str != '')
		{
			//ensure no "OR" for the first tag instance
			if($first != true) $search_str .= ' OR ';
			else $first = false;

			$search_str.= '(' . $desc_str . ')';
		}
	}
	return $search_str;
}

function tag_DESCRIPTION(&$xml,$parentPath,$varName)
{
	$search_str = '';
	$allowed_fields = array('STATUS'=>'Status',"FULLTEXT"=>'SearchText',"LANGUAGEID"=>"LanguageID","TITLE"=>"Title","FRIENDLYTITLE"=>'FriendlyTitle',"HEADER"=>"Header","BODY"=>"Body","SIGNATURE"=>"Signature","SUMMARY"=>"Summary","BIBLIO"=>"Biblio","COPYRIGHT"=>"Copyright","CUSTOM"=>"Custom","URL"=>"URL");
	$desc_array = $xml->match($parentPath."/*");
	$languageID_crit = false;
	$status_crit = false;

	// preparing a matrix with all the possible values for all the fields
	foreach($desc_array as $path)
	{
		$n = $xml->nodeName($path);
		if (isset($allowed_fields[$n]))
		{
			$fieldname = $allowed_fields[$n];
			if($n=='CUSTOM' && $xml->match($path.'/*'))
			{
				$custom_array = $xml->match($path."/*");
				foreach($custom_array as $custom_path)
				{
					$custom_n = $xml->nodeName($custom_path);
					$custom_op = $xml->getData($custom_path.'/@operator');
					$custom_val = $xml->getData($custom_path);
					$custom_fields[$fieldname.$custom_n.$custom_op][sizeof($desc_fields[$fieldname.$custom_n.$custom_op])]=array("fieldname"=>$custom_n,"value"=>$custom_val,"operator"=>$custom_op);
				}
			}
			else
			{
				$data = $xml->getData($path);
				if($n=='FULLTEXT')
				{
					$data = decode_from_XML(strtolower(removeaccents(trim($data))));
				}

				$operator=$xml->getData($path.'/@operator');
				$desc_fields[$fieldname.$operator][sizeof($desc_fields[$fieldname.$operator])]=array("fieldname"=>$fieldname,"value"=>$data,"operator"=>$operator);
				if($fieldname=='LanguageID')
					$languageID_crit = true;
				if($fieldname=='Status')
					$status_crit = true;
			}
		}
	}

	// adding the eventual languageID as attribute
	if ($xml->match($parentPath."/@languageID"))
		$desc_fields["LanguageID"][sizeof($desc_fields["LanguageID"])] = array("fieldname"=>"LanguageID","value"=>$xml->getData($parentPath."/@languageID"),"operator"=>"=");
	if ($GLOBALS["php_request"] && !$languageID_crit){
		$desc_fields["LanguageID"][sizeof($desc_fields["LanguageID"])] = array("fieldname"=>"LanguageID","value"=>'shared,'.$GLOBALS['NectilLanguage'],"operator"=>"IN");
	}

	if (!$status_crit)
	{
		$desc_fields["Status"][sizeof($desc_fields["Status"])]=array("fieldname"=>"Status","value"=>'published',"operator"=>"=");
	}

	// now creating the sql query to handle that
	// first searching for custom fields, transforming these conditions in descriptionIDs
	$db_conn = db_connect();

	if ($custom_fields)
	{
		foreach($custom_fields as $key => $possible_values)
		{
			$first2 = true;
			$field_str="";
			for($i=0;$i<sizeof($possible_values);$i++)
			{
				$possible = $possible_values[$i];
				$str= manage_string('Value',$possible['value'],$possible['operator'],$varName);
				if($str != "")
				{
					//ensure no "OR" for the first tag instance
					if($first2 != true) 
						$field_str.=" OR ";
					else
						$first2=false;
					$field_str.="(".$str.")";
				}
			}

			if($field_str != "")
			{
				$custom_search_str="`Name`=\"".$possible['fieldname']."\" AND (".$field_str.") ";
				$custom_search_str.=' AND `Status`="published"';

				$custom_sql = 'SELECT `DescriptionID` FROM `descriptions_custom` AS '.$varName.' WHERE '.$custom_search_str;

				$rs = $db_conn->Execute($custom_sql);
				$descIDs = array();
				while($row = $rs->FetchRow())
				{
					$descIDs[]=$row['DescriptionID'];
				}
				// composing a unique operator for this custom field criterion (because every identic custom field with an identic operator must operate an OR, but 2 differents custom field must operate an AND)
				$criterID = $possible['fieldname'].$possible['operator']; 
				if(sizeof($descIDs)>0)
					$desc_fields[$criterID][]=array("fieldname"=>'ID',"value"=>implode(',',$descIDs),"operator"=>'IN');
				else // no match found --> setting a ID IN (-1)
					$desc_fields[$criterID][]=array("fieldname"=>'ID',"value"=> -1,"operator"=>'IN');
			}
		}
	}

	if ($desc_fields)
	{
		$first = true;
		foreach($desc_fields as $key => $possible_values)
		{
			$field_str = '';
			$first2 = true;
			// beginning at 1 because the first element is the field name corresponding to the tag
			for($i=0;$i<sizeof($possible_values);$i++)
			{
				$possible = $possible_values[$i];
				// $possible_values[0] is the field name
				$str ='';
				if($possible['fieldname']=='ID')
					$str= manage_number($possible['fieldname'],$possible['value'],$possible['operator'],$varName);
				else if($possible['value']!=='' || $possible['operator']=='=' || $possible['operator']=='!=' )
					$str= manage_string($possible['fieldname'],$possible['value'],$possible['operator'],$varName);
				if($str != "")
				{
					//ensure no "OR" for the first tag instance
					if($first2 != true) 
						$field_str.=" OR ";
					else
						$first2=false;
					$field_str.="(".$str.")";
				}
			}
			if($field_str != "")
			{
				//ensure no "OR" for the first tag instance
				if($first != true) 
					$search_str.=" AND ";
				else
					$first=false;
				$search_str.="(".$field_str.")";
			}
		}
	}
	
	return $search_str;
}

function getElementWithDescriptionMatching(&$xml,$element_path,$moduleInfo)
{
	$desc_string = tags_DESCRIPTION($xml,$element_path,'descrip');

	$desc_targetIDs = array();
	$excludeIDs = array();

	if($desc_string)
	{
		$db_conn = db_connect();
		$desc_collect_sql = 'SELECT `TargetID` FROM `descriptions` AS descrip WHERE `ModuleTargetID`="' . $moduleInfo->ID . '" AND ' . $desc_string;
		$desc_collect_rs = $db_conn->Execute($desc_collect_sql);
		if($desc_collect_rs)
		{
			while( $desc_row = $desc_collect_rs->FetchRow() )
			{
				$desc_targetIDs[] = $desc_row['TargetID'];
			}
		}

		if(sizeof($desc_targetIDs) == 0)
		{
			$desc_targetIDs[]=-1;
		}
	}
	return array($desc_targetIDs,$excludeIDs);
}