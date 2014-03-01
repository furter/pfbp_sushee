<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions de base pour générer:
		- titrage
		- des caractères de séparation entre des éléments (br espace virgule)
		- titrage image
		- titrage html (h1,…)
	-->
	<!-- -->
	<!-- séparation -->
	<xsl:template name="add_something_if_not_last">
		<xsl:param name="something"/>
		<xsl:if test="position()!=last()">
			<xsl:value-of select="$something"/>
		</xsl:if>
	</xsl:template>
	<xsl:template name="add_br_if_not_last">
		<xsl:if test="position()!=last()">
			<br/>
		</xsl:if>
	</xsl:template>
	<xsl:template name="add_coma_if_not_last">
		<xsl:call-template name="add_something_if_not_last">
			<xsl:with-param name="add_something_if_not_last" select="', '"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template name="add_dash_if_not_last">
		<xsl:call-template name="add_something_if_not_last">
			<xsl:with-param name="add_something_if_not_last" select="' - '"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template name="add_space_if_not_last">
		<xsl:call-template name="add_something_if_not_last">
			<xsl:with-param name="add_something_if_not_last" select="' '"/>
		</xsl:call-template>
	</xsl:template>
	<!-- -->
	<!-- titre -->
	<!--     media -->
	<xsl:template match="MEDIA" mode="title">
			<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/SUMMARY"/>
	</xsl:template>
	
	<!-- à modifier en fonction de la configuration des médias -->
	<xsl:template match="MEDIA[DESCRIPTIONS/DESCRIPTION/SUMMARY = '']" mode="title">
		<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/>
	</xsl:template>
	<!--    contact -->
	<xsl:template match="CONTACT" mode="title">
		<xsl:value-of select="concat(INFO/FIRSTNAME, ' ', INFO/LASTNAME)"/>
	</xsl:template>
	<xsl:template match="CONTACT[INFO/CONTACTTYPE = 'PM']" mode="title">
		<xsl:value-of select="INFO/DENOMINATION"/>
	</xsl:template>
	<!-- -->
	<!-- titrages html -->
	<xsl:template match="*" mode="h1">
		<h1>
			<xsl:apply-templates select="." mode="title"/>
		</h1>
	</xsl:template>
	<xsl:template match="*" mode="h2">
		<h2>
			<xsl:apply-templates select="." mode="title"/>
		</h2>
	</xsl:template>
	<xsl:template match="*" mode="h3">
		<h3>
			<xsl:apply-templates select="." mode="title"/>
		</h3>
	</xsl:template>
	<xsl:template match="*" mode="h4">
		<h4>
			<xsl:apply-templates select="." mode="title"/>
		</h4>
	</xsl:template>
	<xsl:template match="*" mode="h5">
		<h5>
			<xsl:apply-templates select="." mode="title"/>
		</h5>
	</xsl:template>
	<xsl:template match="*" mode="h6">
		<h6>
			<xsl:apply-templates select="." mode="title"/>
		</h6>
	</xsl:template>
	<!-- -->
	<!-- titrage image -->
	<!--xsl:template name="titrage">
		<xsl:param name="text"/>
		<xsl:param name="color"/>
		<xsl:param name="size"/>
		<xsl:param name="font" select="'light'"/>
		<xsl:param name="first">true</xsl:param>
		<xsl:variable name="real_text">
			<xsl:value-of select="translate($text,$apos,$new_apos)"/>
		</xsl:variable>
		<img alt="" src="{//NECTIL/host}/common/titrage.php?title={$real_text}&amp;color={$color}&amp;size={$size}&amp;font={$font}">
			<xsl:if test="$first='true'">
				<xsl:attribute name="alt">
					<xsl:value-of select="$text"/>
				</xsl:attribute>
			</xsl:if>
		</img>
	</xsl:template-->
	<xsl:template name="titrage">
		<xsl:param name="text"/>
		<xsl:param name="color"/>
		<xsl:param name="size"/>
		<xsl:param name="font" select="'light'"/>
		<xsl:param name="first">true</xsl:param>
		<xsl:variable name="real_text">
			<xsl:choose>
				<xsl:when test="substring-before($text,' ')">
					<xsl:value-of select="substring-before($text,' ')"/>
					<xsl:text> </xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$text"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<img alt="" src="{//NECTIL/host}/common/titrage.php?title={$real_text}&amp;color={$color}&amp;size={$size}&amp;font={$font}">
			<xsl:if test="$first='true'">
				<xsl:attribute name="alt">
					<xsl:value-of select="$text"/>
				</xsl:attribute>
			</xsl:if>
		</img>
		<xsl:if test="substring-after($text,' ')!=''">
			<xsl:call-template name="titrage">
				<xsl:with-param name="text" select="substring-after($text,' ')"/>
				<xsl:with-param name="color" select="$color"/>
				<xsl:with-param name="size" select="$size"/>
				<xsl:with-param name="font" select="$font"/>
				<xsl:with-param name="first">false</xsl:with-param>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
