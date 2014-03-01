<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/searchprofile.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/*
Caching resolved profiles (resolving = node transformed in a class). we can then get back the resolved profile knowing the node describing the profile
*/
class Sushee_ElementProfileFactory extends SusheeObject{

	var $profiles = array();

	function getProfile($node){
		$nodeID = $node->getUniqueID();
		if($this->profiles[$nodeID]){
			return $this->profiles[$nodeID];
		}else{
			$profile = new Sushee_ElementProfile($node);
			$this->profiles[$nodeID] = $profile;
			return $profile;
		}
	}

}
/*
This class defines what should be returned for an element (= resolved profile)
*/
class Sushee_ElementProfile extends SusheeObject{

	var $mode;
	var $get_owner;
	var $get_modifier;
	var $get_creator;
	var $get_weekdays;
	var $get_info;
	var $desc_profile;
	var $desc_discarding;
	var $profileFields_by_module;
	var $get_dependencies;
	var $get_descriptions;
	var $get_categories;
	var $get_comments;
	var $get_omnilinks;
	var $path;
	var $xml;

	// modify the profile node and add what is included in the predefined profile
	// Ex: <RETURN profile="mini"/> --> <RETURN><INFO>....</INFO></RETURN>
	static function getPredefinedProfileNode($node,$profile_name){

		switch($profile_name){
			case 'mailingMedias':
			case 'publication':
				$node->appendChild('<INFO/>');
				$node->appendChild('<DESCRIPTIONS/>');
				$node->appendChild('<CATEGORIES/>');
				Sushee_ElementProfile::getPredefinedProfileNode($node->appendChild('<DEPENDENCIES/>'),'mini');
				break;
			case 'mini':
				$node->appendChild('<INFO/>'); // formerly was more specific, determining each field of each module, but DEPRECATED to simplify handling
				break;
			case 'complete':
				$node->appendChild('<INFO/>');
				$node->appendChild('<DESCRIPTIONS/>');
				$node->appendChild('<CATEGORIES/>');
				Sushee_ElementProfile::getPredefinedProfileNode($node->appendChild('<DEPENDENCIES/>'),'mini');
				$node->appendChild('<COMMENTS/>');
				break;
			case 'descriptions_only':
				$node->appendChild('<DESCRIPTIONS/>');
				break;
			case 'empty':
				$node->appendChild('<INFO get="false"/>');
				break;
			case 'mailing_publication':
				$node->appendChild('<INFO creator_info="true"/>');
				$node->appendChild('<DESCRIPTIONS/>');
				$node->appendChild('<CATEGORIES/>');
				Sushee_ElementProfile::getPredefinedProfileNode($node->appendChild('<DEPENDENCIES/>'),'mini');
				break;
			case 'mini_inbox':
				$node->appendChild(
					'<INFO>
						<ID/>
						<ACCOUNTID/>
						<TYPE/>
						<PRIORITY/>
						<FROM/>
						<TO/>
						<SENDINGDATE/>
						<RECEIVINGDATE/>
						<ATTACHMENTS/>
						<SUBJECT/>
						<READ/>
						<FLAG/>
						<FOLDER/>
						<TRASH/>
						<JUNK/>
						<DIRECTORYID/>
						<HTML/>
					</INFO>');
				break;
			case 'mini_publication':
				$node->appendChild(
					'<INFO>
						<MEDIATYPE/><TEMPLATE/><PAGETOCALL/>
					</INFO>');
				$node->appendChild('<DESCRIPTIONS profile="label"/>');
				break;
			default:
				$node->appendChild('<INFO/>');
		}
	}

	// resolves a profile described in a XML node
	function Sushee_ElementProfile($node){
		if(is_object($node)){
			if($node->nodename($profile_path)=='RETURN'){
				$this->isReturn = true;
			}

			if(!$node->exists('NOTHING')){ // NOTHING allows to cut every return, even INFO 
				// --- READ-ONLY ---
				$mode = $node->getData('/INFO[1]/@mode');
				if(!$mode)
					$mode = $node->getData('/@mode');
				if ( $node->valueOf('/@profile')){
					$this->getPredefinedProfileNode($node,$node->valueOf('/@profile'));
				}
				if(!$mode)
					$mode = 'normal';
				// --- INFO ---
				$get_info = $node->getData('/INFO[1]/@get');

				if($get_info==='false' ) $get_info=false;
				else{
					if($this->isReturn && !$node->exists('/INFO[1]'))
						$get_info = false;
					else
						$get_info = true;
				}

				// attribute on INFO allowing to get the infos of the contact with CREATORID indicated in element
				$get_creator = $node->getData('/INFO[1]/@creator_info');
				if(!$get_creator){
					$get_creator = $node->getData('/INFO[1]/@creator-info');
				}
				if(!$get_creator){
					$get_creator = $node->exists('/INFO[1]/CREATORID/INFO');
				}

				// attribute on INFO allowing to get the infos of the contact with MODIFIERID indicated in element
				$get_modifier = $node->getData('/INFO[1]/@modifier_info');
				if(!$get_modifier){
					$get_modifier = $node->getData('/INFO[1]/@modifier-info');
				}
				if(!$get_modifier){
					$get_modifier = $node->exists('/INFO[1]/MODIFIERID/INFO');
				}

				// attribute on INFO allowing to get the infos of the contact with OWNERID indicated in element
				$get_owner = $node->getData('/INFO[1]/@owner_info');
				if(!$get_owner){
					$get_owner = $node->getData('/INFO[1]/@owner-info'); // $get_owner = ???
				}
				if(!$get_owner){
					$get_owner = $node->exists('/INFO[1]/OWNERID/INFO');
				}

				// attribute on INFO allowing to get supplementary info about the dates
				$get_weekdays = $node->getData('/INFO[1]/@weekdays')==='true';
				$get_timestamp = $node->getData('/INFO[1]/@timestamp')==='true';
				$get_ymd = $node->getData('/INFO[1]/@year-month-day')==='true';

				// attribute on INFO allowing to get field security
				$get_security = $node->getData('/INFO[1]/@security')==='true';

				if ($get_info){
					if($node->exists("/INFO[1]/*")){
						$profile_fields = $node->getElements("INFO/*[@get!='false' or not(@get)]");
						$profile_array = array();
						if ( sizeof($profile_fields)!=0 ){
							foreach($profile_fields as $fieldnode){
								$profile_array[$fieldnode->nodeName()]=true;
							}
						}
						$info_profile = new InfoProfile( $profile_array );
					}else{
						$info_profile = new InfoProfile( false );
					}
					$info_profile->includeWeekday($get_weekdays);
					$info_profile->includeTimestamp($get_timestamp);
					$info_profile->includeYearMonthDay($get_ymd);
					$info_profile->includeCreatorInfo($get_creator);
					$info_profile->includeModifierInfo($get_modifier);
					$info_profile->includeOwnerInfo($get_owner);
					$info_profile->includeSecurity($get_security);
					$this->info_profile = $info_profile;
				}
				// --- DESCRIPTION ---
				$description_profile_path = $profile_path.'/DESCRIPTIONS[1]';

				$get_descriptions = $node->getData($description_profile_path.'/@get');
				$node_descriptions = $node->exists($description_profile_path);
				if(!$node_descriptions){
					$description_profile_path = $profile_path.'/DESCRIPTION[1]';
					$node_descriptions = $node->exists($description_profile_path);
				}else{
					if($node->exists($description_profile_path.'/DESCRIPTION[1]'))
						$description_profile_path = $description_profile_path.'/DESCRIPTION[1]';
				}

				if( ($get_descriptions==='true' || ($node_descriptions && $get_descriptions===FALSE))) $get_descriptions=true;
				else $get_descriptions=false;
				$desc_profile = new DescriptionProfile();
				if($get_descriptions){
					// this is for all depth : no way to get different description profile at different depth
					$desc_profile_name = $node->getData($description_profile_path.'/@profile');
					if($desc_profile_name)
						$desc_profile = new DescriptionProfile($desc_profile_name);
					else
						$desc_profile = new DescriptionProfile();
					/* CALLER WANT TO KNOW WHICH OTHER LANGUAGES ARE AVAILABLE */
					$desc_stats = $node->getData($description_profile_path.'/@stats');
					$desc_profile->setLanguageAvailability($desc_stats==='true');

					/* CALLER WANT TO KNOW the weekdays */
					$desc_weekdays = $node->getData($description_profile_path.'/@weekdays');
					$desc_profile->setWeekdaysAvailability($desc_weekdays==='true');

					$desc_status = $node->getData($description_profile_path.'/@status');
					if($desc_status)
						$desc_profile->setStatusMode($desc_status);

					if($GLOBALS["php_request"]){
						$desc_profile->setAccessMode(NECTIL_PUBLIC);
					}else{
						$desc_profile->setAccessMode(NECTIL_OS);
					}

					/* CALLER WANT TO DISCARD THE ELEMENTS WHICH DONT HAVE A DESCRIPTION */
					$desc_discarding = $node->getData($description_profile_path.'/@discard-element-if');

					/* LOOKING WHICH FIELDS ARE WANTED IN THE RETURNED XML */
					$node_profile = $node->getElements($description_profile_path.'/*');
					if($node_profile){
						$desc_profile->reset();
						foreach($node_profile as $desc_node){
							$nodename_profile = $desc_node->nodeName();
							if($nodename_profile!='CUSTOM'){
								$desc_profile->activateNativeField($nodename_profile);
							}else{
								$desc_profile->activateNativeField($nodename_profile);
								$custom_nodes = $desc_node->getElements('./*');
								foreach($custom_nodes as $custom){
									$desc_profile->activateCustomField($custom->nodeName());
								}
							}

						}
					}
					/* WHICH LANGUAGES MUST BE RETURNED */
					$desc_language = $node->getData($description_profile_path.'/@languageID');
					if (!$desc_language)
						$desc_language = $node->getData($description_profile_path.'/LANGUAGEID');
					if (!$desc_language)
						$desc_language='';
					if ($GLOBALS["php_request"] && isset($GLOBALS["NectilLanguage"]) && $GLOBALS['restrict_language'] && $desc_language=='')
						$desc_language = $GLOBALS["NectilLanguage"];
					if ($desc_language==='all')
						$desc_language='';

					if($desc_language!=''){
						$desc_profile->setLanguageMode(NECTIL_UNI_LNG);
						$desc_profile->setLanguage($desc_language);
					}else{
						$desc_profile->setLanguageMode(NECTIL_MULTI_LNG);
					}
					$desc_output = $node->getData($description_profile_path.'/@output');
					if (!$desc_output)
						$desc_output = 'html';
					if(isset($GLOBALS["priority_language"]) && $GLOBALS["priority_language"]!==false){
						$desc_profile->setPriorLanguage($GLOBALS["priority_language"]);
					}
					$desc_profile->setDestination($desc_output);
				}

				// --- CATEGORIES ---
				$get_categories = $node->getData('/CATEGORIES[1]/@get');
				$node_categories = $node->exists('/CATEGORIES[1]');
				if( ($get_categories==='true'|| ($node_categories && $get_categories===FALSE))) $get_categories=true;
				else $get_categories=false;

				// --- COMMENTS ---
				$get_comments = $node->getData('/COMMENTS[1]/@get');
				$node_comments = $node->exists('/COMMENTS[1]');
				if( ($get_comments==='true' || ($node_comments && $get_comments===FALSE))) $get_comments=true;
				else $get_comments=false;

				// --- DEPENDENCIES ---
				$get_depth = $node->getData('/@depth');
				$get_dependencies = $node->getData('/DEPENDENCIES[1]/@get');
				$node_dependencies = $node->exists('/DEPENDENCIES[1] | '.$profile_path.'/DEPENDENCY[1]');

				if( $get_dependencies === 'true' ) $get_dependencies = true; // get = true
				else if( $get_dependencies === 'false' ) $get_dependencies = false; // get = false
				else if( $node_dependencies ) $get_dependencies = true; // DEPENDENCY(IES) nodes
				else if( $get_depth > 1 ) $get_dependencies = true; // no DEPENDENCY(IES) node but depth set
				else $get_dependencies = false;

				$get_omnilinks = $node->exists('/OMNILINKS[1]');
			}

			$this->mode= $mode;
			$this->get_owner= $get_owner;
			$this->get_modifier= $get_modifier;
			$this->get_creator= $get_creator;
			$this->get_weekdays= $get_weekdays;
			$this->get_info= $get_info;
			$this->desc_profile= $desc_profile;
			$this->desc_discarding= $desc_discarding;
			$this->profileFields_by_module= $profileFields_by_module;
			$this->get_dependencies= $get_dependencies;
			$this->get_descriptions= $get_descriptions;
			$this->get_categories= $get_categories;
			$this->get_comments= $get_comments;
			$this->get_omnilinks= $get_omnilinks;
		}

		$this->path = $node->getPath();
		$this->xml = $node->getDocument();

	}

	function returnInfo(){
		return $this->get_info;
	}

	function getInfoMode(){
		return $this->mode;
	}

	function getIncludedFields(){
		if($this->info_profile){
			return $this->info_profile->getIncludedFields();
		}
		return array();
	}

	function getInfoProfile(){
		return $this->info_profile;
	}

	function returnDependencies(){
		return $this->get_dependencies;
	}

	function returnDescriptions(){
		return $this->get_descriptions;
	}

	function returnCategories(){
		return $this->get_categories;
	}

	function returnComments(){
		return $this->get_comments;
	}

	function returnOmnilinks(){
		return $this->get_omnilinks;
	}
}

/*
object selecting which profile (for the DESCRIPTION) should be used for an element
*/
class sushee_DescriptionProfileManager extends SusheeObject{

	function sushee_DescriptionProfileManager(){
		$this->profileFactory = new Sushee_ElementProfileFactory();
		$this->profiles = new Vector();
	}

	function setProfile($key,$profile){
		$this->profiles->add($key,$profile);
	}

	function getProfile($element){
		return $this->profileFactory->getProfile($element->getProfileNode())->desc_profile;
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