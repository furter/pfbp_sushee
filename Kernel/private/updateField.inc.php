<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/updateField.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nectil_element.class.php');
require_once(dirname(__FILE__).'/../common/db_manip.class.php');
require_once(dirname(__FILE__).'/../private/update.nql.php');
require_once(dirname(__FILE__).'/../common/translator.class.php');

class sushee_updateField extends NQLOperation{

	var $fieldID = false;

	function parse(){
		// taking the ID of the field to modify
		// update on fields are only possible with the ID, WHERE is too dangerous, because it could modify too much on the database
		$this->fieldID = $this->firstNode->valueOf('@ID');
		if(!$this->fieldID){
			// trying with a denomination and a module ?
			$denomination = $this->firstNode->valueOf('@denomination');
			$module = $this->firstNode->valueOf('@module');
			if($denomination && $module){
				$db_conn = db_connect();
				$field_row = $db_conn->getRow('SELECT `ID` FROM `fields` WHERE `Denomination`="'.encode_for_db($denomination).'" AND `Module`="'.encode_for_db($module).'"');
				if(!$field_row){
					$this->setError('Field `'.$denomination.'` for module `'.$module.'` doesnt exist');
					return false;
				}else{
					$this->fieldID = $field_row['ID'];
					$this->firstNode->setAttribute('ID',$this->fieldID);
				}
			}else{
				$this->setError('You didnt provide the ID of the field to update (or denomination + module)');
				return false;
			}
		}

		return true;
	}

	function operate(){
		// first getting back the former field informations in the database, in order to compare with the new one
		$fieldModuleElt = new FieldModuleElement($this->fieldID);
		$fieldModuleElt->loadFields();

		// field that is not modifiable: MODULE
		$newModule = $this->firstNode->valueOf('INFO/MODULE');
		if($newModule && $newModule!=$fieldModuleElt->getField('Module')){
			$this->setError('A field cannot be transferred from a module to another, please verify or remove the new MODULE node in your request');
			return false;
		}
		// module from which is the field
		$moduleInfo = moduleInfo($fieldModuleElt->getField('Module'));
		$fieldname = $fieldModuleElt->getField('FieldName');
		$table = $moduleInfo->getTable();
		$DBField = $table->getField($fieldname);
		if(!$DBField){
			$this->setError('Field `'.$fieldname.'` doesnt exist in table `'.$moduleInfo->getTablename().'`');
			return false;
		}

		// fields that needs real intervention on the database: Type, SQLType, FieldName, Denomination, DefaultValue
		$infoNode = $this->firstNode->getElement('INFO');
		if(!$infoNode){
			$this->firstNode->appendChild('<INFO/>');
			$infoNode = $this->firstNode->getElement('INFO');
		}
		$newType = $infoNode->valueOf('TYPE');
		$newSQLType = decode_from_xml($infoNode->valueOf('SQLTYPE'));
		$newFieldname = SQLNameCleaner::execute($infoNode->valueOf('FIELDNAME'));
		$newDenomination = SQLNameCleaner::execute($infoNode->valueOf('DENOMINATION'));
		$newDefaultValue = $infoNode->valueOf('DEFAULTVALUE');
		$newFulltextIndexValue = $infoNode->valueOf('FULLTEXTINDEX');
		$newSignedValue = $infoNode->valueOf('SIGNED');
		$newZerofillValue = $infoNode->valueOf('ZEROFILL');
		$newNullValue = $infoNode->valueOf('NULL');

		// Type is a virtual type : int, text, boolean, but a text might be represented by different SQL types. Text can be LongText, Varchar(255), etc.
		// SQL Type is the real type used in MySQL: Int(10), Tinyint(1), Varchar(255)
		$sqlchange = false;
		if($newSQLType && $newSQLType!=$DBField->getSQLType()){ // user provided a new SQL type to use

			$DBField->setSQLType($newSQLType);

			// new SQL type might imply a new Type
			$newType = $DBField->getType();

			// replacing the former Type by the new type in the xml
			$infoNode->modifyOrAppend('TYPE',$newType);
			$sqlchange = true;
		}else if($newType && $newType!=$DBField->getType()){

			$isValidType = $DBField->setType($newType);
			if(!$isValidType){
				$this->setError('Invalid type, should be one of these : '.implode(', ',$DBField->getValidTypes()) );
				return false;
			}
			// new type implies a new SQL Type
			$newSQLType = $DBField->getSQLType();

			// replacing the former SQL Type by the new sql type in the xml
			$infoNode->modifyOrAppend('SQLTYPE',$newSQLType);
			$sqlchange = true;
		}

		if($newFieldname && $newFieldname != $DBField->getName()){

			$DBField->setName($newFieldname);

			// fieldname is the real name of the field and denomination is its name in NQL(in uppercase)
			$newDenomination = $DBField->getNQLName();
			$infoNode->modifyOrAppend('DENOMINATION',$newDenomination);
			$sqlchange = true;
		}else if($newDenomination && strtoupper($newDenomination) != $DBField->getNQLName()){

			// if denomination has changed, we assume the user wanted to change the fieldname
			$DBField->setName($newDenomination);
			$newFieldname = $DBField->getName();

			// replacing in request by the official NQL name (user may have given it as lowercase)
			$newDenomination = $DBField->getNQLName();
			$denominationNode = $this->firstNode->getElement('INFO/DENOMINATION');
			$denominationNode->setValue($newDenomination);

			$infoNode->modifyOrAppend('FIELDNAME',$newFieldname);
			$sqlchange = true;

		}else if($newDenomination && $newDenomination===strtolower($DBField->getNQLName())){ // if newDenomination is lowercase in request, not allowing to insert it like this in database
			$denominationNode = $this->firstNode->getElement('INFO/DENOMINATION');
			$denominationNode->setValue(strtoupper($newDenomination));
		}

		if($newDefaultValue!==false && $newDefaultValue!=$DBField->getDefaultValue()){
			$DBField->setDefaultValue($newDefaultValue);
			$sqlchange = true;
		}

		if($newFulltextIndexValue!==false && $newFulltextIndexValue != $DBField->isFulltextIndexed()){
			if($DBField->isFulltextIndexable() && $newFulltextIndexValue){
				$DBField->enableFulltextIndex(1);
			}else{
				if($newFulltextIndexValue){ // means its not full indexable and we should warn the user
					$this->setError('Field with type `'.$DBField->getType().'` cannot use a fulltext index');
					return false;
				}else{
					$DBField->enableFulltextIndex(0);
					$infoNode->modifyOrAppend('FULLTEXTINDEX',0);
				}

			}
			$sqlchange = true;
		}

		if($newNullValue!==false && $newNullValue != $DBField->isNULLEnabled()){
			$DBField->enableNULL($newNullValue);
			$sqlchange = true;
		}

		if($newSignedValue!==false && $newSignedValue != $DBField->isSigned()){
			$DBField->enableSigned($newSignedValue);
			$sqlchange = true;
		}

		if($newZerofillValue!==false && $newZerofillValue != $DBField->isZeroFillEnabled()){
			// certain types of fields (text) cannot be zerofilled
			if($DBField->isZeroFillable() && $newZerofillValue){
				$DBField->enableZeroFill(1);
				// a zerofilled field cannot be signed
				if($DBField->isSigned()){
					$DBField->enableSigned(false);
					$infoNode->modifyOrAppend('SIGNED',0);
				}
			}else{
				if($newZerofillValue){// means its not zerofillable and we should warn the user
					$this->setError('Field with type `'.$DBField->getType().'` cannot be zerofilled');
					return false;
				}else{
					$DBField->enableZeroFill(0);
					$infoNode->modifyOrAppend('ZEROFILL',0);
				}
			}
			$sqlchange = true;
		}

		// completing with the decomposition of the sqltype in two parts
		$infoNode->modifyOrAppend('DBTYPE',$DBField->getDBType($DBField->getSQLType()));
		$infoNode->modifyOrAppend('OPTION',$DBField->getOption($DBField->getSQLType()));

		if($sqlchange){
			// applying changes
			$res = $DBField->update();
			if(!$res){
				$this->setError($DBField->getError());
				return false;
			}
		}

		$translateme = $this->firstNode->getAttribute('translate');
		if($translateme == 'true' || $translateme == 'forced')
		{
			/**
	 		 * @author	françois
			 */

			// composing a fieldname in fluid english in order to translate
			$readablefieldname = $DBField->getReadableName();

			// render the field name as word list
			$listfieldname = str_replace(' ' , ',' , strtolower($readablefieldname));

			// if not null it means that we don't need the classic languages but only those given
			$translateTo   = $this->firstNode->getAttribute('translateTo');

			// if not null the translation will be put this particular language
			$translateTarget = $this->firstNode->getAttribute('translateTarget');

			if (!$translateTo)
			{
				$translateTo = 'fre,eng';
			}

			if ($translateme == 'true')
			{	
				// get the current situation to remove existing translation
				$shell = new Sushee_Shell(false);
				$shell->addCommand('
			       	<GET>
					   <FIELD ID="'.$this->fieldID.'"></FIELD>
					   <RETURN>
					      <DESCRIPTIONS languageID="all">
					         <TITLE/>
					      </DESCRIPTIONS>
					   </RETURN>
					</GET>
			    ');
				$shell->execute();
				$descriptions = $shell->getElement('/RESPONSE/RESULTS/FIELD/DESCRIPTIONS');
				
				$lng_array = explode(',',$translateTo);
				$lng_new = array();
				foreach($lng_array as $lng)
				{
					// only add languages with no content
					if (!$descriptions->exists('DESCRIPTION[@languageID = "'.$lng.'"]/TITLE/text()'))
					{
						$lng_new[] = $lng;
					}
				}
				$translateTo = implode(',',$lng_new);
			}

			// check because maybe no translation needed
			if ($translateTo)
			{
				// get the translation
				$shell = new Sushee_Shell(false);
				$shell->addCommand('
			        <GET name="'.$lg.'">
						<TEXT name="'.$listfieldname.'" languageID="'.$translateTo.'"/>
					</GET>
			    ');
				$shell->execute();

				// create associative array
				foreach ($shell->getElements('/RESPONSE/RESULTS/TEXT') as $text)
				{
					$name = strtolower($text->valueOf('@name'));
					$ln = $text->valueOf('@language');
					$trads[$ln][$name] = $text->valueOf('.');
				}

				if ($translateTarget)
				{
					$lng_array[] = $translateTarget;
				}
				else
				{
					$lng_array = explode(',' , $translateTo);
				}

				$useless_words = array('id','1','xml');
				$name_array = explode(',' , $listfieldname);
				foreach($lng_array as $lng)
				{
					$translation = '';
					foreach($name_array as $nam)
					{
						$trd = $trads[$lng][$nam];

						// only words that are not useless OR if only one word
						if (!in_array(strtolower($nam),$useless_words) || count($name_array) === 1 )
						{
							if (!$trd) {
								$trd = $nam;
							}
							if ($nam == '_') {
								// _ = separator of module
								// trim to remove previous space
								$translation = trim($translation);
							}
							$translation .= $trd . ' ';
						}
					}
					$translation = trim($translation);
					if($translation)
					{
						$desc.= '<DESCRIPTION languageID="'.$lng.'"><TITLE>'.encode_to_xml($translation).'</TITLE></DESCRIPTION>';
					}
				}

				// saving the descriptions
				$this->firstNode->appendChild('<DESCRIPTIONS>'.$desc.'</DESCRIPTIONS>');
			}
		}

		// letting the usual update in NQL do its job
		$update = new UpdateElement($this->getName(),$this->operationNode);
		$update->execute();

		$this->setMsg($update->getMsg());

		// forcing the session to reload the definition of the module to be synchronised with the changes made
		$moduleInfo->clearInSession();

		return true;
	}
}