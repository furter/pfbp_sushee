<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions génériques pour générer
		- les attributs href
		- classe active
		- des liens
		- des liste de lient / item de navigation
		- lien en savoir plus
		-->
		<!-- -->
		<!-- attribut href -->
		<xsl:template match="MEDIA" mode="href">
			<xsl:attribute name="href">
				<xsl:value-of select="INFO/MEDIATYPE"/>
				<xsl:text>.php?ID=</xsl:text>
				<xsl:value-of select="@ID"/>
			</xsl:attribute>
		</xsl:template>
		<xsl:template match="MEDIA[INFO/MEDIATYPE = 'Links']" mode="href">
			<xsl:attribute name="href">
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/URL"/>
			</xsl:attribute>
		</xsl:template>
		<xsl:template match="MEDIA[INFO/PAGETOCALL != '']" mode="href">
			<xsl:attribute name="href">
				<xsl:value-of select="INFO/PAGETOCALL"/>
				<xsl:text>?ID=</xsl:text>
				<xsl:value-of select="@ID"/>
			</xsl:attribute>
			<xsl:attribute name="title">
				<xsl:apply-templates select="." mode="title"/>
			</xsl:attribute>
		</xsl:template>
		<!-- -->
		<!-- attribut href -->
		<xsl:template match="MEDIA" mode="active"/>
		<xsl:template match="MEDIA[@ID=//URL/ID or DEPENDENCIES/DEPENDENCY[@type='mediaNavigation']/MEDIA/@ID = //URL/ID]" mode="active">
			<xsl:attribute name="class">active</xsl:attribute>
		</xsl:template>
		<!-- -->
		<!-- lien -->
		<xsl:template match="MEDIA" mode="link">
			<a>
				<xsl:apply-templates select="." mode="href"/>
				<xsl:apply-templates select="." mode="active"/>
				<xsl:apply-templates select="." mode="title"/>
			</a>
		</xsl:template>
		<xsl:template match="MEDIA[INFO/MEDIATYPE = 'Links']" mode="link">
			<a href="{DESCRIPTIONS/DESCRIPTION/URL}" target="_blank">
				<xsl:apply-templates select="." mode="title"/>
			</a>
		</xsl:template>
		<!-- -->
		<!-- list item avec lien / item de navigation -->
		<xsl:template match="MEDIA[INFO/MEDIATYPE = 'Website']" mode="navigation_item">
			<li>
				<a>
					<xsl:apply-templates select="." mode="href"/>
					<xsl:apply-templates select="." mode="active"/>
					<xsl:value-of select="//LABEL[@name='website_home']"/>
				</a>
			</li>
		</xsl:template>
		<xsl:template match="MEDIA" mode="navigation_item">
			<li>
				<xsl:apply-templates select="." mode="link"/>
			</li>
		</xsl:template>		
		<!-- -->
		<!-- lien en savoir plus-->
		<xsl:template match="MEDIA" mode="more">
			<p class="more">
				<a>
					<xsl:apply-templates select="." mode="href"/>
					<xsl:value-of select="//LABEL[@name='read_more']"/>
				</a>
			</p>
		</xsl:template>
</xsl:stylesheet>