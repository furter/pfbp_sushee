<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour
		- les entêtes du tableau
	 	- générer des listing sur base d'un xml de configuration et des résultats
		- des fonctions qui ajoute une fonction de tri dans l'entête des différentes colonnes (pour les données dans INFO et DESCRIPTION)
		- options d'affichages en rapport au même xml de configuration		
	-->
	<!-- -->
	<!-- table header -->
	<xsl:template match="GROUP" mode="display_header">
		<xsl:apply-templates select="*" mode="display_header"/>
	</xsl:template>
	<xsl:template match="UPDATE" mode="display_header">
		<th> - </th>
	</xsl:template>
	<xsl:template match="DATA" mode="display_header">
		<xsl:param name="target">
			<xsl:choose>
				<xsl:when test="@service = 'DEPENDENCY'">
					<xsl:value-of select="@type"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@path"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>
		<xsl:param name="class" select="concat(../@class, ' ', $target)"/>
		<th>
			<xsl:if test="$class != ''">
				<xsl:attribute name="class">
					<xsl:value-of select="$class"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates select="." mode="sort"/>
			<br/>
			<br/>
			<xsl:apply-templates select="." mode="label"/>
		</th>
	</xsl:template>	
	<!-- -->
	<!-- CELLS -->
	<xsl:template match="*" mode="display_cell">
		<xsl:param name="element"/>
		<td>
			<xsl:call-template name="add_class"/>
			<xsl:apply-templates select="." mode="display_cell_content">
				<xsl:with-param name="element" select="$element"/>
			</xsl:apply-templates>
		</td>
	</xsl:template>
	<xsl:template match="GROUP" mode="display_cell">
		<xsl:param name="element"/>
		<xsl:apply-templates select="*" mode="display_cell">
			<xsl:with-param name="element" select="$element"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="GROUP" mode="display_cell_content">
		<xsl:param name="element"/>
		<xsl:apply-templates select="*" mode="display_cell_content">
			<xsl:with-param name="element" select="$element"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="UPDATE[../@module='CONTACT']" mode="display_cell_content">
		<xsl:param name="element"/>
		<a href="#" onclick="display_contact('{$element/@ID}');">
			<xsl:value-of select="//LABEL[@name='update']"/>
		</a>
	</xsl:template>
	<xsl:template match="UPDATE[../@module='MEDIA']" mode="display_cell_content">
		<xsl:param name="element"/>
		<xsl:param name="mediatype" select="translate($element/INFO/MEDIATYPE, $majuscules, $minuscules)"/>
		<a href="#" onclick="display_{$mediatype}('{$element/@ID}');">
			<xsl:value-of select="//LABEL[@name='update']"/>
		</a>
	</xsl:template>
	<xsl:template match="DATA" mode="display_cell_content">
		<xsl:param name="element"/>
		<xsl:value-of select="$element//*[name() = current()/@path]" mode="label"/>
	</xsl:template>
	<xsl:template match="DATA[@service='CATEGORY']" mode="display_cell_content">
		<xsl:param name="element"/>
		<xsl:for-each select="$element/CATEGORIES//CATEGORY[contains(@path, current()/@path)]">
			<xsl:value-of select="LABEL"/>
			<xsl:call-template name="add_coma_if_not_last"/>
		</xsl:for-each>
	</xsl:template>
	<xsl:template match="DATA[@service='DEPENDENCY']" mode="display_cell_content">
		<xsl:param name="element"/>
		<xsl:param name="dep_type" select="@type"/>
		<xsl:for-each select="$element/DEPENDENCIES/DEPENDENCY[@type = $dep_type]/*">
			<xsl:apply-templates select="." mode="title"/>
			<xsl:choose>
				<xsl:when test="$dep_type = 'BookToPeopleContact'">
					<xsl:if test="INFO/EMAIL1 != ''">
						<xsl:text> - </xsl:text>
						<xsl:value-of select="INFO/EMAIL1"/>
					</xsl:if>
					<xsl:if test="INFO/PHONE1 != ''">
						<xsl:text> - </xsl:text>
						<xsl:value-of select="INFO/PHONE1"/>
					</xsl:if>
					<xsl:if test="INFO/MOBILEPHONE != ''">
						<xsl:text> - </xsl:text>
						<xsl:value-of select="INFO/MOBILEPHONE"/>
					</xsl:if>
				</xsl:when>
				<xsl:when test="$dep_type = 'BookToGraphist'">
					<xsl:if test="INFO/DENOMINATION != '' and INFO/FIRSTNAME != ''">
						<xsl:text> - </xsl:text>
						<xsl:value-of select="INFO/DENOMINATION"/>
					</xsl:if>
				</xsl:when>
			</xsl:choose>
			<xsl:if test="INFO/COUNTRYID and INFO/COUNTRYID != 'bel'">
				<xsl:text> (</xsl:text>
				<xsl:value-of select="INFO/COUNTRYID"/>
				<xsl:text>)</xsl:text>
			</xsl:if>			
			<xsl:call-template name="add_coma_if_not_last"/>
		</xsl:for-each>
	</xsl:template>
	<!-- -->
	<!-- construction du listing -->
	<xsl:template match="UPDATE" mode="content">
		<xsl:param name="element"/>
		<td>
			<a href="#" onclick="display_contact('{$element/@ID}');">
				<xsl:value-of select="//LABEL[@name='update']"/>
			</a>
		</td>
	</xsl:template>	
	<!-- -->
	<!-- listing -->
	<xsl:template match="MEDIA" mode="data">
		<xsl:param name="current_element" select="."/>
		<tr id="m{@ID}">
			<xsl:apply-templates select="/RESPONSE/DISPLAY/*" mode="display_cell">
				<xsl:with-param name="element" select="$current_element"/>
			</xsl:apply-templates>
		</tr>
	</xsl:template>
	<xsl:template match="CONTACT" mode="data">
		<xsl:param name="current_element" select="."/>
		<tr id="c{@ID}">
			<xsl:apply-templates select="/RESPONSE/DISPLAY/*" mode="display_cell">
				<xsl:with-param name="element" select="$current_element"/>
			</xsl:apply-templates>
		</tr>
	</xsl:template>
	<!-- -->
	<!-- specific elements sort template -->
	<xsl:template match="*" mode="basic_sort">
		<xsl:param name="var"/>
		<xsl:param name="type" select="@data-type"/>
		<xsl:variable name="current_url">
			<xsl:value-of select="//NECTIL/this_script"/>
			<xsl:text>?</xsl:text>
			<xsl:for-each select="//URL/*[name() != 'now' and name() != 'language' and name() != 'page' and name() != 'sort' and name() != 'order' and name() != 'type']">
				<xsl:value-of select="name()"/>
				<xsl:text>=</xsl:text>
				<xsl:value-of select="."/>
				<xsl:text>&amp;</xsl:text>
			</xsl:for-each>
		</xsl:variable>
		<a href="{$current_url}sort={$var}&amp;order=descending&amp;type={$type}">
			<img src="images/down.gif"/>
		</a>
		<a href="{$current_url}sort={$var}&amp;order=ascending&amp;type={$type}">
			<img src="images/up.gif"/>
		</a>
	</xsl:template>
	<xsl:template match="DATA" mode="sort"/>
	<xsl:template match="DATA[@service='DESCRIPTION' or @service='INFO']" mode="sort">
		<xsl:apply-templates select="." mode="basic_sort">
			<xsl:with-param name="var">
				<xsl:value-of select="@path"/>
			</xsl:with-param>
		</xsl:apply-templates>
	</xsl:template>	
	<!-- -->
	<!-- display options -->
	<xsl:template match="DISPLAY" mode="display_options">
		<ul>
			<xsl:apply-templates select="*" mode="display_options"/>
		</ul>
	</xsl:template>
	<xsl:template match="DATA | GROUP" mode="display_options">
		<xsl:param name="target">
			<xsl:choose>
				<xsl:when test="name() = 'GROUP'">
					<xsl:value-of select="@class"/>
				</xsl:when>
				<xsl:when test="@service = 'DEPENDENCY'">
					<xsl:value-of select="@type"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@path"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>
		<xsl:variable name="cbID" select="concat('d_o_', $target)"/>
		<li id="cont_{$cbID}">
			<input type="checkbox" name="{$cbID}" id="{$cbID}" checked="checked" onclick="toggle_display('{$target}');"/>
			<label for="{$cbID}">
				<xsl:apply-templates select="." mode="label"/>
			</label>
			<xsl:if test="name() = 'GROUP'">
				<ul id="{$cbID}_subcontent">
					<xsl:apply-templates select="*" mode="display_options"/>
				</ul>
			</xsl:if>
		</li>
	</xsl:template>

</xsl:stylesheet>