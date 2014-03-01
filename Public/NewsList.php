<?php
include_once("common.php");
if (!isset( $_GET['page']) || $_GET['page'] == "") {
	$_GET['page'] = 1;
}

$NQL = new NQL();
if ( $_GET['fromos'] == 'true') {
	$NQL->includeUnpublished( true );
}
$NQL->addCommand(get_media($_GET['ID']));
$NQL->addCommand('
	<SEARCH name="data">
		<MEDIA mediatype="News"/>
		<RETURN depth="2">
			<INFO>
				<CREATIONDATE/>
				<MEDIATYPE/>
			</INFO>
			<DESCRIPTIONS/>
			<DEPENDENCIES>
				<DEPENDENCY type="mediaContent">
				<INFO>
					<PAGETCALL/>
					<MEDIATYPE/>
				</INFO>
				<DESCRIPTIONS/>
				</DEPENDENCY>
			</DEPENDENCIES>
		</RETURN>
		<SORT select="INFO/CREATIONDATE" order="descending"/>
		<PAGINATE display="10" page="'.$_GET['page'].'"/>
	</SEARCH>');
echo $NQL->transform("NewsList.xsl");
?>