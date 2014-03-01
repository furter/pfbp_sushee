<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/XML2.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");

define('XML_OPTION_SKIP_WHITE',1);

class XML extends SusheeObject{
	var $simpleXML;
	var $_lastError;
	var $loaded = false;
	var $parseOptions =array();
	var $entities = true;
	
	//Create a new object XML2 from a valide xml string
	function XML($str=false){
		if(substr($str,-4)=='.xml'){
			return $this->importFromFile($str);
		}
		if($str){
			// automatically adding the namespaces registered in the database to avoid any namespace omission
			$str = $this->addNamespace($str);
			try{
				$this->simpleXML = new SimpleXMLElement($str);
				$this->_lastError = '';
				$this->loaded = true;
			}catch( Exception $E){
				$this->_lastError = $E->getMessage();
				$this->loaded = false;
			}
			
		}else{
			$this->_lastError = "Your XML is empty";
			$this->loaded = false;
		}
	}
	
	
	function getLastError() {
    	return $this->_lastError;
  	}
  	
  	
	function reset() {
    	$this->_lastError   = '';
    	unset($this->simpleXML);
  	}
  	
  	
  	
  /*
   * Given a context this function returns the containing XML
   *
   * @param $absoluteXPath  (string) The address of the node you would like to export.
   *                                 If empty the whole document will be exported.
   * @param $xmlHeader      (array)  The string that you would like to appear before
   *                                 the XML content.  ie before the <root></root>.  If you
   *                                 do not specify this argument, the xmlHeader that was 
   *                                 found in the parsed xml file will be used instead. 
   * Return a well-formed XML string based on SimpleXML element
   */
	function exportAsXml($absoluteXPath= NULL,$xmlHeader=NULL){
		if(!is_object($this->simpleXML)){
			return false;
		}
		if($absoluteXPath == NULL){
			$result =  $this->simpleXML->asXML();
		}
		else{
			$targets = @$this->simpleXML->xpath($absoluteXPath);
			if($targets === false){
				errors_log('Invalid expression '.$absoluteXPath);
				$result = '';
			}else{
				foreach($targets as $node){
					$result .= $node->asXML();
				}
			}
			
				
		}
		return $result;
	}
	
	
	/*Retrieves the name of a node specified in the argument.  So if the argument was '/A[1]/B[2]' then it
   * would return 'B' if the node did exist in the tree.
	 * @param  $xPathQuery (mixed) document path of the node
	 * @return             (mixed)  string of the name of the specified node 
   *                             	 If the node did not exist, then returns FALSE.
	 */
	function nodeName($xpath){
		if(!is_object($this->simpleXML)){
			return false;
		}
		if($xpath == NULL or $xpath  =='/'){
			return $this->simpleXML->getName();
		}
		else{
			$nodes = @$this->simpleXML->xpath($xpath);
				if($nodes != false){
					$domNode = dom_import_simplexml($nodes[0]);
					$nodeName = $domNode->nodeName;
					
				}
				else{
					$nodeName = false;
				}
			return $nodeName;
		}
	}	
	
	
	
	/* Get the node defined by the $absoluteXPath.
   *
   * @param   $absoluteXPath (string) (optional, default is 'super-root') xpath to the node.
   * @return                 (array)  The node, or FALSE if the node wasn't found.
   */
 	function getNode($xpath){
 		if($xpath == NULL or $xpath == '/'){
 			$node = $this->simpleXML;
 		}
 		else{
 			$nodes = @$this->simpleXML->xpath($xpath);
 				if($nodes == false){
 					$node = false;
 				}
 				else{
 					$node = $nodes[0];
 				}
 		}
 		return $node;
 	}
 	
 	
 	
 	 /*
 	  * Convert the well-formed XML document in the given file to an simpleXMLElement object.
 	  */
 	function importFromFile($fileName){
 		if(!file_exists($fileName)){
 			$this->loaded = false;
 			$this->_lastError = 'File doesnt exist';
 			return false;
 		}

 		$xmlString = file_get_contents($fileName);
		
		if($xmlString){
			return $this->importFromString($this->addNamespace($xmlString));
		}
		else{
			return false;
		}
 	}
 	
	function addNamespace($str){
		//if(strpos($str,' xmlns:')===false){
			$pos_end_opening_tag = strpos($str,'>');
	  		require_once(dirname(__FILE__)."/../common/namespace.class.php");
			$namespaces = new NamespaceCollection();
			$namespaces_str = $namespaces->getXMLHeader();
		
	  		if($pos_end_opening_tag!==false){
				if($str[$pos_end_opening_tag-1]=='?'){
					// it is the ending of the XML header, looking for the next one
	  				$pos_end_opening_tag = strpos($str,'>',$pos_end_opening_tag+1);
	  			}
	  			if($str[$pos_end_opening_tag-1]=='/'){ // meaning its an empty node  : <node/>
	  				$pos_end_opening_tag--;
	  			}
				$namespace_presence = strrpos($str,' xmlns:', - strlen($str) + $pos_end_opening_tag);
				if($namespace_presence===false){ // if xml namespaces are not yet defined on this node
	  				$str = substr_replace($str,$namespaces_str,$pos_end_opening_tag,0);
				}
	  		}
		//}
		return $str;
	}
 	
 	 /*
 	  * Takes a well-formed XML string and returns it as an simpleXMLElement object.
 	  */
 	function importFromString($xmlString){
 		if($this->parseOptions[XML_OPTION_SKIP_WHITE]){
 			$xmlString = preg_replace('(>\s+<)','><',$xmlString);
 			//die($xmlString);
 		}
 		$success = simplexml_load_string($xmlString);
 		//die(var_dump($success));
 		if($success === false){
 			return false;
 		}
 		else{
 			$this->simpleXML = $success;
 			$this->loaded = true;
 			return true;	
 		}
 	}
 		
  	/*
   * Removes a node from the XML document.
   * @param  $xPathQuery  (string) xpath to the node
   * @return NO
  	 */
  	function removeChild($xPathQuery, $autoReindex=TRUE){
		if(!is_object($this->simpleXML)){
			return false;
		}
  		$nodesToRemove = @$this->simpleXML->xpath($xPathQuery);
  			foreach($nodesToRemove as $nodeToRemove){
  				$domNodeToRemove = dom_import_simplexml($nodeToRemove);
  				$domNodeToRemove->parentNode->removeChild($domNodeToRemove);
  				$this->isModified = true;
  			} 
  	}

  	
  	/*
  	 * Replace the node(s) that matches the xQuery with the passed node
  	 * @param  $xPathQuery  (string) Xpath to the node being replaced.
  	 * @param  $node        (mixed)  String or Array (Usually a String)
  	 * @return	NO
  	 */		
  	function replaceChild($xPathQuery, $replacement, $autoReindex=true){
		if(!is_object($this->simpleXML)){
			return false;
		}
  		$domReplacement = DOMDocument::loadXML($replacement);
  		$nodes = @$this->simpleXML->xpath($xPathQuery);
		if($nodes){
			foreach($nodes as $node){
	  			$domNode = dom_import_simplexml($node);
	  			if($domNode){
	  				$domReplace = $domNode->ownerDocument->importNode($domReplacement->documentElement->cloneNode(true),true);
	  				$domNode->parentNode->replaceChild($domReplace,$domNode);
					$this->isModified = true;
	  			}
	  		}
		}else{
			errors_log('Invalid expression '.$xPathQuery);
		}
  	}
  	
  	/*
  	 * Appends a child to anothers children.
  	 * @param  $xPathQuery  (string) Xpath to the node to append to.
  	 * @param  $node a simpleXMLElement object
  	 * @return NO
  	 */
  	function appendChild($xPathQuery, $nodeToAdd, $afterText=FALSE, $autoReindex=TRUE){
  		if(!is_object($this->simpleXML)){
  			return false;
  		}
		// removing header if present
		if(substr($nodeToAdd,0,5)=='<?xml'){
			$pos_end_header = strpos($nodeToAdd,'?>');
			if($pos_end_header!==false){
				$nodeToAdd = substr($nodeToAdd,$pos_end_header+2);
			}
		}
  		// // <INFO><SUSHEE:BLA should be <INFO xmlns:SUSHEE=""><SUSHEE:BLA 
  		// if not DOM will not care about namespaces
  		if(strpos($nodeToAdd,' xmlns:')===false){
	  		$pos_end_opening_tag = strpos($nodeToAdd,'>');
	  		require_once(dirname(__FILE__)."/../common/namespace.class.php");
			$namespaces = new NamespaceCollection();
			$namespaces_str = $namespaces->getXMLHeader();
	  		if($pos_end_opening_tag!==false){
	  			if($nodeToAdd[$pos_end_opening_tag-1]=='/'){
	  				$pos_end_opening_tag--;
	  			}
	  			$nodeToAdd = substr_replace($nodeToAdd,$namespaces_str,$pos_end_opening_tag,0);
	  		}
  		}
		
  		$domNodeToAdd = DOMDocument::loadXML($nodeToAdd); 
  		
  		$nodes = @$this->simpleXML->xpath($xPathQuery);
		if($nodes){
			foreach($nodes as $node){
	  			$domNode = dom_import_simplexml($node);
	  			if($domNode && $domNodeToAdd){
	  				$b = $domNode->ownerDocument->importNode($domNodeToAdd->documentElement->cloneNode(true),true);
					$this->isModified = true;
	  				$domNode->appendChild($b);
	  			}
	  		}
		}else{
			errors_log('Invalid expression '.$xPathQuery);
		}
		
  	}
  	
  	/*
  	 * This method do a node/attribute search in the xml document linked to the XML2 object.
  	 * @param		$xPathQuery XPath query to be evaluated
  	 * @return		array of simpleXMLElement objects if the search succeed , FALSE otherwise. 
  	 */
  	function match($xPathQuery){
  		if(!is_object($this->simpleXML)){
  			return false;
  		}
  		else{
  			$xpath_array = array();
  			
			$doc = dom_import_simplexml($this->simpleXML);
			$xpath = new DOMXpath($doc->ownerDocument);
			$elements = array();
			if($xPathQuery)
				$elements = @$xpath->query($xPathQuery); // @ disables WARNINGS when expression is invalid
			if($elements === false){
				errors_log('Invalid expression '.$xPathQuery);
			}else{
				foreach($elements as $node){
					$xpath_array[] = $this->recomposeXpath($node);
				}
			}
			
  			return $xpath_array;
  		}
  	}
  	
  	function recomposeXpath(/* DomNode */ $node){
  		
  		$parentNode = $node->parentNode;
  		$xpath='';
  		if($parentNode && (get_class($parentNode)=='DOMElement' || get_class($parentNode)=='DOMNode') ){
  			$xpath = $this->recomposeXpath($parentNode);
  		}
  		$xpath.='/';
  		if($node->nodeType == XML_ATTRIBUTE_NODE){
  			$xpath.='@';
  		}
		if($node->nodeName=='#text'){
			$xpath.='text()';
		}else{
			$xpath.=$node->nodeName;
		}
  		
  		if($parentNode && $node->nodeType != XML_ATTRIBUTE_NODE){
  			$xpath.='[';
  			$sibling = $node;
  			$i = 1;
  			while($sibling = $sibling->previousSibling ){
  				if($sibling->nodeName == $node->nodeName){
  					$i++;
  				}
  			}
  			$xpath.=$i;
  			$xpath.=']';
  		}
  		
  		return $xpath;
  		
  	}
  	
  	/*
  	 * Inserts a node before the reference node with the same parent.
  	 * @param  $xPathQuery  (string) Xpath to the node to insert new node before
  	 * @param  $node	a simpleXMLElement object
  	 * 
  	 */
  	function insertBefore($xPathQuery,$nodeToAdd,$shiftRight=TRUE, $afterText=TRUE, $autoReindex=TRUE){
  		if(!is_object($this->simpleXML)){
  			return false;
  		}
		if(!$nodeToAdd){
			return false;
		}

  		// // <INFO><SUSHEE:BLA should be <INFO xmlns:SUSHEE=""><SUSHEE:BLA 
  		// if not DOM will not care about namespaces
  		if(strpos($nodeToAdd,' xmlns:')===false){
  			$pos_end_opening_tag = strpos($nodeToAdd,'>');
  			require_once(dirname(__FILE__)."/../common/namespace.class.php");
			$namespaces = new NamespaceCollection();
			$namespaces_str = $namespaces->getXMLHeader();
  				if($pos_end_opening_tag!==false){
  					if($nodeToAdd[$pos_end_opening_tag-1]=='/'){
  						$pos_end_opening_tag--;
  					}
  					$nodeToAdd = substr_replace($nodeToAdd,$namespaces_str,$pos_end_opening_tag,0);
  				}
  		}
  		$domDocToAdd = DOMDocument::loadXML($nodeToAdd);
  		$nodes = @$this->simpleXML->xpath($xPathQuery);
		if($nodes){
			foreach($nodes as $node){
	  			$domNode = dom_import_simplexml($node);
	  			if($domNode && $domDocToAdd){
	  				$domNodeToAdd = $domDocToAdd->documentElement->cloneNode(true);
	  				$domNodeToAdd = $domNode->ownerDocument->importNode($domDocToAdd->documentElement->cloneNode(true),true);
	  				$domNode->parentNode->insertBefore($domNodeToAdd,$domNode);
	  			}
	  		}
			return true;
		}else{
			errors_log('Invalid expression '.$xPathQuery);
			return false;
		}
  		
  	}
  	
  	/*
  	 * Retrieves a dedicated attribute value or a hash-array of all attributes of a node.
  	 * 
  	 * The first param $absoluteXPath must be a valid xpath OR a xpath-query that results 
   * to *one* xpath. If the second param $attrName is not set, a hash-array of all attributes 
   * of that node is returned.
   * 
   * Optionally you may pass an attribute name in $attrName and the function will return the 
   * string value of that attribute.
   *  @param  $absoluteXPath (string) Full xpath OR a xpath-query that results to *one* xpath.
   *  @param  $attrName      (string) (Optional) The name of the attribute.
   *  @return                (mixed)  hash-array or a string of attributes depending if the 
   *                                 parameter $attrName was set
  	 */
  	function getAttributes($xPathQuery,$attrName=NULL){
		if(!is_object($this->simpleXML)){
			return false;
		}
  		$doc = dom_import_simplexml($this->simpleXML);
		$xpath = new DOMXpath($doc->ownerDocument);
		$elements = $xpath->query($xPathQuery);
		if($elements->length==0){
  			return false;
  		}
  		$domnode = $elements->item(0);
		$attributes = $domnode->attributes;
		$length = $attributes->length;
		for($i=$length-1;$i>=0;$i--){
			$attributeArray[$attributes->item($i)->nodeName]=$attributes->item($i)->nodeValue;
		}
		if($attrName !== NULL){
			if(!isset($attributeArray[$attrName])){
				return false;
			}
  			$attributeValue = $attributeArray[$attrName];
			// simple XML transforms 'true'string value into a boolean, correct that
			if($attributeValue==true)
				return 'true';
				
  			return encode_to_xml($attributeValue);
  		}else{
  			return $attributeArray;
  		}
  	}
  	
  	
  	/*
  	 * Set attributes of a node(s).
  	 * This method sets a number single attributes. An existing attribute is overwritten with the new value
  	 * @param  $xPathQuery (string) xpath to the node
  	 *  @param  $attributeName       (string) Attribute name.
  	 *  @param  $value      (string) Attribute value.
  	 *	@return	NO	
  	 */
  	function setAttribute($xPathQuery,$attributeName,$value){
		if(!is_object($this->simpleXML)){
			return false;
		}
  		$nodesToImpact = @$this->simpleXML->xpath($xPathQuery);
		if($nodesToImpact){
			foreach($nodesToImpact as $node){
	  			$targetNode = dom_import_simplexml($node);
				$targetNode->setAttribute($attributeName,$value);
	  		}
		}else{
			errors_log('Invalid expression '.$xPathQuery);
			return false;
		}
  		
		return true;
  	}
  	
  	
  	/*
  	 * Removes an attribute of a node(s).
  	 * This method removes *ALL* attributres per default unless the second parameter $attrList is set.
  	 * $attrList can be either a single attr-name as string OR a vector of attr-names as array.
  	 * removeAttribute(<xPath>);                     # will remove *ALL* attributes.
   	 *  removeAttribute(<xPath>, 'A');                # will only remove attributes called 'A'.
   	 *  removeAttribute(<xPath>, array('A_1','A_2')); # will remove attribute 'A_1' and 'A_2'.
   	 *  @param   $xPathQuery (string) xpath to the node
   	 *  @param   $attrList   (mixed)  (optional) if not set will delete *all*
   	 *  FALSE if the node couldn't be found
  	 */
  	function removeAttribute($xPathQuery, $attrList=NULL){
  		if(!is_object($this->simpleXML)){
			return false;
		}
		$nodesToImpact = @$this->simpleXML->xpath($xPathQuery);
		if($attrList !== NULL){
  			if(is_string($attrList)){
  				$attrList = array($attrList);
  			}
  			if(!is_array($attrList)){
  				return false;
  			}
  		}
		if($nodesToImpact){
			foreach($nodesToImpact as $node){
	  			$targetNode = dom_import_simplexml($node);

				if($attrList === NULL){
					$attributes = $targetNode->attributes;
					$length = $attributes->length;
					for($i=$length-1;$i>=0;$i--){
						$targetNode->removeAttribute($attributes->item($i)->name);
					}
				}
				else{
					foreach($attrList as $attr){
						$targetNode->removeAttribute($attr);
					}
				}
	  		}
		}else{
			errors_log('Invalid expression '.$xPathQuery);
			return false;
		}
  		
		return true;
  	}
  	
  	
  	
  	
  	/*
  	 * Retrieve all the text from a node as a single string.
  	 * Sample  
   * Given is: <AA> This <BB\>is <BB\>  some<BB\>text </AA>
   * Return of getData('/AA[1]') would be:  " This is   sometext "
   * The first param $xPathQuery must be a valid xpath OR a xpath-query that 
   * results to *one* xpath.
   *  
   * @param  $xPathQuery (string) xpath to the node - resolves to *one* xpath.
   * @return             (mixed)  The returned string, FALSE if the node couldn't be found or is not unique.
  	 */
  	function getData($xPathQuery){
  		if(!is_object($this->simpleXML)){
			return false;
		}
  		$doc = dom_import_simplexml($this->simpleXML);
		$xpath = new DOMXpath($doc->ownerDocument);
		$elements = $xpath->query($xPathQuery);
		if($elements->length==0){
  			return false;
  		}
  		$domnode = $elements->item(0);
		
  		if($domnode->nodeType == XML_ATTRIBUTE_NODE){
  			return $this->_encode(encode_to_xml($domnode->nodeValue));
  		}
		if($domnode->nodeType == XML_TEXT_NODE){
  			return $this->_encode(encode_to_xml($domnode->wholeText));
  		}
		
  		return $this->_encode(encode_to_xml($domnode->nodeValue)); // former XML API didnt decode, so we keep that behaviour
  	}

	function _encode($str){
		if($this->entities){
			return utf8_To_UnicodeEntities($str);
		}
		return $str;
	}
	
	function enableEntities($boolean = true){
		$this->entities = $boolean;
	}
  	
  	/*
  	 * Replace a sub string of a text-part OR attribute-value.
  	 * @param  $xPathQuery		(string) xpath to the node
  	 * @param  $replacement		(string) The string to replace with.
  	 * @return NO
  	 */
  	function replaceData($xPathQuery, $replacement){
		if(!is_object($this->simpleXML)){
			return false;
		}
		$nodesToImpact = @$this->simpleXML->xpath($xPathQuery);
		
		if($nodesToImpact){
			
			foreach($nodesToImpact as $node){
	  			$targetNode = dom_import_simplexml($node);
				$childNodes = $targetNode->childNodes;
				$this->isModified = true;
				while ($targetNode->childNodes->length){
				     $targetNode->removeChild($targetNode->firstChild);
				}
				$targetNode->appendChild(new DOMText($replacement));
	  		}
		}else{
			errors_log('Invalid expression '.$xPathQuery);
			return false;
		}
  		
		return true;
  	}
  	
  	
  	
  	/*
  	 * Insert a sub string in a text-part OR attribute-value.
  	 * 
  	 * @param  $xPathQuery (string) xpath to the node
  	 * @param  $data       (string) The string to replace with.
  	 * @return				NO	
  	 */
  	function insertData($xPathQuery, $data){
  		$this->replaceData($xPathQuery,$data);
  	}
  	
	
	
	
  	
  	/*
  	 * Return the  value of the node determined by the  xpath param evaluation
  	 * 
  	 * @param $xpath		(string) xpath to the node
  	 */
	function valueOf($xpath){
		$val = $this->getData($xpath);
		
		return $val;
	}
	
	
	/*
	 * Return a xml string which is a copy of the node determined by the xpath param evaluation
	 * @param $xpath		(string) xpath to the node
	 */
	function copyOf($xPathQuery){
		if(!is_object($this->simpleXML)){
			return false;
		}
		$nodes = @$this->simpleXML->xpath($xPathQuery);
		$str = '';
		if($nodes === false){
			errors_log('Invalid expression '.$xPathQuery);
		}else{
			foreach($nodes as $node){
				$str.=$this->_encode($node->asXML());
			}
		}
		return $str;
		
		
	}
	/*
	function copyOf($xPathQuery){
		if(!is_object($this->simpleXML)){
			return false;
		}
		// using dom because SimpleXML->asXML method makes errors with namespaces
		$doc = dom_import_simplexml($this->simpleXML);
		$xpath = new DOMXpath($doc->ownerDocument);
		$elements = $xpath->query($xPathQuery);
		if($elements->length==0){
  			return false;
  		}
		for($i=0;$i<$elements->length;$i++){
			$domnode = $elements->item($i);
			$str.= $doc->saveXML($domnode);
		}
  		
		return $str;
		
	}*/
	
	
	/*
	 * This method verifies the existence of a node , using the given xpath param.
	 * 
	 *  @param $xpath		(string) xpath to the node
	 *  @return				'true' if the node exists, 'false'otherwise		
	 */
	function exists($xpath){
		if(!is_object($this->simpleXML)){
			return false;
		}
		$vector = @$this->simpleXML->xpath($xpath);
		if($vector){
			return true;
		}
		else{
			return false;
		}
	}
		
	
	
	
	
	/*
	 * This method returns an array of simpleXMLElement objects which match to the given xpath param
	 * @param $xpath		(string) xpath to the node
	 * @return				array of simpleXMLElement objects or null array if the xpath don't match to nothing. 
	 */
	/* XMLNode array */function getElements($xpath){
		$xpath_array = $this->match($xpath);
		$array = array();
		if(is_array($xpath_array)){
			foreach($xpath_array as $xpath){
				$array[]=new XMLNode($this,$xpath);
			}
		}
		return $array;
	}
	
	
	
	/*
	 * This method returns a simpleXMLElement object which matches to the given xpath param.
	 *  When there are nodes having the same tag,if the number of the node is not indicated in the xpath param,
	 *  first node will be returned.
	 *  
	 *   @param $xpath		(string) xpath to the node
	 *   @return			an simpleXMLElement object
	 */
	/* XMLNode */function getElement($xPathQuery){
		if(!is_object($this->simpleXML)){
			return false;
		}
		
		$doc = dom_import_simplexml($this->simpleXML);
		$xpath = new DOMXpath($doc->ownerDocument);
		$elements = $xpath->query($xPathQuery);
		if($elements->length==0){
  			return false;
  		}
		$node = $elements->item(0);
		return new XMLNode( $this , $this->recomposeXpath($node));
	}
	
	function getFirstchild(){
		return $this->getElement('/*[1]');
	}
	
	
	/*
	 * This method returns the number of matches found for the given xpath param
	 * 
	 * @param $xpath		(string) xpath to the node
	 */
	function count($xpath){
		if(!is_object($this->simpleXML)){
			return false;
		}
		$vector = @$this->simpleXML->xpath($xpath);
		if($vector === false)
			return 0;
		return sizeOf($vector);
	}
	
	
	/*
	 * This method returns the xml string matching  to the node determinated by the given xpath param.
	 * If the xpath param is empty, all the xml content of the object will be returned.
	 * 
	 * @param $absoluteXPath		(string) xpath to the node
	 * @return 						an xml string 			
	 */
	function toString($absoluteXPath='/',$xmlHeader=NULL){
		
		if(!is_object($this->simpleXML)){
			return false;
		}
		if($absoluteXPath =='/'){
			$xmlStr = $this->simpleXML->asXML();
				// removing the newline just after the header added by DOM
				$xmlStr=str_replace("?>\r",'?>',$xmlStr);
				$xmlStr=str_replace("?>\n",'?>',$xmlStr);
				// adding the header
				if($xmlHeader!==NULL){
					$posEndXmlHeader = strpos($xmlStr,'?>');
					if($posEndXmlHeader!==false){
						$xmlStr = substr($xmlStr,$posEndXmlHeader+2);
						$xmlStr = $xmlHeader.$xmlStr;
					}
				}
			return trim($this->_encode($xmlStr)); // removing extra whitespace, newlines, etc
		}
		else{
			$nodes = false;
			if($absoluteXPath)
				$nodes = @$this->simpleXML->xpath($absoluteXPath);
			if($nodes === false){
				return false;
			}
			else{
					foreach($nodes as $node){
						$xmlStr.= $node->asXML();
					}
			}
					
			if($xmlHeader !== NULL){
				$posEndXmlHeader = strpos($xmlStr,'?>');
				if($posEndXmlHeader!==false){
					$xmlStr = substr($xmlStr,$posEndXmlHeader+2);
					$xmlStr = $xmlHeader.$xmlStr;
				}
			}
			return trim($this->_encode($xmlStr));// removing extra whitespace, newlines, etc
		}
	}
		
	
	 
	/*
	 * 
	 */
	function getArray($xPathQuery=''){
		 $matching_paths = @$this->simpleXML->xpath($xPathQuery);
		 $nodes_content = array();
		 if($matching_paths){
		 	foreach($matching_paths as $path){
		 		$nodehead = '</?'.$path->getName().'>';
		 		$prettyXML = ereg_replace($nodehead,'',$path->asXML()); 
		 		$nodes_content[] = $prettyXML;
		 	}
		 }
		 
		 return $nodes_content;
		
	}
	// FOR COMPATIBILITY
	function reindexNodeTree(){
		;
	}
	
	function setModMatch($boolean){
		;
	}
	
	function setSkipWhiteSpaces($boolean){
		$this->parseOptions[XML_OPTION_SKIP_WHITE] = $boolean;
	}
	
	function getxSusheeOperator($xpath){
		$node = new XMLNode($this,$xpath);
		$operator = $node->getAttribute('operator');
		if(!$operator){
			$operator = $node->getAttribute('op');
		}
		return $operator;
	}
	
	function getUniqueID(){
		if(!$this->uniqueID){
			$this->uniqueID = md5($this->toString());
		}
		return $this->uniqueID;
	}
	
	function implode($separator,$xpath){
		$nodes = $this->getElements($xpath);
		$first = true;
		foreach($nodes as $node){
			if(!$first){
				$str.=$separator;
			}
			$str.=$node->valueOf();
			$first = false;
		}
		return $str;
	}

}


?>