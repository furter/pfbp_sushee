<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/module.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/datas_structure.class.php");
require_once(dirname(__FILE__)."/../common/processor.class.php");
require_once(dirname(__FILE__)."/../common/susheesession.class.php");
require_once(dirname(__FILE__).'/../common/db_manip.class.php');
require_once(dirname(__FILE__).'/../common/namespace.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');

define('SUSHEE_POSTPROCESSOR',"postprocessor");
define('SUSHEE_PREPROCESSOR',"preprocessor");
define('NECTIL_SEARCHTEXT',"searchtext");

class modules extends SusheeObject{
	var $list;
	var $loaded;
	var $db_conn;
	var $modulesNames;
	var $index=0;

	function modules(){
			$this->list = array();
			$modulesNames = $this->modulesNames();
			foreach($modulesNames as $moduleName){
				$moduleInfo = moduleInfo($moduleName);
				if ($moduleInfo->loaded && $moduleName && $moduleInfo->getActionSecurity("SEARCH")!==false){
				$this->list[] = $moduleInfo->getName();
			}
		}
	}

	function modulesNames(){
		$request = new Sushee_Request();
		$db_conn = $request->getModuleDbConn();
		$recordSet = $db_conn->Execute('SELECT `Denomination` FROM `modules` ORDER BY `Denomination`');
		$modulesNames = array();
		while( $module = $recordSet->FetchRow() ){
			$modulesNames[] = $module['Denomination'];
		}
		return $modulesNames;
	}

	function reset(){
		//reset($this->list);
		$this->index = 0;
	}

	function next(){
		$moduleName = $this->list[$this->index];
		if(!$moduleName){
			return false;
		}
		$this->index++;
		return moduleInfo($moduleName);
	}

	function getLoginXML(){
		$this->reset();
		$xml='<MODULES>';
		while($moduleInfo = $this->next()){
			$xml.=$moduleInfo->getLoginXML();
		}
		$xml.='</MODULES>';
		return $xml;
	}

	function getLightSecurityXML(){
		$this->reset();
		$xml='<MODULES>';
		while($moduleInfo = $this->next()){
			$xml.=$moduleInfo->getLightSecurityXML();
		}
		$xml.='</MODULES>';
		return $xml;
	}
}
/*
Cette classe contient les infos d'un module et les infos de securite !!
*/
class moduleInfo extends SusheeObject
{
	var $ID;
	var $name;
	var $tableName;
	var $tableMasterName;
	var $privateModule = false;
	var $forbiddenFields; // some fields are never allowed for modification
	var $actions;
	var $tableFields;
	var $_lastError;
	var $loaded;
	var $fieldsBySecurity;
	var $fieldsWithXML;
	var $isMandatory;
	var $services;
	var $depTypes;
	var $composite;
	var $virtualIDs;
	var $virtualKey;
	var $teamIDs;
	var $advancedSecurity = false; // multigroups and multiowners security

	function getName()
	{
		return $this->name;
	}

	function getxSusheeName()
	{
		return strtoupper($this->getName());
	}

	function getTablename()
	{
		return $this->tableName;
	}

	function getLastError()
	{
		return $this->_lastError;
	}

	function getID()
	{
		return $this->ID;
	}

	function isNative()
	{
		return ($this->getID() < 1024);
	}

	function isExtension()
	{
		return ($this->extends != '');
	}

	function getParentModule()
	{
		if ($this->extends != '')
		{
			return moduleInfo($this->extends);
		}
		else
		{
			return false;
		}
	}

	function getTable()
	{
		if(!$this->table)
		{
			$this->table = new ModuleDatabaseTable($this->tableName);
			$this->table->setModule($this);
		}
		return $this->table;
	}

	function __sleep()
	{
		// we dont want objects ModuleDatabaseTable and TableField to be saved in session : that would be too heavy
		$this->table = null;
		foreach($this->tableFields as $name)
		{
			unset($this->tableFields['object']);
		}
	}

	function getNameSpace()
	{
		$explosion = explode(':',$this->name);
		if(sizeof($explosion)>1)
		{
			$ns = new SusheeNamespace($explosion[0]);
			return $ns;
		}
		else
		{
			return false;
		}
	}

	function getXML()
	{
		$xml ='<MODULE ID="'.$this->getID().'">';
		$xml.=	'<DENOMINATION>'.$this->getName().'</DENOMINATION>';
		$xml.=	'<FIELDS>';
		$nql = new NQL(false);
		$nql->addCommand(
			'<SEARCH>
				<FIELD module="'.$this->getName().'"/>
				<RETURN>
					<INFO>
						<DENOMINATION/>
						<FIELDNAME/>
						<TYPE/>
						<SQLTYPE/>
						<DEFAULTVALUE/>
						<LISTNAME/>
						<SEARCHABLE/>
						<PRESET/>
					</INFO>
					<DESCRIPTION>
						<TITLE/>
						<SUMMARY/>
					</DESCRIPTION>
				</RETURN>
			</SEARCH>');
		$nql->execute();
		$result = $nql->getElement('/RESPONSE/RESULTS');
		$xml.=$result->toString('./*');
		$xml.=	'</FIELDS>';
		$xml.=	'<DEPENDENCIES>';

		$depTypesSet = new DependencyTypeSet($this->getID());
		while($depType = $depTypesSet->next())
		{
			$moduleTarget = $depType->getModuleTarget();
			$xml.='<DEPENDENCY type="'.$depType->getName().'" module="'.$moduleTarget->getName().'"/>';
		}
		$xml.=	'</DEPENDENCIES>';
		$xml.='</MODULE>';
		return $xml;
	}

	function getLoginXML()
	{
		$modulename = $this->getName();
		$xml ='<MODULE ID="'.$this->getID().'">';
		$xml.=	'<'.strtoupper($modulename).' />';
		$xml.=	'<INFO><DENOMINATION>'.$modulename.'</DENOMINATION></INFO>';
		$xml.=	$this->getXMLSecurity();
		$xml.='</MODULE>';
		return $xml;
	}

	function getLightSecurityXML()
	{
		$modulename = $this->getName();
		$xml ='<MODULE name="'.encode_to_xml($this->getName()).'" ID="'.$this->getID().'" security="'.$this->getGeneralSecurity().'">';
		$xml.='<'.strtoupper($modulename).' />';
		$xml.=$this->getActionsXMLSecurity();
		$xml.=$this->getServicesXMLSecurity();
		$xml.=$this->getDependenciesXMLSecurity();
		$xml.='</MODULE>';
		return $xml;
	}

	function getGeneralSecurity()
	{
		if ($this->getActionSecurity('CREATE'))
		{
			return 'W';
		}
		else if ($this->getActionSecurity('SEARCH'))
		{
			return 'R';
		}
		return '0';
	}

	function registerFields()
	{
		// save the definition of the fields in the fields table
		require_once(dirname(__FILE__)."/../common/nectil_element.class.php");

		$table = $this->getTable();
		$fields = $table->getFields(); // using the list from the table itself as the list saved in the current object might be out-of-date
		$db_conn = db_connect();
		foreach($fields as $field)
		{
			$field->register();
		}
		// the new list has to be saved in session
		$this->clearInSession();
	}

	function registerProcessors()
	{
		$db_conn = db_connect();
		$sql = 'SELECT `ID` FROM  `modules_processors` WHERE `ModuleID`=\''.$this->getID().'\' ';
		$rs = $db_conn->execute($sql);
		if($rs)
		{
			while($row = $rs->fetchRow())
			{
				$type = new dependencyType('processors');
				$dep = new Dependency($type,$this->getID(),$row['ID']);
				$dep->create();
			}
		}
	}

	function getFields()
	{
		require_once(dirname(__FILE__).'/../common/db_manip.class.php');

		$fields = array();
		$table = $this->getTable();
		foreach($this->tableFields as $field_def)
		{
			$field = new TableField();
			$field->setTable($table);
			$field->setModule($this);
			$field->setExistence(true); // for optim, cause we are sure the field exists, and it avoids a SQL request to check its existence
			$field->setName($field_def['REALNAME']);
			$ado_type = $field_def['TYPE'];

			$field->setAdoType($ado_type);
			$fields[$field->getName()] = $field;
		}
		return $fields;
	}

	function getField($name)
	{
		require_once(dirname(__FILE__).'/../common/db_manip.class.php');

		$table = $this->getTable();
		$name = strtoupper($name);

		// searching xsusheename, tableFields has an associative key on it
		if(isset($this->tableFields[$name]))
		{
			$field_def = $this->tableFields[$name];

			// we save an object describing and managing the field
			if(!isset($field_def['object']))
			{
				$field = new TableField();
				$field->setTable($table);
				$field->setModule($this);
				$field->setName($field_def['REALNAME']);
				$field->setExistence(true); // for optim, cause we are sure the field exists, and it avoids a SQL request to check its existence
				$ado_type = $field_def['TYPE'];
				$field->setAdoType($ado_type);

				$this->tableFields[$name]['object'] = $field;
			}
			else
			{
				$field = $field_def['object'];
			}
			return $field;
		}
		return false;
	}

	// returns the field indicating if an element is part of an extension
	// usually its name is the name of the extension : lig:family, boris:friend
	function getExtensionField()
	{
		return $this->getField($this->getName());
	}

	function moduleInfo($moduleName/* can be the ID too */,$userID=0)
	{
		$specific_db_conn = db_connect(FALSE);
		// taking the userID in the session if not given in argument
		if ($userID==0 && isset($_SESSION[$GLOBALS['nectil_url']]['SESSIONuserID']))
			$userID = $_SESSION[$GLOBALS['nectil_url']]['SESSIONuserID'];

		$request = new Sushee_Request();
		$db_conn = $request->getModuleDbConn();

		// --------
		// GETTING THE TABLENAME,MODULENAME AND MODULEID
		// --------

		if ( !is_numeric($moduleName) ){
			$moduleName = strtolower($moduleName);
			$sql = "SELECT * from `modules` WHERE `Denomination`=\"$moduleName\";";
		}else{
			$sql = "SELECT * from `modules` WHERE `ID`=\"$moduleName\";";
		}

		if (!$row = $db_conn->GetRow($sql))
		{
			$this->_lastError = "No module $moduleName in the modules table.";
			$this->loaded = FALSE;
			return FALSE;
		}
		else
		{
			$this->ID = $row["ID"];
			$this->name = $row["Denomination"];
			$this->extends = $row['Extends'];

			if($row['AdvancedSecurity'] == 1)
			{
				$this->advancedSecurity = true;
			}

			if ($row['PrivateModule'] == 1)
			{
				$this->privateModule = true;
			}

				$this->tableMasterName = $row['TableName'];
			$this->tableName = $this->tableMasterName;
		}

		if ($this->name=='contact' || $this->name=='modulekey' || $this->name=='keyring' || $this->name=='applicationkey' || $this->name=='template' || $this->name == 'field')
			$this->isMandatory = true;
		else
			$this->isMandatory = false;


		// allows to know whether the security is composed of several  virtual moduleKey
		$this->composite = false;
		$this->virtualIDs = array();

		// --------
		// GETTING THE FIELDS INFORMATIONS
		// --------

		// some fields are never allowed in modification
		$this->forbiddenFields = array('IsLocked'=>'R','CreationDate'=>'R','ModificationDate'=>'R','SearchText'=>'0');//'ID'=>'R','Activity'=>'R'

		if ($this->name=="mailing")
			$this->forbiddenFields['DataToSendXML']='0';
		if($this->name=="mail"){
			$this->forbiddenFields['RichText']='0';
			$this->forbiddenFields['MessageSource']='0';
			$this->forbiddenFields['UniqueID']='0';
		}


		$this->fieldsWithXML = array();
		if($this->name=='modulekey'){
			$this->fieldsWithXML=array('Fields'=>true,'Services'=>true,'Dependencies'=>true);
		}else if ($this->name=='applicationkey'){
			$this->fieldsWithXML=array('Permissions'=>true);
		}else if ($this->name=='resident'){
			$this->fieldsWithXML=array('Profile'=>true);
		}else if ($this->name=='mailing'){
			$this->fieldsWithXML=array('RecipientsSearch'=>true,'RecipientsXML'=>true,'MediasXML'=>true,'ExcludeRecipients'=>true);
		}else if ($this->name=='contact'){
			$this->fieldsWithXML=array('Prefs'=>true);
		}else if ($this->name=='mail'){
			$this->fieldsWithXML=array('StyledText'=>true);
		}else if ($this->name=='mailsaccount'){
			$this->fieldsWithXML=array('Config'=>true,'Signature'=>true);
		}else if ($this->name=='document'){
			$this->fieldsWithXML=array('BodyText'=>true);
		}else if($this->name=='batch'){
			$this->fieldsWithXML=array('Command'=>true,'Response'=>true);
		}else if($this->name=='cron'){
			$this->fieldsWithXML=array('Command'=>true);
		}else if($this->name=='cronlog'){
			$this->fieldsWithXML=array('Response'=>true);
		}
		// determining virtualKeys for each module
		if($this->name=='contact')
			$this->virtualKey ='ContactType';
		else if($this->name=='media')
			$this->virtualKey ='MediaType';
		else if($this->name=='group')
			$this->virtualKey ='IsTeam';
		else if($this->name=='mailing')
			$this->virtualKey ='Status';
		else if($this->name=='sound_art')
			$this->virtualKey ='Type';
		else if($this->name=='mail')
			$this->virtualKey ='Type';

		$field_array = $specific_db_conn->MetaColumns($this->tableName);
		//Returns an array of ADOFieldObject's, one field object for every column of $table. A field object is a class instance with (name, type, max_length)

		//////////////////////////////////////////////////
		// getting the permissions in the modulekeys
		//////////////////////////////////////////////////

		$count_sql = "SELECT COUNT( DISTINCT `ID` ) AS ct FROM `modulekeys` WHERE `ModuleToID`=".$this->ID.";";
		$count_row = $specific_db_conn->GetRow($count_sql);
		$this->IsPrivacySensitive = false;

		// initializing security arrays
		$this->tableFields = array();
		$this->actions = array();
		$this->privacies = array();
		$service_sql = "SELECT `Denomination` FROM `services`;";
		$service_rs = $db_conn->Execute($service_sql);
		$this->services = array();
		if ($service_rs){
			while($service_row = $service_rs->FetchRow()){
				$this->services[$service_row["Denomination"]]=array();
				$this->services[$service_row["Denomination"]]["SECURITY"][0]="W";
			}
		}
		// ---------
		// DEPTYPES
		// ---------
		$sql = "SELECT `ID`,`Denomination` FROM `dependencytypes` WHERE `ModuleOriginID`='".$this->ID."';";
		$deptypes_rs = $specific_db_conn->Execute($sql);
		$this->depTypes = getDependencyTypesArray($deptypes_rs);

		// ---------
		// FIELDS
		// ---------
		$virtualID = 0; // no virtualID
		if($field_array)
		{
			foreach($field_array as $field)
			{
				$tag = strtoupper($field->name);
				$type = $specific_db_conn->MetaType($field->type);
				$security_array = array();
				$security_array[$virtualID] = 'W';
				$this->tableFields[$tag] = array('SECURITY'=>$security_array,'REALNAME'=>$field->name,'TYPE'=>$type);

				// if the name of the field ends with XML, assuming its an XML Field (handling of these fields is different)
				if(substr($field->name,-3) == 'XML')
				{
					$this->fieldsWithXML[$field->name] = true;
				}
			}
		}
		// ---------
		// ACCESS IS SECURED : USER REQUEST 
		// SPECIAL CASE : IF NO EXISTING MODULEKEY FOR THE MODULE, OPEN ACCESS TO EVERYONE
		// ---------
		if (Sushee_Request::isSecured() && !$count_row['ct']=='0'){

			// no user connected, force no access
			if($userID === NULL){
				$xmlSecurityStr='<SECURITY/>';
				$this->buildSecurity($virtualID,$xmlSecurityStr);

			}else{
				// looking modulekeys on this module for user
				$sql = "SELECT perm.* FROM `modulekeys` AS perm LEFT JOIN `dependencies` AS dep ON (dep.`TargetID` = perm.`ID`) LEFT JOIN `dependencies` AS dep2 ON (dep2.`OriginID` = dep.`OriginID`) WHERE dep2.`TargetID` = '$userID' AND dep2.`DependencyTypeID` = 3 AND dep.`DependencyTypeID` = 4 AND perm.`ModuleToID` = '".$this->ID."';";

				$rs = $specific_db_conn->Execute($sql);
				$nb_modulekeys = 0;

				while($row = $rs->FetchRow()){
					// forcing complete access on modulekey and applicationkey if present, this way, we can't have a partial key on modulekey or application key (it would be a security hole)
					if ($this->name == 'modulekey' || $this->name == 'applicationkey'){
						$row['Create'] = 1;
						$row['Update'] = 1;
						$row['Delete'] = 1;
					}
					// a XML defining what security is defined for the user
					$xmlSecurityStr='<SECURITY>
										<ACTIONS>
											<SEARCH>'.$row['Search'].'</SEARCH>
											<CREATE>'.$row['Create'].'</CREATE>
											<UPDATE>'.$row['Update'].'</UPDATE>
											<DELETE>'.$row['Delete'].'</DELETE>
										</ACTIONS>
										<FIELDS>'.$row['Fields'].'</FIELDS>
										<SERVICES>'.$row['Services'].'</SERVICES>
										<DEPENDENCIES>'.$row['Dependencies'].'</DEPENDENCIES>
									</SECURITY>';

					// Is Virtual means the access to the module is not defined through the whole module
					// The user can then have more than one modulekey for the same module
					// A modulekey is defined for each value of a certain field. Ex: mediatype in module media
					// This allows to give access to only some part of a module. Ex: access to media of type News
					if($row['IsVirtual']==1){
						$this->composite = true;
						$virtualID = $row['VirtualID'];
					}
					if(!$row['VirtualID'])
						$row['VirtualID'] = 0; // 0 means no virtual key, and security for all elements
					$this->buildSecurity($row['VirtualID'],$xmlSecurityStr,$row['IsPrivate']);
					$nb_modulekeys++;
				}

				// no module key, default access
				if($nb_modulekeys==0){
					$xmlSecurityStr = $this->getDefaultAccess();

					$this->buildSecurity($virtualID,$xmlSecurityStr);
				}
			}
		// ---------
		// ACCESS IS NOT SECURED : DEVELOPER REQUEST
		// ---------
		}else{

			$services_array = getServicesArray();
			$services = "";
			foreach($services_array as $service){
				$n = $service['Denomination'];
				$services.='<'.$n.'>'.$service['SECURITY'].'</'.$n.'>';
			}
			// <!-- all fields,all services,all dependencytypes -->
			$xmlSecurityStr=
				'<SECURITY>
					<ACTIONS>
						<SEARCH>1</SEARCH>
						<CREATE>1</CREATE>
						<UPDATE>1</UPDATE>
						<DELETE>1</DELETE>
					</ACTIONS>
					<FIELDS></FIELDS>
					<SERVICES>'.$services.'</SERVICES>
					<DEPENDENCIES/>
				</SECURITY>'; 
			$this->buildSecurity($virtualID,$xmlSecurityStr);
		}

		// ---------
		// SPECIAL CASES OF PRIVACY
		// ---------

		// these modules are always private (user is the only one who can see its own elements, unless indicated in the element by resetting OwnerID)
		if( ($this->name == 'mail' || $this->name == 'mailsaccount' || $this->name == 'event' || $this->name == 'ticket') && !$GLOBALS["dev_request"]===TRUE){
			$this->IsPrivacySensitive = true;
			if(!isset($this->privacies[0]))
				$this->privacies[0] = true;
		}

		// group is always private, and we need to know which team the user is part of, because it gives him access to public elements of other users (GroupID indicates which team can access an element)
		if($this->name == 'group' && $userID){
			$this->IsPrivacySensitive = true;
			if(!isset($this->privacies[0]))
				$this->privacies[0] = true;
			$this->teamIDs = array();
			$group_sql = 'SELECT dep.`OriginID` FROM `dependencies` AS dep LEFT JOIN `groups` AS gp ON gp.`ID`=dep.`OriginID` WHERE gp.`IsTeam`=1 AND dep.`TargetID`='.$userID.' AND dep.`DependencyTypeID`=1;';
			sql_log($group_sql);
			$rs = $specific_db_conn->Execute($group_sql);
			while($row = $rs->FetchRow()){
				$this->teamIDs[] = $row['OriginID'];
			}
		}

		// if private module && security OK, switch (and create) user table
		if ($this->privateModule == true && $userID != 0 && $this->getGeneralSecurity() !== '0' )
		{
			$this->tableName = $this->tableMasterName.'_'.$_SESSION[$GLOBALS['nectil_url']]['SESSIONuserID'];

			// check existence of the private table
			$sql = 'SELECT `ID` FROM `'.$this->tableName.'` LIMIT 1;';
			$res = $db_conn->Execute($sql);

			if (!$res)
			{
				// table doesn't exist
				$sql = 'CREATE TABLE `'.$this->tableName.'` LIKE `'.$this->tableMasterName.'`;';
				$res = $db_conn->Execute($sql);

				if (!$sql)
				{
					// error creating the private table
					throw new SusheeException('Unable to initialize private module table in: '.$this->name);
				}

				// add default elements ID = 0
				$sql = 'INSERT `'.$this->tableName.'` SELECT * FROM `'.$this->tableMasterName.'` WHERE `ID` = 1;';
				$res = $db_conn->Execute($sql);
			}
		}

		$this->loaded = TRUE;
	}

	function getDefaultAccess(){
		switch($this->name){
			case 'field':
				$xmlSecurityStr=
					'<SECURITY>
						<ACTIONS>
							<SEARCH>1</SEARCH>
						</ACTIONS>
						<SERVICES>
							<description>R</description>
							<dependencies>R</dependencies>
						</SERVICES>
					</SECURITY>';
				break;
			case 'module':
				$xmlSecurityStr=
					'<SECURITY>
						<ACTIONS>
							<SEARCH>1</SEARCH>
						</ACTIONS>
						<SERVICES>
							<description>R</description>
							<dependencies>R</dependencies>
						</SERVICES>
						<DEPENDENCIES><fields>R</fields></DEPENDENCIES>
					</SECURITY>';
				break;
			case 'app':
				$xmlSecurityStr=
					'<SECURITY>
						<ACTIONS>
							<SEARCH>1</SEARCH>
						</ACTIONS>
						<SERVICES>
							<description>R</description>
							<dependencies>R</dependencies>
						</SERVICES>
						<DEPENDENCIES><officity:appNamespaces>R</officity:appNamespaces></DEPENDENCIES>
					</SECURITY>';
				break;
			case 'namespace':
				$xmlSecurityStr=
					'<SECURITY>
						<ACTIONS>
							<SEARCH>1</SEARCH>
						</ACTIONS>
						<SERVICES>
							<description>R</description>
							<dependencies>R</dependencies>
						</SERVICES>
						<DEPENDENCIES><officity:namespaceModules>R</officity:namespaceModules><officity:namespaceApps>R</officity:namespaceApps></DEPENDENCIES>
					</SECURITY>';
				break;
			default:
				$xmlSecurityStr='<SECURITY/>';
		}
		return $xmlSecurityStr;
	}

	function exists(){
		return $this->loaded;
	}

	function buildSecurity($virtualID,$xmlSecurityStr,$privacy=''){
		$xmlSecurity = new XML($xmlSecurityStr);
		$user = new Sushee_User();

		// privacy is the content of the IsPrivate field. 0 means we have to deal about privacy. D or nothing means we do not care
		if($privacy==='0' && $user->isAuthentified()){
			$this->IsPrivacySensitive = true;
		}

		$this->privacies[$virtualID] = true;

		// first parsing the xml to have all field security and use it if necessary
		$xml_fields_array = $xmlSecurity->getElements('/SECURITY/FIELDS[1]/*');
		$xml_fields_security = array();
		foreach($xml_fields_array as $xml_fields_node){
			$xml_fields_security[$xml_fields_node->nodeName()] = $xml_fields_node->valueOf();
		}

		foreach($this->tableFields as $tag=>$config){
			$fieldName = $config['REALNAME'];
			$security = 'W';
			if (!isset($this->forbiddenFields[$fieldName])){
				if (isset($xml_fields_security[$tag])) {
					$security = $xml_fields_security[$tag];
					if ($security!='R' && $security!='W' && $security!='0') {
						$security = 'W';
					}
				}
			}else{
				$security = $this->forbiddenFields[$fieldName];
			}

			$this->tableFields[$tag]['SECURITY'][$virtualID] = $security;
		}

		// determine which actions are authorized for this user
		$this->actions[$virtualID] = array('CREATE'=>false,'UPDATE'=>false,'DELETE'=>false,'SEARCH'=>false,'GET'=>false);// defaults

		$create = $xmlSecurity->getData('/SECURITY/ACTIONS/CREATE');
		if ($create){
			$this->actions[$virtualID]['CREATE']=true;

			if($virtualID!==0 ){
				// need to allow generic CREATE access, if we have CREATE access on a virtualkey
				if($this->actions[0]['CREATE']!==true )
					$this->actions[0]['CREATE'] = true;
			}
		}
		$update = $xmlSecurity->getData('/SECURITY/ACTIONS/UPDATE');
		if ($update){
			$this->actions[$virtualID]['UPDATE']=true;

			if($virtualID!==0 ){
				// need to allow generic UPDATE access, if we have UPDATE access on a virtualkey
				if($this->actions[0]['UPDATE']!==true )
					$this->actions[0]['UPDATE'] = true;
			}
		}
		$delete = $xmlSecurity->getData('/SECURITY/ACTIONS/DELETE');
		if ($delete){
			$this->actions[$virtualID]['DELETE']=true;

			if($virtualID!==0 ){
				// need to allow generic DELETE access, if we have DELETE access on a virtualkey
				if($this->actions[0]['DELETE']!==true )
					$this->actions[0]['DELETE'] = true;
			}
		}
		$search = $xmlSecurity->getData('/SECURITY/ACTIONS/SEARCH');
		if ($search){
			$this->actions[$virtualID]['SEARCH']=true;
			if($virtualID!==0){
				array_push($this->virtualIDs,$virtualID);

			}

			$this->actions[$virtualID]['GET']=true;

			if($virtualID!==0 ){
				// need to allow generic SEARCH access, if we have SEARCH access on a virtualkey
				if($this->actions[0]['GET']!==true )
					$this->actions[0]['GET'] = true;
				if($this->actions[0]['SEARCH']!==true )
					$this->actions[0]['SEARCH'] = true;
			}
		}
		// getting the security for the services : by default everything is authorized
		$xml_services_array = $xmlSecurity->getElements('/SECURITY/SERVICES/*');
		foreach($xml_services_array as $service_node){
			$nodeName =  $service_node->nodeName();
			if (isset($this->services[$nodeName])){
				$this->services[$nodeName]['SECURITY'][$virtualID] = $service_node->valueOf();
				if($virtualID!==0 ){
					if($this->getServiceSecurityLevel($nodeName,$this->services[$nodeName]['SECURITY'][$virtualID]) > $this->getServiceSecurityLevel($nodeName,$this->services[$nodeName]['SECURITY'][0]))
						$this->services[$nodeName]['SECURITY'][0] = $this->services[$nodeName]['SECURITY'][$virtualID];
				}
			}
		}
		// getting the security for the dependencyTypes
		$xml_depTypes_array = $xmlSecurity->getElements('/SECURITY/DEPENDENCIES/*');
		foreach($xml_depTypes_array as $depType_node){
			$nodeName =  $depType_node->nodeName();

			if (isset($this->depTypes[$nodeName])){
				$this->depTypes[$nodeName]['SECURITY'][$virtualID] = $depType_node->valueOf();
				if($this->getServiceSecurityLevel($nodeName,$this->depTypes[$nodeName]['SECURITY'][$virtualID]) > $this->getServiceSecurityLevel($nodeName,$this->depTypes[$nodeName]['SECURITY'][0]))
					$this->depTypes[$nodeName]['SECURITY'][0] = $this->depTypes[$nodeName]['SECURITY'][$virtualID];
			}
		}
	}
	//------------------------------------------------------
	function checkDescriptionLanguage($language,$element=false){
		$virtualID = $this->determineVirtualID($element);
		if(isset($this->descriptionLanguage[$virtualID])){
			return ($this->descriptionLanguage[$virtualID]==$language);
		}else
			return $this->getServiceSecurity('description',$element);
	}

	//------------------------------------------------------
	function isElementAuthorized($element,$requiredPermission='R'){

		$user = new NectilUser();
		$request = new Sushee_Request();
		if(!$user->isAuthentified() && !$request->isSecured()){ // considering all access are allowed
			return true;
		}
		if(!$user->isAuthentified() && $request->isSecured()){
			return false;
		}
		$virtualID = 0; // index of the virtual security (virtual security are used for having different security on medias depending of the mediatype)

		$userID = $user->getID();
		// if the element is the contact of the connected user, returning true, always
		if($this->getName()=='contact' && $element['ID']==$userID){
			return true;
		}

		if($this->composite){
			// composite means we have different keys for a same module (ex: for different mediatypes). At the moment, only Media allows that
			$virtualID = $element[$this->virtualKey];
			if(!in_array($virtualID,$this->virtualIDs))
				$virtualID = 0;
		}

		if(!isset($this->privacies[$virtualID])){
			return false;
		}else{
			// ---------------
			// Simple security
			// ---------------
			if($requiredPermission=='W' && !$this->actions[$virtualID]['UPDATE']){ // if user asks for write permission and module is read-only returning false
				// read-only access is signaled by setting permission on ID to R
				return false;
			}

			// Privacy is applied on some module automatically (e.g. mail) or on demande in the modulekey (IsPrivate = 0)
			if($this->IsPrivacySensitive == true){

				// the user is the owner of the element
				if($element['OwnerID']==$userID)
					return true;
				// the user is member of one the team that have access to the element
				if($user->isInTeam($element['GroupID'])){
					return true;
				}
				// ---------------
				// Advanced security
				// ---------------
				$advancedSecurity = $this->isAdvancedSecurityEnabled();
				if($advancedSecurity){
					// in advanced security all these fields have to be empty for the element to be public
					if($element['OwnerID']==0 && $element['GroupID']==0 && !$element['Owners'] && !$element['Groups'])
						return true;

					// clean the owners and groups as saved in the database  : we have after that 1857_R,2_W,334_R
					$cleanGroups = str_replace('g_','',$element['Groups']);
					$cleanOwners = str_replace('c_','',$element['Owners']);

					// making a vector with the Ids of each team and each owner authorized
					$groups = explode(',',$cleanGroups);
					$owners = explode(',',$cleanOwners);

					// CHECKING OWNERS
					// user is one of the owner and has write permissions (so of course read permissions too)
					if( in_array($userID.'_W',$owners) ){ 
						return true;
					}
					// use has read permission and that's the security level required
					if( in_array($userID.'_R',$owners) && $requiredPermission=='R' ){
						return true;
					}

					// CHECKING GROUPS
					// user is in one of the team owning the element
					foreach($groups as $groupSecurity){
						list($groupID,$groupPermission) = explode('_',$groupSecurity);
						if($user->isInTeam($groupID)){
							// group has write permission
							if($groupPermission=='W'){
								return true;
							}
							// group has read permission and that's the security level required
							if($groupPermission=='R' && $requiredPermission=='R'){
								return true;
							}
						}
					}


				}else{
					// element is public (simple security)
					if(($element['OwnerID']==0 || $element['OwnerID']==-1) && $element['GroupID']==0)
						return true;
				}


				return false;
			}else{
				return true;
			}
		}

	}

	//------------------------------------------------------
	function getServiceSecurityLevel($name,$value){
		switch($name){
			case 'description':
				switch($value){
					case 'W': return 100;
					case 'publisher': return 100;
					case 'reviser': return 75;
					case 'writer': return 50;
					case 'R': return 25;
					default: return 0;
				}
				break;
			default:
				switch($value){
					case 'W': return 100;
					case 'R': return 50;
					default: return 0;
				}
		}
	}

	function getWorkflowForwards($element=false){
		$virtualID = $this->determineVirtualID($element);
		if (is_array($this->workflow_forward[$virtualID]))
				return $this->workflow_forward[$virtualID];
		else
			return $this->workflow_forward[0];
	}

	//------------------------------------------------------
	function getSQLSecurity($element_name,$requestName='SEARCH'){
		// Security can be managed through 4 fields : OWNERID,GROUPID,OWNERS,GROUPS
		// OWNERID: one owner
		// OWNERS : multi owners (both can be used in parallel)

		// GROUPID: one owner group
		// GROUPS : multi owner groups (both can be used in parallel)

		// -------------------------------------------
		// general variables
		// -------------------------------------------
		$user = new Sushee_User();
		$userID = $user->getID();
		$isConnected = $user->isAuthenticated();

		$where_string = '';
		// -------------------------------------------
		// module specific security
		// -------------------------------------------
		// limiting access to user personal mails
		if($this->getName()=='mail' && $requestName!='GET' && $isConnected){
			return " AND ($element_name.`OwnerID`='".$userID."')";
		}
		// limiting access to the fields of the modules or to the module itself the user has permissions to use
		if($this->getName() == 'field' || $this->getName() == 'module'){
			$modules  = new modules();
			$modules->reset();
			$first = true;
			$moduleNames = '';
			while($moduleInfo = $modules->next()){
				if($moduleInfo->getActionSecurity("SEARCH")!= false){
					if(!$first){
						$moduleNames.=',';
					}
					$moduleNames.='"'.encodequote($moduleInfo->getName()).'"';
					$first = false;
				}
			}
			if($this->getName() == 'field'){
				$where_string.='/* Authorized modules fields only */ AND `Module` IN ('.$moduleNames.')';
			}else{
				$where_string.='/* Authorized modules only */ AND `Denomination` IN ('.$moduleNames.')';
			}
		}
		// limiting access to the apps the user has permissions to use
		if($this->getName() == 'app'){
			$apps = new ApplicationCollection();
			$appsIDs = '1';  // 1 is template
			while($app = $apps->next()){
				$appsIDs.=',';
				$appsIDs.="'".encodequote($app->getID())."'";
			}
			$where_string.='/* Authorized apps only */ AND `ID` IN ('.$appsIDs.')';
		}
		// -------------------------------------------
		// the security is not applicable on module templates
		// -------------------------------------------
		$template = " OR $element_name.`ID`='1'";
		if($this->name=='contact' && $isConnected){
			$own_contact = " OR $element_name.`ID`='".$userID."'";
		}
		// -------------------------------------------
		// SQL indicating that the element has no owner and no group
		// -------------------------------------------
		$no_owner_no_group = "$element_name.`OwnerID` IN (0,-1) AND $element_name.`GroupID` = 0";
		// -------------------------------------------
		// SQL indicating groupID is one of the team of the connected user
		// -------------------------------------------
		if($this->IsPrivacySensitive && $isConnected){
			$groupModuleInfo = moduleInfo('group');
			if(sizeof($groupModuleInfo->teamIDs)>0){
				$group_cond = ' OR ('.$element_name.'.`GroupID` IN ('.implode(",",$groupModuleInfo->teamIDs).'))';
			}
		}
		// -------------------------------------------
		// advanced security : fields OWNER AND GROUPS
		// -------------------------------------------
		if($this->isAdvancedSecurityEnabled()){
			// owners and groups are written like this in the database : c_{ID}_{R/W},c_{ID}_{R/W}
			$groupModuleInfo = moduleInfo('group');
			$groups_cond='';
			if(sizeof($groupModuleInfo->teamIDs)>0){
				$groups_cond = ' g_'.implode('_* g_',$groupModuleInfo->teamIDs).'_*';
			}
			// if advanced security is enabled, owners and groups have precedence over the OwnerID and GroupID 
			// it means a OWNERID=0 and GROUPID wont give automatic access to the element, Owners and Groups must also be empty (these variables are used lower)
			$owners_groups_isnull = 
				$element_name.'.`Owners` = "" AND '.
				$element_name.'.`Groups` = "" ';
			$advancedSecurity_isnull = ' AND '.$owners_groups_isnull;

			$advancedSecurity = " OR MATCH ($element_name.`Owners`,$element_name.`Groups`) AGAINST (' c_".$userID."_*".$groups_cond."' IN BOOLEAN MODE) OR ($owners_groups_isnull AND $no_owner_no_group)";

		}else{
			$advancedSecurity = '';
		}

		// -------------------------------------------
		// privacy applies and NO composite (composite is for having different security for different mediatypes)
		// -------------------------------------------
		if($this->IsPrivacySensitive && $isConnected && !$this->composite){
			$where_string.="/* Security - private */ AND ($element_name.`OwnerID`='".$userID."' OR ($no_owner_no_group $advancedSecurity_isnull)$group_cond $own_contact $template $advancedSecurity) ";
		}
		// only restrictive virtualKey applies
		if($this->composite && sizeof($this->virtualIDs)>0 && !$this->IsPrivacySensitive){
			$element_names = array();
			foreach($this->virtualIDs as $key){
				$element_names[]="'".$key."'";
			}
			$where_string.="/* Security - not private - virtual */ AND (".$element_name.".".$this->virtualKey.' IN ('.implode(',',$element_names).")$own_contact$template$advancedSecurity) ";
		}
		// -------------------------------------------
		// privacy applies and composite too (composite is for having different security for different mediatypes)
		// -------------------------------------------
		if($this->IsPrivacySensitive && $isConnected && $this->composite && sizeof($this->virtualIDs)>0){
			// privacy and virtualKey apply
			$privacy_cond = " AND ($element_name.`OwnerID`='".$userID."' OR ($no_owner_no_group $advancedSecurity_isnull) $group_cond) ";
			$element_names = array();
			foreach($this->virtualIDs as $key){
				$cond=$element_name.".".$this->virtualKey."='".$key."'";
				if($this->privacies[$key])
					$cond.=$privacy_cond;
				$element_names[]='('.$cond.')';

			}
			$where_string.="/* Security - private - virtual */ AND (".implode(" OR ",$element_names)." $own_contact $template) ";
		}
		return $where_string;
	}
	
	//------------------------------------------------------
	
	function getXMLSecurity()
	{
		// -------
		// RETURNS A XML STRING REPRESENTING THE SECURITY FOR THE USER CONNECTED FOR THE MODULE
		// -------

		//return $this->xmlSecurityStr;
		$xml = '';
		$xml.='<SECURITY>';
		// -------
		// ACTIONS
		// -------
		$xml.=$this->getActionsXMLSecurity();
		// -------
		// FIELDS
		// -------
		$fields = $this->getFields();
		foreach($fields as $field){
			$fieldname = $field->getNQLName();
			$fieldsStr.='<'.$fieldname.'>'.$this->getFieldSecurity($fieldname).'</'.$fieldname.'>';
		}
		$xml.='<FIELDS>'.$fieldsStr.'</FIELDS>';
		// -------
		// SERVICES
		// -------
		$xml.=$this->getServicesXMLSecurity();
		// -------
		// DEPENDENCY TYPES
		// -------
		$xml.=$this->getDependenciesXMLSecurity();
		$xml.='</SECURITY>';
		return $xml;
	}

	function getActionsXMLSecurity()
	{
		$xml = '';
		// -------
		// ACTIONS
		// -------
		$xml.='<ACTIONS>';
		$xml.=	'<SEARCH>'.$this->getActionSecurity('SEARCH').'</SEARCH>';
		$xml.=	'<CREATE>'.$this->getActionSecurity('CREATE').'</CREATE>';
		$xml.=	'<UPDATE>'.$this->getActionSecurity('UPDATE').'</UPDATE>';
		$xml.=	'<DELETE>'.$this->getActionSecurity('DELETE').'</DELETE>';
		$xml.='</ACTIONS>';

		return $xml;
	}

	function getServicesXMLSecurity()
	{
		$services = "";
		foreach($this->services as $serviceName=>$security)
		{
			$services.='<'.$serviceName.'>'.$this->getServiceSecurity($serviceName).'</'.$serviceName.'>';
		}
		$xml.='<SERVICES>'.$services.'</SERVICES>';
		return $xml;
	}

	function getDependenciesXMLSecurity()
	{
		$depStr = '';
		$deps = new DependencyTypeSet($this->getID());
		while($depType = $deps->next())
		{
			$depTypeName = $depType->getName();
			if($depTypeName)
			{
				$depStr.='<'.$depTypeName.'>';
				$depStr.=$this->getDepTypeSecurity($depTypeName);
				$depStr.='</'.$depTypeName.'>';
			}
		}

		$xml.='<DEPENDENCIES>'.$depStr.'</DEPENDENCIES>';
		return $xml;
	}

	function isXMLField($fieldName)
	{
		return isset($this->fieldsWithXML[$fieldName]);
	}

	function determineVirtualID($element)
	{
		if(!is_array($element) || $this->composite==false)
			return 0;
		else if(isset($element[$this->virtualKey]))
			return $element[$this->virtualKey];
		else
			return 0;
	}

	//------------------------------------------------------

	function getFieldsBySecurity($minSecurity,$element=false){
		/* O < R < W
		0 -> get all fields
		R -> all readable fields
		w -> all writable fields
		*/
		$virtualID = $this->determineVirtualID($element);
		// if we already asked for this minSecurity, we have kept it and return it directly
		if (isset($this->fieldsBySecurity[$virtualID][$minSecurity]))
				return $this->fieldsBySecurity[$virtualID][$minSecurity];
		$ret_array = array();
		if (!is_array($this->tableFields)){
			debug_log("What the heck is going on ??? tableFields array couldn't be determined.".$this->name." ".($this->loaded==false));
				return $ret_array;
		}
		if($minSecurity == '0'){
			foreach($this->tableFields as $field){
				array_push($ret_array,$field['REALNAME']);
			}
		}else if($minSecurity == 'R'){
			foreach($this->tableFields as $field){
				if($field['SECURITY'][$virtualID]=='R' ||$field['SECURITY'][$virtualID]=='W')
				array_push($ret_array,$field["REALNAME"]);
			}
		}else if($minSecurity == 'W'){
			foreach($this->tableFields as $field){
				if($field['SECURITY'][$virtualID]=='W')
				array_push($ret_array,$field['REALNAME']);
			}
		}
		$this->fieldsBySecurity[$virtualID][$minSecurity]=$ret_array;
		return $ret_array;
	}
	//------------------------------------------------------
	function getFieldSecurity($fieldName,$element=false){
		$fieldName = strtoupper($fieldName);
		$virtualID = $this->determineVirtualID($element);
		if(isset($this->tableFields[$fieldName]['SECURITY'][$virtualID]))
			return $this->tableFields[$fieldName]['SECURITY'][$virtualID];
		else
			return "0";
	}
	//------------------------------------------------------
	function getFieldName($fieldName){
		$fieldName=strtoupper($fieldName);
		if(isset($this->tableFields[$fieldName]['REALNAME']))
			return $this->tableFields[$fieldName]['REALNAME'];
		else
			return false;
	}
	function existField($fieldName){
		$fieldName=strtoupper($fieldName);
		if(isset($this->tableFields[$fieldName]['REALNAME']))
			return true;
		else
			return false;
	}
	//------------------------------------------------------
	function getFieldType($fieldName){
		$fieldName=strtoupper($fieldName);
		if(isset($this->tableFields[$fieldName]['TYPE']))
			return $this->tableFields[$fieldName]['TYPE'];
		else
			return false;
	}
	//------------------------------------------------------
	function getActionSecurity($actionName,$element=false){
		$virtualID = $this->determineVirtualID($element);
		if(isset($this->actions[$virtualID][$actionName]))
			return $this->actions[$virtualID][$actionName];
		else
			return false;
	}
	//------------------------------------------------------
	function getServiceSecurity($serviceName,$element=false){
		// variants for the services names
		if($serviceName=='dependency'){
			$serviceName='dependencies';
		}
		if($serviceName=='comments'){
			$serviceName='comment';
		}
		if($serviceName=='categories'){
			$serviceName='category';
		}
		if($serviceName=='descriptions'){
			$serviceName='description';
		}


		$virtualID = $this->determineVirtualID($element);
		if(isset($this->services[$serviceName]['SECURITY'][$virtualID]))
			return $this->services[$serviceName]['SECURITY'][$virtualID];
		else
			return "0";
	}
	//------------------------------------------------------
	function getDepTypeSecurity($depTypeName,$element=false){
		$virtualID = $this->determineVirtualID($element);
		if(isset($this->depTypes[$depTypeName]['SECURITY'][$virtualID]))
			return $this->depTypes[$depTypeName]['SECURITY'][$virtualID];
		else
			return "0";
	}
	function generateSearchText(&$new_values,$elementID=false,$node=false){
		switch($this->name){
				case "mail":
				$SearchTxt = '';
				$SearchTxt .= $new_values['From'].' '. $new_values['To'];
				$SearchTxt .= $new_values['Subject'].' '.$new_values['PlainText'];
				if ($new_values['Attachments'])
				{
					$attachments_array = explode(',',$new_values['Attachments']);
					$SearchTxt .= ' '.implode(' ',$attachments_array);
				}

				break;
			case "contact":
				$SearchTxt="";
				if($new_values['ContactType']=='PP')
				$SearchTxt.=$new_values['FirstName'].' '.$new_values['LastName'].' '.$new_values['Denomination'];
				else
				$SearchTxt.=$new_values['Denomination'].' '.$new_values['FirstName'].' '.$new_values['LastName'];
				$SearchTxt.=' '.$new_values['Address'].' '.$new_values['Postalcode'].' '.$new_values['City'].' '.$new_values['StateOrProvince'];
				$SearchTxt.=' '.$new_values['Phone1'].' '.$new_values['MobilePhone'].' '.$new_values['Fax'];
				$SearchTxt.=' '.$new_values['Email1'].' '.$new_values['Email2'];
				$SearchTxt.=' '.$new_values['Phone2'].' '.$new_values['Notes'];

				break;
			case "media":
				$SearchTxt="";
				$SearchTxt.=$new_values['Denomination'];
				$SearchTxt.=' '.$new_values['MediaType'];

				break;
			case "event":
				$SearchTxt="";
				$SearchTxt.=$new_values['Title'];
				if( isset($new_values['Comment']) ) {
					$SearchTxt.=' '.$new_values['Comment'];
				}
				break;
			case 'batch':
				$SearchTxt='';
				$fields = array('Denomination','Domain','Notes','Response','Command','Status','Callback');
				$first = true;
				foreach($fields as $fieldname){
					if(!$first){
						$SearchTxt.=' ';
					}
					if (isset($new_values[$fieldname])) {
						$SearchTxt .= $new_values[$fieldname];
					}
					$first = false;
				}
				break;
			case 'cron':
				$SearchTxt='';
				$fields = array('Denomination','Domain','Notes','Command','Status','Callback');
				$first = true;
				foreach($fields as $fieldname){
					if(!$first){
						$SearchTxt.=' ';
					}
					if (isset($new_values[$fieldname])) {
						$SearchTxt .= $new_values[$fieldname];
					}
					$first = false;
				}
				break;
			case 'cronlog':
				$SearchTxt='';
				$fields = array('Status','Response');
				$first = true;
				foreach($fields as $fieldname){
					if(!$first){
						$SearchTxt.=' ';
					}
					if (isset($new_values[$fieldname])) {
						$SearchTxt .= $new_values[$fieldname];
					}
					$first = false;
				}
				break;
		}

		if(!$this->searchtextProcessor)
		{
			// if already initialized, using it
			$this->searchtextProcessor = &new ModuleProcessingQueue();
			$this->searchtextProcessor->setModule($this);
			$this->searchtextProcessor->setType(NECTIL_SEARCHTEXT);
		}

		$process_data = &new ModuleProcessingData();
		$process_data->setElementID($elementID);
		$process_data->setNewValues($new_values);
		if(is_object($node)){
			$process_data->setNode($node);
		}
		$this->searchtextProcessor->setData($process_data);
		$this->searchtextProcessor->execute();

		// getting back the value returned by the series of processors
		$SearchTxt.=$this->searchtextProcessor->getResponse();

		// we reset node, to avoid errors when saving the processors in session (Fatal error: Exception thrown without a stack frame in Unknown on line 0 )
		// its impossible to save in session object with reference to other object (&)
		// a bit tricky !!!
		$process_data->node = false;

		//default searchtext with all text fields if nothing is configured
		if(!$SearchTxt){
			$fields = $this->getFields();
			foreach($fields as $field){
				$fieldName = $field->getName();
				$value = $new_values[$fieldName];
				if(!isset($this->forbiddenFields[$field->getName()]) && $field->getType()=='text' && $fieldName != 'Owners' && $fieldName!='Groups'){ // Owners and Groups should not be written in the searchtext
					$SearchTxt.=$value.' ';
				}
			}
		}

		//return strtolower(removeAccents(decode_from_XML($SearchTxt)));
		return Sushee_getSearchText($SearchTxt);
	}

	function getPreProcessors($command)
	{
		$process = &new ModuleProcessingQueue();
		$process->setModule($this);
		$process->setCommand($command);
		$process->setType(SUSHEE_PREPROCESSOR);

		return $process;
	}

	function getPostProcessors($command)
	{

		$process = &new ModuleProcessingQueue();
		$process->setModule($this);
		$process->setCommand($command);
		$process->setType(SUSHEE_POSTPROCESSOR);

		return $process;
	}

	function preProcess($command,$elementID,&$node,&$former_values,&$new_values,&$return_values,$exclude = false){

		// getting the queue of preprocessors for this command
		$process = $this->getPreProcessors($command);

		$process_data = &new ModuleProcessingData();
		$process_data->setModule($this);
		$process_data->setElementID($elementID);
		$process_data->setNode($node);
		$process_data->setFormerValues($former_values);
		$process_data->setNewValues($new_values);
		$process_data->setNoticeableValues($return_values);

		$process->setData($process_data);

		$process->execute();

		$process_data = $process->getData();

		$new_values = $process_data->getNewValues();
		$former_values = $process_data->getFormerValues();
		$return_values = $process_data->getNoticeableValues();

		return $process;
	}

	function postProcess($command,$elementID,&$node,&$former_values,&$new_values,&$return_values){
		// getting the queue of postprocessors for this command
		$process = $this->getPostProcessors($command);

		$process_data = &new ModuleProcessingData();
		$process_data->setModule($this);
		$process_data->setElementID($elementID);
		$process_data->setNode($node);
		$process_data->setFormerValues($former_values);
		$process_data->setNewValues($new_values);
		$process_data->setNoticeableValues($return_values);

		$process->setData($process_data);

		$res = $process->execute();

		$process_data = $process->getData();

		$new_values = $process_data->getNewValues();
		$former_values = $process_data->getFormerValues();
		$return_values = $process_data->getNoticeableValues();

		return $process;
	}

	function isAdvancedSecurityEnabled(){
		return $this->advancedSecurity;
	}

	function enableAdvancedSecurity($boolean=true){
		$this->advancedSecurity = $boolean;
	}

	function clearInSession(){
		Sushee_Session::clearVariable('public'.$this->getName());
		Sushee_Session::clearVariable('public'.$this->getID());
		Sushee_Session::clearVariable('private'.$this->getName());
		Sushee_Session::clearVariable('private'.$this->getID());
		Sushee_Session::clearVariable('modulesLightSecurity');
	}

	// returns the direct extension, extending this module really
	function getDirectExtensions(){
		$vector = new Vector();
		$sql = 'SELECT `ID` FROM `modules` WHERE `Extends` = "'.$this->getName().'";';
		$db_conn = db_connect();
		$rs = $db_conn->execute($sql);
		while($row = $rs->fetchRow()){
			$vector->add($row['ID'],moduleInfo($row['ID']));
		}
		return $vector;
	}

	// returns the extensions, descending all the inheriting objects
	function getExtensions(){
		$to_include = new Vector();
		$to_handle[] = $this;
		while($handled = array_pop($to_handle)){
			$extensions = $handled->getDirectExtensions();
			while($extension = $extensions->next()){
				$to_handle[] = $extension;
				$to_include->add($extension->getID(),moduleInfo($extension->getID())); // we recall moduleInfo, because we need a reference to the object, and not the object itself
			}
		}
		return $to_include;
	}

	// returns all parent modules, until the top
	function getParents(){
		$parents = new Vector();
		$module = $this;
		while($module = $module->getParentModule()){
			$parents->add($module->getID(),moduleInfo($module->getID())); // we recall moduleInfo, because we need a reference to the object, and not the object itself
		}
		return $parents;
	}

	function getNextID(){
		if($this->privateModule){
			$db_conn = db_connect();
			// private module -> get the unique ID
			$sql = 'UPDATE `'.$this->tableMasterName.'_auto_increment` SET `ID`=`ID`+1 WHERE 1 LIMIT 1;';
			$db_conn->Execute($sql);
			$sql = 'SELECT `ID` FROM `'.$this->tableMasterName.'_auto_increment` WHERE 1 LIMIT 1;';
			$row = $db_conn->getRow($sql);
			return $row['ID'];
		}
		return false;
	}
}