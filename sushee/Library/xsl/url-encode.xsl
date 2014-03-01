<?xml version="1.0" encoding="UTF-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/xsl/url-encode.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<!-- ISO-8859-1 based URL-encoding demo
			Written by Mike J. Brown, mike@skew.org.
			Updated 2002-05-20.

			No license; use freely, but credit me if reproducing in print.

			Also see http://skew.org/xml/misc/URI-i18n/ for a discussion of
			non-ASCII characters in URIs.
	-->

	<!-- The string to URL-encode.
			Note: By "iso-string" we mean a Unicode string where all
			the characters happen to fall in the ASCII and ISO-8859-1
			ranges (32-126 and 160-255) -->

	<!-- Characters we'll support.
			We could add control chars 0-31 and 127-159, but we won't. -->

	<xsl:variable name="ascii"> !"#$%&amp;'()*+,-./0123456789:;&lt;=&gt;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~</xsl:variable>
	<xsl:variable name="latin1"> ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ</xsl:variable>

	<!-- Characters that usually don't need to be escaped -->
	<xsl:variable name="safe">!'()*-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz~</xsl:variable>
	<xsl:variable name="hex">0123456789ABCDEF</xsl:variable>


	<xsl:template name="url-ascii-encode">
		<xsl:param name="str"/>	
		<xsl:if test="$str">
			<xsl:variable name="first-char" select="substring($str,1,1)"/>
			<xsl:choose>
				<xsl:when test="contains($safe,$first-char)">
					<xsl:value-of select="$first-char"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="codepoint">
						<xsl:choose>
							<xsl:when test="contains($ascii,$first-char)">
								<xsl:value-of select="string-length(substring-before($ascii,$first-char)) + 32"/>
							</xsl:when>
							<xsl:when test="contains($latin1,$first-char)">
								<xsl:value-of select="string-length(substring-before($latin1,$first-char)) + 160"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:message terminate="no">Warning: string contains a character that is out of range! Substituting "?".</xsl:message>
								<xsl:text>63</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<xsl:call-template name="gethex">
						<xsl:with-param name="code" select="$codepoint"/>
					</xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:if test="string-length($str) &gt; 1">
				<xsl:call-template name="url-encode">
					<xsl:with-param name="str" select="substring($str,2)"/>
				</xsl:call-template>
			</xsl:if>
		</xsl:if>
	</xsl:template>
	
	<xsl:template name="url-utf8-encode">
		<xsl:param name="str"/>	
		<xsl:if test="$str">
			<xsl:variable name="first-char" select="substring($str,1,1)"/>
			<xsl:choose>
				<xsl:when test="contains($safe,$first-char)">
					<xsl:value-of select="$first-char"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="codepoint">
						<xsl:choose>
							<xsl:when test="contains($ascii,$first-char)">
								<xsl:value-of select="string-length(substring-before($ascii,$first-char)) + 32"/>
							</xsl:when>
							<xsl:when test="contains($latin1,$first-char)">
								<xsl:value-of select="string-length(substring-before($latin1,$first-char)) + 160"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:message terminate="no">Warning: string contains a character that is out of range! Substituting "?".</xsl:message>
								<xsl:text>63</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<xsl:choose>
						<xsl:when test="$codepoint &gt; 127 and $codepoint &lt; 192 ">
							<xsl:text>%C2</xsl:text>
							<xsl:call-template name="gethex">
								<xsl:with-param name="code" select="$codepoint"/>
							</xsl:call-template>
						</xsl:when>						
						<xsl:when test="$codepoint &gt; 191 and $codepoint &lt; 256">
							<xsl:text>%C3</xsl:text>
							<xsl:call-template name="gethex">
								<xsl:with-param name="code" select="number($codepoint) - 64"/>
							</xsl:call-template>
						</xsl:when>
						<xsl:otherwise>
							<xsl:call-template name="gethex">
								<xsl:with-param name="code" select="$codepoint"/>
							</xsl:call-template>
						</xsl:otherwise>
					</xsl:choose>

				</xsl:otherwise>
			</xsl:choose>
			<xsl:if test="string-length($str) &gt; 1">
				<xsl:call-template name="url-utf8-encode">
					<xsl:with-param name="str" select="substring($str,2)"/>
				</xsl:call-template>
			</xsl:if>
		</xsl:if>
	</xsl:template>
	
	<xsl:template name="gethex">
		<xsl:param name="code"/>	
 		<xsl:variable name="hex-digit1" select="substring($hex,floor($code div 16) + 1,1)"/>
		<xsl:variable name="hex-digit2" select="substring($hex,$code mod 16 + 1,1)"/>
		<xsl:value-of select="concat('%',$hex-digit1,$hex-digit2)"/>
	</xsl:template>
	
</xsl:stylesheet>