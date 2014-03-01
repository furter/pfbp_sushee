<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:output method="html" indent="yes"/>
  <xsl:include href="params.xsl"/>
  
  <xsl:template match="/RESPONSE">
  	<html>
  		<head>
  			<title>nectil exemple 1</title>
  			<style>p{font-size:9;}</style>
  			<link rel="stylesheet" type="text/css" href="../OS/test.css"/>
  		</head>
  		<body>
  		
			<ul>
				<li>
					<xsl:choose>
						<xsl:when test="$language = 'fre'">French</xsl:when>
						<xsl:otherwise><a href="example.php?language=fre">French</a></xsl:otherwise>
					</xsl:choose>
				</li>
				<li>
					<xsl:choose>
						<xsl:when test="$language = 'eng'">English</xsl:when>
						<xsl:otherwise><a href="example.php?language=eng">English</a></xsl:otherwise>
					</xsl:choose>
				</li>
			</ul>
			
  			<xsl:for-each select="RESULTS/MEDIA">
  			
  				<h1><a href="example2.php?ID={@ID}"><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/></a></h1>
				<h2><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/HEADER"/></h2>
				
  			</xsl:for-each>
			
			
  		</body>
  	</html>
  </xsl:template>
</xsl:stylesheet>