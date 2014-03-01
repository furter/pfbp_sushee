<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions spécifiques pour les générations automatique de contenu sur base d'un xml de configuration
	-->
	<!-- -->
	<!-- config details - form -->
	<xsl:template match="FORM" mode="config">
		<xsl:param name="onchange"/>
		<form name="{//CONFIG/FORM/@name}" id="{//CONFIG/FORM/@name}" action="{//NECTIL/this_script}">
			<xsl:apply-templates select="*" mode="config">
				<xsl:with-param name="onchange" select="$onchange"/>
			</xsl:apply-templates>
		</form>
	</xsl:template>
	<xsl:template match="MORE" mode="config">
		<xsl:param name="onchange"/>
		<div id="{@name}" class="hidden">
			<xsl:apply-templates select="*" mode="config">
				<xsl:with-param name="onchange" select="$onchange"/>
			</xsl:apply-templates>
		</div>
		<div id="{@name}_button">
			<a class="discreet_button" href="javascript:">
				<xsl:attribute name="onclick">
					<xsl:text>$('#</xsl:text>
					<xsl:value-of select="@name"/>
					<xsl:text>').toggleClass('hidden');</xsl:text>
					<xsl:text>if($('#</xsl:text>
					<xsl:value-of select="@name"/>
					<xsl:text>').hasClass('hidden').toString() == 'true') {$(this).text('</xsl:text>
					<xsl:value-of select="//LABEL[@name='d_browse_more_criterias']"/>
					<xsl:text>');} else {$(this).text('</xsl:text>
					<xsl:value-of select="//LABEL[@name='d_browse_less_criterias']"/>
					<xsl:text>');}</xsl:text>
				</xsl:attribute>
				<xsl:value-of select="//LABEL[@name='d_browse_more_criterias']"/>
			</a>
		</div>
	</xsl:template>
	<xsl:template match="SELECT[@type='ENUM']" mode="config">
		<xsl:param name="onchange"/>
		<xsl:call-template name="form_select_numbers">
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="from" select="@start"/>
			<xsl:with-param name="start" select="0"/>
			<xsl:with-param name="end" select="@end - @start"/>
			<xsl:with-param name="name" select="@path"/>
			<xsl:with-param name="selected_value" select="//URL/*[name() = current()/@path]"/>
			<xsl:with-param name="first_option_label" select="//LABEL[@name='f_select_first_option_label']"/>
			<xsl:with-param name="select_label">
				<xsl:apply-templates select="." mode="label"/>
			</xsl:with-param>
			<xsl:with-param name="options_node" select="//RESULTS/ENUM/E"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="SELECT[@type='COUNTRY']" mode="config">
		<xsl:param name="onchange"/>
		<xsl:call-template name="form_select">
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="name" select="@path"/>
			<xsl:with-param name="selected_value" select="//URL/*[name() = current()/@path]"/>
			<xsl:with-param name="first_option_label" select="//LABEL[@name='f_select_first_option_label']"/>
			<xsl:with-param name="select_label">
				<xsl:value-of select="//LABEL[@name = 'country']"/>
			</xsl:with-param>
			<xsl:with-param name="options_node" select="//RESULTS/*[name() = current()/@type]"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="SELECT[@type='LIST']" mode="config">
		<xsl:param name="onchange"/>
		<xsl:call-template name="form_select">
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="name" select="@path"/>
			<xsl:with-param name="selected_value" select="//URL/*[name() = current()/@path]"/>
			<xsl:with-param name="first_option_label" select="//LABEL[@name='f_select_first_option_label']"/>
			<xsl:with-param name="select_label">
				<xsl:value-of select="//LABEL[@name = current()/@path]"/>
			</xsl:with-param>
			<xsl:with-param name="options_node" select="//RESULTS/LIST[@name = current()/@path]"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="SELECT[@type='CATEGORY']" mode="config">
		<xsl:param name="onchange"/>
		<xsl:param name="selected_value" select="current()/@path"/>
		<xsl:variable name="category" select="//RESULTS//CATEGORY[UNIQUENAME = current()/@path]"/>
		<xsl:call-template name="form_select">
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="first_option_label" select="//LABEL[@name='f_select_first_option_label']"/>
			<xsl:with-param name="name" select="@path"/>
			<xsl:with-param name="selected_value" select="//URL/*[name() = $selected_value]"/>
			<xsl:with-param name="select_label">
				<xsl:value-of select="$category/LABEL"/>
			</xsl:with-param>
			<xsl:with-param name="options_node" select="$category/CATEGORY"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="SELECT[@type='DEPENDENCY']" mode="config">
		<xsl:param name="onchange"/>
		<xsl:param name="selected_value" select="current()/@dep_type"/>
		<xsl:variable name="results_node" select="//RESULTS[@name = $selected_value]"/>
		<xsl:call-template name="form_select">
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="first_option_label" select="//LABEL[@name='f_select_first_option_label']"/>
			<xsl:with-param name="name" select="@path"/>
			<xsl:with-param name="selected_value" select="//URL/*[name() = $selected_value]"/>
			<xsl:with-param name="select_label" select="//LABEL[@name = concat('f_', $selected_value)]"/>
			<xsl:with-param name="options_node" select="$results_node"/>
			<xsl:with-param name="sort_type">
				<xsl:for-each select="//RESULTS[@name = $selected_value]/*[1]">
					<xsl:value-of select="name()"/>
				</xsl:for-each>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="SELECT[@type='MEDIA']" mode="config">
		<xsl:param name="onchange"/>
		<xsl:call-template name="form_select">
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="name" select="@path"/>
			<xsl:with-param name="selected_value" select="//URL/*[name() = current()/@path]"/>
			<xsl:with-param name="first_option_label" select="//LABEL[@name='f_select_first_option_label']"/>
			<xsl:with-param name="select_label">
				<xsl:value-of select="//LABEL[@name = current()/@path]"/>
			</xsl:with-param>
			<xsl:with-param name="options_node" select="//RESULTS[@name = current()/@path]/MEDIA"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="DATA" mode="config">
		<xsl:variable name="value">
			<xsl:apply-templates select="." mode="value"/>
		</xsl:variable>
		<xsl:if test="$value != ''">
			<p>
				<strong>
					<xsl:apply-templates select="." mode="label"/>
					<xsl:text>: </xsl:text>
				</strong>
				<xsl:value-of select="$value"/>
			</p>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
