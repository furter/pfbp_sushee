<?xml version="1.0" encoding="utf-8"?>
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/templates/vcalendar.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="text" indent="no" encoding="utf-8"/>
	
	<xsl:template match="/RESPONSE">
		<xsl:text>BEGIN:VCALENDAR</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>PRODID:-//Nectil//Officity Dates</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>VERSION:2.0</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>METHOD:PUBLISH</xsl:text><xsl:text>
X-WR-TIMEZONE:Europe/Brussels
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Europe/Brussels
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
END:STANDARD
END:VTIMEZONE
</xsl:text>
		<xsl:apply-templates select="RESULTS"/>
		<xsl:text>END:VCALENDAR</xsl:text><xsl:text>
</xsl:text>
	</xsl:template>
	
	<xsl:template match="RESULTS">
		<xsl:apply-templates select="EVENT"/>
	</xsl:template>
	
	<xsl:template match="EVENT">
		<xsl:text>BEGIN:VEVENT</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>ORGANIZER:MAILTO:</xsl:text><xsl:value-of select="/RESPONSE/RESULTS/CONTACT/INFO/EMAIL1"/><xsl:text>
</xsl:text>
		<xsl:text>DTSTART:</xsl:text><xsl:apply-templates select="INFO/START"/><xsl:text>
</xsl:text>
		<xsl:text>DTEND:</xsl:text><xsl:apply-templates select="INFO/END"/><xsl:text>
</xsl:text>
		<xsl:text>TRANSP:OPAQUE</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>SEQUENCE:0</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>UID:</xsl:text><xsl:choose>
			<xsl:when test="INFO/UID!=0 and INFO/UID!=''">
				<xsl:value-of select="INFO/UID"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>Officity</xsl:text><xsl:value-of select="@ID"/>
			</xsl:otherwise>
		</xsl:choose><xsl:text>
</xsl:text>
		<xsl:text>DTSTAMP:</xsl:text><xsl:apply-templates select="INFO/CREATIONDATE"/><xsl:text>
</xsl:text>
		<xsl:text>SUMMARY:</xsl:text><xsl:apply-templates select="INFO/TITLE"/><xsl:text>
</xsl:text>
		<xsl:if test="INFO/COMMENT/text()">
			<xsl:text>DESCRIPTION:</xsl:text><xsl:apply-templates select="INFO/COMMENT"/><xsl:text>
</xsl:text>
		</xsl:if>
		<xsl:text>PRIORITY:1</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>CLASS:PUBLIC</xsl:text><xsl:text>
</xsl:text>
		<xsl:if test="//EVENTCALENDAR[INFO/ID=current()/INFO/CALENDARID]">
			<xsl:text>CATEGORY:</xsl:text><xsl:value-of select="//EVENTCALENDAR[INFO/ID=current()/INFO/CALENDARID]/INFO/DENOMINATION"/><xsl:text>
</xsl:text>
		</xsl:if>
		<xsl:apply-templates select="." mode="alarm"/>
		<xsl:text>END:VEVENT</xsl:text><xsl:text>
</xsl:text>
	</xsl:template>
	
	<xsl:template match="INFO/START | INFO/END | INFO/ALARMDATE | INFO/CREATIONDATE">
		<xsl:value-of select="substring(.,1,4)"/>
		<xsl:value-of select="substring(.,6,2)"/>
		<xsl:value-of select="substring(.,9,2)"/>
		<xsl:text>T</xsl:text>
		<xsl:if test="substring(.,12,2) &lt; 11">
			<xsl:text>0</xsl:text>
		</xsl:if>
		<xsl:value-of select="substring(.,12,2) - 1"/>
		<xsl:value-of select="substring(.,15,2)"/>
		<xsl:value-of select="substring(.,18,2)"/>
		<xsl:text>Z</xsl:text>
	</xsl:template>
	
	<xsl:template match="EVENT[INFO/ALARMOFFSET=0 or not(INFO/ALARMOFFSET/text())]" mode="alarm">
		<xsl:text></xsl:text>
	</xsl:template>
	
	<xsl:template match="EVENT[INFO/ALARMOFFSET/text() and INFO/ALARMOFFSET!=0]" mode="alarm">
		<xsl:text>BEGIN:VALARM</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>TRIGGER:</xsl:text><xsl:apply-templates select="INFO/ALARMOFFSET"/><xsl:text>
</xsl:text>		
		<!--xsl:text>ACTION:AUDIO</xsl:text><xsl:text>
</xsl:text-->
		<xsl:text>ACTION:DISPLAY</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>DESCRIPTION:Reminder</xsl:text><xsl:text>
</xsl:text>
		<!--xsl:text>ATTACH;FMTTYPE=audio/basic:</xsl:text><xsl:apply-templates select="//EVENTCALENDAR[INFO/ID=current()/INFO/CALENDARID]" mode="sound"/><xsl:text>
</xsl:text>
		<xsl:text>REPEAT:1</xsl:text><xsl:text>
</xsl:text>
		<xsl:text>DURATION:PT1H</xsl:text><xsl:text>
</xsl:text-->
		<xsl:text>END:VALARM</xsl:text><xsl:text>
</xsl:text>
	</xsl:template>
	
	<xsl:template match="INFO/ALARMOFFSET">
		<xsl:text>-PT</xsl:text><xsl:value-of select=". div 60000"/><xsl:text>M</xsl:text>
	</xsl:template>
	
	<xsl:template match="EVENTCALENDAR" mode="sound">
		<xsl:value-of select="/RESPONSE/NECTIL/kernel_url"/>
		<xsl:text>Library/sounds/</xsl:text>
		<xsl:value-of select="INFO/ALERT"/>
		<xsl:text>.mp3</xsl:text>
	</xsl:template>
	
</xsl:stylesheet>