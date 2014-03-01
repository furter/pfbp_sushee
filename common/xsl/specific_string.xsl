<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour
	 	- gérer le pluriel d'une labelisation
	-->
	<!-- -->
	<!-- accords -->
	<xsl:template name="check_pluriel">
		<xsl:param name="zero"/>
		<xsl:param name="un"/>
		<xsl:param name="plusieurs"/>
		<xsl:param name="value"/>
		<xsl:choose>
			<xsl:when test="$value = 0">
				<xsl:value-of select="$zero"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$value"/>
				<xsl:text> </xsl:text>
				<xsl:choose>
					<xsl:when test="$value = 1">
						<xsl:value-of select="$un"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$plusieurs"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>