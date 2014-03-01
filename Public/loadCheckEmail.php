<?php
include_once("common.php");

if ($_GET['test'] == 1) {
	$_POST['EMAIL'] = 'nicolas@speculoos.com';
	$_POST['mailID'] = 4190;
}
if ($_POST['EMAIL'] != '') {
	$NQL = new NQL(false);
	$NQL->addCommand('
		<SEARCH name="contact">
			<CONTACT email1="'.$_POST['EMAIL'].'"/>
			<RETURN><INFO get="false"/></RETURN>
		</SEARCH>
	');
	$NQL->execute();
	if ($NQL->exists('/RESPONSE/RESULTS/CONTACT')) {
		//contact in DB - send mail for subscribsion;
		$_GET['uID'] = $NQL->valueOf('/RESPONSE/RESULTS/CONTACT/@ID');
		$_GET['r'] = rand(1000000000, 9999999999);
		$NQL->addCommand('<GET name="mail" refresh="daily"><MEDIA ID="'.$_POST['mailID'].'"/><RETURN><INFO><MEDIATYPE/></INFO><DESCRIPTIONS languageID="fre"/></RETURN></GET>');
		$NQL->addCommand('<SEARCH name="BookSubmission" test="'.$_POST['mailID'].'" refresh="daily">
			<MEDIA mediatype="BookSubmission">
				<DESCENDANT>
					<MEDIA ID="'.$_POST['mailID'].'" />
				</DESCENDANT>
			</MEDIA>
			<RETURN><INFO get="false" /></RETURN>
		</SEARCH>');
		$NQL->execute();
		$subject = $NQL->valueOf('/RESPONSE/RESULTS[@name="mail"]/MEDIA/DESCRIPTIONS/DESCRIPTION/HEADER');
		$email = $NQL->transformToText('TransformEmail.xsl');
		$return = $NQL->copyOf('/RESPONSE/RESULTS/MEDIA/DESCRIPTIONS/DESCRIPTION/SUMMARY');
		$email = str_replace('<!DOCTYPE p PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">', '', $email);
		$email = str_replace("\n", "", $email);
		sendhtmlmail('Prix Fernand Baudin Prijs', 'info@prixfernandbaudinprijs.be', '', $_POST['EMAIL'], $subject, $email);
		echo $return;
	} else {
		echo '1';// go on
	}
} else {
	echo '0';
}

