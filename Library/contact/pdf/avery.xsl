<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8"/>

	<xsl:param name="label_list">
		<Labels>
		  <label id="J8159" name="Avery J8159" columns="3" rows="8" height="33.9" width="64" total="24" topmargin="13.7" leftmargin="6.3" rightmargin="6.3" bottommargin="12.1" horizontalgap="2.7" verticalgap="0"/>
		  <label id="J8160" name="Avery J8160" columns="3" rows="7" height="38.1" width="64" total="21" topmargin="15.9" leftmargin="7.2" rightmargin="7.2" bottommargin="14.4" horizontalgap="2" verticalgap="0"/>
		  <label id="J8161" name="Avery J8161" columns="3" rows="6" height="46.6" width="64" total="18" topmargin="9.5" leftmargin="7.2" rightmargin="7.2" bottommargin="7.9" horizontalgap="2" verticalgap="0"/>
		  <label id="J8162" name="Avery J8162" columns="2" rows="8" height="33.9" width="99" total="16" topmargin="13.7" leftmargin="4.7" rightmargin="4.7" bottommargin="12.1" horizontalgap="2.6" verticalgap="0"/>
		  <label id="J8163" name="Avery J8163" columns="2" rows="7" height="38.1" width="99" total="14" topmargin="15.9" leftmargin="4.7" rightmargin="4.7" bottommargin="14.4" horizontalgap="2.6" verticalgap="0"/>
		  <label id="J8165" name="Avery J8165" columns="2" rows="4" height="67.7" width="99" total="8" topmargin="13.8" leftmargin="4.7" rightmargin="4.7" bottommargin="12.4" horizontalgap="2.6" verticalgap="0"/>
		  <label id="J8166" name="Avery J8166" columns="2" rows="3" height="93.1" width="99" total="6" topmargin="9.5" leftmargin="4.7" rightmargin="4.7" bottommargin="8.2" horizontalgap="2.6" verticalgap="0"/>
		  <label id="J8167" name="Avery J8167" columns="1" rows="1" height="289" width="200" total="1" topmargin="4.7" leftmargin="5.2" rightmargin="5.2" bottommargin="3.3" horizontalgap="-0.4" verticalgap="0"/>
		  <label id="J8168" name="Avery J8168" columns="1" rows="2" height="143.2" width="201" total="2" topmargin="5" leftmargin="4.7" rightmargin="4.7" bottommargin="5.6" horizontalgap="-1.4" verticalgap="0"/>
		  <label id="L7159" name="Avery L7159" columns="3" rows="8" height="33.9" width="64" total="24" topmargin="13.7" leftmargin="6.3" rightmargin="6.3" bottommargin="12.1" horizontalgap="2.7" verticalgap="0"/>
		  <label id="L7160" name="Avery L7160" columns="3" rows="7" height="38.1" width="64" total="21" topmargin="15.9" leftmargin="7.2" rightmargin="7.2" bottommargin="14.4" horizontalgap="2" verticalgap="0"/>
		  <label id="L7161" name="Avery L7161" columns="3" rows="6" height="46.6" width="64" total="18" topmargin="9.5" leftmargin="7.2" rightmargin="7.2" bottommargin="7.9" horizontalgap="2" verticalgap="0"/>
		  <label id="L7162" name="Avery L7162" columns="2" rows="8" height="33.9" width="99" total="16" topmargin="13.7" leftmargin="4.7" rightmargin="4.7" bottommargin="12.1" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7163" name="Avery L7163" columns="2" rows="7" height="38.1" width="99" total="14" topmargin="15.9" leftmargin="4.7" rightmargin="4.7" bottommargin="14.4" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7164" name="Avery L7164" columns="3" rows="4" height="71.5" width="64" total="12" topmargin="5" leftmargin="7.2" rightmargin="7.2" bottommargin="6" horizontalgap="2" verticalgap="0"/>
		  <label id="L7165" name="Avery L7165" columns="2" rows="4" height="67.7" width="99" total="8" topmargin="13.8" leftmargin="4.7" rightmargin="4.7" bottommargin="12.4" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7166" name="Avery L7166" columns="2" rows="3" height="93.1" width="99" total="6" topmargin="9.5" leftmargin="4.7" rightmargin="4.7" bottommargin="8.2" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7167" name="Avery L7167" columns="1" rows="1" height="289" width="200" total="1" topmargin="4.7" leftmargin="5.2" rightmargin="5.2" bottommargin="3.3" horizontalgap="-0.4" verticalgap="0"/>
		  <label id="L7168" name="Avery L7168" columns="1" rows="2" height="143.2" width="201" total="2" topmargin="5" leftmargin="4.7" rightmargin="4.7" bottommargin="5.6" horizontalgap="-1.4" verticalgap="0"/>
		  <label id="L7169" name="Avery L7169" columns="2" rows="2" height="139" width="99" total="4" topmargin="9" leftmargin="4.7" rightmargin="4.7" bottommargin="10" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7173" name="Avery L7173" columns="2" rows="5" height="57" width="99" total="10" topmargin="6" leftmargin="4.7" rightmargin="4.7" bottommargin="6" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7418" name="Avery L7418" columns="2" rows="4" height="55" width="86" total="8" topmargin="38.5" leftmargin="19" rightmargin="19" bottommargin="38.5" horizontalgap="0" verticalgap="0"/>
		  <label id="L7421" name="Avery L7421" columns="2" rows="2" height="139.3" width="97" total="4" topmargin="9.5" leftmargin="7.8" rightmargin="7.8" bottommargin="8.9" horizontalgap="0.3" verticalgap="0"/>
		  <label id="L7551" name="Avery L7551" columns="5" rows="13" height="21.2" width="38" total="65" topmargin="11.6" leftmargin="4.7" rightmargin="4.7" bottommargin="9.8" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7562" name="Avery L7562" columns="2" rows="8" height="33.9" width="99" total="16" topmargin="13.7" leftmargin="4.7" rightmargin="4.7" bottommargin="12.1" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7563" name="Avery L7563" columns="2" rows="7" height="38.1" width="99" total="14" topmargin="15.9" leftmargin="4.7" rightmargin="4.7" bottommargin="14.4" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7565" name="Avery L7565" columns="2" rows="4" height="67.7" width="99" total="8" topmargin="13.8" leftmargin="4.7" rightmargin="4.7" bottommargin="12.4" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7651" name="Avery L7651" columns="5" rows="13" height="21.2" width="38" total="65" topmargin="11.6" leftmargin="4.7" rightmargin="4.7" bottommargin="9.8" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7670" name="Avery L7670" columns="3" rows="4" height="63.5" width="64" total="12" topmargin="14.7" leftmargin="5.2" rightmargin="5.2" bottommargin="10.3" horizontalgap="4" verticalgap="4.5"/>
		  <label id="L7680" name="Avery L7680" columns="5" rows="13" height="21.2" width="38" total="65" topmargin="11.6" leftmargin="4.7" rightmargin="4.7" bottommargin="9.8" horizontalgap="2.6" verticalgap="0"/>
		  <label id="L7690" name="Avery L7690" columns="5" rows="13" height="21.2" width="38" total="65" topmargin="11.6" leftmargin="4.7" rightmargin="4.7" bottommargin="9.8" horizontalgap="2.6" verticalgap="0"/>
		  <label id="S3423" name="Avery 3423" columns="2" rows="8" height="105" width="35" total="16" topmargin="8" leftmargin="4.7" rightmargin="4.7" bottommargin="8" horizontalgap="0" verticalgap="0"/>
		</Labels>
	</xsl:param>

</xsl:stylesheet>
