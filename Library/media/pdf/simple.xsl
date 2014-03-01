<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8"/>
	<xsl:param name="files_dir"></xsl:param>
	<xsl:template match="/RESPONSE">
  		<fo:root xmlns:fo="http://www.w3.org/1999/XSL/Format">
			<fo:layout-master-set>
			  <!--fo:simple-page-master master-name="A4">
			  </fo:simple-page-master-->
			  <fo:simple-page-master master-name="page-master"  page-width="210mm" page-height="297mm" margin-top="1cm" margin-bottom="1cm" margin-left="1cm" margin-right="1cm">
               <fo:region-body margin="0cm" margin-bottom="1cm"/>
               <fo:region-before extent="0cm"/>
			   <fo:region-after extent="1cm"/>
			   </fo:simple-page-master>
			</fo:layout-master-set>
			
			<fo:page-sequence master-reference="page-master">
			  <fo:static-content flow-name="xsl-region-after">
			  	<fo:block text-align="center"><fo:page-number/></fo:block>
			  </fo:static-content>
			  <fo:flow flow-name="xsl-region-body">
			  	<fo:block space-after="1cm"><fo:external-graphic src="{$files_dir}/file/nectil.svg"/></fo:block>
			  	<xsl:for-each select="RESULTS/MEDIA">
					<fo:block font-size="32pt" font-family="sans serif" font-weight="normal" space-after="5mm"><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/></fo:block>
					<fo:block><fo:external-graphic src="{$files_dir}{DESCRIPTIONS/DESCRIPTION/CUSTOM/*[1]}" height="2cm"/></fo:block>
					<fo:block font-size="16pt" space-after="1cm"><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/HEADER"/></fo:block>
					<fo:block font-size="12pt" space-after="1cm"><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/BODY"/></fo:block>
					<xsl:if test="/RESPONSE/RESULTS/MEDIA[1]/DEPENDENCIES/DEPENDENCY/MEDIA">
					<fo:list-block margin-left="1cm">
						<xsl:for-each select="/RESPONSE/RESULTS/MEDIA[1]/DEPENDENCIES/DEPENDENCY/MEDIA">
						
							<fo:list-item>
							 <fo:list-item-label><fo:block></fo:block></fo:list-item-label>
							 <fo:list-item-body>
								<fo:block space-after="5mm"><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/TITLE"/></fo:block>
								<fo:block><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/HEADER"/></fo:block>
								<fo:block><xsl:value-of select="DESCRIPTIONS/DESCRIPTION/BODY"/></fo:block>
							</fo:list-item-body>
							</fo:list-item>
						</xsl:for-each>
					</fo:list-block>
					</xsl:if>
				</xsl:for-each>
			  </fo:flow>
			</fo:page-sequence>
		</fo:root>
	</xsl:template>
</xsl:stylesheet>
