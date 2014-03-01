<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions pour déployer des navigations spécifiques (sur base de liste par exemple)
	-->
	<!-- -->
	<!-- tools navigation -->
	<xsl:template name="tools_navigation">
		<xsl:apply-templates select="/RESPONSE/RESULTS/LIST[@name='admin_navigation']" mode="tools_nav"/>
	</xsl:template>
	<xsl:template match="LIST" mode="tools_nav">
		<div id="tools">
			<ul>
				<xsl:apply-templates select="ITEM" mode="tools_nav"/>
			</ul>
		</div>
	</xsl:template>
	<xsl:template match="ITEM" mode="tools_nav">
		<li id="{@value}_container" class="tool_nav">
			<a href="#" onclick="tool_nav_toggle_display('{@value}');">
				<xsl:value-of select="@label"/>
			</a>
			<form name="{@value}" id="{@value}" action="{//NECTIL/this_script}" method="get" class="hidden tool">
				<input type="hidden" name="ID" value="{//URL/ID}"/>
				<input type="hidden" name="sort" value="{//URL/sort}"/>
				<input type="hidden" name="{@value}" value="1"/>
				<xsl:apply-templates select="." mode="specific"/>
			</form>
		</li>
	</xsl:template>
	<xsl:template match="ITEM[@value='search']" mode="specific">
		<xsl:call-template name="form_input_text">
			<xsl:with-param name="name" select="'search'"/>
			<xsl:with-param name="value" select="//URL/search"/>
			<xsl:with-param name="label" select="//LABEL[@name='f_search']"/>
		</xsl:call-template>
		<xsl:apply-templates select="." mode="button"/>
	</xsl:template>
	<xsl:template match="ITEM[@value='browse']" mode="specific">
		<xsl:apply-templates select="/RESPONSE/CONFIG/FORM" mode="config"/>
		<xsl:apply-templates select="." mode="button"/>
	</xsl:template>
	<xsl:template match="ITEM[@value='display']" mode="specific">
		<xsl:apply-templates select="/RESPONSE/DISPLAY" mode="display_options"/>
	</xsl:template></xsl:stylesheet>