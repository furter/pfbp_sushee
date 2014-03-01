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

`/sushee/templates/keyring.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="UTF-8" indent="yes" method="html" />

	<xsl:param name="keyring" select="/RESPONSE/RESULTS[@name='keyring']/KEYRING[1]"/>
	<xsl:param name="user" select="/RESPONSE/RESULTS[@name='user']/CONTACT[1]"/>
	<xsl:param name="sender" select="/RESPONSE/RESULTS[@name='sender']/CONTACT[1]"/>
	<xsl:param name="template" select="/RESPONSE/RESULTS[@name='template']/TEMPLATE[1]"/>
	<xsl:param name="password" select="/RESPONSE/RESULTS[@name='password']/PASSWORD[1]"/>
	
	<xsl:param name="desc-language">
		<xsl:value-of select="$template/DESCRIPTIONS/DESCRIPTION[1]/LANGUAGEID"/>
	</xsl:param>

	<xsl:template match="/RESPONSE">
		<html>
			<body>
				<xsl:apply-templates select="$template/DESCRIPTIONS/DESCRIPTION[LANGUAGEID=$desc-language]/BODY"/>
			</body>
		</html>
	</xsl:template>
	
	<!-- HTML STYLES -->
	
	<xsl:template match="DESCRIPTIONS/DESCRIPTION/*[CSS]">
		<div class="css {name()}">
			<xsl:apply-templates select="CSS"/>
		</div>
	</xsl:template>
	
	<xsl:template match="CSS">
		<xsl:apply-templates select="*"/>
	</xsl:template>
	
	<xsl:template match="CSS//*">
		<xsl:element name="{name()}">
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates select="node()"/>
		</xsl:element>
	</xsl:template>
	
	<xsl:template match="CSS//a">
		<xsl:element name="{name()}">
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates select="node()"/>
		</xsl:element>
	</xsl:template>
	
	<xsl:template match="CSS//li[not(node())]">
		<xsl:element name="p">
			<xsl:text>&#160;</xsl:text>
		</xsl:element>
	</xsl:template>
	
	<xsl:template match="CSS/p[not(node())]">
		<xsl:element name="{name()}">
			<xsl:copy-of select="@*"/>
			<xsl:text>&#160;</xsl:text>
		</xsl:element>
	</xsl:template>
	
	<xsl:template match="CSS/*/img">
		<xsl:element name="{local-name()}">
			<xsl:variable name="style" select="@style"/>
			<xsl:attribute name="style">
				<xsl:value-of select="$style" />
				<xsl:choose>
					<xsl:when test="$style = 'float:right;'">margin-right:0px;</xsl:when>
					<xsl:when test="$style = 'float:left;'">margin-left:0px;</xsl:when>
					<xsl:when test="$style = 'vertical-align:middle;'">margin-left:0px;margin-right:0px;</xsl:when>
					<xsl:otherwise>truc</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
        	<xsl:copy-of select="./attribute::*[name(.) != 'style']"/>
        	<xsl:apply-templates/>
      	</xsl:element>
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
		<a href="{/RESPONSE/NECTIL/nectil_url}/">
			<xsl:value-of select="/RESPONSE/NECTIL/nectil_url"/>
			<xsl:text>/</xsl:text>
		</a>
	</xsl:template>
	
	<xsl:template match="KEY[@name='apps']">
		<a href="{/RESPONSE/NECTIL/nectil_url}/apps/">
			<xsl:value-of select="/RESPONSE/NECTIL/nectil_url"/>
			<xsl:text>/apps/</xsl:text>
		</a>
	</xsl:template>

	<xsl:template match="KEY[@name='secure']">
		<a href="{/RESPONSE/NECTIL/nectil_url}/secure">
			<xsl:value-of select="/RESPONSE/NECTIL/nectil_url"/>
			<xsl:text>/secure</xsl:text>
		</a>
	</xsl:template>
	
	<xsl:template match="KEY[@name='public']">
		<a href="{/RESPONSE/NECTIL/public_url}">
			<xsl:value-of select="/RESPONSE/NECTIL/public_url"/>
		</a>
	</xsl:template>

</xsl:stylesheet>
