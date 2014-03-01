<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/exception.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/nectil_object.class.php");


if(class_exists('Exception')){
	class SusheeException extends Exception{
	}

}else{
	// mimic the behaviour of exceptions in PHP4
	class SusheeException extends SusheeObject{

		var $message = 'Unknown exception';   // exception message
		var $string;                          // __toString cache
		var $code = 0;                        // user defined exception code
		var $file;                            // source filename of exception
		var $line;                            // source line of exception
		var $trace;                           // backtrace
		var $previous;                        // previous exception if nested exception

		function SusheeException($message = null, $code = 0, SusheeException $previous = null){
			$this->message = $message;
			$this->code = $code;
			$this->previous = $previous;
		}

		function getMessage(){// message of exception

		}        
		function getCode(){// code of exception

		}           
		function getFile(){// code of exception

		}           
		function getLine(){// source line

		}
		function getTrace(){// an array of the backtrace()

		}
		function getPrevious(){// previous exception

		}
		function getTraceAsString(){// formatted string of trace

		}
		function __toString(){

		}
	}
	
}
/* errors sent by pre and postprocessors */
class SusheeProcessorException extends SusheeException{
	var $code = SUSHEE_ERROR_PROCESSOREXCEPTION;
	
}
class SusheeProcessorWarning extends SusheeException{}

/* return message sent by a pre or postprocessor */
class SusheeProcessorMessage extends SusheeObject{
	
	var $message;
	
	function SusheeProcessorMessage($message){
		$this->message = $message;
	}
	
	function getMessage(){
		return $this->message;
	}
}
class SusheeXSLTException extends SusheeException{}
?>