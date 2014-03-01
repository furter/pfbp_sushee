<?php
include_once("../../Kernel/common/common_functions.inc.php");
include_once('../../Kernel/common/nql.class.php');

$nql = new NQL(false);
$nql->addCommand('
	<SEARCH>
		<MEDIA>
			<DESCRIPTIONS>
				<DESCRIPTION>
					<LANGUAGEID>shared</LANGUAGEID>
				</DESCRIPTION>
			</DESCRIPTIONS>
		</MEDIA>
		<RETURN>
			<INFO>
				<MEDIATYPE />
			</INFO>
			<DESCRIPTIONS languageID="all"> 
					<LANGUAGEID />
			</DESCRIPTIONS>
		</RETURN>
	</SEARCH>');
$nql->execute();
//$nql->xml_out();die();
$medias = $nql->getElements('/RESPONSE/RESULTS/MEDIA');
$query = '';
foreach ($medias as $m) {
	$fre = $m->exists('DESCRIPTIONS/DESCRIPTION[@languageID = "fre"]');
	$dut = $m->exists('DESCRIPTIONS/DESCRIPTION[@languageID = "dut"]');
	$eng = $m->exists('DESCRIPTIONS/DESCRIPTION[@languageID = "eng"]');
	if ( $fre == true && $dut == true && $eng == true ) {
		$mID = $m->valueOf('@ID');
		$query .= '
		<UPDATE>
			<MEDIA ID="'.$mID.'">
				<DESCRIPTIONS>
					<DESCRIPTION>
						<LANGUAGEID>shared</LANGUAGEID>
						<STATUS>unpublished</STATUS>
					</DESCRIPTION>
				</DESCRIPTIONS>
			</MEDIA>
		</UPDATE>';
	}	
}
$nql->addCommand( $query );
$nql->execute();
$nql->xml_out();
