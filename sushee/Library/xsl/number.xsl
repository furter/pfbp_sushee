<?xml version="1.0"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/xsl/number.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:doc="http://xsltsl.org/xsl/documentation/1.0" xmlns:str="http://xsltsl.org/string">
	
	<xsl:template name="currency">
		<xsl:param name="amount" />
		<xsl:value-of select="$amount"/>
		<xsl:choose>
			<xsl:when test="not(contains($amount,'.'))">
				<xsl:text>.00</xsl:text>
			</xsl:when>
			<xsl:when test="string-length(substring-after($amount,'.')) = 1">
				<xsl:text>0</xsl:text>
			</xsl:when>
		</xsl:choose>
	</xsl:template>
	
	<!-- ROUND (call) -->
	
	<xsl:template name="round">
		<xsl:param name="valeur" select="'0'" />
		<xsl:choose>
		<xsl:when test="ceiling($valeur*100) - $valeur*100 &lt;= 0.5">
			<xsl:value-of select="ceiling($valeur*100) div 100"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="floor($valeur*100) div 100"/>
		</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<!-- ROUND (apply) -->
	
	<xsl:template match="*/text()" mode="round">
		<xsl:choose>
			<xsl:when test="ceiling(.*100) - .*100 &lt;= 0.5">
				<xsl:value-of select="ceiling(.*100) div 100"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="floor(.*100) div 100"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
</xsl:stylesheet>

