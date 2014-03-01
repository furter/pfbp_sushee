<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/xsl/contact.inc.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:fo="http://www.w3.org/1999/XSL/Format">
	<xsl:output method="xml" indent="yes" encoding="utf-8"/>

	<xsl:template match="CONTACT" mode="label">
		<xsl:choose>
			<xsl:when test="INFO/CONTACTTYPE = 'PP'">
				<xsl:call-template name="label-pp"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="label-pm"/>
			</xsl:otherwise>
		</xsl:choose>	
	</xsl:template>

	<xsl:template name="label-pp">
		<xsl:param name="contact" select="."/>
		<xsl:param name="info" select="$contact/INFO"/>
		<xsl:if test="$info/LASTNAME!=''"><xsl:value-of select="$info/LASTNAME"/><xsl:text> </xsl:text></xsl:if>
		<xsl:if test="$info/DENOMINATION!=''">"<xsl:value-of select="$info/DENOMINATION"/>"<xsl:text> </xsl:text></xsl:if>
		<xsl:if test="$info/FIRSTNAME!=''"><xsl:value-of select="$info/FIRSTNAME"/></xsl:if>
	</xsl:template>
	
	<xsl:template name="label-pm">
		<xsl:param name="contact" select="."/>
		<xsl:param name="info" select="$contact/INFO"/>
		<xsl:param name="titlevalue" select="$info/TITLE"/>
		<xsl:variable name="titlelabel" select="//ITEM[@value=$titlevalue]/@label"/>
		<xsl:variable name="title">
			<xsl:choose>
				<xsl:when test="$titlelabel!=''"><xsl:value-of select="$titlelabel"/></xsl:when>
				<xsl:otherwise><xsl:value-of select="$titlevalue"/></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:choose>
			<xsl:when test="$info/DENOMINATION!=''">
				<xsl:value-of select="$info/DENOMINATION"/>
				<xsl:if test="$title!=''"><xsl:text> </xsl:text><xsl:value-of select="$title"/></xsl:if>
			</xsl:when>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="CONTACT" mode="city">
		<xsl:if test="INFO/POSTALCODE!=''"><xsl:value-of select="INFO/POSTALCODE"/><xsl:text> - </xsl:text></xsl:if>
		<xsl:if test="INFO/CITY!=''"><xsl:value-of select="INFO/CITY"/></xsl:if>	
	</xsl:template>
	
	<xsl:template match="CONTACT" mode="country">
		<xsl:variable name="country" select="INFO/COUNTRYID"/>
		<xsl:value-of select="//COUNTRY[@ID=$country]/LABEL"/>
	</xsl:template>	
	
	<xsl:template match="CONTACT" mode="vat">
		<xsl:text>TVA: </xsl:text>
		<xsl:choose>
			<xsl:when test="INFO/VAT!=''"><xsl:value-of select="INFO/VAT"/></xsl:when>
			<xsl:otherwise><xsl:text>N/A</xsl:text></xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>
