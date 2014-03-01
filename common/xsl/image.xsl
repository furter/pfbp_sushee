<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions de base pour générer:
		- générer/redimensionner des images à la volée
		- redimensionner des images en fonction des proportions
	-->
	<!-- images -->
	<!-- -->
	<xsl:template match="*" mode="visual_proportion">
		<xsl:param name="visual">
			<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/CUSTOM/VISUAL"/>
		</xsl:param>
		<xsl:param name="width"/>
		<xsl:param name="height"/>
		<xsl:param name="alt">
			<xsl:apply-templates select="." mode="title"/>
			<xsl:if test="DESCRIPTIONS/DESCRIPTION/COPYRIGHT != ''">
				<xsl:text> - </xsl:text>
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/COPYRIGHT"/>
			</xsl:if>
		</xsl:param>
		<xsl:param name="proportion" select="DESCRIPTIONS/DESCRIPTION/CUSTOM/VISUAL/@width div DESCRIPTIONS/DESCRIPTION/CUSTOM/VISUAL/@height"/>
		<xsl:choose>
			<xsl:when test="$proportion &gt; 1.5">
				<xsl:call-template name="image">
					<xsl:with-param name="width" select="$width"/>
					<xsl:with-param name="visual" select="$visual"/>
					<xsl:with-param name="alt" select="$alt"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="image">
					<xsl:with-param name="height" select="$height"/>
					<xsl:with-param name="visual" select="$visual"/>
					<xsl:with-param name="alt" select="$alt"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="MEDIA" mode="visual">
		<xsl:param name="width"/>
		<xsl:param name="class"/>
		<xsl:param name="height"/>
		<xsl:param name="visual">
			<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/CUSTOM/VISUAL"/>
		</xsl:param>
		<xsl:variable name="alt">
			<xsl:apply-templates select="." mode="title"/>
			<xsl:if test="DESCRIPTIONS/DESCRIPTION/COPYRIGHT != ''">
				<xsl:text> - </xsl:text>
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/COPYRIGHT"/>
			</xsl:if>
		</xsl:variable>
		<xsl:if test="$visual != ''">
			<xsl:call-template name="image">
				<xsl:with-param name="width" select="$width"/>
				<xsl:with-param name="height" select="$height"/>
				<xsl:with-param name="visual" select="$visual"/>
				<xsl:with-param name="class" select="$class"/>
				<xsl:with-param name="alt" select="$alt"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<xsl:template match="FILE" mode="visual">
		<xsl:param name="width"/>
		<xsl:param name="height"/>
		<xsl:param name="class"/>
		<xsl:param name="visual">
			<xsl:value-of select="@path"/>
		</xsl:param>
		<xsl:param name="alt">
			<xsl:apply-templates select="ancestor::MEDIA[1]" mode="title"/>
			<xsl:text> - </xsl:text>
			<xsl:value-of select="@shortname"/>
			<xsl:if test="../../../COPYRIGHT != ''">
				<xsl:text> © </xsl:text>
				<xsl:value-of select="../../../COPYRIGHT"/>
			</xsl:if>			
		</xsl:param>
		<xsl:if test="$visual != ''">
			<xsl:call-template name="image">
				<xsl:with-param name="width" select="$width"/>
				<xsl:with-param name="height" select="$height"/>
				<xsl:with-param name="visual" select="$visual"/>
				<xsl:with-param name="class" select="$class"/>
				<xsl:with-param name="alt" select="$alt"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<xsl:template name="image">
		<xsl:param name="width">200</xsl:param>
		<xsl:param name="height"/>
		<xsl:param name="alt"/>
		<xsl:param name="visual"/>
		<xsl:param name="class"/>
		<xsl:param name="img_id"/>
		<img>
			<xsl:attribute name="src">
				<xsl:value-of select="$common"/>
				<xsl:text>img_resize.php?path=</xsl:text>
				<xsl:value-of select="$visual"/>
				<xsl:choose>
					<xsl:when test="$height != ''">
						<xsl:text>&amp;height=</xsl:text>
						<xsl:value-of select="$height"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>&amp;width=</xsl:text>
						<xsl:value-of select="$width"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:attribute name="alt">
				<xsl:value-of select="$alt"/>
			</xsl:attribute>
			<xsl:attribute name="title">
				<xsl:value-of select="$alt"/>
			</xsl:attribute>
			<xsl:if test="$class != ''">
				<xsl:attribute name="class">
					<xsl:value-of select="$class"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="$img_id != ''">
				<xsl:attribute name="id">
					<xsl:value-of select="$img_id"/>
				</xsl:attribute>
			</xsl:if>
		</img>
	</xsl:template>
	<!-- dossier d'image > ajouter autant de nom de champs que souhaité -->
	<xsl:template match="VISUAL_FOLDER" mode="visual_folder">
		<xsl:param name="name" select="."/>
		<xsl:param name="alt">
				<xsl:apply-templates select="../../../../../MEDIA" mode="title"/>
				<xsl:if test="../../COPYRIGHT != ''">
					<xsl:text> © </xsl:text>
					<xsl:value-of select="../../COPYRIGHT"/>
				</xsl:if>
		</xsl:param>
		<xsl:for-each select="../TREE[@path = $name]/FILE">
			<xsl:sort select="@shortname" data-type="text" order="ascending"/>
			<div id="img_{ancestor::MEDIA/@ID}_{@shortname}" class="img_container">
				<xsl:apply-templates select="." mode="visual_proportion">
					<xsl:with-param name="visual" select="@path"/>
					<xsl:with-param name="height" select="100"/>
					<xsl:with-param name="proportion" select=" @width div @height"/>
					<xsl:with-param name="alt" select="$alt"/>
				</xsl:apply-templates>
				<xsl:apply-templates select="." mode="tools"/>
			</div>
		</xsl:for-each>
	</xsl:template>
	

</xsl:stylesheet>