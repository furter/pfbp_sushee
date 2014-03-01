<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour les générations automatique de contenu sur base d'un xml de configuration
	-->
	<!-- -->
	<!-- config details - form -->
	<xsl:template match="DISPLAY" mode="display">
		<xsl:param name="tag" select="@tag"/>
		<xsl:param name="childtag" select="@childtag"/>
		<xsl:param name="element"/>
		<xsl:element name="{$tag}">
			<xsl:apply-templates select="*" mode="display">
				<xsl:with-param name="tag" select="'li'"/>
				<xsl:with-param name="element" select="$element"/>
			</xsl:apply-templates>
		</xsl:element>
	</xsl:template>
	<xsl:template match="GROUP" mode="display">
		<xsl:param name="tag"/>
		<xsl:param name="childtag" select="@childtag"/>
		<xsl:param name="element"/>
		<xsl:element name="{$tag}">
			<xsl:call-template name="add_class"/>
			<xsl:element name="{@tag}">
				<xsl:call-template name="add_class"/>
				<xsl:apply-templates select="*" mode="display">
					<xsl:with-param name="tag" select="'li'"/>
					<xsl:with-param name="element" select="$element"/>
				</xsl:apply-templates>
			</xsl:element>
		</xsl:element>
	</xsl:template>
	<xsl:template match="LINK" mode="display">
		<xsl:param name="tag"/>
		<xsl:param name="element"/>
		<xsl:element name="{$tag}">
			<xsl:call-template name="add_class"/>
			<a>
				<xsl:apply-templates select="$element" mode="href"/>
				<xsl:value-of select="//LABEL[@name='read_more']"/>
			</a>
		</xsl:element>
	</xsl:template>
	<xsl:template match="DATA" mode="display">
		<xsl:param name="tag"/>
		<xsl:param name="element"/>
		<xsl:element name="{$tag}">
			<xsl:attribute name="class">
				<xsl:call-template name="class_content"/>
				<xsl:if test="@path='concours'">
					<xsl:text> </xsl:text>
					<xsl:for-each select="$element/CATEGORIES//CATEGORY[ contains(@path, current()/@path) ]">
						<xsl:if test="@ID != 24 and @ID != 29 and @ID != 33">
							<xsl:value-of select="UNIQUENAME"/>
							<xsl:text> </xsl:text>
						</xsl:if>
					</xsl:for-each>
				</xsl:if>
			</xsl:attribute>
			<xsl:apply-templates select="." mode="display_content">
				<xsl:with-param name="element" select="$element"/>
			</xsl:apply-templates>
		</xsl:element>
	</xsl:template>
	<xsl:template match="GROUP" mode="display_content">
		<xsl:param name="element"/>
		<xsl:apply-templates select="*" mode="display_content">
			<xsl:with-param name="element" select="$element"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="DATA" mode="display_content">
		<xsl:param name="element"/>
		<xsl:value-of select="$element//*[name() = current()/@path]" mode="label"/>
	</xsl:template>
	<xsl:template match="DATA[@service='CATEGORY']" mode="display_content">
		<xsl:param name="element"/>
		<xsl:for-each select="$element/CATEGORIES/CATEGORY[contains(@path, current()/@path)]">
			<xsl:if test="@ID != 24 and @ID != 29 and @ID != 33">
				<xsl:value-of select="LABEL"/>
				<xsl:call-template name="add_coma_if_not_last"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:template>
	<xsl:template match="DATA[@service='DEPENDENCY']" mode="display_content">
		<xsl:param name="element"/>
		<xsl:param name="dep_type" select="@type"/>
		<label>
			<xsl:apply-templates select="." mode="label"/>
		</label>
		<xsl:for-each select="$element/DEPENDENCIES/DEPENDENCY[@type = $dep_type]/*">
			<xsl:apply-templates select="." mode="title"/>
			<!--xsl:choose>
				<xsl:when test="$dep_type = 'BookToGraphist'">
					<xsl:if test="INFO/DENOMINATION != '' and INFO/FIRSTNAME != ''">
						<xsl:text> - </xsl:text>
						<xsl:value-of select="INFO/DENOMINATION"/>
					</xsl:if>
				</xsl:when>
			</xsl:choose-->
			<!--
			<xsl:text> (</xsl:text>
			<xsl:value-of select="INFO/COUNTRYID"/>
			<xsl:text>)</xsl:text>
			-->
			<xsl:if test="position() != last()">
				<xsl:text>, </xsl:text>
			</xsl:if>
		</xsl:for-each>
	</xsl:template>
	<xsl:template match="DATA[@service='DEPENDENCY' and (@type='BookToGraphist' or @type='BookToEditor' or @type='BookToPrinter')]" mode="display_content">
		<xsl:param name="element"/>
		<xsl:param name="dep_type" select="@type"/>
		<xsl:param name="desc_field" select="translate(substring($dep_type, 7), $minuscules, $majuscules)"/>
		<label>
			<xsl:apply-templates select="." mode="label"/>
		</label>
		<xsl:choose>
			<xsl:when test="$element/DESCRIPTIONS/DESCRIPTION/CUSTOM/*[name() = $desc_field] != ''">
				<xsl:value-of select="$element/DESCRIPTIONS/DESCRIPTION/CUSTOM/*[name() = $desc_field]"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:for-each select="$element/DEPENDENCIES/DEPENDENCY[@type = $dep_type]/*">
					<xsl:apply-templates select="." mode="title"/>
					<!--xsl:choose>
						<xsl:when test="$dep_type = 'BookToGraphist'">
							<xsl:if test="INFO/DENOMINATION != '' and INFO/FIRSTNAME != ''">
								<xsl:text> - </xsl:text>
								<xsl:value-of select="INFO/DENOMINATION"/>
							</xsl:if>
						</xsl:when>
					</xsl:choose-->
					<!--
					<xsl:text> (</xsl:text>
					<xsl:value-of select="INFO/COUNTRYID"/>
					<xsl:text>)</xsl:text>
					-->
					<xsl:if test="position() != last()">
						<xsl:text>, </xsl:text>
					</xsl:if>
				</xsl:for-each>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
