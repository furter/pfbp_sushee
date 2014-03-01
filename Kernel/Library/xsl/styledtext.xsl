<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/xsl/styledtext.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	
	<xsl:import href="string.xsl" />
	
	<!-- CLEANUP EMPTY li -->
	
	<xsl:template match="CSS//li[not(node())]">
		<xsl:element name="p">
			<xsl:text>&#160;</xsl:text>
		</xsl:element>
	</xsl:template>
	
	<!-- EMPTY TAG IN CSS -->
	
	<xsl:template match="CSS/*[not(text()) and not(*) and name() != 'img' and name() != 'br']">
		<xsl:element name="{name()}">
			<xsl:copy-of select="@*"/>
			<xsl:text>&#160;</xsl:text>
		</xsl:element>
	</xsl:template>

	<!-- NO TEXT AND HAVE CHILD IN CSS (ul,...) -->
	
	<xsl:template match="CSS//*[not(text()) and (*)]">
		<xsl:element name="{local-name()}">
	    	<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
	  	</xsl:element>
	</xsl:template>

	<!-- BR -->
	
	<xsl:template match="CSS//br">
		<xsl:copy-of select="."/>
	</xsl:template>

	<!-- IMG -->
	
	<xsl:template match="CSS//img">
		<xsl:element name="{local-name()}">
	    	<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
	  	</xsl:element>
	</xsl:template>
	
	<!-- TABLE -->
	
	<xsl:template match="CSS//table[@class='tablestyle']">
		<xsl:copy-of select="."/>
	</xsl:template>
	

	<!-- NOT EMPTY TAG -->
	
	<!--xsl:template match="CSS/*[text()]">
		<xsl:copy-of select="."/>
	</xsl:template-->
	
	<xsl:template match="CSS//*[text()]">
		<xsl:element name="{local-name()}">
        	<xsl:copy-of select="./attribute::*"/>
			<xsl:apply-templates/>
      	</xsl:element>
	</xsl:template>
	
	<!-- TABULATION -->
	
	<xsl:template match="CSS//text()">
		<xsl:call-template name="replace-string">
			<xsl:with-param name="text" select="."/>
			<xsl:with-param name="from" select="'&#9;'"/>
			<xsl:with-param name="to" select="'&#160;&#160;'"/>
		</xsl:call-template>
	</xsl:template>
	
	<!-- NECTIL URL -->
	
	<xsl:template match="CSS//nectil_url">
		<xsl:choose>
		    <xsl:when test="@pagetocall">
		      <a href='{@pagetocall}?ID={@ID}'><xsl:value-of select="."/></a>
		    </xsl:when>
			<xsl:otherwise>
		      <a href='{@mediatype}.php?ID={@ID}'><xsl:value-of select="."/></a>
		    </xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- HREF -->
	
	<xsl:template name="href">
		<xsl:attribute name="href">
			<xsl:choose>
				<xsl:when test="INFO/PAGETOCALL != '' or @pagetocall != ''">
					<xsl:value-of select="INFO/PAGETOCALL | @pagetocall"/>?ID=<xsl:value-of select="@ID"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="INFO/MEDIATYPE | @mediatype"/>.php?ID=<xsl:value-of select="@ID"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
	</xsl:template>
	
</xsl:stylesheet>	
