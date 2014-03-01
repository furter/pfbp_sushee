<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:import href="common.xsl"/>
	<xsl:param name="user" select="/RESPONSE/RESULTS[@name='user']/CONTACT"/>
	<xsl:param name="userInfo" select="/RESPONSE/RESULTS[@name='user']/CONTACT/INFO"/>
	<xsl:template match="/RESPONSE">
		<xsl:call-template name="html"/>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/MEDIATYPE = 'BookSubmission']" mode="specific_head_content">
		<link rel="stylesheet" type="text/css" href="{//NECTIL/host}/common/js/jquery.autocomplete.css"/>
		<script type="text/javascript" src="{//NECTIL/host}/common/js/jquery.autocomplete.min.js">&#xA0;</script>
		<script type="text/javascript">
			<xsl:text>var mailExistingUserID</xsl:text>
			<xsl:if test="//MEDIA[DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = 'BookToPeopleContact']//MEDIA[INFO/MEDIATYPE = 'MailContent']/@ID != ''">
				<xsl:value-of select="//MEDIA[DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = 'BookToPeopleContact']//MEDIA[INFO/MEDIATYPE = 'MailContent']/@ID"/>
			</xsl:if>
			<xsl:text>;</xsl:text>
			<xsl:text>var toCheckContributorType = new Array(</xsl:text>
			<xsl:for-each select="//RESULTS[@name='media']//MEDIA[INFO/MEDIATYPE = 'BookContributor' and DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE != '']">
				<xsl:value-of select="concat($apos, DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE, $apos)"/>
				<xsl:if test="position() != last()">
					<xsl:text>,</xsl:text>
				</xsl:if>
			</xsl:for-each>
			<xsl:text>);</xsl:text>
			<xsl:text>var fromEmail = '</xsl:text>
			<xsl:value-of select="$user/@ID"/>
			<xsl:text>';</xsl:text>
			<xsl:text>var fill_mandatory_fields = '</xsl:text>
			<xsl:value-of select="translate(//LABEL[@name='fill_mandatory_fields'], $apos, $new_apos)"/>
			<xsl:text>';</xsl:text>
			<xsl:text>var BookSubmission_contributor_found = '</xsl:text>
			<xsl:value-of select="translate(//LABEL[@name='BookSubmission_contributor_found'], $apos, $new_apos)"/>
			<xsl:text>';</xsl:text>
			<xsl:text>var BookSubmission_contributor_not_found = '</xsl:text>
			<xsl:value-of select="translate(//LABEL[@name='BookSubmission_contributor_not_found'], $apos, $new_apos)"/>
			<xsl:text>';</xsl:text>
		</script>
		<script type="text/javascript" src="BookSubmission.js?a={//URL/now}">&#xA0;</script>
		<!--
		<script type="text/javascript" src="{//NECTIL/public_url}jquery.smoothanchors2.js">&#xA0;</script>
		<script type="text/javascript">
			<xsl:text>$(document).ready(function(){$.smoothAnchors("normal", "linear", false);});</xsl:text>
		</script>
		-->
	</xsl:template>
	<xsl:template match="MEDIA[INFO/MEDIATYPE='BookSubmission']" mode="content">
		<div id="content">
			<xsl:apply-templates select="." mode="h1"/>
			<h4>
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/SIGNATURE"/>
			</h4>
			<div class="text">
				<xsl:apply-templates select="." mode="header"/>
				<xsl:apply-templates select="." mode="body"/>
			</div>
			<xsl:if test="DEPENDENCIES/DEPENDENCY[@type='mediaContent']/MEDIA">
				<p>
					<em>
						<xsl:value-of select="//LABEL[@name='BookSubmission_steps']"/>
					</em>
				</p>
				<ol class="subnavigation">
					<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='mediaContent']/MEDIA" mode="inside_navigation_item"/>
				</ol>
			</xsl:if>
		</div>
		<xsl:apply-templates select="." mode="specific"/>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/MEDIATYPE = 'BookSubmission']//MEDIA" mode="inside_navigation_item">
		<li>
			<a onclick="moveToStep({position()});">
				<xsl:attribute name="class">
					<xsl:choose>
						<xsl:when test="position() = 1">
							<xsl:text>active</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>inactive</xsl:text>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
				<xsl:apply-templates select="." mode="title"/>
			</a>
		</li>
	</xsl:template>
	<xsl:template match="MEDIA" mode="specific">
		<div id="specific">
			<div id="specific_mask" style="width:{count(DEPENDENCIES/DEPENDENCY/MEDIA) * 305 + 305}px;">
				<xsl:apply-templates select="DEPENDENCIES/DEPENDENCY[@type='mediaContent']/MEDIA" mode="big_preview"/>
			</div>
			<div class="right mask"/>
			<br class="break"/>
		</div>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/MEDIATYPE = 'BookSubmission']//MEDIA" mode="big_preview">
		<xsl:param name="template_class">
			<xsl:choose>
				<xsl:when test="INFO/TEMPLATE != ''">
					<xsl:value-of select="INFO/TEMPLATE"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="concat(INFO/MEDIATYPE, ' ', DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE)"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>
		<div class="{$template_class} preview gouttiere" id="m{@ID}">
			<!--
			<ul class="preview_navigation">
				<xsl:if test="position() &gt; 1">
					<li>
						<a onclick="moveToStep({position() - 1})">
							<xsl:call-template name="prev_arrow"/>
						</a>
					</li>
				</xsl:if>
				<xsl:if test="position() != last()">
					<li>
						<a onclick="moveToStep({position() + 1})">
							<xsl:call-template name="next_arrow"/>
						</a>
					</li>
				</xsl:if>
			</ul>
			-->
			<!--
			<xsl:apply-templates select="." mode="h4"/>
			-->
			<div class="sub_navigation">
				<xsl:call-template name="prev_step"/>
				<xsl:call-template name="next_step">
					<xsl:with-param name="label">
						<xsl:choose>
							<xsl:when test="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = 'BookToOther'">
								<xsl:value-of select="//LABEL[@name='BookSubmission_NextStep']"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:apply-templates select="." mode="button_label"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:with-param>
				</xsl:call-template>
				<br class="break"/>
			</div>
			<div class="info">
				<xsl:apply-templates select="." mode="h2"/>
				<xsl:apply-templates select="." mode="body"/>
				<xsl:apply-templates select="." mode="specific_content">
					<xsl:with-param name="position" select="position()"/>
				</xsl:apply-templates>
			</div>
		</div>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/TEMPLATE = 'BookRemarks']" mode="specific_content">
		<xsl:param name="position"/>
		<xsl:param name="form_name" select="'BookRemarks'"/>
		<form name="{$form_name}" id="{$form_name}" class="BookRemarks">
			<xsl:call-template name="form_textarea">
				<xsl:with-param name="label" select="//LABEL[@name='book_remarks']"/>
				<xsl:with-param name="name" select="'BIBLIO'"/>
			</xsl:call-template>
			<div class="button_container">
				<a onclick="validateAndmoveToNextStep();">
					<xsl:apply-templates select="." mode="button_label"/>
				</a>
			</div>
		</form>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/TEMPLATE = 'BookCandidate']" mode="specific_content">
		<xsl:param name="position"/>
		<xsl:param name="form_name" select="'BookCandidate'"/>
		<form name="{$form_name}" id="{$form_name}" class="BookCandidate">
			<xsl:call-template name="form_input_text">
				<xsl:with-param name="label" select="//LABEL[@name='book_TITLE']"/>
				<xsl:with-param name="name" select="'TITLE'"/>
				<xsl:with-param name="mandatory" select="1"/>
			</xsl:call-template>
			<xsl:call-template name="form_input_text">
				<xsl:with-param name="label" select="//LABEL[@name='book_HEADER']"/>
				<xsl:with-param name="name" select="'HEADER'"/>
			</xsl:call-template>
			<xsl:call-template name="form_input_text">
				<xsl:with-param name="label" select="//LABEL[@name='book_EVENTSTART']"/>
				<xsl:with-param name="name" select="'EVENTSTART'"/>
				<xsl:with-param name="mandatory" select="1"/>
			</xsl:call-template>
			<xsl:call-template name="form_input_text">
				<xsl:with-param name="label" select="//LABEL[@name='book_BOOK_LEGAL_DEPOSIT']"/>
				<xsl:with-param name="name" select="'BOOK_LEGAL_DEPOSIT'"/>
			</xsl:call-template>
			<xsl:call-template name="form_input_text">
				<xsl:with-param name="label" select="//LABEL[@name='book_BOOK_ISBN']"/>
				<xsl:with-param name="name" select="'BOOK_ISBN'"/>
			</xsl:call-template>
			<xsl:for-each select="/RESPONSE/RESULTS[@name='book_theme']/CATEGORY">
				<div class="select_container">
					<label for="{UNIQUENAME}">
						<xsl:value-of select="LABEL"/>
						<small class="mandatory">*</small>
					</label>
					<select class="mandatory" id="{UNIQUENAME}" name="{UNIQUENAME}">
						<option value="">
							<xsl:value-of select="//LABEL[@name='f_select_first_option_label']"/>
						</option>
						<xsl:for-each select="CATEGORY">
							<xsl:sort select="LABEL" data-type="text" order="ascending"/>
							<option value="{@ID}">
								<xsl:value-of select="LABEL"/>
							</option>
						</xsl:for-each>
					</select>
				</div>
				<!--
				<xsl:call-template name="form_select">
					<xsl:with-param name="name" select="UNIQUENAME"/>
					<xsl:with-param name="select_label" select="LABEL"/>
					<xsl:with-param name="first_option_label" select="//LABEL[@name='f_select_first_option_label']"/>
					<xsl:with-param name="first_option_value" select="''"/>
					<xsl:with-param name="options_node" select="CATEGORY"/>
					<xsl:with-param name="mandatory" select="1"/>
				</xsl:call-template>
				-->
			</xsl:for-each>
			<!-- // commenté le 18/11/2012 par N suite à la demande d'annelies
			<div class="input_text_container" id="addBookAuthor">
				<label for="BOOK_AUTHORS">
					<xsl:value-of select="//LABEL[@name='book_BOOK_AUTHORS']"/>
				</label>
				<div class="book_author">
					<input type="text" class="texte" maxlength="" value="" name="BOOK_AUTHORS_firstname[]" id="BOOK_AUTHORS_firstname"/>
					<input type="text" class="texte" maxlength="" value="" name="BOOK_AUTHORS_lastname[]" id="BOOK_AUTHORS_lastname"/>
				</div>
				<br class="break"/>
			</div>
			<div class="small_button_container">
				<a onclick="addBookAuthor();">
					<xsl:value-of select="//LABEL[@name='BookSubmission_add_book_author']"/>
				</a>
			</div>
			-->
			<xsl:call-template name="form_textarea">
				<xsl:with-param name="name" select="'book_REMARKS'"/>
				<xsl:with-param name="label" select="//LABEL[@name='book_remarks']"/>
				<xsl:with-param name="class" select="'c_remark'"/>
			</xsl:call-template>
			<!--xsl:call-template name="prev_step"/-->
			<div class="button_container">
				<a onclick="checkBookForm('{$form_name}');">
					<xsl:apply-templates select="." mode="button_label"/>
				</a>
			</div>
		</form>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/MEDIATYPE = 'BookContributor']" mode="specific_content">
		<xsl:param name="position"/>
		<xsl:param name="type" select="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE"/>
		<xsl:param name="form_name" select="concat(INFO/MEDIATYPE, '_', $type)"/>
		<xsl:if test="$type = 'BookToOther'">
			<div class="other_contributor">
				<!--xsl:call-template name="prev_step"/-->
				<div class="button_container">
					<a onclick="skipStepAndGoFurther();">
						<xsl:value-of select="//LABEL[@name='BookSubmission_NoOtherContributorToEncode']"/>
					</a>
				</div>
				<!--
				<div class="button_container">
					<a onclick="displayOtherContributorForm('#m{@ID}');">
						<xsl:value-of select="//LABEL[@name='BookSubmission_OtherContributorToEncode']"/>
					</a>
				</div>
				-->
			</div>
		</xsl:if>
		<xsl:if test="$type != 'BookToPeopleContact'">
			<xsl:variable name="container_name" select="concat('SEARCH_', $form_name)"/>
			<div class="search_container">
				<!--
				<xsl:if test="$type = 'BookToOther'">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='BookSubmission_search_contributor']"/>
					<xsl:with-param name="name" select="concat('SEARCH_', $form_name)"/>
					<xsl:with-param name="class" select="'search'"/>
				</xsl:call-template>
				-->
				<div class="input_text_container {$container_name}">
					<label for="{$container_name}">
						<xsl:value-of select="//LABEL[@name='BookSubmission_search_contributor']"/>
					</label>
					<br class="break"/>
					<input type="text" class="search ac_input" name="{$container_name}" id="{$container_name}"/>
					<div class="button_container">
						<a onclick="checkContact('{$form_name}');">
							<xsl:value-of select="//LABEL[@name='f_search']"/>
						</a>
					</div>
					<br class="break"/>
					<div class="message"/>
				</div>
			</div>
		</xsl:if>
		<form name="{$form_name}" id="{$form_name}" class="BookContributor {$type}">
			<!--
			<xsl:if test="$type = 'BookToOther'">
				<xsl:attribute name="style">display:none;</xsl:attribute>
			</xsl:if>
			-->
			<input type="hidden" name="cID" value=""/>
			<xsl:call-template name="form_input_text">
				<xsl:with-param name="label" select="//LABEL[@name='Email']"/>
				<xsl:with-param name="name" select="'EMAIL1'"/>
				<xsl:with-param name="mandatory">
					<xsl:if test="$type = 'BookToPeopleContact'">
						<xsl:text>1</xsl:text>
					</xsl:if>
				</xsl:with-param>
				<xsl:with-param name="value">
					<xsl:if test="$user and $type = 'BookToPeopleContact'">
						<xsl:value-of select="$userInfo/EMAIL1"/>
					</xsl:if>
				</xsl:with-param>
				<xsl:with-param name="readonly">
					<xsl:if test="$userInfo">
						<xsl:text>1</xsl:text>
					</xsl:if>
				</xsl:with-param>
			</xsl:call-template>
			<div class="not_vital">
				<xsl:if test="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = 'BookToPeopleContact' and not($userInfo)">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
				<xsl:call-template name="form_select">
					<xsl:with-param name="name" select="'CONTACTTYPE'"/>
					<xsl:with-param name="options_node" select="//LIST[@name='contacttype']/ITEM"/>
					<xsl:with-param name="select_label" select="//LABEL[@name='contacttype']"/>
					<xsl:with-param name="onchange">
						<xsl:text>switch_contact_type('</xsl:text>
						<xsl:value-of select="$form_name"/>
						<xsl:text>');</xsl:text>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_DENOMINATION']"/>
					<xsl:with-param name="name" select="'DENOMINATION'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/DENOMINATION"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="containerStyle">
						<xsl:if test="$user/INFO/CONTACTTYPE = 'PP' or not($user)">
							<xsl:text>display:none;</xsl:text>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory">
						<xsl:if test="$user/INFO/CONTACTTYPE = 'PM'">
							<xsl:text>1</xsl:text>
						</xsl:if>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label">
						<xsl:copy-of select="//LABEL[@name='f_EMAIL2']"/>
					</xsl:with-param>
					<xsl:with-param name="name" select="'EMAIL2'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/EMAIL2"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="containerStyle">
						<xsl:if test="$user/INFO/CONTACTTYPE = 'PP' or not($user)">
							<xsl:text>display:none;</xsl:text>
						</xsl:if>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_FIRSTNAME']"/>
					<xsl:with-param name="name" select="'FIRSTNAME'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/FIRSTNAME"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory">
						<xsl:if test="$user/INFO/CONTACTTYPE = 'PP' or not($user)">
							<xsl:text>1</xsl:text>
						</xsl:if>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_LASTNAME']"/>
					<xsl:with-param name="name" select="'LASTNAME'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/LASTNAME"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory">
						<xsl:if test="$user/INFO/CONTACTTYPE = 'PP' or not($user)">
							<xsl:text>1</xsl:text>
						</xsl:if>
					</xsl:with-param>
				</xsl:call-template>
				<div class="checkboxes_container contributorType">
					<label>
						<xsl:value-of select="//LABEL[@name='BookSubmission_contributorTypes']"/>
						<small class="mandatory">*</small>
					</label>
					<br/>
					<div style="width:50%; float:left">
						<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToGraphist']" mode="checkbox">
							<xsl:with-param name="type" select="$type"/>
							<xsl:with-param name="form_name" select="$form_name"/>
						</xsl:apply-templates>
						<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToEditor']" mode="checkbox">
							<xsl:with-param name="type" select="$type"/>
							<xsl:with-param name="form_name" select="$form_name"/>
						</xsl:apply-templates>
						<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToPrinter']" mode="checkbox">
							<xsl:with-param name="type" select="$type"/>
							<xsl:with-param name="form_name" select="$form_name"/>
						</xsl:apply-templates>
						<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToBinder']" mode="checkbox">
							<xsl:with-param name="type" select="$type"/>
							<xsl:with-param name="form_name" select="$form_name"/>
						</xsl:apply-templates>
						<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToIllustrator']" mode="checkbox">
							<xsl:with-param name="type" select="$type"/>
							<xsl:with-param name="form_name" select="$form_name"/>
						</xsl:apply-templates>
					</div>
					<xsl:if test="$type != 'BookToPeopleContact'">
						<div style="width:50%; float:left">
							<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToAuthor']" mode="checkbox">
								<xsl:with-param name="type" select="$type"/>
								<xsl:with-param name="form_name" select="$form_name"/>
							</xsl:apply-templates>
							<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToPhotograph']" mode="checkbox">
								<xsl:with-param name="type" select="$type"/>
								<xsl:with-param name="form_name" select="$form_name"/>
							</xsl:apply-templates>
							<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToTranslator']" mode="checkbox">
								<xsl:with-param name="type" select="$type"/>
								<xsl:with-param name="form_name" select="$form_name"/>
							</xsl:apply-templates>
							<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToOther']" mode="checkbox">
								<xsl:with-param name="type" select="$type"/>
								<xsl:with-param name="form_name" select="$form_name"/>
								<xsl:with-param name="onclick">
									<xsl:text>$('#</xsl:text>
									<xsl:value-of select="$form_name"/>
									<xsl:text>_other').toggle();</xsl:text>
								</xsl:with-param>
							</xsl:apply-templates>
							<br class="break"/>
						</div>
						<xsl:call-template name="form_input_text">
							<xsl:with-param name="label" select="//LABEL[@name='f_BookOtherContributorType']"/>
							<xsl:with-param name="name" select="'BookOtherContributorType'"/>
							<xsl:with-param name="containerClass" select="'BookOtherContributorType'"/>
							<xsl:with-param name="containerID" select="concat($form_name,'_other')"/>
							<xsl:with-param name="containerStyle" select="'display:none;'"/>
						</xsl:call-template>
					</xsl:if>
					<br class="break"/>
				</div>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_PHONE1']"/>
					<xsl:with-param name="name" select="'PHONE1'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/PHONE1"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory">
						<xsl:if test="$type = 'BookToPeopleContact'">
							<xsl:text>1</xsl:text>
						</xsl:if>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_MOBILEPHONE']"/>
					<xsl:with-param name="name" select="'MOBILEPHONE'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/MOBILEPHONE"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory" select="0"/>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_ADDRESS']"/>
					<xsl:with-param name="name" select="'ADDRESS'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/ADDRESS"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory">
						<xsl:text>1</xsl:text>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_POSTALCODE']"/>
					<xsl:with-param name="name" select="'POSTALCODE'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/POSTALCODE"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory">
						<xsl:text>1</xsl:text>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_CITY']"/>
					<xsl:with-param name="name" select="'CITY'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/CITY"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory">
						<xsl:text>1</xsl:text>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_select_country">
					<xsl:with-param name="select_label" select="//LABEL[@name='f_COUNTRYID']"/>
					<xsl:with-param name="name" select="'COUNTRYID'"/>
					<xsl:with-param name="selected_value">
						<xsl:choose>
							<xsl:when test="$user and $type = 'BookToPeopleContact' and $userInfo/COUNTRYID != ''">
								<xsl:value-of select="$userInfo/COUNTRYID"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>bel</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:with-param>
					<xsl:with-param name="mandatory">
						<xsl:text>1</xsl:text>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_textarea">
					<xsl:with-param name="name" select="'book_REMARKS'"/>
					<xsl:with-param name="label" select="//LABEL[@name='book_remarks']"/>
					<xsl:with-param name="class" select="'c_remark'"/>
				</xsl:call-template>
				<xsl:call-template name="form_next_step">
					<xsl:with-param name="form_name" select="$form_name"/>
				</xsl:call-template>
			</div>
			<div class="empty">
				<xsl:if test="$type = 'BookToPeopleContact' and not($userInfo)">
					<div class="button_container">
						<a>
							<xsl:attribute name="onclick">
								<xsl:text>checkEmail('</xsl:text>
								<xsl:value-of select="$form_name"/>
								<xsl:text>', </xsl:text>
								<xsl:value-of select="//MEDIA[DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = 'BookToPeopleContact']//MEDIA[INFO/MEDIATYPE = 'MailContent']/@ID"/>
								<xsl:text>);</xsl:text>
							</xsl:attribute>
							<xsl:value-of select="//LABEL[@name='BookSubmission_check_mail_in_db']"/>
						</a>
					</div>
				</xsl:if>
			</div>
		</form>
		<div class="contributor_already_endoded" style="display:none;">
			<div class="message">
				<p>
					<xsl:value-of select="//LABEL[@name='BookSubmission_contributor_already_encoded']"/>
				</p>
			</div>
			<div class="auto_complete"/>
			<!--xsl:call-template name="prev_step"/-->
			<div class="button_container">
				<a onclick="validateAndmoveToNextStep();">
					<xsl:apply-templates select="." mode="button_label"/>
				</a>
			</div>
		</div>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/TEMPLATE = '' and INFO/MEDIATYPE = 'SubmissionStep']" mode="specific_content">
		<div class="button_container">
			<a onclick="validateAndmoveToNextStep();">
				<xsl:apply-templates select="." mode="button_label"/>
			</a>
		</div>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/TEMPLATE = 'BookSubmissionSummary']" mode="specific_content">
		<xsl:param name="position"/>
		<div class="autoComplete">
			<xsl:apply-templates select="$media/DEPENDENCIES/DEPENDENCY/MEDIA[INFO/TEMPLATE = 'BookCandidate']" mode="h4"/>
			<div class="book"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToAuthor']" mode="auto_complete_box"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToEditor']" mode="auto_complete_box"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToGraphist']" mode="auto_complete_box"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToPrinter']" mode="auto_complete_box"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToBinder']" mode="auto_complete_box"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToPhotograph']" mode="auto_complete_box"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToIllustrator']" mode="auto_complete_box"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToTranslator']" mode="auto_complete_box"/>
			<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value='BookToOther']" mode="auto_complete_box"/>
			<xsl:apply-templates select="(//MEDIA[INFO/TEMPLATE = 'BookRemarks']) [1]" mode="auto_complete_box"/>
		</div>
		<!--xsl:call-template name="prev_step"/-->
		<div class="button_container" style="display:none;">
			<a onclick="sendBookSubmission({//MEDIA[INFO/TEMPLATE = 'BookSubmissionConfirm']//MEDIA[INFO/MEDIATYPE = 'MailContent']/@ID});">
				<xsl:apply-templates select="." mode="button_label"/>
			</a>
		</div>
	</xsl:template>
	<xsl:template match="MEDIA" mode="auto_complete_box">
		<h4 class="{INFO/TEMPLATE}">
			<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/>
		</h4>
		<div class="{INFO/TEMPLATE}"/>
	</xsl:template>
	<xsl:template match="ITEM" mode="auto_complete_box">
		<h4 class="{@value}">
			<xsl:value-of select="@label"/>
		</h4>
		<div class="{@value}"/>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/TEMPLATE = 'BookSubmissionConfirm']" mode="specific_content">
		<xsl:param name="position"/>
		<div class="autoComplete"/>
	</xsl:template>
	<xsl:template name="prev_step">
		<xsl:if test="./preceding-sibling::MEDIA">
			<div class="button_container prev">
				<a onclick="goBack();">
					<xsl:value-of select="//LABEL[@name='BookSubmission_PrevStep']"/>
				</a>
			</div>
		</xsl:if>
	</xsl:template>
	<xsl:template name="next_step">
		<xsl:param name="label">
			<xsl:apply-templates select="." mode="button_label"/>
		</xsl:param>
		<xsl:param name="type" select="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE"/>
		<xsl:param name="form_name">
			<xsl:choose>
				<xsl:when test="INFO/TEMPLATE = 'BookCandidate'">
					<xsl:value-of select="'BookCandidate'"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="concat(INFO/MEDIATYPE, '_', $type)"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>
		<xsl:if test="./following-sibling::MEDIA">
			<!--
			<div class="button_container next">
				<a onclick="skipStepAndGoFurther();">
					<xsl:value-of select="//LABEL[@name='BookSubmission_NextStep']"/>
				</a>
			</div>
			-->
			<div class="button_container next">
				<xsl:choose>
					<xsl:when test="INFO/TEMPLATE = 'BookCandidate'">
						<a onclick="checkBookForm('{$form_name}');">
							<xsl:value-of select="$label"/>
						</a>
					</xsl:when>
					<xsl:when test="INFO/TEMPLATE = 'BookSubmissionSummary'">
						<a onclick="sendBookSubmission({//MEDIA[INFO/TEMPLATE = 'BookSubmissionConfirm']//MEDIA[INFO/MEDIATYPE = 'MailContent']/@ID});">
							<xsl:value-of select="$label"/>
						</a>
					</xsl:when>
					<xsl:when test="INFO/MEDIATYPE = 'SubmissionStep' and INFO/TEMPLATE = ''">
						<a onclick="validateAndmoveToNextStep();">
							<xsl:value-of select="$label"/>
						</a>
					</xsl:when>
					<!--
					<xsl:when test="$type = 'BookToOther'">
						<a>
							<xsl:attribute name="onclick">
								<xsl:text>checkContributorForm('</xsl:text>
								<xsl:value-of select="$form_name"/>
								<xsl:text>');</xsl:text>
								<xsl:if test="$type = 'BookToOther'">
									<xsl:text>addOtherContributorForm('#m</xsl:text>
									<xsl:value-of select="@ID"/>
									<xsl:text>');</xsl:text>
								</xsl:if>
							</xsl:attribute>
							<xsl:apply-templates select="." mode="button_label"/>
						</a>
					</xsl:when>
					-->
					<xsl:when test="$type = 'BookToBinder'">
						<a onclick="skipStepAndGoFurther();">
							<xsl:value-of select="$label"/>
						</a>
					</xsl:when>
					<xsl:otherwise>
						<a onclick="checkContributorForm('{$form_name}');">
							<xsl:value-of select="$label"/>
						</a>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</xsl:if>
	</xsl:template>
	<xsl:template name="form_next_step">
		<xsl:param name="form_name"/>
		<xsl:param name="type" select="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE"/>
		<!--xsl:call-template name="prev_step"/-->
		<xsl:if test="$type = ''">
			<div class="button_container">
				<a onclick="checkContributorForm('{$form_name}');">
					<xsl:apply-templates select="." mode="button_label"/>
				</a>
			</div>
		</xsl:if>
		<xsl:if test="$type = 'BookToOther'">
			<div class="button_container">
				<!--
				<a onclick="cancelOtherContributor('#m{@ID}');">
				-->
				<a onclick="skipStepAndGoFurther();">
					<xsl:value-of select="//LABEL[@name='BookSubmission_cancelOtherContributor']"/>
				</a>
			</div>
		</xsl:if>
		<div class="button_container">
			<xsl:choose>
				<xsl:when test="$type = 'BookToBinder'">
					<a onclick="skipStepAndGoFurther();">
						<xsl:apply-templates select="." mode="button_label"/>
					</a>
				</xsl:when>
				<xsl:otherwise>
					<a>
						<xsl:attribute name="onclick">
							<xsl:text>checkContributorForm('</xsl:text>
							<xsl:value-of select="$form_name"/>
							<xsl:text>');</xsl:text>
							<!--
							<xsl:if test="$type = 'BookToOther'">
								<xsl:text>addOtherContributorForm('#m</xsl:text>
								<xsl:value-of select="@ID"/>
								<xsl:text>');</xsl:text>
							</xsl:if>
							-->
						</xsl:attribute>
						<xsl:apply-templates select="." mode="button_label"/>
					</a>
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>
	<xsl:template match="MEDIA" mode="button_label">
		<xsl:choose>
			<xsl:when test="DESCRIPTIONS/DESCRIPTION[@languageID != 'shared']/SIGNATURE != ''">
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION[@languageID != 'shared']/SIGNATURE"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="//LABEL[@name='BookSubmission_NextStep']"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="ITEM" mode="checkbox">
		<xsl:param name="type"/>
		<xsl:param name="form_name"/>
		<xsl:param name="onclick"/>
		<div class="checkbox_container">
			<input type="checkbox" value="{@value}" name="contributorType[]" id="{$form_name}_CT_{@value}">
				<xsl:if test="$type = @value and $type != 'BookToOther'">
					<xsl:attribute name="checked">checked</xsl:attribute>
				</xsl:if>
				<xsl:if test="$onclick !=''">
					<xsl:attribute name="onclick">
						<xsl:value-of select="$onclick"/>
					</xsl:attribute>
				</xsl:if>
			</input>
			<label for="{$form_name}_CT_{@value}">
				<xsl:value-of select="@label"/>
			</label>
		</div>
	</xsl:template>
</xsl:stylesheet>
