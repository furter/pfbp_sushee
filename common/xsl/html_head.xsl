<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions génériques pour générer
		- des attributs du nœuds html
		- classe active
		- des liens
		- des liste de lient / item de navigation
		- lien en savoir plus
		-->
	<!-- -->
	<!-- attribut du nœud html -->
	<xsl:template name="attribute_html">
		<xsl:attribute name="lang">
			<xsl:value-of select="//RESULTS/LANGUAGE[@ID = //NECTIL/language]/ISO1"/>
		</xsl:attribute>
	</xsl:template>
	<!-- -->
	<!-- nœud HEAD -->
	<xsl:template name="html_head">
		<head>
			<meta name="description" content="{$website/DESCRIPTIONS/DESCRIPTION/CUSTOM/METADESCRIPTION}"/>
			<meta name="keywords">
				<xsl:attribute name="content">
					<xsl:apply-templates select="$website" mode="title"/>
					<xsl:if test="$website/@ID != $media/@ID">
						<xsl:text> &gt; </xsl:text>
						<xsl:apply-templates select="$media" mode="title"/>
					</xsl:if>
					<xsl:text> - </xsl:text>
					<xsl:for-each select="$website/DESCRIPTIONS/DESCRIPTION/CUSTOM/METAKEYWORD">
						<xsl:value-of select="."/>
						<xsl:call-template name="add_dash_if_not_last"/>
					</xsl:for-each>
				</xsl:attribute>
			</meta>
			<meta name="date" content="{/RESPONSE/URL/today}"/>
			<meta name="content-language" content="{/RESPONSE/NECTIL/language}"/>
			<meta name="language" content="{/RESPONSE/NECTIL/language}"/>
			<meta name="dateofLastModification" content="{$media/INFO/MODIFICATIONDATE}"/>
			<meta name="robots" content="index,follow"/>
			<meta name="googlebot" content="index,follow"/>
			<meta name="content-type" content="text/html"/>
			<title>
				<xsl:apply-templates select="$website" mode="title"/>
				<xsl:if test="$media and $website/@ID != $media/@ID">
					<xsl:text> - </xsl:text>
					<xsl:apply-templates select="$media" mode="title"/>
				</xsl:if>
			</title>
			<link rel="stylesheet" type="text/css" href="styles.css"/>
			<xsl:if test="//URL/style=1">
				<link rel="stylesheet" type="text/css" href="../Public-2011-02-07/styles.css"/>
			</xsl:if>
			<xsl:apply-templates select="$media" mode="specific_head_content_init"/>
			<script type="text/javascript" src="{$public}script.js"> </script>
			<script type="text/javascript" src="/common/js/jquery.min.js"> </script>
			<link rel="icon" href="" type="image/x-icon"/>
			<!-- si besoin d'ajouter du contenu spécifique dans le header : styles, scripts, ... -->
			<xsl:apply-templates select="$website" mode="specific_head_content"/>
			<xsl:apply-templates select="$media" mode="specific_head_content"/>
		</head>
	</xsl:template>
	<xsl:template match="MEDIA" mode="specific_head_content">
		
	</xsl:template>
	<xsl:template match="MEDIA" mode="specific_head_content_init">
		
	</xsl:template>
</xsl:stylesheet>
