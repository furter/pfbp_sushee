<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/categories.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/nectil_element.class.php");
require_once(dirname(__FILE__)."/../common/console.class.php");
require_once(dirname(__FILE__).'/../common/log.class.php');

class CategoryLink extends SusheeObject{

	var $element = false;
	var $category = false;
	var $categoryID = false;
	var $moduleTargetID = false;
	var $elementID = false;

	function CategoryLink($element=false,$category=false){
		if($element)
			$this->setElement($element);
		if($category)
			$this->setCategory($category);
	}

	function setElement($element){
		// $this->logFunction('setElement');
		if(is_object($element)){
			$this->element = $element;
			$this->elementID = $this->element->getID();
			$moduleInfo = $element->getModule();
			if($moduleInfo){
				$this->moduleTargetID = $moduleInfo->getID();
			}
		}else{
			$this->log($element);
		}
	}

	function setCategory($category){
		// $this->logFunction('setCategory');
		if(is_object($category)){
			$this->category = $category;
			$this->categoryID = $this->category->getID();
		}
	}

	function exists(){
		// $this->logFunction('exists');
		$moduleTargetID = $this->moduleTargetID;
		$elementID = $this->elementID;
		$categoryID = $this->categoryID;
		if(!$moduleTargetID || !$elementID || !$categoryID){
			return false;
		}
		$db_conn = db_connect();
		$sql = "SELECT * FROM `categorylinks` WHERE `ModuleTargetID`='$moduleTargetID' AND `TargetID`='$elementID' AND `CategoryID`='$categoryID';";
		sql_log($sql);
		$row = $db_conn->getRow($sql);
		if($row){
			return true;
		}else{
			return false;
		}
	}

	function getModule(){
		return moduleInfo($this->moduleTargetID);
	}

	function create(){
		// $this->logFunction('create');
		if(!$this->exists()){
			$db_conn = db_connect();

			// ------------------------------------------------------------------------
			// COLLECTING DATAS
			// ------------------------------------------------------------------------
			$moduleTargetID = $this->moduleTargetID;
			$elementID = $this->elementID;
			$categoryID = $this->categoryID;
			if(!$moduleTargetID || !$elementID || !$categoryID){
				return false;
			}

			// ------------------------------------------------------------------------
			// DATABASE INSERTION
			// ------------------------------------------------------------------------
			$sql = "INSERT INTO `categorylinks`(`ModuleTargetID`,`TargetID`,`CategoryID`) VALUES('$moduleTargetID','$elementID','$categoryID');";
			sql_log($sql);
			$db_conn->Execute($sql);

			// ------------------------------------------------------------------------
			// ACTION LOGGING
			// ------------------------------------------------------------------------
			$moduleInfo = $this->getModule();
			$action_log_file = new UserActionLogFile();
			$action_object = new UserActionObject($moduleInfo->getName(),$elementID);
			$action_target = new UserActionTarget(UA_OP_APPEND,UA_SRV_CATEG,$categoryID);
			$action_log = new UserActionLog($this->getOperation(), $action_object , $action_target );
			$action_log_file->log( $action_log );

			return true;
		}else{
			return false;
		}

	}

	function getOperation(){
		return 'UPDATE';
	}

	function delete(){
		// $this->logFunction('delete');
		if($this->exists()){
			$db_conn = db_connect();

			// ------------------------------------------------------------------------
			// COLLECTING DATAS
			// ------------------------------------------------------------------------
			$moduleTargetID = $this->moduleTargetID;
			$elementID = $this->elementID;
			$categoryID = $this->categoryID;
			if(!$moduleTargetID || !$elementID || !$categoryID){
				return false;
			}

			// ------------------------------------------------------------------------
			// DATABASE DELETION
			// ------------------------------------------------------------------------
			$sql = "DELETE FROM `categorylinks` WHERE `ModuleTargetID`='$moduleTargetID' AND `TargetID`='$elementID' AND `CategoryID`='$categoryID';";
			sql_log($sql);
			$db_conn->Execute($sql);

			// ------------------------------------------------------------------------
			// ACTION LOGGING
			// ------------------------------------------------------------------------
			$moduleInfo = $this->getModule();
			$action_log_file = new UserActionLogFile();
			$action_object = new UserActionObject($moduleInfo->getName(),$elementID);
			$action_target = new UserActionTarget(UA_OP_REMOVE,UA_SRV_CATEG,$categoryID);
			$action_log = new UserActionLog($this->getOperation(), $action_object , $action_target );
			$action_log_file->log( $action_log );


			return true;
		}else{
			return false;
		}
	}

	function getCategory(){
		return $this->category;
	}

	function getElement(){
		return $this->element;
	}
}

class CategoryLinksSet extends Vector{
	function CategoryLinksSet(){
		parent::Vector();
		$this->console = new XMLConsole();
	}

	function add($categ){
		parent::add($categ->getID(),$categ);
	}

	function setElement($element){
		$this->element = $element;
	}

	function getElement(){
		return $this->element;
	}

	function replace(){
		// $this->logFunction('replace');
		// --------------------------------
		// FIRST REMOVING FORMER CATEGORIES
		// --------------------------------
		$formerCategories = new CategoryLinksSet();
		$element = $this->getElement();
		$formerCategories->setElement($element);
		$db_conn = db_connect();
		$moduleInfo = $element->getModule();
		if($moduleInfo){
			$moduleTargetID = $moduleInfo->getID();
		}else{
			return false;
		}
		// taking former categories, but not the one which will be appended just after
		$formercateg_sql = 'SELECT `CategoryID` FROM `categorylinks` WHERE `ModuleTargetID` = \''.$moduleTargetID.'\' AND `TargetID` = \''.$element->getID().'\' AND `CategoryID` NOT IN ('.$this->implode().')';
		sql_log($formercateg_sql);
		$formerCateg_rs = $db_conn->execute($formercateg_sql);
		if($formerCateg_rs){
			$formerCateg_bool = false;
			while($formerCateg_row = $formerCateg_rs->FetchRow()){
				$formerCateg = &new Category($formerCateg_row['CategoryID']);
				// adding the former category in the set (this set is to be deleted)
				$formerCategories->add($formerCateg);
				$formerCateg_bool = true;
			}
			if($formerCateg_bool){ // there are former categories
				$formerCategories->delete(); // we delete the set
				$this->console->addMessage($formerCategories->getXML());
			}
		}else{
			return false;
		}
		// --------------------------------
		// NOW APPENDING NEW CATEGORIES
		// --------------------------------
		$this->append();
	}

	function append(){
		// $this->logFunction('append');
		$this->console->addMessage('<CATEGORIES operation="append">');
		$link = new CategoryLink();
		$link->setElement($this->element);
		$this->reset();
		while($categ = $this->next()){
			if($categ->exists()){
				$link->setCategory($categ);
				$res = $link->create();
				if($res){
					$this->console->addMessage('<CATEGORY ID="'.$categ->getID().'"/>');
				}else{
					$this->console->addMessage('<CATEGORY ID="'.$categ->getID().'">This category was already assigned</CATEGORY>');
				}
			}else{
				$this->addCategoryError($categ);
			}

		}
		$this->console->addMessage('</CATEGORIES>');
	}

	function addCategoryError($categ){
		if($categ->getID()){
			$attrib = ' ID="'.$categ->getID().'"';
		}else if($categ->getDenomination()){
			$attrib = ' name="'.$categ->getDenomination().'"';
		}else if($categ->getPath()){
			$attrib = ' path="'.$categ->getPath().'"';
		}
		$this->console->addMessage('<CATEGORY'.$attrib.'>This category doesn\'t exist</CATEGORY>');
	}

	function delete(){
		// $this->logFunction('delete');
		$this->console->addMessage('<CATEGORIES operation="remove">');
		$link = new CategoryLink();
		$link->setElement($this->element);
		$this->reset();
		while($categ = $this->next()){
			if($categ->exists()){
				$link->setCategory($categ);
				$res = $link->delete();
				if($res){
					$this->console->addMessage('<CATEGORY ID="'.$categ->getID().'"/>');
				}else{
					$this->console->addMessage('<CATEGORY ID="'.$categ->getID().'">This category wasn\'t assigned</CATEGORY>');
				}
			}else{
				$this->addCategoryError($categ);
			}

		}
		$this->console->addMessage('</CATEGORIES>');
	}

	function getXML(){
		return $this->console->getXML();
	}
}

class CategoryCollection extends SusheeObject{

	var $fatherID = false;
	var $loaded = false;
	var $vect = false;

	function setFatherID($fatherID){
		$this->loaded = false;
		$this->fatherID = $fatherID;
	}

	function next(){
		$this->load();
		return $this->vect->next();
	}


	function load(){
		if(!$this->loaded){
			$this->loaded = true;
			$this->vect = new Vector();
			$db_conn = db_connect();
			$sql = 'SELECT `ID` FROM `categories` WHERE `FatherID` = "'.encodequote($this->fatherID).'";';
			sql_log($sql);
			$rs = $db_conn->Execute($sql);
			if($rs){
				while($row = $rs->fetchRow()){
					$this->vect->add($row['ID'],new Category($row['ID']));
				}
			}
		}
	}
}

class Category extends SusheeObject{

	var $ID = false;
	var $path = false;
	var $denomination = false;
	var $loaded = false;
	var $exists = false;

	function Category($ID=false){
		$this->ID = $ID;
	}

	function setPath($path){
		$this->path = $path;
		$this->loaded = false;
		$this->load();
	}

	function setDenomination($denomination){
		$this->denomination = $denomination;
		$this->loaded = false;
		$this->load();
	}

	function getDenomination(){
		return $this->denomination;
	}

	function getPath(){
		return $this->path;
	}

	function load(){
		if(!$this->loaded){
			$this->loaded = true;
			$sql = false;
			if($this->ID){
				$sql = 'SELECT `ID`,`Path`,`Denomination` FROM `categories` WHERE `ID`=\''.$this->ID.'\'';
			}else if($this->path){
				$sql = 'SELECT `ID`,`Path`,`Denomination` FROM `categories` WHERE `Path` LIKE "'.encodequote($this->path).'"';
			}else if($this->denomination){
				$sql = 'SELECT `ID`,`Path`,`Denomination` FROM `categories` WHERE `Denomination` LIKE "'.encodequote($this->denomination).'"';
			}
			if($sql){
				sql_log($sql);
				$db_conn = db_connect();
				$row = $db_conn->GetRow($sql);
				if($row){
					$this->path = $row['Path'];
					$this->ID = $row['ID'];
					$this->denomination = $row['Denomination'];
					$this->exists = true;
				}else{
					$this->exists = false;
				}

			}else{
				$this->exists = false;
			}
		}
		return $this->exists;
	}

	function getID(){
		return $this->ID;
	}

	function exists(){
		return $this->load();
	}
}




class CategoriesFactory extends SusheeObject{

	var $xmlNode;
	var $elementID;
	var $ModuleID;
	var $elementValues; // for virtual security
	var $console;

	function CategoriesFactory($ModuleID,$xmlNode,$elementID,$elementValues=array()){
		$this->console = new XMLConsole();

		$this->ModuleID = $ModuleID;
		$this->moduleInfo = moduleInfo($ModuleID);

		$this->xmlNode = $xmlNode;
		$this->elementID = $elementID;
		$this->elementValues = $elementValues;
	}

	function setElementID($elementID){
		$this->elementID = $elementID;
	}

	function getXML(){
		return $this->console->getXML();
	}

	function execute(){
		// $this->logFunction('execute');
		$categoriesNodes = $this->xmlNode->getElements('./CATEGORIES');
		if(sizeof($categoriesNodes)>0){
			foreach($categoriesNodes as $categoriesNode){
				$operation = $categoriesNode->valueOf('@operation');

				if(!$operation){
					$operation='replace';
				}

				$categ_links = new CategoryLinksSet();
				$elt = new ModuleElement($this->ModuleID,$this->elementID);
				//die($elt->getID());
				$categ_links->setElement($elt);

				$elementNodes = $categoriesNode->getElements('CATEGORY');
				$i = 1;
				//$this->log($this->xmlNode->toString());
				foreach($elementNodes as $node){
					$categoryID=$node->valueOf("@ID");
					if($categoryID){
						$categ = new Category($categoryID);
						$categ_links->add($categ);
					}else{
						$categ_name=$node->valueOf("@name");
						if($categ_name){
							$categ = new Category();
							$categ->setDenomination($categ_name);
							$categ_links->add($categ);
						}else{
							$categoryPath = $node->valueOf("@path");
							if($categoryPath){
								$categ = new Category();
								$categ->setPath($categoryPath);
								$categ_links->add($categ);
							}else{
								$categoryFatherID = $node->valueOf("@fatherID");
								if($categoryFatherID){
									$children = new CategoryCollection();
									$children->setFatherID($categoryFatherID);
									while($categ = $children->next()){
										$categ_links->add($categ);
									}
								}
							}
						}
					}
					$i++;
				}
				switch($operation){
					case 'remove':
						$categ_links->delete();
						break;
					case 'append':
						$categ_links->append();
						break;
					default:
						$categ_links->replace();
				}
				$this->console->addMessage($categ_links->getXML());
			}
		}
	}
}