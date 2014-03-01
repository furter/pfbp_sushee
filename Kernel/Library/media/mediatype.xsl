<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/Library/media/mediatype.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
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