<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/XML.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/XML2.class.php");

class XMLNode extends SusheeObject{
	var $xml;
	var $rootXpath;
	var $name;
	
	function XMLNode($xml,$xpath){
		$this->xml = $xml;
		if(substr($xpath,-1)=='/')
			$xpath = substr($xpath,0,-1);
		$this->rootXpath = $xpath;
		if($xml)
			$this->name = $xml->nodeName($this->rootXpath);
	}
	function getPath(){
		return $this->rootXpath;
	}
	function getDocument(){
		return $this->xml;
	}
	function canonicalize($xpath){
		if(substr($xpath,0,1)=='.' && substr($xpath,0,2)!='..')
			$xpath = substr($xpath,1);
		else if(substr($xpath,0,1)!='/' && strlen($xpath)>0)
			$xpath = '/'.$xpath;
		return $xpath;
	}
	
	function setValue($value){
		return $this->xml->replaceData($this->rootXpath,$value);
	}
	
	function valueOf($xpath=''){
		$xpath = $this->canonicalize($xpath);
		$val = $this->xml->valueOf($this->rootXpath.$xpath);
		return $val;
	}
	function getData($xpath=''){
		return $this->valueOf($xpath);
	}
	function nodeName(){
		return $this->name;
	}
	
	function getNodename(){
		return $this->nodeName();
	}
	
	function copyOf($xpath){
		$xpath = $this->canonicalize($xpath);
		return $this->xml->copyOf($this->rootXpath.$xpath);
	}
	function toString($xpath=''){
		return $this->copyOf($xpath);
	}
	
	function getElement($xpath){
		$xpath = $this->canonicalize($xpath);
		$xmlNode = $this->xml->getElement($this->rootXpath.$xpath);
		return $xmlNode;
	}
	
	function getParent(){
		return $this->getElement('/..');
	}
	
	function getChildren(){
		return $this->getElements('/*');
	}
	
	function hasChildren(){
		return sizeof($this->getChildren())>0;
	}
	
	function getFirstchild(){
		return $this->getElement('*[1]');
	}
	
	function getAttributes(){
		$attributes = array();
		$attributesNodes = $this->getElements('@*');
		foreach($attributesNodes as $node){
			$attributes[$node->nodeName()] = $node->valueOf();
		}
		return $attributes;
	}
	
	function getAttribute($name){
		return $this->valueOf('@'.$name);
	}
	
	function getElements($xpath){
		$xpath = $this->canonicalize($xpath);
		$vector = $this->xml->getElements($this->rootXpath.$xpath);
		return $vector;
	}
	
	function exists($xpath){
		$xpath = $this->canonicalize($xpath);
		$vector = $this->xml->getElements($this->rootXpath.$xpath);
		return (sizeof($vector)>0);
	}
	
	function replaceValue($new_value){
		$this->xml->isModified = true;
		$this->xml->replaceData($this->rootXpath,$new_value);
	}
	
	function count($xpath){
		$xpath = $this->canonicalize($xpath);
		return $this->xml->count($this->rootXpath.$xpath);
	}
	
	function removeChild($xpath){
		$this->xml->isModified = true;
		$xpath = $this->canonicalize($xpath);
		return $this->xml->removeChild($this->rootXpath.$xpath);
	}
	
	function setAttribute($name,$value){
		if($name){
			$this->xml->isModified = true;
			return $this->xml->setAttribute($this->rootXpath,$name,$value);
		}
	}
	
	function removeAttribute($name){
		$this->xml->isModified = true;
		return $this->xml->removeAttribute($this->rootXpath,$name);
	}
	
	function appendChild($node_str){
		$this->xml->isModified = true;
		$this->xml->appendChild($this->rootXpath,$node_str);
		return new XMLNode($this->getDocument(),$this->rootXpath.'/*[last()]');
	}
	
	function insertBefore($node_str){
		$this->xml->isModified = true;
		$this->xml->insertBefore($this->getParent()->getPath(),$node_str);
	}
	
	function modifyOrAppend($nodename,$value){
		$node = $this->getElement($nodename);
		if($node){
			$node->setValue($value);
		}else{
			$this->appendChild('<'.$nodename.'>'.$value.'</'.$nodename.'>');
		}
		return true;
	}
	
	function getxSusheeOperator(){
		$operator = $this->getAttribute('operator');
		if(!$operator){
			$operator = $this->getAttribute('op');
		}
		if(!$operator){
			$operator = $this->getAttribute('operation');
		}
		return $operator;
	}
	
	function getUniqueID(){
		return $this->xml->getUniqueID().$this->getPath();
	}
	
	function remove(){
		return $this->xml->removeChild($this->rootXpath);
	}
}

class StringXMLNode extends SusheeObject{
	var $name;
	var $value;
	var $forbidden_chars = array(' ',':',',','.','+','/','*');
	var $attributes;
	
	function StringXMLNode($name,$value=false){
		$this->name = $name;
		$this->value = $value;
	}
	
	function setAttribute($name,$value){
		$this->attributes[$name] = $value;
	}
	
	function getNodename(){
		$nodename = strtoupper(str_replace($this->forbidden_chars,'',$this->name));
		return $nodename;
	}
	
	function getXML(){
		$nodename = $this->getNodename();
		$attributes = '';
		foreach($this->attributes as $key=>$value){
			$attributes.=' '.str_replace($this->forbidden_chars,'',$key).'="'.encode_to_xml($value).'"';
		}
		return '<'.$nodename.$attributes.'>'.encode_to_xml($this->value).'</'.$nodename.'>';
	}
	
	function getOpeningTag(){
		$nodename = $this->getNodename();
		return '<'.$nodename.'>';
	}
	
	function getClosingTag(){
		$nodename = $this->getNodename();
		return '</'.$nodename.'>';
	}
}

class XMLFastParser extends SusheeObject{
	var $offset = 0;
	var $xml;
	
	function XMLFastParser($xml){
		$this->xml = $xml;
	}
	
	function valueOf($xpath){
		if(substr($xpath,0,1)!='/'){
			$xpath = '/'.$xpath;
		}
		$val = $this->_descend($xpath,false);
		$this->reset();
		return $val;
	}
	
	function reset(){
		$this->offset = 0;
	}
	
	function _descend($xpath,$return_node=false){
		$explosion = explode('/',$xpath);
		if(sizeof($explosion)==2){
			if($return_node){
				$node = $this->getNode($explosion[1]);
				//echo 'Found '.$node->toString().'<br/>';
				return $node;
			}else{
				$content = $this->getNodeContent($explosion[1]);
				//echo 'Found '.$content.'<br/>';
				return $content;
			}
		}
		do{
			$firstnode = $this->getNode($explosion[1]);
			if(!$firstnode){
				return false;
			}
			
			$new_explosion = $explosion;
			array_shift($new_explosion); // empty cell
			array_shift($new_explosion); // first real node
			$new_xpath = '/'.implode('/',$new_explosion);
			//echo 'Looking for '.$new_xpath.' in '.encode_to_xml($firstnode->toString()).'(offset:'.$firstnode->offset.')<br/>';
			$val = $firstnode->_descend($new_xpath,$return_node);
		}while($val===false);
		
		return $val;
	}
	
	function getElement($xpath){
		$val = $this->_descend($xpath,true);
		$this->reset();
		return $val;
	}
	
	function getNode($nodename){
		//echo 'Looking for '.$nodename.' in '.encode_to_xml($this->xml).'<br/>';
		$len_nodename = strlen($nodename);
		$node_pos = strpos($this->xml,'<'.$nodename.' ',$this->offset);
		if($node_pos===false){
			$node_pos = strpos($this->xml,'<'.$nodename.'>',$this->offset);
		}
		if($node_pos!==false){
			$end_node_pos = strpos($this->xml,'</'.$nodename.'>',$node_pos+$len_nodename+2/*  the two brackets */);
			$this->offset=$node_pos+$len_nodename+2/*  the two brackets */; // advancing to the end of the opening node, to allow getting the next node of the same type, or another one
			$node_str = substr($this->xml,$node_pos,- $node_pos + $end_node_pos+$len_nodename+3 /* two brackets, the slash */);
			$node = new XMLFastNode($node_str);
			$node->offset = $len_nodename+2;
			return $node;
		}else{
			return false;
		}
	}
	
	function getNodeContent($nodename){
		$node = $this->getNode($nodename);
		if(!$node){
			return false;
		}
		$node_str = $node->toString();
		$opening_tag_end = strpos($node_str,'>');
		$closing_tag_start = strpos($node_str,'</'.$nodename,$opening_tag_end);
		return substr($node_str,$opening_tag_end+1,$closing_tag_start - $opening_tag_end - 1);
	}
	
	function getAttribute($attrname){
		$len_attrname = strlen($attrname);
		$attr_pos = strpos($this->xml,$attrname.'="',$this->offset);
		$opening_tag_end = strpos($this->xml,'>',$this->offset);
		if($attr_pos!==false && $opening_tag_end > $attr_pos){ // attribute must belong to the current node, we verify its positionned before the end of the opening tag
			$attr_end_pos = strpos($this->xml,'"',$attr_pos+$len_attrname+2);
			return substr($this->xml,$attr_pos+$len_attrname+2 /* equals + quote */, - 2 - $attr_pos + $attr_end_pos - $len_attrname);
		}
		return false;
	}
	
	function toString(){
		return $this->xml;
	}
	
}

class XMLFastNode extends XMLFastParser
{
	function XMLFastNode($xml){
		$this->xml = $xml;
	}
}

class sushee_PHPObjects2XML extends SusheeObject{
	
	function encodeNodename($param_name){
		$param_name = str_replace(array(',',';','/',':',']','['),'',$param_name);
		if(is_numeric($param_name))
			$param_name = 'item';
		if (is_numeric(substr($param_name,0,1)))
			$param_name = '_'.$param_name;
		return $param_name;
	}
	
	function encodeValue($param_value){
		return encode_to_XML(utf8_decode(utf8_To_UnicodeEntities(stripcslashes($param_value))));
	}
	
	function execute($array){
		$strResponse='';
		if(is_array($array)){
			foreach($array as $param_name=>$param_value){
				$param_name = $this->encodeNodename($param_name);
				$strResponse.='<'.$param_name.'>';
				if(is_array($param_value) || is_object($param_value)){
					$strResponse.=$this->execute($param_value);
				}else{
					$strResponse.=$this->encodeValue($param_value);
				}
				$strResponse.='</'.$param_name.'>';
			}
		}else if(is_object($array)){
			$object_class = $this->encodeNodename(get_class($array));
			$strResponse.='<'.$object_class.'>';
			$strResponse.='<vars>'.$this->execute(get_object_vars($array)).'</vars>';
			$strResponse.='<methods>';
			$methods = get_class_methods(get_class($array));
			foreach($methods as $methodname){
				$strResponse.='<'.$this->encodeNodename($methodname).'/>';
			}
			$strResponse.='</methods>';
			$strResponse.='</'.$object_class.'>';
		}else{
			return $array;
		}
		
		return $strResponse;
	}
}