<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour générer de labelisation d'éléments
	-->
	<!-- -->
	<!-- specific element label -->
	<xsl:template match="DATA" mode="label">
		<xsl:value-of select="@path"/>
	</xsl:template>
	<xsl:template match="DATA[@service='INFO' or @service='DESCRIPTION']" mode="label">
		<xsl:param name="label" select="concat('f_', @path)"/>
		<xsl:choose>
			<xsl:when test="//LABEL[@name=$label]">
				<xsl:value-of select="//LABEL[@name=$label]"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="@path"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="DATA[@service='CATEGORY']" mode="label">
		<xsl:value-of select="/RESPONSE/RESULTS//CATEGORY[UNIQUENAME = current()/@path]/LABEL"/>
	</xsl:template>
	<xsl:template match="DATA[@service='DEPENDENCY']" mode="label">
		<xsl:param name="type" select="@type"/>
		<xsl:param name="label" select="//DEPENDENCYTYPE[TYPE = $type]/DENOMINATION/LABEL[@languageID = //NECTIL/language]"/>
		<xsl:choose>
			<xsl:when test="$label != ''">
				<xsl:value-of select="$label"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$type"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="CONTACT | MEDIA" mode="label">
		<xsl:apply-templates select="." mode="title"/>
	</xsl:template>
	<xsl:template match="*[@type='CATEGORY']" mode="label">
		<xsl:value-of select="/RESPONSE/RESULTS//CATEGORY[UNIQUENAME = current()/@path]/LABEL"/>
	</xsl:template>
	<xsl:template match="*[@type='ENUM']" mode="label">
		<xsl:choose>
			<xsl:when test="//LABEL[@name = current()/@path]">
				<xsl:value-of select="//LABEL[@name = current()/@path]"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="@path"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="*[@type='MEDIA']" mode="label">
		<xsl:value-of select="//LABEL[@name = current()/@path]"/>
	</xsl:template>
	<xsl:template match="*[@type='COUNTRY']" mode="label">
		<xsl:value-of select="//LABEL[@name='country']"/>
	</xsl:template>
	<xsl:template match="*[@type='LIST']" mode="label">
		<xsl:value-of select="//LABEL[@name=current()/@path]"/>
	</xsl:template>
	<xsl:template match="GROUP" mode="label">
		<xsl:value-of select="@class"/>
	</xsl:template>
	<xsl:template match="DATA[@type='DESCRIPTION'] | DATAS" mode="label">
		<xsl:variable name="label" select="current()/@label"/>
		<xsl:value-of select="/RESPONSE/RESULTS/LABEL[@name=$label]"/>
	</xsl:template>
	
</xsl:stylesheet>
