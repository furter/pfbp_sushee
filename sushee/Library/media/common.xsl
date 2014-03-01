<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/media/common.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
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
			<link rel="stylesheet" type="text/css" href="{$public}common.css" />
			<script type="text/javascript" src="{$public}utilities.js">&#160;</script>
		</head>
	</xsl:template>

</xsl:stylesheet>
