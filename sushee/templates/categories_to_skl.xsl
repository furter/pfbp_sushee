<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/templates/categories_to_skl.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="no" encoding="utf-8" omit-xml-declaration="yes"/>
	
	<xsl:param name="source-language" select="/RESPONSE/XLIFF-CONFIG/source-language/@ID"/>
	<xsl:param name="destination-language" select="/RESPONSE/XLIFF-CONFIG/destination-language/@ID"/>
	
	<xsl:template match="/">
		<xsl:apply-templates select="//CATEGORY"/>
	</xsl:template>
	
	<xsl:template match="CATEGORY">
		<UPDATE>
			<CATEGORY ID="{@ID}" fatherID="{@fatherID}">
				<!--xsl:apply-templates select="LABEL[@languageID!=$destination-language]"/-->
				<LABEL languageID="{$destination-language}">%%%category<xsl:value-of select="@ID"/>%%%<xsl:text>
</xsl:text></LABEL>
			</CATEGORY>
		</UPDATE>
	</xsl:template>
	
	<xsl:template match="LABEL">
		<LABEL languageID="{@languageID}"><xsl:value-of select="."/></LABEL>
	</xsl:template>
</xsl:stylesheet>