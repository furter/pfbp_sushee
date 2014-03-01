<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/templates/showRichText.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:template match="/RESPONSE">
		<xsl:apply-templates select="RESULTS[@name='mail-infos']/MAIL"/>
	</xsl:template>
	<xsl:template match="/RESPONSE/RESULTS[@name='mail-infos']/MAIL">
		<html>
			<head>
				<title><xsl:value-of select="INFO/SUBJECT"/></title>
				<meta name="viewport" content="width=device-width"/>
				<style>
					html,body
					{
						margin:0;padding:0;
						height:100%;
						overflow:hidden;
					}
					
					body
					{
						position:relative;
					}
					
					.headers
					{
						width:100%;height:16%;
						margin:2% 0 0;
					}
					
					.maxHeight
					{
						height:20%;
						overflow:auto;
						position:relative;
						background:#FAFAFA;
						border-bottom:1px solid #000;
					}
					
					.headers .label
					{
						width:10%;
						text-align:right;vertical-align:top;
						font-size:85%;
					}
					
					.value
					{
						text-align:left;vertical-align:top;
						font-size:85%;
					}
					
					#richtext
					{
						border:0;margin:0;padding:0;
						position:relative;
						width:100%;
						height:80%;
					}
					
					#plaintext
					{
						margin:5px;
					}
					
				</style>
			</head>
			<body>
				<div class="maxHeight">
					<table class="headers">
						<tr>
							<td class="label">
								<strong>From :</strong>
							</td>
							<td class="value">
								<xsl:apply-templates select="./INFO/FROM"/>
							</td>
						</tr>
						<tr>
							<td class="label">
								<strong>Subject :</strong>
							</td>
							<td class="value">
								<xsl:apply-templates select="./INFO/SUBJECT"/>
							</td>
						</tr>
						<tr>
							<td class="label">
								<strong>Date :</strong>
							</td>
							<td class="value">
								<xsl:apply-templates select="./INFO/RECEIVINGDATE"/>
							</td>
						</tr>
						<tr>
							<td class="label">
								<strong>To :</strong>
							</td>
							<td class="value">
								<xsl:apply-templates select="./INFO/TO"/>
							</td>
						</tr>
						<tr>
							<td>&#160;</td>
							<td>&#160;</td>
						</tr>
					</table>
				</div>	
					<xsl:choose>
						<xsl:when test="@html">
							<iframe id="richtext"  frameborder="#" src="{/RESPONSE/NECTIL/kernel_url}private/showOnlyRichText.php?ID={@ID}"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates select="INFO/PLAINTEXT"/>
						</xsl:otherwise>
					</xsl:choose>
					
				
			</body>
		</html>
	</xsl:template>
	
	<xsl:template match="INFO/PLAINTEXT">
		<div id="plaintext">
			<xsl:apply-templates/>
		</div>
	</xsl:template>
	
	<xsl:template match="br">
		<br/>
	</xsl:template>
	
</xsl:stylesheet>
