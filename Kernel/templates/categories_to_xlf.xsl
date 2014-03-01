<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/templates/categories_to_xlf.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="no" encoding="utf-8" omit-xml-declaration="yes"/>
	
	<xsl:param name="source-language" select="/RESPONSE/XLIFF-CONFIG/source-language/@ID"/>
	<xsl:param name="source-language-iso1" select="/RESPONSE/XLIFF-CONFIG/source-language/@ISO1"/>
	<xsl:param name="destination-language" select="/RESPONSE/XLIFF-CONFIG/destination-language/@ID"/>
	<xsl:param name="destination-language-iso1" select="/RESPONSE/XLIFF-CONFIG/destination-language/@ISO1"/>
	
	<xsl:template match="/">
		<xsl:apply-templates select="//CATEGORY"/>
	</xsl:template>
	
	<xsl:template match="CATEGORY">
		<trans-unit id="category{@ID}">
			<source xml:lang="{$source-language-iso1}"><xsl:value-of select="LABEL[@languageID=$source-language]"/></source>
			<target state="new" xml:lang="{$destination-language-iso1}"><xsl:value-of select="LABEL[@languageID=$source-language]"/></target>
		</trans-unit>
	</xsl:template>
</xsl:stylesheet>