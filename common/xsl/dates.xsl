<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" omit-xml-declaration="yes"/>
	<!--
	 contiens des fonctions de base pour générer
		- une date simple
		- des dates sur une période
		- le numéro du jour
		- la date et l'heure
	-->
	<xsl:template name="day">
		<xsl:param name="date"/>
		<xsl:choose>
			<xsl:when test="substring($date,9,1) = 0">
				<xsl:value-of select="substring($date,10,1)"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="substring($date,9,2)"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template name="date_hour">
		<xsl:param name="date"/>
		<xsl:call-template name="date">
			<xsl:with-param name="date" select="$date"/>
		</xsl:call-template>
		<xsl:text> </xsl:text>
		<xsl:value-of select="//LABEL[@name='between_date_and_hour']"/>
		<xsl:text> </xsl:text>
		<xsl:value-of select="substring($date, 12, 5)"/>
	</xsl:template>
	<xsl:template name="date">
		<xsl:param name="date"/>
		<xsl:call-template name="day">
			<xsl:with-param name="date">
				<xsl:value-of select="$date"/>
			</xsl:with-param>
		</xsl:call-template>
		<xsl:text>/</xsl:text>
		<xsl:value-of select="substring($date,6,2)"/>
		<xsl:text>/</xsl:text>
		<xsl:value-of select="substring($date,1,4)"/>
	</xsl:template>
	<xsl:template name="dates">
		<xsl:param name="start" select="INFO/EVENTSTART"/>
		<xsl:param name="end" select="INFO/EVENTEND"/>
		<xsl:choose>
			<xsl:when test="substring($end,1,4) = 0000">
				<xsl:call-template name="date">
					<xsl:with-param name="date">
						<xsl:value-of select="$start"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="substring($start,1,4) != substring($end,1,4)">
				<xsl:call-template name="date_hour">
					<xsl:with-param name="date">
						<xsl:value-of select="$start"/>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:text> - </xsl:text>
				<xsl:call-template name="date_hour">
					<xsl:with-param name="date">
						<xsl:value-of select="$end"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="substring($start,6,2) != substring($end,6,2)">
				<xsl:call-template name="day">
					<xsl:with-param name="date">
						<xsl:value-of select="$start"/>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:text> </xsl:text>
				<xsl:value-of select="translate(/RESPONSE/RESULTS/MONTH[@ID = substring($start,6,2)]/LABEL, $majuscules,$minuscules)"/>
				<xsl:text> - </xsl:text>
				<xsl:call-template name="day">
					<xsl:with-param name="date">
						<xsl:value-of select="$end"/>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:text> </xsl:text>
				<xsl:value-of select="translate(/RESPONSE/RESULTS/MONTH[@ID = substring($start,6,2)]/LABEL, $majuscules,$minuscules)"/>
				<xsl:text> </xsl:text>
				<xsl:value-of select="substring($start,1,4)"/>
			</xsl:when>
			<xsl:when test="substring($start,9,2) != substring($end,9,2)">
				<xsl:call-template name="day">
					<xsl:with-param name="date">
						<xsl:value-of select="$start"/>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:choose>
					<xsl:when test="substring($start,9,2) = substring($end,9,2) - 1">
						<xsl:text> + </xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text> → </xsl:text>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:call-template name="day">
					<xsl:with-param name="date">
						<xsl:value-of select="$end"/>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:text>-</xsl:text>
				<xsl:value-of select="substring($start,6,2)"/>
				<!--xsl:value-of	select="translate(/RESPONSE/RESULTS/MONTH[@ID = substring($start,6,2)]/LABEL, $majuscules,$minuscules)"/-->
				<xsl:text>-</xsl:text>
				<xsl:value-of select="substring($start,1,4)"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="date_hour">
					<xsl:with-param name="date">
						<xsl:value-of select="$start"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
</xsl:stylesheet>