<?xml version="1.0"?><xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">	<xsl:param name="data" select="/RESPONSE/RESULTS[@name='data']/MEDIA"/>	<xsl:template match="/RESPONSE">		<html>			<head>				<title>Prix Fernand Baudin - Liste des soumissions</title>				<style>					body{ font-family:Helvetica, Arial, Sans-Serif;}					table, tr, td, th {border:1px solid black; border-collapse:collapse; vertical-align:top; padding:2px;font-size:11px; text-align:left;}					a img{border:none; margin-right:3px;}				</style>			</head>			<body>				<h1>Prix Fernand Baudin - Liste des soumissions</h1>				<table>					<tr>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">NUMBER</xsl:with-param>								<xsl:with-param name="type">number</xsl:with-param>							</xsl:call-template>							<xsl:text>Num&#xE9;ro</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">TITLE</xsl:with-param>							</xsl:call-template>							<xsl:text>Titre</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">YEAR</xsl:with-param>							</xsl:call-template>							<xsl:text>Ann&#xE9;e</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">CATEGORY[6]</xsl:with-param>							</xsl:call-template>							<xsl:text>Cat&#xE9;gorie</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">CATEGORY[21]</xsl:with-param>							</xsl:call-template>							<xsl:text>Prix</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">DEPENDENCY[BookToEditor]</xsl:with-param>							</xsl:call-template>							<xsl:text>Editeur</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">DEPENDENCY[BookToGraphist]</xsl:with-param>							</xsl:call-template>							<xsl:text>Graphiste</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">DEPENDENCY[BookToBinder]</xsl:with-param>							</xsl:call-template>							<xsl:text>Relieur</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">DEPENDENCY[BookToPrinter]</xsl:with-param>							</xsl:call-template>							<xsl:text>Imprimeur</xsl:text>						</th>						<th>							<xsl:call-template name="sort">								<xsl:with-param name="var">DEPENDENCY[BookToPeopleContact]</xsl:with-param>							</xsl:call-template>							<xsl:text>Personne de contact</xsl:text>						</th>					</tr>					<xsl:apply-templates select="/RESPONSE/RESULTS[@name='data']" mode="listing"/>				</table>			</body>		</html>	</xsl:template>	<xsl:template match="RESULTS" mode="listing">		<xsl:for-each select="$data">			<xsl:apply-templates select="." mode="data"/>		</xsl:for-each>	</xsl:template>	<xsl:template match="RESULTS[/RESPONSE/URL/sort != '']" mode="listing">		<xsl:for-each select="$data">			<xsl:sort select=".//*[name() = //URL/sort]" data-type="{//URL/type}" order="{//URL/order}"/>			<xsl:apply-templates select="." mode="data"/>		</xsl:for-each>	</xsl:template>		<xsl:template match="RESULTS[contains(/RESPONSE/URL/sort, 'DEPENDENCY') = 'true']" mode="listing">		<xsl:param name="dept_type" select="substring-before(substring-after(//URL/sort, '['), ']')"/>		<xsl:for-each select="$data">			<xsl:sort select="DEPENDENCIES/DEPENDENCY[@type=$dept_type]/CONTACT[1]/INFO/LASTNAME | DEPENDENCIES/DEPENDENCY[@type=$dept_type]/CONTACT[1]/INFO/DENOMINATION[../LASTNAME = '']" data-type="{//URL/type}" order="{//URL/order}"/>			<xsl:apply-templates select="." mode="data"/>		</xsl:for-each>	</xsl:template>	<xsl:template match="RESULTS[contains(/RESPONSE/URL/sort, 'CATEGORY') = 'true']" mode="listing">		<xsl:param name="fatherID" select="substring-before(substring-after(//URL/sort, '['), ']')"/>		<xsl:for-each select="$data">			<xsl:sort select="CATEGORIES/CATEGORY[@fatherID=$fatherID]/LABEL" data-type="{//URL/type}" order="{//URL/order}"/>			<xsl:apply-templates select="." mode="data"/>		</xsl:for-each>	</xsl:template>	<xsl:template name="sort">		<xsl:param name="var"/>		<xsl:param name="type">text</xsl:param>		<a href="{//NECTIL/this_script}?sort={$var}&amp;order=descending&amp;type={$type}">			<img src="images/down.gif"/>		</a>		<a href="{//NECTIL/this_script}?sort={$var}&amp;order=ascending&amp;type={$type}">			<img src="images/up.gif"/>		</a>	</xsl:template>	<xsl:template name="add_class">		<xsl:param name="class"/>		<xsl:if test="$class != ''">			<xsl:attribute name="class">				<xsl:value-of select="$class"/>			</xsl:attribute>		</xsl:if>	</xsl:template>	<xsl:template match="*" mode="td">		<xsl:param name="class"/>		<td>			<xsl:call-template name="add_class"/>			<xsl:apply-templates select="." mode="td_content"/>		</td>	</xsl:template>	<xsl:template match="CATEGORIES" mode="td">		<xsl:param name="class"/>		<xsl:param name="fatherID"/>		<td>			<xsl:call-template name="add_class"/>			<xsl:for-each select="CATEGORY[@fatherID = $fatherID]">				<xsl:value-of select="LABEL"/>				<xsl:call-template name="add_coma_if_not_last"/>			</xsl:for-each>		</td>	</xsl:template>	<xsl:template match="DEPENDENCY" mode="td">		<xsl:param name="class"/>		<td>			<xsl:call-template name="add_class"/>			<xsl:call-template name="display_contact_dependency">				<xsl:with-param name="dependency" select="@type" />			</xsl:call-template>		</td>	</xsl:template>	<xsl:template match="*" mode="td_content">		<xsl:value-of select="."/>	</xsl:template>	<xsl:template match="CATEGORY" mode="td_content">		<xsl:value-of select="LABEL"/>	</xsl:template>	<xsl:template match="CATEGORY[UNIQUENAME ='refus']" mode="td_content">		<strong>REFUS: </strong>		<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/CUSTOM/REFUS"/>	</xsl:template>	<xsl:template match="MEDIA" mode="data">		<tr>			<xsl:apply-templates select="DESCRIPTIONS/DESCRIPTION/CUSTOM/NUMBER" mode="td"/>			<xsl:apply-templates select="DESCRIPTIONS/DESCRIPTION/TITLE" mode="td"/>			<xsl:apply-templates select="DESCRIPTIONS/DESCRIPTION/CUSTOM/YEAR" mode="td"/>			<xsl:apply-templates select="CATEGORIES/CATEGORY[@fatherID = '6']" mode="td"/>			<xsl:apply-templates select="CATEGORIES" mode="td">				<xsl:with-param name="fatherID" select="21"/>			</xsl:apply-templates>						<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='BookToEditor']" mode="td"/>			<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='BookToGraphist']" mode="td"/>			<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='BookToBinder']" mode="td"/>			<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='BookToPrinter']" mode="td"/>			<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='BookToPeopleContact']" mode="td"/>		</tr>	</xsl:template>	<xsl:template name="display_contact_dependency">		<xsl:param name="dependency"/>		<xsl:for-each select="*">			<xsl:apply-templates select="." mode="title"/>			<xsl:call-template name="add_coma_if_not_last"/>		</xsl:for-each>	</xsl:template>	<xsl:template match="CONTACT" mode="title">		<xsl:value-of select="INFO/DENOMINATION"/>	</xsl:template>		<xsl:template match="CONTACT[INFO/DENOMINATION = '']" mode="title">		<xsl:value-of select="INFO/TITLE"/>		<xsl:text> </xsl:text>		<xsl:value-of select="INFO/FIRSTNAME"/>		<xsl:text> </xsl:text>		<xsl:value-of select="INFO/LASTNAME"/>	</xsl:template>	<xsl:template name="add_coma_if_not_last">		<xsl:if test="position() != last()">			<xsl:text>, </xsl:text>		</xsl:if>	</xsl:template></xsl:stylesheet>