<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/commandline.class.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

require_once(dirname(__FILE__)."/../common/nectil_object.class.php");
require_once(dirname(__FILE__)."/../common/sushee.class.php");

//---------------------------
// Sushee_CommandLine : execute an external shell application
//---------------------------

class Sushee_CommandLine extends SusheeObject{
	
	var $command;
	var $output;
	var $logging;
	
	function Sushee_CommandLine($command){
		$this->setCommand($command);
		$this->logging = true;
	}
	
	function setCommand($command){
		$this->command = $command;
	}
	
	function getCommand(){
		return $this->command;
	}
	// enable or disable logging in /logsdev/debug.log
	function enableLogging(){
		$this->logging = true;
	}
	
	function disableLogging(){
		$this->logging = false;
	}
	
	function execute(){
		
		if($this->logging)
			$this->log($this->command);
		
		// On windows the command line is a bit tricky and using the call executable is more portable and works beter
		if(Sushee_Instance::isWindows()){
			$this->output = shell_exec('call '.$this->command.' 2>&1');
		}else{
			$this->output = shell_exec($this->command.' 2>&1');
		}
		
		if($this->logging)
			$this->log($this->output);
			
		return $this->output;
	}
	
	function getOutput(){
		return $this->output;
	}
}

//---------------------------
// API Compatibility class 
//---------------------------

class CommandLine extends Sushee_CommandLine{
	
}

//---------------------------
// Sushee_BackgroundProcess : execute an external shell application, but in background, in order to let Sushee continue its job
//---------------------------

class Sushee_BackgroundProcess extends Sushee_CommandLine{
	
	var $name;
	var $pidFile;
	var $pid;
	
	function Sushee_BackgroundProcess($name){
		$this->name = $name;
		
		// we save the process ID in a file to be able to kill or restart the process later
		$pidsFolder = new Folder("/pids/");
		if(!$pidsFolder->exists()){
			$pidsFolder->create();
		}
		$this->pidFile = new File('/pids/'.$name.".pid");
		$this->pid = false;
	}
	
	function execute(){
		// nohup is a linux executable allowing to make background processing
		$bkg_command = 'nohup '.$this->command.' > /dev/null & echo $!';
		
		$pid = exec($bkg_command);
		
		$this->pidFile->save($pid);
		$this->pid = $pid;
	}
	
	function getPid(){
		if($this->pid !== false)
			return $this->pid;
		
		if($this->pidFile && $this->pidFile->exists()){
			$formerPid = $this->pidFile->toString();
			return $formerPid;
			
		}else
			return false;
	}
	
	function stop(){
		$formerPid = $this->getPid();
		if($formerPid){
			exec('kill '.$formerPid);
		}
	}
	
	function restart(){
		$this->stop();
		$this->execute();
	}
	
	function isRunning(){
		$formerPid = $this->getPid();
		if($formerPid){
			// checking if the process is already active
			exec('ps '.$formerPid.' 2>&1',$lineArray);
			
			if(sizeof($lineArray)>1){
				return true;
			}else
				return false;
		}else
			return false;
	}
}

//---------------------------
// Sushee_JavaCommandLine : calling java external applications to execute some specific tasks (PDF generation, Saxon XSL2 processor)
//---------------------------

class Sushee_JavaCommandLine extends SusheeObject{
	
	var $options;
	var $classpath;
	
	function Sushee_JavaCommandLine(){
		
	}
	
	function execute(){
		$cmd = new Sushee_CommandLine();
		
		// getting the path to the java executable in Sushee configuration file
		$java = makeExecutableUsable( Sushee_Instance::getConfigValue('javaExecutable') );
		
		// java max memory can be configured in Sushee, to allow more heavy treatment (PDF generation)
		if( Sushee_Instance::getConfigValue('javaMaxMemory') ){
			$memory = (int) Sushee_Instance::getConfigValue('javaMaxMemory');
		}else{
			$memory = 512; // by default
		}
		
		$java_call = $java.' -Xms128M -Xmx'.escapeshellarg($memory).'M -Djava.awt.headless=true';
		$java_call.=' '.implode(' ',$this->options);
		
		if(Sushee_Instance::isWindows())
			$classpathSeparator = ';';
		else
			$classpathSeparator = ':';
		
		$java_call.=' -classpath '.escapeshellarg(implode($classpathSeparator,$this->classpath));
		$java_call.=' '.$this->command;
		
		$cmd->setCommand($java_call);
		
		return $cmd->execute();
	}
	
	function addJavaOption($option){
		$this->options[]=$option;
	}
	
	function addLibrary($lib){
		$this->classpath[]=$lib;
	}
	
	function setCommand($command){
		$this->command = $command;
	}
}