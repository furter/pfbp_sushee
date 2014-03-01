<?php

require_once(dirname(__FILE__).'/../common/Mail/pop3.php');
require_once(dirname(__FILE__).'/../common/Mail/mimeDecode.php');
require_once(dirname(__FILE__).'/../file/file_functions.inc.php');
require_once(dirname(__FILE__).'/../common/automatic_classifier.class.php');
require_once(dirname(__FILE__).'/../common/crypt.class.php');
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/nectil_element.class.php');
require_once(dirname(__FILE__).'/../common/mail.class.php');
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");
require_once(dirname(__FILE__)."/../common/nql.class.php");

define('NQL1_PAGING',1);
define('NQL2_PAGING',2);

class checkMailsAccount extends RetrieveOperation
{
	var $perPage = 5;
	var $page = 1;
	var $profileNode = false;
	var $accountIDs = false;
	var $profile = false;
	var $paging_mode = NQL1_PAGING;
	
	function parse()
	{
		if($this->operationNode->exists('RETURN'))
		{
			$profileNode = $this->operationNode->getElement('RETURN');
		}
		else
		{
			$profileNode = $this->firstNode->getElement('WITH');
		}

		if($profileNode)
		{
			$this->profileNode = $profileNode;
			$perPage = $profileNode->valueOf('/@perPage');
			if(!$perPage)
				$perPage = $profileNode->valueOf('/@byPage');
			if($perPage && is_numeric($perPage))
				$this->perPage = $perPage;

			$this->profile = array('profile_path'=>$profileNode->getPath(),'profile_xml'=>$profileNode->getDocument());
			
		}
		else
		{
			$profile_name = 'mini_inbox';
			$this->profile = array('profile_name'=>$profile_name);
		}
		
		// --- PAGING --- //
		if($this->operationNode->exists('PAGINATE'))
		{
			$this->paging_mode = NQL2_PAGING;
			$paginateNode = $this->operationNode->getElement('PAGINATE');
			$perPage = $paginateNode->valueOf('/@display');
			if($perPage && is_numeric($perPage))
				$this->perPage = $perPage;
			$page = $paginateNode->valueOf('/@page');
			if($page && (is_numeric($page) || $page==='last'))
				$this->page = $page;
		}
		
		// --- ACCOUNTING --- //
		$IDstring = $this->firstNode->valueOf('/@ID');
		if($IDstring)
		{
			$IDs_array = explode(",",$IDstring);
			if(sizeof($IDs_array)>0)
			{
				$this->accountIDs = $IDs_array;
			}
		}
		return true;
	}
	
	function operate(){
		// connect to DB and get the accounts
		$db_conn= db_connect();
		$mails_to_grab_at_end = array();
		$moduleInfo = moduleInfo('mail');
		$account_cond = '';
		$totalCount = 0;
		if(is_array($this->accountIDs))
		{
			$account_cond=' AND `ID` IN ('.implode(',',$this->accountIDs).')';
		}
		
		$user = new NectilUser();
		if(!$user->isAuthentified())
		{
			$this->setError('You\'re not authenticated.');
			return false;
		}

		$acc_sql = 'SELECT `Denomination`,`ID`,`Host`,`Port`,`Login`,`Password`,`Encryption`,`LeaveOnServer`,`Email` FROM `mailsaccounts` WHERE `OwnerID`='.$user->getID().' AND `ID`!=1'.$account_cond.' AND `Activity`!=0';
		$rs = $db_conn->Execute($acc_sql);

		// foreach account 
		if($rs)
		{
			while($account_values = $rs->FetchRow())
			{
				$account = &new MailsAccount($account_values);
				if($this->page=='last')
				{
					$account->setRetreivingMode('descending');
				}
			
				// connect, login
				$connect_res = $account->connect();
				if($connect_res)
				{
					$login_res = $account->login();
					if($login_res===true)
					{
						// prepare entering mails
						$account->prepareEnteringMails();
					}
					else
					{
						$this->setError("Your login/password look erroneous for account `".$account->getField('Email')."`. Please verify them before trying again.");
						return false;
					}
				}
				else
				{
					$this->setError("Could not connect to your Mail server (".$account->getField('Host').")");
					return false;
				}
			}

			$this->log('finished prep');
			
			// for x mails (starting from the top or the bottom)
			$where_sql = ' FROM `'.$moduleInfo->getTableName().'` WHERE `Activity` = 2 AND `OwnerID` = \''.$user->getID().'\'';
			$count_waiting_mails_sql = 'SELECT COUNT(`ID`) AS ct'.$where_sql;
			do
			{
				$ct_row = $db_conn->getRow($count_waiting_mails_sql);
				$totalCount = $ct_row['ct'];
				$waiting_mails_sql = 'SELECT `ID`,`OwnerID`,`AccountID`,`UniqueID`';
				$waiting_mails_sql.=$where_sql;
				if($this->page == 'last'){
					$waiting_mails_sql.=' ORDER BY `ID` DESC';
				}
				$waiting_mails_sql.=' LIMIT 0,'.$this->perPage;
				sql_log($waiting_mails_sql);
				$waiting_mails_rs = $db_conn->Execute($waiting_mails_sql);
				while($mail_values = $waiting_mails_rs->FetchRow())
				{
					// parse and complete mail
					$mail = new Mail($mail_values);
					if($mail->parse())
						$mails_to_grab_at_end[]=$mail->getID();
					else
						$mail->delete(true);
					$this->log('finished parsing of one mail');
					header('X-pmaPing: Pong');
				}
			}
			while ($totalCount > 0 && sizeof($mails_to_grab_at_end) < $this->perPage);

			$this->log('finished parsing');
			$reg = new Sushee_MailsAccountRegister('');
			$reg->reset();
			while($account = $reg->next())
			{
				$account->disconnect();
			}
		}
	
		if(sizeof($mails_to_grab_at_end)>0)
		{
			// return a xml containing the detail about these mails
			
			$shell = new Sushee_Shell();
			$shell->setPublic(false);
			$shell->addCommand(
				'<SEARCH>
					<MAIL ID="'.implode(',',$mails_to_grab_at_end).'"/>'.
					$this->operationNode->copyOf('/RETURN').
					$this->operationNode->copyOf('/PAGINATE').
					$this->operationNode->copyOf('/SORT').
				'</SEARCH>');
			$shell->execute();
			
			$mails = $shell->copyOf('/RESPONSE/RESULTS/*');
		}
		else
		{
			$mails = '';
		}
		
		$attributes = $this->getOperationAttributes();
		
		if ($this->perPage)
		{
			$isLastPage = true;
			if($totalCount == 0)
			{
				// no mail left in the queue
				$totalCount = sizeof($mails_to_grab_at_end);
			}
			
			$totalPages = ceil($totalCount / $this->perPage);
			
			if($this->page == 'last' )
				$this->page = $totalPages;
			
			if($this->page < $totalPages)
				$isLastPage = false;
			
 			if($this->paging_mode == NQL2_PAGING)
			{
				// --- NQL2_PAGING --- //
				
				$attributes.=' page="'.$this->page.'"';
				$attributes.=' pages="'.$totalPages.'"';
				if($isLastPage)
					$attributes.=' last-page="true"';
				else
					$attributes.=' last-page="false"';
				$attributes.=' hits="'.$totalCount.'"';
			}
			else
			{
				// --- NQL1_PAGING ---//
				$attributes.=' page="'.$this->page.'"';

				if($isLastPage)
					$attributes.=' isLastPage="true"';
				else
					$attributes.=' isLastPage="false"';

				$attributes.=' totalPages="'.$totalPages.'"';
				$attributes.=' totalCount="'.$totalCount.'"';
			}
			
		}
		$this->setXML('<RESULTS'.$attributes.'>'.$mails.'</RESULTS>');
		return true;
	}
}