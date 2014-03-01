<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<xsl:output method="html" indent="yes"/>
	<xsl:include href="params.xsl"/>
	
	<xsl:template match="/RESPONSE">
		<html>
			<head>
				<title>nectil exemple2</title>
			</head>
			<body>
			
				<xsl:call-template name="language_navigation"><xsl:with-param name="bizz">example2.php?ID=<xsl:value-of select="RESULTS/MEDIA[1]/@ID"/></xsl:with-param></xsl:call-template>
			
				<xsl:for-each select="RESULTS/MEDIA">
				
					<h1><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/></h1>
					<h2><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/HEADER"/></h2>
					<p><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/BODY"/></p>
					<!-- the children -->
					<ul>
						<xsl:for-each select="DEPENDENCIES/DEPENDENCY/MEDIA">
							<li><xsl:call-template name="child"/></li>
						</xsl:for-each>
					</ul>
					
				</xsl:for-each>
			
			</body>
		</html>
	</xsl:template>
	
	
	<xsl:template name="child">
			<h1><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/></h1>
			<h2><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/HEADER"/></h2>
			<p><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/BODY"/></p>
		
		<xsl:if test="DESCRIPTIONS/DESCRIPTION/FILES/ICON!=''">
				<img src="{$files_dir}{DESCRIPTIONS/DESCRIPTION/FILES/ICON}"/>
		</xsl:if>
	</xsl:template>
	
	
		
	<xsl:template name="language_navigation">
	<xsl:param name="bizz">error.php?ID=0</xsl:param>
		<ul>
			<li>
				<xsl:choose>
					<xsl:when test="$language = 'fre'">French</xsl:when>
					<xsl:otherwise><a href="{$bizz}&amp;language=fre">French</a></xsl:otherwise>
				</xsl:choose>
			</li>
			<li>
				<xsl:choose>
					<xsl:when test="$language = 'eng'">English</xsl:when>
					<xsl:otherwise><a href="{$bizz}&amp;language=eng">English</a></xsl:otherwise>
				</xsl:choose>
			</li>
		</ul>
	</xsl:template>
	
	
	
	
</xsl:stylesheet>
