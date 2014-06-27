<?php
include_once("common.php");
//
$NQL = new NQL(false);
$NQL->addCommand('
<SEARCH name="media">
	<MEDIA mediatype="PDF">
		<ANCESTOR>
			<MEDIA ID="'.$_GET['ID'].'"/>
		</ANCESTOR>
	</MEDIA>
	<RETURN depth="1">
		<INFO><MEDIATYPE/></INFO>
		<DESCRIPTIONS/>
	</RETURN>
</SEARCH>');
$NQL->addCommand('<GET><LIST name="contributorType"/></GET>');
$NQL->addCommand('<GET name="book_theme"><CATEGORIES ID="26" /><RETURN depth="2" /></GET>');
$NQL->addCommand('<GET name="l_fre"><LABELS languageID="fre"/></GET>');
$NQL->addCommand('<GET name="l_dut"><LABELS languageID="dut"/></GET>');
$NQL->addCommand('<GET name="countries"><COUNTRIES languageID="eng"/></GET>');
$NQL->addCommand('
	<GET name="book">
		<MEDIA ID="'.$_GET['bID'].'"/>
		<RETURN depth="2">
			<INFO>
				<FIRSTNAME/><CONTACTTYPE/><LASTNAME/><DENOMINATION/>
				<MEDIATYPE/>
				<EVENTSTART/>
				<CREATIONDATE/>
			</INFO>
			<DESCRIPTIONS/>
			<DEPENDENCIES>
				<DEPENDENCY>
					<INFO><FIRSTNAME/><CONTACTTYPE/><LASTNAME/><DENOMINATION/></INFO>
				</DEPENDENCY>
			</DEPENDENCIES>
			<CATEGORIES/>
		</RETURN>
	</GET>');
//
$NQL->execute();
$book = str_replace(" ", "", removeaccents($NQL->valueOf("/RESPONSE/RESULTS[@name='book']/MEDIA/DESCRIPTIONS/DESCRIPTION/TITLE")) );
///
$y = date('Y');
$m = date('m');
if ( $m < 2) {
	$y = $y - 1;
}

///
$pdf = $NQL->transformToPDF('BookSubmissionConfirmPDF.xsl',false);
$file = '../Files'.$pdf;
$file_name = "PFBP".$y."-".$book.".pdf";
filedownload($file,$file_name);

//$NQL->transformToPDF("BookSubmissionConfirmPDF.xsl");
?>