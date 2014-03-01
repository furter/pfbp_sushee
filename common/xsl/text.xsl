<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions de base pour générer:
		- des textes stylés
	-->
	<!-- -->
	<!-- textes -->
	<xsl:template match="MEDIA" mode="body">
		<xsl:apply-templates select="DESCRIPTIONS/DESCRIPTION/BODY/CSS" mode="css">
			<xsl:with-param name="class" select="'BODY'"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="MEDIA" mode="header">
		<xsl:apply-templates select="DESCRIPTIONS/DESCRIPTION/HEADER/CSS" mode="css">
			<xsl:with-param name="class" select="'HEADER'"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="MEDIA" mode="summary">
		<xsl:apply-templates select="DESCRIPTIONS/DESCRIPTION/SUMMARY/CSS" mode="css">
			<xsl:with-param name="class" select="'SUMMARY'"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="MEDIA" mode="signature">
		<xsl:apply-templates select="DESCRIPTIONS/DESCRIPTION/SIGNATURE/CSS" mode="css">
			<xsl:with-param name="class" select="'SIGNATURE'"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="CSS" mode="css">
		<xsl:param name="class"/>
		<div class="styled_text {$class}">
			<xsl:apply-templates select="*"/>
		</div>
	</xsl:template>
	<!-- -->
	<!-- texte stylé -->
	<xsl:template match="CSS//*">
		<xsl:choose>
			<xsl:when test="name(.)='nectil_url' and not(@pagetocall)">
				<a href="{@mediatype}.php?ID={@ID}" class="{@class}">
					<xsl:value-of select="."/>
				</a>
			</xsl:when>
			<xsl:when test="name(.)='nectil_url' and @pagetocall">
				<a href="{@pagetocall}?ID={@ID}" class="{@class}">
					<xsl:value-of select="."/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:element name="{local-name()}">
					<xsl:copy-of select="./@*"/>
					<xsl:apply-templates/>
				</xsl:element>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- -->
	<!-- images dans texte stylé -->
	<xsl:template match="CSS//img[@class='image']">
		<xsl:element name="{local-name()}">
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates select="@style"/>
			<xsl:choose>
				<xsl:when test="@height != ''">
					<xsl:apply-templates select="@src">
						<xsl:with-param name="height" select="@height"/>
					</xsl:apply-templates>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="@src">
						<xsl:with-param name="width" select="@width"/>
					</xsl:apply-templates>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:apply-templates select="@width"/>
			<xsl:apply-templates select="@height"/>
			<xsl:apply-templates/>
		</xsl:element>
	</xsl:template>
	<xsl:template match="CSS//img[@class='image']/@src">
		<xsl:param name="width" select="284"/>
		<xsl:param name="height" select="284"/>
		<xsl:attribute name="src">
			<xsl:value-of select="$common"/>
			<xsl:text>img_resize.php?path=</xsl:text>
			<xsl:value-of select="substring-after(.,//NECTIL/files_url)"/>
			<xsl:text>&amp;</xsl:text>
			<xsl:choose>
				<xsl:when test="$height != ''">
					<xsl:text>height=</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>width=</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:value-of select="substring-before($width, 'px')"/>
		</xsl:attribute>
	</xsl:template>
	<xsl:template match="CSS//img[@class='image']/@style">
		<xsl:attribute name="style">
			<xsl:choose>
				<xsl:when test=". = 'float:left;'">
					<xsl:value-of select="."/>
					<xsl:text> margin:6px 20px 6px 0;</xsl:text>
				</xsl:when>
				<xsl:when test=". = 'float:right;'">
					<xsl:value-of select="."/>
					<xsl:text> margin:6px 0 6px 20px;</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="."/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
	</xsl:template>
	<xsl:template match="CSS//img[@class='image']/@width"/>
	<xsl:template match="CSS//img[@class='image']/@height"/>

</xsl:stylesheet>