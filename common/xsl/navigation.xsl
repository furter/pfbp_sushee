<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions génériques pour générer
		- des liste de lient / item de navigation
		- navigation général d'un site
	-->
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
			<xsl:apply-templates select="." mode="active"/>
			<xsl:apply-templates select="." mode="link"/>
			<xsl:if test="DEPENDENCIES/DEPENDENCY[@type='mediaNavigation']/MEDIA/@ID = $ID">
				<ul>
					<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='mediaNavigation']/MEDIA" mode="navigation_item"/>
				</ul>
			</xsl:if>
		</li>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/MEDIATYPE='Website']" mode="navigation">
		<div id="navigation">
			<ul>
				<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='mediaNavigation']/MEDIA" mode="navigation_item"/>
			</ul>
		</div>
	</xsl:template>
	<xsl:template name="navigation">
		<xsl:apply-templates select="$website" mode="navigation"/>
	</xsl:template>
	<xsl:template match="LIST" mode="navigation">
		<ul>
			<xsl:apply-templates select="*" mode="navigation"/>
		</ul>
	</xsl:template>
	<xsl:template match="ITEM" mode="navigation">
		<li>
			<a href="#{@value}">
				<xsl:value-of select="@label"/>
			</a>
		</li>
	</xsl:template>
</xsl:stylesheet>
