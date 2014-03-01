<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createMediatype.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nql.class.php');
require_once(dirname(__FILE__)."/../common/descriptions.inc.php");
require_once(dirname(__FILE__)."/../common/dependencies.inc.php");
require_once(dirname(__FILE__)."/../common/categories.inc.php");

class createMediatype extends NQLOperation{
	function parse(){
		return true;
	}
	
	function operate(){
		// Determining what to change or what to insert for the mediatype
		$this->log("createMediatype function");
		$db_conn = db_connect();

		$sql = "SELECT * FROM `descriptionsconfig` WHERE ID=-1;";
		$pseudo_desc_rs = $db_conn->Execute($sql);

		// first deleting the mediatypes that are not present anymore
		$sql = 'SELECT ID,MediaKind FROM `mediatypes`;';
		$rs = $db_conn->Execute($sql);
		while($row = $rs->FetchRow() )
		{
			$xpath_expr = $current_path."/MEDIATYPE[UNIQUENAME='".$row['MediaKind']."']";
			
			$mediatype_in_xml = $this->operationNode->getElement($xpath_expr);
			// checking it's not a mediatype that is forbidden to this user
			$mediaModuleInfo = moduleInfo('media');
			if($mediaModuleInfo->composite && sizeof($mediaModuleInfo->virtualIDs)>0 && !in_array($row['MediaKind'],$mediaModuleInfo->virtualIDs))
			{
				$mediatype_in_xml=true; // do not delete it because the user doesn't even know it exists
				$this->log('do not delete it because the user doesnt even know it exists');
			}
			if($mediatype_in_xml==false){
				$this->log('delete type '.$row['MediaKind']);
				$config_sql = 'SELECT `DescriptionConfigID` FROM `mediatypesconfig` WHERE `MediaTypeID`='.$row['ID'].';';
				
				$desc_rs = $db_conn->Execute($config_sql);
				while($config = $desc_rs->FetchRow()){
					$del_sql = 'DELETE FROM `descriptionsconfig` WHERE ID='.$config['DescriptionConfigID'].';';
					$db_conn->Execute($del_sql);
				}
				$del_sql = 'DELETE FROM `mediatypesconfig` WHERE `MediaTypeID`='.$row['ID'].';';
				$db_conn->Execute($del_sql);
				$del_sql = 'DELETE FROM `mediatypes` WHERE ID='.$row['ID'].';';
				$db_conn->Execute($del_sql);
				$this->deleteKeyring($row['MediaKind']);
			}else
				$this->log('not deleting '.$row['MediaKind']);
		}
		$this->log("handling mediatypes at ".$current_path."/MEDIATYPE");

		$mediaTypesNodes = $this->operationNode->getElements("/MEDIATYPE[not(@update='false')]");
		$this->log("got array of mediatypes ");
		
		foreach($mediaTypesNodes as $mediatypeNode)
		{
			$this->log("handling mediatype ".$UniqueName);
			
			$UniqueName = $mediatypeNode->valueOf("/UNIQUENAME");
			$ID = $mediatypeNode->valueOf("/ID");
			$Icon = $mediatypeNode->valueOf("/ICON");
			$IsComposite = $mediatypeNode->valueOf("/ISCOMPOSITE");
			$Select = $mediatypeNode->valueOf("/SELECT");
			$IsPubli = $mediatypeNode->valueOf("/ISPUBLI");
			$IsEvent = $mediatypeNode->valueOf("/ISEVENT");
			$IsTemplate = $mediatypeNode->valueOf("/ISTEMPLATE");
			$IsPageToCall = $mediatypeNode->valueOf("/ISPAGETOCALL");
			$CssFile = $mediatypeNode->valueOf("/CSSFILE");
			$DepConfig = $mediatypeNode->copyOf("/DEPENDENCIES/*");
			$Structural = $mediatypeNode->valueOf("/STRUCTURALTYPE");

			$sql = 'SELECT * FROM `mediatypes` WHERE MediaKind="'.$UniqueName.'";';
			$row = $db_conn->GetRow($sql);
			// the mediaType already exists ?
			if (!$row)
			{
				$this->log("new mediatype ".$UniqueName);
				// must first delete the old medias with the same mediatypeid
				if($ID)
				{
					$moduleInfo = moduleInfo('media');
					$old_medias_rs = $db_conn->Execute('SELECT ID FROM `'.$moduleInfo->tableName.'` WHERE `MediaTypeID`='.$ID.' AND Activity=1;');
					while($media_row = $old_medias_rs->FetchRow())
					{
						// dependencies
						$othersql.=deleteDependenciesTo($moduleInfo->ID,$media_row['ID']);
						$othersql.=deleteDependenciesFrom($moduleInfo->ID,$media_row['ID']);
						//categories
						$othersql.=removeFromCategories($moduleInfo->ID,$media_row['ID']);
						//descriptions
						$othersql.=deleteDescriptions($moduleInfo->ID,$media_row['ID']);
						// comments
						$othersql.=deleteComments($moduleInfo->ID,$media_row['ID']);
						$db_conn->Execute("UPDATE `".$moduleInfo->tableName."` SET `Activity`=0 WHERE ID=".$media_row['ID'].";");
					}
				}

				$sql = 'INSERT INTO `mediatypes`  (`ID`, `MediaKind`, `DepConfig`, `Icon`, `IsComposite`, `Select`, `Priority`, `IsPubli`, `IsEvent`, `IsTemplate`, `IsPageToCall`, `CssFile`, `StructuralType`) 
				 		VALUES ("'.$ID.'","'.encode_for_DB($UniqueName).'","'.encodeQuote($DepConfig).'","'.encode_for_DB($Icon).'","'.encode_for_DB($IsComposite).'","'.encode_for_DB($Select).'","999","'.encode_for_DB($IsPubli).'","'.encode_for_DB($IsEvent).'","'.encode_for_DB($IsTemplate).'","'.encode_for_DB($IsPageToCall).'","'.encode_for_DB($CssFile).'","'.encode_for_DB($Structural).'");';

				$db_conn->Execute($sql);
				$ID = $db_conn->Insert_Id();
				$this->createDefaultFiles($UniqueName);
				$this->createKeyring($UniqueName);
			}
			else
			{
				$ID = $row['ID'];

				if ($Structural)
				{
					// special case because mediaconfigurator don't handle structural type
					$structural_update = ',StructuralType="'.encode_for_DB($Structural).'"';
				}

				$sql = 'UPDATE mediatypes SET DepConfig="'.encodeQuote($DepConfig).'",Icon="'.encode_for_DB($Icon).'",IsComposite="'.encode_for_DB($IsComposite).'",`Select`="'.encode_for_DB($Select).'",`IsPubli`="'.encode_for_DB($IsPubli).'",`IsEvent`="'.encode_for_DB($IsEvent).'",`IsTemplate`="'.encode_for_DB($IsTemplate).'",`IsPageToCall`="'.encode_for_DB($IsPageToCall).'",CssFile="'.encode_for_DB($CssFile).'"'.$structural_update.' WHERE ID='.$ID.';';
				$db_conn->Execute($sql);

				// deleting the descriptionConfig previously associated
				$sql = "SELECT * FROM mediatypesconfig WHERE MediaTypeID=$ID";
				//$this->log('old description_config '.$sql);
				$rs = $db_conn->Execute($sql);
				while($row = $rs->FetchRow())
				{
					$sql = "DELETE FROM descriptionsconfig WHERE ID=".$row['DescriptionConfigID'].";";
					$db_conn->Execute($sql);
					$this->log('delete previous description_config '.$sql);
				}
				// deleting the mediatypes previously associated
				$sql = "DELETE FROM mediatypesconfig WHERE MediaTypeID=$ID;";
				$db_conn->Execute($sql);
			}
			
			//$this->log("config for mediatype ".$UniqueName);
			$configNodes = $mediatypeNode->getElements("CONFIG/*");
			$mediaModuleInfo = moduleInfo('media');
			foreach($configNodes as $configNode)
			{
				$languageID = $configNode->valueOf("@languageID");
				$denomination = $configNode->valueOf("DENOMINATION");
				// first creating the description config and after creating the mediatypeconfig because we must reference the ID of the first in the second
				$DescConfig = $configNode->copyOf("DESCRIPTIONCONFIG/*");
				
				$Alingual = $configNode->valueOf("DESCRIPTIONCONFIG/@alingual");
				if($Alingual!=1)
				{
					$Alingual = 0;					
				}

				$desc_row = array("LanguageID"=>$languageID,"ModuleID"=>$mediaModuleInfo->ID,"Config"=>$DescConfig,"Alingual"=>$Alingual);
				$sql = $db_conn->GetInsertSQL($pseudo_desc_rs, $desc_row);
				$db_conn->Execute($sql);
				$descConfigID = $db_conn->Insert_Id();
				$sql = 'INSERT INTO mediatypesconfig VALUES("'.$ID.'","'.$languageID.'","'.encode_for_DB($denomination).'","'.$descConfigID.'")';
				$db_conn->Execute($sql);
			}
		}

		$this->setSuccess("MediaTypes successfully modified.");
		return true;
	}
	
	function deleteKeyring($UniqueName)
	{
		$NQL = new NQL(false);
		$NQL->addOperation(
			'<KILL>
				<MODULEKEY>
					<WHERE>
						<INFO>
							<MODULETOID>5</MODULETOID>
							<ISVIRTUAL>1</ISVIRTUAL>
							<VIRTUALID>'.encode_to_xml($UniqueName).'</VIRTUALID>
						</INFO>
					</WHERE>
				</MODULEKEY>
			</KILL>');
		$NQL->execute();
	}
	
	function createKeyring($UniqueName)
	{
		$NQL = new NQL(false);
		$NQL->addOperation(
			'<CREATE>
				<MODULEKEY>
					<INFO>
						<MODULETOID>5</MODULETOID>
						<ISVIRTUAL>1</ISVIRTUAL>
						<DENOMINATION>'.encode_to_xml($UniqueName).' Read-write</DENOMINATION>
						<VIRTUALID>'.encode_to_xml($UniqueName).'</VIRTUALID>
						<FIELDS>
							<ID>W</ID>
						</FIELDS>
						<SERVICES>
							<category>W</category>
							<description>W</description>
							<file>W</file>
							<dependencies>W</dependencies>
							<comment>W</comment>
						</SERVICES>
					</INFO>
				</MODULEKEY>
			</CREATE>');
		$NQL->addOperation(
			'<CREATE>
				<MODULEKEY>
					<INFO>
						<MODULETOID>5</MODULETOID>
						<ISVIRTUAL>1</ISVIRTUAL>
						<ISPRIVATE>0</ISPRIVATE>
						<DENOMINATION>'.encode_to_xml($UniqueName).' Read-write, only on public and his own elements</DENOMINATION>
						<VIRTUALID>'.encode_to_xml($UniqueName).'</VIRTUALID>
						<FIELDS>
							<ID>W</ID>
						</FIELDS>
						<SERVICES>
							<category>W</category>
							<description>W</description>
							<file>W</file>
							<dependencies>W</dependencies>
							<comment>W</comment>
						</SERVICES>
					</INFO>
				</MODULEKEY>
			</CREATE>');
		$NQL->addOperation(
			'<CREATE>
				<MODULEKEY>
					<INFO>
						<MODULETOID>5</MODULETOID>
						<ISVIRTUAL>1</ISVIRTUAL>
						<DENOMINATION>'.encode_to_xml($UniqueName).' Read-only</DENOMINATION>
						<VIRTUALID>'.encode_to_xml($UniqueName).'</VIRTUALID>
						<FIELDS>
							<ID>R</ID>
						</FIELDS>
						<SERVICES>
							<category>R</category>
							<description>R</description>
							<file>R</file>
							<dependencies>R</dependencies>
							<comment>R</comment>
						</SERVICES>
					</INFO>
				</MODULEKEY>
			</CREATE>');
		$NQL->execute();
	}

	function createDefaultFiles($UniqueName)
	{
		// now creating the good files in Public/
		$library_default_dir = dirname(__FILE__)."/../Library/media/";
		$content = file_in_string($library_default_dir."mediatype.php");
		$content = str_replace('mediatype.xsl',$UniqueName.'.xsl',$content);
		$new_php_file = $GLOBALS["Public_dir"].$UniqueName.".php";
		if(!file_exists($new_php_file) && is_writable($GLOBALS["Public_dir"]) )
		{
			saveInFile($content,$new_php_file);
			chmod_Nectil($new_php_file);
			$new_xsl_file = $GLOBALS["Public_dir"].$UniqueName.".xsl";
			@copy($library_default_dir."mediatype.xsl",$new_xsl_file );
			chmod_Nectil($new_xsl_file);
			// copy the rest of the ressource files
			$ressource_files = array('common.css','common.xsl','default_nectil.css','default_nectil.xsl','utilities.js','navigation.xml');
			foreach($ressource_files as $res_file)
			{
				if(!file_exists($GLOBALS["Public_dir"].$res_file))
				{
					@copy($library_default_dir.$res_file,$GLOBALS["Public_dir"].$res_file);
					chmod_Nectil($GLOBALS["Public_dir"].$res_file);
				}
			}
		}
	}
}