<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/db_manip.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/file.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
require_once(dirname(__FILE__)."/../common/registers.class.php");

class Database extends SusheeObject{

	var $name;

	function Database($name){
		$this->name = $name;
	}

	function getName(){
		return $this->name;
	}

	/*function getTable($name){
		return new DatabaseTable($this->getName(),$name);
	}*/

	function getTables(){
		$db_conn = $this->getConnection();
		$rs = $db_conn->execute('SHOW TABLES');
		$tables = array();
		if($rs){
			while($row = $rs->fetchRow()){
				$tables[] = new DatabaseTable($this,$row[0]);
			}
		}
		return $tables;
	}

	function getConnection(){
		$db_conn = specific_db_connect($this->getName());
		return $db_conn;
	}

	function execute($sql){
		$db_conn = $this->getConnection();
		$res = $db_conn->execute($sql);
		return $res;
	}

	function create(){
		$db_conn = db_connect();
		$create_db_sql = 'CREATE DATABASE `'.$this->getName().'` ;';
		$db_conn->Execute($create_db_sql);
	}

	function delete(){
		$db_conn = db_connect();
		$drop_db_sql = 'DROP DATABASE `'.$this->getName().'` ;';
		$db_conn->Execute($drop_db_sql);
	}

	function export($sqlFile = false){
		if(!$sqlFile){
			$sqlFile = new TempFile();
			$sqlFile->setExtension('sql');
		}
		$tables = $this->getTables();
		foreach($tables as $table){
			$table->export($sqlFile);
			$sqlFile->append("\n");
		}
		return $sqlFile;
	}
}



class DatabaseTable extends SusheeObject{

	var $name;
	var $db = false;

	function DatabaseTable($db,$tablename){
		$this->name = $tablename;
		$this->db = $db;
		// if no database given, taking the sushee database (in the beginning this class was intended to manage residents database)
		if(!$this->db){
			$this->db = new Database($GLOBALS["db_name"]);
		}
	}

	function getName(){
		return $this->name;
	}

	function getDatabaseName(){
		return $this->db->getName();
	}

	function export($sqlFile = false){
		if(!$sqlFile){
			$sqlFile = new TempFile();
			$sqlFile->setExtension('sql');
		}
		$sqlFile->append($this->getCreateTableSQL()."\r\n");
		$db_conn = $this->db->getConnection();
		if($db_conn){
			$pseudo_rs = $db_conn->execute('SELECT * FROM `'.$this->getName().'` WHERE -1');
			$rs= $db_conn->execute('SELECT * FROM `'.$this->getName().'`');
			if($rs){
				while($row = $rs->fetchRow()){
					$str=$db_conn->GetInsertSQL($pseudo_rs, $row);
					$sqlFile->append($str.";\r\n");
				}
			}
		}
		return $sqlFile;
	}

	function getCreateTableSQL(){
		if(is_object($this->db)){
			$db_conn = $this->db->getConnection();
			if($db_conn){
				$struct_sql = 'SHOW CREATE TABLE `'.$this->getName().'`;';
				$struct_row = $db_conn->getRow($struct_sql);
				if(!$struct_row){
					$this->setError($db_conn->ErrorMsg());
					return false;
				}
				return $struct_row['Create Table'].';';
			}
		}
		return false;
	}
	/*
	function getDataSQL(){
		if(is_object($this->db)){
			$db_conn = $this->db->getConnection();
			if($db_conn){
				$pseudo_rs = $db_conn->execute('SELECT * FROM `'.$this->getName().'` WHERE -1');
				$str = '';
				$rs= $db_conn->execute('SELECT * FROM `'.$this->getName().'`');
				if($rs){
					while($row = $rs->fetchRow()){
						$str.=$db_conn->GetInsertSQL($pseudo_rs, $row);
					}
				}
				return $str;
			}
		}
		return false;
	}*/

	function getSQLName(){
		$name = '`'.$this->getName().'`';
		if($this->getDatabaseName()){
			$name = '`'.$this->getDatabaseName().'`.'.$name;
		}
		return $name;
	}

	function copy($toTable){
		$sql = 'INSERT INTO '.$toTable->getSQLName().' SELECT * FROM '.$this->getSQLName();
		$db_conn = db_connect();
		$res = $db_conn->execute($sql);
		if(!$res){
			$this->setError($db_conn->ErrorMsg());
			return false;
		}
		return true;
	}

	function duplicate($name){
		$db_conn = db_connect();
		$create_sql  = $this->getCreateTableSQL();
		$duplicate_tablename = SQLNameCleaner::execute($name);
		$duplicate_create_sql = str_replace('`'.$this->getName().'`','`'.$duplicate_tablename.'`',$create_sql);
		$res = $db_conn->execute($duplicate_create_sql);
		if(!$res){
			$this->setError($db_conn->ErrorMsg());
			return false;
		}

		$duplicate_table = new DatabaseTable(false,$duplicate_tablename);
		$res = $this->copy($duplicate_table);
		if(!$res){
			return false;
		}

		return $duplicate_table;
	}
}

class TableField extends SusheeObject
{
	var $name;
	var $type = null;
	var $default = false;
	var $table;
	var $desc = false;
	var $error = false;
	var $registrationID = false; // ID in the fields database
	var $moduleInfo = false;
	var $acceptsNULL = null;
	var $zeroFill = null;
	var $signed = null;
	var $fulltextIndex = null;
	var $exists = null;
	var $validTypes = array('int','boolean','text','date','datetime','number','list','file','float','systemList','textarea','styled');

	function TableField($name=false)
	{
		$this->setName($name);
	}

	function getModule()
	{
		// usually this->moduleInfo is assigned by a parent class
		if($this->moduleInfo)
		{
			return $this->moduleInfo;
		}
		
		// if its not assigned by a parent class, retrieving it
		$table = $this->getTable();
		if($table)
		{
			$name = $table->getName();

			// check if private module table
			$private_ext = '_'.OfficityUser::getID();
			if (substr($name,-strlen($private_ext)) == $private_ext)
			{
				$name = substr($name,0,-strlen($private_ext));
			}

			$sql = 'SELECT `Denomination` FROM `modules` WHERE `TableName`="'.$name.'"';
			$db_conn = db_connect();
			$row = $db_conn->getRow($sql);
			if($row)
			{
				$this->setModule(moduleInfo($row['Denomination']));
				return $this->moduleInfo;
			}
		}
		return false;
	}

	function setModule($moduleInfo)
	{
		$this->moduleInfo = $moduleInfo;
	}

	function getMYSQLDescription()
	{
		if(is_array($this->desc))
		{
			return $this->desc;
		}
		else
		{
			$table = $this->getTable();
			if($table)
			{
				$sql = '/* TableField.getMYSQLDescription */DESCRIBE `'.$table->getName().'` `'.$this->getName().'`';
				$db_conn = db_connect();
				sql_log($sql);
				$row = $db_conn->getRow($sql);
				$this->desc = $row;
				return $row;
			}
			else
			{
				return false;
			}
		}
	}

	function exists()
	{
		if($this->exists===null)
		{
			$field_desc = $this->getMYSQLDescription();
			if($field_desc)
			{
				$this->exists = true;
			}
			else
			{
				$this->exists = false;
			}
		}
		return $this->exists;
	}

	function setExistence($boolean=true)
	{
		$this->exists = $boolean;
	}

	function setTable($table)
	{
		$this->table = $table;
	}

	function getTable()
	{
		return $this->table;
	}

	function setType($type)
	{
		if( !in_array($type,$this->getValidTypes()) )
		{
			return false;
		}

		if($type!=$this->type && $this->type!=null)
		{
			$this->setSQLType($this->getSQLType($type));
		}

		$this->type = $type;
		return true;
	}

	function getXML()
	{
		$xml = '';
		$xml.='<FIELD>';
		$xml.=	'<INFO>';
		$xml.=		'<DENOMINATION>'.encode_to_xml($this->getNQLName()).'</DENOMINATION>';
		$xml.=		'<FIELDNAME>'.encode_to_xml($this->getName()).'</FIELDNAME>';
		$xml.=		'<TYPE>'.encode_to_xml($this->getType()).'</TYPE>';
		$xml.=		'<SQLTYPE>'.encode_to_xml($this->getSQLType()).'</SQLTYPE>';
		$xml.=		'<DEFAULTVALUE>'.encode_to_xml($this->getDefaultValue()).'</DEFAULTVALUE>';
		$xml.=	'</INFO>';
		$xml.='</FIELD>';
		return $xml;
	}

	function getTypicalDefaultValue()
	{
		if ($this->getName() === 'ID')
		{
			// auto increment -> no default value
			return false;
		}

		switch($this->getType())
		{
			case 'systemList':
			case 'list':
			case 'text':
			case 'file':
				return false;
				break;
			case 'number':
			case 'int':
				return 0;
				break;
			case 'boolean':
				return 0;
				break;
			case 'float':
				return 0;
				break;
			case 'datetime':
				return '0000-00-00 00:00:00';
				break;
			case 'date':
				return '0000-00-00';
				break;
			default:
				return false;
		}
	}

	function getDefaultValue()
	{
		if($this->default === false)
		{
			if($this->exists())
			{
				$desc = $this->getMYSQLDescription();
				if(!$desc['Default'])
				{
					$desc['Default'] = false;
				}

				$this->default = $desc['Default'];
				return $this->default;
			}
			else
			{
				return $this->getTypicalDefaultValue();
			}
		}
		else
		{
			return $this->default;
		}
	}

	function setName($name){
		if($this->name){
			$this->formername = $this->name; // we keep the old name, because we might need it when saving the field definition later (we have to know how the field was named before in order to change it)
		}else{
			$this->formername = $name;
		}
		$this->name = $name;
	}

	function getName()
	{
		return $this->name;
	}

	function getFormerName()
	{
		return $this->formername;
	}

	function getNQLName()
	{
		return strtoupper($this->name);
	}

	function getxSusheeName()
	{
		return $this->getNQLName();
	}

	function isParentField()
	{
		// if module is an extension, its fields are prefixed with the module name.
		// If not, the field doesnt belong to the module itself but to a parent module
		$moduleInfo = $this->getModule();
		$fieldname = $this->getName();

		if(!$moduleInfo->isExtension())
		{
			return false;
		}

		// begins with the module name : field is part of the extension
		if( substr($fieldname,0,strlen($moduleInfo->getName())) == $moduleInfo->getName())
		{
			return false;
		}

		return true;
	}

	function getReadableName()
	{
		// looking where are the uppercase letters to decompose the fieldname in words
		$readablefieldname = '';
		$fieldname = $this->getName();

		// if a module is an extension, fieldname is prefixed with the module name, which is not interesting to translate
		$moduleInfo = $this->getModule();
		if($moduleInfo->isExtension())
		{
			// begins with the module name
			if( substr($fieldname,0,strlen($moduleInfo->getName())) == $moduleInfo->getName())
			{
				$fieldname = substr($fieldname,strlen($moduleInfo->getName()));
				// cleaning special characters (SQL fieldname should only contain underscores as special characters)
				$fieldname = str_replace(array('_'),'',$fieldname);
			}
		}

		$letter = $fieldname[0];
		$readablefieldname .= $letter;

		// preceding letter is uppercase ?
		$prec_is_upper = strtoupper($letter) == $letter;
		for($i = 1; $i<strlen($fieldname);$i++)
		{
			$letter = $fieldname[$i];
			$is_upper = strtoupper($letter) == $letter;
			$next_is_lower = false;
			$next_letter = $fieldname[$i+1];
			if($next_letter)
			{
				$next_is_lower = strtolower($next_letter)==$next_letter;
			}

			// if changing case : ...aB... or Ba -> considering its a new word
			// if number : date1 -> new word 

			if((!$prec_is_upper && $is_upper) || (is_numeric($letter) && !is_numeric($prec_letter)) || ($is_upper && $next_is_lower))
			{
				$readablefieldname .= ' ' . $letter;
			}
			else if($letter == '_')
			{
				$readablefieldname .= ' _ ';
			}
			else
			{
				$readablefieldname .= $letter;
			}

			$prec_is_upper = $is_upper;
			$prec_letter = $letter;
		}
		return $readablefieldname;
	}

	function setError($error)
	{
		$this->error = $error;
	}

	function getError()
	{
		return $this->error;
	}

	function changeType($new_type)
	{
		$table = $this->getTable();
		if($table)
		{
			$sql = 'ALTER TABLE `'.$table->getName().'` CHANGE `'.$this->getName().'` `'.$this->getName().'` '.$this->getSQLType($new_type);
			$db_conn = db_connect();
			sql_log($sql);
			$res = $db_conn->Execute($sql);
			if($res)
			{
				$this->type = $new_type;
				$this->desc = false; // type changed, forcing to reload the description of the field
				return true;
			}
			$this->setError($db_conn->ErrorMsg());
		}
		return false;
	}

	function changeSQLType($new_type)
	{
		$table = $this->getTable();
		if($table)
		{
			$sql = 'ALTER TABLE `'.$table->getName().'` CHANGE `'.$this->getName().'` `'.$this->getName().'` '.$this->getSQLDefinition($new_type);
			$db_conn = db_connect();
			sql_log($sql);
			$res = $db_conn->Execute($sql);
			if($res)
			{
				$this->type = false;
				$this->desc = false; // type changed, forcing to reload the description of the field
				return true;
			}
			$this->setError($db_conn->ErrorMsg());
		}
		return false;
	}

	function clean($str)
	{
		return SQLNameCleaner::execute($str);
	}

	function changeName($new_name)
	{
		$table = $this->getTable();
		if($table)
		{
			$new_name = $this->clean($new_name);
			$sql = 'ALTER TABLE `'.$table->getName().'` CHANGE `'.$this->getName().'` `'.$new_name.'` '.$this->getSQLDefinition();
			$db_conn = db_connect();
			sql_log($sql);
			$res = $db_conn->Execute($sql);
			if($res)
			{
				$this->name = $new_name;
				return true;
			}
			$this->setError($db_conn->ErrorMsg());
		}
		return false;
	}

	function update()
	{
		$table = $this->getTable();
		if($table)
		{
			// first managing index before changing type
			$index = $this->getFulltextIndex(); // existing index ?
			$shouldIndex = $this->isFulltextIndexed();
			if(!$shouldIndex && $index)
			{
				// removing fulltext index
				$indexname = $index['Key_name'];
				$sql = 'ALTER TABLE `'.$table->getName().'` DROP INDEX '.$indexname;
				$db_conn = db_connect();
				sql_log($sql);
				$db_conn->execute($sql);
			}

			if($shouldIndex && !$index)
			{
				// adding fulltext index
				$sql = 'ALTER TABLE `'.$table->getName().'` ADD FULLTEXT (
				`'.$this->getName().'`
				)';
				$db_conn = db_connect();
				sql_log($sql);
				$db_conn->Execute($sql);
			}

			$sql = 'ALTER TABLE `'.$table->getName().'` CHANGE `'.$this->getFormerName().'` `'.$this->getName().'` '.$this->getSQLDefinition();
			$db_conn = db_connect();
			sql_log($sql);
			$res = $db_conn->Execute($sql);
			if($res)
			{
				$this->formername = $this->getName();
				return true;
			}
			$this->setError($db_conn->ErrorMsg());
		}
		return false;
	}

	function changeDefaultValue($default_value)
	{
		$table = $this->getTable();
		if($table)
		{
			$sql = 'ALTER TABLE `'.$table->getName().'` ALTER COLUMN `'.$this->getName().'` SET DEFAULT "'.encode_quote($default_value).'"';
			$db_conn = db_connect();
			sql_log($sql);
			$res = $db_conn->Execute($sql);
			if($res)
			{
				$this->default = $default_value;
				return true;
			}
			$this->setError($db_conn->ErrorMsg());
		}
		return false;
	}

	function setDefaultValue($default)
	{
		$type = $this->getType();

		// empty string is not allowed as a default value for dates and datetimes
		if($default === '' && ( $type === 'date' || $type == 'datetime' || $type == 'number'))
		{
			// resetting to the typical default value
			$default = $this->getTypicalDefaultValue();
		}

		$this->default = $default;
	}

	function getSQLDefinition($sqltype=false)
	{
		$type = $this->getType();
		$default = $this->getDefaultValue();

		if(!$sqltype)
		{
			$sqltype = $this->getSQLType($type);
		}

		$sql = $sqltype;

		if($this->isNumeric())
		{
			if(!$this->isSigned())
				$sql.=' unsigned';

			if($this->isZerofillEnabled())
				$sql.=' zerofill';
		}

		if($default !== false && $type !='text')
		{
			$sql.=' DEFAULT \''.$default.'\'';
		}
		
		if($this->isNULLEnabled())
		{
			$sql.= ' NULL ';
		}
		else
		{
			$sql.= ' NOT NULL ';
		}

		if($this->getName()=='ID')
		{
			$sql.= ' AUTO_INCREMENT ';
		}

		return $sql;
	}

	// existing sushee types
	function getValidTypes(){
		return $this->validTypes;
	}

	// module.class still uses adotype, this class has to be compatible
	function setAdoType($ado_type){
		switch($ado_type){
			case "N"://Numbers
			case "I"://Integers
			case "R"://autoincrement
				$this->setType('int');
				break;
			case "L"://Logical field (boolean or bit-field
				$this->setType('boolean');
				break;
			case "C"://characters
			case "X"://big characters
			case "B"://blob
				$this->setType('text');
				break;
			case "D"://Date
				$this->setType('date');
				break;
			case "T"://timestamp
				 $this->setType('datetime');
				break;
			default:
				 $this->setType('text');
		}
	}

	// getting the SQL type corresponding to the sushee predefined types
	function getSQLType($type=false){
		if($this->exists() && !$type){
			if(!$this->sqltype){
				// returning the field type given by MySQL
				$desc = $this->getMYSQLDescription();
				$this->sqltype = $desc['Type'];
			}
			return $this->sqltype;
		}else{
			// if field doesnot yet exist, returning the official field sql dependending on the type asked
			if(!$type)
				$type = $this->getType();
			switch($type){
				case 'text':
				case 'file':
					if($this->getDefaultValue()){
						$sqltype = 'varchar(255)';
					}else{
						$sqltype = 'text';
					}
					break;
				case 'list':
				case 'systemList':
					$sqltype = 'varchar(255)';
					break;
				case 'number':
				case 'int':
					$sqltype = 'int(4)';
					break;
				case 'boolean':
					$sqltype = 'tinyint(1)';
					break;
				case 'float':
					$sqltype = 'float';
					break;
				case 'datetime':
					$sqltype = 'datetime';
					break;
				case 'date':
					$sqltype = 'date';
					break;
				default:
					$sqltype = 'text';
			}
			return $sqltype;
		}

	}

	// guessing the sushee type from the SQL type in the database
	function getType(){
		if(!$this->type && $this->exists()){
			$sqltype = $this->getSQLType();
			if(strpos($sqltype,'text')!==false || strpos($sqltype,'char')!==false){
				return 'text';
			}
			if(strpos($sqltype,'enum')!==false){
				return 'list';
			}
			if(strpos($sqltype,'decimal')!==false ){
				return 'decimal';
			}
			if(strpos($sqltype,'tinyint(1)')!==false)
				return 'boolean';
			if($sqltype=='float')
				return 'float';
			if($sqltype=='datetime')
				return 'datetime';
			if($sqltype=='date')
				return 'date';
			if(strpos($sqltype,'int')!==false){
				return 'int';
			}

		}
		return $this->type;
	}

	function setSQLType($sqltype){
		$this->sqltype = $sqltype;
	}

	function delete(){
		$table = $this->getTable();
		if($table){
			$this->exists=null; // allowing to check later if the field is really deleted
			$sql = 'ALTER TABLE `'.$table->getName().'` DROP COLUMN `'.$this->getName().'`';
			$db_conn = db_connect();
			sql_log($sql);
			$res = $db_conn->Execute($sql);
			// IF EXIST statement on columns don't exists in mysql
			// return true to avoid errors if field don't exist anymore
			// like in module heritage
			return true;

			// if($res){
			// 	return true;
			// }
			// $this->setError($db_conn->ErrorMsg());
		}
		return false;
	}

	function isMandatory(){
		$mandatory_fields = array('ID','Activity','IsLocked','CreatorID','OwnerID','GroupID','ModifierID','CreationDate','ModificationDate','SearchText');
		return (in_array($this->getName(),$mandatory_fields));
	}

	function isSystem(){
		return $this->isMandatory();
	}

	function createFieldElement(){
		if($this->getRegistrationID()===false){
			require_once(dirname(__FILE__)."/../common/nectil_element.class.php");

			$db_conn = db_connect();
			$moduleInfo = $this->getModule();
			// creating the field configuration in database
			$values = array();
			$values['Denomination'] = $this->getNQLName();
			$values['FieldName'] = $this->getName();
			$values['Module'] = $moduleInfo->getName();
			$values['Type'] = $this->getType();
			$values['Null'] = $this->isNullEnabled();
			$values['Signed'] = $this->isSigned();
			$values['ZeroFill'] = $this->isZeroFillEnabled();

			// special cases
			if($moduleInfo->getName()=='contact'){
				switch($this->getName()){
					case 'CountryID':
						$values['Type'] = 'systemList';
						$values['ListName'] = 'Countries';
						break;
					case 'LanguageID':
						$values['Type'] = 'systemList';
						$values['ListName'] = 'Languages';
						break;
					case 'Preview':
						$values['Type'] = 'file';
						break;
				}
			}

			$values['SQLType'] = $this->getSQLType();
			$values['DBType'] = $this->getDBType($values['SQLType']);
			$values['Option'] = $this->getOption($values['SQLType']);
			$values['FulltextIndex'] = $this->isFulltextIndexed();
			$values['DefaultValue'] = $this->getDefaultValue();
			$values['Displayable'] = 0;
			$values['Searchable'] = ($this->getName() == 'SearchText') ? 1 : 0;
			$fieldConfig = new FieldModuleElement($values);
			$fieldConfig->create();

			$this->registrationID = $fieldConfig->getID();
		}
	}

	function getDBType($sqltype){
		$explosion = explode('(',$sqltype);
		return trim(str_replace(array('unsigned','zerofill'),'',$explosion[0]));
	}

	function getOption($sqltype){
		$explosion = explode('(',$sqltype);
		if($explosion[1]){
			$closing_parenthesis = strpos($explosion[1],')');
			return trim(str_replace(array('unsigned','zerofill'),'',substr($explosion[1],0,$closing_parenthesis)));
		}
		return false;
	}

	function createModuleToFieldDependency(){
		require_once(dirname(__FILE__).'/../common/dependencies.class.php');
		require_once(dirname(__FILE__).'/../common/dependency.class.php');

		$moduleInfo = $this->getModule();
		$type = new dependencyType('fields');
		$dep = new Dependency($type,$moduleInfo->getID(),$this->getRegistrationID());

		//debug_log('adding field ' .$this->getName() . ' in module '.  $moduleInfo->getName() );
		//debug_log('create dep: ' . $moduleInfo->getID() . ' - ' . $this->getRegistrationID());

		$dep->create();
	}

	function register(){ // save the definition of the field in the fields table
		$this->createFieldElement();
		$this->createModuleToFieldDependency();
	}

	function isRegistered(){ // definition exists in fields table
		if($this->getRegistrationID()!==false){
			return true;
		}else{
			return false;
		}
	}

	function getRegistrationID(){
		$db_conn = db_connect();
		$moduleInfo = $this->getModule();
		$sql = 'SELECT `ID` FROM `fields` WHERE `Denomination`="'.$this->getName().'" AND `Module`="'.$moduleInfo->getName().'"';
		sql_log($sql);
		$row = $db_conn->getRow($sql);
		if(!$row){
			return false;
		}
		$this->registrationID = $row['ID'];
		return $this->registrationID;
	}

	function encodeForSQL($node){
		if(is_string($node)){
			$value = $node;
		}else if(is_object($node)){
			$value = $node->valueOf();
			if($node->hasChildren()){
				$value = $node->toString('./*');
			}
		}

		$name = $this->getName();
		if(($name=='Owners' || $name=='Groups') && is_object($node)){
			$value = '';
			// for these fields, internal format is c_{ID}_{R/W},...
			if($name=='Owners'){
				$prefix = 'c_';
			}else{
				$prefix = 'g_';
			}
			$particle = '_';
			$separator = ',';
			$children = $node->getChildren();
			$first = true;
			foreach($children as $child){
				$ID = $child->getAttribute('ID');
				$security = $child->getAttribute('security');
				if($ID && $security){
					if(!$first)
						$value.=$separator;
					$value.=$prefix.$ID.$particle.$security;
					$first = false;
				}
			}
		}

		$type = $this->getType();
		switch($type){
			case 'int':
				if($value=='visitor'){
					$value = OfficityUser::getID();
				}
				return decode_from_XML($value);
				break;
			case 'date':
			case 'datetime':
				require_once(dirname(__FILE__)."/../common/date.class.php");
				$converter = new DateTimeKeywordConverter($value);
				$value = $converter->execute();
				return $value;
			case 'text':
				if($this->isXML()){
					return $value;
				}
				return decode_from_XML($value);
			default:
				return decode_from_XML($value);
		}
	}

	function isDate(){
		$type = $this->getType();
		if($type=='date' || $type=='datetime'){
			return true;
		}
		return false;
	}

	function isNumeric(){
		$type = $this->getType();
		if($type=='int' || $type=='float' || $type=='boolean'){
			return true;
		}
		return false;
	}

	function isXML(){
		$name = $this->getName();
		if(strtoupper(substr($name,-3))=='XML'){
			return true;
		}
		$moduleInfo = $this->getModule();
		return $moduleInfo->isXMLField($name);
	}

	function encodeForNQL($value,$attributes='',$profile=false){
		require_once(dirname(__FILE__)."/../common/fields.class.php");

		$n = $this->getNQLName();

		// owners and groups have a	 special internal format but should be returned as a XML
		if($n == 'GROUPS' || $n == 'OWNERS'){



			if($value){
				$explosion = explode(',',$value);
				$value = '';
				if($n == 'GROUPS'){
					$elt_type = 'GROUP';

					$group_fields_collection = new FieldsCollection();
					$group_fields_collection->add(new DBField('Denomination'));
				}else{
					$elt_type = 'CONTACT';

					$contact_fields_collection = new FieldsCollection();
					$contact_fields_collection->add(new DBField('FirstName'));
					$contact_fields_collection->add(new DBField('LastName'));
					$contact_fields_collection->add(new DBField('Email1'));
					$contact_fields_collection->add(new DBField('Denomination'));
				}
				foreach($explosion as $part){

					list($prefix,$ID,$security) = explode('_',$part);

					// keeping a register of owners details, to avoid reloading them if we load many elements with the same owners (which is often the case)
					$ownersRegister = new Sushee_Register('OwnersRegister');

					// checking first if the element is in the register
					$registerID = $prefix.$ID;
					$elt_infos = $ownersRegister->getElement($registerID);

					// if not in the register, we load the detail
					if(!$elt_infos){
						if($elt_type == 'CONTACT'){

							$contact = new Contact($ID);
							$contact->loadFields($contact_fields_collection);
							$elt_infos = 'firstname="'.encode_to_xml($contact->getField('FirstName')).'" lastname="'.encode_to_xml($contact->getField('LastName')).'" email1="'.encode_to_xml($contact->getField('Email1')).'" denomination="'.encode_to_xml($contact->getField('Denomination')).'"';

						}else{

							$group = new Group($ID);
							$group->loadFields($group_fields_collection);
							$elt_infos = 'denomination="'.encode_to_xml($group->getField('Denomination')).'"';

						}

						$ownersRegister->add($registerID,$elt_infos);
					}

					// building the final output with the contact detail and the security this contact has on the element
					$value.='<'.$elt_type.' ID="'.$ID.'" '.$elt_infos.' security="'.$security.'"/>';
				}
			}
			return '<'.$n.$attributes.'>'.$value.'</'.$n.'>';
		}

		if($profile && $profile->isSecurityIncluded()){
			$attributes.=' security="'.encode_to_xml($this->getModule()->getFieldSecurity($n)).'"';
		}

		// other nodes
		if($this->isXML()){
			// removing header if present
			if(substr($value,0,5)=='<?xml'){
				$pos_end_header = strpos($value,'?>');
				if($pos_end_header!==false){
					$value = substr($value,$pos_end_header+2);
				}
			}
			$str='<'.$n.$attributes.'>'.$value.'</'.$n.'>';
		}else{
			$str='<'.$n.$attributes.'>'.encode_to_XML($value).'</'.$n.'>';
		}
		return $str;
	}

	function encodeDateForNQL($value,$profile=false){
		// name of the field
		$n = $this->getNQLName();

		// profiling
		if(is_object($profile)){
			$include_weekdays = $profile->isWeekdayIncluded();
			$include_timestamp = $profile->isTimestampIncluded();
			$include_ymd = $profile->isYearMonthDayIncluded();
		}else{
			$include_weekdays = false;
		}

		// composing the special attributes
		$attributes = '';

		if($profile && $profile->isSecurityIncluded()){
			$attributes.=' security="'.encode_to_xml($this->getModule()->getFieldSecurity($n)).'"';
		}

		if($include_weekdays && $value!='0000-00-00 00:00:00' && $value!='9999-12-31 23:59:59' && $value!='0000-00-00' && $value!='9999-12-31'){
			require_once(dirname(__FILE__)."/../common/date.class.php");
			$date = new Date($value);
			$attributes.=' weekday="'.$date->getWeekday().'"';
		}
		if($include_timestamp){
			require_once(dirname(__FILE__)."/../common/date.class.php");
			$date = new Date($value);
			$attributes.=' timestamp="'.$date->getTime().'"';
		}
		if($include_ymd){
			require_once(dirname(__FILE__)."/../common/date.class.php");
			$date = new Date($value);
			$attributes.=' year="'.$date->getYear().'" month="'.$date->getMonth().'" day="'.$date->getDay().'"';
		}

		// composing the complete xml node
		$str='<'.$n.$attributes.'>'.encode_to_XML($value).'</'.$n.'>';
		return $str;
	}

	function disableNULL(){
		$this->acceptsNULL = false; 
	}

	function enableNULL($boolean = true){
		$this->acceptsNULL = $boolean; 
	}

	function isNULLEnabled(){
		if($this->exists() && $this->acceptsNULL===null){
			$desc = $this->getMYSQLDescription();
			if($desc['Null']==='YES'){
				$this->acceptsNULL = true;
			}else{
				$this->acceptsNULL = false;
			}
		}
		return $this->acceptsNULL;
	}

	function enableZeroFill($boolean=true){
		$this->zeroFill = $boolean;
	}

	function isZeroFillEnabled(){
		if($this->exists() && $this->zeroFill===null){
			$desc = $this->getMYSQLDescription();
			if(strpos($desc['Type'],'zerofill')!==false){
				$this->zeroFill = true;
			}else{
				$this->zeroFill = false;
			}
		}
		return $this->zeroFill;
	}

	function isZeroFillable(){
		return $this->isNumeric();
	}

	function enableSigned($boolean = true){
		$this->signed = $boolean;
	}

	function isSigned(){
		if($this->exists() && $this->signed===null){
			$desc = $this->getMYSQLDescription();
			if(strpos($desc['Type'],'unsigned')!==false || (strpos($desc['Type'],'int')===false && strpos($desc['Type'],'float')===false)){
				$this->signed = false;
			}else{
				$this->signed = true;
			}
		}
		return $this->signed;
	}

	// FULLTEXT INDEX is a special index for texts that allows fast search on text fields
	function enableFulltextIndex($boolean = true){
		$this->fulltextIndex = (boolean)$boolean;
	}

	function getFulltextIndex(){
		$table = $this->getTable();
		$sql = 'SHOW INDEX FROM `'.$table->getName().'`';
		$db_conn = db_connect();
		sql_log($sql);
		$rs = $db_conn->execute($sql);
		while($row = $rs->fetchRow()){
			if($row['Column_name']==$this->getName() && $row['Index_type']=='FULLTEXT'){
				return $row;
			}
		}
		return false;
	}

	function isFulltextIndexed(){
		if($this->exists() && $this->fulltextIndex===null){
			if($this->getFulltextIndex()){
				$this->fulltextIndex = true;
			}else{
				$this->fulltextIndex = false;
			}
		}
		return $this->fulltextIndex;
	}

	function isFulltextIndexable(){
		$sqltype = $this->getSQLType();
		if(strpos($sqltype,'text')!==false){
			return true;
		}
		return false;
	}

}

class SQLNameCleaner extends SusheeObject{

	static function execute($str){
		$forbidden_chars = array(';',',','\'','"','+','*','/','=','$','&','!','(',')','<','>',' ','.');
		return str_replace($forbidden_chars,'',$str);
	}
}

class ModuleDatabaseTable extends SusheeObject{

	var $name;
	var $mandatory_fields = array();
	var $error = false;
	var $moduleID = false;

	function clean($str){
		return SQLNameCleaner::execute($str);
	}

	function setModule($moduleInfo){
		$this->moduleID = $moduleInfo->getID();
	}

	function getModule(){
		return moduleInfo($this->moduleID);
	}

	function ModuleDatabaseTable($name){

		$this->name = $name;

		// Assigning types by hand to avoid TableField suboptimal handling (it goes and look in the database)
		$field = new TableField('Activity');
		$field->setSQLType('tinyint(1)');
		$field->setType('boolean');
		$field->setDefaultValue(1);
		$this->mandatory_fields[] = $field;

		$field = new TableField('IsLocked');
		$field->setSQLType('tinyint(1)');
		$field->setType('boolean');
		$this->mandatory_fields[] = $field;

		$field = new TableField('CreatorID');
		$field->setSQLType('int(4)');
		$field->setType('number');
		$this->mandatory_fields[] = $field;

		$field = new TableField('OwnerID');
		$field->setSQLType('int(4)');
		$field->setType('number');
		$this->mandatory_fields[] = $field;

		$field = new TableField('GroupID');
		$field->setSQLType('int(4)');
		$field->setType('number');
		$this->mandatory_fields[] = $field;

		$field = new TableField('ModifierID');
		$field->setSQLType('int(4)');
		$field->setType('number');
		$this->mandatory_fields[] = $field;

		$field = new TableField('CreationDate');
		$field->setSQLType('datetime');
		$field->setType('datetime');
		$this->mandatory_fields[] = $field;

		$field = new TableField('ModificationDate');
		$field->setSQLType('datetime');
		$field->setType('datetime');
		$this->mandatory_fields[] = $field;

		$field = new TableField('SearchText');
		$field->setSQLType('text');
		$field->setType('text');
		$this->mandatory_fields[] = $field;

	}

	function getField($fieldname){
		$field = new TableField($fieldname);
		$field->setTable($this);
		if($this->moduleID){
			$field->setModule($this->getModule());
		}
		if(!$field->exists())
			return false;
		return $field;
	}

	function getFields(){
		$sql = 'DESCRIBE `'.$this->getName().'`';
		sql_log($sql);
		$db_conn = db_connect();
		$rs = $db_conn->Execute($sql);
		if($rs){
			while($row = $rs->FetchRow()){
				$fieldname = $row['Field'];
				$field = new TableField($fieldname);
				$field->setTable($this);
				$field->setExistence(true);
				$field->setType($row['Type']);
				if($this->moduleID){
					$field->setModule($this->getModule());
				}
				$fields[] = $field;
			}
			return $fields;
		}else{
			$this->setError($db_conn->ErrorMsg());
			return false;
		}
	}

	function create(){
		$sql = 
			'CREATE TABLE `'.$this->getName().'` (
				`ID` int(4) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
			) ENGINE = MYISAM ;';
		sql_log($sql);
		$db_conn = db_connect();
		$db_conn->Execute($sql);
		foreach($this->mandatory_fields as $field){
			$this->addField($field);
		}

	}

	function delete(){
		$sql = 
			'DROP TABLE `'.$this->getName().'`;';
		sql_log($sql);
		$db_conn = db_connect();
		$db_conn->Execute($sql);
	}

	function getName(){
		return $this->name;
	}

	function addField($field){
		$name = $field->getName();

		$sql = 'ALTER TABLE `'.$this->getName().'` ADD COLUMN `'.$this->clean($name).'` '.$field->getSQLDefinition();
		$db_conn = db_connect();
		sql_log($sql);
		$res = $db_conn->Execute($sql);
		if(!$res){
			$this->setError($db_conn->ErrorMsg());
			return false;
		}

		// SearchText index
		if($name=='SearchText' || $field->isFulltextIndexed()){
			 $sql = 'ALTER TABLE `'.$this->getName().'` ADD FULLTEXT (
			`'.$this->clean($name).'`
			)';
			sql_log($sql);
			$db_conn->Execute($sql);
		}

		$field->setTable($this);
		return true;
	}

	function removeField($field){
		if($field->getName()=='ID')
			return false;
		foreach($this->mandatory_fields as $mandatory_field){
			if($mandatory_field->getName()==$field->getName()){
				return false;
			}
		}
		$sql = 'ALTER TABLE `'.$this->getName().'` DROP COLUMN `'.$this->clean($field->getName()).'` ';
		sql_log($sql);
		$db_conn = db_connect();
		$db_conn->Execute($sql);
	}

	function changeName($new_name){
		$new_name = $this->clean($new_name);
		$sql = 'ALTER TABLE `'.$this->getName().'` RENAME TO `'.$new_name.'`;';
		$db_conn = db_connect();
		sql_log($sql);
		$res = $db_conn->Execute($sql);
		if($res){
			$this->name = $new_name;
			return true;
		}
		$this->setError($db_conn->ErrorMsg());
		return false;
	}

	function setError($error){
		$this->error = $error;
	}

	function getError(){
		return $this->error;
	}

	function indexFulltext($fields){
		// FULLTEXT INDEX ON MULTIPLE FIELDs
		if(is_object($fields)){
			$fields = array($fields);
		}
		$db_conn = db_connect();
		$first = true;
		$fieldnames = '';
		foreach($fields as $field){
			if(!$first)$fieldnames.=',';
			$fieldnames.='`'.$this->clean($field->getName()).'`';
			$first = false;
		}
		$sql = 
		'ALTER TABLE `'.$this->getName().'` ADD FULLTEXT (
		'.$fieldnames.'
		);';
		sql_log($sql);
		return $db_conn->Execute($sql);
	}

	function exists(){
		$sql = '/* ModuleDatabaseTable.exists */DESCRIBE `'.$this->getName().'`;';
		$db_conn = db_connect();
		$row = $db_conn->getRow($sql);

		if(!$row){
			return false;
		}
		return true;
	}

	function enableAdvancedSecurity($boolean=true){
		if($boolean){
			// enabling advanced security
			$ownersField = new TableField('Owners');
			$groupsField = new TableField('Groups');

			$ownersField->setType('text');
			$groupsField->setType('text');

			$ownersField->disableNULL();
			$groupsField->disableNULL();

			$this->addField($ownersField);
			$this->addField($groupsField);

			// indexing the owners and groups fields to allow fast security checks
			$this->indexFulltext(array($ownersField,$groupsField));
		}else{
			// removing advanced security
			$ownersField = new TableField('Owners');
			$groupsField = new TableField('Groups');
			$this->removeField($ownersField);
			$this->removeField($groupsField);
		}
	}
}

class DependenciesTable extends DatabaseTable{

	function DependenciesTable($name){
		$this->name = $name;
	}

	function create(){
		$createTable_sql = 'CREATE TABLE `'.$this->getName().'` (
		 `OriginID` bigint(20) NOT NULL DEFAULT \'0\',
		 `TargetID` bigint(20) NOT NULL DEFAULT \'0\',
		 `DependencyTypeID` bigint(20) NOT NULL DEFAULT \'0\',
		 `Ordering` smallint(5) unsigned NOT NULL DEFAULT \'1\',
		 `TargetOrdering` smallint(5) unsigned NOT NULL DEFAULT \'0\',
		 `DepInfo` text NOT NULL,
		 `Comment` text NOT NULL,
		 `SearchText` text NOT NULL,
		 PRIMARY KEY (`OriginID`,`TargetID`,`DependencyTypeID`),
		 KEY `DependencyTypeID` (`DependencyTypeID`)
		)';
		$db_conn = db_connect();
		sql_log($createTable_sql);
		$db_conn->execute($createTable_sql);
	}
}