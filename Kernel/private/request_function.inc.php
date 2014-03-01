<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/request_function.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/common_functions.inc.php");
require_once(dirname(__FILE__)."/../common/nectil_user.class.php");

function request($stringofXML/* can be an object too */ ,$no_response_node = false,$supp_params = false,$include_navigation=false,$forced_language=false,$restrict_language=false,$priority_language=false,$php_request=false,$public=false){
	
	//------------------------------------------------
	// DETERMINING THE BASIC SECURITY
	//------------------------------------------------

	$sess = &$_SESSION[$GLOBALS["nectil_url"]];

	// saving the request main parameters
	$GLOBALS["restrict_language"] = $restrict_language;
	$GLOBALS["dev_request"] = $public;
	$GLOBALS["php_request"] = $php_request;
	$GLOBALS["priority_language"] = $priority_language;

	// if language has changed since it was first saved in the session at the first request
	if (isset($_GET['language']) && $_GET['language']!='' && $_GET['language']!=$GLOBALS["NectilLanguage"] && $_GET["language"]!='shared'){
		$GLOBALS["NectilLanguage"] = $sess["language"] = $_GET['language'];
	}

	if ($GLOBALS['php_request']!==true)
		@set_time_limit(300);

	$lifeTimes = $GLOBALS['cache_Lifetimes'];

	// logging the request
	if (is_object($stringofXML)){
		query_log($stringofXML->toString());
	}else{
		query_log($stringofXML);
	}

	// if its already an object, using it directly
	if (is_object($stringofXML))
	{
		$xml = $stringofXML;
	}
	else
	{
		$stringofXML = trim($stringofXML);
		$stringofXML = utf8_decode(utf8_To_UnicodeEntities($stringofXML));
		
		//special patch used for the "SAFARI" bug
		if ( $stringOfXML!="" && substr($stringofXML,-1) != ">" )
		{
		    $stringofXML.=">";
		}

		// building a tree with the xmlString
		$xml = new XML($stringofXML);
	}
	// free the memory used by the string representation of the XML
	unset($stringofXML);
	$stringofXML = null;
	
	if (!$xml->loaded){
		$strResponse='<MESSAGE msgType="1">Invalid XML Message</MESSAGE>';
	}else{
		//------------------------------------------------
		// SUSHEE IS USED AS A PROXY FOR ANOTHER SUSHEE
		//------------------------------------------------
		
		// this sushee is used as a proxy for another one, all requests are sent to the other one and returned as is
		if(Sushee_Instance::isSlave()){
			require_once(dirname(__FILE__)."/../common/url.class.php");
			
			// to have url params given in the /RESPONSE/URL
			foreach($_GET as $key=>$value){
				$url_params.='&'.$key.'='.$value;
			}
			
			$urlHandler = new URL(Sushee_Instance::getMasterURL().'private/request.php?PHPSESSID='.Sushee_Instance::getSlaveSessionID().'&SusheeReturnSuppParams=true'.$url_params);
			$urlHandler->setMethod('post');
			$urlHandler->setBody($xml->toString());
			$urlHandler->addHeader('Content-Type','text/xml;charset=utf-8');
			
			$strRet = $urlHandler->execute();
			response_log($strRet);
			return $strRet;
			
		}
		//------------------------------------------------
		// PREPARING THE HEADERS : NECTIL AND URL PARAMS
		//------------------------------------------------
		
		// checking the user is authentified/connected
		$user = new NectilUser();
		if ( $user->isAuthentified() ){
			$private_request = TRUE;
		}else
			$private_request = FALSE;
		
		// a language was forced in the request
		if ($forced_language===false)
			$forced_language = $xml->valueOf('/*[1]/@languageID');
		if ($forced_language){
			$save_language = $GLOBALS["NectilLanguage"];
			$save_restrict = $GLOBALS['restrict_language'];
			$GLOBALS['restrict_language'] = true;
			$GLOBALS["NectilLanguage"] = $forced_language;
		}
		
		// -- query given through the php function "query" --
		if ($supp_params===true || $_GET['SusheeReturnSuppParams']==='true')
		{
			// -- a piece of xml with all parameters in the url --
			if (sizeof($_GET)>0){
				$strResponse.='<URL>';
				$transformer = new sushee_PHPObjects2XML();
				$strResponse.=$transformer->execute($_GET);
				$strResponse.='</URL>';
			}
			
			// Useful variables for the developers
			$request = new Sushee_Request();
			$sushee = new Sushee_Instance();
			
			$nectil_vars = array();
			$nectil_vars['env'] = $request->getEnvironment();
			$nectil_vars['cache'] = $sess['cache'];
			$nectil_vars['language'] = $request->getLanguage();
			$nectil_vars['this_script'] = $request->getScript();
			if ($GLOBALS['resident_name'])
				$nectil_vars['resident_name'] = $GLOBALS['resident_name'];
			if ($user->getID()){
				$nectil_vars['logged'] = 'true';
			}else{
				$nectil_vars['logged'] = 'false';
			}
			$nectil_vars['nectil_url'] = $sushee->getUrl();
			$nectil_vars['files_url'] = $sushee->getFilesURL();
			$nectil_vars['public_url'] = $sushee->getPublicURL();
			$nectil_vars['kernel_url'] = $sushee->getKernelURL();
			$nectil_vars['host'] = $sushee->getHost();
		
			if (isset($GLOBALS['original_url']))
				$nectil_vars['original_url'] = $GLOBALS['original_url'];
			$nectil_vars['this_url'] = $_SERVER['REQUEST_URI'];
			
			// building an url to change the language of the page
			$nectil_vars["language_url"] = $nectil_vars["this_url"];
			$nectil_vars["language_url"] = str_replace(array("cache=false&","cache=false"),"",$nectil_vars["language_url"]);
			$nectil_vars["language_url"] = preg_replace ( '/&language=.[^&]*/i', '', $nectil_vars["language_url"]);
			$nectil_vars["language_url"] = preg_replace ( '/\?language=.[^&]*$/i', '?', $nectil_vars["language_url"]);
			$nectil_vars["language_url"] = preg_replace ( '/\?language=.[^&]*&/i', '?', $nectil_vars["language_url"]);
			
			if (strpos($nectil_vars["language_url"],'?')===FALSE){
				$nectil_vars["language_url"] = $nectil_vars["language_url"].'?';
			}else if (substr($nectil_vars["language_url"],-1)!='&' && substr($nectil_vars["language_url"],-1)!='?'){
				$nectil_vars["language_url"] = $nectil_vars["language_url"].'&';
			}
			
			// composing the piece of XML
			$strResponse.='<NECTIL>';
			foreach($nectil_vars as $param_name=>$param_value){
				$strResponse.='<'.$param_name.'>'.encode_to_XML($param_value).'</'.$param_name.'>';
			}
			$strResponse.='</NECTIL>';
		}
		if ($GLOBALS['php_request']===true)
		{
			$public_request = TRUE;
			// including the params in the url
			if ($include_navigation){
				// now including the navigation
				$navigation_file = getcwd().'/navigation.php';
				if (!file_exists($navigation_file))
					$navigation_file = getcwd().'/navigation.xml';
				else{
					$navigation_file = include($navigation_file);
					$included_nav=true;
				}
				if ( file_exists($navigation_file) || $included_nav)
				{
					$navigation_xml = new XML($navigation_file);
					if ($navigation_xml->loaded){
						$sub_queries_array = $navigation_xml->match('/QUERY/*');
						$navigation_xml->setAttribute('/QUERY/*', 'fromFile', 'navigation.xml');
						if (!$xml->match('/QUERY/*[1]')){
							foreach($sub_queries_array as $path){
								$xml->appendChild('/QUERY',$navigation_xml->toString($path),FALSE,FALSE);
							}
							$xml->reindexNodeTree();
						}else{
							for($i=sizeof($sub_queries_array);$i>=0;$i--){
								$path = $sub_queries_array[$i];
								$xml->insertBefore('/QUERY/*[1]',$navigation_xml->toString($path),TRUE,FALSE);
							}
							$xml->reindexNodeTree();
						}

					}
				}
			}
		}
		else
		{
			$public_request = FALSE;
		}

		$db_conn = db_connect();
		
		// -- get all queries --
		$queries_array = $xml->match("/QUERY[1]/*");
		
		// creating the cache dir if it doesnt exist
		$cache_dir = $GLOBALS["nectil_dir"].'/Files/cache/xsushee/';
		if(!file_exists($cache_dir)){
			makeDir($cache_dir);
		}

		//------------------------------------------------
		// HANDLING THE DIFFERENT COMMANDS
		//------------------------------------------------

		Sushee_Timer::lap('Start Queries');

		foreach($queries_array as $current_path)
		{
			$static = $xml->getData($current_path.'/@static');

			// -- static piece of xml --
			if ($static==='true')
			{
				$query_result = $xml->toString($current_path,'',false);
				$query_result = str_replace(array("\r\n","\r","\n"),'',$query_result);
			}
			else
			{
				// -- real xsushee commands --
				$refresh = $xml->getData($current_path.'/@refresh');
				$name = $xml->getData($current_path.'/@name');

				$requestName = strtoupper($xml->nodeName($current_path));
				$firstNode = strtoupper($xml->nodeName($current_path.'/*[1]'));

				$query_result ="";
				$cached = false;
				$must_get_cached = false;

				// -- cache handling --
				if ($refresh!==FALSE && $refresh!=='live' && $_GET['cache']!=='false' && array_key_exists($refresh,$lifeTimes))
				{
					
					$lifeTime = $lifeTimes[$refresh][0];
					$options = array('cacheDir' => $cache_dir,'lifeTime' => $lifeTime );
				
					include_once(dirname(__FILE__)."/../common/Cache/Lite.php");
					
					$Cache_Lite = new Cache_Lite($options);
					$xml->removeAttribute($current_path, 'refresh');
					$stringofSubquery = $xml->toString($current_path);
					$id = generateID(array(str_replace(array("\r\n","\r","\n","\t"),'',$stringofSubquery),$restrict_language,$public,$GLOBALS["NectilLanguage"],$GLOBALS["priority_language"]));
					$cache_section = 'subquery';
					if ($firstNode == 'TRADUCTION')
					{
						$cache_section = 'os_trads';
					}

					if ( $_GET['cache']!=='refresh' && ($data = $Cache_Lite->get($id,$cache_section)) )
					{
						$query_result = $data;
						$cached = true;
					}
					else
					{
						$must_get_cached = true;
					}
				}
			
				if ( !$cached || $firstNode == 'TRADUCTION' )
				{
					$firstNodePath = $current_path.'/*[1]';
					$requestElementID=$xml->getData($firstNodePath.'/@ID');
					$mustconfirm=$xml->getData($current_path.'/@confirm');
					// we save the number of queries at that moment
					$nb_queries= $GLOBALS["EXECS"];
			
					if ( 
						$mustconfirm===false 
						&& 
						(		$requestName == 'CONNECT' || $public_request || $private_request 
								|| 
								( 
									($requestName == "GET" || $requestName == "SEARCH") 
									&&  
									($firstNode == "DESCRIPTIONCONFIG" || $firstNode == "LANGUAGES" || $firstNode == "COMMENTSCONFIG" || $firstNode == "TRADUCTION" || $firstNode == "COUNTRIES" || $firstNode == "MONTHS" || $firstNode == "DAYS" || $firstNode == "CATEGORIES" || $firstNode == 'LABELS' || $firstNode == 'LIST')
								 )
						) 
						){
						//------------------------------------	
						// <SEARCH><...>
						//------------------------------------	
						if ($requestName == 'SEARCH' || $requestName == 'GET' || $requestName == 'GETCHILDREN' || $requestName == 'GETPARENT' || $requestName == 'GETANCESTOR' || $requestName == 'COUNT'){
							
							if( $firstNode == 'MEDIA' ){
								
								include_once dirname(__FILE__)."/../private/search.inc.php";
								$nqlOp = new searchElement($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'SECURITY' ){
								
								include_once(dirname(__FILE__)."/../private/getSecurity.inc.php");
								$nqlOp = new getSecurity($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'VISITOR' ){
								
								include_once(dirname(__FILE__)."/../private/getVisitor.inc.php");
								$nqlOp = new getVisitor($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'LOGDEV' ){
								
								$contains_logdev_command = true;
								include_once dirname(__FILE__)."/../private/SearchLogFile.php";
								$nqlOp = new searchLogFile($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'DEPTYPE' || $firstNode == 'DEPTYPES' || $firstNode == 'DEPENDENCY_TYPES' || $firstNode == 'DEPENDENCYENTITY' ){
								
								include_once dirname(__FILE__)."/../private/searchDeptype.inc.php";
								$nqlOp = new searchDeptype($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'MEDIATYPE' || $firstNode == 'MEDIATYPES' ){
								
								include_once dirname(__FILE__)."/../private/searchMediatype.inc.php";
								$query_result = searchMediatype($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if( $firstNode == 'CONNECTED' ){
								
								include_once dirname(__FILE__)."/../private/searchConnected.inc.php";
								$query_result = searchConnected($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if( $firstNode == 'LANGUAGES' ){ 
								
								include_once dirname(__FILE__)."/../private/languages.inc.php";
								$nqlOp = new Sushee_SearchLanguages($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'COUNTRY' ||  $firstNode == 'COUNTRIES' ){
								
								include_once dirname(__FILE__)."/../private/countries.inc.php";
								$nqlOp = new searchCountries($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'TRADUCTION' ){ 
								
								include_once dirname(__FILE__)."/../private/searchTraductions.inc.php";
								$nqlOp = new searchTraductions($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'MAILING_GROUP' ){ 
								
								include_once dirname(__FILE__)."/../private/mailing.inc.php";
								$query_result = searchMailingGroup($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if( $firstNode == 'APPLICATION' ){
								
								include_once dirname(__FILE__)."/../private/searchApplication.inc.php";
								$nqlOp = new searchApplication($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'CALENDAR' ){
								
								include_once dirname(__FILE__)."/../private/searchCalendar.inc.php";
								$nqlOp = new searchCalendar($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'CATEGORY' || $firstNode == 'CATEGORIES' ){
								
								include_once dirname(__FILE__)."/../private/searchCategories.inc.php";
								$nqlOp = new searchCategories($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'COMMENTSCONFIG' ){ 
								
								include_once dirname(__FILE__)."/../private/searchCommentsConfig.inc.php";
								$query_result = searchCommentsConfig($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if( $firstNode == 'COOKIES' || $firstNode == 'COOKIE' ){
								
								include_once dirname(__FILE__)."/../private/getcookies.inc.php";
								$nqlOp = new sushee_getCookies($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'DESCRIPTIONCONFIG' ){ 
								
								include_once dirname(__FILE__)."/../private/searchDescConfig.inc.php";
								$nqlOp = new searchDescriptionConfig($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'DESCRIPTION' ){
								
								include_once dirname(__FILE__)."/../private/searchDescription.inc.php";
								$nqlOp = new searchDescription($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'ENUM' ){
								
								include_once dirname(__FILE__)."/../private/getenum.inc.php";
								$nqlOp = new getEnum($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'IMAGE' ){
								
								include_once dirname(__FILE__)."/../private/getimage.inc.php";
								$nqlOp = new getImage($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'LABEL' || $firstNode == 'LABELS' ){
								
								include_once dirname(__FILE__)."/../private/searchLabels.inc.php";
								$nqlOp = new sushee_searchLabels($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'MAILING_RECIPIENTS' ){
								
								include_once dirname(__FILE__)."/../private/searchMailingRecipients.inc.php";
								$query_result = searchMailingRecipients($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if( $firstNode == 'MONTHS' || $firstNode == 'MONTH' ){
								
								include_once dirname(__FILE__)."/../private/getmonths.inc.php";
								$nqlOp = new getMonths($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'DAYS' || $firstNode == 'DAY' || $firstNode == 'WEEKDAY' ){
								
								include_once dirname(__FILE__)."/../private/getdays.inc.php";
								$nqlOp = new getDays($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'LISTS' || $firstNode == 'LIST' ){
								
								include_once dirname(__FILE__)."/../private/searchLists.inc.php";
								$nqlOp = new searchLists($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'PREFS' ){
								
								include_once dirname(__FILE__)."/../private/searchPrefs.inc.php";
								$query_result = searchPrefs($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if( $firstNode == 'WEBSERVICE' ){
								
								include_once dirname(__FILE__)."/../private/searchWebservice.inc.php";
								$nqlOp = new searchWebservice($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'MODULES' || $firstNode == 'MODULE' ){
								
								include_once dirname(__FILE__)."/../private/searchModule.inc.php";
								$nqlOp = new searchModule($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'APPLICATIONS' || $firstNode == 'APPLICATION' ){
								
								include_once dirname(__FILE__)."/../private/searchApplication.inc.php";
								$nqlOp = new searchApplication($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'LOG' ){
								
								include_once dirname(__FILE__)."/../private/searchLog.inc.php";
								$nqlOp = new searchLog($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'TIME' || $firstNode == 'DATE' ){
								
								include_once dirname(__FILE__)."/../private/getTime.inc.php";
								$nqlOp = new Sushee_getTime($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'TEXT' ){
								
								include_once dirname(__FILE__)."/../private/getText.inc.php";
								$nqlOp = new Sushee_getText($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( $firstNode == 'LEXICON' ){
								
								include_once dirname(__FILE__)."/../private/getLexicon.inc.php";
								$nqlOp = new sushee_getLexicon($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if( moduleInfo($firstNode)->loaded ){
									
								include_once dirname(__FILE__)."/../private/search.inc.php";
								$nqlOp = new searchElement($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
									
							}else{
									
								include_once dirname(__FILE__)."/../private/executeCustomCmd.inc.php";
								$nqlOp = new sushee_executeCustomCommand($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
									
							}
							
						//------------------------------------	
						// <CREATE><...>
						//------------------------------------
						}else if ($requestName == "CREATE"){
							
							if ($firstNode == "DEPENDENCYENTITY"){
								
								include_once dirname(__FILE__)."/../private/createDeptype.inc.php";
								$nqlOp = new createDeptype($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "CATEGORY"){
								
								include_once dirname(__FILE__)."/../private/createCategory.inc.php";
								$query_result = createCategory($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == "COMMENT"){
								
								include_once dirname(__FILE__)."/../private/comments.nql.php";
								$nqlOp = new createComment($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "CONTACT" && $xml->getData($firstNodePath.'/@source')!==FALSE){
								
								include_once dirname(__FILE__)."/../private/import_vcard.inc.php";
								$query_result = importVCard($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == "SERVERMAIL"){
								
								include_once dirname(__FILE__)."/../private/createMail.inc.php";
								$nqlOp = new createServerMail($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "PDF"){
								
								include_once dirname(__FILE__)."/../private/createPDF.inc.php";
								$query_result = createPDF($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if($firstNode == 'RTF'){
								
								include_once dirname(__FILE__)."/../private/createRTF.inc.php";
								$nqlOp = new sushee_createRTF($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "ARCHIVE"){
								
								include_once dirname(__FILE__)."/../private/createArchive.inc.php";
								$nqlOp = new createArchive($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "XLIFF"){
								
								include_once dirname(__FILE__)."/../private/createXLIFF.inc.php";
								$query_result = createXLIFF($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == "LABEL"){
								
								include_once dirname(__FILE__)."/../private/updateLabels.inc.php";
								$nqlOp = new sushee_updateLabel($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "CSV"){
								
								include_once dirname(__FILE__)."/../private/createCSV.inc.php";
								$nqlOp = new createCSV($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'CAPTCHA'){
								
								include_once dirname(__FILE__)."/../private/createCaptcha.inc.php";
								$nqlOp = new createCaptcha($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'RESIDENT-LICENSE'){
								
								include_once dirname(__FILE__)."/../private/createResidentLicense.inc.php";
								$nqlOp = new createResidentLicense($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'VCALENDAR'){
								
								include_once dirname(__FILE__)."/../private/createVCal.inc.php";
								$nqlOp = new createVCal($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'APPLICATION'){
								
								include_once dirname(__FILE__)."/../private/createApplication.inc.php";
								$nqlOp = new createApplication($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'FIELD'){
								
								include_once dirname(__FILE__)."/../private/createField.inc.php";
								$nqlOp = new sushee_createField($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($firstNode == 'NAMESPACE'){
								
								include_once dirname(__FILE__)."/../private/createNamespace.inc.php";
								$nqlOp = new sushee_createNamespace($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($firstNode == 'OMNILINKTYPE'){
								
								include_once dirname(__FILE__)."/../private/createOmnilinktype.inc.php";
								$nqlOp = new sushee_createOmnilinkstype($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($firstNode == 'TEXT'){
								
								include_once dirname(__FILE__)."/../private/createText.inc.php";
								$nqlOp = new Sushee_createText($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($firstNode == 'LEXICON'){
								
								include_once dirname(__FILE__)."/../private/createLexicon.inc.php";
								$nqlOp = new Sushee_createLexicon($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($firstNode == 'LIST'){
								
								include_once dirname(__FILE__)."/../private/createList.inc.php";
								$nqlOp = new Sushee_createList($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if(moduleInfo($firstNode)->loaded){
								
								include_once dirname(__FILE__)."/../private/create.nql.php";
								$nqlOp = new createElement($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else{
								
								include_once dirname(__FILE__)."/../private/executeCustomCmd.inc.php";
								$nqlOp = new sushee_executeCustomCommand($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}
						//------------------------------------	
						// <DELETE/KILL><...>
						//------------------------------------
						}else if ($requestName == "DELETE"){
							
							if ($firstNode == "DEPENDENCYENTITY"){
								
								include_once dirname(__FILE__)."/../private/deleteDeptype.inc.php";
								$nqlOp = new deleteDeptype($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "CATEGORY"){
								
								include_once dirname(__FILE__)."/../private/deleteCategory.inc.php";
								$query_result = deleteCategory($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == "COMMENT"){
								
								include_once dirname(__FILE__)."/../private/comments.nql.php";
								$nqlOp = new deleteComment($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'MODULE'){
								
								include_once dirname(__FILE__)."/../private/deleteModule.inc.php";
								$nqlOp = new deleteModule($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'APPLICATION'){
								
								include_once dirname(__FILE__)."/../private/deleteApplication.inc.php";
								$nqlOp = new deleteApplication($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($firstNode == 'LABEL'){
								
								include_once dirname(__FILE__)."/../private/deleteLabel.inc.php";
								$nqlOp = new sushee_deleteLabel($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if(moduleInfo($firstNode)->loaded){
								
								include_once dirname(__FILE__)."/../private/delete.inc.php";
								$nqlOp = new deleteElement($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else{
								
								include_once dirname(__FILE__)."/../private/executeCustomCmd.inc.php";
								$nqlOp = new sushee_executeCustomCommand($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}
						}else if ($requestName == "KILL"){
							
							if ($firstNode == 'MODULE'){
								
								include_once dirname(__FILE__)."/../private/deleteModule.inc.php";
								$nqlOp = new deleteModule($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if(moduleInfo($firstNode)->loaded){
								
								include_once dirname(__FILE__)."/../private/delete.inc.php";
								$nqlOp = new deleteElement($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else{
								
								include_once dirname(__FILE__)."/../private/executeCustomCmd.inc.php";
								$nqlOp = new sushee_executeCustomCommand($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}
						//------------------------------------	
						// <UPDATE><...>
						//------------------------------------
						}else if ($requestName == "UPDATE"){
							
							if ($firstNode == "DEPENDENCYENTITY"){
								
								include_once dirname(__FILE__)."/../private/updateDeptype.inc.php";
								$nqlOp = new updateDepType($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "DESCRIPTIONCONFIG"){
								
								include_once dirname(__FILE__)."/../private/updateDescConfig.inc.php";
								$query_result = updateDescConfig($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == "FIELD"){
								
								include_once dirname(__FILE__)."/../private/updateField.inc.php";
								$nqlOp = new sushee_updateField($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "MEDIATYPE"){
								
								include_once dirname(__FILE__)."/../private/createMediatype.inc.php";
								$nqlOp = new createMediatype($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "CATEGORIES"){
								
								include_once dirname(__FILE__)."/../private/updateCategories.inc.php";
								$nqlOp = new sushee_updateCategories($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "MEDIALANGUAGES" && !$public_request){
								
								include_once dirname(__FILE__)."/../private/updateMedialanguages.inc.php";
								$query_result = updateMedialanguages($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == "CATEGORY"){
								
								include_once dirname(__FILE__)."/../private/updateCategory.inc.php";
								$nqlOp = new updateCategory($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "COMMENT"){
								
								include_once dirname(__FILE__)."/../private/comments.nql.php";
								$nqlOp = new updateComment($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == "PREFS"){
								
								include_once dirname(__FILE__)."/../private/updatePrefs.inc.php";
								$query_result = updatePrefs($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == "LABEL"){
								
								include_once dirname(__FILE__)."/../private/updateLabels.inc.php";
								$nqlOp = new sushee_updateLabel($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'MODULE'){
								
								include_once dirname(__FILE__)."/../private/updateModule.inc.php";
								$nqlOp = new updateModule($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'APPLICATION'){
								
								include_once dirname(__FILE__)."/../private/updateApplication.inc.php";
								$nqlOp = new updateApplication($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($firstNode == 'OMNILINKTYPE'){
								
								include_once dirname(__FILE__)."/../private/updateOmnilinktype.inc.php";
								$nqlOp = new sushee_updateOmnilinktype($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($requestName == 'UPDATE' && $firstNode == 'TEXT'){
								
								include_once dirname(__FILE__)."/../private/updateText.inc.php";
								$nqlOp = new Sushee_updateText($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($requestName == 'UPDATE' && $firstNode == 'LEXICON'){
								
								include_once dirname(__FILE__)."/../private/updateLexicon.inc.php";
								$nqlOp = new Sushee_updateLexicon($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if(moduleInfo($firstNode)->loaded){
								
								include_once dirname(__FILE__)."/../private/update.nql.php";
								$nqlOp = new updateElement($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else{
								
								include_once dirname(__FILE__)."/../private/executeCustomCmd.inc.php";
								$nqlOp = new sushee_executeCustomCommand($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}
						//------------------------------------	
						// OTHER COMMMANDS
						//------------------------------------
						}else if ($requestName == 'GETDESCENDANT'){
							
							include_once dirname(__FILE__)."/../private/getdescendant.inc.php";
							$nqlOp = new getDescendant($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if ($requestName == 'SKELETON'){
							
							include_once dirname(__FILE__)."/../private/skeleton.inc.php";
							$nqlOp = new Skeleton($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if ($requestName == "FEEDBACK"){
							
							include_once dirname(__FILE__)."/../private/feedback.inc.php";
							$nqlOp = new Sushee_createFeedback($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
						
						}else if ($requestName == 'CHECK' ){
							
							if ($firstNode == 'MAILING'){
								
								include_once(dirname(__FILE__)."/../private/check_mailing.inc.php");
								$query_result = check_mailing($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == 'MAILSACCOUNT'){
								
								include_once(dirname(__FILE__)."/../private/check_mailsaccount.inc.php");
								$nqlOp = new checkMailsAccount($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else{
								
								include_once dirname(__FILE__)."/../private/executeCustomCmd.inc.php";
								$nqlOp = new sushee_executeCustomCommand($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}
						}else if ($requestName == 'SEND' && $firstNode == 'MAILINGPREVIEW' ){
							
							include_once dirname(__FILE__)."/../private/sendMailingPreview.inc.php";
							$query_result = sendMailingPreview($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
							
						}else if ($requestName == 'INCLUDE'){
							
							include_once dirname(__FILE__)."/../private/includeFile.inc.php";
							$nqlOp = new sushee_includeFile($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
								
						}else if ($requestName == 'IMPORT'){
							
							if ($firstNode == 'CSV'){
								
								include_once dirname(__FILE__)."/../private/importCSV.inc.php";
								$nqlOp = new importCSV($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if ($firstNode == 'XLIFF'){
								
								include_once dirname(__FILE__)."/../private/importXLIFF.inc.php";
								$query_result = importXLIFF($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
								
							}else if ($firstNode == 'ICAL'){
								
								include_once dirname(__FILE__)."/../private/importICal.inc.php";
								$nqlOp = new importICal($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else if($firstNode == 'APP'){
								
								include_once dirname(__FILE__)."/../private/importApp.inc.php";
								$nqlOp = new sushee_importApp($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}else{
								
								include_once dirname(__FILE__)."/../private/executeCustomCmd.inc.php";
								$nqlOp = new sushee_executeCustomCommand($name,new XMLNode($xml,$current_path));
								$query_result = $nqlOp->execute();
								
							}
						}else if ($requestName == 'EXPORT' && $firstNode == 'OFFICITY'){
							
							include_once(dirname(__FILE__)."/../private/exportOfficity.inc.php");
							$nqlOp = new exportOfficity($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'EXPORT' && $firstNode == 'APP'){
							
							include_once(dirname(__FILE__)."/../private/exportApp.inc.php");
							$nqlOp = new sushee_exportApp($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'DOWNLOAD' && $firstNode == 'FILE'){
							
							include_once(dirname(__FILE__)."/../private/downloadFile.inc.php");
							$nqlOp = new sushee_downloadFile($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if ($requestName == 'FILEORDIRECTORY'){
							
							include_once dirname(__FILE__)."/../file/file_request.inc.php";
							$query_result = filerequest($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
							
						}else if ($requestName == 'CONFIRM'){
							
							include_once dirname(__FILE__)."/../private/confirmOperation.inc.php";
							$nqlOp = new sushee_confirmOperation($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if ($requestName == 'DUPLICATE'){
							
							include_once dirname(__FILE__)."/../private/duplicate.inc.php";
							$query_result = duplicate($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
							
						}else if ($requestName == 'SAVE' && $firstNode == 'MAIL'){
							
							include_once dirname(__FILE__)."/../private/saveMail.inc.php";
							$query_result = saveMail($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
							
						}else if ($requestName == 'UNSUSCRIBE' && $firstNode == 'CONTACT'){
							
							include_once dirname(__FILE__)."/../private/unsuscribeContact.inc.php";
							$query_result = unsuscribeContact($name,$xml,$requestName,$current_path,$firstNode,$firstNodePath);
							
						}else if ($requestName == 'PROCESS' && $firstNode == 'MOVIE'){
							
							include_once dirname(__FILE__)."/../private/processMovie.inc.php";
							$nqlOp = new processMovie($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if ($requestName == 'CONNECT' && $firstNode == 'LOGIN'){
							
							include_once dirname(__FILE__)."/../private/connect.inc.php";
							$nqlOp = new SusheeConnectOperation($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							$res = $nqlOp->getOperationSuccess();
						
							// might change the handling of the rest of the request, if user is now connected
							if ( $res ){
								$private_request = TRUE;
							}else{
								$private_request = FALSE;
							}
							
						}else if ($requestName == 'DISCONNECT' && !$firstNode){
							
							include_once dirname(__FILE__)."/../private/connect.inc.php";
							$nqlOp = new SusheeDisconnectOperation($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
						
							// not connected anymore, not authorized to make private NQL commands anymore
							$private_request = FALSE;
						
						}else if ($requestName == 'ANALYSE' && $firstNode == 'CSV' ){
							
							include_once dirname(__FILE__)."/../private/analyseCSV.inc.php";
							$nqlOp = new analyseCSV($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if ($requestName == 'TRANSLATE' && $firstNode == 'STRING'){
							
							include_once dirname(__FILE__)."/../private/translateString.inc.php";;
							$nqlOp = new sushee_translateString($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'EMPTY' && $firstNode == 'CACHE'){
							
							include_once dirname(__FILE__)."/../private/emptyCache.inc.php";
							$nqlOp = new sushee_emptyCache($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'TRANSFORM' && $firstNode == 'QUERY'){
							
							include_once dirname(__FILE__)."/../private/transformQuery.inc.php";
							$nqlOp = new sushee_transformQuery($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if(($requestName == 'FIRST' || $requestName == 'LAST') && $firstNode ){
							
							include_once dirname(__FILE__)."/../private/first_last.inc.php";
							$nqlOp = new sushee_first_Last_element($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'BACKUP' && $firstNode == 'MODULE'){
							
							include_once dirname(__FILE__)."/../private/backupModule.inc.php";
							$nqlOp = new sushee_backupModule($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'DISTINCT' && moduleInfo($firstNode)->loaded){
							
							include_once dirname(__FILE__)."/../private/distinctField.inc.php";
							$nqlOp = new sushee_distinctField($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'AUTHORIZE' && $firstNode == 'WEBACCOUNT'){
							
							include_once dirname(__FILE__)."/../private/authorizeWebaccount.inc.php";
							$nqlOp = new Sushee_authorizeWebAccount($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'DISCONNECT' && $firstNode == 'USER'){
							
							include_once dirname(__FILE__)."/../private/disconnectUser.inc.php";
							$nqlOp = new Sushee_disconnectUser($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'TRY' && $firstNode == 'QUERY'){
							
							include_once dirname(__FILE__)."/../private/tryQuery.inc.php";
							$nqlOp = new Sushee_tryCommands($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else if($requestName == 'CALL' && $firstNode == 'WEBACCOUNT'){
							
							include_once dirname(__FILE__)."/../private/callWebaccount.inc.php";
							$nqlOp = new Sushee_callWebAccount($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
							
						}else{
							
							include_once dirname(__FILE__)."/../private/executeCustomCmd.inc.php";
							$nqlOp = new sushee_executeCustomCommand($name,new XMLNode($xml,$current_path));
							$query_result = $nqlOp->execute();
						}
					
				
					}else if ($mustconfirm!==false){
					
						// Action that needs confirmation of the user
						if ($mustconfirm!=='true'){
							$confirm_id = $mustconfirm;
						}else{
							$confirm_id = str_replace(array('.',' '),'',microtime());
						}
						include_once dirname(__FILE__)."/../common/file.class.php";
						$xml->removeAttribute($current_path, 'confirm');
						$mustconfirm_operation = $xml->toString($current_path,'');
						$confirm_dir = new Folder('/confirm/');
						$confirm_dir->create();
						$confirm_file = new File('/confirm/subquery_'.$confirm_id.'.xml');
						if ($confirm_file->exists()){
							$current_content = $confirm_file->toString();
							$new_content = str_replace('</QUERY>',$mustconfirm_operation.'</QUERY>',$current_content);
							$confirm_file->save($new_content);
						}else{
							$confirm_file->save('<?xml version="1.0"?><QUERY>'.$mustconfirm_operation.'</QUERY>');
						}
						$query_result = generateMsgXML(4,"Operation was postponed until further user confirmation",0,$confirm_id,$name,'','','confirm_url="'.$GLOBALS["backoffice_url"].'public/confirm.php?ID='.$confirm_id.'"');
					
					}else{
						$query_result = generateMsgXML(3,"Operation unauthorized : your session must have expired, try to login again.",0,'',$name);
					}
				}
			
				// -- logging because the user is authentified and we want to know the time of last action --
				if (!$public)
				{
					// -- from a user, not from a public page (website, etc) --
					$loginObject = $user->getLoginObject();
					if ($loginObject && !$loginObject->isSaved())
					{
						// -- saving time of last action --
						$loginObject->save();
					}
				}

				// -- saving in cache --
				if ($must_get_cached===TRUE)
				{
					$Cache_Lite->save($query_result,$id,$cache_section);
				}
							
				Sushee_Timer::lap($requestName.' on '.$firstNode);
			}

			$strResponse .= $query_result;
			
			unset($query_result);
		}

		unset($queries_array);
		if ($forced_language)
		{
			$GLOBALS["NectilLanguage"] = $save_language;
			$GLOBALS["restrict_language"] = $save_restrict;
		}
	}

	if ($no_response_node===true)
	{
		$strRet = $strResponse;
	}
	else
	{
		// enclosing the results of the differents requests in a unique node
		// handling the namespaces necessary for the external objects

		require_once(dirname(__FILE__)."/../common/namespace.class.php");

		$namespaces = new NamespaceCollection();
		$namespaces_str = $namespaces->getXMLHeader();

		if ($GLOBALS['no_variable_nectil_vars']!==true && !$GLOBALS["php_request"])
		{
			$strRet = SUSHEE_XML_HEADER.'<RESPONSE'.$namespaces_str.' userID="'.$sess['SESSIONuserID'].'" sessionID="'.session_id().'">'.$strResponse;
		}
		else
		{
			$strRet = SUSHEE_XML_HEADER.'<RESPONSE'.$namespaces_str.'>'.$strResponse;
		}

		// -- adding some stats --
		if (!$GLOBALS["php_request"] || $_GET['stats'] === 'true' || $GLOBALS['sushee_stats'] === true)
		{
			$strRet.="<SQL_STATS><TOTALQUERIES>".($GLOBALS["EXECS"]+$GLOBALS["CACHED"])."</TOTALQUERIES><SearchQUERIES>".$GLOBALS["SearchQUERIES"]."</SearchQUERIES></SQL_STATS>";
			$strRet.="<TotalNectilElements>".$GLOBALS["TotalNectilElements"]."</TotalNectilElements>";
			$strRet.='<TIME>'.getTimer('stats').'</TIME>';
			$strRet.='<TIMER>'.Sushee_Timer::toXML().'</TIMER>';
		}

		$strRet .= "</RESPONSE>";
	}

	// -- logging the xml --
	if ($contains_logdev_command === true)
	{
		// do nothing
	}
	else
	{
		response_log($strRet);
	}

	// logging the task timers if any was configured
	$timers = Sushee_TaskTimers::toString();
	if($timers)
	{
		debug_log($timers);
	}

	return $strRet;
}