<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/templates/generic_csv.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="text" indent="no" encoding="utf-8"/>
	
	<!-- 1. determine the object by selecting the first element -->
	<xsl:param name="object" select="local-name(/RESPONSE/RESULTS/*[1])"/>
	
	<xsl:param name="search">&quot;&#x0d;&#x0a;</xsl:param><!-- the characters to replace or remove in texts -->
	<xsl:param name="replaces">&#8221;</xsl:param>
	
	<xsl:param name="enclosure">&quot;</xsl:param>
	<xsl:param name="separator">;</xsl:param>
	<xsl:param name="element_with_max_categoryID">
		<xsl:for-each select="//*[local-name() = $object]/CATEGORIES">
			<xsl:sort select="count(CATEGORY)"/>
			<xsl:if test="position()=last()">
				<xsl:value-of select="../@ID"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:param>
	<xsl:param name="element_with_max_category" select="/RESPONSE/RESULTS//*[@ID = $element_with_max_categoryID][1]"/>
	
	<xsl:param name="element_with_max_descriptionID">
		<xsl:for-each select="//*[local-name() = $object]/DESCRIPTIONS">
			<xsl:sort select="count(DESCRIPTION)"/>
			<xsl:if test="position()=last()">
				<xsl:value-of select="../@ID"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:param>
	<xsl:param name="element_with_max_description" select="/RESPONSE/RESULTS//*[@ID = $element_with_max_descriptionID][1]"/>
	
	<xsl:param name="element_with_max_infosID">
		<xsl:for-each select="//*[local-name() = $object and local-name(parent::node()) != 'COMMENT']/INFO">
			<xsl:sort select="count(*)"/>
			<xsl:if test="position()=last()">
				<xsl:value-of select="../@ID"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:param>
	<xsl:param name="element_with_max_infos" select="//*[local-name() = $object and local-name(parent::node()) != 'COMMENT' and @ID = $element_with_max_infosID]" />

	<xsl:param name="element_with_max_customID">
		<xsl:for-each select="//*[local-name() = $object]/DESCRIPTIONS/DESCRIPTION/CUSTOM">
			<xsl:sort select="count(*)"/>
			<xsl:if test="position()=last()">
				<xsl:value-of select="../@ID"/>
			</xsl:if>
		</xsl:for-each>
	</xsl:param>
	<xsl:param name="element_with_max_custom" select="/RESPONSE/RESULTS//*/DESCRIPTIONS/DESCRIPTION[@ID = $element_with_max_customID][1]"/>
	
	<xsl:template match="/RESPONSE">

		<!-- ....................................................................................................... -->
		<!-- column headers -->

		<xsl:for-each select="$element_with_max_infos/INFO/*">
			<xsl:call-template name="simple_column_name_cell">
				<xsl:with-param name="path">INFO</xsl:with-param>
			</xsl:call-template>
		</xsl:for-each>

		<xsl:for-each select="$element_with_max_category/CATEGORIES/CATEGORY">
			<xsl:value-of select="$enclosure"/>
			<xsl:call-template name="column_name">
				<xsl:with-param name="path">CATEGORIES</xsl:with-param>
			</xsl:call-template>
			<xsl:choose>
				<xsl:when test="$element_with_max_category/CATEGORIES/CATEGORY/@path">
					<xsl:text>.@path</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>.@ID</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:value-of select="$enclosure"/>
			<xsl:value-of select="$separator"/>
		</xsl:for-each>
		
		<xsl:for-each select="$element_with_max_description/DESCRIPTIONS/DESCRIPTION">
	
			<!-- the name of the DESCRIPTION column -->
			<xsl:variable name="column_part_name">
				<xsl:text>DESCRIPTIONS.DESCRIPTION</xsl:text>
				<xsl:call-template name="node_count" />
			</xsl:variable>
			
			<xsl:for-each select="*[local-name()!='CUSTOM']">
				<xsl:call-template name="simple_column_name_cell">
					<xsl:with-param name="path">
						<xsl:value-of select="$column_part_name" />
					</xsl:with-param>
				</xsl:call-template>
			</xsl:for-each>

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

		<xsl:for-each select="RESULTS//*[local-name() = $object and local-name(parent::node()) != 'COMMENT']">
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
				<xsl:choose>
					<xsl:when test="$element_with_max_category/CATEGORIES/CATEGORY/@path">
						<xsl:value-of select="$current_element/CATEGORIES/CATEGORY[position()=$current_pos]/@path"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$current_element/CATEGORIES/CATEGORY[position()=$current_pos]/@ID"/>
					</xsl:otherwise>
				</xsl:choose>
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
	
	<xsl:template name="node_count">
		
		<!-- if there are other nodes of the same type in the same parent, we indicate the index-->
		
		<xsl:if test="count(../*[local-name()=name(current())]) &gt; 1">
			<xsl:text>[</xsl:text>
			<xsl:value-of select="count(preceding-sibling::*[local-name()=name(current())]) + 1"/>
			<xsl:text>]</xsl:text>
		</xsl:if>

	</xsl:template>

	<xsl:template name="column_particle">
		<xsl:value-of select="local-name()" />
		<xsl:call-template name="node_count" />
	</xsl:template>

	<xsl:template name="column_name">
		<xsl:param name="path"></xsl:param>
		<xsl:value-of select="$path" />
		<xsl:text>.</xsl:text>
		<xsl:call-template name="column_particle"/>
	</xsl:template>

	<xsl:template name="simple_column_name_cell">
		<xsl:param name="path"></xsl:param>
		<xsl:value-of select="$enclosure"/>
		<xsl:call-template name="column_name">
			<xsl:with-param name="path" select="$path"/>
		</xsl:call-template>
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
			<xsl:value-of select="translate(.,$search,$replaces)"/>
			<!--xsl:text>
</xsl:text--><!-- end of line-->
		</xsl:for-each>
	</xsl:template>
	
	<xsl:template match="text()">
		<xsl:value-of select="translate(.,$search,$replaces)"/>
	</xsl:template>
	
	<!-- ........................................................................................................... -->
</xsl:stylesheet>
