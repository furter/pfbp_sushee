<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/common_functions.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/ 

// die('
// <h1>This Website is temporarily unavailable.</h1>
// <h3>Due to an unforseen technical problem, the service is currently unavailable. We do everything possible to fix the problem quickly. Sorry for the inconvenience.</h3>
// <h1>Ce site est momentan&eacute;ment indisponible.</h1>
// <h3>En raison d&apos;un probl&egrave;me impr&eacute;vu technique, le service est actuellement indisponible. Nous mettons tout en &oelig;uvre pour corriger le probl&egrave;me au plus vite. Nous sommes d&eacute;sol&eacute; pour le d&eacute;sagr&eacute;ment encouru.</h3>
// ');

error_reporting( E_ERROR | E_PARSE | E_RECOVERABLE_ERROR | E_USER_ERROR | E_WARNING );

ini_set('session.gc_maxlifetime',86400);
ini_set('default_charset','UTF-8');

function isNectilMaster($url){
	$nectil_master = $GLOBALS["NectilMasterURL"];
	if(substr($nectil_master,0,4)=='www.')
		$nectil_master_alternative = substr($nectil_master,4);
	else
		$nectil_master_alternative = 'www.'.$nectil_master;
	$ok = false;
	
	if ($url==$GLOBALS['SusheeProtocol'].$nectil_master || $url==$GLOBALS['SusheeProtocol']."127.0.0.1" || $url==$GLOBALS['SusheeProtocol']."localhost" )
		$ok = true;
	if($nectil_master_alternative!='' && $url==$GLOBALS['SusheeProtocol'].$nectil_master_alternative )
		$ok = true;
	if(isset($GLOBALS["NectilMasterURL2"]) && $GLOBALS["NectilMasterURL2"]!='' && $ok===false){
		$nectil_master2_array = explode(',',$GLOBALS["NectilMasterURL2"]);
		foreach($nectil_master2_array as $nectil_master2){
			if($url==$GLOBALS['SusheeProtocol'].$nectil_master2)
				$ok = true;
			if($ok === false){
				if(substr($nectil_master2,0,4)=='www.')
					$nectil_master2_alternative = substr($nectil_master2,4);
				else
					$nectil_master2_alternative = 'www.'.$nectil_master2;
				if($nectil_master2_alternative!='' && $url==$GLOBALS['SusheeProtocol'].$nectil_master2_alternative)
					$ok = true;
			}
		}
	}
	return $ok;
}

//start timer for performance counting
require_once(dirname(__FILE__).'/../common/timer.class.php');
Sushee_Timer::start();

function chmod_Nectil($target_location){
	//global $directoryCHMOD;
	error_reporting(E_ERROR /*| E_WARNING*/ | E_PARSE);
	if(is_dir($target_location))
		$directoryCHMOD=0777;
	else
		$directoryCHMOD=0666;
	$old_umask = umask(0);
	chmod($target_location,$directoryCHMOD);
	umask($old_umask);
}

function redirect($redirect){
	header("HTTP/1.1 301 Moved Permanently");
	header ('location: '.$redirect);
	header("Connection: close");
	exit();
}

function formatMailAdress($address, $comment = false) {

    $address = trim($address);
    $comment = trim($comment);

    return $comment ? '"' . $comment . '" <' . $address . '>' : $address;
}

function sendMail($recipient_email,$subject,$body="",$sender="Sushee",$sender_email="",$headers="")
{
	require_once(dirname(__FILE__).'/../common/mail.class.php');

	$mail = new ServerMail();
	$mail->addRecipient($recipient_email);
	$mail->setSubject($subject);
	$mail->setText($body);
	$mail->setSender($mail->formatMailAdress($sender_email,$sender));

	if($headers)
	{
		$mail->addHeader($headers);
	}

	return $mail->execute();
}

function sendHTMLMail($sender,$sender_mail,$recipient,$recipient_email,$subject,$html,$text_alt='',$file='')
{
	require_once(dirname(__FILE__).'/../common/mail.class.php');

	$mail = new ServerMail();

	$mail->setSubject($subject);
	if($text_alt)
	{
		$mail->setText(UnicodeEntities_To_utf8($text_alt));
	}
	else if($html)
	{
		$mail->setText(generatePlainTextFromHTML($html));
	}
	
	if($html)
	{
		$mail->setHTML($html);
	}

	// senders, recipients
	$mail->setSender($mail->formatMailAdress($sender_mail,$sender));
	$mail->addRecipient($mail->formatMailAdress($recipient_email,$recipient));
	
	if($file)
	{
		$attachment = $GLOBALS["directoryRoot"].$file;
		if(is_file($attachment))
		{
			$mail->addAttachment($attachment);
		}
	}	
	return $mail->execute();
}

function xslt_error_handler($handler, $errno, $level, $info){
	$args = func_get_args();
	$infos = $args[3];
	$copy = array();
	foreach($infos as $key => $value){
		//echo $key." is ".$value."<br/>";
		$copy[$key]=$value;
	}
	
	
	if (substr($copy["URI"],0,7)=="file://")
		$copy["URI"]=substr($copy["URI"],7);
	$xsl_str = file_in_string($copy["URI"]);
	$xsl_str = str_replace("\r\n","\n",$xsl_str);
	$xsl_lines = explode("\n",$xsl_str);
	
	echo "<html>";
	echo "<head><title>".ucfirst($copy["msg"])."</title></head>";
	echo "<body>";
	echo "<h1>".ucfirst($copy["msg"])."</h1>";
	echo "<p style='font-size:110%;'>You have an error on line <strong>".$copy["line"]."</strong><br/> in file <span style:'text-decoration:underline;'>".$copy["URI"]."</span></p>";
	
	echo "<p style='line-height:18px;padding:5px;margin-left:20px;background-color:lightgrey;'>";
	echo "<code>".encode_to_XML($xsl_lines[$copy["line"]-2])."<br/><span style='color:red;'>".encode_to_XML($xsl_lines[$copy["line"]-1])."</span><br/>".encode_to_XML($xsl_lines[$copy["line"]])."</code></p>";
	if ($copy["code"]==2)
		echo "<p style='padding:5px;margin-left:20px;background-color:lightyellow;'><strong>Hint: </strong> Try to use the native characters instead. <ul><li> &amp;eacute; becomes &#233; <small style='color:grey;'>(or &amp;#233;)</small> </li><li>&amp;nbsp; becomes &amp;#160;</li></ul></p>";
		//&#160;
	echo "</body>";
	echo "</html>";
	die();
}

function getServerOS()
{
	$uname= php_uname();
	if (strtoupper(substr($uname, 0, 3))=="WIN")
		return "windows";
	else if(strtoupper(substr($uname, 0, 5))=="LINUX")
		return "linux";
	else
		return "other";
}

function generateID($params)
{
	if (!is_array($params))
		$params=array($params);
	$total='';
	foreach($params as $elem){
		if (is_array($elem))
			$elem=implode('',$elem);
		$total.=$elem;
	}
	return md5($total);
}

function flash_query($stringofXML)
{
	$output_xml = FALSE;
	
	//require(dirname(__FILE__)."/../private/request.php");
	require_once(dirname(__FILE__)."/../private/request_function.inc.php");
	$strRet = request($stringofXML);
	return $strRet;
}

function query($stringofXML="",$navigation=TRUE,$restrict_language=TRUE,$public=TRUE,$supp_params=TRUE,$no_response_node=false,$priority_language=false){

	// --- reajusting language if changed ---
	if(isset($_GET['language']) && $_GET['language'] != $GLOBALS['NectilLanguage'])
	{
		$GLOBALS['NectilLanguage'] = $_GET['language'];
	}

	if(isset($_GET['viewing_code']) && $_GET['viewing_code']!='' && $_GET['owner']!=='false' && substr($stats['URL'],0,8)!='/sushee/' && strlen($_GET['viewing_code'])>32)
	{
		// --- first getting back its current Mail2Web state ---
		require_once(dirname(__FILE__)."/../private/mailing_functions.inc.php");

		$mailingID = substr($_GET['viewing_code'],33);
		$db_conn = db_connect();
		$_GET['viewing_code'] = substr($_GET['viewing_code'],0,32);
		$recip_row = $db_conn->GetRow('SELECT * FROM `mailing_recipients` WHERE `ViewingCode`="'.$_GET['viewing_code'].'"'.(($mailingID!==false)?' AND `MailingID`=\''.$mailingID.'\' ':''));
		$url_update = '';
		if(is_array($recip_row))
		{
			if($recip_row['Mail2Web']==0)
			{
				// putting the url of the current page
				$url_update.=',Mail2WebFirstURL="'.$path.'"';
			}
			
			// if there is and ID, also putting the title of the media in the current language
			if(isset($_GET['ID']))
			{
				if($recip_row['Mail2WebMediaID'])
				{
					$viewedIDs = explode(',',$recip_row['Mail2WebMediaID']);
				}
				else
				{
					$viewedIDs = array();
				}

				if(!in_array($_GET['ID'],$viewedIDs))
				{
					$is_new = true;
				}

				if($is_new)
				{
					$viewedIDs[]=$_GET['ID'];
					$url_update.=',Mail2WebMediaID="'.implode(",", $viewedIDs).'"';
					$descrip_sql = 'SELECT * FROM `descriptions` WHERE `ModuleTargetID`=5 AND `Status`="published" AND `TargetID`="'.$_GET['ID'].'" AND LanguageID="'.$_SESSION[$GLOBALS["nectil_url"]]["language"].'";';
					$media_row = $db_conn->GetRow($descrip_sql);
					if($recip_row['Mail2WebMediaTitle'])
					{
						$viewedTitles = explode(',',$recip_row['Mail2WebMediaTitle']);
					}
					else
					{
						$viewedTitles = array();
					}

					$viewedTitles[] = str_replace(',','',$media_row['Title']);
					$url_update .= ',Mail2WebMediaTitle="'.implode(",", $viewedTitles).'"';
				}
			}
			
			if($recip_row['ViewingDate']!='0000-00-00 00:00:00')
			{
				$viewingDate = $recip_row['ViewingDate'];
			}
			else
			{
				$viewingDate = $GLOBALS['sushee_today'];
			}
			
			$recip_mail2web_sql = 'UPDATE `mailing_recipients` SET `Status`="sent",`HTMLInMailbox`=1,`ViewingDate`="'.$viewingDate.'",`Mail2Web`=`Mail2Web`+1'.$url_update.' WHERE `ViewingCode`="'.$_GET['viewing_code'].'"'.(($mailingID!==false)?' AND `MailingID`=\''.$mailingID.'\' ':'');
			$db_conn->Execute($recip_mail2web_sql);
			updateContactValidity($recip_row['ContactID'],0);
			if($recip_row['Mail2Web']==0)
			{
				updateMailingNbrNbrHTMLInMailbox($recip_row['MailingID']);
				updateMailingNbrMail2Web($recip_row['MailingID']);
			}
			updateMailingNbrSeen($recip_row['MailingID']);
		}
	}

	$output_xml = FALSE;
	if (substr($stringofXML,-4)==".xml" && file_exists(getcwd()."/".$stringofXML))
	{
		$handle = fopen(getcwd()."/".$stringofXML, "r");
		while (!feof($handle))
		{
			$buffer = fgets($handle, 4096);
			$newstringofXML.=$buffer;
		}
		$stringofXML = $newstringofXML;
	}

	require_once(dirname(__FILE__)."/../private/request_function.inc.php");

	$strRet = request($stringofXML,$no_response_node,$supp_params,$navigation,false,$restrict_language,$priority_language,true,$public);

	return $strRet;
}

function isCrawler($agent)
{
	return eregi ( "(bot)|(google)|(slurp)|(spider)|(crawl)|(archive)|(linkwalker)|(findlinks)|(biglotron)|(worm)|(twiceler)",$agent);
}

function fromSearchEngine($fromUrl)
{
	return eregi ( "(query=)|(search\?)|(&q=)|(\?q=)",$fromUrl);
}

function checkCaptcha($value,$name='default')
{
	$captcha = getCaptcha($name);
	if($captcha===false)
		return false;
	if(str_replace('o','0',strtolower($value))===strtolower($captcha))
		return true;
	return false;
}

function getCaptcha($name='default')
{
	if(!isset($_SESSION[$GLOBALS["nectil_url"]]['captcha'][$name]))
		return false;
	
	return $_SESSION[$GLOBALS["nectil_url"]]['captcha'][$name];
}

function transform($xml,$template,$more_params=array(),$nectil_url_at_end=true /* OBSOLETE */,$nl2br=true,$html_on_error=true,$output_type="html",$entities = true){
	include_once(dirname(__FILE__)."/../common/Cache/Lite.php");
	
	$options = array(
		'cacheDir' => $GLOBALS["nectil_dir"].'/Files/cache/html/',
		'lifeTime' => 2592000 /*30*24*3600*/
	);
	if(!file_exists($options['cacheDir'])){
		makeDir($options['cacheDir']);
	}
	
	if (isset($_GET['cache']) && $_GET['cache']==='refresh'){
		$remove = true;
	}
	$cwd = getcwd();
	if (file_exists($cwd."/$template"))
		$filetime = filemtime($cwd."/$template");
	else if(file_exists($template))
		$filetime = filemtime($template);
	if (file_exists($cwd."/common.xsl"))
		$common_filetime = filemtime($cwd."/common.xsl");
	else if(file_exists("common.xsl"))
		$common_filetime = filemtime("common.xsl");
	$id = generateID(array($_GET,$_POST,$_SESSION[$GLOBALS["nectil_url"]]["language"],$xml,$template,$more_params,$filetime,$common_filetime));

	// Create a Cache_Lite object
	$Cache_Lite = new Cache_Lite($options);

	if ($remove==true){
		$Cache_Lite->remove($id,'transform');
	}

	$sess = &$_SESSION[$GLOBALS["nectil_url"]];
	if ($_GET['cache']!=='false' && $sess["xml"]!="true" && $_GET["xml"]!="true"  && ($data = $Cache_Lite->get($id,'transform')) )
	{
		Sushee_Timer::lap('xsl cached');
		return $data;
	}
	else
	{
		$data = real_transform($xml,$template,$more_params,$nl2br,$html_on_error,$entities);
		if ($_GET["cache"]!=='false')
		{
			$Cache_Lite->save($data,$id,'transform');
		}

		return $data;
	}
}

function pdf_transform($result,$template,$download = true,$nl2br = false)
{
	require_once(dirname(__FILE__)."/../common/pdf.class.php");

	if($nl2br)
	{
		$result = nl2br($result);
	}

	if($_GET['fo']==='true')
	{
		$XSLFoGen = new XSLFoGenerator();
		$XSLFoGen->setTemplate($template);
		$res = $XSLFoGen->execute($result);
		
		$fo_file = $XSLFoGen->getFile();
		$fo_str = $fo_file->toString();
		xml_out($fo_str);
	}else{
		$generator = new SusheePDFGenerator();
		
		if($GLOBALS['PDFGenerator']=='ibex'){
			$generator->setPDFGenerator(new IbexPDFGenerator());
		}else{
			$generator->setPDFGenerator(new FopPDFGenerator());
		}

		$generator->setTemplate($template);

		if($_GET['cache']==='refresh' || $_GET['cache']==='false'){
			$generator->setCacheMode(false);
		}

		$sys = $generator->execute($result);
		$pdf_file = $generator->getFile();

		debug_log($sys);

		if ($pdf_file->exists() && $_GET['debug']!=='true' && $download==true){
			$pdf_file->forceDownload();

		}else if($download==true){
			echo $sys;
		}
		if ($pdf_file->exists())
			return $pdf_file->getPath();
		else
			return false;
	}
}

function getFilesRoot()
{
	global $directoryRoot;
	return $directoryRoot;
}

function transform_to_pdf($result,$template,$download = true)
{
	global $directoryRoot;
	global $slash;
	$res = pdf_transform($result,$template,$download);
	if($res)
		return $directoryRoot.$res;
	else 
		return $res;
}

function nectil_xslt_transform($transform_config)
{
	require_once(dirname(__FILE__).'/../common/xslt.class.php');
	
	$xml = $transform_config['xml'];
	$template = $transform_config['template'];
	$more_params = $transform_config['more_params'];
	$html_on_error = $transform_config['html_on_error'];
	
	if(is_array($more_params)){
		// additional params and default params
		$params = array_merge(nectil_xslt_params(),$more_params);
	}else{
		// no additional params, only the default ones
		$params = nectil_xslt_params();
	}

	if (!$GLOBALS['xslt_engine']) {
		// retro-compatibility
		if (function_exists("xslt_create") && $GLOBALS['use_libxslt']!==true && $transform_config['use_libxslt']!==true && $GLOBALS['use_saxon']!==true){
			$GLOBALS['xslt_engine'] = 'sablotron';
		}elseif($GLOBALS['use_saxon']){
			$GLOBALS['xslt_engine'] = 'saxon';
		}else if($GLOBALS['use_phpxslt']){
			$GLOBALS['xslt_engine'] = 'phpxslt';
		}else{
			$GLOBALS['xslt_engine'] = 'libxslt';
		}
	}

	$transformer = new SusheeXSLTransformer();
	$transformer->setParams($params);
	$transformer->setTemplate($template);
	$transformer->setEngine($GLOBALS['xslt_engine']);
	$transformer->outputError($html_on_error);

	return $transformer->execute($xml);
}

function get_xml_from_post_data($log=true)
{
	$HTTP_RAW_POST_DATA = $GLOBALS['HTTP_RAW_POST_DATA'];
	$stringofXML = utf8_decode(utf8_To_UnicodeEntities($HTTP_RAW_POST_DATA));
	$stringofXML=trim($stringofXML);
	if( $stringOfXML!="" && substr($stringofXML,-1) != ">" ){
		$stringofXML.=">";
	}
	if($log){
		// Logging
		query_log($stringofXML);
	}
	// building a tree with the xmlString
	$xml = new XML($stringofXML);
	return $xml;
}

function getLanguage()
{
	return $GLOBALS["NectilLanguage"];
}

function nectil_xslt_params()
{
	$sess = &$_SESSION[$GLOBALS["nectil_url"]];
	
	$params = array('today'=>$GLOBALS["sushee_today"]);
	$params['cache'] = $sess['cache'];
	$params["language"] = $GLOBALS["NectilLanguage"];
	$params["sessionID"] = session_id();
	
	$params["files_url"]=$GLOBALS["files_url"];
	$params["files_dir"]=$GLOBALS["nectil_dir"]."/Files";
	$params["public_dir"]=$GLOBALS["nectil_dir"]."/Public";
	
	$params["this_url"]=$_SERVER['REQUEST_URI'];
	$params["this_script"]=basename($_SERVER['SCRIPT_NAME']);
	$params["language_url"]=$params["this_url"];
	$params["language_url"]=str_replace(array("cache=false&","cache=false"),"",$params["language_url"]);
	$params["language_url"]=preg_replace ( '/&language=.[^&]*/i', '', $params["language_url"]);
	$params["language_url"]=preg_replace ( '/\?language=.[^&]*$/i', '?', $params["language_url"]);
	$params["language_url"]=preg_replace ( '/\?language=.[^&]*&/i', '?', $params["language_url"]);
	if (strpos($params["language_url"],'?')===FALSE)
	$params["language_url"]=$params["language_url"].'?';
	else if(substr($params["language_url"],-1)!='&' && substr($params["language_url"],-1)!='?')
	$params["language_url"]=$params["language_url"].'&';
	$params["logged"]="false";
	$params["edition"]="false";
	$params["all_languages"]=$sess["all_languages"];
	if (isset($sess['SESSIONuserID']))
		$params["logged"]="true";
	if (isset($sess['edition']) && $sess['edition']=="true")
		$params["edition"]="true";
	return $params;
}

function resolve_template_path($template)
{
	global $slash;
	if(substr($template,0,7)=='file://'){
		$template = substr($template,7);
	}else if(file_exists(getcwd ().$slash."$template")){
		$template = getcwd().$slash."$template";
	}else if(file_exists("$template")){
		$template = realpath("$template");
	}
	return $template;
}

function real_transform($xml,$template,$more_params=array(),$nl2br=true,$html_on_error=true,$entities=true)
{
	$template = resolve_template_path($template);
	if(!file_exists($template))
	{
		throw new SusheeException('File `'.$template.'` doesnt exist');
	}

	if($nl2br)
	{
		$xml = nl2br($xml);
	}

	if( ($sess["xml"]=="true" || $_GET["xml"]=="true") && ($GLOBALS['xmlVisible']!='false' || substr($_SERVER["DOCUMENT_ROOT"].$_SERVER['REQUEST_URI'],0,strlen($GLOBALS["library_dir"]))==$GLOBALS["library_dir"]))
	{
	  xml_out($xml);
	}

	$transform_config = array('xml'=>$xml,'template'=>$template,'more_params'=>$more_params,'html_on_error'=>$html_on_error);

	$xslt_html = nectil_xslt_transform($transform_config);
	$xslt_html = bbdecode($xslt_html);

   	if($entities){
		$xslt_html = utf8_To_UnicodeEntities($xslt_html);
   	}

   	return $xslt_html;
}

function transform_to_text($xml,$template,$more_params=array(),$nl2br=true){
	$template = resolve_template_path($template);
	
	if($nl2br)
		$xml = nl2br($xml);
	
	$xml = generate_utf8($xml);

	$transform_config = array('xml'=>$xml,'template'=>$template,'more_params'=>$more_params,'html_on_error'=>false);
	$xslt_text = nectil_xslt_transform($transform_config);
	return $xslt_text;
}

function handleFieldOperator($operator,$former_value,$value){
	switch($operator){
		case 'uppercase':
			if($value){
				$value=strtoupper($value);
			}else{
				$value=strtoupper($former_value);
			}
			return $value;
			break;
		case 'lowercase':
			if($value){
				$value=strtolower($value);
			}else{
				$value=strtolower($former_value);
			}
			return $value;
			break;
		case 'capitalize':
			if($value){
				$value=ucfirst(strtolower($value));
			}else{
				$value=ucfirst(strtolower($former_value));
			}
			return $value;
			break;
		case 'md5':
		case 'MD5':
			return md5($value);
		case 'encrypt':
			return mysql_password($value);
		case 'before':
			return $value.$former_value;
		case 'append':
			return $former_value.$value;
		case '+':
			return $former_value+$value;
		case '-':
			return $former_value-$value;
		case '*':
			return $former_value*$value;
		case '/':
			if($value!=0)
			return $former_value/$value;
		case '++':
			return $former_value+1;
		case '--':
			return $former_value-1;
		default:
			return $value;
	}
}

function isLogged(){
	return isset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']);
}

function create($xml,$current_path,$firstNodePath){
	$firstNode = $xml->nodeName($current_path.'/*[1]');
	include_once(dirname(__FILE__)."/../private/create.inc.php");
	$query_result = createQuery("",$xml,"CREATE",$current_path,$firstNode,$firstNodePath);
	$message_xml = new XML('<?xml version="1.0" encoding="utf-8"?>'.$query_result);
	return $message_xml->getData('/MESSAGE/@elementID');
}
function update($xml,$current_path,$firstNodePath){
	$firstNode = $xml->nodeName($current_path.'/*[1]');
	include_once(dirname(__FILE__)."/../private/update.inc.php");
	$query_result = updateQuery("",$xml,"UPDATE",$current_path,$firstNode,$firstNodePath);
	$message_xml = new XML('<?xml version="1.0" encoding="utf-8"?>'.$query_result);
	//echo "/*".$message_xml->getData('/MESSAGE/attribute::modificationDate')."*/";
	return $message_xml->getData('/MESSAGE/@modificationDate');
}


function shorten($path){

	if ( substr($path,0,strlen($_SERVER["DOCUMENT_ROOT"]))===$_SERVER["DOCUMENT_ROOT"] )
	$short_path = substr($path,strlen($_SERVER["DOCUMENT_ROOT"]));
	return $short_path;
}

function checkSpam($message){
	$message = strtolower($message);
	if(strpos($message,'mime-version:')!==false)
		return true;
	if(strpos($message,'to:')!==false)
		return true;
	if(strpos($message,'bcc:')!==false)
		return true;
	if(strpos($message,'cc:')!==false)
		return true;
	if(strpos($message,'subject:')!==false)
		return true;
	if(strpos($message,'content-type:')!==false)
		return true;
	if(strpos($message,'multipart/alternative;')!==false)
		return true;
	if(strpos($message,'boundary=')!==false)
		return true;
	if(strpos($message,'content-transfer-encoding:')!==false)
		return true;
}


function file_in_string($path){
	if (file_exists($path)){
		$handle = fopen($path, "r");
		while (!feof($handle)) {
   			$buffer = fgets($handle, 4096);
   			$str.=$buffer;
		}
		return $str;
	}else 
		return false;
}
function dir_xml($path){
	include_once(dirname(__FILE__)."/../file/file_functions.inc.php");
	if ($dir = @opendir($path)) {
        /* loop once for each name in the directory */
		$fileList = "<?xml version='1.0' encoding='utf-8'?><DIR>";
        while($file = readdir($dir)) {
            $isFileVisible=true;
			// if the name is not a directory and the name is not the name of this program file
			$extension = substr($file,-4);
            if($file == "." || $file == ".." || $file == "$ThisFileName" || ($extension!=".xml" && $extension!=".XML") )
                $isFileVisible = false;
            
            if($isFileVisible) {
				$file_xml = new XML(/*$path.'/'.$file*/);
				
				$file_xml->setSkipWhiteSpaces(true);
				
				$file_xml->importFromFile($path.'/'.$file);
				if ($file_xml->loaded){
					if ($file_xml->match("/FILE")){
						
						$fileList.=$file_xml->toString("/","",false);
					}else if ($file_xml->match("/DIR")){
						
						$fileList.=$file_xml->toString("/DIR/FILE","",false);
					}
				}
            }
        }
		$fileList.="</DIR>";
		return unutf8($fileList);
    }
    else {
		die( xml_msg("1","-1","-1","Directory doesn't exist:".$path));
    }
}

function login($email,$password){
	$output_xml = FALSE;
	$stringofXML='<QUERY><LOGIN>'.encode_to_XML(stripcslashes($email)).'</LOGIN><LANGUAGEID>'.$_SESSION[$GLOBALS["nectil_url"]]["language"].'</LANGUAGEID><PASSWORD>'.encode_to_XML(stripcslashes($password)).'</PASSWORD></QUERY>';
	$stringofXML = utf8_encode($stringofXML);
	
	require_once(dirname(__FILE__)."/../public/login.php");
	return $strRet;
}

function getUserID(){
	return $_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID'];
}

function sendNewPassword($contactID,$nectil_url="",$signer = array()){
	
	if(!is_numeric($contactID))
		$sql = 'SELECT * FROM `contacts` WHERE `Email1`="'.encodeQuote($contactID).'" AND `Activity`=1;';
	else
		$sql = 'SELECT * FROM `contacts` WHERE `ID`='.$contactID.' AND `Activity`=1;';
	
	$db_conn = db_connect();
	$row = $db_conn->GetRow($sql);
	if($row && $row['Password']){
		include_once(dirname(__FILE__).'/../common/keyringmail.class.php');
		
		$keyringmail = new KeyringMail();
		$contactElt = new Contact($row);
		$keyringmail->setContact($contactElt);
		$keyringmail->setTemplate(new Template(3)); // 3 is the ID of the default template for new passwords
		$keyring = $contactElt->getKeyring();
		
		if(!$keyring)
			return false;
			
		$keyringmail->setKeyring($keyring);
		
		$nectil_password = generate_password();
		$upd_sql = 'UPDATE `contacts` SET `Password`="'.mysql_password($nectil_password).'" WHERE `ID`=\''.$row['ID'].'\';';
		
		$res = $db_conn->Execute($upd_sql);
		
		$keyringmail->setPassword($nectil_password);
		$res = $keyringmail->send();
		if(!$res)
			return false;
		return true;
	}
	return false;
}

function generate_password($digits=8,$c=1,$st='L'){
   if(!ereg("^([4-9]|((1|2){1}[0-9]{1}))$",$digits)) // 4-29 chars allowed
     $digits=4;
   for(;;)
   {
     $pwd=null; $o=null;
     // Generates the password ....
     for ($x=0;$x<$digits;)
     {
         $y = rand(1,1000);
         if($y>350 && $y<601) $d=chr(rand(48,57));
         if($y<351) $d=chr(rand(65,90));
         if($y>600) $d=chr(rand(97,122));
         if($d!=$o)
         {           
           $o=$d; $pwd.=$d; $x++;
         }
     }
     // if you want that the user will not be confused by O or 0 ("Oh" or "Null")
     // or 1 or l ("One" or "L"), set $c=true;
     if($c)
     {
         $pwd=eregi_replace("(l|i)","1",$pwd);
         $pwd=eregi_replace("(o)","0",$pwd);
     }
     // If the PW fits your purpose (e.g. this regexpression) return it, else make a new one
     // (You can change this regular-expression how you want ....)
     if(ereg("^[a-zA-Z]{1}([a-zA-Z]+[0-9][a-zA-Z]+)+",$pwd))
         break;   
   }
   if($st=="L") $pwd=strtolower($pwd);
   if($st=="U") $pwd=strtoupper($pwd);
   return $pwd;
}

function getInfos(&$moduleInfo,$IDs)
{
	$db_conn = db_connect();
	if (!is_array($IDs))
		$IDs = array($IDs);
	$sql = "SELECT * FROM `".$moduleInfo->tableName."` WHERE ";
	for($i=0;$i<sizeof($IDs);$i++){
		$sql.=" `ID`='".$IDs[$i]."'";
		if ($i!=sizeof($IDs)-1)
		$sql.=" OR";
	}
	$rs = $db_conn->Execute($sql);
	return $rs;
}

function getInfo(&$moduleInfo,$ID)
{
	$db_conn = db_connect();
	$sql = "SELECT * FROM `".$moduleInfo->tableName."` WHERE `ID`='$ID';";
	//sql_log($sql);
	$row = $db_conn->GetRow($sql);
	return $row;
}

function resetModule(&$moduleInfo)
{
	$db_conn = db_connect();
	$sql = "DELETE FROM `".$moduleInfo->tableName."` WHERE `ID`!=1 AND `IsLocked`=0;";
	$db_conn->Execute($sql);
	return $sql;
}

function getServicesArray()
{
	$db_conn = db_connect(TRUE);
	$sql = "SELECT * FROM services;";
	$rs = $db_conn->Execute($sql);
	$services = array();
	if ($rs){
		while($row = $rs->FetchRow()){
			$services[$row["Denomination"]]=$row;
			$services[$row["Denomination"]]["SECURITY"]="W";
		}
	}
	return $services;
}

function generateInfoXML(&$moduleInfo,&$elem,&$fields_array,$profile_array,$output='html',$info_tag=true,$include_creator_info=false,$include_weekdays=false,$include_modifier_info=false,$include_owner_info=false)
{
	require_once(dirname(__FILE__)."/../common/infoxml.class.php");

	$infoGenerator = new NectilElementInfo($moduleInfo->getID(),$elem['ID'],$elem);
	$infoGenerator->setSecurityProfile($fields_array);

	$profile = new InfoProfile($profile_array);
	$profile->includeInfoTag($info_tag);
	$profile->includeCreatorInfo($include_creator_info);
	$profile->includeModifierInfo($include_modifier_info);
	$profile->includeOwnerInfo($include_owner_info);
	$profile->includeWeekday($include_weekdays);
	$infoGenerator->setProfile($profile);

	return $infoGenerator->getXML();
}

function getLanguageInfo($ID)
{
	$db_conn = db_connect(TRUE);
	$sql = "SELECT * FROM languages WHERE ID='$ID';";
	$row = $db_conn->GetRow($sql);
	return $row;
}

function getCountryInfo($ID)
{
	$db_conn = db_connect(TRUE); // it's common
	$sql = "SELECT * FROM countries WHERE ID='$ID';";
	$row = $db_conn->GetRow($sql);
	return $row;
}

function resetNectilSession()
{
	session_start();
	unset($_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']);
	unset($_SESSION[$GLOBALS["nectil_url"]]["private"]);
	unset($_SESSION[$GLOBALS["nectil_url"]]["public"]);
	unset($_SESSION[$GLOBALS["nectil_url"]]['sushee']);
	session_write_close();
}

function logout()
{
	resetNectilSession();
}

function prepareNectilSession()
{
	if (!isset($_SESSION[$GLOBALS["nectil_url"]]['public']))
		$_SESSION[$GLOBALS["nectil_url"]]['public'] = array("moduleInfo_array"=>array(),"moduleInfo_array_byID"=>array(),"depType_array"=>array(),"depType_array_byID"=>array());
	if (!isset($_SESSION[$GLOBALS["nectil_url"]]['private']))
		$_SESSION[$GLOBALS["nectil_url"]]['private'] = array("moduleInfo_array"=>array(),"moduleInfo_array_byID"=>array(),"depType_array"=>array(),"depType_array_byID"=>array());
	if(!isset($GLOBALS[$GLOBALS['nectil_url']]['public']))
		$GLOBALS[$GLOBALS["nectil_url"]]['public'] = array("moduleInfo_array"=>array(),"moduleInfo_array_byID"=>array(),"depType_array"=>array(),"depType_array_byID"=>array());
}

// function that gets back a global moduleInfo instead of duplicating each time
function &moduleInfo($moduleName/* can be the ID too*/)
{
	require_once(dirname(__FILE__)."/../common/susheesession.class.php");
	require_once(dirname(__FILE__)."/../common/nectil_user.class.php");

	$variable_name = '';

	if(Sushee_Request::isSecured())
	{
		$prefix='public';
	}
	else
	{
		$prefix='private';
	}

	// to have only one notation in session
	if (!is_numeric($moduleName))
	{
		$moduleName = strtolower($moduleName);
	}

	$variable_name = $moduleName;
	if(!Sushee_Session::getVariable($prefix.$variable_name))
	{
		$moduleInfo = new moduleInfo($moduleName);

		if($moduleInfo->loaded)
		{
			// saving the two forms of accessing the module definition in the session (by ID and by name)
			Sushee_Session::saveVariable($prefix.strtolower($moduleInfo->getName()),$moduleInfo);
			Sushee_Session::saveVariable($prefix.$moduleInfo->getID(),$moduleInfo);
		}
		else
		{
			// if its not a valid module, simply returning the object as it is and not saving in session something invalid
			return $moduleInfo;
		}
	}

	return Sushee_Session::getVariable($prefix.$variable_name);
}

function depType($depName/* can be the ID too*/,$moduleName="")
{
	prepareNectilSession();
	if ($GLOBALS['dev_request']===TRUE)
		$sess_mod_array =  &$_SESSION[$GLOBALS['nectil_url']]['public'];
	else
		$sess_mod_array =  &$_SESSION[$GLOBALS['nectil_url']]['private'];
	if ($GLOBALS['serverOS']=='windows')
		return new dependencyType($depName,$moduleName);
	if (!is_numeric($depName)){
		if (1 || !isset($sess_mod_array['depType_array']) || !isset($sess_mod_array['depType_array'][$depName]) || !$sess_mod_array['depType_array'][$depName]->tableName ){
			  $sess_mod_array['depType_array'][$depName] = new dependencyType($depName,$moduleName);
			  $sess_mod_array['depType_array_byID'][$sess_mod_array['depType_array'][$depName]->ID]=$sess_mod_array['depType_array'][$depName];
			  return $sess_mod_array['depType_array'][$depName];
		}else{ // we return the object defined earlier
			return $sess_mod_array['depType_array'][$depName];
		}
	}else{
		if (1 || !isset($sess_mod_array['depType_array_byID']) || !isset($sess_mod_array['depType_array_byID'][$depName]) || !$sess_mod_array['depType_array'][$depName]->tableName ){
			$sess_mod_array['depType_array_byID'][$depName] = new dependencyType($depName,$moduleName);
			$sess_mod_array['depType_array'][$sess_mod_array['depType_array_byID'][$depName]->name]=$sess_mod_array['depType_array_byID'][$depName];
			return $sess_mod_array['depType_array_byID'][$depName];
		}else{
			return $sess_mod_array['depType_array_byID'][$depName];
		}
	}
}

function generateMsgXML($strType,$strMsg,$strError=0,$elementID='',$name='',$creationDate='',$modificationDate='',$suppl=''){
	$strRet='<MESSAGE msgType="'.$strType.'" errorCode="'.$strError.'"'.(($elementID)?' elementID="'.$elementID.'"':'').(($name)?' name="'.$name.'"':'').(($creationDate!='')?' creationDate="'.$creationDate.'"':'').' '.(($modificationDate!='')?' modificationDate="'.$modificationDate.'"':'').' '.$suppl.'>'.$strMsg.'</MESSAGE>';
	return $strRet;
}
// to ensure compatibility with the V1 style scripting
function xml_msg($strType,$strID,$strSID,$strMsg,$strError=0)
{
	header ("content-type: text/xml");
	$strRet="<?xml version=\"1.0\"?><RESPONSE userID=\"".$_SESSION[$GLOBALS["nectil_url"]]['SESSIONuserID']."\" sessionID=\"".session_id()."\">".generateMsgXML($strType,$strMsg,$strError)."</RESPONSE>";
	return $strRet;
}

function generate_utf8($str)
{
	return UnicodeEntities_To_utf8(utf8_encode($str));
}

function unutf8($str)
{
	return utf8_decode(UnicodeEntities_To_utf8($str));
}

function xml_out($str)
{
	// outputing to navigator with the correct header
	header ("content-type: text/xml; charset=UTF-8");
	die($str);
}

function debug_log($str)
{
	// log in /logdev/sql.log
	// previously /debug.log or /logs/debug.log
	if ($GLOBALS['sushee_logsdev'] === 'true' || $_SESSION[$GLOBALS['nectil_url']]['logsdev'] === 'true')
	{
		require_once(dirname(__FILE__)."/../common/log.class.php");

		$file = new LogFile('debug.log');
		$file->log( new SusheeLog($str,true) );
	}
}

function sql_log($str)
{
	// log in /logdev/sql.log
	// previously /logs/sql.log
	if ($GLOBALS['sushee_logsdev'] === 'true' || $_SESSION[$GLOBALS['nectil_url']]['logsdev'] === 'true')
	{
		require_once(dirname(__FILE__)."/../common/log.class.php");

		$file = new LogFile('sql.log');
		$file->log( new SusheeLog($str,true) );
	}
}

function response_log($str)
{
	// log in /logdev/response.log
	// previously /out.txt or /logs/response.log
	if ($GLOBALS['sushee_logsdev'] === 'true' || $_SESSION[$GLOBALS['nectil_url']]['logsdev'] === 'true')
	{
		require_once(dirname(__FILE__)."/../common/log.class.php");

		$file = new LogFile('response.log');
		$file->log( new SusheeLog($str,false) );
	}
}

function query_log($str)
{
	// log in /logdev/query.log
	// previously /log.txt or /logs/query.log
	if ($GLOBALS['sushee_logsdev'] === 'true' || $_SESSION[$GLOBALS['nectil_url']]['logsdev'] === 'true')
	{
		require_once(dirname(__FILE__)."/../common/log.class.php");
		require_once(dirname(__FILE__)."/../common/namespace.class.php");

		$file = new LogFile('query.log');

		$cleaner = new sushee_NamespaceCleaner();
		$str = $cleaner->execute($str);

		$file->log( new SusheeLog($str,false) );
	}
}

function errors_log($str)
{
	// log in /logdev/query.log
	// previously /logs/errors.log
	if ($GLOBALS['sushee_logsdev'] === 'true' || $_SESSION[$GLOBALS['nectil_url']]['logsdev'] === 'true')
	{
		require_once(dirname(__FILE__)."/../common/log.class.php");
	
		$file = new LogFile('errors.log');
		$file->setMaxSize(false);
		$file->log( new SusheeLog($str,true) );
	}
}

function makeExecutableUsable($exec)
{
	$exec = "\"".$exec."\"";
	return $exec;
}

function batchFile($command)
{
	global $slash;
	$OS = getServerOS();
	if ($OS=='windows')
	{
		$command = 'call '.$command;
	}
	return $command;
}

function saveInFile($msg,$filename)
{
	// open file
	$fd = fopen($filename, "w+");
	fwrite($fd,$msg);
	fclose($fd);
	//chmod ($filename, 0777);
	chmod_Nectil($filename);
}

function removeaccents($string)
{
   $string = utf8_To_UnicodeEntities($string);
   $search = array("&#192;","&#193;","&#194;","&#195;","&#196;","&#197;","&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#210;","&#211;","&#212;","&#213;","&#214;","&#216;","&#242;","&#243;","&#244;","&#245;","&#246;","&#248;","&#200;","&#201;","&#202;","&#203;","&#232;","&#233;","&#234;","&#235;","&#199;","&#231;","&#204;","&#205;","&#206;","&#207;","&#236;","&#237;","&#238;","&#239;","&#217;","&#218;","&#219;","&#220;","&#249;","&#250;","&#251;","&#252;","&#255;","&#209;","&#241;");
   $replace = array("a","a","a","a","a","a","a","a","a","a","a","a","o","o","o","o","o","o","o","o","o","o","o","o","e","e","e","e","e","e","e","e","c","c","i","i","i","i","i","i","i","i","u","u","u","u","u","u","u","u","y","n","n");	
   // en reserve : &#167; 
   //$string= strtr($string, utf8_decode(unhtmlentities(implode("",$search))),implode("",$replace));
   $string= strtr($string,unhtmlentities(implode("",$search)),implode("",$replace));
   $multiletter_search = array("&#8212;","&#179;","&#178;","&#176;","&#180;","&#187;","&#171;","&#169;","&#8221;","&#8220;","&#160;","&#8211;","&#8216;","&#8217;","&#339;","&#230;","&#8230;","&#8364;","&#8226;","&#367;","&#269;","&#345;","&#253;","&#382;","&#283;","&#353;","&#337;","&#128;","&#357;");
   $multiletter_replace = array("-","3","2","o","'","\"","\"","c","\"","\""," ","-","'","'","oe","ae","...","euro","*","u","c","r","y","z","e","s","o","euro","t");
   $search = array_merge ($search, $multiletter_search);
   $replace = array_merge ($replace, $multiletter_replace);
   $string = str_replace($search,$replace,$string);
   return $string;
}

function removeSpecialChars($string)
{
	$code_entities_match = array(' ','--','&quot;','!','@','#','$','%','^','&','*','(',')','_','+','{','}','|',':','"','<','>','?','[',']','\\',';',"'",',','.','/','*','+','~','`','=');
	return str_replace($code_entities_match, ' ', $string);
}

function Sushee_removeRedundantsWords($string)
{
	// removing extra spaces tabs etc.
	$string = preg_replace('/(\s\s+|\t|\n)/', ' ', $string);
	// get an array of words
	$word_array = explode(' ', $string);
	// get an array containing only unique words
	$unique_word_array = array_unique($word_array);
	// send back a string
	return implode(' ',$unique_word_array);
}

function Sushee_removeSmallWords($string)
{
	$min = $GLOBALS['MySQLFullTextMinLength'];
	$pattern = '/\b\w{1,'.($min-1).'}\b/';
	return preg_replace($pattern,' ',$string);
}

function Sushee_getSearchText($string)
{
	$string = decode_from_XML($string);
	$string = removeaccents($string);
	$string = removeSpecialChars($string);
	$string = Sushee_removeSmallWords($string);
	$string = Sushee_removeRedundantsWords(strtolower($string));
	return trim($string);
}

function Sushee_getRichText($string)
{	
	$styled = new XML($string);
	return $styled->valueOf('/*');
}

function is_utf8($string) {
   return (preg_match('/^([\x00-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xec][\x80-\xbf]{2}|\xed[\x80-\x9f][\x80-\xbf]|[\xee-\xef][\x80-\xbf]{2}|f0[\x90-\xbf][\x80-\xbf]{2}|[\xf1-\xf3][\x80-\xbf]{3}|\xf4[\x80-\x8f][\x80-\xbf]{2})*$/', $string) === 1);
}

function setDownloadHeaders($filename,$filesize=NULL,$charset=false){
	header("Pragma: public");
    header("Expires: 0"); // set expiration time
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header( "Content-type: application/force-download".(($charset!==false)?";charset:".$charset:'') );
    header("Content-Transfer-Encoding: Binary");
	if ($filesize!=NULL)
    	header("Content-length: ".$filesize);
    header("Connection: close");
    header( "Content-Disposition: attachment; filename=\"".encodeQuote($filename)."\"");
}
function getDependencyTypesArray($rs){
	if ($rs){
		$depTypes=array();
		while($row = $rs->FetchRow()){
			$depTypes[$row["Denomination"]]=$row;
			$depTypes[$row["Denomination"]]["SECURITY"]="W";
		}
		
		return $depTypes;
	}else
		return array();
}
function hasTypedLinks($moduleOriginID,$originID){
	$db_conn = db_connect();
	$typesSet = new DependencyTypeSet(false,$moduleOriginID);
	$typesVector = $typesSet->getTypes();
	// if no dependency types, no need to check
	if($typesVector->size()==0)
		return false;
	
	while($depType = $typesVector->next()){
		$sql = "SELECT `".$depType->getOriginFieldName()."` FROM `".$depType->getTableName()."` WHERE `".$depType->getTargetFieldName()."`='$originID' AND `DependencyTypeID` = '".$depType->getIDInDatabase()."' ORDER BY `DependencyTypeID` LIMIT 0,1;";
		sql_log($sql);
	    $row = $db_conn->getRow($sql);
		if($row){
			return true;
		}
	}
    return false;
}

function startswith($str,$start){
	if(substr($str,0,strlen($start))==$start){
		return true;
	}
	return false;
}

include_once(dirname(__FILE__)."/../common/constants.inc.php");
include_once(dirname(__FILE__)."/../common/encoding_functions.inc.php");
// ----------------------
// Config files : can be placed at several places
// ----------------------
if (file_exists(dirname(__FILE__)."/../common/config.inc.php"))
	include_once(dirname(__FILE__)."/../common/config.inc.php");
if (file_exists(dirname(__FILE__)."/../../config.inc.php")) // config at the root of the website
	include_once(dirname(__FILE__)."/../../config.inc.php");
if (file_exists(dirname(__FILE__)."/../common/db_config.inc.php"))
	include_once(dirname(__FILE__)."/../common/db_config.inc.php");
if (file_exists(dirname(__FILE__)."/../../db_config.inc.php")) // database config at the root of the website
	include_once(dirname(__FILE__)."/../../db_config.inc.php");
if (file_exists(dirname(__FILE__)."/../../sushee.conf.php")) // new configuration format : one file at the root
	include_once(dirname(__FILE__)."/../../sushee.conf.php");
// ----------------------
// Security config handling
// ----------------------
if($GLOBALS['CookieHttpOnly']){
	ini_set("session.cookie_httponly", 1);
}
if($GLOBALS['CookieSecure']){
	ini_set('session.cookie_secure',true);
}
// ----------------------
// Library files
// ----------------------
require_once(dirname(__FILE__)."/../common/db_functions.inc.php");
require_once(dirname(__FILE__)."/../file/file_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/XML.class.php");
require_once(dirname(__FILE__)."/../common/XML2.class.php");
require_once(dirname(__FILE__)."/../common/module.class.php");
require_once(dirname(__FILE__)."/../common/dependency.class.php");
require_once(dirname(__FILE__)."/../common/omnilinktype.class.php");
include_once(dirname(__FILE__)."/../common/useful_vars.inc.php");
include_once(dirname(__FILE__)."/../common/image_functions.inc.php");
// ----------------------
// User config file
// ----------------------
if (file_exists($GLOBALS["Public_dir"]."config.inc.php"))
	include_once($GLOBALS["Public_dir"]."config.inc.php");
