<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:fo="http://www.w3.org/1999/XSL/Format" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<!--xsl:import href="avery_fre.xsl"/-->
	<xsl:output method="xml" indent="yes" encoding="utf-8"/>

	<xsl:param name="files_dir"/>
	
	<xsl:param name="selectedtemplate" select="/RESPONSE/Custom/StickerTemplate"/>
	<xsl:param name="stickertemplate" select="document('avery_fre.xml')/Labels/label[@id = $selectedtemplate]"/>
	<xsl:param name="space"> </xsl:param>
	

	<xsl:template match="/RESPONSE">
		<fo:root>
			<xsl:variable name="nbContacts" select="count(RESULTS/CONTACT)"/>
			<xsl:variable name="totalPages" select="ceiling($nbContacts div $stickertemplate/@total)"/>
			
			<xsl:variable name="total" select="$stickertemplate/@total"/>
			
			<xsl:variable name="width" select="$stickertemplate/@width"/>
			<xsl:variable name="height" select="$stickertemplate/@height"/>
			
			<xsl:variable name="columns" select="$stickertemplate/@columns"/>
			<xsl:variable name="rows" select="$stickertemplate/@rows"/>
			
			<xsl:variable name="leftmargin" select="$stickertemplate/@leftmargin"/>
			<xsl:variable name="topmargin" select="$stickertemplate/@topmargin"/>
			<xsl:variable name="rightmargin" select="$stickertemplate/@rightmargin"/>
			<xsl:variable name="bottommargin" select="$stickertemplate/@rightmargin"/>
			
			<xsl:variable name="horizontalgap" select="$stickertemplate/@horizontalgap"/>
			<xsl:variable name="verticalgap" select="$stickertemplate/@verticalgap"/>

			<!-- SETTING PAGE MASTERS --> 
			
			<fo:layout-master-set>
				<fo:simple-page-master
				master-name="page-master"
				page-width="210mm"
				page-height="297mm"
				margin-top="{$topmargin}mm"
				margin-bottom="{$bottommargin}mm"
				margin-left="{$leftmargin}mm"
				margin-right="{$rightmargin}mm">
				
					<fo:region-body margin="0mm"/>
					<fo:region-before extent="0mm"/>
					<fo:region-after extent="0mm"/>
				</fo:simple-page-master>
			</fo:layout-master-set>
			
			<!-- START LAYOUTING --> 
				
			<fo:page-sequence master-reference="page-master">
				
				<!-- Body -->
				<fo:flow flow-name="xsl-region-body">
					
					<xsl:for-each select="RESULTS/CONTACT">
	
						<fo:block-container
						left="{(ceiling((position()+$columns)-1) mod $columns) * ($width+$horizontalgap)}mm"
						top="{floor((ceiling((position()+$total)-1) mod $total) div $columns) * ($height+$verticalgap)}mm"
						width="{$width}mm"
						height="{$height}mm"
						position="absolute">
							
							<xsl:call-template name="contact_generic"/>
						</fo:block-container>
						
						<xsl:if test="((position()+$total) mod $total) = 0">
							<fo:block break-after="page"/>
						</xsl:if>
						
					</xsl:for-each>

				</fo:flow>
			</fo:page-sequence>

		</fo:root>
	</xsl:template>
	
	<xsl:template name="contact_generic">
		<fo:block
		padding-top="1mm"
		margin-left="3mm"
		margin-right="3mm"
		padding-bottom="1mm">
		<xsl:if test="/RESPONSE/Custom/BackgroundImage!=''">
			<xsl:attribute name="background-image"><xsl:value-of select="$files_dir"/><xsl:value-of select="/RESPONSE/Custom/BackgroundImage"/></xsl:attribute>
			<xsl:attribute name="background-attachment">scroll</xsl:attribute>
			<xsl:attribute name="background-repeat">no-repeat</xsl:attribute>
			<xsl:attribute name="background-position-horizontal">right</xsl:attribute>
			<xsl:attribute name="background-position-vertical">bottom</xsl:attribute>
		</xsl:if>
			<xsl:choose>
				<xsl:when test="INFO/CONTACTTYPE='PP'">
					<xsl:call-template name="contact_pp"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="contact_pm"/>
				</xsl:otherwise>
			</xsl:choose>
		</fo:block>
	</xsl:template>
	
	<!-- PERSONNES PHYSIQUES -->
	
	<xsl:template name="contact_pp">
		<xsl:param name="titlevalue" select="INFO/TITLE"/>
		<xsl:variable name="titlelabel" select="/RESPONSE/RESULTS[@name='labels']/LIST[@name='title_pp']/ITEM[@value=$titlevalue]/@label"/>
		<xsl:variable name="title">
			<xsl:choose>
				<xsl:when test="$titlelabel!=''"><xsl:value-of select="$titlelabel"/></xsl:when>
				<xsl:otherwise><xsl:value-of select="$titlevalue"/></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<!-- Destinatire -->
		<fo:block font-size="10pt" font-family="Trade Gothic Extended Bold">
			<xsl:if test="$title!=''"><xsl:value-of select="$title"/>&#160;</xsl:if>
			<xsl:if test="INFO/LASTNAME!=''"><xsl:value-of select="INFO/LASTNAME"/><xsl:text> </xsl:text></xsl:if>
			<xsl:if test="INFO/DENOMINATION!=''">"<xsl:value-of select="INFO/DENOMINATION"/>"<xsl:text> </xsl:text></xsl:if>
			<xsl:if test="INFO/FIRSTNAME!=''"><xsl:value-of select="INFO/FIRSTNAME"/></xsl:if>
		</fo:block>

		
		<!-- Adresse -->
		<fo:block font-size="8pt" font-family="Interstate Light"><xsl:value-of select="INFO/ADDRESS"/></fo:block>
		<fo:block font-size="8pt" font-family="Interstate Light">
			<xsl:if test="INFO/POSTALCODE!=''"><xsl:value-of select="INFO/POSTALCODE"/><xsl:text> - </xsl:text></xsl:if>
			<xsl:if test="INFO/CITY!=''"><xsl:value-of select="INFO/CITY"/></xsl:if>
		</fo:block>
		
		<!-- Pays -->
		<fo:block font-size="8pt" font-family="Interstate Light">
			<xsl:variable name="country" select="INFO/COUNTRYID"/>
			<xsl:value-of select="/RESPONSE/RESULTS[@name='countries']/COUNTRY[@ID=$country]/LABEL"/>
		</fo:block>
	</xsl:template>
	
	<!-- PERSONNES MORALES -->
		
	<xsl:template name="contact_pm">
		<xsl:param name="titlevalue" select="INFO/TITLE"/>
		<xsl:variable name="titlelabel" select="/RESPONSE/RESULTS[@name='labels']/LIST[@name='title_pp']/ITEM[@value=$titlevalue]/@label"/>
		<xsl:variable name="title">
			<xsl:choose>
				<xsl:when test="$titlelabel!=''"><xsl:value-of select="$titlelabel"/></xsl:when>
				<xsl:otherwise><xsl:value-of select="$titlevalue"/></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<!-- Destinatire -->
		<xsl:choose>
			<xsl:when test="INFO/DENOMINATION!=''">
				<fo:block font-size="10pt" font-family="Trade Gothic Extended Bold">
					<xsl:value-of select="INFO/DENOMINATION"/>
					<xsl:if test="$title!=''"><xsl:text> </xsl:text><xsl:value-of select="$title"/></xsl:if>
				</fo:block>
				<xsl:if test="INFO/LASTNAME!=''">
					<fo:block font-size="8pt" font-family="Interstate Bold">
						<xsl:if test="INFO/LASTNAME!=''"><xsl:value-of select="INFO/LASTNAME"/><xsl:text> </xsl:text></xsl:if>
						<xsl:if test="INFO/FIRSTNAME!=''"><xsl:value-of select="INFO/FIRSTNAME"/></xsl:if>
					</fo:block>
				</xsl:if>
			</xsl:when>
			<xsl:otherwise>
				<fo:block font-size="10pt" font-family="Trade Gothic Extended Bold">
					<xsl:if test="$title!=''"><xsl:value-of select="$title"/>&#160;</xsl:if>
					<xsl:if test="INFO/LASTNAME!=''"><xsl:value-of select="INFO/LASTNAME"/><xsl:text> </xsl:text></xsl:if>
					<xsl:if test="INFO/DENOMINATION!=''">"<xsl:value-of select="INFO/DENOMINATION"/><xsl:text>" </xsl:text></xsl:if>
					<xsl:if test="INFO/FIRSTNAME!=''"><xsl:value-of select="INFO/FIRSTNAME"/></xsl:if>
				</fo:block>
			</xsl:otherwise>
		</xsl:choose>
		
		<!-- Adresse -->
		<fo:block font-size="8pt" font-family="Interstate Light"><xsl:value-of select="INFO/ADDRESS"/></fo:block>
		<fo:block font-size="8pt" font-family="Interstate Light">
			<xsl:value-of select="INFO/POSTALCODE"/>-<xsl:value-of select="INFO/CITY"/>
		</fo:block>
		
		<!-- Pays -->
		<fo:block font-size="8pt" font-family="Interstate Light">
			<xsl:variable name="country" select="INFO/COUNTRYID"/>
			<xsl:value-of select="/RESPONSE/RESULTS[@name='countries']/COUNTRY[@ID=$country]/LABEL"/>
		</fo:block>
	</xsl:template>
	
</xsl:stylesheet>
