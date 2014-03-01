<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="text" indent="no" encoding="utf-8"/>
	<xsl:strip-space elements="*"/>
	
	<xsl:template name="intro">
		<xsl:choose>
			<xsl:when test="RESULTS[@name='contact_info']/CONTACT/INFO/GENDER='F'"><xsl:value-of select="RESULTS[@name='mailing_info']/MAILING/DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/PoliteFemale"/></xsl:when>
			<xsl:when test="RESULTS[@name='contact_info']/CONTACT/INFO/GENDER='M'"><xsl:value-of select="RESULTS[@name='mailing_info']/MAILING/DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/PoliteMale"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="RESULTS[@name='mailing_info']/MAILING/DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/PoliteNeutral"/></xsl:otherwise>
		</xsl:choose>
		<xsl:text>&#x0d;&#x0a;&#x0d;&#x0a;</xsl:text>
		<xsl:value-of select="RESULTS[@name='mailing_info']/MAILING/DESCRIPTIONS/DESCRIPTION[1]/HEADER"/>
		<xsl:text>&#x0d;&#x0a;</xsl:text>
		<xsl:value-of select="RESULTS[@name='mailing_info']/MAILING/DESCRIPTIONS/DESCRIPTION[1]/BODY"/>
		<xsl:text>&#x0d;&#x0a;</xsl:text>
		<xsl:value-of select="RESULTS[@name='mailing_info']/*[1]/DESCRIPTIONS/DESCRIPTION[1]/SUMMARY"/>
		<xsl:text>&#x0d;&#x0a;&#x0d;&#x0a;</xsl:text>
		<xsl:variable name="creator" select="RESULTS[@name='mailing_info']/MAILING/INFO/CONTACT"/>
		
		<xsl:value-of select="$creator/INFO/LASTNAME"/>&#160;<xsl:value-of select="$creator/INFO/FIRSTNAME"/>
		<xsl:text>&#x0d;&#x0a;</xsl:text><!-- &#x0a; is the right one on windows, i put the other by precaution for Mac and Linux(not verified) -->
		<xsl:value-of select="$creator/DESCRIPTIONS/DESCRIPTION[1]/TITLE"/>
		<xsl:text>&#x0d;&#x0a;</xsl:text>
		<xsl:value-of select="$creator/INFO/EMAIL1"/>
		<xsl:text>&#x0d;&#x0a;&#x0d;&#x0a;</xsl:text>
	</xsl:template>
	
	<xsl:template match="/RESPONSE">
		<xsl:call-template name="intro"/>
		
		<xsl:for-each select="RESULTS[@name='media_info']/*">
			<xsl:value-of select="translate(DESCRIPTIONS/DESCRIPTION[1]/TITLE,'abcdefghijklmnopqrstuvwxyzàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ','ABCDEFGHIJKLMNOPQRSTUVWXYZÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ')"/><xsl:text>&#x0d;&#x0a;&#x0d;&#x0a;</xsl:text>
			<xsl:if test="DESCRIPTIONS/DESCRIPTION[1]/HEADER!=''">
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION[1]/HEADER"/><xsl:text>&#x0d;&#x0a;</xsl:text>
			</xsl:if>
			<xsl:if test="DESCRIPTIONS/DESCRIPTION[1]/BODY!=''">
				<xsl:text>---&#x0d;&#x0a;</xsl:text>
				<xsl:apply-templates select="DESCRIPTIONS/DESCRIPTION[1]/BODY"/><!--xsl:value-of select="DESCRIPTIONS/DESCRIPTION[1]/BODY"/--><xsl:text>&#x0d;&#x0a;&#x0d;&#x0a;</xsl:text>
			</xsl:if>
			<xsl:if test="DESCRIPTIONS/DESCRIPTION[1]/SIGNATURE!=''">
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION[1]/SIGNATURE"/><xsl:text>&#x0d;&#x0a;&#x0d;&#x0a;&#x0d;&#x0a;</xsl:text>
			</xsl:if>
			<xsl:text>&#x0d;&#x0a;---------------------------------------------------------------&#x0d;&#x0a;&#x0d;&#x0a;</xsl:text>
		</xsl:for-each>
		<xsl:text>Pour se désinscrire de la mailing list : </xsl:text><xsl:value-of select="/RESPONSE/NECTIL/kernel_url"/>public/mailing_unsuscribe.php?mailingID=<xsl:value-of select="RESULTS[1]/@mailingID"/>&amp;contactID=<xsl:value-of select="/RESPONSE/RESULTS[@name='contact_info']/CONTACT/@ID"/><xsl:text>&#x0d;&#x0a;</xsl:text>
		<xsl:text>Online version : </xsl:text><xsl:value-of select="/RESPONSE/NECTIL/kernel_url"/><xsl:text>private/showMailing.php?ID=</xsl:text><xsl:value-of select="RESULTS[1]/@mailingID"/>&amp;viewing_code=<xsl:value-of select="/RESPONSE/RESULTS/CONTACT/@viewing_code"/><xsl:text>&#x0d;&#x0a;</xsl:text>
	</xsl:template>
	
	<xsl:template match="p">
		<xsl:if test="./text()!='' or ./*"><xsl:apply-templates/><xsl:text>&#x0d;&#x0a;&#x0d;&#x0a;</xsl:text></xsl:if>
	</xsl:template>
	<xsl:template match="ul">
		<xsl:for-each select="li">&#160;&#160;&#160;*<xsl:apply-templates/><xsl:text>&#x0d;&#x0a;</xsl:text></xsl:for-each>
		<xsl:text>&#x0d;&#x0a;</xsl:text>
	</xsl:template>
	<xsl:template match="br">&#x0d;&#x0a;</xsl:template>
</xsl:stylesheet>
