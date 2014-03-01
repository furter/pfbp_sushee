<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour l'affichage de la valeur d'un élément
	-->
	<xsl:template match="DATA[@type='CATEGORY']" mode="value">
		<xsl:variable name="path" select="current()/@path"/>
		<xsl:for-each select="//CATEGORIES/CATEGORY[FATHERNAME = $path]">
			<a href="">
				<xsl:value-of select="LABEL"/>
			</a>
			<xsl:call-template name="add_coma_if_not_last"/>
		</xsl:for-each>
	</xsl:template>
	<xsl:template match="DATA[@type='DESCRIPTION']" mode="value">
		<xsl:value-of select="DESCRIPTIONS/DESCRIPTION//*[name() = current()/@node]"/>
	</xsl:template>

</xsl:stylesheet>