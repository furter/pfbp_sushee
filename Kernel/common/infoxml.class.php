<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/infoxml.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

	require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
	require_once(dirname(__FILE__)."/../common/fields.class.php");

	// Composes an XML describing the metadata (<INFO/>) of an element

	class NectilElementInfo extends SusheeObject{
	
		var $elementID;
		var $ModuleID;
		var $moduleInfo;
		var $elementValues;
		/* InfoProfile */ var $profile;
		/* Array */ var $security_profile;
	
		function NectilElementInfo($ModuleID,$elementID,$elementValues){
			$this->ModuleID = $ModuleID;
			$this->moduleInfo = moduleInfo($ModuleID);
		
			$this->elementID = $elementID;
			$this->elementValues = $elementValues;
		
			$this->profile = new InfoProfile();
		}
	
		function setElementID($elementID){
			$this->elementID = $elementID;
		}
	
		function setElementValues($elementValues){
			$this->elementValues = $elementValues;
		}
	
		function getXML(){
		
			if ($this->profile->isInfoTagIncluded()){
				$str='<INFO>';
			}else{
				$str='';
			}
		
		
			$profile_array = $this->profile->getIncludedFields();
			$moduleInfo = $this->moduleInfo;
			$elem = $this->elementValues;
			$fields_array = $this->security_profile;
		
			// --------------------
			// No profile was given
			// --------------------
			if ($profile_array===FALSE || $profile_array===NULL){
				$fields_nbr = count($fields_array);
				for($i=0;$i<$fields_nbr;$i++){
					$n=$fields_array[$i];
					$value=$elem[$n];
					$field = $moduleInfo->getField($n);
					if($field->isDate()){
						$str.=$field->encodeDateForNQL($value,$this->profile);
					}else{
						$str.=$field->encodeForNQL($value,'',$this->profile);
					}

				}
			}else{
				// --------------------
				// Profile is given
				// --------------------
			
			
				$fields_nbr = count($fields_array);
				$profile_nbr = count($profile_array);
				$fields_ok = 0;
				for($i=0;$i<$fields_nbr;$i++){
				
					$n=$fields_array[$i];
					$value=$elem[$n];
					$n=strtoupper($n);
					// we still have to check the profile asks it
					// by default we consider it is asked (if there is no profile)
					//$asked = TRUE;
					// warning : if <INFO> node is empty (and has a get attribute, otherwise this function would not be called) we consider all fields must be included
					$asked = FALSE;
					if (isset($profile_array[$n])){
						$asked = TRUE;
					}
					if ($asked){
					
						$field = $moduleInfo->getField($fields_array[$i]);
					
						if($field->isDate()){
							$str.=$field->encodeDateForNQL($value,$this->profile);
						}else{
							$str.=$field->encodeForNQL($value,'',$this->profile);
						}
						$fields_ok++;
						if ($fields_ok==$profile_nbr)// we did all the fields
							break;
					}
				}
			}

			if($this->profile->isCreatorInfoIncluded() || $this->profile->isModifierInfoIncluded() || $this->profile->isOwnerInfoIncluded())
			{
				$moduleContactInfo = moduleInfo('contact');

				// the set of fields to display when owner-info or creator-info or modifier-info="small"
				if($this->profile->getCreatorInfoProfile()=='small' || $this->profile->getModifierInfoProfile()=='small' || $this->profile->getOwnerInfoProfile()=='small')
				{
					$contact_fields_collection = new FieldsCollection();
					$contact_fields_collection->add(new DBField('FirstName'));
					$contact_fields_collection->add(new DBField('LastName'));
					$contact_fields_collection->add(new DBField('Email1'));
					$contact_fields_collection->add(new DBField('Denomination'));
					$contact_fields_collection->add(new DBField('ContactType'));
				}

				if($this->profile->isCreatorInfoIncluded())
				{
					$creator_profile = false;
					$creator_info_profile = $this->profile->getCreatorInfoProfile();
					// default small profile or list of fields
					if($creator_info_profile==='small')
					{
						$creator_profile = true;
						$creator_fields_collection = $contact_fields_collection;
					}
					else if($creator_info_profile!=false && $creator_info_profile!=='true')
					{
						$creator_profile = true;
						$creator_fields_collection = new FieldsCollection();
						$creator_props = explode(',',$creator_info_profile);
						foreach($creator_props as $prop)
						{
							$creator_fields_collection->add(new DBField($prop));
						}
					}
					// solve the final output
					if($creator_profile)
					{
						// <CONTACT ID="..." firstname="..."/>
						$ID = $elem['CreatorID'];
						$contact = new Contact($ID);
						$contact->loadFields($creator_fields_collection);
						// constructing the node
						$node_str = new StringXMLNode('CONTACT');
						$node_str->setAttribute('ID',$ID);
						$creator_fields_collection->reset();
						while($field = $creator_fields_collection->next())
						{
							$fieldname = $field->getName();
							$node_str->setAttribute(strtolower($fieldname),$contact->getField($fieldname));
						}
						$creator_str = '<CREATOR>'.$node_str->getXML().'</CREATOR>';
					}
					else
					{
						// <CONTACT><INFO>...</INFO></CONTACT>
						$creator_info = getInfo($moduleContactInfo,$elem['CreatorID']);
						$creator_str = generateXMLOutput($creator_info,$moduleContactInfo,array('profile_name'=>'publication'));
					}
				}
			
				if($this->profile->isModifierInfoIncluded())
				{
					$modifier_profile = false;
					$modifier_info_profile = $this->profile->getModifierInfoProfile();
					// default small profile or list of fields
					if($modifier_info_profile==='small')
					{
						$modifier_profile = true;
						$modifier_fields_collection = $contact_fields_collection;
					}
					else if($modifier_info_profile!=false && $modifier_info_profile!=='true')
					{
						$modifier_profile = true;
						$modifier_fields_collection = new FieldsCollection();
						$modifier_props = explode(',',$modifier_info_profile);
						foreach($modifier_props as $prop)
						{
							$modifier_fields_collection->add(new DBField($prop));
						}
					}
					// solve the final output
					if($modifier_profile)
					{
						// <CONTACT ID="..." firstname="..."/>
						$ID = $elem['ModifierID'];
						$contact = new Contact($ID);
						$contact->loadFields($modifier_fields_collection);
						// constructing the node
						$node_str = new StringXMLNode('CONTACT');
						$node_str->setAttribute('ID',$ID);
						$modifier_fields_collection->reset();
						while($field = $modifier_fields_collection->next())
						{
							$fieldname = $field->getName();
							$node_str->setAttribute(strtolower($fieldname),$contact->getField($fieldname));
						}
						$modifier_str = '<MODIFIER>'.$node_str->getXML().'</MODIFIER>';
					}
					else if($this->profile->isCreatorInfoIncluded() && $elem['CreatorID'] == $elem['ModifierID'])
					{
						// not including the same contact twice
					}
					else
					{
						// <CONTACT><INFO>...</INFO></CONTACT>
						$modifier_info = getInfo($moduleContactInfo,$elem['ModifierID']);
						$modifier_str = generateXMLOutput($modifier_info,$moduleContactInfo,array('profile_name'=>'publication'));
					}
				}

				if($this->profile->isOwnerInfoIncluded())
				{
					$owner_profile = false;
					$owner_info_profile = $this->profile->getOwnerInfoProfile();
					// default small profile or list of fields
					if($owner_info_profile==='small')
					{
						$owner_profile = true;
						$owner_fields_collection = $contact_fields_collection;
					}
					else if($owner_info_profile!=false && $owner_info_profile!=='true')
					{
						$owner_profile = true;
						$owner_fields_collection = new FieldsCollection();
						$owner_props = explode(',',$owner_info_profile);
						foreach($owner_props as $prop)
						{
							$owner_fields_collection->add(new DBField($prop));
						}
					}
					// solve the final output
					if($owner_profile)
					{
						$ID = $elem['OwnerID'];
						$contact = new Contact($ID);
						$contact->loadFields($owner_fields_collection);
						// constructing the node
						$node_str = new StringXMLNode('CONTACT');
						$node_str->setAttribute('ID',$ID);
						$owner_fields_collection->reset();
						while($field = $owner_fields_collection->next())
						{
							$fieldname = $field->getName();
							$node_str->setAttribute(strtolower($fieldname),$contact->getField($fieldname));
						}
						$owner_str = '<OWNER>'.$node_str->getXML().'</OWNER>';
					}
					else if($this->profile->isCreatorInfoIncluded() && $elem['CreatorID'] == $elem['OwnerID'])
					{
						// not including the same contact twice
					}
					else if($this->profile->isModifierInfoIncluded() && $elem['ModifierID'] == $elem['OwnerID'])
					{
						// not including the same contact twice
					}
					else
					{
						// <CONTACT><INFO>...</INFO></CONTACT>
						$owner_info = getInfo($moduleContactInfo,$elem['OwnerID']);
						$owner_str = generateXMLOutput($owner_info,$moduleContactInfo,array('profile_name'=>'publication'));
					}
				}

				$str.=$creator_str;
				$str.=$modifier_str;
				$str.=$owner_str;
			}

			if ($this->profile->isInfoTagIncluded())
				$str.='</INFO>';

			return $str;
		}
	
		function setProfile(/* string or array or object InfoProfile */ $profile){
			if(is_object($profile))
				$this->profile = $profile;
			else
				$this->profile = new InfoProfile($profile);
		}
	
		function setSecurityProfile($profile){
			$this->security_profile = $profile;
		}
	}

	class InfoProfile extends SusheeObject{
	
		var $profile_array;
		var $info_tag = true;
	
		var $include_timestamp = false;
		var $include_ymd = false;
		var $include_weekdays = false;
	
		var $include_creator_info = false;
		var $include_modifier_info = false;
		var $include_owner_info = false;
		var $include_field_security = false;
	
		function InfoProfile(/* array */ $profile_array=false){
			$this->profile_array = $profile_array;
		}
		// -----------------------
		// Manipulate the profile
		// -----------------------
		function includeInfoTag($boolean=true){
			$this->info_tag = $boolean;
		}
	
		function includeTimestamp($boolean=true){
			$this->include_timestamp = $boolean;
		}
	
		function includeYearMonthDay($boolean=true){
			$this->include_ymd = $boolean;
		}
	
		function includeWeekday($boolean=true){
			$this->include_weekdays = $boolean;
		}
	
		function includeCreatorInfo($profile){
			$this->include_creator_info = $profile;
		}
	
		function includeModifierInfo($profile){
			$this->include_modifier_info = $profile;
		}
	
		function includeOwnerInfo($profile){
			$this->include_owner_info = $profile;
		}
	
		function includeSecurity($boolean = true){
			$this->include_field_security = $boolean;
		}
	
		// -----------------------
		// Consult the profile
		// -----------------------
		function getIncludedFields(){
			return $this->profile_array;
		}
	
		function isTimestampIncluded(){
			return $this->include_timestamp;
		}
	
		function isYearMonthDayIncluded(){
			return $this->include_ymd;
		}
	
		function isWeekdayIncluded(){
			return $this->include_weekdays;
		}
	
		function isCreatorInfoIncluded(){
			return ($this->include_creator_info!=false);
		}
	
		function isModifierInfoIncluded(){
			return ($this->include_modifier_info!=false);
		}
	
		function isOwnerInfoIncluded(){
			return ($this->include_owner_info!=false);
		}
	
		function isSecurityIncluded(){
			return ($this->include_field_security!=false);
		}
	
		function getCreatorInfoProfile(){
			return $this->include_creator_info;
		}
	
		function getModifierInfoProfile(){
			return $this->include_modifier_info;
		}
	
		function getOwnerInfoProfile(){
			return $this->include_owner_info;
		}
	
		function isInfoTagIncluded(){
			return $this->info_tag;
		}
	}