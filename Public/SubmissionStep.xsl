<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	
	<!-- comment this line to customise your template -->
	<xsl:import href="default_nectil.xsl" />
	
	<!-- un-comment this line to customise your template -->
	<!--xsl:import href="common.xsl" /-->
	
	<xsl:template match="/RESPONSE">
		<html>
			<xsl:call-template name="header" />
			<body>
				<div id="wrap">
					<xsl:call-template name="logo_nectil" />
					<xsl:call-template name="languages_menu" />
					<xsl:call-template name="media_preview" />
					<xsl:call-template name="footer" />
				</div>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>