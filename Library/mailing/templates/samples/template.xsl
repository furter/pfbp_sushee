<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="html" indent="yes" encoding="utf-8"/>
	
	<xsl:template match="/RESPONSE">
		<xsl:param name="recipient" select="/RESPONSE/RESULTS[@name='contact_info']/CONTACT[1]"/>
		<xsl:param name="recipient_viewing_code" select="/RESPONSE/RESULTS[@name='contact_info']/CONTACT[1]/@viewing_code"/>
		<xsl:param name="sender" select="/RESPONSE/RESULTS[@name='mailing_info']/MAILING[1]/INFO/CONTACT[1]"/>
		<xsl:param name="medias" select="/RESPONSE/RESULTS[@name='media_info']/MEDIA"/>
		<xsl:param name="groups" select="/RESPONSE/RESULTS[@name='mailing_info']/MAILING[1]/DEPENDENCIES/DEPENDENCY[@type='mailingGroupRecipients']/GROUP"/>
		
		<a href="{/RESPONSE/NECTIL/kernel_url }public/mailing_unsuscribe.php?mailingID={/RESPONSE/RESULTS[@name='mailing_info']/@mailingID}&amp;contactID={ /RESPONSE/RESULTS[@name='contact_info']/CONTACT/@ID}"> Unsuscribe </a>
	</xsl:template>
	
</xsl:stylesheet>
