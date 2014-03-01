<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/public/mailing_unsuscribe.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
include_once("../common/common_functions.inc.php");
?>
<html>
		<head>
			<title>Unsuscribe</title>
			<style>
			body{background-color:#426c85;padding:0px;margin:0px;font-size:13px;font-family:Lucida Grande, Lucida Sans Unicode, Lucida Sans, Lucida, Helvetica, Arial;}
			#central_cell{
				position:absolute;/*background-color:white;*/width:482px;height:598px;
				background-image:url(../file/bkg_upload.gif);
				background-repeat:no-repeat;
				margin:-300px 0px 0px -242px;
				top: 50%; 
				left: 50%;
				text-align: left;
			}
			#central_cell #content{padding-left:20px;padding-right:20px;padding-top:200px;}
			#main_title{margin-top:21px;margin-bottom:21px;/*margin-left:17px;*/color:#A72C15;font-weight:normal;font-size:15px;}
			</style>
		</head>
		<body>
			<div id="central_cell">
			<div id="content">
<?php
if(sizeof($_POST)>0){
		$xml_str = query(
			'<QUERY>
				<UNSUSCRIBE>
					<CONTACT viewing_code="'.$_POST['viewing_code'].'"/>
				</UNSUSCRIBE>
			</QUERY>');
		$xml = new XML($xml_str);
		$msgType = $xml->getData('/RESPONSE/MESSAGE/@msgType');
		debug_log('errorCode '.$msgType);
		if($msgType!=='1')
			$final_message = 'You\'ve been successfully removed from the mailing list';
		else
			$final_message = 'An error occured during the process of removal.<br> Be sure the link to unsuscribe is complete (Webmails sometimes clean the message).';
		debug_log($xml_str);
		?>
		<h1 id="main_title">
		<?php echo $final_message; ?>
		</h1>
		<?php
}else if(isset($_GET['contactID'])){
	?>
	<h1 id="main_title">For security reasons we disabled the unsuscription from former newsletters.<br> Try instead to use the unsuscription button present in the last newsletter your received.</h1>
	<?php
}else if(!isset($_GET['viewing_code']) || strlen($_GET['viewing_code'])<32){
	?>
	<h1 id="main_title">We cannot process your removal because the link is incomplete.<br> Possible reason : you are using a webmail or an anti-spam filter.</h1>
	<?php
}else{
	?>
			<form action="mailing_unsuscribe.php" method="post">
				<input type="hidden" name="mailingID" value="<?= $_GET['mailingID']?>"/>
				<input type="hidden" name="viewing_code" value="<?= $_GET['viewing_code']?>"/>
				<h1 id="main_title">
				Do you really want to be removed from this mailing list ?
				</h1>
				<div style="margin-top:20px;margin-left:-40px;text-align:center;"><input type="submit" value="Confirm unsubscription"/></div>
				</div>
			</form>
	<?php
}
?>
	</div>
		</body>
	</html>
