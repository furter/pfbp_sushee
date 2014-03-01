<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:import href="../../../common/common.xsl"/>
	<xsl:output method="html" indent="yes" encoding="utf-8"/>
	<xsl:template match="/RESPONSE">
		<xsl:variable name="mailing" select="RESULTS[@name='mailing_info']/MAILING[1]"/>
		<xsl:variable name="recipient" select="RESULTS[@name='contact_info']/CONTACT[1]"/>
		<xsl:variable name="language_to_display">
			<xsl:choose>
				<xsl:when test="//NECTIL/language != ''">
					<xsl:value-of select="//NECTIL/language"/>
				</xsl:when>
				<xsl:when test="$recipient/INFO/LANGUAGEID != ''">
					<xsl:value-of select="$recipient/INFO/LANGUAGEID"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>fre</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="mailing_description" select="$mailing/DESCRIPTIONS/DESCRIPTION[@languageID=$language_to_display]"/>
		<xsl:variable name="show_mailing" select="concat(/RESPONSE/NECTIL/kernel_url, 'private/showMailing.php?ID=', RESULTS[@name='mailing_info']/MAILING[1]/@ID, '&amp;viewing_code=', /RESPONSE/RESULTS[@name='contact_info']/CONTACT[1]/@viewing_code)"/>
		<html>
			<head>
				<title>
					<xsl:value-of select="$mailing_description/TITLE"/>
				</title>
				<style>
					body{font-family:Helvetica, Arial; font-size:12px; text-align:center; color:#000;}
					h1,h2,h3,h4,em {letter-spacing:0.05em; text-transform:uppercase;}
					a{text-decoration:none;color:red;cursor:pointer;}
					p{margin:0;}
					img{border:none;}
					ul, *{margin:0; padding:0;}
					em{color:red; text-transform:uppercase; font-weight:bold; font-style:normal;}
					
					#container{margin:10px auto; width:540px; background:white url(<xsl:value-of select="//NECTIL/host"/>/common/images/bb_540.gif) no-repeat 0 10px;}					
					.content{width:480px; margin:0 auto;}
					
					.tools{font-size:9px; color:#666;}
					.tools a {color:#666;}
					.tools a:hover{text-decoration:underline;}
					.tools ul{list-style:none; margin:0;}
					.tools ul li {display:inline; margin:0 3px;}
					
					#summary {margin:30px auto 10px; }
					#summary * {margin:0; line-height:1em;}
					#summary h1 {font-size:60px; line-height:60px; font-weight:normal; }
					#summary p {text-transform:uppercase; font-size:11px; letter-spacing:0.05em;}
					
					#header{width:240px; margin:10px auto 40px;}
					#header *{text-transform:uppercase; margin:0;}
					#header h2{margin-top:10px; font-weight:normal;}
					#header h3{margin-bottom:5px;}
					#header h4{font-weight:normal; margin-bottom:3px;}
					#header p{text-transform:none; margin-bottom:3px;}
					
					#body{text-align:left; width:360px;}
					#body p {margin-bottom:5px; line-height: 15px;}
					
					#signature {border-top:3px solid yellow; padding-top:5px; width:360px; margin-top:30px; text-align:left; color:#333; font-size:11px;}
					#signature a{color:#333;}
					#signature a:hover{text-decoration:underline;}
					
					#bottom{margin-top:30px; margin-bottom:30px; background:white url(<xsl:value-of select="//NECTIL/host"/>/common/images/bb_540.gif) no-repeat center bottom;}	
					#bottom li.logo{clear:both; display:block; margin-bottom:5px;}
					
				</style>
			</head>
			<body>
				<div id="container">
					<div class="tools" id="top">
						<ul>
							<li>
								<a href="{$show_mailing}&amp;forceLanguageID={//NECTIL/language}">
									<xsl:value-of select="//LABEL[@name='nl_cant_read_mail']"/>
								</a>
							</li>
							<!--xsl:for-each select="/RESPONSE/RESULTS[@name='published_languages']/LANGUAGE[@ID != //NECTIL/language]"-->
							<xsl:for-each select="$mailing/DESCRIPTIONS/DESCRIPTION[BODY != '' and @languageID != //NECTIL/language]">
								<xsl:variable name="label" select="concat('nl_this_mail_in_', @languageID)"/>
								<li>
									<a href="{$show_mailing}&amp;forceLanguageID={@languageID}">
										<xsl:value-of select="//LABEL[@name=$label]"/>
									</a>
								</li>
							</xsl:for-each>
						</ul>
					</div>
					<xsl:apply-templates select="$mailing_description/SUMMARY" mode="nl_content"/>
					<xsl:if test="$mailing_description/CUSTOM/Visual != ''">
						<div id="visual">
							<img src="{//NECTIL/host}/common/img_resize.php?path={$mailing_description/CUSTOM/Visual}&amp;width=540">
								<xsl:attribute name="alt">
									<xsl:for-each select="$mailing_description/SUMMARY/CSS/*">
										<xsl:value-of select="."/>
										<xsl:call-template name="add_dash_if_not_last"/>
									</xsl:for-each>
								</xsl:attribute>
							</img>
						</div>
					</xsl:if>
					<xsl:apply-templates select="$mailing_description/HEADER" mode="nl_content"/>
					<xsl:apply-templates select="$mailing_description/BODY" mode="nl_content"/>
					<xsl:apply-templates select="$mailing_description/SIGNATURE" mode="nl_content"/>
					<div id="bottom" class="tools">
						<ul>
							<li class="logo">
								<img src="{//NECTIL/host}/common/images/logo-FB-rgb.gif" alt="Prix Fernand Baudin Prijs"/>
							</li>
							<li>
								<a href="{//NECTIL/public_url}NewsletterUnsubscribe.php?cID={$recipient/@ID}">
									<xsl:value-of select="//LABEL[@name='nl_unsubscribe']"/>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</body>
		</html>
	</xsl:template>
	<xsl:template match="*" mode="nl_content">
		<xsl:variable name="node" select="translate(name(), $majuscules, $minuscules)"/>
		<xsl:if test=". != ''">
			<div id="{$node}" class="content">
				<xsl:apply-templates select="CSS/*"/>
			</div>
		</xsl:if>
	</xsl:template>
	<xsl:template match="SUMMARY//h1">
		<h1>
			<xsl:call-template name="titrage">
				<xsl:with-param name="text" select="translate(., $minuscules, $majuscules)"/>
				<xsl:with-param name="color" select="'000000'"/>
				<xsl:with-param name="size" select="60"/>
				<xsl:with-param name="font" select="'light'"/>
			</xsl:call-template>
		</h1>
	</xsl:template>
</xsl:stylesheet>
