<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/public/configuration.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<!--xsl:import href="common.xsl"/-->
	<!--xsl:import href="params.xsl"/-->
	<xsl:output method="html" indent="yes" encoding="utf-8"/>
		
	<xsl:template match="/RESPONSE">	
	
		<html>
			<head>
				<title>Nectil Media - Configuration</title>
			</head>
			<body aLink="#003366" bgColor="#ffffff" leftMargin="0" link="#003366" text="#003366" topMargin="0" vLink="#003366" marginheight="10" marginwidth="10">
		
		
				<div>
					<h2>Media Languages</h2>
					<xsl:for-each select="RESULTS[@name='languages']/LANGUAGE">		
						<b><xsl:value-of select="UNIVERSAL"/></b><xsl:if test="PUBLISHED='1'">(Published)</xsl:if><br/>
					</xsl:for-each>	
				</div>
				
		
		
		
				<div>
					<h2>Media Configuration</h2>
					<xsl:for-each select="RESULTS[@name='mediatype']/MEDIATYPE">	

							<!--
							<xsl:for-each select="*">
								<xsl:value-of select="."/>
							</xsl:for-each>	
							-->
							<p>
							<b><xsl:value-of select="UNIQUENAME"/></b><br/>
							<xsl:if test="ISCOMPOSITE='1'">Is composite<br/></xsl:if>
							<xsl:if test="ISPUBLI='1'">Is Publication date sensitive<br/></xsl:if>
							<xsl:if test="ISEVENT='1'">Is Event date sensitive<br/></xsl:if>
							<xsl:if test="ISTEMPLATE='1'">Is Template sensitive<br/></xsl:if>
							<xsl:if test="ISPAGETOCALL='1'">Is Page to call sensitive<br/></xsl:if>
							<xsl:if test="CSSFILE!=''">Have a particular CSS<br/></xsl:if>
							</p>
							

					</xsl:for-each>
				</div>
				
				
				
				<div>
					<h2>Dependency Configuration</h2>
					<xsl:for-each select="RESULTS[@name='dependencytype']/DEPENDENCY_TYPE">	
							<b><xsl:value-of select="TYPE"/></b><br/>
					</xsl:for-each>
				</div>
				
			</body>
		</html>
		
	</xsl:template>
	
</xsl:stylesheet>