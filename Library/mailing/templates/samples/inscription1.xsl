<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="html" indent="yes" encoding="utf-8"/>
	
	<xsl:template match="/RESPONSE">
		<html>
			<head>
				<title>Mailing list subscription form</title>
			</head>
			<body>
				<form method="post" action="inscription1.php">
					<table class="formulaire_inscription">
						<tr><td class="col1">Lastname:</td>
							<td class="col2"><input class="textinput" type="text" name="lastname" value="{/RESPONSE/URL/lastname}"/></td>
						</tr>
						<tr><td class="col1">Firstname:</td>
							<td class="col2"><input class="textinput" name="firstname" type="text" value="{/RESPONSE/URL/firstname}"/></td>
						</tr>
						<tr><td class="col1">Email:</td>
							<td class="col2"><input class="textinput" name="email" type="text" value="{/RESPONSE/URL/email}"/></td>
						</tr>
						<tr><td class="col1">Address:</td>
							<td class="col2"><input class="textinput" name="address" type="text" value=""/></td>
						</tr>
						<tr><td class="col1"><label for="city">City:</label></td>
							<td><input class="textinput" name="city" type="text" value="{/RESPONSE/URL/city}"/></td>
						</tr><tr><td class="col1">Country:</td>
							<td class="col2">
								<select name="countryid">
									<xsl:variable name="countryid" select="/RESPONSE/URL/countryid"/>
									<xsl:for-each select="/RESPONSE/RESULTS[@name='countries']/COUNTRY">
										<option value="{@ID}" >
										<xsl:if test="(@ID='bel' and not(/RESPONSE/URL/countryid)) or (/RESPONSE/URL/countryid and $countryid = current()/@ID)"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
										<xsl:value-of select="LABEL"/>
										</option>
									</xsl:for-each>
								</select>
							</td>
						</tr>
						<tr><td colspan="2" style="text-align:right;"><input class="submit" value="Send" type="submit"/></td></tr>
					</table>
				</form>
				
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
