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
		<script type="text/javascript" src="{//NECTIL/host}/common/js/autoCompleteContact.js">&#xA0;</script>
		<script type="text/javascript">
			function checkContact () {
				$('form .message').html('');			
				contact = $('#DENOMINATION').val();
				if (contact != '') {
					$.post('loadCheckContact.php', {contact: contact}, function (data) {
						//$('form .message').html(data);
						var reponse = eval('(' + data + ')');
						result = reponse.reponse;
						if ( result == 0 ) {
							$('form .message').html('le formulaire');
						} else {
							cID = reponse.cID;
							if ( result == 2 ) {
								$('form .message').html(cID+' split la chaine');
							} else {
							$('form .message').html(cID+' au suivant');
							}
						}
					});
				}
			}
			<xsl:text>$(document).ready(function(){autoCompleteContact();});</xsl:text>
		</script>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/MEDIATYPE='BookSubmission']" mode="content">
		<xsl:variable name="form_name" select="'form'"/>
		<div id="content">
			<form name="{$form_name}" id="{$form_name}" class="BookContributor">
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_DENOMINATION']"/>
					<xsl:with-param name="name" select="'DENOMINATION'"/>
					<xsl:with-param name="onblur" select="'checkContact();'"/>
				</xsl:call-template>
				<div class="message"/>
			</form>
		</div>
	</xsl:template>
	<xsl:template match="MEDIA[INFO/MEDIATYPE = 'BookContributor']" mode="specific_content">
		<xsl:param name="position"/>
		<xsl:param name="type" select="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE"/>
		<xsl:param name="form_name" select="concat(INFO/MEDIATYPE, '_', $type)"/>
		<xsl:if test="$type = 'BookToOther'">
			<div class="other_contributor">
				<!--xsl:call-template name="prev_step"/-->
				<div class="button_container">
					<a onclick="summarizeSubmission();">
						<xsl:value-of select="//LABEL[@name='BookSubmission_NoOtherContributorToEncode']"/>
					</a>
				</div>
				<div class="button_container">
					<a onclick="$('#m{@ID} form').show(); $('#m{@ID} .other_contributor').hide(); ">
						<xsl:value-of select="//LABEL[@name='BookSubmission_OtherContributorToEncode']"/>
					</a>
				</div>
			</div>
		</xsl:if>
		<form name="{$form_name}" id="{$form_name}" class="BookContributor {$type}">
			<xsl:if test="$type = 'BookToOther'">
				<xsl:attribute name="style">display:none;</xsl:attribute>
			</xsl:if>
			<xsl:call-template name="form_input_text">
				<xsl:with-param name="label" select="//LABEL[@name='Email']"/>
				<xsl:with-param name="name" select="'EMAIL1'"/>
				<xsl:with-param name="mandatory" select="1"/>
				<!--
				<xsl:with-param name="onblur">
					<xsl:if test="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = 'BookToPeopleContact'">
						<xsl:text>checkEmail('</xsl:text>
						<xsl:value-of select="$form_name"/>
						<xsl:text>', </xsl:text>
						<xsl:value-of select="//MEDIA[DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = 'BookToPeopleContact']//MEDIA[INFO/MEDIATYPE = 'MailContent']/@ID"/>
						<xsl:text>);</xsl:text>
					</xsl:if>
				</xsl:with-param>
				-->
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
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_DENOMINATION']"/>
					<xsl:with-param name="name" select="'DENOMINATION'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/DENOMINATION"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory" select="3"/>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_FIRSTNAME']"/>
					<xsl:with-param name="name" select="'FIRSTNAME'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/FIRSTNAME"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory" select="3"/>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_LASTNAME']"/>
					<xsl:with-param name="name" select="'LASTNAME'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/LASTNAME"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory" select="3"/>
				</xsl:call-template>
				<div class="checkboxes_container contributorType">
					<label>
						<xsl:value-of select="//LABEL[@name='BookSubmission_contributorTypes']"/>
						<small class="mandatory">*</small>
					</label>
					<br/>
					<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToGraphist']" mode="checkbox">
						<xsl:with-param name="type" select="$type"/>
						<xsl:with-param name="form_name" select="$form_name"/>
					</xsl:apply-templates>
					<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToEditor']" mode="checkbox">
						<xsl:with-param name="type" select="$type"/>
						<xsl:with-param name="form_name" select="$form_name"/>
					</xsl:apply-templates>
					<br class="break"/>
					<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToPrinter']" mode="checkbox">
						<xsl:with-param name="type" select="$type"/>
						<xsl:with-param name="form_name" select="$form_name"/>
					</xsl:apply-templates>
					<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToBinder']" mode="checkbox">
						<xsl:with-param name="type" select="$type"/>
						<xsl:with-param name="form_name" select="$form_name"/>
					</xsl:apply-templates>
					<br class="break"/>
					<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToIllustrator']" mode="checkbox">
						<xsl:with-param name="type" select="$type"/>
						<xsl:with-param name="form_name" select="$form_name"/>
					</xsl:apply-templates>
					<xsl:if test="$type != 'BookToPeopleContact'">
						<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToAuthor']" mode="checkbox">
							<xsl:with-param name="type" select="$type"/>
							<xsl:with-param name="form_name" select="$form_name"/>
						</xsl:apply-templates>
						<br class="break"/>
						<xsl:apply-templates select="//LIST[@name='contributorType']/ITEM[@value = 'BookToPhotograph']" mode="checkbox">
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
					<xsl:with-param name="mandatory" select="2"/>
				</xsl:call-template>
				<xsl:call-template name="form_input_text">
					<xsl:with-param name="label" select="//LABEL[@name='f_MOBILEPHONE']"/>
					<xsl:with-param name="name" select="'MOBILEPHONE'"/>
					<xsl:with-param name="value">
						<xsl:if test="$user and $type = 'BookToPeopleContact'">
							<xsl:value-of select="$userInfo/MOBILEPHONE"/>
						</xsl:if>
					</xsl:with-param>
					<xsl:with-param name="mandatory" select="2"/>
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
						<xsl:if test="$type = 'BookToPeopleContact'">
							<xsl:text>1</xsl:text>
						</xsl:if>
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
						<xsl:if test="$type = 'BookToPeopleContact'">
							<xsl:text>1</xsl:text>
						</xsl:if>
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
						<xsl:if test="$type = 'BookToPeopleContact'">
							<xsl:text>1</xsl:text>
						</xsl:if>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:call-template name="form_select_country">
					<xsl:with-param name="select_label" select="//LABEL[@name='f_COUNTRYID']"/>
					<xsl:with-param name="name" select="'COUNTRYID'"/>
					<xsl:with-param name="selected_value"/>
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
						<xsl:if test="$type = 'BookToPeopleContact'">
							<xsl:text>1</xsl:text>
						</xsl:if>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:if test="$type = 'BookToBinder'">
					<div class="button_container next">
						<a onclick="goFurther();">
							<xsl:value-of select="//LABEL[@name='BookSubmission_skip_binder']"/>
						</a>
					</div>
				</xsl:if>
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
			<xsl:for-each select="//LIST[@name='contributorType']/ITEM">
				<h4>
					<xsl:value-of select="@label"/>
				</h4>
				<div class="{@value}"/>
			</xsl:for-each>
		</div>
		<!--xsl:call-template name="prev_step"/-->
		<div class="button_container" style="display:none;">
			<a onclick="sendBookSubmission({//MEDIA[INFO/TEMPLATE = 'BookSubmissionConfirm']//MEDIA[INFO/MEDIATYPE = 'MailContent']/@ID});">
				<xsl:apply-templates select="." mode="button_label"/>
			</a>
		</div>
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
				<a onclick="goFurther();">
					<xsl:value-of select="//LABEL[@name='BookSubmission_NextStep']"/>
				</a>
			</div>
			-->
			<div class="button_container next">
				<xsl:choose>
					<xsl:when test="INFO/TEMPLATE = 'BookCandidate'">
						<a onclick="checkBookForm('{$form_name}');">
							<xsl:apply-templates select="." mode="button_label"/>
						</a>
					</xsl:when>
					<xsl:when test="INFO/TEMPLATE = 'BookSubmissionSummary'">
						<a onclick="sendBookSubmission({//MEDIA[INFO/TEMPLATE = 'BookSubmissionConfirm']//MEDIA[INFO/MEDIATYPE = 'MailContent']/@ID});">
							<xsl:apply-templates select="." mode="button_label"/>
						</a>
					</xsl:when>
					<xsl:when test="INFO/MEDIATYPE = 'SubmissionStep' and INFO/TEMPLATE = ''">
						<a onclick="validateAndmoveToNextStep();">
							<xsl:apply-templates select="." mode="button_label"/>
						</a>
					</xsl:when>
					<xsl:otherwise>
						<a onclick="checkContributorForm('{$form_name}');">
							<xsl:apply-templates select="." mode="button_label"/>
						</a>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</xsl:if>
	</xsl:template>
	<xsl:template name="form_next_step">
		<xsl:param name="form_name"/>
		<!--xsl:call-template name="prev_step"/-->
		<xsl:if test="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = ''">
			<div class="button_container">
				<a onclick="checkContributorForm('{$form_name}');">
					<xsl:apply-templates select="." mode="button_label"/>
				</a>
			</div>
		</xsl:if>
		<div class="button_container">
			<a>
				<xsl:attribute name="onclick">
					<xsl:text>checkContributorForm('</xsl:text>
					<xsl:value-of select="$form_name"/>
					<xsl:text>');</xsl:text>
					<xsl:if test="DESCRIPTIONS/DESCRIPTION/CUSTOM/TYPE = 'BookToOther'">
						<xsl:text>addOtherContributorForm('#m</xsl:text>
						<xsl:value-of select="@ID"/>
						<xsl:text>');</xsl:text>
					</xsl:if>
				</xsl:attribute>
				<xsl:apply-templates select="." mode="button_label"/>
			</a>
		</div>
	</xsl:template>
	<xsl:template match="MEDIA" mode="button_label">
		<xsl:choose>
			<xsl:when test="DESCRIPTIONS/DESCRIPTION/SIGNATURE != ''">
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/SIGNATURE"/>
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
