<?php
include_once("common.php");
$NQL = new NQL();
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
		<PAGINATE display="1" page="1"/>
	</SEARCH>');
echo $NQL->transform("News.xsl");
?>