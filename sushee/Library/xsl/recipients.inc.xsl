<?xml version="1.0" encoding="UTF-8" ?>
<!--
	Recipients.inc.xsl
	Created by François Dispaux on 2008-02-10.
	Modified by François Dispaux on 2008-02-10.
	Copyright (c) 2008 Nectil SA. All rights reserved.
-->

<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/xsl/recipients.inc.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="FROM | TO | CC | BCC" mode="list">
		<xsl:call-template name="split-recipients">
			<xsl:with-param name="recipient-type" select="name(.)"/>
			<xsl:with-param name="string" select="."/>
		</xsl:call-template>
	</xsl:template>
	
	<xsl:template match="FROM | TO | CC | BCC" mode="resumee">
		<xsl:call-template name="resume-recipients">
			<xsl:with-param name="recipient-type" select="name(.)"/>
			<xsl:with-param name="string" select="."/>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="split-recipients">
		<xsl:param name="recipient-type"/>
		<xsl:param name="string"/>
		<xsl:choose>
			<xsl:when test="contains($string,',')">
				<xsl:variable name="before" select="substring-before($string,',')"/>
				<xsl:variable name="after" select="substring-after($string,',')"/>

				<xsl:call-template name="resolve-recipients">
					<xsl:with-param name="recipient-type" select="$recipient-type"/>
					<xsl:with-param name="recipient-string" select="$before"/>
				</xsl:call-template>

				<xsl:call-template name="split-recipients">
					<xsl:with-param name="recipient-type" select="$recipient-type"/>
					<xsl:with-param name="string" select="$after"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="resolve-recipients">
					<xsl:with-param name="recipient-type" select="$recipient-type"/>
					<xsl:with-param name="recipient-string" select="$string"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template name="resume-recipients">
		<xsl:param name="recipient-type"/>
		<xsl:param name="string"/>
		<xsl:choose>
			<xsl:when test="contains($string,',')">
				<xsl:variable name="before" select="substring-before($string,',')"/>
				<xsl:variable name="after" select="substring-after($string,',')"/>

				<xsl:call-template name="resolve-recipients">
					<xsl:with-param name="recipient-type" select="$recipient-type"/>
					<xsl:with-param name="recipient-string" select="$before"/>
					<xsl:with-param name="recipients-count" select="$after"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="resolve-recipients">
					<xsl:with-param name="recipient-type" select="$recipient-type"/>
					<xsl:with-param name="recipient-string" select="$string"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template name="resolve-recipients">
		<xsl:param name="recipients-count"/>
		<xsl:param name="recipient-string"/>
		<xsl:param name="recipient-type"/>
		
		<xsl:variable name="recipient">
			<xsl:choose>
				<xsl:when test="contains($recipient-string,'&#60;') and contains($recipient-string,'&#62;')">
					<xsl:call-template name="recipient-label"><xsl:with-param name="string" select="$recipient-string"/></xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="email-to-label"><xsl:with-param name="email" select="$recipient-string"/></xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<xsl:variable name="email">
			<xsl:choose>
				<xsl:when test="contains($recipient-string,'&#60;') and contains($recipient-string,'&#62;')">
					<xsl:call-template name="recipient-email"><xsl:with-param name="string" select="$recipient-string"/></xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$recipient-string"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<tr>
			<!--a href="mailto:{$recipient-string}"-->
				<td class="recipient-type">
					<xsl:choose>
						<xsl:when test="$recipient-type = 'FROM'">De:</xsl:when>
						<xsl:when test="$recipient-type = 'TO'">A:</xsl:when>
						<xsl:when test="$recipient-type = 'CC'">Cc:</xsl:when>
						<xsl:when test="$recipient-type = 'BCC'">Bcc:</xsl:when>
					</xsl:choose>
				</td>
				<td class="recipient-info">
					<span class="recipient"><xsl:value-of select="$recipient"/></span>
					<xsl:choose>
						<xsl:when test="not($recipients-count = '')">
								<xsl:variable name="other-recipients">
									<xsl:call-template name="count-recipients">
										<xsl:with-param name="string" select="$recipients-count"/>
									</xsl:call-template>
								</xsl:variable>
								<xsl:if test="$other-recipients &gt; 0">
									<span class="count">
										<xsl:text> (and </xsl:text>
										<xsl:value-of select="$other-recipients"/>
										<xsl:text> other </xsl:text>
										<xsl:choose>
											<xsl:when test="$other-recipients = 1">
												<xsl:text>recipient)</xsl:text>
											</xsl:when>
											<xsl:otherwise>
												<xsl:text>recipients)</xsl:text>
											</xsl:otherwise>
										</xsl:choose>
									</span>
								</xsl:if>
						</xsl:when>
						<xsl:otherwise>
							<span class="email"><xsl:value-of select="$email"/></span>
						</xsl:otherwise>
					</xsl:choose>
				</td>
			<!--/a-->
		</tr>
	</xsl:template>

	<xsl:template name="count-recipients">
		<xsl:param name="string"/>
		<xsl:param name="counter" select="0"/>
		<xsl:choose>
			<xsl:when test="contains($string,',')">
				<xsl:variable name="after" select="substring-after($string,',')"/>
				<xsl:call-template name="count-recipients">
					<xsl:with-param name="string" select="$after"/>
					<xsl:with-param name="counter" select="$counter + 1"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$counter"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="email-to-label">
		<xsl:param name="email"/>
		<xsl:variable name="user" select="substring-before($email,'@')"/>
		<xsl:value-of select="translate($user,'.',' ')" />
	</xsl:template>	

	<xsl:template name="recipient-label">
		<xsl:param name="string"/>
	
		<xsl:choose>
			<xsl:when test="contains($string,'&#60;') and contains($string,'&#62;')">
				<xsl:variable name="label" select="substring-before($string,'&#60;')"/>
				<xsl:variable name="final-label">
					<xsl:choose>
						<xsl:when test="substring($label,1,1) = ' '">
							<xsl:call-template name="strip-quotes">
								<xsl:with-param name="string" select="substring-after($label,' ')"/>
							</xsl:call-template>
						</xsl:when>
						<xsl:otherwise>
							<xsl:call-template name="strip-quotes">
								<xsl:with-param name="string" select="$label"/>
							</xsl:call-template>	
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:choose>
					<xsl:when test="$final-label = ''">
						<xsl:variable name="email">
							<xsl:call-template name="recipient-email">
								<xsl:with-param name="string" select="$string"/>
							</xsl:call-template>
						</xsl:variable>
						<xsl:call-template name="email-to-label">
							<xsl:with-param name="email" select="$email"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$final-label"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="email-to-label">
					<xsl:with-param name="email" select="$string"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="recipient-email">
		<xsl:param name="string"/>
		<xsl:variable name="email" select="substring-after($string,'&#60;')"/>
		<xsl:value-of select="substring-before($email,'&#62;')"/>
	</xsl:template>

	<xsl:template name="strip-quotes">
		<xsl:param name="string"/>
		<xsl:variable name="unquoted">
			<xsl:call-template name="replace-string">
				<xsl:with-param name="text" select="$string"/>
				<xsl:with-param name="from">"</xsl:with-param>
				<xsl:with-param name="to" select="''"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:variable name="unaposted">
			<xsl:call-template name="replace-string">
				<xsl:with-param name="text" select="$unquoted"/>
				<xsl:with-param name="from">'</xsl:with-param>
				<xsl:with-param name="to" select="''"/>
			</xsl:call-template>
		</xsl:variable>	
		<xsl:value-of select="$unaposted"/>	
	</xsl:template>
	
</xsl:stylesheet>