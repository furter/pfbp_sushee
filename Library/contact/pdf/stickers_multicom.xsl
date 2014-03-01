<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:fo="http://www.w3.org/1999/XSL/Format" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<!--xsl:import href="labels_multicom.xsl"/-->
	<xsl:import href="stickers.xsl"/>
	<xsl:param name="selectedtemplate" select="/RESPONSE/Custom/StickerTemplate"/>
	<xsl:param name="stickertemplate" select="document('labels_multicom.xml')/Labels/label[@id = $selectedtemplate]"/>
	
</xsl:stylesheet>
