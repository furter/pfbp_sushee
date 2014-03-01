<?xml version="1.0" encoding="UTF-8"?>
<!--
	XML Spreadsheet to html
	Created by Verdeyen Boris on 2007-04-18.
	Copyright (c) 2007 Nectil. All rights reserved.
-->
<!--

Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/templates/excel_to_html.xsl` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" version="1.0">
	<xsl:output encoding="UTF-8" omit-xml-declaration = "yes" indent="no" method="xml"/>
	<xsl:decimal-format name="euro"
	decimal-separator="," grouping-separator="."/>
	<xsl:param name="width"></xsl:param>
	<xsl:param name="class"></xsl:param>
	<xsl:template match="/ss:Workbook">
		<xsl:for-each select="ss:Worksheet">
			<xsl:for-each select="ss:Table">
				<!-- for debugging -->
				<!--style>td{border:1px solid gray;}</style-->
				<table>

					<xsl:if test="$class!=''">
						<xsl:attribute name="class"><xsl:value-of select="$class"/></xsl:attribute>
					</xsl:if>
					
					<xsl:attribute name="style">
						<xsl:text>border-collapse:collapse;</xsl:text>
						<xsl:if test="$width!=''">
							<xsl:text>width:</xsl:text>
							<xsl:value-of select="$width"/>
							<xsl:text>;</xsl:text>
						</xsl:if>
					</xsl:attribute>

					<xsl:variable name="total_column_width" select="sum(ss:Column/@ss:Width)"/>

					<xsl:for-each select="ss:Row">
						<xsl:if test="@ss:Index and @ss:Index != count(preceding-sibling::ss:Row)+1">
							<xsl:call-template name="empty_rows">
								<xsl:with-param name="number" select="@ss:Index - count(preceding-sibling::ss:Row)+1"/>
							</xsl:call-template>
						</xsl:if>
						<tr>
							<xsl:variable name="styles">
								<xsl:apply-templates select="@ss:Height"/>
								<xsl:apply-templates select="/ss:Workbook/ss:Styles/ss:Style[@ss:ID=current()/@ss:StyleID]"/>
							</xsl:variable>
							<xsl:if test="$styles!=''">
								<xsl:attribute name="style"><xsl:value-of select="$styles"/></xsl:attribute>
							</xsl:if>
							<!-- every cell will apply the templates for its next cell -->
							<xsl:apply-templates select="ss:Cell[1]">
								<xsl:with-param name="total_column_width" select="$total_column_width"/>
							</xsl:apply-templates>
						</tr>
						<xsl:if test="position()=last() and ss:Cell/@ss:MergeDown">
							<xsl:variable name="max_merge_down">
								<xsl:for-each select="ss:Cell/@ss:MergeDown">
									<xsl:sort select="."
										data-type="number"/>
									<xsl:if test="position()=last()">
										<xsl:value-of select="."/>
									</xsl:if>
								</xsl:for-each>
							</xsl:variable>
							<xsl:call-template name="empty_rows">
								<xsl:with-param name="number" select="$max_merge_down +1 "/>
							</xsl:call-template>
						</xsl:if>
						<xsl:if test="position()=last() and not(ss:Cell) and preceding-sibling::ss:Row[1]/ss:Cell/@ss:MergeDown">
							<xsl:variable name="max_merge_down">
								<xsl:for-each select="preceding-sibling::ss:Row[1]/ss:Cell/@ss:MergeDown">
									<xsl:sort select="."
										data-type="number"/>
									<xsl:if test="position()=last()">
										<xsl:value-of select="."/>
									</xsl:if>
								</xsl:for-each>
							</xsl:variable>
							<xsl:call-template name="empty_rows">
								<xsl:with-param name="number" select="$max_merge_down "/><!--  not +1 because we have already the empty line to merge -->
							</xsl:call-template>
						</xsl:if>
					</xsl:for-each>
				</table>
			</xsl:for-each>
		</xsl:for-each>
	</xsl:template>
	
	<xsl:template match="ss:Cell">
		<xsl:param name="previous_position" select="position() - 1"/>
		<xsl:param name="total_column_width" select="0"/>
		<xsl:variable name="shifted" select="@ss:Index and @ss:Index &gt; $previous_position + 1"/>
		<xsl:variable name="pos">
			<xsl:choose>
				<xsl:when test="$shifted">
					<xsl:value-of select="@ss:Index"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$previous_position + 1"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:if test="$shifted">
			<xsl:call-template name="empty_cells">
				<xsl:with-param name="number" select="@ss:Index - $previous_position"/>
				<xsl:with-param name="pos" select="$previous_position + 1"/>
				<xsl:with-param name="total_column_width" select="$total_column_width"/>
			</xsl:call-template>
		</xsl:if>
		<td>
			<xsl:if test="@ss:MergeAcross">
				<xsl:attribute name="colspan"><xsl:value-of select="@ss:MergeAcross + 1"/></xsl:attribute>
			</xsl:if>
			<xsl:if test="@ss:MergeDown">
				<xsl:attribute name="rowspan"><xsl:value-of select="@ss:MergeDown + 1"/></xsl:attribute>
			</xsl:if>
			<xsl:attribute name="style">
				<xsl:apply-templates select="../../ss:Column[position()=$pos]" >
					<xsl:with-param name="total_column_width" select="$total_column_width"/>
				</xsl:apply-templates>
				<xsl:apply-templates select="@ss:Height"/>
				<xsl:apply-templates select="/ss:Workbook/ss:Styles/ss:Style[@ss:ID=current()/../../ss:Column[position()=$pos]/@ss:StyleID]"/>
				<xsl:apply-templates select="/ss:Workbook/ss:Styles/ss:Style[@ss:ID=current()/@ss:StyleID]"/>
				<xsl:apply-templates select="ss:Data/@ss:Type" mode="styles"/>
			</xsl:attribute>
			<xsl:apply-templates select="ss:Data"/>
		</td>
		<xsl:apply-templates select="following-sibling::ss:Cell[1]">
			<xsl:with-param name="previous_position" select="$pos"/>
			<xsl:with-param name="total_column_width" select="$total_column_width"/>
		</xsl:apply-templates>
	</xsl:template>
	
	 <!-- and ../@ss:StyleID=/ss:Workbook/ss:Styles/ss:Style[ss:NumberFormat]/@ss:ID -->
	<xsl:template match="@ss:Type" mode="styles"/><!-- by default, a data type doesn't imply style -->
	
	<!-- a number with no alignment in its style is right aligned  -->
	<xsl:template match="@ss:Type[.='Number']" mode="styles">
		<xsl:if test="not(/ss:Workbook/ss:Styles/ss:Style[@ss:ID=current()/ancestor::ss:Cell/@ss:StyleID]/ss:Alignment/@ss:Horizontal)">
			<xsl:text>text-align:right;</xsl:text>
		</xsl:if>
	</xsl:template>

	<xsl:template match="ss:Data[@ss:Type='Number' and ../@ss:StyleID=/ss:Workbook/ss:Styles/ss:Style[ss:NumberFormat]/@ss:ID]">
		<!-- taking the part before the semicolon(;), because after is for negative number -->
		<xsl:variable name="number_format" select="/ss:Workbook/ss:Styles/ss:Style[@ss:ID=current()/../@ss:StyleID]/ss:NumberFormat/@ss:Format"/>
		<xsl:choose>
			<xsl:when test="$number_format='Currency'">
				<xsl:value-of select="format-number(.,'#.##0,00','euro')"/><xsl:text> €</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="format-number(., $number_format)" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="ss:Data[@ss:Type='Number' and ../@ss:StyleID=/ss:Workbook/ss:Styles/ss:Style[not(ss:NumberFormat)]/@ss:ID]">
		<!-- numbers without number formats are given a default number format without decimals -->
		<xsl:value-of select="format-number(., '#.#')" />
	</xsl:template>

	<xsl:template match="ss:Data[../@ss:HRef != '']">
		<a href="{../@ss:HRef}">
			<xsl:apply-templates/>
		</a>
	</xsl:template>

	<xsl:template match="ss:Data[@ss:Type='String']">
		<xsl:apply-templates />
	</xsl:template>
	
	<xsl:template match="ss:Data[@ss:Type='String']//*">
		<xsl:apply-templates/>
	</xsl:template>
		
	<xsl:template match="ss:Data[@ss:Type='String']//*[local-name() = 'Font']">
		<span style="color:{@*[local-name() = 'Color']};">
			<xsl:apply-templates/>
		</span>
	</xsl:template>

	<xsl:template match="ss:Data[@ss:Type='String']//*[local-name() = 'B']">
		<strong>
			<xsl:apply-templates/>
		</strong>
	</xsl:template>
	
	<xsl:template match="ss:Data[@ss:Type='String']//*[local-name() = 'U']">
		<xsl:apply-templates/>
	</xsl:template>
	
	<xsl:template match="ss:Data[@ss:Type='String']//*[local-name() = 'I']">
		<em>
			<xsl:apply-templates/>
		</em>
	</xsl:template>
	
	<xsl:template match="ss:Data[@ss:Type='String']//text()">
		<xsl:value-of select="."/>
	</xsl:template>

	<xsl:template match="ss:Data[@ss:Type='String' and starts-with(.,'http://')]">
		<xsl:choose>
			<xsl:when test="contains(.,'.png') or contains(.,'.jpg') or contains(.,'.gif')">
				<img src="{.}" />
			</xsl:when>
			<xsl:otherwise>
				<a href="{.}">
					<xsl:value-of select="."/>
				</a>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="empty_cells">
		<xsl:param name="number"/>
		<xsl:param name="pos">1</xsl:param>
		<xsl:param name="total_column_width" select="0"/>
		<xsl:if test="$number &gt; 1">
			<xsl:variable name="cur-pos" select="position()"/>
			<xsl:if test="not( 
							../preceding-sibling::ss:Row/ss:Cell
								[position()=$pos]
								[@ss:MergeDown &gt; 
									count( ../following-sibling::ss:Row[position() &lt; $cur-pos] )
								 ] )">
				<td>
					<xsl:attribute name="style">
						<xsl:apply-templates select="../../ss:Column[position()=$pos]" >
							<xsl:with-param name="total_column_width" select="$total_column_width"/>
						</xsl:apply-templates>
						<xsl:apply-templates select="/ss:Workbook/ss:Styles/ss:Style[@ss:ID=current()/../../ss:Column[position()=$pos]/@ss:StyleID]"/>
					</xsl:attribute>
				</td>
			</xsl:if>
			<xsl:call-template name="empty_cells">
				<xsl:with-param name="number" select="$number - 1"/>
				<xsl:with-param name="pos" select="$pos + 1"/>
				<xsl:with-param name="total_column_width" select="$total_column_width"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<xsl:template name="empty_rows">
		<xsl:param name="number"/>
		<xsl:param name="pos">1</xsl:param>
		<xsl:if test="$number &gt; 1">
			<tr/>
			<xsl:call-template name="empty_rows">
				<xsl:with-param name="number" select="$number - 1"/>
				<xsl:with-param name="pos" select="$pos + 1"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<xsl:template match="ss:Column">
		<xsl:param name="total_column_width"/>
		<xsl:if test="number($total_column_width)">
			<xsl:text>width:</xsl:text>
			<xsl:value-of select="floor(@ss:Width div $total_column_width * 100 )"/>
			<xsl:text>%;</xsl:text>
		</xsl:if>
	</xsl:template>
	
	<xsl:template match="@ss:Height">
		<!--xsl:text>/* fixed height */</xsl:text-->
		<xsl:text>height:</xsl:text>
		<xsl:value-of select="floor(.)"/>
		<xsl:text>px;</xsl:text>
	</xsl:template>
	
	<xsl:template match="/ss:Workbook/ss:Styles/ss:Style"><!-- style node in general -->
		<xsl:apply-templates select="ss:Font"/>
		<xsl:apply-templates select="ss:Alignment"/>
		<xsl:apply-templates select="ss:Borders/ss:Border"/>
		<xsl:apply-templates select="ss:Interior"/>
	</xsl:template>
	
	<xsl:template match="ss:Interior">
		<xsl:if test="@ss:Color">
			<xsl:text>background-color:</xsl:text>
			<xsl:value-of select="@ss:Color"/>
			<xsl:text>;</xsl:text>
		</xsl:if>
	</xsl:template>
	
	<xsl:template match="ss:Font"><!-- Fonts -->
		<!--xsl:text>/* fonts styles */</xsl:text-->
		<xsl:if test="@ss:Color">
			<xsl:text>color:</xsl:text>
			<xsl:value-of select="@ss:Color"/>
			<xsl:text>;</xsl:text>
		</xsl:if>
		<xsl:if test="@ss:FontName and @ss:FontName != 'Verdana' and @ss:FontName != 'Arial' and @ss:FontName != 'Helvetica'">
			<xsl:text>font-family:</xsl:text>
			<xsl:value-of select="@ss:FontName"/>
			<xsl:text>;</xsl:text>
		</xsl:if>
		<xsl:if test="@ss:Bold &gt; 0">
			<xsl:text>font-weight:bold;</xsl:text>
		</xsl:if>
		<xsl:if test="@ss:Italic &gt; 0">
			<xsl:text>font-style:italic;</xsl:text>
		</xsl:if>
		<xsl:choose>
			<xsl:when test="@ss:Size">
				<xsl:text>font-size:</xsl:text>
				<xsl:value-of select="@ss:Size"/>
				<xsl:text>px;</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>font-size:10px;</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="ss:Alignment">
		<!--xsl:text>/* text alignments */</xsl:text-->
		<xsl:choose>
			<xsl:when test="@ss:Horizontal='Left'">
				<xsl:text>text-align:left;</xsl:text>
			</xsl:when>
			<xsl:when test="@ss:Horizontal='Right'">
				<xsl:text>text-align:right;</xsl:text>
			</xsl:when>
			<xsl:when test="@ss:Horizontal='Center'">
				<xsl:text>text-align:center;</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>text-align:left;</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:choose>
			<xsl:when test="@ss:Vertical='Top'">
				<xsl:text>vertical-align:top;</xsl:text>
			</xsl:when>
			<xsl:when test="@ss:Vertical='Bottom'">
				<xsl:text>vertical-align:bottom;</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>vertical-align:middle;</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="ss:Border">
		<!--xsl:text>/* borders */</xsl:text-->
		<xsl:if test="@ss:Position">
			<xsl:text>border-</xsl:text>
			<xsl:choose>
				<xsl:when test="@ss:Position='Top'">
					<xsl:text>top</xsl:text>
				</xsl:when>
				<xsl:when test="@ss:Position='Bottom'">
					<xsl:text>bottom</xsl:text>
				</xsl:when>
				<xsl:when test="@ss:Position='Left'">
					<xsl:text>left</xsl:text>
				</xsl:when>
				<xsl:when test="@ss:Position='Right'">
					<xsl:text>right</xsl:text>
				</xsl:when>
			</xsl:choose>
			<xsl:text>:</xsl:text>
			<xsl:value-of select="@ss:Weight"/>
			<xsl:text>px </xsl:text>
			<xsl:choose>
				<xsl:when test="@ss:LineStyle='Continuous'">
					<xsl:text>solid</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>solid</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:text> </xsl:text>
			<xsl:choose>
				<xsl:when test="@ss:Color">
					<xsl:value-of select="@ss:Color"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>black</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:text>;</xsl:text>
		</xsl:if>
	</xsl:template>
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
			<xsl:value-of select="$to"/>
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
</xsl:stylesheet>
