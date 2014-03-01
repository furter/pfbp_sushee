<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/customcommands/translate_field_command.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
error_reporting(E_ERROR);
/**
 */
/**#@+
 * Required files
 */
require_once realpath(dirname(__FILE__) . '/../../common/translator.class.php');
/**#@-*/



/**
 * Translate Field Command
 * 
 * Usage:
 * 
 * <TRANSLATE>
 * 		<FIELD [ID=""]>
 * 			<FROM>LANGUAGE ID</FROM>
 * 			<TO>LANGUAGE ID</TO>
 * 			<TARGET>LANGUAGE ID | shared</TARGET>
 * 			<MODULE></MODULE>
 * 		</FIELD>
 * </TRANSLATE>
 * 
 * 
 * @author 		julien@nectil.com
 * @since		March 14, 2011
 */
class TranslateFieldCommand
{
	private $translator;
	private $ID;	
	private $module;
	private $from;
	private $fromDesc = true;		
	private $to;
	private $target;
	
	
	private function init($command)
	{
		$ID     = trim($command->valueOf('FIELD/@ID'));
		$module = trim($command->valueOf('FIELD/MODULE'));
		$from   = trim($command->valueOf('FIELD/FROM'));
		$to     = trim($command->valueOf('FIELD/TO'));
		$target = trim($command->valueOf('FIELD/TARGET'));
		
		if (is_numeric($ID)){
			$this->ID = $ID;
		}
		
		if ($module){									
			$this->module = array();
			foreach (explode(',', $module) as $module){
				if ($module){
					$this->module[] = $module;	
				}				
			}
		}
		
		if (!$from || !Sushee_Language::isValid($from)){
			$from = 'eng';			
			$this->fromDesc = false;
		}
		
		$this->from = new Sushee_Language($from);
		$this->from->getISO1();
		
		$this->translator = new Sushee_Translator();
		$this->translator->setOriginLanguage($this->from);
		
		if ($to){
			$this->to = array();
			foreach (explode(',', $to) as $langID){
				$langID = trim($langID);
				if ($langID && Sushee_Language::isValid($langID)){
					$lang = new Sushee_Language($langID);
					$lang->getISO1();
					$this->to[] = $lang;
				}
			}			
		}
		
		if (!$this->to){
			$this->to = $this->translator->getClassicLanguages();
		}
		
		if ($target && ($target=='shared' || Sushee_Language::isValid($target))){
			$this->target = $target;
		}
	}
	
	public function execute($command)
	{		
		$this->init($command);
		
		if ($this->ID){
			$searchCrits = "<FIELD ID='{$this->ID}'/>";
			
		} elseif ($this->module){			
			$searchCrits = '
				<FIELD>
					<INFO>
						<MODULE operator="IN">' . implode(',', $this->module) . '</MODULE>
					</INFO>
				</FIELD>';
			
		} else {
			$searchCrits = "<FIELD/>";
		}
		
		$shell = new Sushee_Shell(false);
		
		$shell->addCommand('
			<SEARCH>
				' . $searchCrits . '
				<RETURN>
					<INFO>
						<MODULE/>
						<DENOMINATION/>
						<FIELDNAME/>
					</INFO>
					<DESCRIPTIONS languageID="all">
						<TITLE/>
					</DESCRIPTIONS>
				</RETURN>
			</SEARCH>');
				
		$shell->execute();
		
		

		$updates = array();
		$fromID  = $this->from->getID();
		
		$fields = $shell->getElements('/RESPONSE/RESULTS/FIELD');		
		$i      = 0;
		$iMax   = count($fields);
		
		$jMax   = count($this->to);
		
		for (; $i<$iMax; $i++){
			$field   = $fields[$i];
			$fieldID = $field->valueOf('@ID');
			$module  = $field->valueOf('INFO/MODULE');
			
			if (!isset($updates[$module])){
				$updates[$module] = array();
			}
			
			$value = '';						
			if ($this->fromDesc){
				$value = trim($field->valueOf("DESCRIPTIONS/DESCRIPTION[@languageID='{$fromID}']/TITLE"));
			} else {
				$value = $this->getReadableName($field->valueOf('INFO/FIELDNAME'));
			}
			
			if ($value){
				if ($this->target && !trim($field->valueOf("DESCRIPTIONS/DESCRIPTION[@languageID='{$this->target}']/TITLE"))){
					$this->translator->setTargetLanguage($this->to[0]);
					$translation = $this->translator->execute($value);
					
					if ($translation){
						$translation = encode_to_xml($translation);
						$updates[$module][] = "
							<UPDATE>
								<FIELD ID='{$fieldID}'>
									<DESCRIPTIONS>
										<DESCRIPTION languageID='{$this->target}'>
											<TITLE>{$translation}</TITLE>
										</DESCRIPTION>
									</DESCRIPTIONS>
								</FIELD>
							</UPDATE>";
					}
				} elseif (!$this->target){
					for ($j=0; $j<$jMax; $j++){
						$lang   = $this->to[$j];
						$langID = $lang->getID();
						
						$this->translator->setTargetLanguage($lang);
						$translation = $this->translator->execute($value);
						
						if ($translation){
							$translation = encode_to_xml($translation);
							$updates[$module][] = "
								<UPDATE>
									<FIELD ID='{$fieldID}'>
										<DESCRIPTIONS>
											<DESCRIPTION languageID='{$langID}'>
												<TITLE>{$translation}</TITLE>
											</DESCRIPTION>
										</DESCRIPTIONS>
									</FIELD>
								</UPDATE>";
						}
					}
				}
			}//if ($value)
		}
		
		$doBatches = false;
		if (trim($command->valueOf('FIELD/@batch'))=='true'){
			$doBatches = true;
		}
		
		$query = array();
		
		foreach ($updates as $module => $cmds){
			$cmds = implode('', $cmds);
			
			if ($doBatches){				
				$query[] = "
					<CREATE>
						<BATCH>
							<INFO>
								<DOMAIN>translate/field/{$module}</DOMAIN>
								<TYPE>nql</TYPE>
								<COMMAND>{$cmds}</COMMAND>
								<STATUS>pending</STATUS>
							</INFO>
						</BATCH>
					</CREATE>";
			} else {
				$query[] = $cmds;
			}
		}
		
		return '<QUERY>' . implode('', $query) . '</QUERY>';		
	}
		
	public function getReadableName($fieldName)
	{
		//ex: 'foo:bar_Foo'     -> array('foo:bar', 'Foo');
		//ex: 'foo:bar_Foo_Bar' -> array('foo:bar', 'Foo_Bar');
		//ex: 'FooBar'          -> array('FooBar');
		$parts = explode('_', $fieldName, 2);
		
		if (count($parts)>1){
			//real field name
			//without namespace and/or module name
			$fieldName = $parts[1];
		}
		
		$humanFriendlyName = '';	
		$fieldName         = str_replace(array('_'), '', $fieldName);//case where real field name does contains underscore(s). Does it happen? 
		$fieldNameUpper    = strtoupper($fieldName);
		
		for ($i=0, $len=strlen($fieldName); $i<$len; $i++){
			$charA = $fieldName[$i];		//FirstName, Email1, ...
			$charB = $fieldNameUpper[$i];	//FIRSTNAME, EMAIL1, ...
			
			if ($charA==$charB){//'F'=='F', '1'=='1'
				$humanFriendlyName.= ' ';
			}
			
			$humanFriendlyName.= $charA;
		}
		
		return ltrim($humanFriendlyName);
	}	
}