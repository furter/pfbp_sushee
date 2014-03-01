<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour générer des attributs en fonction du nœud et de son type
	-->
	<!-- -->
	<!-- ajout d'attribut à nœudw -->
	<xsl:template match="MEDIA" mode="attribute">
		<xsl:attribute name="class">
			<xsl:value-of select="INFO/MEDIATYPE"/>
		</xsl:attribute>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/PAGETOCALL != '']" mode="attribute">
		<xsl:attribute name="class">
			<xsl:value-of select="substring-before(INFO/PAGETOCALL, '.php')"/>
		</xsl:attribute>
	</xsl:template>
	<!-- pour la affichage automatisé -->
	<xsl:template name="class_content">
		<xsl:if test="@path">
			<xsl:value-of select="@path"/>
		</xsl:if>
		<xsl:if test="@type">
			<xsl:value-of select="@type"/>
		</xsl:if>
		<xsl:if test="ancestor::GROUP">
			<xsl:text> </xsl:text>
			<xsl:value-of select="ancestor::GROUP/@class"/>
		</xsl:if>
		<xsl:if test="name() = 'GROUP' and @class != ''">
			<xsl:text> </xsl:text>
			<xsl:value-of select="@class"/>
		</xsl:if>
		<xsl:if test="position() = last()">
			<xsl:text> last</xsl:text>
		</xsl:if>
	</xsl:template>
	<xsl:template name="add_class">
		<xsl:param name="class">
			<xsl:call-template name="class_content"/>
		</xsl:param>
		<xsl:if test="$class != ''">
			<xsl:attribute name="class">
				<xsl:value-of select="$class"/>
			</xsl:attribute>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
