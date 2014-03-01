<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="text" indent="no" encoding="utf-8"/>
	<xsl:param name="minuscules">abcdefghijklmnopqrstuvwxyzàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ</xsl:param>
	<xsl:param name="sans_accent">abcdefghijklmnopqrstuvwxyzaaaaaaæceeeeiiiidnoooooouuuuyp</xsl:param>
	
	<xsl:param name="search">&quot;&#x0d;&#x0a;</xsl:param><!-- the characters to replace or remove in texts -->
	<xsl:param name="replaces">&#8221;</xsl:param>
	
	<xsl:param name="enclosure">&quot;</xsl:param>
	<xsl:param name="separator">;</xsl:param>
	<xsl:param name="element_with_max_categoryID">
		<xsl:for-each select="//CATEGORIES">
			<xsl:sort select="count(CATEGORY)"/>
			<xsl:if test="position()=last()">
				<xsl:value-of select="../@ID"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:param>
	<xsl:param name="element_with_max_category" select="/RESPONSE/RESULTS/CONTACT[@ID=$element_with_max_categoryID]"/>
	<xsl:param name="element_with_max_descriptionID">
		<xsl:for-each select="//DESCRIPTIONS">
			<xsl:sort select="count(DESCRIPTION)"/>
			<xsl:if test="position()=last()">
				<xsl:value-of select="../@ID"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:param>
	<xsl:param name="element_with_max_description" select="/RESPONSE/RESULTS/CONTACT[@ID=$element_with_max_descriptionID]"/>
	<xsl:param name="element_with_max_infosID">
		<xsl:for-each select="//INFO">
			<xsl:sort select="count(*)"/>
			<xsl:if test="position()=last()">
				<xsl:value-of select="../@ID"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:param>
	<xsl:param name="element_with_max_infos" select="/RESPONSE/RESULTS/CONTACT[@ID=$element_with_max_infosID]"/>
	<xsl:param name="element_with_max_customID">
		<xsl:for-each select="//DESCRIPTIONS/DESCRIPTION/CUSTOM">
			<xsl:sort select="count(*)"/>
			<xsl:if test="position()=last()">
				<xsl:value-of select="../@ID"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:param>
	<xsl:param name="element_with_max_custom" select="/RESPONSE/RESULTS/CONTACT/DESCRIPTIONS/DESCRIPTION[@ID=$element_with_max_customID]"/>
	
	<xsl:template match="/RESPONSE">
		<!-- ....................................................................................................... -->
		<!-- column headers -->
		<xsl:for-each select="$element_with_max_infos/INFO/*">
			<xsl:call-template name="simple_column_name_cell"/>
		</xsl:for-each>
		<xsl:for-each select="$element_with_max_category/CATEGORIES/CATEGORY">
			<xsl:value-of select="$enclosure"/>
			<xsl:call-template name="column_name"/>
			<xsl:text>.@name</xsl:text>
			<xsl:value-of select="$enclosure"/>
			<xsl:value-of select="$separator"/>
		</xsl:for-each>
		<xsl:for-each select="$element_with_max_description/DESCRIPTIONS/DESCRIPTION">
			<xsl:for-each select="*[local-name()!='CUSTOM']">
				<xsl:call-template name="simple_column_name_cell"/>
			</xsl:for-each>
			<xsl:variable name="column_part_name"><!-- the name of the column till DESCRIPTION -->
				<xsl:call-template name="column_name"/>
			</xsl:variable>
			<xsl:for-each select="$element_with_max_custom/CUSTOM/*">
				<xsl:value-of select="$enclosure"/>
				<xsl:value-of select="$column_part_name"/>.CUSTOM.<xsl:value-of select="local-name()"/>
				<xsl:value-of select="$enclosure"/>
				<xsl:value-of select="$separator"/>
			</xsl:for-each>
		</xsl:for-each>
		<xsl:text>
</xsl:text><!-- end of line-->
		<!-- ....................................................................................................... -->
		<!-- the elements -->
		<xsl:for-each select="RESULTS/CONTACT"><!-- CONTACT,GROUP,MEDIA-->
			<xsl:variable name="current_element" select="."/>
			<xsl:for-each select="$element_with_max_infos/INFO/*">
				<xsl:variable name="nodename" select="local-name()"/>
				<xsl:value-of select="$enclosure"/>
				<xsl:apply-templates select="$current_element/INFO/*[local-name()=$nodename]/node()"/>
				<xsl:value-of select="$enclosure"/>
				<xsl:value-of select="$separator"/>
			</xsl:for-each>
			<xsl:for-each select="$element_with_max_category/CATEGORIES/CATEGORY">
				<xsl:variable name="current_pos" select="position()"/>
				<xsl:value-of select="$enclosure"/>
				<xsl:value-of select="$current_element/CATEGORIES/CATEGORY[position()=$current_pos]/UNIQUENAME"/>
				<xsl:value-of select="$enclosure"/>
				<xsl:value-of select="$separator"/>
			</xsl:for-each>
			<xsl:for-each select="$element_with_max_description/DESCRIPTIONS/DESCRIPTION">
				<xsl:variable name="current_pos" select="position()"/>
				<xsl:choose>
					<xsl:when test="$current_element/DESCRIPTIONS/DESCRIPTION[position()=$current_pos]"><!--  there is a description a this position-->
						<xsl:for-each select="$current_element/DESCRIPTIONS/DESCRIPTION[position()=$current_pos]">
							<xsl:variable name="current_description" select="."/>
							<xsl:for-each select="*[local-name()!='CUSTOM']">
								<xsl:value-of select="$enclosure"/>
								<xsl:apply-templates select="node()"/>
								<xsl:value-of select="$enclosure"/>
								<xsl:value-of select="$separator"/>
							</xsl:for-each>
							<xsl:for-each select="$element_with_max_custom/CUSTOM/*">
								<xsl:variable name="nodename" select="local-name()"/>
								<xsl:value-of select="$enclosure"/>
								<xsl:apply-templates select="$current_description/CUSTOM/*[local-name()=$nodename]"/>
								<xsl:value-of select="$enclosure"/>
								<xsl:value-of select="$separator"/>
							</xsl:for-each>
						</xsl:for-each>
					</xsl:when>
					<xsl:otherwise><!-- there is no description, putting empty cells -->
						<xsl:for-each select="$element_with_max_description/DESCRIPTIONS/DESCRIPTION[position()=$current_pos]/*[local-name()!='CUSTOM']">
							<xsl:call-template name="empty_cell"/>
						</xsl:for-each>
						<xsl:for-each select="$element_with_max_custom/CUSTOM/*">
							<xsl:call-template name="empty_cell"/>
						</xsl:for-each>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
			<xsl:text>
</xsl:text><!-- end of line-->
		</xsl:for-each>
	</xsl:template>
	
	<!-- ........................................................................................................... -->
	<xsl:template name="column_particle">
		<xsl:value-of select="local-name()"/>
		<xsl:if test="count(../*[local-name()=name(current())]) &gt; 1"><!-- if there are other nodes of the same type in the same parent, we indicate the index-->
			<xsl:text>[</xsl:text>
			<xsl:value-of select="count(preceding-sibling::*[local-name()=name(current())]) + 1"/>
			<xsl:text>]</xsl:text>
		</xsl:if>
	</xsl:template>
	<xsl:template name="column_name">
		<xsl:for-each select="ancestor::*[count(ancestor::*) &gt; 2 ]"><!--  generating the XPATH. Ex: INFO.ID, DESCRIPTIONS.DESCRIPTION.ID -->
			<xsl:call-template name="column_particle"/>
			<xsl:text>.</xsl:text>
		</xsl:for-each>
		<xsl:call-template name="column_particle"/>
	</xsl:template>
	
	<xsl:template name="simple_column_name_cell">
		<xsl:value-of select="$enclosure"/>
		<xsl:call-template name="column_name"/>
		<xsl:value-of select="$enclosure"/>
		<xsl:value-of select="$separator"/>
	</xsl:template>
	<xsl:template name="empty_cell">
		<xsl:text>&quot;&quot;</xsl:text>
		<xsl:value-of select="$separator"/>
	</xsl:template>
	<!-- ........................................................................................................... -->
	<xsl:template match="CSS">
		<xsl:for-each select="*">
			<xsl:variable name="string" select="translate(.,$search,$replaces)"/>
			<xsl:value-of select="translate($string,$minuscules,$sans_accent)"/>
			<!--xsl:text>minuscules sans_accent
</xsl:text--><!-- end of line-->
		</xsl:for-each>
	</xsl:template>
	
	<xsl:template match="text()">
		<xsl:variable name="string" select="translate(.,$search,$replaces)"/>
		<xsl:value-of select="translate($string,$minuscules,$sans_accent)"/>
<!--		<xsl:value-of select="translate(.,$search,$replaces)"/>-->
	</xsl:template>
	
	<!-- ........................................................................................................... -->
</xsl:stylesheet>
