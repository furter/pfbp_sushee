<?xml version="1.0"?><xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">	<xsl:import href="common.xsl"/>	<xsl:template match="/RESPONSE">		<html>			<xsl:call-template name="attribute_html"/>			<head>				<xsl:call-template name="html_head"/>			</head>			<body>				<xsl:apply-templates select="$media" mode="attribute"/>				<div id="container" class="col_13">					<xsl:apply-templates select="$website" mode="top"/>					<xsl:apply-templates select="$media" mode="content"/>				</div>			</body>		</html>	</xsl:template>	<xsl:template match="MEDIA" mode="content">		<!--		<div id="content">			<div id="main_content" class="col_9 gouttiere">				<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='mediaContent']/MEDIA" mode="in_content"/>			</div>			<div id="side_content" class="col_4">				<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='rightContent']/MEDIA" mode="in_content"/>			</div>			<br class="break"/>		</div>		-->	</xsl:template></xsl:stylesheet>