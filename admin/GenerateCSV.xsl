<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:import href="common.xsl"/>
	<xsl:output method="text" indent="no" encoding="utf-8"/>
	<xsl:param name="minuscules">abcdefghijklmnopqrstuvwxyzàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ</xsl:param>
	<xsl:param name="sans_accent">abcdefghijklmnopqrstuvwxyzaaaaaaæceeeeiiiidnoooooouuuuyp</xsl:param>
	
	<xsl:param name="search">&quot;&#x0d;&#x0a;</xsl:param><!-- the characters to replace or remove in texts -->
	<xsl:param name="replaces">&#8221;</xsl:param>
	
	<xsl:param name="enclosure">&quot;</xsl:param>
	<xsl:param name="separator">;</xsl:param>
	<xsl:template match="/RESPONSE">
		<xsl:apply-templates select="/RESPONSE/DISPLAY/*" mode="CSV_display_header"/>
		<xsl:text>
</xsl:text>
		<xsl:for-each select="RESULTS[@name='data']/*">
			<xsl:variable name="current_element" select="."/>
			<xsl:apply-templates select="/RESPONSE/DISPLAY/*" mode="CSV_display_cell">
				<xsl:with-param name="element" select="$current_element"/>
			</xsl:apply-templates>
			<xsl:text>
</xsl:text>
		</xsl:for-each>
	</xsl:template>
	<!-- -->
	<!-- CELLS -->
	<xsl:template match="GROUP" mode="CSV_display_cell">
		<xsl:param name="element"/>
		<xsl:apply-templates select="*" mode="CSV_display_cell">
			<xsl:with-param name="element" select="$element"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="UPDATE" mode="CSV_display_cell">
	</xsl:template>
	<xsl:template match="DATA" mode="CSV_display_cell">
		<xsl:param name="element"/>
		<xsl:if test="/RESPONSE/RETURN//*[name() = current()/@path]">
			<xsl:variable name="string">
				<xsl:apply-templates select="$element//*[name() = current()/@path]" mode="label"/>
			</xsl:variable>
			<xsl:call-template name="to_string">
				<xsl:with-param name="string" select="$string"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<xsl:template match="DATA[@function='length']" mode="CSV_display_cell">
		<xsl:param name="element"/>
		<xsl:variable name="string">
		<xsl:value-of select="string-length($element//*[name() = current()/@path])"/>
		</xsl:variable>
		<xsl:call-template name="to_string">
			<xsl:with-param name="string" select="$string"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="DATA[@service='CATEGORY']" mode="CSV_display_cell">
		<xsl:param name="father_in_path" select="concat('/', @path,'/')"/>
		<xsl:param name="element"/>
		<xsl:if test="/RESPONSE/RETURN/CATEGORIES/CATEGORY[@uniquename = current()/@path]">
			<xsl:variable name="string">
				<xsl:for-each select="$element/CATEGORIES/CATEGORY[contains(@path, $father_in_path)]">
					<xsl:value-of select="LABEL"/>
					<xsl:call-template name="add_coma_if_not_last"/>
				</xsl:for-each>
			</xsl:variable>
			<xsl:call-template name="to_string">
				<xsl:with-param name="string" select="$string"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<xsl:template match="DATA[@service='DEPENDENCY']" mode="CSV_display_cell">
		<xsl:param name="element"/>
		<xsl:if test="/RESPONSE/RETURN/DEPENDENCIES/DEPENDENCY[@type = current()/@type]">
			<xsl:variable name="string">
				<xsl:for-each select="$element/DEPENDENCIES/DEPENDENCY[@type = current()/@type]/*">
					<xsl:apply-templates select="." mode="label"/>
					<xsl:call-template name="add_coma_if_not_last"/>
				</xsl:for-each>
			</xsl:variable>
			<xsl:call-template name="to_string">
				<xsl:with-param name="string" select="$string"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<!-- -->
	<!-- header -->
	<xsl:template match="GROUP" mode="CSV_display_header">
		<xsl:apply-templates select="*" mode="CSV_display_header"/>
	</xsl:template>
	<xsl:template match="UPDATE" mode="CSV_display_header"/>
	<xsl:template match="DATA" mode="CSV_display_header">
		<xsl:if test="/RESPONSE/RETURN//*[name() = current()/@path]">
			<xsl:variable name="string">
				<xsl:apply-templates select="." mode="label"/>
				<xsl:if test="@function = 'length'">
					<xsl:text> - longueur</xsl:text>
				</xsl:if>
			</xsl:variable>
			<xsl:call-template name="to_string">
				<xsl:with-param name="string" select="$string"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<xsl:template match="DATA[@service='CATEGORY']" mode="CSV_display_header">
		<xsl:if test="/RESPONSE/RETURN/CATEGORIES/CATEGORY[@uniquename = current()/@path]">
			<xsl:variable name="string">
				<xsl:value-of select="/RESPONSE/RESULTS//CATEGORY[UNIQUENAME = current()/@path]/LABEL"/>
			</xsl:variable>
			<xsl:call-template name="to_string">
				<xsl:with-param name="string" select="$string"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<xsl:template match="DATA[@service='DEPENDENCY']" mode="CSV_display_header">
		<xsl:if test="/RESPONSE/RETURN/DEPENDENCIES/DEPENDENCY[@type = current()/@type]">
			<xsl:variable name="string">
				<xsl:apply-templates select="." mode="label"/>
			</xsl:variable>
			<xsl:call-template name="to_string">
				<xsl:with-param name="string" select="$string"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<!-- ........................................................................................................... -->
	<xsl:template name="to_string">
		<xsl:param name="string"/>
		<xsl:variable name="temp_string" select="translate($string,$search,$replaces)"/>
		<xsl:value-of select="$enclosure"/>
		<xsl:value-of select="translate($temp_string,$minuscules,$sans_accent)"/>
		<xsl:value-of select="$enclosure"/>
		<xsl:value-of select="$separator"/>
	</xsl:template>
</xsl:stylesheet>
