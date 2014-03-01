<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/createCaptcha.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__).'/../common/image_functions.inc.php');
require_once(dirname(__FILE__).'/../common/nqlOperation.class.php');
require_once(dirname(__FILE__).'/../common/file.class.php');
require_once(dirname(__FILE__)."/../common/image.class.php");

class createCaptcha extends RetrieveOperation{
	var $code;
	var $backgroundColor='#999999';
	var $color ='#ffffff';
	var $fontSize = '24';
	var $paddingTop = '4';
	var $paddingBottom = '4';
	var $paddingLeft = '12';
	var $paddingRight = '12';
	var $captcha_name = 'default';
	function parse(){
		if(is_object($this->firstNode)){
			$backgroundColor = $this->firstNode->valueOf('@background-color');
			$color = $this->firstNode->valueOf('@color');
			$fontSize = $this->firstNode->valueOf('@font-size');
			$padding = $this->firstNode->valueOf('@padding');
			$paddingTop = $this->firstNode->valueOf('@padding-top');
			$paddingBottom = $this->firstNode->valueOf('@padding-bottom');
			$paddingLeft = $this->firstNode->valueOf('@padding-left');
			$paddingRight = $this->firstNode->valueOf('@padding-right');
		}
		if($backgroundColor)
			$this->backgroundColor = $backgroundColor;
		if($color)
			$this->color = $color;
		if($fontSize)
			$this->fontSize = $fontSize;
		if($padding){
			$this->paddingTop = $padding;
			$this->paddingBottom = $padding;
			$this->paddingLeft = $padding;
			$this->paddingRight = $padding;
		}
		if($paddingTop)
			$this->paddingTop = $paddingTop;
		if($paddingBottom)
			$this->paddingBottom = $paddingBottom;
		if($paddingLeft)
			$this->paddingLeft = $paddingLeft;
		if($paddingRight)
			$this->paddingRight = $paddingRight;
		return true;
	}
	
	function operate(){
		$xml = '';
		$attributes = $this->getOperationAttributes();
		$xml.='<RESULTS'.$attributes.'>';
		$this->code = generate_password(6,1,'A');
		/*$path = createText(
			'<text color="'.$this->color.'" size="'.$this->fontSize.'" background-color="'.$this->backgroundColor.'" >'.encode_to_xml($this->code).'</text>'
			,false);*/
		$text = &new DistortedText($this->code);
		$text->setFontSize($this->fontSize);
		$text->setColor($this->color);
		$text->setBackgroundColor($this->backgroundColor);
		$res = $text->execute();
		if($res){
			$file = &$text->getTarget();
			$path = $file->getPath();
			$this->image = imageTransform(
				'<IMAGE path="'.$path.'">
					<crop position="topright" background-color="'.$this->backgroundColor.'" width="+'.($this->paddingLeft).'" height="+'.($this->paddingBottom).'"/>
					<crop position="bottomleft" background-color="'.$this->backgroundColor.'" width="+'.($this->paddingRight).'" height="+'.($this->paddingTop).'"/>
				</IMAGE>'
				,false);
			$xml.='<CAPTCHA>'.encode_to_xml($this->image).'</CAPTCHA>';
			$xml.='</RESULTS>';
			$this->setXML($xml);
			$_SESSION[$GLOBALS["nectil_url"]]['captcha'][$this->captcha_name]=$this->code;
			return $xml;
		}else{
			$this->setError('Problem generating a text with ImageMagick');
			return false;
		}
		
	}
	
	function getFile(){
		return new File($this->image);
	}
}

?>