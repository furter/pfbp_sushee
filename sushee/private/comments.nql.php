<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/comments.nql.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
require_once(dirname(__FILE__)."/../common/comments.class.php");
require_once(dirname(__FILE__)."/../common/nqlOperation.class.php");

class CommentNQLOperation extends NQLOperation{
	
	var $ID = false;
	var $title = false;
	var $body = false;
	var $type = false;
	var $file = false;
	var $creator = false;
	var $checked = false;
	var $targetID = false;
	var $module = false;
	
	function parse(){
		
		$this->ID = $this->firstNode->valueOf("@ID");
		if(!$this->ID)
			$this->ID = $this->firstNode->valueOf("ID");
		$this->targetID = $this->firstNode->valueOf("@targetID");
		if(!$this->targetID && !$this->ID){
			$this->setError('No target ID was provided : no comment was processed.');
			return false;
		}
		$this->module = moduleInfo($this->firstNode->valueOf("@module"));
		if (!$this->module->loaded && !$this->ID){
			$this->setError('Not a valid module : '.$this->firstNode->valueOf("@module"));
			return false;
		}
		$this->creator = $this->firstNode->valueOf("CONTACT[1]/@ID");
		$this->type = $this->firstNode->valueOf("TYPE");
		$this->checked = $this->firstNode->valueOf("CHECKED");
		$this->title = $this->firstNode->valueOf("TITLE[1]");
		$this->file = $this->firstNode->valueOf("FILE[1]");
		$this->body = $this->firstNode->toString("BODY[1]/*");
		if (!$this->body)
			$this->body = $this->firstNode->valueOf("BODY[1]");
		return true;
	}
	
	function operate(){
		if($this->ID){
			$comment = new NectilElementComment($this->ID);
		}else{
			$comment = new NectilElementComment();
		}
		if($this->title)
			$comment->setTitle($this->title);
		if($this->body)
			$comment->setBody($this->body);
		if($this->type)
			$comment->setType($this->type);
		if($this->checked!==false)
			$comment->setChecked($this->checked);
		if($this->file)
			$comment->setFile($this->file);
		if($this->creator)
			$comment->setCreator($this->creator);
		if($this->module)
			$comment->setModule($this->module);
		if($this->targetID)
			$comment->setTargetID($this->targetID);
		$res = $comment->save();
		if($res){
			if($this->ID){
				$this->setMsg(generateMsgXML(0,'Update of comment successfully processed.',0,$this->ID,$this->name));
			}else{
				$this->setMsg(generateMsgXML(0,'Creation of comment successfully processed.',0,$comment->getID(),$this->name));
			}
			return true;
		}else{
			$this->setError('Comment creation/update failed probably for SQL reason');
			return false;
		}
		
	}
	
	function getID(){
		return $this->ID;
	}
}

class createComment extends NQLOperation{
	
	var $operation;
	
	function parse(){
		$this->operation = new CommentNQLOperation($this->getName(),$this->getOperationNode());
		$this->operation->setFirstnode($this->getFirstnode());
		$res = $this->operation->parse();
		$this->setMsg($this->operation->getMsg());
		return $res;
	}
	
	function operate(){
		$res = $this->operation->operate();
		$this->setMsg($this->operation->getMsg());
		return $res;
	}
}

class updateComment extends NQLOperation{
	
	function parse(){
		$this->operation = new CommentNQLOperation($this->getName(),$this->getOperationNode());
		$this->operation->setFirstnode($this->getFirstnode());
		$res = $this->operation->parse();
		$this->setMsg($this->operation->getMsg());
		return $res;
	}
	
	function operate(){
		$res = $this->operation->operate();
		$this->setMsg($this->operation->getMsg());
		return $res;
	}
	
}


class deleteComment extends NQLOperation{
	function parse(){
		$ID = $this->firstNode->valueOf("@ID");
		if(!$ID)
			$ID = $this->firstNode->valueOf("ID");
		if(!$ID){
			$this->setError('No ID was provided : no deletion was processed.');
			return false;
		}
		$this->ID = $ID;
		return true;
	}
	
	function operate(){
		$comment = new NectilElementComment($this->ID);
		$comment->delete();
		$this->setSuccess('Delete of comment successfully processed.');
		return true;
	}
}
?>