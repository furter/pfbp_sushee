<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/templates/workflow.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="html" indent="yes" encoding="utf-8"/>
	<xsl:param name="element" select="/RESPONSE/RESULTS[@name='element']/*[1]"/>
	<xsl:param name="editor" select="/RESPONSE/RESULTS[@name='editor']/CONTACT"/>
	<xsl:param name="submitted_desc" select="($element/DESCRIPTIONS/DESCRIPTION[STATUS='submitted' or STATUS='checked'])[1]"/>
	<xsl:template match="/RESPONSE">
		<html>
			<body>
				<h1 style="font-size:15px;">
					<xsl:value-of select="$submitted_desc/TITLE"/>
				</h1>
				<h2 style="font-size:14px;font-weight:normal;font-style:italic;">
					<xsl:text>submitted by : </xsl:text>
					<xsl:value-of select="$editor/INFO/FIRSTNAME"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="$editor/INFO/LASTNAME"/>
					<xsl:text> on </xsl:text>
					<xsl:value-of select="substring($submitted_desc/MODIFICATIONDATE,9,2)"/>
					<xsl:text>/</xsl:text>
					<xsl:value-of select="substring($submitted_desc/MODIFICATIONDATE,6,2)"/>
					<xsl:text>/</xsl:text>
					<xsl:value-of select="substring($submitted_desc/MODIFICATIONDATE,1,4)"/>
					<xsl:text> at </xsl:text>
					<xsl:value-of select="substring($submitted_desc/MODIFICATIONDATE,12,5)"/>
				</h2>
				
				<p>
					<xsl:text>Preview it here : </xsl:text>
					<xsl:value-of select="/RESPONSE/NECTIL/public_url"/><xsl:value-of select="$element/INFO/MEDIATYPE"/>.php?ID=<xsl:value-of select="$element/@ID"/>&amp;version=<xsl:value-of select="$submitted_desc/@ID"/>
				</p>
				
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>