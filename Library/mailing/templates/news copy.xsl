<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="html" indent="yes" encoding="utf-8"/>
		
	<xsl:template match="/RESPONSE">
		<xsl:variable name="mailing" select="RESULTS[@name='mailing_info']/MAILING[1]"/>
		<xsl:variable name="recipient" select="RESULTS[@name='contact_info']/CONTACT[1]"/>
		<html>
			<head>
				<title>
					<xsl:value-of select="$mailing/DESCRIPTIONS/DESCRIPTION[1]/TITLE"/>
				</title>
				<style>
					a{text-decoration:none;color:black;cursor:pointer;}
					p{margin:0;}
					img{border:none;}
				</style>
			</head>
			<body style="padding:0;margin:10px;font-family:sans-serif;">
				<div style="border:1px solid black;padding:2px;width:682px;margin:0;">
				<div id="content" style="margin:0;font-size:12px;border:1px solid black;width:680px;">
					<div style="margin-top:25px;margin-left:50px;margin-right:50px;">
								<h1 style="font-size:22px;margin-bottom:5px;padding-bottom:0;">
									<xsl:value-of select="$mailing/DESCRIPTIONS/DESCRIPTION[1]/TITLE"/>
								</h1>
								<p class="Header" style="margin-top:0;font-size:15px;font-style:italic;font-weight:bold;margin-bottom:10px;">
									<xsl:copy-of select="$mailing/DESCRIPTIONS/DESCRIPTION[1]/HEADER/node()"/>
								</p>
								<p class="Courtesy" style="font-size:15px;font-weight:bold;margin-bottom:5px;">
									<xsl:choose>
										<xsl:when test="$recipient/INFO/GENDER='F'">
											<xsl:value-of select="$mailing/DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/PoliteFemale"/>
										</xsl:when>
						
										<xsl:when test="$recipient/INFO/GENDER='M'">
											<xsl:value-of select="$mailing/DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/PoliteMale"/>
										</xsl:when>
						
										<xsl:otherwise>
											<xsl:value-of select="$mailing/DESCRIPTIONS/DESCRIPTION[1]/CUSTOM/PoliteNeutral"/>
										</xsl:otherwise>
									</xsl:choose>
								</p>
								<p class="Body" style="font-size:12px;margin-bottom:50px;">
									<xsl:copy-of select="$mailing/DESCRIPTIONS/DESCRIPTION[1]/BODY"/>
								</p>
								<div style="margin-bottom:100px;">
									<xsl:for-each select="/RESPONSE/RESULTS[@name='media_info']/MEDIA">
									<div style="margin-bottom:40px;">
										<h2 style="font-size:14px;font-weight:bold;margin-bottom:4px;"><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/></h2>
										<div class="Header" style="font-weight:bold;font-size:12px;margin-bottom:5px;">
										<xsl:copy-of select="DESCRIPTIONS/DESCRIPTION/HEADER/node()"/>
										</div>
										<div class="Body" style="font-size:12px;">
										<xsl:copy-of select="DESCRIPTIONS/DESCRIPTION/BODY/node()"/>
										</div>
									</div>
									</xsl:for-each>
								</div>
						</div>
				</div>
				</div>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
