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
					<xsl:choose>
						<xsl:when test="$mailing/DESCRIPTIONS/DESCRIPTION[@languageID = //NECTIL/language]">
							<xsl:value-of select="//NECTIL/language"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$mailing/INFO/DEFAULTLANGUAGE"/>
						</xsl:otherwise>
					</xsl:choose>
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
					body{font-family:Helvetica, Arial; font-size:12px; text-align:center; color:#000; background:#ccc;}
					h1,h2,h3,h4,em {letter-spacing:0.05em; text-transform:uppercase;}
					a{text-decoration:none;color:white;cursor:pointer;}
					p{margin:0;}
					img{border:none;}
					ul, *{margin:0; padding:0;}
					em{color:white; text-transform:uppercase; font-weight:bold; font-style:normal;}
					
					#container{margin:10px auto; width:540px;}					
					.content{width:480px; margin:0 auto;}
					
					.tools{font-size:9px; color:#666;}
					.tools a {color:#666;}
					.tools a:hover{text-decoration:underline;}
					.tools ul{list-style:none; margin:0;}
					.tools ul li {display:inline; margin:0 3px;}
					
					#summary {margin:30px auto 10px; }
					#summary * {line-height:0.9em;}
					#summary h1 {font-size:60px; line-height:60px; font-weight:normal; }
					#summary h1, #summary h2, #summary h3, #summary h4, #summary h5, #summary h6 {margin:15px 0;}
					#summary p {text-transform:uppercase; font-size:11px; letter-spacing:0.05em;}
					
					#header{width:360px; margin:40px auto 40px;}
					#header *{text-transform:uppercase; margin:0;}
					#header h2{margin-top:10px; font-weight:normal;}
					#header h3{margin-bottom:5px;}
					#header h4{font-weight:normal; margin-bottom:3px;}
					#header p{text-transform:none; margin-bottom:3px;}
					
					#body{text-align:left; width:400px;}
					#body p {margin-bottom:5px; line-height: 15px;}
					#body h1, #body h2, #body h3, #body h4, #body h5, #body h6 {margin-bottom:5px; margin-top:10px;}
					#body h2{text-align:center;margin-bottom:5px; margin-top:20px;}
					#body h3{text-align:center;}
					.books{text-align:left; width:400px; margin:0 auto;}
					.book{margin:10px 0;}
					.book img{background:white; margin:5px 0;}
					
					#signature {border-top:3px solid white; padding-top:5px; width:400px; margin-top:30px; text-align:left; color:#333; font-size:11px;}
					#signature a{color:#333;}
					#signature a:hover{text-decoration:underline;}
					
					#bottom{min-height:22px; margin-top:30px; margin-bottom:30px;}	
					#top li.logo{clear:both; display:block; margin:30px 0;}
					/*strong{color:white;}*/
					
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
							<li class="logo">
								<img src="{//NECTIL/host}/common/images/logo-FB-trans.png" alt="Prix Fernand Baudin Prijs"/>
							</li>
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
					<xsl:if test="/RESPONSE/RESULTS[@name='media_info']/MEDIA/INFO/MEDIATYPE = 'Book'">
						<div class="books">
							<br/>
							<p>
								<strong>Laur&#xE9;ats / Laureaten / Laureates:</strong>
							</p>
							<xsl:apply-templates select="/RESPONSE/RESULTS[@name='media_info']/MEDIA[CATEGORIES/CATEGORY[@ID=23]]" mode="in_newsletter"/>
							<br/>
							<p>
								<strong>Nomin&#xE9;s / Genomineerden / Nominee:</strong>
							</p>
							<xsl:apply-templates select="/RESPONSE/RESULTS[@name='media_info']/MEDIA[CATEGORIES/CATEGORY[@ID=22]]" mode="in_newsletter"/>
							<br/>
							<p>
								<strong>Pr&#xE9;-nomin&#xE9;s / Pre-genomineerden / Pre-nominee:</strong>
							</p>
							<xsl:apply-templates select="/RESPONSE/RESULTS[@name='media_info']/MEDIA[CATEGORIES/CATEGORY[@ID=49]]" mode="in_newsletter"/>
						</div>
					</xsl:if>
					<xsl:apply-templates select="$mailing_description/SIGNATURE" mode="nl_content"/>
					<div id="bottom" class="tools">
						<ul>
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
	<xsl:template match="MEDIA[INFO/MEDIATYPE = 'Book']" mode="in_newsletter">
		<div class="book">
			<xsl:if test="CATEGORIES/CATEGORY/@ID = 23">
				<xsl:apply-templates select="." mode="visual">
					<xsl:with-param name="width" select="250"/>
				</xsl:apply-templates>
			</xsl:if>
			<p>
				<u>Titre / Titel / Title:</u>
				<br/>
				<strong>
					<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/>
					<xsl:if test="DESCRIPTIONS/DESCRIPTION/HEADER != ''">
						<xsl:value-of select="concat('(', DESCRIPTIONS/DESCRIPTION/HEADER ,')')"/>
					</xsl:if>
				</strong>
				<br/>
				<u>Graphiste / Grafisch ontwerper / Graphic designer:</u>
				<br/>
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/CUSTOM/GRAPHIST"/>
				<br/>
				<u>&#xC9;diteur / Uitgever / Publisher:</u>
				<br/>
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/CUSTOM/EDITOR"/>
				<br/>
				<u>Imprimeur / Drukker / Printer:</u>
				<br/>
				<xsl:value-of select="DESCRIPTIONS/DESCRIPTION/CUSTOM/PRINTER"/>
				<br/>
			</p>
		</div>
	</xsl:template>
	<xsl:template match="*" mode="nl_content">
		<xsl:variable name="node" select="translate(name(), $majuscules, $minuscules)"/>
		<xsl:if test=". != ''">
			<div id="{$node}" class="content">
				<xsl:apply-templates select="CSS/*"/>
			</div>
		</xsl:if>
	</xsl:template>
	<!--
	<xsl:template match="SIGNATURE//img">
		<xsl:copy-of select="."/>
	</xsl:template>
	-->
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
