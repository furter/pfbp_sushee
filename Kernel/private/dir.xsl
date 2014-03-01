<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/private/dir.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="html" indent="yes" encoding="utf-8"/>
	<xsl:param name="language">en</xsl:param>
	<xsl:template match="/DIR">
	<html>
	<head>
		<title>Directory content</title>
		<style>
			body{font-family:sans-serif;margin:0;}
			h1{background-color:gray;padding:5px;color:white;border-bottom:1px solid black;}
			ul{list-style:none;width:60%;}
			li{padding:3px;border-bottom:1px solid black;padding-bottom:6px;border-left:1px solid black;position:relative;}
			li.pair{background-color:lightgrey;}
			li strong{color:darkslategray;}
			span.explain{font-size:80%;font-style:italic;}
			img.icon{vertical-align:middle;position:absolute;right:10;}
			p.bottom_page{border:1px solid lightgrey;background-color:gray;color:white;padding:3px;}
		</style>
	</head>
	<body>
		<h1>Directory content</h1>
		<ul>
			<xsl:for-each select="FILE">
				<li>
					<xsl:if test="position() mod 2 = 1"><xsl:attribute name="class">pair</xsl:attribute></xsl:if>
					<xsl:if test="position()=1"><xsl:attribute name="style">border-top:1px solid black;</xsl:attribute></xsl:if>
					&#160;<strong>&gt;</strong>
					<xsl:value-of select="NAME"/>&#160;-&#160;<span class="explain"><xsl:value-of select="INFO[@languageID=$language]"/></span>
					<xsl:if test="ICON!=''"><img class="icon" height="25" src="{ICON}"/></xsl:if>
				</li>
			</xsl:for-each>
		</ul>
		<p class="bottom_page">Nectil SA</p>
	</body>
	</html>
	</xsl:template>

</xsl:stylesheet>
