<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions génériques pour générer
		- input
		- select (normal, pays, nombres, ...)
		- captcha
		- vérification de champs obligatoire pour le label et le champs
	-->
	<!-- -->
	<!-- mandatory -->
	<xsl:template name="check_mandatory">
		<xsl:param name="mandatory"/>
		<xsl:if test="$mandatory = 1">
			<small class="mandatory" title="{//LABEL[@name='mandatory']}">*</small>
		</xsl:if>
	</xsl:template>
	<xsl:template name="check_mandatory_class">
		<xsl:param name="mandatory"/>
		<xsl:if test="$mandatory = 1">
			<xsl:attribute name="class">mandatory</xsl:attribute>
		</xsl:if>
	</xsl:template>
	<!-- -->
	<!-- input text -->
	<xsl:template name="form_input_text">
		<xsl:param name="label"/>
		<xsl:param name="containerID"/>
		<xsl:param name="containerClass" select="'input_text_container'"/>
		<xsl:param name="containerStyle"/>
		<xsl:param name="value"/>
		<xsl:param name="class">texte</xsl:param>
		<xsl:param name="type">text</xsl:param>
		<xsl:param name="name"/>
		<xsl:param name="onfocus"/>
		<xsl:param name="onkeypress"/>
		<xsl:param name="onblur"/>
		<xsl:param name="maxchars"/>
		<xsl:param name="mandatory"/>
		<xsl:param name="more_info"/>
		<div class="{$containerClass} {$name}">
			<xsl:if test="$containerID != ''">
				<xsl:attribute name="id">
					<xsl:value-of select="$containerID"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="$containerStyle != ''">
				<xsl:attribute name="style">
					<xsl:value-of select="$containerStyle"/>
				</xsl:attribute>
			</xsl:if>
			<label for="{$name}">
				<xsl:value-of select="$label"/>
				<xsl:call-template name="check_mandatory">
					<xsl:with-param name="mandatory" select="$mandatory"/>
				</xsl:call-template>
			</label>
			<input type="{$type}" id="{$name}" name="{$name}" value="{$value}" maxlength="{$maxchars}">
				<xsl:if test="$onblur != ''">
					<xsl:attribute name="onblur">
						<xsl:value-of select="$onblur"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:if test="$onkeypress != ''">
					<xsl:attribute name="onkeypress">
						<xsl:value-of select="$onkeypress"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:if test="$onfocus != ''">
					<xsl:attribute name="onfocus">
						<xsl:value-of select="$onfocus"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:attribute name="class">
					<xsl:value-of select="$class"/>
					<xsl:if test="$mandatory = 1">
						<xsl:text> mandatory</xsl:text>
					</xsl:if>
				</xsl:attribute>
			</input>
			<xsl:if test="$more_info != ''">
				<dfn>
					<xsl:value-of select="$more_info"/>
				</dfn>
			</xsl:if>
			<br class="break"/>
		</div>
	</xsl:template>
	<!-- captcha -->
	<!-- -->
	<xsl:template name="form_captcha">
		<xsl:param name="label" select="//LABEL[@name='f_captcha']"/>
		<xsl:param name="name">visitor-guess</xsl:param>
		<xsl:param name="mandatory" select="1"/>
		<xsl:param name="more_info"/>
		<div class="captcha_container">
			<label for="{$name}">
				<xsl:value-of select="$label"/>
				<xsl:call-template name="check_mandatory">
					<xsl:with-param name="mandatory" select="$mandatory"/>
				</xsl:call-template>
			</label>
			<div class="img_captcha">
				<!--img src="captcha.php" alt="{$label}" class="captcha"/-->
				<img src="{//NECTIL/files_url}{/RESPONSE/RESULTS/CAPTCHA}" alt="{$label}" class="captcha"/>
			</div>
			<input type="text" id="{$name}" name="{$name}" class="texte"/>
			<xsl:if test="$more_info != ''">
				<dfn>
					<xsl:value-of select="$more_info"/>
				</dfn>
			</xsl:if>
			<br class="break"/>
		</div>
	</xsl:template>
	<!-- radio -->
	<!-- -->
	<xsl:template name="form_radio">
		<xsl:param name="label"/>
		<xsl:param name="value"/>
		<xsl:param name="aID"/>
		<xsl:param name="name"/>
		<xsl:param name="mandatory"/>
		<input type="radio" id="{$aID}" name="{$name}" value="{$value}"/>
		<label for="{$aID}">
			<xsl:value-of select="$label"/>
			<xsl:call-template name="check_mandatory"/>
		</label>
	</xsl:template>
	<!-- -->
	<!-- textarea -->
	<xsl:template name="form_textarea">
		<xsl:param name="label"/>
		<xsl:param name="onclick"/>
		<xsl:param name="onfocus"/>
		<xsl:param name="value"/>
		<xsl:param name="class">texte</xsl:param>
		<xsl:param name="name"/>
		<xsl:param name="maxchars"/>
		<xsl:param name="mandatory"/>
		<div class="textarea_container">
			<xsl:if test="$label != ''">
				<label for="{$name}">
					<xsl:value-of select="$label"/>
					<xsl:call-template name="check_mandatory">
						<xsl:with-param name="mandatory" select="$mandatory"/>
					</xsl:call-template>
				</label>
			</xsl:if>
			<textarea id="{$name}" name="{$name}" rows="" cols="">
				<xsl:if test="$onclick != ''">
					<xsl:attribute name="onclick">
						<xsl:value-of select="$onclick"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:if test="$maxchars != ''">
					<xsl:attribute name="maxlength">
						<xsl:value-of select="$maxchars"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:if test="$onfocus != ''">
					<xsl:attribute name="onfocus">
						<xsl:value-of select="$onfocus"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:attribute name="class">
					<xsl:value-of select="$class"/>
					<xsl:if test="$mandatory = 1">
						<xsl:text> mandatory</xsl:text>
					</xsl:if>
				</xsl:attribute>
				<xsl:value-of select="$value"/>
			</textarea>
		</div>
	</xsl:template>
	<!-- -->
	<!-- select -->
	<!-- base -->
	<xsl:template name="form_select">
		<xsl:param name="name"/>
		<xsl:param name="onchange"/>
		<xsl:param name="sort_type"/>
		<xsl:param name="mandatory"/>
		<xsl:param name="select_label"/>
		<xsl:param name="first_option_label"/>
		<xsl:param name="first_option_value"/>
		<xsl:param name="options_node"/>
		<xsl:param name="selected_value"/>
		<xsl:param name="from"/>
		<xsl:param name="start"/>
		<xsl:param name="end"/>
		<xsl:param name="containerStyle"/>
		<div class="select_container {$name}">
			<xsl:if test="$containerStyle != ''">
				<xsl:attribute name="style">
					<xsl:value-of select="$containerStyle"/>
				</xsl:attribute>
			</xsl:if>
			<label for="{$name}">
				<!--span class="position">
					<xsl:value-of select="position()"/>
					<xsl:text>. </xsl:text>
				</span-->
				<xsl:value-of select="$select_label"/>
				<xsl:call-template name="check_mandatory">
					<xsl:with-param name="mandatory" select="$mandatory"/>
				</xsl:call-template>
			</label>
			<!--br/-->
			<select name="{$name}" id="{$name}">
				<xsl:if test="$mandatory = 1">
					<xsl:attribute name="class">mandatory</xsl:attribute>
				</xsl:if>
				<xsl:if test="$onchange != ''">
					<xsl:attribute name="onchange">
						<xsl:value-of select="$onchange"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:if test="$first_option_label != ''">
					<option value="{$first_option_value}">
						<xsl:value-of select="$first_option_label"/>
					</option>
				</xsl:if>
				<xsl:choose>
					<xsl:when test="$sort_type = 'CONTACT'">
						<xsl:for-each select="$options_node/*">
							<xsl:sort select="INFO/DENOMINATION | INFO/LASTNAME[../CONTACTTYPE = 'PP']  " data-type="text" order="ascending"/>
							<xsl:apply-templates select="." mode="select_option">
								<xsl:with-param name="selected_value" select="$selected_value"/>
								<xsl:with-param name="from" select="$from"/>
								<xsl:with-param name="start" select="$start"/>
								<xsl:with-param name="end" select="$end"/>
							</xsl:apply-templates>
						</xsl:for-each>
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="$options_node" mode="select_option">
							<xsl:with-param name="selected_value" select="$selected_value"/>
							<xsl:with-param name="from" select="$from"/>
							<xsl:with-param name="start" select="$start"/>
							<xsl:with-param name="end" select="$end"/>
						</xsl:apply-templates>
					</xsl:otherwise>
				</xsl:choose>
			</select>
			<br class="break"/>
		</div>
	</xsl:template>
	<!-- nombres -->
	<xsl:template name="form_select_numbers">
		<xsl:param name="onchange"/>
		<xsl:param name="start"/>
		<xsl:param name="from"/>
		<xsl:param name="end"/>
		<xsl:param name="name"/>
		<xsl:param name="mandatory"/>
		<xsl:param name="select_label"/>
		<xsl:param name="first_option_label"/>
		<xsl:param name="first_option_value"/>
		<xsl:param name="options_node" select="/RESPONSE/RESULTS/ENUM/E[. &gt;= $start and . &lt;= $end]"/>
		<xsl:param name="selected_value"/>
		<xsl:call-template name="form_select">
			<xsl:with-param name="name" select="$name"/>
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="mandatory" select="$mandatory"/>
			<xsl:with-param name="select_label" select="$select_label"/>
			<xsl:with-param name="first_option_label" select="$first_option_label"/>
			<xsl:with-param name="first_option_value" select="$first_option_value"/>
			<xsl:with-param name="options_node" select="$options_node"/>
			<xsl:with-param name="selected_value" select="$selected_value"/>
			<xsl:with-param name="from" select="$from"/>
			<xsl:with-param name="start" select="$start"/>
			<xsl:with-param name="end" select="$end"/>
		</xsl:call-template>
	</xsl:template>
	<!-- locations - à garder? -->
	<xsl:template name="form_select_locations">
		<xsl:param name="onchange"/>
		<xsl:param name="selected_value"/>
		<xsl:param name="name"/>
		<xsl:call-template name="form_select">
			<xsl:with-param name="name" select="$name"/>
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="mandatory" select="1"/>
			<xsl:with-param name="select_label" select="//LABEL[@name='city']"/>
			<xsl:with-param name="first_option_label" select="//LABEL[@name='select_answer']"/>
			<xsl:with-param name="first_option_value">null</xsl:with-param>
			<xsl:with-param name="options_node" select="/RESPONSE/RESULTS[@name='localites']/LIST/ITEM"/>
			<xsl:with-param name="selected_value" select="$selected_value"/>
		</xsl:call-template>
	</xsl:template>
	<!-- - - pays -->
	<xsl:template name="form_select_country">
		<xsl:param name="onchange"/>
		<xsl:param name="name"/>
		<xsl:param name="selected_value"/>
		<xsl:param name="first_option_label" select="//LABEL[@name='f_select_first_option_label']"/>
		<xsl:param name="select_label" select="//LABEL[@name = 'country']"/>
		<xsl:call-template name="form_select">
			<xsl:with-param name="name" select="$name"/>
			<xsl:with-param name="onchange" select="$onchange"/>
			<xsl:with-param name="selected_value" select="$selected_value"/>
			<xsl:with-param name="first_option_label" select="$first_option_label"/>
			<xsl:with-param name="select_label" select="$select_label"/>
			<xsl:with-param name="options_node" select="/RESPONSE/RESULTS/COUNTRY"/>
		</xsl:call-template>
	</xsl:template>
	<!-- - - - les différentes possibilités pour les éléments d'un select -->
	<xsl:template name="form_select_option">
		<xsl:param name="selected_value"/>
		<xsl:param name="label"/>
		<xsl:param name="value"/>
		<option value="{$value}">
			<xsl:if test="$value = $selected_value">
				<xsl:attribute name="selected"/>
			</xsl:if>
			<xsl:value-of select="$label"/>
		</option>
	</xsl:template>
	<xsl:template match="LABEL" mode="select_option">
		<xsl:param name="selected_value"/>
		<xsl:call-template name="form_select_option">
			<xsl:with-param name="selected_value" select="$selected_value"/>
			<xsl:with-param name="label" select="."/>
			<xsl:with-param name="value" select="."/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="CONTACT | MEDIA" mode="select_option">
		<xsl:param name="selected_value"/>
		<xsl:call-template name="form_select_option">
			<xsl:with-param name="selected_value" select="$selected_value"/>
			<xsl:with-param name="label">
				<xsl:apply-templates select="." mode="title"/>
			</xsl:with-param>
			<xsl:with-param name="value" select="@ID"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="COUNTRY" mode="select_option">
		<xsl:param name="selected_value"/>
		<xsl:call-template name="form_select_option">
			<xsl:with-param name="selected_value" select="$selected_value"/>
			<xsl:with-param name="label" select="LABEL"/>
			<xsl:with-param name="value" select="@ID"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="CATEGORY" mode="select_option">
		<xsl:param name="selected_value"/>
		<xsl:call-template name="form_select_option">
			<xsl:with-param name="selected_value" select="$selected_value"/>
			<xsl:with-param name="label">
				<xsl:if test="@depth = 4">
					<xsl:text> - </xsl:text>
				</xsl:if>
				<xsl:value-of select="LABEL"/>
			</xsl:with-param>
			<xsl:with-param name="value" select="@path"/>
		</xsl:call-template>
		<xsl:apply-templates select="CATEGORY" mode="select_option">
			<xsl:with-param name="selected_value" select="$selected_value"/>
		</xsl:apply-templates>
	</xsl:template>
	<xsl:template match="ITEM" mode="select_option">
		<xsl:param name="selected_value"/>
		<xsl:call-template name="form_select_option">
			<xsl:with-param name="selected_value" select="$selected_value"/>
			<xsl:with-param name="label" select="@label"/>
			<xsl:with-param name="value" select="@value"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template match="E" mode="select_option">
		<xsl:param name="selected_value"/>
		<xsl:param name="from"/>
		<xsl:param name="start"/>
		<xsl:param name="end"/>
		<xsl:if test=". &gt;= $start and . &lt;= $end ">
			<xsl:variable name="result">
				<xsl:value-of select="$from + ."/>
			</xsl:variable>
			<xsl:call-template name="form_select_option">
				<xsl:with-param name="selected_value" select="$selected_value"/>
				<xsl:with-param name="label" select="$result"/>
				<xsl:with-param name="value" select="$result"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<!-- -->
	<!-- bouton -->
	<!-- -->
	<!-- boutons des formulaires -->
	<xsl:template match="MEDIA | ITEM" mode="button">
		<div class="input_button_container">
			<input type="submit" class="button" value="send"/>
		</div>
	</xsl:template>
</xsl:stylesheet>
