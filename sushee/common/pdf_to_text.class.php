<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/pdf_to_text.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
class pdf_to_text{
	var $sourcefile;
	var $lastError;
	var $isLoaded;
	var $trace;
	
	function pdf_to_text($sourcefile){
		$this->sourcefile = $sourcefile;
		if(!file_exists($sourcefile)){
			$this->lastError = 'File doesn\'t exist';
			$this->isLoaded = false;
		}else{
			$this->isLoaded = true;
		}
			
	}
	
	function log($msg){
		$this->trace.=$msg."\n<br/>";
	}
	
	function getLastError(){
		return $this->lastError;
	}
	
	function _getBasicOutputParameters(){
		return array('extractText'=>true,'tagStream'=>false);
	}
	
	function getPSData(){
		$parameters = $this->_getBasicOutputParameters();
		$parameters['extractText']=false;
		$parameters['tagStream']=true;
		return $this->_extract($parameters);
	}
	
	function getString(){
		$parameters = $this->_getBasicOutputParameters();
		return $this->_extract($parameters);
	}
	
	function getXML(){
		$parameters = $this->_getBasicOutputParameters();
		$parameters['tagStream']=true;
		return $this->_extract($parameters);
	}
	
	function getLogs(){
		return $this->trace;
	}
	
	function _extract($parameters){
		$pdf_content = '';
		if($this->isLoaded){
			$command = 'pdftotext -nopgbrk -q -raw -enc UTF-8 "'.$this->sourcefile.'" -'; // the dash at the end is there to force output on stdout
			debug_log($command);
			$pdf_content = shell_exec($command);
		}
		return $pdf_content;
	}
	
	function _extract_bak($parameters) {
	   	if($this->isLoaded){
	   	   	$fp = fopen($this->sourcefile, 'rb');
		   	$content = fread($fp, filesize($this->sourcefile));
		   	fclose($fp);
   	   	}else
			return '';
		
	   	$searchstart = 'stream';
	 	$lg_searchstart = strlen($searchstart);
	   	$searchend = 'endstream';
		$lg_searchend = strlen($searchend);
	   	$pdfText = '';
	   	$pos = 0;
	   	$pos2 = 0;
	   	$startpos = 0;
		$pdfText='';
		$stream_index = 1;
	   	while ($pos !== false && $pos2 !== false) {

	       	$pos = strpos($content, $searchstart, $startpos);
	       	$pos2 = strpos($content, $searchend, $startpos + 1);

	       	if ($pos !== false && $pos2 !== false){
				$pos = $pos+$lg_searchstart;
			   	
	           	if ($content[$pos] == "\r" && $content[$pos + 1] == "\n") {
	               	$pos += 2;
	           	}else if ($content[$pos] == "\n" || $content[$pos] == "\r") {
	               	$pos++;
	           	}
				
	           	if ($content[$pos2 - 2] == "\r" && $content[$pos2 - 1] == "\n") {
	               	$pos2 -= 2;
	           	} else if ($content[$pos2 - 1] == "\n" || $content[$pos2 - 1] == "\r") {
	               	$pos2--;
	           	}
				$lg_textsection = $pos2 - $pos;
				
	           	$textsection = substr(
	               	$content,
	               	$pos,
	               	$lg_textsection
	           		);
			   	
	           	$data = @gzuncompress($textsection);
				
				
				$stream = ''; 
				if($parameters['extractText'])
	           		$stream .= $this->extractText($data);
				else
					$stream .= $data;
				if($parameters['tagStream'])
					$stream = '[stream index="'.$stream_index.'"]'.$stream.'[/stream]';
				$pdfText.=$stream;
	           	$startpos = $pos2 + $lg_searchend - 1;
				
				$stream_index++;
	       	}
	   	}
		
	   	return preg_replace('/(\s)+/', ' ', $pdfText);
	}

	function extractText($psData){
	   //return $psData;
	   if (!is_string($psData)) {
	       return '';
	   }

	   $text = '';

	   // Handle brackets in the text stream that could be mistaken for
	   // the end of a text field. I'm sure you can do this as part of the
	   // regular expression, but my skills aren't good enough yet.
	   $psData = str_replace('\)', '##ENDBRACKET##', $psData);
	   $psData = str_replace('\]', '##ENDSBRACKET##', $psData);

	   preg_match_all(
	       	'/'.
			'(T[wdcm*])?'.
			'[\s]*'.
			'(\[([^\]]*)\]|\(([^\)]*)\))'.
			'[\s]*Tj/si',
	       $psData,
	       $matches
	   );
	   for ($i = 0; $i < sizeof($matches[0]); $i++) {
			$this->log('found '.$matches[0][$i]);
	       if ($matches[3][$i] != '') {
	           // Run another match over the contents.
	           preg_match_all('/\(([^)]*)\)/si', $matches[3][$i], $subMatches);
	           foreach ($subMatches[1] as $subMatch) {
	               $text .= $subMatch;
	           }
	       } else if ($matches[4][$i] != '') {
	           $text .= ($matches[1][$i] == 'Tc' ? ' ' : '') . $matches[4][$i];
	       }
	   }

	   // Translate special characters and put back brackets.
	   $trans = array(
	       /*'...'                => '&hellip;',
	       '\205'                => '&hellip;',
	       '\221'                => chr(145),
	       '\222'                => chr(146),
	       '\223'                => chr(147),
	       '\224'                => chr(148),
	       '\226'                => '-',
	       '\267'                => '&bull;',
	       '\('                => '(',
	       '\['                => '[',*/
	       '##ENDBRACKET##'    => ')',
	       '##ENDSBRACKET##'    => ']'/*,
	       chr(133)            => '-',
	       chr(141)            => chr(147),
	       chr(142)            => chr(148),
	       chr(143)            => chr(145),
	       chr(144)            => chr(146),*/
	   );
	   $text = strtr($text, $trans);

	   return $text;

	}
}


?>