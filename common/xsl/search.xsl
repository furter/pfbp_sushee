<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour 
		- l'affichage des résultats d'une recherche via le contenu du nœud <URL>
		- générer la pagination à partir du nœud <RESULTS>
	-->
	<!-- -->
	<!-- search details -->
	<xsl:template match="URL" mode="search_details">
		<xsl:param name="form_name" select="/RESPONSE/CONFIG/FORM/@name"/>
		<xsl:param name="mediatype" select="/RESPONSE/DISPLAY/@mediatype"/>
		<xsl:param name="module">
			<xsl:choose>
				<xsl:when test="$mediatype != ''">
					<xsl:value-of select="$mediatype"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="/RESPONSE/DISPLAY/@module"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>
		<xsl:variable name="elements_found" select="concat($module, '_found')"/>
		<xsl:variable name="hits" select="/RESPONSE/RESULTS[@name='data']/@hits"/>
		<div class="browser_tools col_8" id="search_details">
			<ul>
				<li class="title">
					<xsl:value-of select="//LABEL[@name='f_search_criterias']"/>
				</li>
				<xsl:if test="//URL/search != ''">
					<li>
						<xsl:value-of select="//URL/search"/>
					</li>
				</xsl:if>
				<xsl:for-each select="*">
					<xsl:variable name="var_name" select="name()"/>
					<xsl:variable name="var_value" select="."/>
					<xsl:for-each select="/RESPONSE/CONFIG/FORM[@name = $form_name]//*[@path = $var_name]">
						<xsl:if test="$var_value != ''">
							<li>
								<xsl:apply-templates select="." mode="label"/>
								<xsl:text>: </xsl:text>
								<xsl:choose>
									<xsl:when test="@type='CATEGORY'">
										<xsl:value-of select="(//CATEGORY[@path = $var_value]/LABEL) [1]"/>
									</xsl:when>
									<xsl:when test="@type='LIST'">
										<xsl:value-of select="(//LIST/ITEM[@value = $var_value]/@label) [1]"/>
									</xsl:when>
									<xsl:when test="@type='COUNTRY'">
										<xsl:value-of select="(//COUNTRY[@ID = $var_value]/LABEL) [1]"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="//URL/*[name() = current()/@path]"/>
									</xsl:otherwise>
								</xsl:choose>
							</li>
						</xsl:if>
					</xsl:for-each>
				</xsl:for-each>
			</ul>
			<ul>
				<li class="title">
					<xsl:value-of select="$hits"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="//LABEL[@name=$elements_found]"/>
				</li>
				<xsl:if test="$hits &gt; 0">
					<li>
						<a href="#" onclick="generate_{$module}_CSV();">
							<xsl:value-of select="//LABEL[@name='download_listing']"/>
						</a>
					</li>
				</xsl:if>
			</ul>
		</div>
	</xsl:template>
	<!-- -->
	<!-- pagination -->
	<xsl:template match="RESULTS" mode="pagination">
		<xsl:if test="@pages &gt; 1">
			<xsl:variable name="base_link">
				<xsl:value-of select="//NECTIL/this_script"/>
				<xsl:text>?</xsl:text>
				<xsl:for-each select="//URL/*[name() != 'now' and name() != 'language' and name() != 'page']">
					<xsl:value-of select="name()"/>
					<xsl:text>=</xsl:text>
					<xsl:value-of select="."/>
					<xsl:text>&amp;</xsl:text>
				</xsl:for-each>
			</xsl:variable>
			<div class="browser_tools col_8">
				<ul>
					<li class="title">
						<xsl:value-of select="//LABEL[@name='pages']"/>
					</li>
					<xsl:if test="@page &gt; 1">
						<li>
							<a href="{$base_link}page={@page - 1}">
								<xsl:text>Previous</xsl:text>
							</a>
						</li>
					</xsl:if>
					<xsl:for-each select="/RESPONSE/RESULTS/ENUM/E[. &lt;= current()/@pages]">
						<li>
							<a href="{$base_link}page={.}">
								<xsl:if test=". = //URL/page">
									<xsl:attribute name="class">active</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="."/>
							</a>
						</li>
					</xsl:for-each>
					<xsl:if test="@page != @pages">
						<li>
							<a href="{$base_link}page={@page + 1}">
								<xsl:text>Next</xsl:text>
							</a>
						</li>
					</xsl:if>
				</ul>
			</div>
		</xsl:if>
	</xsl:template>
	

</xsl:stylesheet>