<?xml version="1.0"?><xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">	<xsl:import href="common.xsl"/>	<xsl:template match="/RESPONSE">		<xsl:call-template name="html"/>	</xsl:template>	<xsl:template match="MEDIA[INFO/MEDIATYPE='Selection']" mode="content">		<div id="content">			<xsl:apply-templates select="." mode="h1"/>			<h4>				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/SIGNATURE"/>			</h4>			<ul class="subnavigation">				<xsl:apply-templates select="$data" mode="inside_navigation_item"/>			</ul>			<xsl:apply-templates select="." mode="header"/>			<xsl:apply-templates select="." mode="body_preview"/>		</div>		<xsl:apply-templates select="." mode="specific"/>	</xsl:template>	<xsl:template match="MEDIA[INFO/MEDIATYPE='Selection' and //URL/mode = 'text']" mode="content">		<div id="content">			<xsl:apply-templates select="." mode="h1"/>			<!--h5>				<a href="{//NECTIL/this_script}?ID={$ID}">					<xsl:value-of select="//LABEL[@name='back_to_selection_gallery']"/>				</a>			</h5-->			<xsl:apply-templates select="." mode="body"/>		</div>		<xsl:apply-templates select="." mode="specific"/>	</xsl:template>	<xsl:template match="MEDIA" mode="specific">		<div id="specific">			<xsl:apply-templates select="$data" mode="big_preview"/>		</div>	</xsl:template>	<xsl:template match="MEDIA[//URL/mode = 'text']" mode="specific">		<div id="specific" class="fix">			<h4>				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/SIGNATURE"/>			</h4>			<xsl:apply-templates select="$data" mode="small_preview"/>		</div>	</xsl:template>	<xsl:template match="MEDIA" mode="body_preview">		<xsl:variable name="content">			<xsl:for-each select="DESCRIPTIONS/DESCRIPTION/BODY/CSS/*[name() = 'h1' or name() = 'h2' or name() = 'h3']">				<xsl:value-of select="node()"/>				<xsl:text> - </xsl:text>			</xsl:for-each>		</xsl:variable>		<xsl:if test="$content != ''">			<ul class="body_preview">				<li class="link">					<a href="{INFO/MEDIATYPE}.php?ID={@ID}&amp;mode=text">						<xsl:value-of select="//LABEL[@name='read_more']"/>					</a>				</li>				<li>					<a href="{INFO/MEDIATYPE}.php?ID={@ID}&amp;mode=text">						<xsl:value-of select="substring($content,1, string-length($content) - 3)"/>					</a>				</li>			</ul>		</xsl:if>	</xsl:template></xsl:stylesheet>