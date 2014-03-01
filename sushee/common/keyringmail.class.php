<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/keyringmail.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
include_once(dirname(__FILE__).'/../common/nectil_element.class.php');
include_once(dirname(__FILE__).'/../common/mail.class.php');
include_once(dirname(__FILE__).'/../common/nql.class.php');

class KeyringMail extends SusheeObject
{
	var $keyring = false;
	var $password = false;
	var $template = false;
	var $contact = false;

	function KeyringMail(){}

	function setKeyring($keyring)
	{
		$this->keyring = $keyring;
	}

	function setContact($contact)
	{
		$this->contact = $contact;
	}

	function setPassword($password)
	{
		$this->password = $password;
	}

	function getPassword()
	{
		return $this->password;
	}

	function send()
	{
		if ($this->keyring && $this->contact)
		{
			$keyringID = $this->keyring->getID();
			$contactID = $this->contact->getID();
			
			$servermail = new ServerMail();
			$nql = new NQL(false);
			
			$nql->setLanguage($this->contact->getLanguage());
			
			// first checking the keyring is not in "sendaccess" = false
			$nql->addCommand(
				'<SEARCH name="keyring">
					<KEYRING ID="'.$keyringID.'">
						<INFO>
							<SENDACCESS operator="!=">0</SENDACCESS>
						</INFO>
					</KEYRING>
					<RETURN>
						<NOTHING/>
					</RETURN>
				</SEARCH>');
			$send_automatic_email = $nql->exists('/RESPONSE/RESULTS/KEYRING');

			if ($send_automatic_email)
			{
				// looking if there is a specific template for this keyring
				$return = 
					'<RETURN>
						<INFO/>
						<DESCRIPTION>
							<TITLE/>
							<CUSTOM>
								<sender/>
								<cc/>
							</CUSTOM>
						</DESCRIPTION>
					</RETURN>';
				$template = $this->getTemplate();
				if (!$template)
					return false;
				
				$templateID = $template->getID();
				$nql->addCommand(
					'<GET name="template">
						<TEMPLATE ID="'.$templateID.'"/>
						'.$return.'
					</GET>');

				// if no specific template, taking the default template
				$nql->execute();
				$templateNode = $nql->getElement('/RESPONSE/RESULTS[@name="template"]/TEMPLATE');

				if ($templateNode)
				{
					$templateID = $templateNode->valueOf('@ID');
					$template_localpath = $templateNode->valueOf('INFO/PATH');
					// path to the text version
					$template_localalternativepath = $templateNode->valueOf('INFO/ALTERNATIVEPATH');
					if (!$template_localpath)
					{
						// if not path indicated in the template, taking the default
						$template_localpath = '/'.Sushee_dirname.'/templates/keyring.xsl';
					}
					
					// prefixing with the path of the nectil installation
					$template_path = $GLOBALS["nectil_dir"].$template_localpath;
					if ($template_localalternativepath)
					{
						$template_alternativepath = $GLOBALS["nectil_dir"].$template_localalternativepath;
					}
					else
					{
						$template_alternativepath = false;
					}
					
					// sender and subject of the e-mail
					// (first description used because if shared description should not be used if description available in user's language
					$sender = decode_from_XML(UnicodeEntities_To_utf8($templateNode->valueOf('DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/sender')));
					$cc = decode_from_XML(UnicodeEntities_To_utf8($templateNode->valueOf('DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/cc')));
					$subject = decode_from_XML(UnicodeEntities_To_utf8($templateNode->valueOf('DESCRIPTIONS/DESCRIPTION[1]/TITLE')));

					$servermail->setSender($sender);
					$servermail->setSubject($subject);
					$servermail->addRecipient($this->contact->getEmail());
					if ($cc)
					{
						$servermail->addCc($cc);
					}
	
					// adding the detail of the contact, of the template and of the keyring in the NQL query, in order to allow full customization of the sent email
					$nql->addCommand(
						'<GET name="user">
							<CONTACT ID="'.$contactID.'"/>
							<RETURN>
								<INFO>
									<EMAIL1/>
									<FIRSTNAME/>
									<LASTNAME/>
								</INFO>
							</RETURN>
						</GET>');

					// also adding the user which assigned the keyring to add an eventual signature (not in the default template, but <KY name="sender"/> is the keyword to have in another template)
					$sender = new OfficityUser();
					if ($sender->getID())
					{
						$nql->addCommand(
							'<GET name="sender">
								<CONTACT ID="'.$sender->getID().'"/>
								<RETURN>
									<INFO>
										<EMAIL1/>
										<FIRSTNAME/>
										<LASTNAME/>
									</INFO>
								</RETURN>
							</GET>');
					}

					if (!$this->getPassword())
					{
						$nql->addCommand('<RESULTS name="password" static="true"><FORMER/></RESULTS>');
					}
					else
					{
						$nql->addCommand('<RESULTS name="password" static="true"><PASSWORD>'.$this->getPassword().'</PASSWORD></RESULTS>');
					}

					$nql->addCommand(
						'<GET name="keyring">
							<KEYRING ID="'.$keyringID.'"/>
						</GET>');

					$nql->addCommand(
						'<GET name="template">
							<TEMPLATE ID="'.$templateID.'"/>
							<RETURN>
								<INFO/>
								<DESCRIPTIONS/>
							</RETURN>
						</GET>');

					$nql->execute();
					$html = $nql->transform($template_path);
					$servermail->setHTML($html);

					if ($template_alternativepath)
					{
						$text = $nql->transformToText($template_alternativepath);
						$servermail->setText($text);
					}

					$res = $servermail->execute();
					return true;
				}
				return false;
			}
			return false;
		}
		else
		{
			return false;
		}
	}

	function setTemplate($template)
	{
		$this->template = $template;
	}

	function getTemplate()
	{
		$nql = new NQL(false);
		
		if ($this->template)
		{
			if ($this->template->getID() == 3 && !$this->template->exists())
			{
				$nql->addCommand('<INCLUDE file="/'.Sushee_dirname.'/Library/updates/20090415-templates/default-templates.nql"/>');
				$nql->execute();
			}
			return $this->template;
		}

		if ($this->keyring)
		{
			$keyringID = $this->keyring->getID();
			
			$nql->addCommand(
				'<SEARCH name="template">
					<TEMPLATE>
						<DEPENDENCY type="keyringTemplate" mode="reverse">
							<KEYRING ID="'.$keyringID.'"/>
						</DEPENDENCY>
					</TEMPLATE>
					<RETURN>
						<NOTHING/>
					</RETURN>
				</SEARCH>');

			// if no specific template, taking the default template
			$nql->execute();
			$templateNode = $nql->getElement('/RESPONSE/RESULTS[@name="template"]/TEMPLATE');
			if ($templateNode)
			{
				return new Template($templateNode->valueOf('@ID'));
			}
			else
			{
				$nql->addCommand(
					'<GET name="template">
						<TEMPLATE ID="2"/>
						<RETURN>
							<NOTHING/>
						</RETURN>
					</GET>');
				$nql->execute();
				$templateNode = $nql->getElement('/RESPONSE/RESULTS[@name="template"]/TEMPLATE');
				if (!$templateNode)
				{
					// if not available (deleted, or whatever), creating it
					$nql->addCommand('<INCLUDE file="/'.Sushee_dirname.'/Library/updates/20090415-templates/default-templates.nql"/>');
					$nql->execute();
				}

				// ID=2 is the default template for sending keyring
				return new Template(2);
			}
		}
		return false;
	}
}