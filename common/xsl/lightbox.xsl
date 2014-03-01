<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour générer un conteneur de lightbox
	-->
	<xsl:template name="lightbox">
		<xsl:param name="content"/>
		<div id="lightbox" class="hidden">
			<div id="lightbox_container">
				
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>