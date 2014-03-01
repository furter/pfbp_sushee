<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/searchoutputmanager.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/searchprofile.class.php");
require_once(dirname(__FILE__)."/../common/dependencies.class.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/console.class.php");
require_once(dirname(__FILE__)."/../common/comments.inc.php");
require_once(dirname(__FILE__)."/../common/categories.inc.php");
require_once(dirname(__FILE__)."/../common/dependencies.inc.php");
require_once(dirname(__FILE__)."/../common/infoxml.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/omnilinks.class.php");

/*
object describing a sushee element ready to output
*/

class Sushee_SearchOutputElement extends SusheeObject{

	var $module;
	var $ID;
	var $depth = 1;
	var $security;
	var $attributes;
	var $row;
	var $info_xml;
	var $IsPrivate;
	var $type;
	var $profileNode;
	var $more;
	var $dependencies;
	var $dependencies_hits; // the number of dependencies by type (its a vector with an entry for each dependencytype ID)

	function getUniqueID(){
		return $this->module.'_'.$this->ID;
	}

	function getID(){
		return $this->ID;
	}

	function getRow(){
		return $this->row;
	}

	function getProfileNode(){
		return $this->profileNode;
	}

	function getModule(){
		return moduleInfo($this->module);
	}

	function setDependenciesHits($depType,$hits){
		$this->dependencies_hits[$depType->getID()] = $hits;
	}

	function getDependenciesHits($depType){
		return $this->dependencies_hits[$depType->getID()];
	}

	function addDepType($depType){
		$this->dependencies_hits[$depType->getID()] = 0;
		$this->dependencies[$depType->getID()] = array();
	}

	function addDependency($depType,$dep_output_elt){
		$dep_output_elt->depth = $this->depth + 1;
		$this->dependencies[$depType->getID()][] = $dep_output_elt;
		$this->dependencies_hits[$depType->getID()]++;
	}

	function addAdditionalContent($more){
		$this->more.= $more;
	}

	function getAdditionalContent(){
		return $this->more;
	}

	function getDepth(){
		return $this->depth;
	}

}

/*
object describing a set of elements for which we want the dependencies of a certain deptype
*/
class Sushee_SearchOutputDependenciesSet extends SusheeObject{

	var $depTypeID;
	var $elemIDs = array();
	var $profileNode;
	var $mode = SUSHEE_DEP_NORMAL_MODE;
	var $hitsonly = false;

	function Sushee_SearchOutputDependenciesSet($depType){
		$this->depTypeID = $depType->getID();
		// we need to save the mode, because we do not keep the deptype itself but only its ID
		if($depType->IsReverseMode()){
			$this->setReverseMode();
		}
	}

	function getDepType(){
		// when re-creating the deptype, we have to re-set the mode we have kept in the constructor
		$depType = depType($this->depTypeID);
		if($this->IsReverseMode()){
			$depType->setReverseMode();
		}
		return $depType;
	}

	function setProfileNode($profileNode){
		$this->profileNode = $profileNode;
	}

	function getProfileNode(){
		return $this->profileNode;
	}

	function getElementIDs(){
		return $this->elemIDs;
	}

	// using the dependency in the other direction ?
	function setReverseMode(){
		$this->mode = SUSHEE_DEP_REVERSE_MODE;
	}

	function IsReverseMode(){
		return ($this->mode == SUSHEE_DEP_REVERSE_MODE);
	}

	// returning only the element counts and not the data
	function enableHitsOnly(){
		$this->hitsonly = true;
	}

	function hitsOnlyEnabled(){
		return $this->hitsonly;
	}
}


/*
object managing the xsushee output having a SQL result set and a profile described by a XML node
*/
class Sushee_SearchOutputManager extends SusheeObject{

	var $moduleID;
	var $depth;
	var $main_rs;
	var $files_collect = false;
	var $used_files;
	var $profile_config;

	// this array will be used by generateElementXML to rebuild the complete XML tree
	var $elementsServicesXML = array();

	var $operationNode = false; // at the moment only used in search.inc.php, and only useful for postprocessing

	function Sushee_SearchOutputManager(){
		$this->depth = false;
	}

	// operation node is the node containing the profile node (RETURN or WITH)
	function setOperationnode($node){
		$this->operationNode = $node;
	}

	function getOperationnode(){
		return $this->operationNode;
	}

	// for compatibility, we kept this strange config array that can contain a profile node or a profile name
	function setProfileConfig($profile_config){
		$this->profile_config = $profile_config;
	}

	// allows to collect every file contained in the elements returned in the output, to have an archive with th datas and the files used
	function setFilesCollect($bool){
		$this->files_collect = $bool;
		if($bool && !is_array($this->used_files))
			$this->used_files = array();
	}

	// returns the files used in the elements present in the output
	function getFiles(){
		return $this->used_files;
	}

	// the result set from which we are going to produce the xsushee output
	function setResultSet(/* SQL ResultSet */$rs){
		$this->main_rs = $rs;
	}

	// the depth is how many levels of elements the user wants to have in his output
	function setDepth($depth){
		$this->depth = $depth;
	}

	// module (object type) of the elements returned in the first level
	function setModule($moduleID){
		if(is_numeric($moduleID))
			$this->moduleID = $moduleID;
		if(is_object($moduleID))
			$this->moduleID = $moduleID->getID();
	}

	function getModule(){
		return moduleInfo($this->moduleID);
	}

	// element obj is an object containing the different parts of the output of an element
	function buildOutputElement($moduleInfo,$elem,$profileNode){
		$ID = $elem['ID'];
		$completeID = $moduleInfo->getName().'_'.$ID;
		$output_elt = new Sushee_SearchOutputElement();

		$output_elt->ID = $ID;

		if ($elem['DependencyTypeID']!=0){
			$dependencyType = depType($elem['DependencyTypeID']);
			if ($dependencyType->loaded)
				$attributes.= ' linkType="'.$dependencyType->name.'"';
		}


		$output_elt->attributes = $attributes;
		$moduleName = strtoupper($moduleInfo->getName());
		$output_elt->module = $moduleName;

		// saving the security on the element to display it in the XML
		if(Sushee_Request::isSecured()){
			// private by default
			$security = '0';
			// writable element
			if($moduleInfo->isElementAuthorized($elem,'W')){
				$security = 'W';
			// readonly element
			}else if($moduleInfo->isElementAuthorized($elem,'R')){
				$security = 'R';
			}
			$output_elt->security = $security;
		}

		$output_elt->row = $elem;
		$output_elt->profileNode = $profileNode;

		// check we are not in private mode
		$output_elt->IsPrivate = $this->getPrivacy($moduleInfo,$elem);

		return $output_elt;
	}

	// returns the complete output XML
	function getXML(){

		// resultset is an error message and not a regular SQL result set
		if (is_string($this->main_rs) || !$this->main_rs )
			return '';
		// resultset is empty or is an object unusable by this class
		if (is_object($this->main_rs) && $this->main_rs->RecordCount()==0)
			return '';

		// ------------------------------------------------
		// PROFILE INITIALIZATION AND DEFAULT PROFILE
		// ------------------------------------------------

		// for compat: profile can be given as an array, with the name of the profile (see in this->handleFreelinks)
		if($this->profile_config)
		{
			if($this->profile_config['profile_name'])
			{
				$xml = new XML('<RETURN profile="'.$this->profile_config['profile_name'].'"/>');
				$profileNode = $xml->getElement('/RETURN');
			}
			else
			{
				$profileNode = new XMLNode($this->profile_config['profile_xml'],$this->profile_config['profile_path']);
			}
		}
		else if($this->getOperationnode())
		{
			$profileNode = $this->getOperationnode()->getElement('/RETURN');
			if(!$profileNode)
			{
				$profileNode = $this->getOperationnode()->getElement('/*[1]/WITH');
				// empty WITH node would return INFO, DESCRIPTION, CATEGORIES (compat only because WITH is DEPRECATED)
				// was intended for the website to be easier to program, returning immediately all informations necessary
				if($profileNode && !$profileNode->getAttribute('profile') && !$profileNode->exists('*') && Sushee_Request::isProjectRequest() && !Sushee_Request::isSecured())
				{
					$profileNode->appendChild('<INFO/>');
					$profileNode->appendChild('<DESCRIPTIONS/>');
					$profileNode->appendChild('<CATEGORIES/>');
				}
			}
		}
		
		// oneHit attribute allows to have a complete return when there is only one result in the search, allowing the Flash OS to display it immediately
		if($profileNode && $profileNode->getAttribute('oneHit')==='true')
		{
			$xml = new XML('<RETURN profile="complete"></RETURN>');
			$profileNode = $xml->getElement('/RETURN');
		}

		if(!$profileNode)
		{
			// default profileNode
			if(Sushee_Request::isProjectRequest() && !Sushee_Request::isSecured())
			{
				// means its a request on a website built with sushee
				$xml = new XML('<RETURN profile="publication"></RETURN>');
			}
			else
			{
				$xml = new XML('<RETURN><INFO/></RETURN>');
			}
			$profileNode = $xml->getElement('/RETURN');
		}

		if($profileNode->valueOf('@profile'))
		{
			Sushee_ElementProfile::getPredefinedProfileNode($profileNode,$profileNode->valueOf('@profile'));
		}
		else if($profileNode->nodeName()=='RETURN' && !$profileNode->exists('*'))
		{
			// case of an empty RETURN
			$profileNode->appendChild('<INFO/>');
		}

		// given depth of dependencies
		$this->depth = $profileNode->getAttribute('depth');
		$depthInRequest = true;
		if(!$this->depth)
		{
			// deducing it from the request : counting how deep the user wants his result
			$this->depth = $this->computeMaxDepth($profileNode);
			$depthInRequest = false;
		}

		$db_conn = db_connect();
		$main_rs = $this->main_rs;
		$moduleInfo = moduleInfo($this->moduleID);
		if (is_array($main_rs))
		{
			$elem = $main_rs;
		}
		else
		{
			$elem = $main_rs->FetchRow();
		}

		$moduleName = strtoupper($moduleInfo->name);
		$index = 0;

		// pushing root elements (first level) into an array to handle them
		$root_elements = array();
		$to_handle = array();
		$previous_level_elements = array();
		$elements_wdesc_by_module = array();
		$elements_wcateg_by_module = array();
		$elements_wcomments_by_module = array();

		while($elem)
		{
			$output_elt = $this->buildOutputElement($moduleInfo,$elem,$profileNode);
			if ($main_rs->result_page)
			{
				$this_index = $main_rs->startIndex+$index+1;
				$output_elt->attributes.=" onTotalCount='$this_index'";
			}

			$to_handle[] = $output_elt;
			$root_elements[] = $output_elt;
			if (is_array($main_rs))
			{
				$elem = FALSE;
			}
			else
			{
				$elem = $main_rs->FetchRow();
			}
			$index++;
		}

		// ------------------------------------------------
		// TREATING EACH ELEMENT, AND MANAGING ITS CHILDREN IF NECESSARY (AND PUSHING THEM INTO THE HANDLE ARRAY)
		// ------------------------------------------------

		$next_level = array();
		$this->profileFactory = new Sushee_ElementProfileFactory();
		$descriptionProfileManager = new sushee_DescriptionProfileManager();

		while($output_elt = array_shift($to_handle))
		{
			$moduleInfo = moduleInfo($output_elt->module);

			// building the profile
			$profile = $this->profileFactory->getProfile($output_elt->getProfileNode());
			$descriptionProfileManager->setProfile($output_elt->getProfileNode()->getUniqueID(),$profile->desc_profile);

			// handling services
			if($profile->returnInfo())
			{
				$output_elt->info_xml = $this->generateInfoXML($moduleInfo,$output_elt->getRow(),$profile);
			}

			$moduleID = $moduleInfo->getID();
			$ID = $output_elt->getID();
			$elem = $output_elt->getRow();

			// returning DESCRIPTIONS, saving for further massive handling of DESCRIPTIONS
			if ($profile->returnDescriptions() && $moduleInfo->getServiceSecurity('description',$elem)!=='0')
			{
				if(!isset($elements_wdesc_by_module[$moduleID][$ID]))
				{
					// dont replace if already existing
					$elements_wdesc_by_module[$moduleID][$ID] = $output_elt;
				}
			}

			// returning CATEGORIES, saving for further massive handling of CATEGORIES
			if ($profile->returnCategories() && $moduleInfo->getServiceSecurity('category',$elem)!=='0')
			{
				if(!isset($elements_wcateg_by_module[$moduleID][$ID]))
				{
					// dont replace if already existing
					$elements_wcateg_by_module[$moduleID][$ID] = $output_elt;
				}
			}

			// returning COMMENTS, saving for further massive handling of COMMENTS
			if ($profile->returnComments() && $moduleInfo->getServiceSecurity('comment',$elem)!=='0')
			{
				if(!isset($elements_wcomments_by_module[$moduleID][$ID]))
				{
					// dont replace if already existing
					$elements_wcomments_by_module[$moduleID][$ID] = $output_elt;
				}
			}

			// returning OMNILINKS, saving for further massive handling of OMNILINKS
			if ($profile->returnOmnilinks() && $moduleInfo->getServiceSecurity('omnilink',$elem)!=='0')
			{
				$output_elt->omnilinks_xml = $this->handleOmnilinks($moduleInfo,$ID,$profile);
			}

			// handling dependencies of element if not yet at maximum depth
			if($profile->returnDependencies() && $moduleInfo->getServiceSecurity('dependency',$elem)!=='0')
			{
				if($this->depth == 'all' || $output_elt->getDepth() < $this->depth)
				{
					$alltypes_hitsonly = false;

					// ------------------------------------------------
					// DEPENDENCIES PROFILING : DETERMINING WHAT IS TO RETURN
					/// ------------------------------------------------

					// keeping elements in one array in order to attach them their dependencies once we got them
					$previous_level_elements[$output_elt->getUniqueID()] = $output_elt;
					$depTypeSet = false;
					// specific deptypes asked in request
					$typesContainerNode = $output_elt->profileNode;
					// if there is a DEPENDENCIES, looking into it. If not looking into the current profile node
					if($output_elt->profileNode->getElement('DEPENDENCIES'))
					{
						$typesContainerNode = $output_elt->profileNode->getElement('DEPENDENCIES');
						// returning only the hits and no ELEMENT node 
						// <DEPENDENCIES hits-only="true"/>
						if($typesContainerNode->getAttribute('hits-only')==='true')
						{
							$alltypes_hitsonly = true;
						}
					}
	
					// looking the DEPENDENCY nodes
					$depTypeNodes = $typesContainerNode->getElements('DEPENDENCY[@type]');
					if(sizeof($depTypeNodes))
					{
						$depTypeSet = new Vector();
						foreach($depTypeNodes as $typeNode)
						{
							$dependencyType = &new dependencyType($typeNode->getAttribute('type'));
							$depTypeSet->add($dependencyType->getID(),$dependencyType);
						}
					}
					else if(( ($output_elt->getDepth() <= $this->depth  || $this->depth == 'all' ) && $depthInRequest) || $typesContainerNode)
					{
						// depth given in request and we are not at this depth yet, we can go on
						// we take all the deps : 
						// - if depth attribute was set in the developer request 
						// - if DEPENDENCIES is set without DEPENDENCY nodes inside

						// taking all deptypes for the origin module
						$depTypeSet = new DependencyTypeSet($moduleInfo->getID());
					}
					
					if($depTypeSet)
					{
						while($dependencyType = &$depTypeSet->next())
						{
							$hits_only = false;
							// from origin to target : normal mode
							// if mode="reverse" is put on the DEPENDENCY node, we switch to : from target to origin
							$mode = SUSHEE_DEP_NORMAL_MODE;

							// initializing the dependencies array
							$output_elt->addDepType($dependencyType);

							// determining where to look for profile and saving it
							$profileNode = $output_elt->profileNode;
							$depType_profileNode = $typesContainerNode->getElement('DEPENDENCY[@type="'.$dependencyType->getName().'"]');
							if($depType_profileNode)
							{
								$profileNode = $depType_profileNode;

								// checking the mode
								// <DEPENDENCY type="..." mode="reverse"/>
								if($depType_profileNode->getAttribute('mode') == SUSHEE_DEP_REVERSE_MODE)
								{
									$dependencyType->setReverseMode();
								}

								// returning only the hits and no ELEMENT node 
								// <DEPENDENCY type="..." hits-only="true"/>
								if($depType_profileNode->getAttribute('hits-only')=='true')
								{
									$hits_only = true;
								}
							}
							
							$deps_profileNode = $output_elt->profileNode->getElement('DEPENDENCIES[* and not(DEPENDENCY)]');
							if($deps_profileNode)
							{
								$profileNode = $deps_profileNode;

							}
							
							// for compatibility : Flash OS still uses the old notation <DEPENDENCIES><WITH>...
							if($profileNode->exists('WITH'))
							{
								$profileNode = $profileNode->getElement('WITH');
							}

							// if profile is empty, taking the profile of the superior level
							if(!$profileNode->exists('*'))
							{
								$profileNode = $output_elt->profileNode;
							}

							// all elements with a same deptype to explore are kept together and we make the request later once for all elements
							$depSetAssembler = $next_level[$dependencyType->getID()];
							if(!$depSetAssembler)
							{
								// it does not exist yet
								$depSetAssembler = new Sushee_SearchOutputDependenciesSet($dependencyType);
								$depSetAssembler->setProfileNode($profileNode);

								// enabling hits only (only counting the deps and not returning them)
								if($hits_only || $alltypes_hitsonly)
								{
									$depSetAssembler->enableHitsOnly();
								}

								$next_level[$dependencyType->getID()] = $depSetAssembler;
							}
							$depSetAssembler->elemIDs[] = $output_elt->getID();
						}
					}
				}
			}

			// adding the content added by eventual postprocessors
			$return_values = array();
			$process = $moduleInfo->postProcess('SEARCH',$ID,$this->getOperationnode(),$elem,$elem,$return_values /* fake array because, return values is not used */);
			$output_elt->addAdditionalContent($process->getResponse());

			// ------------------------------------------------
			// DEPENDENCIES RETRIEVING : TAKING THE DATA IN THE DB
			// ------------------------------------------------

			// we finished a level, looking what is to examine next
			// this is more optimal to do one sql request to get all elements for a specific deptype in one request
			if(sizeof($to_handle)==0){
				while($depSetAssembler = array_shift($next_level)){

					$dependencyType = $depSetAssembler->getDepType();
					$profileNode = $depSetAssembler->getProfileNode();

					// the module outputted at upper level
					$moduleOriginInfo = $dependencyType->getModuleOrigin();
					// the module outputted at this level
					$moduleTargetInfo = $dependencyType->getModuleTarget();


					if($depSetAssembler->hitsOnlyEnabled()){
						// taking only the element count
						$returned_str = 'COUNT(element.`ID`) AS hits,dep.`'.$dependencyType->getOriginFieldname().'`';
					}else{
						// taking the element datas
						$returned_str = '';
						$returned_info = $profileNode->getElements('/INFO/*');
						$which_element = 'element';
						if(sizeof($returned_info)>0){
							foreach ($returned_info as $info_node){
								$fieldname = $moduleTargetInfo->getFieldName($info_node->nodeName());
								if($fieldname && $fieldname != 'ID'){ // if fieldname exists, and ID is added automatically
									$returned_str .= $which_element.'.`'.$fieldname.'`,';
								}
							}
							$returned_str .= $which_element.'.`ID`';
						}else{
							$returned_str .= $which_element.'.*';
						}
						$returned_str = 'DISTINCT '.$returned_str;
						// the info specific to the dependency
						$returned_str.=",dep.`Comment` AS DepComment,dep.`DepInfo`,dep.`".$dependencyType->getOriginFieldname()."`";
					}

					// SQL to get the dependencies
					// NB: reverse mode is handled by DepType class : it returns the right fieldname
					$origin_cond = '(dep.`'.$dependencyType->getOriginFieldname().'` IN ('.implode(',',$depSetAssembler->getElementIDs()).'))';

					$sql = "SELECT 
								".$returned_str." 
							FROM 
								`".$moduleTargetInfo->getTableName()."` AS element,
								`".$dependencyType->getTablename()."` AS dep 
							WHERE 
									( 
										dep.`".$dependencyType->getOriginFieldname()."` IN (".implode(',',$depSetAssembler->getElementIDs()).") 
									) 
								AND  
									dep.`DependencyTypeID` = '".$dependencyType->getIDInDatabase()."' 
								AND 
									element.`ID` = dep.`".$dependencyType->getTargetFieldname()."` 
								AND 
									element.`Activity` = 1";

					// MEDIA are only returned if they are PUBLISHED (in the publication, of course in admin, they are returned)
					$request = new Sushee_Request();
					if ($request->isProjectRequest() && $moduleTargetInfo->getName()=='media' && !($GLOBALS["take_unpublished"]===true)){
						$sql.=' AND element.`Published` = 1 ';
					}

					// Grouping the deps by their origin, to have hits by elements
					if($depSetAssembler->hitsOnlyEnabled()){
						$sql.=' GROUP BY dep.`'.$dependencyType->getOriginFieldname().'`';
					}

					// ordering in the saved order
					$sql.=" ORDER BY dep.`".$dependencyType->getOriginFieldname()."`,dep.`DependencyTypeID`,dep.`".$dependencyType->getOrderingFieldname()."` ASC";
					sql_log($sql);
					$dep_rs = $db_conn->Execute($sql);

					if($dep_rs)
					{
						while($dep_row = $dep_rs->fetchRow())
						{
							$origin_completeID = $moduleOriginInfo->getxSusheeName().'_'.$dep_row[$dependencyType->getOriginFieldname()];
							$output_elt = $previous_level_elements[$origin_completeID];
							if(!$output_elt)
							{
								// try parent module for dependencies of extended module
								$origin_completeID = $moduleInfo->getxSusheeName().'_'.$dep_row[$dependencyType->getOriginFieldname()];
								$output_elt = $previous_level_elements[$origin_completeID];
								if(!$output_elt)
								{
									throw new SusheeException('Problem encountered while building result tree : element '.$origin_completeID.' could not be retrieved to attach its children');
								}
							}

							if($depSetAssembler->hitsOnlyEnabled())
							{
								// storing only the deps count
								$output_elt->setDependenciesHits($dependencyType,$dep_row['hits']);
							}
							else
							{
								// storing the datas over the element
								$dep_output_elt = $this->buildOutputElement($moduleTargetInfo,$dep_row,$profileNode);
								$to_handle[] = $dep_output_elt;
								
								$more='<DEPINFO>'.$dep_row['DepInfo'].'</DEPINFO>';
								$more.='<COMMENT>'.$dep_row['DepComment'].'</COMMENT>'; // DepComment is not the real name of the field, but it was aliased like that in the SQL request in order to avoid confusion with any Comment field in the element
								$dep_output_elt->addAdditionalContent($more);
								
								$output_elt->addDependency($dependencyType,$dep_output_elt);
							}
						}
					}
				}
				$next_level = array();
				$previous_level_elements = array();
			}
			$GLOBALS["TotalNectilElements"]++;
		}

		// ------------------------------------------------
		// SERVICES MANAGEMENT, COLLECTING SERVICE DATA FOR EVERY ELEMENT RETURNED
		// ------------------------------------------------
		// initializing the array that will contain the services XML for each elements
		// this array will be used by generateElementXML to rebuild the complete XML tree

		foreach($elements_wdesc_by_module as $moduleID=>$elements){
			$moduleInfo = moduleInfo($moduleID);
			$descManager = new descriptionsOutputManager($moduleID,$elements);
			$descManager->setProfileManager($descriptionProfileManager);
			$descriptionSetVector = &$descManager->getDescriptionSets();
			$descriptionSetVector->reset();
			while($descSet = &$descriptionSetVector->next()){
				$xml = $descSet->getXML();
				$this->setServiceXML('description',$moduleInfo,$descSet->elementID,$xml);
			}
			if($this->files_collect === true){
				$this->used_files = array_merge($this->used_files,$descManager->getFiles());
			}

		}
		foreach($elements_wcateg_by_module as $moduleID=>$elements){
			$moduleInfo = moduleInfo($moduleID);
			$rs = getCategories($moduleID,$elements);
			if (is_object($rs)){
				while($service = $rs->FetchRow()){
					$serviceElemID = $service['TargetID'];
					$this->setServiceXML('category',$moduleInfo,$serviceElemID,generateCategoriesXML($service));
				}
			}
		}
		foreach($elements_wcomments_by_module as $moduleID=>$elements){
			$moduleInfo = moduleInfo($moduleID);
			$rs = getComments($moduleInfo->getID(),$elements);
			if (is_object($rs)){
				while($service = $rs->FetchRow()){
					$serviceElemID = $service['TargetID'];
					$this->setServiceXML('comment',$moduleInfo,$serviceElemID,generateCommentsXML($service,$desc_output));
				}
			}
		}

		// ------------------------------------------------
		// BUILDING THE FINAL XML TREE AS A TEXT STRING
		// ------------------------------------------------
		foreach($root_elements as $output_elt){
			$query_result.=$this->generateElementXML(0,$output_elt,$isGet);
		}
		return $query_result;
	}

	function setServiceXML($service,$moduleInfo,$elemID,$xml){
		$this->elementsServicesXML[$service][$moduleInfo->getID()][$elemID].= $xml;
	}

	function getServiceXML($service,$moduleInfo,$elemID){
		$xml = $this->elementsServicesXML[$service][$moduleInfo->getID()][$elemID];
		return $xml;
	}

	// returns the privacy of a single element (a private element is an element that user cannot see because he is NOT one of the owners)
	function getPrivacy($moduleInfo,$elem){
		// check we are not in private mode
		$privacy = false;
		if($moduleInfo->IsPrivacySensitive){
			if($moduleInfo->isElementAuthorized($elem)){
				$privacy = false;
			}else
				$privacy = true;
		}
		// check we are not limited to a subset of this module (virtual module)
		if($moduleInfo->composite===true){
			if($moduleInfo->getActionSecurity('SEARCH',$elem)===false){
				$privacy = true;
			}
		}
		// if it's the user's own contact, we can show it
		if( $moduleInfo->name == 'contact' && $elem['ID'] == OfficityUser::getID()){
			$privacy = false;
		}
		return $privacy;
	}

	// returns the output in the INFO node
	function generateInfoXML($moduleInfo,$elem,$profile){

		// authorized fields
		$fields_array = $moduleInfo->getFieldsBySecurity('R');

		// fields asked for return in XML
		if (is_object($profile)){
			$profile_array = $profile->getIncludedFields();
		}else{
			$profile_array = false;
		}
		// user own contact fiche is completely readable to him, even if he's got very few rights
		$own_contact = ($moduleInfo->getName() == 'contact' && $elem['ID'] === OfficityUser::getID());
		if($own_contact){
			$fields_array = $moduleInfo->getFieldsBySecurity('0');
		}

		if($profile->getInfoMode() == 'read-only'){

			$infoXML = $this->generateInfoXMLReadOnly($moduleInfo,$elem,$fields_array,$profile_array,$profile->desc_output);

		}else{

			$infoGenerator = new NectilElementInfo($moduleInfo->getID(),$elem['ID'],$elem);
			if(is_object($profile)){
				$infoGenerator->setProfile($profile->getInfoProfile());
			}
			$infoGenerator->setSecurityProfile($fields_array);

			$infoXML = $infoGenerator->getXML();
		}
		return $infoXML;
	}


	// returns the XML for the INFO node, but in the special read-only mode, more compact (fields are written in attributes instead of node)
	function generateInfoXMLReadOnly(&$moduleInfo,&$elem,&$fields_array,$profile_array){
		$str='<INFO ';
		if ($profile_array===FALSE || $profile_array===NULL){
			$fields_nbr = count($fields_array);

			for($i=0;$i<$fields_nbr;$i++){
				$n=$fields_array[$i];
				$value=$elem[$n];
				$value = str_replace(array("\r\n", "\r", "\n"), '', $value);
				if (!$moduleInfo->isXMLField($fields_array[$i])){
					$str.=strtoupper($n).'="'.encode_to_XML($value).'" ';
				}
			}
		}else{
			$fields_nbr = count($fields_array);
			$profile_nbr = count($profile_array);
			$fields_ok = 0;
			for($i=0;$i<$fields_nbr;$i++){
				$n=$fields_array[$i];
				$value=$elem[$n];
				$value = str_replace(array("\r\n", "\r", "\n"), '', $value);
				//$n=strtoupper($n);
				$asked = FALSE;
				if (isset($profile_array[strtoupper($n)]))
					$asked = TRUE;
				if ($asked){
					if (!$moduleInfo->isXMLField($fields_array[$i])){
						$str.=strtoupper($n).'="'.encode_to_XML($value).'" ';
					}
				}
			}
		}
		$str.='/>';
		return $str;
	}

	// builds the complete output for an element, using the different XML parts
	function generateElementXML($this_depth,&$element_obj,$isGet=false){
		$profile = $this->profileFactory->getProfile($element_obj->getProfileNode());

		$element_str='';
		$discard = false;

		if($element_obj->IsPrivate === true)
			$more_attributes.='private="true" ';

		$element_str.='<'.$element_obj->module.' depth="'.($this_depth+1).'" '.$more_attributes.'ID="'.$element_obj->getID().'"'.$element_obj->attributes;

		if(Sushee_Request::isSecured()){
			$element_str.=' security="'.$element_obj->security.'"';
		}
		$element_str.='>';
		if ($element_obj->IsPrivate !== true){
			$element_str.=$element_obj->info_xml;

			if($profile->returnDescriptions() ){
				$descXML = $this->getServiceXML('description',$element_obj->getModule(),$element_obj->getID());
				if($profile->desc_discarding === 'absent' && $descXML == ''){
					return '';
				}
				$element_str.='<DESCRIPTIONS>';
				$element_str.=$descXML;
				$element_str.='</DESCRIPTIONS>';
			}
			if($profile->returnCategories() ){
				$element_str.='<CATEGORIES>';
				$element_str.=$this->getServiceXML('category',$element_obj->getModule(),$element_obj->getID());
				$element_str.='</CATEGORIES>';
			}
			if($profile->returnComments() ){
				$element_str.='<COMMENTS>';
				$element_str.=$this->getServiceXML('comment',$element_obj->getModule(),$element_obj->getID());
				$element_str.='</COMMENTS>';
			}
			if($profile->returnDependencies() ){
				if(is_array($element_obj->dependencies)){
					$element_str.='<DEPENDENCIES>';
					foreach($element_obj->dependencies as $depID=>$deps){
						$dependencyType = depType($depID);
						$moduleTargetInfo = moduleInfo($dependencyType->ModuleTargetID);

						$element_str.='<DEPENDENCY type="'.$dependencyType->name.'" module="'.$moduleTargetInfo->name.'"  hits="'.$element_obj->getDependenciesHits($dependencyType).'">';

						foreach($deps as $dep){
							$element_str.=$this->generateElementXML($this_depth+1,$dep);
						}
						$element_str.='</DEPENDENCY>';
					}
					$element_str.='</DEPENDENCIES>';
				}
			}

			if($profile->returnOmnilinks())
				$element_str.=$element_obj->omnilinks_xml;

			$element_str.=$element_obj->getAdditionalContent();
		}

		$element_str.='</'.$element_obj->module.'>';

		return $element_str;
	}

	// builds the output for the omnilinks of an element
	// omnilinks are links that are not restrained to a certain type of object
	function handleOmnilinks($moduleInfo,$elemID,$profile){
		// taking the different types of omnilink for this module
		$types = new sushee_omnilinkTypeSet(/*$moduleInfo->getID()*/);

		$links_str = '';

		$profile_node = new XMLNode($profile->xml,$profile->path);

		// user asked for specific types
		if($profile_node->exists('OMNILINKS/OMNILINK')){
			$types_nodes = $profile_node->getElements('OMNILINKS/OMNILINK');
			$types = new Vector();
			foreach($types_nodes as $type_node){
				$type = new sushee_OmnilinkType($type_node->getAttribute('type'));
				if($type->loaded){
					$types->add($type->getName(),$type);
				}
			}
		}

		$links_str.='<OMNILINKS>';
		// looping through the different types
		while($type = $types->next()){
			$hits = 0;
			$omnilink_profile_node = $profile_node->getElement('OMNILINKS');
			if($profile_node->getElement('OMNILINKS/OMNILINK[@type="'.$type->getName().'"]')){
				$omnilink_profile_node = $profile_node->getElement('OMNILINKS/OMNILINK[@type="'.$type->getName().'"]');
			}
			$elements_str = '';
			// taking all links with this type
			// first from the element to the multimodule 
			$elt_omnilinks = new sushee_ElementOmnilinkers($type,$moduleInfo,$elemID);
			while($omnilink = $elt_omnilinks->next()){
				$element = $this->handleOmnilink($type,$omnilink_profile_node,$type->getModule(),$omnilink->getOmnilinkerID());
				$elements_str.= $element;
				if($element)
					$hits++;
			}

			// now from the element to the omnilinker (of one specific type)
			$elt_omnilinks = new sushee_ElementOmnilinked($type,$elemID);
			while($omnilink = $elt_omnilinks->next()){
				$element=$this->handleOmnilink($type,$omnilink_profile_node,$omnilink->getModuleTarget(),$omnilink->getElementID());
				$elements_str.= $element;
				if($element)
					$hits++;
			}

			$links_str.='<OMNILINK type="'.$type->getName().'" hits="'.$hits.'">';
			$links_str.=$elements_str;
			$links_str.='</OMNILINK>';
		}
		$links_str.='</OMNILINKS>';

		return $links_str;
	}

	// builds the output for one omnilink to a given element
	function handleOmnilink($type,$profile_node,$moduleInfo,$elementID){

		$output_manager = new Sushee_SearchOutputManager();
		$output_manager->setDepth(false);

		// composing a  xSushee request taking the target element
		$target_requestXML = new XML(
			'<GET name="omnilinked">
				<'.$moduleInfo->getxSusheeName().' ID="'.$elementID.'"/>
				<RETURN>'.$profile_node->copyOf('/*').'</RETURN>
			</GET>');
		$profile_path = '/GET/RETURN';

		// SQL request taking the target element
		$sql='';
		$rs = getResultSet($moduleInfo,$target_requestXML,'/*[1]',$sql);

		// asking an output manager to compose a xSushee response corresponding to the request
		$output_manager->setResultSet($rs);
		$output_manager->setOperationNode($target_requestXML->getFirstchild());
		$output_manager->setModule($moduleInfo->getID());
		$profile = array('profile_xml'=>$target_requestXML,'profile_path'=>$profile_path);
		$output_manager->setProfileConfig($profile);

		return $output_manager->getXML();
	}

	// count how deep the user wants to go. 
	private function computeMaxDepth($node){
		$children = $node->getElements('DEPENDENCY');
		if(sizeof($children) == 0){
			$children = $node->getElements('DEPENDENCIES/DEPENDENCY');
		}
		if(sizeof($children) == 0){
			$children = $node->getElements('DEPENDENCIES');
		}
		$maxDepth = 0;
		foreach($children as $childNode){
			$childDepth = $this->computeMaxDepth($childNode);
			if($childDepth > $maxDepth){
				$maxDepth = $childDepth;
			}
		}
		return 1 + $maxDepth;
	}
}