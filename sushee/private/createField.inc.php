<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createField.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');
require_once(dirname(__FILE__).'/../private/create.nql.php');
require_once(dirname(__FILE__).'/../common/translator.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');

class sushee_createField extends NQLOperation{

	var $fieldDenomination = false;
	var $type = false;
	var $moduleID = false;

	function getModule(){
		return moduleInfo($this->moduleID);
	}

	function parse(){
		// checking we  have the fields necessary to create a new field (Module,Denomination or FieldName,Type)
		$infoNode = $this->firstNode->getElement('INFO');
		$missing_info = false;
		if($infoNode){
			if(!$infoNode->getElement('MODULE')){
				$missing_info = true;
			}else{
				$moduleName = $infoNode->valueOf('MODULE');
				$moduleInfo = moduleInfo($moduleName);
				if(!$moduleInfo->loaded){
					$this->setError('Invalid Module name');
					return false;
				}
				$this->moduleID = $moduleInfo->getID();
			}
			if(!$infoNode->valueOf('DENOMINATION') && !$infoNode->valueOf('FIELDNAME')){
				$missing_info = true;
			}else{
				$fieldName = $infoNode->valueOf('FIELDNAME');
				$denomination = $infoNode->valueOf('DENOMINATION');
				if($fieldName)
					$this->fieldDenomination = $fieldName;
				else
					$this->fieldDenomination = $denomination;
			}
			if(!$infoNode->valueOf('TYPE')){
				$missing_info = true;
			}else{
				$this->type = $infoNode->valueOf('TYPE');
			}
		}else{
			$missing_info = true;
		}

		if($missing_info){
			$this->setError('Not enough information to create the field : Module, Denomination (or FieldName) and Type are mandatory');
			return false;
		}

		if($moduleInfo->isExtension()){
			// when the module is an extension, we have to prefix the name of the field with the name of the module
			$moduleName = $moduleInfo->getName();
			$len = strlen($moduleName);
			$fieldStart = substr($this->fieldDenomination,0,$len+1);
			if(($fieldStart)!=$moduleName.'_'){
				$this->fieldDenomination = $moduleName.'_'.$this->fieldDenomination;
			}
		}

		$this->fieldDenomination = SQLNameCleaner::execute($this->fieldDenomination);
		return true;
	}

	function operate(){
		// checking the field does not exist yet
		$moduleInfo = $this->getModule();
		$infoNode = $this->firstNode->getElement('INFO');
		if( $moduleInfo->getField($this->fieldDenomination) ){
			$this->setError('Field `'.$this->fieldDenomination.'` already exists on table `'.$moduleInfo->tableName.'`.');
			return false;
		}
		// creating the field on the database
		$table = $moduleInfo->getTable();
		$field = new TableField($this->fieldDenomination);
		$field->setTable($table);

		// checking it does not exist yet
		if($field->exists()){
			$this->setError('Field `'.$field->getName().'` already exists in table `'.$table->getName().'`');
			return false;
		}

		$isValidType = $field->setType($this->type);
		if(!$isValidType){
			$this->setError('Invalid type, should be one of these : '.implode(', ',$field->getValidTypes()) );
			return false;
		}

		// field can accept null value ?
		if($infoNode->exists('NULL')){
			$field->enableNull($infoNode->valueOf('NULL'));
		}

		// field is signed ?
		if($infoNode->exists('SIGNED')){
			$field->enableSigned($infoNode->valueOf('SIGNED'));
		}

		// field must be padded with zero ?
		if($infoNode->exists('ZEROFILL')){
			$zerofill = $infoNode->valueOf('ZEROFILL');
			if($zerofill && $field->isZeroFillable()){
				// zerofill can only be applied on unsigned fields
				$field->enableSigned(false);
				// changing the value of signed in XML
				$infoNode->modifyOrAppend('SIGNED',0);
				$field->enableZerofill(true);
			}else{
				if($zerofill){
					$this->setError('Field with type `'.$field->getType().'` cannot be zerofilled');
					return false;
				}
				$field->enableZerofill(false);
				// no zerofill, changin in XML
				$infoNode->modifyOrAppend('ZEROFILL',0);
			}
		}

		// field must have fulltext index ?
		if($infoNode->exists('FULLTEXTINDEX')){
			$fulltextindex = $infoNode->valueOf('FULLTEXTINDEX');
			// fulltext index can only be used on textfields
			if($fulltextindex && $field->isFulltextIndexable()){
				$field->enableFulltextIndex($infoNode->valueOf('FULLTEXTINDEX'));
			}else{
				if($fulltextindex){ // type is incompatible with fulltext index
					$this->setError('Field with type `'.$field->getType().'` cannot use a fulltext index');
					return false;
				}
				$infoNode->modifyOrAppend('FULLTEXTINDEX',0);
			}
		}

		if($infoNode->exists('DEFAULTVALUE')){
			$defaultValue = $infoNode->valueOf('DEFAULTVALUE');
			if($defaultValue){
				$field->setDefaultValue($defaultValue);
			}
		}

		$res = $table->addField($field);
		if(!$res){
			$this->setError($table->getError());
			return false;
		}

		if($this->firstNode->valueOf('INFO/SQLTYPE')){
			// if user asks for a specific SQL type (instead of the default), changing it
			$field->changeSQLType(decode_from_xml($this->firstNode->valueOf('INFO/SQLTYPE')));
		}else{
			$infoNode->modifyOrAppend('SQLTYPE',encode_to_xml($field->getSQLType()));
		}

		// completing the NQL with the correct field information
		$infoNode->modifyOrAppend('DEFAULTVALUE',encode_to_xml($field->getDefaultValue()));
		$infoNode->modifyOrAppend('DENOMINATION',encode_to_xml($field->getNQLName()));
		$infoNode->modifyOrAppend('FIELDNAME',encode_to_xml($field->getName()));

		// completing with the decomposition of the sqltype in two parts
		$infoNode->modifyOrAppend('DBTYPE',$field->getDBType($field->getSQLType()));
		$infoNode->modifyOrAppend('OPTION',$field->getOption($field->getSQLType()));

		// letting the usual creation in NQL do its job
		$create = new CreateElement($this->getName(),$this->operationNode);
		$res = $create->execute();
		if(!$res){
			$this->setError($create->getError());
			$field->delete();
			return false;
		}

		$this->setMsg($create->getMsg());

		// adding automatic translation (Google API) for the term in the descriptions of the elements
		if($create->getElementID() && $this->firstNode->getAttribute('translate')=='true'){
			// letting the UPDATE translate --> it will fill the descriptions
			$nql = new MiniNQL('<UPDATE><FIELD ID="'.$create->getElementID().'" translate="true"/></UPDATE>');
			$nql->execute();
		}

		// add the field in the module dependency
		$field->createModuleToFieldDependency();

		// forcing the session to reload the definition of the module to be synchronised with the changes made
		$moduleInfo->clearInSession();

		return true;
	}
}