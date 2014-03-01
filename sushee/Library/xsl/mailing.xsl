<?xml version="1.0"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/xsl/mailing.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:import href="string.xsl"/>
	<xsl:template match="CSS//*">
		<!--usage-->
		<!--xsl:apply-templates select="//CSS/node()"/-->
		<xsl:choose>
			<xsl:when test="name(.)='nectil_url'">
				<xsl:choose>
					<xsl:when test="@pagetocall!='' and @pagetocall!='undefined'">
						<a class="nectil_link" href="{/RESPONSE/NECTIL/public_url}{@pagetocall}?ID={@ID}&amp;viewing_code={/RESPONSE/RESULTS[@name=&quot;contact_info&quot;]/CONTACT/@viewing_code}">
							<xsl:value-of select="."/>
						</a>
					</xsl:when>
					<xsl:otherwise>
						<a class="nectil_link" href="{/RESPONSE/NECTIL/public_url}{@mediatype}.php?ID={@ID}&amp;viewing_code={/RESPONSE/RESULTS[@name=&quot;contact_info&quot;]/CONTACT/@viewing_code}">
							<xsl:value-of select="."/>
						</a>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="name(.)='a'">
				<a>
					<xsl:attribute name="href"><xsl:value-of select="/RESPONSE/NECTIL/public_url"/>Action.php?url=<xsl:call-template name="replace-string"><xsl:with-param name="text" select="@href"/><xsl:with-param name="from" select="'&amp;'"/><xsl:with-param name="to" select="'%26'"/></xsl:call-template>&amp;viewing_code=<xsl:value-of select="/RESPONSE/RESULTS[@name='contact_info']/CONTACT/@viewing_code"/></xsl:attribute>
					<xsl:copy-of select="./attribute::*[name()!='href']"/>
					<xsl:apply-templates/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:element name="{local-name()}">
					<xsl:copy-of select="./attribute::*"/>
					<xsl:if test="name(..)='CSS' and not(node())">&#160;</xsl:if><!-- empty paragraph style -->
					<xsl:apply-templates/>
				</xsl:element>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
