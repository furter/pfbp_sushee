<?xml version="1.0" encoding="UTF-8" ?>
<!--
	keyring
	Created by Verdeyen Boris on 2009-04-10.
	Copyright (c) 2009 Nectil SA. All rights reserved.
-->

<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/templates/keyring-text.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="text" />

	<xsl:param name="keyring" select="/RESPONSE/RESULTS[@name='keyring']/KEYRING[1]"/>
	<xsl:param name="user" select="/RESPONSE/RESULTS[@name='user']/CONTACT[1]"/>
	<xsl:param name="template" select="/RESPONSE/RESULTS[@name='template']/TEMPLATE[1]"/>
	<xsl:param name="password" select="/RESPONSE/RESULTS[@name='password']/PASSWORD[1]"/>
	
	<xsl:param name="desc-language">
		<xsl:value-of select="$template/DESCRIPTIONS/DESCRIPTION[1]/LANGUAGEID"/>
	</xsl:param>

	<xsl:template match="/RESPONSE">
		<xsl:apply-templates select="$template/DESCRIPTIONS/DESCRIPTION[LANGUAGEID=$desc-language]/BODY"/>
	</xsl:template>
	
	<!-- HTML STYLES -->
	
	<xsl:template match="DESCRIPTIONS/DESCRIPTION/*[CSS]">
		<xsl:apply-templates select="CSS"/>
	</xsl:template>
	
	<xsl:template match="CSS">
		<xsl:apply-templates select="*"/>
	</xsl:template>
	
	<xsl:template match="CSS//*">
		<xsl:apply-templates select="node()"/>
	</xsl:template>
	
	<xsl:template match="CSS/p">
		<xsl:if test="./text()!='' or ./*"><xsl:apply-templates/><xsl:text>&#x0a;&#x0a;</xsl:text></xsl:if>
	</xsl:template>
	
	<xsl:template match="CSS/ul">
		<xsl:for-each select="li">&#160;&#160;&#160;*<xsl:apply-templates/><xsl:text>&#x0a;</xsl:text></xsl:for-each>
		<xsl:text>&#x0a;</xsl:text>
	</xsl:template>
	
	<xsl:template match="CSS//br" priority="1.6">
		<xsl:text>&#x0a;</xsl:text>
	</xsl:template>
	
	<xsl:template match="CSS//a">
		<xsl:value-of select="@href"/>
	</xsl:template>
	
	<xsl:template match="CSS/*/img">
		<xsl:value-of select="@src"/>
	</xsl:template>
	
	<!-- KEYWORDS -->
	
	<xsl:template match="KEY[@name='who']">
		<xsl:value-of select="$user/INFO/FIRSTNAME"/>
		<xsl:text> </xsl:text>
		<xsl:value-of select="$user/INFO/LASTNAME"/>
	</xsl:template>
	
	<xsl:template match="KEY[@name='sender']">
		<xsl:value-of select="$sender/INFO/FIRSTNAME"/>
		<xsl:text> </xsl:text>
		<xsl:value-of select="$sender/INFO/LASTNAME"/>
	</xsl:template>
	
	<xsl:template match="KEY[@name='login']">
		<xsl:value-of select="$user/INFO/EMAIL1"/>
	</xsl:template>
	
	<xsl:template match="KEY[@name='password']">
		<xsl:choose>
			<xsl:when test="$password">
				<xsl:value-of select="$password"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="$template/DESCRIPTIONS/DESCRIPTION[LANGUAGEID=$desc-language]/CUSTOM/formerpassword"/>
			</xsl:otherwise>
		</xsl:choose>
		
	</xsl:template>
	
	<xsl:template match="KEY[@name='url']">
		<xsl:value-of select="/RESPONSE/NECTIL/nectil_url"/>
		<xsl:text>/</xsl:text>
	</xsl:template>

	<xsl:template match="KEY[@name='apps']">
		<xsl:value-of select="/RESPONSE/NECTIL/nectil_url"/>
		<xsl:text>/apps/</xsl:text>
	</xsl:template>

	<xsl:template match="KEY[@name='secure']">
		<xsl:value-of select="/RESPONSE/NECTIL/nectil_url"/>
		<xsl:text>/secure</xsl:text>
	</xsl:template>
	
	<xsl:template match="KEY[@name='public']">
		<xsl:value-of select="/RESPONSE/NECTIL/public_url"/>
	</xsl:template>
	
</xsl:stylesheet>
