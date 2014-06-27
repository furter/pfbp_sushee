<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<!-- WARNING Pour un document XHTML ne pas utiliser de majuscule dans les meta, nom du fichier css, all tags, ... -->
	<!-- WARNING Pour un document XHTML ne pas utiliser de tags vides -> <div>&#160;</div>   &#160; = espace vide -->
	
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" omit-xml-declaration="no" />
	<xsl:param name="public" select="/RESPONSE/NECTIL/public_url"/>
	<xsl:param name="files" select="/RESPONSE/NECTIL/files_url"/>
	<xsl:param name='media' select="/RESPONSE/RESULTS[@name='media']/MEDIA[1]"/>
	
	<!-- HTML Header -->
	<xsl:template name="header">
		<head>
			<meta name="keywords" content="{$media/DESCRIPTIONS/DESCRIPTION/TITLE} {$media/DESCRIPTIONS/DESCRIPTION/HEADER}"/>
			<meta name="generator" content="Nectil"/>
			<meta name="description" content="Nectil website - Powered by Nectil"/>
			<meta name="date" content="{/RESPONSE/URL/today}"/>
			<meta name="content-language" content="{/RESPONSE/NECTIL/language}"/>
			<meta name="language" content="{/RESPONSE/NECTIL/language}"/>
			<meta name="dateofLastModification" content="{$media/INFO/MODIFICATIONDATE}"/>
			<meta name="robots" content="index,follow"/>
			<meta name="googlebot" content="index,follow"/>
			<meta name="content-type" content="text/html"/>
			<title><xsl:value-of select="$media/DESCRIPTIONS/DESCRIPTION/TITLE" /></title>
			<link rel="stylesheet" type="text/css" href="{$public}default_nectil.css" />
			<script type="text/javascript" src="{$public}utilities.js">&#160;</script>
		</head>
	</xsl:template>

	<!-- Logo Nectil -->
	<xsl:template name="logo_nectil">
		<div id="logo_nectil">
			<a href="http://www.nectil.com" title="Nectil">
				<img src="{$public}../Kernel/Library/media/images/nectil_black_shine.gif" alt="Nectil" />
			</a>
		</div>
	</xsl:template>
	
	<!-- Language Menu -->
	<xsl:template name="languages_menu">
		<div id="languages">
			<xsl:for-each select="/RESPONSE/RESULTS[@name='published_languages']/LANGUAGE[ (PUBLISHED='1') ]">
				<xsl:choose>
					<xsl:when test="@ID = /RESPONSE/NECTIL/language"><xsl:value-of select="UNIVERSAL"/></xsl:when>
					<xsl:otherwise><a class="orange" href="{/RESPONSE/NECTIL/language_url}language={@ID}" title="{UNIVERSAL}"><xsl:value-of select="UNIVERSAL"/></a></xsl:otherwise>
				</xsl:choose>
				<xsl:if test="position()!=last()"> - </xsl:if>
			</xsl:for-each>
			<xsl:text> - </xsl:text><a class="orange" href="{/RESPONSE/NECTIL/language_url}xml=true" title="see xml source">see xml source</a>
			<xsl:text> - </xsl:text><a class="orange" href="#" onclick="closeAll();" title="see xml source">close all</a>
			
		</div>
	</xsl:template>

	<!-- Signature / Footer -->
	<xsl:template name="footer">
		<div id="footer">
			<div id="w3c">
				<a href="http://validator.w3.org/check?uri=referer" title="xhtml validation">
					<img src="{$public}../Kernel/Library/media/images/xhtml.gif" alt="xhtml validation" />
				</a>
				<a href="http://jigsaw.w3.org/css-validator/check/referer" title="css validation">
					<img src="{$public}../Kernel/Library/media/images/css.gif" alt="css validation" />
				</a>	
			</div>
			<p>Powered by <a href="http://www.nectil.com" title="Nectil" class="orange">Nectil Shaper</a></p>
		</div>
	</xsl:template>

	<!-- Media Preview -->
	<xsl:template name="media_preview">
	
		<xsl:param name="media" select="$media"/>
		<xsl:param name="info" select="$media/INFO"/>
		<xsl:param name="description" select="$media/DESCRIPTIONS/DESCRIPTION[1]"/>
		<xsl:param name="dependencies" select="$media/DEPENDENCIES"/>
		
		<div id="cell">
			<div id="top_cell">&#160;</div>
			<div id="content">
				<xsl:if test="$description/CUSTOM/Visual/text() or $description/CUSTOM/Preview/text() or $description/CUSTOM/Icon/text()">
					<img class="align_right">
					<xsl:choose>
						<xsl:when test="$description/CUSTOM/Visual/text()">
							<xsl:attribute name="src">
								<xsl:value-of select="$files"/><xsl:value-of select="$description/CUSTOM/Visual"/>
							</xsl:attribute>
							<xsl:attribute name="alt">Visual</xsl:attribute>
						</xsl:when>
						<xsl:when test="$description/CUSTOM/Preview/text()">
							<xsl:attribute name="src">
								<xsl:value-of select="$files"/><xsl:value-of select="$description/CUSTOM/Preview/text()"/>
							</xsl:attribute>
							<xsl:attribute name="alt">Preview</xsl:attribute>
						</xsl:when>
						<xsl:otherwise >
							<xsl:attribute name="src">
								<xsl:value-of select="$files"/><xsl:value-of select="$description/CUSTOM/Icon/text()"/>
							</xsl:attribute>
							<xsl:attribute name="alt">Icon</xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
					</img>
				</xsl:if>
				<div id="preview">
					<!-- TITLE MEDIA -->
					<h1>
						<xsl:choose>
							<xsl:when test="$description/TITLE!=''"><xsl:value-of select="$description/TITLE"/></xsl:when>
							<xsl:otherwise><xsl:value-of select="$info/DENOMINATION"/></xsl:otherwise>
						</xsl:choose>
						<em><xsl:text> (</xsl:text><xsl:value-of select="$info/MEDIATYPE"/> - <xsl:value-of select="$media/@ID"/><xsl:text>)</xsl:text></em>
					</h1>
					
					<!-- HEADER MEDIA -->
					<xsl:if test="$description/HEADER/CSS">
						<h2><a id="link_header_{$media/@ID}" class="open" href="#link_header_{$media/@ID}" onclick="switchVisibility(this,'header_media')" title="Header">Header</a></h2>
						<div id="header_media" class="media_section">
							<xsl:apply-templates select="$description/HEADER/CSS/*" />
						</div>
					</xsl:if>
					
					<!-- BODY MEDIA -->
					<xsl:if test="$description/BODY/CSS">
						<h2><a id="link_body_{$media/@ID}" class="open" href="#link_body_{$media/@ID}" onclick="switchVisibility(this,'body_media')" title="Body">Body</a></h2>
						<div id="body_media" class="media_section">
							<xsl:apply-templates select="$description/BODY/CSS/*" />
						</div>
					</xsl:if>
					
					<!-- DESCRIPTIONS MEDIA -->
					<!-- si il existe un fils du noeud custom non vide ou qu'il existe un noeud COPYRIGHT ou BIBLIO ou SIGNATURE ou SUMMARY ou URL -->	
					<xsl:if test="count($description/CUSTOM/*[. != ''])&gt;0 or $description/COPYRIGHT or $description/BIBLIO or $description/SIGNATURE or $description/SUMMARY or $description/URL">
						<h2><a id="link_content_{$media/@ID}" class="open" href="#link_content_{$media/@ID}" onclick="switchVisibility(this,'content_media')"  title="Body">Content</a></h2>
						<div id="content_media" class="media_section">
							<ul>
								<!-- Pour "COPYRIGHT / BIBLIO / SIGNATURE / SUMMARY / URL" si non vide -->
								<xsl:for-each select="$description/*[. != '' and ( name() = 'COPYRIGHT' or name() = 'BIBLIO' or name() = 'SIGNATURE' or name() = 'SUMMARY' or name() = 'URL') ]" >
									<xsl:call-template name="description_list" />
								</xsl:for-each>
								<!-- Pour les fils du noeud CUSTOM si non vide -->
								<xsl:for-each select="$description/CUSTOM/*[. != '']">
									<xsl:call-template name="description_list" />
								</xsl:for-each>
							</ul>
						</div>
					</xsl:if>
				</div>

				<div id="media_dependencies">
					<dl id="nav_depth">
						<dt>Dependencies depth : </dt>
						<dd>
							<a id="nav_1" href="{/RESPONSE/NECTIL/this_script}?ID={$media/@ID}&amp;depth=1#nav_1" title="1">
								<xsl:if test="/RESPONSE/URL/depth = 1"><xsl:attribute name="class">selected</xsl:attribute></xsl:if>	1
							</a>
						</dd>
						<dd>
							<a id="nav_2" href="{/RESPONSE/NECTIL/this_script}?ID={$media/@ID}&amp;depth=2#nav_2" title="2">
								<xsl:if test="/RESPONSE/URL/depth = 2 or not(/RESPONSE/URL/depth)"><xsl:attribute name="class">selected</xsl:attribute></xsl:if>	2
							</a>
						</dd>
						<dd>
							<a id="nav_3" href="{/RESPONSE/NECTIL/this_script}?ID={$media/@ID}&amp;depth=3#nav_3" title="3">
								<xsl:if test="/RESPONSE/URL/depth = 3"><xsl:attribute name="class">selected</xsl:attribute></xsl:if>	3
							</a>
						</dd>
						<dd>
							<a id="nav_4" href="{/RESPONSE/NECTIL/this_script}?ID={$media/@ID}&amp;depth=4#nav_4" title="4">
								<xsl:if test="/RESPONSE/URL/depth = 4"><xsl:attribute name="class">selected</xsl:attribute></xsl:if>	4
							</a>
						</dd>
						<dd>
							<a id="nav_all" href="{/RESPONSE/NECTIL/this_script}?ID={$media/@ID}&amp;depth=all#nav_all" title="all">
								<xsl:if test="/RESPONSE/URL/depth = 'all'"><xsl:attribute name="class">selected</xsl:attribute></xsl:if>	all
							</a>
						</dd>
					</dl>
					
					<xsl:call-template name="media_dependencies"/>
				</div>
				
				<div class="breaker">&#160;</div>
			</div>
			<div id="bottom_cell">&#160;</div>
		</div>
	</xsl:template>

	<!-- Dependencies Tree -->
	<xsl:template name="media_dependencies">
		<xsl:param name="my_media" select="$media" />
		<xsl:param name="info" select="$my_media/INFO" />
		<xsl:param name="description" select="$my_media/DESCRIPTIONS/DESCRIPTION[1]"/>
		<xsl:param name="pagetocall">
			<xsl:choose>
				<xsl:when test="$info/PAGETOCALL != ''">
					<xsl:value-of select="$info/PAGETOCALL" /><xsl:text>?</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$info/MEDIATYPE" /><xsl:text>.php?</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:param>

		<div class="tree">
			<h3>
				<a href="{$pagetocall}ID={$my_media/@ID}" >
					<xsl:choose>
						<xsl:when test="$description/TITLE!=''">
							<xsl:attribute name="title">
								<xsl:value-of select="$description/TITLE"/>
							</xsl:attribute>
							<xsl:value-of select="$description/TITLE"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="title">
								<xsl:value-of select="$info/DENOMINATION"/>
							</xsl:attribute>
							<xsl:value-of select="$info/DENOMINATION"/>
						</xsl:otherwise>
					</xsl:choose>
				</a>	
			</h3>
			<div class="details">
				<p class="mediatype"><xsl:value-of select="$info/MEDIATYPE" /></p>
				<p class="creation_date"><xsl:value-of select="$info/CREATIONDATE" /></p>
				<xsl:if test="$info/MODIFICATIONDATE/text()"><p class="modification_date"><xsl:value-of select="$info/MODIFICATIONDATE" /></p></xsl:if>
				<p class="modifier"><xsl:value-of select="$info/CONTACT/INFO/FIRSTNAME" />&#160;<xsl:value-of select="$info/CONTACT/INFO/LASTNAME" /></p>
			</div>
				<xsl:for-each select="$my_media/DEPENDENCIES/DEPENDENCY[MEDIA]">
				<ul class="dependency">
					<li>
						<h4><a id="link_{MEDIA/@ID}" class="open" href="#link_{MEDIA/@ID}" onclick="switchVisibility(this,'content_' + {MEDIA/@ID})" ><xsl:value-of select="./@type"/></a></h4>
						<ul id="content_{MEDIA/@ID}" class="medias">
							<xsl:for-each select="MEDIA">
								<li>
									<xsl:call-template name="media_dependencies" >
										<xsl:with-param name="my_media" select="."/>
									</xsl:call-template>
								</li>
							</xsl:for-each>
						</ul>
					</li>
				</ul>			
			</xsl:for-each>
		</div>
	</xsl:template>

	<!-- Nectil URLs mapping -->
	<xsl:template match="CSS//*">
		<xsl:choose>
			<xsl:when test="name(.)='nectil_url'">
			  	<xsl:choose>
					<xsl:when test="@pagetocall!='' and @pagetocall!='undefined'"><a class="nectil_link" href="{$public}{@pagetocall}?ID={@ID}&amp;viewing_code={$viewing_code}"><xsl:value-of select="."/></a></xsl:when>
			  		<xsl:otherwise><a class="nectil_link" href="{$public}{@mediatype}.php?ID={@ID}&amp;viewing_code={$viewing_code}"><xsl:value-of select="."/></a></xsl:otherwise>
				</xsl:choose>
	         </xsl:when>
			<xsl:otherwise>
				<xsl:element name="{local-name()}">
					<xsl:copy-of select="./attribute::*"/>
					<xsl:apply-templates/>
				</xsl:element>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template name="description_list">
		<!-- TO DO check des tois dernier caractères -> si jpg, png, gif -> aperçu -->
		<li>
			<xsl:variable name="extension" select="substring(.,string-length(.) - 2)" />
			<xsl:choose>
				<xsl:when test="./CSS">
					<h3 class="description_css">
						<a id="link_{name()}" class="open" href="#link_{name()}" onclick="switchVisibility(this,'{name()}_media')" title="{name()}">
							<xsl:value-of select="name(.)" />
						</a>
					</h3>
					<div id="{name()}_media" class="description_section">
						<xsl:apply-templates select="./CSS/*" />
					</div>
				</xsl:when>
				<xsl:when test="$extension = 'jpg' or $extension = 'gif' or $extension = 'png'">
					<xsl:attribute name="class">img</xsl:attribute>
					<h3 class="description_css">
						<a id="link_{name()}" class="open" href="#link_{name()}" onclick="switchVisibility(this,'{name()}_media')" title="{name()}">
							<xsl:value-of select="name()" /> : 
						</a>	
					</h3>
					<div id="{name()}_media" class="description_section">
						<a href="{$files}{.}" title="{$files}{.}"><img src="{$files}{.}" alt="{$files}{.}" /></a>
					</div>	
				</xsl:when>
				<xsl:when test="$extension = 'pdf' or $extension = 'doc' or $extension = 'xls' or $extension = 'ppt' or $extension = 'txt'">
					<xsl:attribute name="class">doc</xsl:attribute>
					<h3 class="description_css">
						<a id="link_{name()}" class="open" href="#link_{name()}" onclick="switchVisibility(this,'{name()}_media')" title="{name()}">
							<xsl:value-of select="name()" /> : 
						</a>	
					</h3>
					<div id="{name()}_media" class="description_section">
						<a href="{$files}{.}" title="{$files}{.}"><img src="{$files}{.}" alt="{$files}{.}" /></a>
					</div>	
				</xsl:when>
				<xsl:otherwise>
					<strong><xsl:value-of select="name()" /> : </strong> <xsl:value-of select="." />
				</xsl:otherwise>
			</xsl:choose>
		</li>
	</xsl:template>

</xsl:stylesheet>
