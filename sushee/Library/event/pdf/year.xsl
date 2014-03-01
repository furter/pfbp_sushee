<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/event/pdf/year.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:fo="http://www.w3.org/1999/XSL/Format">
	<xsl:import href="string.xsl"/>
	<xsl:import href="../../../sushee/Library/xsl/recipients.inc.xsl"/>
	<xsl:output method="xml" indent="yes" encoding="utf-8"/>
	
	<xsl:variable name="mails" select="/RESPONSE/RESULTS/MAIL" /><!--[@name = 'print2PDF']-->
	<xsl:variable name="files" select="/RESPONSE/NECTIL/files_url"/>
	<xsl:variable name="public" select="/RESPONSE/NECTIL/public_url"/>
	
	<xsl:template match="/RESPONSE">
		<fo:root>

			<fo:layout-master-set>
				
				<fo:simple-page-master master-name="page-master" page-width="210mm" page-height="297mm" margin-top="10mm" margin-bottom="5mm" margin-left="20mm" margin-right="20mm">
					<fo:region-body margin-top="7mm" margin-bottom="25mm" />
					<fo:region-before extent="7mm" region-name="navigation" />
					<fo:region-after extent="20mm" region-name="disclaimer" />
				</fo:simple-page-master>
				
				<fo:simple-page-master master-name="toc" page-width="210mm" page-height="297mm" margin-top="10mm" margin-bottom="5mm" margin-left="20mm" margin-right="20mm">
					<fo:region-body margin-top="0mm" margin-bottom="25mm" />
					<fo:region-after extent="20mm" region-name="disclaimer" />
				</fo:simple-page-master>
				
			</fo:layout-master-set>

			<xsl:if test="count($mails) &gt; 1">
				<fo:page-sequence master-reference="toc">

					<fo:static-content flow-name="disclaimer">
						<xsl:call-template name="disclaimer"/>
					</fo:static-content>
	
					<fo:flow flow-name="xsl-region-body">
						<fo:block font-size="21pt" space-after="3mm">
							<xsl:text>Index </xsl:text>
							<fo:inline font-size="10pt">
								<xsl:text>(</xsl:text>
								<xsl:value-of select="count($mails)"/>
								<xsl:text> e-mails from </xsl:text>
								<xsl:value-of select="substring-before($mails[ not(@ID &gt; preceding::MAIL/@ID) and not(@ID &gt; following::MAIL/@ID) ]/INFO/RECEIVINGDATE,' ')"/>
								<xsl:text> to </xsl:text>
								<xsl:value-of select="substring-before($mails[ not(@ID &lt; preceding::MAIL/@ID) and not(@ID &lt; following::MAIL/@ID) ]/INFO/RECEIVINGDATE,' ')"/>
								<xsl:text>)</xsl:text>
							</fo:inline>
						</fo:block>
						<xsl:call-template name="toc" />
					</fo:flow>
				</fo:page-sequence>	
			</xsl:if>

			<!-- flows -->
			<xsl:for-each select="$mails">
				<xsl:sort select="INFO/RECEIVINGDATE" data-type="text" order="ascending"/>
				
				<fo:page-sequence master-reference="page-master">

					<fo:static-content flow-name="navigation">
						<xsl:call-template name="navigation"/>
					</fo:static-content>
					
					<fo:static-content flow-name="disclaimer">
						<xsl:call-template name="disclaimer"/>
					</fo:static-content>
					
					<fo:flow flow-name="xsl-region-body" font-size="10pt">
							<xsl:call-template name="mail"/>

							<xsl:if test="position() = last()">
								<fo:block id="end_of_document" />
							</xsl:if>
					</fo:flow>	
				</fo:page-sequence>		
			</xsl:for-each>

		</fo:root>
	</xsl:template>

	<xsl:template name="mail">

		<!-- hearder -->
		<xsl:call-template name="header"  />

		<!--fo:block linefeed-treatment="ignore" white-space-treatment="preserve" white-space-collapse="false" font-size="10pt">
			<xsl:value-of select="INFO/PLAINTEXT" />
		</fo:block-->
		
		<xsl:choose>
			<xsl:when test="INFO/STYLEDTEXT/CSS/node()">
				<xsl:apply-templates select="INFO/STYLEDTEXT/CSS/node()"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="text-quoting">
					<xsl:with-param name="text" select="INFO/PLAINTEXT"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>

		
		<xsl:if test="INFO/ATTACHMENTS and INFO/ATTACHMENTS/text() != ''">
			<xsl:call-template name="folio">
				<xsl:with-param name="folder">
					<xsl:value-of select="INFO/FOLDER"/>
					<xsl:if test="INFO/TYPE = 'in'">
						<xsl:value-of select="INFO/ID"/>
						<xsl:text>/</xsl:text>
					</xsl:if>
				</xsl:with-param>
				<xsl:with-param name="attachs" select="INFO/ATTACHMENTS"/>
			</xsl:call-template>
		</xsl:if>

	</xsl:template>

	<xsl:template name="header">

		<fo:table font-size="10pt" font-family="sans-serif" space-after="5mm" padding-bottom="2mm" padding-top="3mm" border-bottom="solid .5pt black" border-top="solid 1.5pt black">
			<fo:table-column column-width="30mm"/>
			<fo:table-column />
			<fo:table-body>

					<fo:table-row>
						<fo:table-cell padding-bottom="1mm">
							<fo:block font-weight="bold" font-size="9pt" text-align="right" color="#999999">From: </fo:block>
						</fo:table-cell>
						<fo:table-cell>
							<fo:block><xsl:value-of select="INFO/FROM"/></fo:block>
						</fo:table-cell>
					</fo:table-row>
					
					<fo:table-row>
						<fo:table-cell padding-bottom="1mm">
							<fo:block font-weight="bold" font-size="9pt" text-align="right" color="#999999">Subject: </fo:block>
						</fo:table-cell>
						<fo:table-cell>
							<fo:block><xsl:value-of select="INFO/SUBJECT"/></fo:block>
						</fo:table-cell>
					</fo:table-row>
										
					<fo:table-row>
						<fo:table-cell padding-bottom="1mm">
							<fo:block font-weight="bold" font-size="9pt" text-align="right" color="#999999">Receiving Date: </fo:block>
						</fo:table-cell>
						<fo:table-cell>
							<fo:block><xsl:value-of select="INFO/RECEIVINGDATE"/></fo:block>
						</fo:table-cell>
					</fo:table-row>
															
					<fo:table-row>
						<fo:table-cell padding-bottom="1mm">
							<fo:block font-weight="bold" font-size="9pt" text-align="right" color="#999999">Sending Date: </fo:block>
						</fo:table-cell>
						<fo:table-cell>
							<fo:block><xsl:value-of select="INFO/SENDINGDATE"/></fo:block>
						</fo:table-cell>
					</fo:table-row>
															
					<fo:table-row>
						<fo:table-cell padding-bottom="1mm">
							<fo:block font-weight="bold" font-size="9pt" text-align="right" color="#999999">To: </fo:block>
						</fo:table-cell>
						<fo:table-cell>
							<fo:block><xsl:value-of select="INFO/TO"/></fo:block>
						</fo:table-cell>
					</fo:table-row>

					<xsl:if test="INFO/CC and INFO/CC/text() != ''">									
						<fo:table-row>
							<fo:table-cell padding-bottom="1mm">
								<fo:block font-weight="bold" font-size="9pt" text-align="right" color="#999999">Cc: </fo:block>
							</fo:table-cell>
							<fo:table-cell>
								<fo:block><xsl:value-of select="INFO/CC"/></fo:block>
							</fo:table-cell>
						</fo:table-row>
					</xsl:if>
					
					<xsl:if test="INFO/BCC and INFO/BCC/text() != ''">									
						<fo:table-row>
							<fo:table-cell padding-bottom="1mm">
								<fo:block font-weight="bold" font-size="9pt" text-align="right" color="#999999">Bcc: </fo:block>
							</fo:table-cell>
							<fo:table-cell>
								<fo:block><xsl:value-of select="INFO/BCC"/></fo:block>
							</fo:table-cell>
						</fo:table-row>
					</xsl:if>
										
					<xsl:if test="INFO/ATTACHMENTS and INFO/ATTACHMENTS/text() != ''">									
						<fo:table-row>
							<fo:table-cell padding-bottom="1mm">
								<fo:block font-weight="bold" font-size="9pt" text-align="right" color="#999999">Attachments: </fo:block>
							</fo:table-cell>
							<fo:table-cell>
								<fo:block><xsl:value-of select="INFO/ATTACHMENTS"/></fo:block>
							</fo:table-cell>
						</fo:table-row>
					</xsl:if>

			</fo:table-body>
		</fo:table>

	</xsl:template>

	<!-- Paragraph match -->

	<xsl:template match="p">
		<xsl:choose>
			<xsl:when test="@class = 'quote'">
				<fo:block font-family="serif" font-size="9pt" font-style="italic" space-after="1mm" margin-left="10mm">
					<xsl:apply-templates/>
				</fo:block>
			</xsl:when>
			<xsl:otherwise>
				<!-- classic paragraph case -->
				<fo:block font-family="sans-serif" font-size="9pt" space-after="2mm">
					<xsl:apply-templates/>
				</fo:block>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="h1">
		<fo:block font-family="sans-serif" font-size="15pt" font-weight="bold" space-after="2mm" space-before="2mm">
			<xsl:apply-templates/>
		</fo:block>
	</xsl:template>

	<xsl:template match="br">
		<fo:block/>
	</xsl:template>

	<!--xsl:template match="text()">
		<fo:block font-family="sans-serif" space-after="0mm">
			<xsl:value-of select="."/>
		</fo:block>
	</xsl:template-->

	<xsl:template match="h2">
		<fo:block font-family="sans-serif" font-size="13pt" font-weight="bold" space-after="2mm" space-before="2mm">
			<xsl:apply-templates/>
		</fo:block>
	</xsl:template>

	<xsl:template match="h3">
		<fo:block font-family="sans-serif" font-size="12pt" font-weight="bold" space-after="2mm" space-before="2mm">
			<xsl:apply-templates/>
		</fo:block>
	</xsl:template>

	<xsl:template match="h4">
		<fo:block font-family="sans-serif" font-size="11pt" font-weight="bold" space-after="2mm">
			<xsl:apply-templates/>
		</fo:block>
	</xsl:template>

	<xsl:template match="pre">
		<fo:block font-family="monospace" font-size="9pt" space-after="2mm">
			<xsl:apply-templates/>
		</fo:block>
	</xsl:template>

	<xsl:template match="code">
		<fo:block font-family="monospace" font-size="9pt" space-after="2mm">
			<xsl:apply-templates/>
		</fo:block>
	</xsl:template>

	<xsl:template match="address">
		<fo:block font-family="sans-serif" space-after="2mm">
			<xsl:apply-templates/>
		</fo:block>
	</xsl:template>

	<xsl:template match="li">
		<fo:list-block margin-left="{10 * count(ancestor::ul)}mm" font-family="sans-serif" font-size="9pt">
			<fo:list-item>
				<fo:list-item-label>
					<fo:block>&#8226;</fo:block>
				</fo:list-item-label>
				<fo:list-item-body>
					<fo:block space-after="0mm" space-before="0mm" margin-left="{10 * count(ancestor::ul) + 5}mm">
						<xsl:apply-templates/>
					</fo:block>
				</fo:list-item-body>
			</fo:list-item>
		</fo:list-block>
	</xsl:template>

	<!-- Character match -->

	<xsl:template match="img">
		<fo:external-graphic src="{$files}{@src}" width="{@width}" alignment-adjust="auto">
			<xsl:attribute name="src">
				<xsl:call-template name="replace-string">
					<xsl:with-param name="text" select="@src"/>
					<xsl:with-param name="from">[files_url]</xsl:with-param>
					<xsl:with-param name="to"><xsl:value-of select="$files" /></xsl:with-param>
				</xsl:call-template>
			</xsl:attribute>
		</fo:external-graphic>
	</xsl:template>

	<xsl:template match="br">
		<fo:block></fo:block>
	</xsl:template>

	<xsl:template match="strong">
		<fo:inline font-weight="bold">
			<xsl:apply-templates/>
		</fo:inline>
	</xsl:template>

	<xsl:template match="em">
		<fo:inline font-style="italic">
			<xsl:apply-templates/>
		</fo:inline>
	</xsl:template>
	
	<xsl:template match="cite">
		<fo:inline font-style="italic">
			<xsl:apply-templates/>
		</fo:inline>
	</xsl:template>
	
	<!-- Tools -->

	<xsl:template name="replace-string">
		<xsl:param name="text"/>
		<xsl:param name="from"/>
		<xsl:param name="to"/>
			<xsl:choose>
				<xsl:when test="contains($text, $from)">
			
					<xsl:variable name="before" select="substring-before($text, $from)"/>
					<xsl:variable name="after" select="substring-after($text, $from)"/>
					<xsl:variable name="prefix" select="concat($before, $to)"/>
			
					<xsl:value-of select="$before"/>
					<xsl:copy-of select="$to"/>
					<xsl:call-template name="replace-string">
						<xsl:with-param name="text" select="$after"/>
						<xsl:with-param name="from" select="$from"/>
						<xsl:with-param name="to" select="$to"/>
					</xsl:call-template>
				</xsl:when> 
				<xsl:otherwise>
					<xsl:value-of select="$text"/>
				</xsl:otherwise>
			</xsl:choose>
	</xsl:template>

 	<xsl:template name="disclaimer">
		<fo:block font-family="sans-serif" text-align="center" font-size="8pt" padding-top="1mm" space-after="2mm">
			<xsl:text>Page </xsl:text>
			<fo:inline font-weight="bold"><fo:page-number /></fo:inline>
			<xsl:text>/</xsl:text>
			<fo:page-number-citation ref-id="end_of_document" />
		</fo:block>
		<fo:block font-family="sans-serif" text-align="center" font-size="6pt" line-height="6.5pt">
			Nectil SA — Rue Charles Demeer 21A, 1020 Bruxelles — info@nectil.com — http://www.nectil.com 
		</fo:block>
		<fo:block font-family="sans-serif" text-align="center" font-size="6pt" line-height="6.5pt">
			tel: +32.24270484 — TVA: BE 863 686 416 — Bank IBAN: BE45-310-1781796-89 - BIC: BBRUBEBB010 
		</fo:block>
		<fo:block font-family="sans-serif" text-align="center" font-size="6pt" line-height="6.5pt" padding-top="1mm">
			Cet email a été envoyé par un collaborateur de Nectil SA/NV. Les informations qu’il contient sont confidentielles, et destinées à être utilisées uniquement par leur destinataire. Il est interdit à quiconque d’accéder à cet email. Si vous n’êtes pas le destinataire visé, toute divulgation, copie et diffusion des informations contenues dans cet email est interdite. Si vous avez reçu cet email par erreur, merci de prévenir immédiatement l’expéditeur et d’en détruire toutes copies éventuelles.
		</fo:block>

	</xsl:template>

 	<xsl:template name="navigation">
		<fo:table font-size="8pt" font-family="sans-serif" id="{generate-id()}">
			<fo:table-column column-width="30mm"/>
			<fo:table-column />
			<fo:table-body>
					<fo:table-row>
						<fo:table-cell text-align="left">
							<fo:block>
								<xsl:text>Page </xsl:text>
								<fo:inline font-weight="bold"><fo:page-number /></fo:inline>
								<xsl:text>/</xsl:text>
								<fo:page-number-citation ref-id="end_of_document" />
							</fo:block>
						</fo:table-cell>
						<fo:table-cell text-align="right">
							<fo:block>
								<xsl:value-of select="INFO/SUBJECT"/>
								<xsl:text> (</xsl:text>
								<xsl:value-of select="INFO/RECEIVINGDATE"/>
								<xsl:text>)</xsl:text>
							</fo:block>
						</fo:table-cell>
					</fo:table-row>
			</fo:table-body>
		</fo:table>
	</xsl:template>
	
 	<xsl:template name="toc">
		<fo:table font-size="8pt" padding-top="3mm" border-top="solid 1.5pt black">
			<fo:table-column column-width="28mm"/>
			<fo:table-column column-width="4mm"/>
			<fo:table-column column-width="35mm"/>
			<fo:table-column />
			<fo:table-column column-width="10mm"/>
			<fo:table-body>
				<xsl:for-each select="$mails">
					<xsl:sort select="INFO/RECEIVINGDATE" data-type="text" order="ascending"/>
					<xsl:call-template name="toc_sub"/>
				</xsl:for-each>
			</fo:table-body>
		</fo:table>
	</xsl:template>

	<xsl:template name="toc_sub">
		<fo:table-row>
			<fo:table-cell padding-bottom="1mm">
				<fo:block text-align="left">
					<fo:basic-link internal-destination="{generate-id()}" >
						<xsl:value-of select="INFO/RECEIVINGDATE"/>
					</fo:basic-link>
				</fo:block>
			</fo:table-cell>
			<fo:table-cell padding-bottom="1mm">
				<fo:block text-align-last="justify">
					<xsl:choose>
						<xsl:when test="INFO/TYPE = 'in'">
							<fo:external-graphic height="3mm" src="{/RESPONSE/NECTIL/host}/Library/mail/pdf/img/in.jpg"/>
						</xsl:when>
						<xsl:otherwise>
							<fo:external-graphic height="3mm" src="{/RESPONSE/NECTIL/host}/Library/mail/pdf/img/out.jpg"/>
						</xsl:otherwise>
					</xsl:choose>
				</fo:block>
			</fo:table-cell>
			<fo:table-cell padding-bottom="1mm">
				<fo:block text-align-last="left">
					<fo:basic-link internal-destination="{generate-id()}">
						<xsl:call-template name="recipient-label">
							<xsl:with-param name="string" select="INFO/FROM"/>
						</xsl:call-template>
					</fo:basic-link>
					<fo:leader color="#999999" leader-pattern="dots" leader-length.minimum="0pt" leader-length.maximum="100%" leader-pattern-width="1mm"/>
				</fo:block>
			</fo:table-cell>
			<fo:table-cell padding-bottom="1mm">
				<fo:block text-align-last="justify">
					<fo:basic-link internal-destination="{generate-id()}" >
						<xsl:value-of select="INFO/SUBJECT"/>
					</fo:basic-link>
					<fo:leader color="#999999" leader-pattern="dots" leader-length.minimum="0pt" leader-length.maximum="100%" leader-pattern-width="1mm"/>
				</fo:block>
			</fo:table-cell>
			<fo:table-cell text-align="right">
				<fo:block>
					<fo:page-number-citation ref-id="{generate-id()}" />
				</fo:block>
			</fo:table-cell>
		</fo:table-row>
	</xsl:template>

	<xsl:template name="folio">
		<xsl:param name="folder"/>
		<xsl:param name="attachs"/>
		
		<xsl:variable name="file">
			<xsl:choose>
				<xsl:when test="contains($attachs,',')">
					<xsl:value-of select="substring-before($attachs,',')"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$attachs"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:if test="contains($file,'.jpg') or contains($file,'.png') or contains($file,'.gif') or contains($file,'.tif') or contains($file,'.jpeg') or contains($file,'.tiff')">
			<!-- special apple mail fucking bug -->
			<xsl:if test="not(contains($attachs,concat($file,'1')))">
				<fo:block font-size="9pt">
					<xsl:value-of select="$file"/>
				</fo:block>
				<fo:block>
					<fo:external-graphic src="{$files}{$folder}{$file}"/>
				</fo:block>
			</xsl:if>
		</xsl:if>
		<xsl:if test="substring-after($attachs,',') != ''">
			<xsl:call-template name="folio">
				<xsl:with-param name="folder" select="$folder"/>
				<xsl:with-param name="attachs" select="substring-after($attachs,',')"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<xsl:template name="text-quoting">
		<xsl:param name="text"/>
		
		<xsl:variable name="line">
			<xsl:choose>
				<xsl:when test="contains($text,'&#xA;')">
					<xsl:value-of select="substring-before($text,'&#xA;')"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$text"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:choose>
			<xsl:when test="$line != '' and $line != '&gt; ' and $line != '&gt;&gt; ' and $line != '&gt;&gt;&gt; ' and $line != '&gt;&gt;&gt;&gt; ' and $line != '&gt;&gt;&gt;&gt;&gt; ' ">
				<fo:block>
					<xsl:call-template name="quote-level">
						<xsl:with-param name="line" select="$line"/>
					</xsl:call-template>
					<xsl:value-of select="$line"/>
				</fo:block>
			</xsl:when>
			<xsl:otherwise>
				<fo:block line-height="6pt"/>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:if test="substring-after($text,'&#xA;') != ''">
			<xsl:call-template name="text-quoting">
				<xsl:with-param name="text" select="substring-after($text,'&#xA;')"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	
	<xsl:template name="quote-level">
		<xsl:param name="line"/>
		<xsl:choose>
			<xsl:when test="starts-with($line,'&gt;&gt;&gt;&gt;&gt; ')">
				<xsl:attribute name="color">#ffff00</xsl:attribute>
				<xsl:attribute name="text-indent">25mm</xsl:attribute>
			</xsl:when>
			<xsl:when test="starts-with($line,'&gt;&gt;&gt;&gt; ')">
				<xsl:attribute name="color">#ff00ff</xsl:attribute>
				<xsl:attribute name="text-indent">20mm</xsl:attribute>
			</xsl:when>
			<xsl:when test="starts-with($line,'&gt;&gt;&gt; ')">
				<xsl:attribute name="color">#0000ff</xsl:attribute>
				<xsl:attribute name="text-indent">15mm</xsl:attribute>
			</xsl:when>
			<xsl:when test="starts-with($line,'&gt;&gt; ')">
				<xsl:attribute name="color">#00ff00</xsl:attribute>
				<xsl:attribute name="text-indent">10mm</xsl:attribute>
			</xsl:when>
			<xsl:when test="starts-with($line,'&gt; ')">
				<xsl:attribute name="color">#ff0000</xsl:attribute>
				<xsl:attribute name="text-indent">5mm</xsl:attribute>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name="space-before">1mm</xsl:attribute>
				<xsl:attribute name="space-after">1mm</xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
