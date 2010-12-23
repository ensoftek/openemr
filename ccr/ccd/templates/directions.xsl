<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:a="urn:astm-org:CCR" xmlns:fo="http://www.w3.org/1999/XSL/Format">
	<xsl:template match="a:Directions">
		<xsl:for-each select="a:Direction">
			<xsl:choose>
				<xsl:when test="position() mod 2=0">
					<tr class="even">
						<xsl:choose>
							<xsl:when test="a:Description/a:Text">
								<td>
									<xsl:value-of select="a:Description/a:Text"/>
								</td>
							</xsl:when>
							<xsl:otherwise>
								<td>
									<xsl:value-of select="a:Dose/a:Value"/><xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Dose/a:Units/a:Unit"/><xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Route/a:Text"/><xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Frequency/a:Value"/>
									<xsl:if test="a:Duration"><xsl:text xml:space="preserve"> </xsl:text>(for <xsl:value-of select="a:Duration/a:Value"/><xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Duration/a:Units/a:Unit"/>)
																								</xsl:if>
								</td>
								<xsl:if test="a:MultipleDirectionModifier/a:ObjectAttribute">
									<td>
										<xsl:for-each select="a:MultipleDirectionModifier/a:ObjectAttribute">
											<xsl:value-of select="a:Attribute"/>
											<br/>
											<xsl:value-of select="a:AttributeValue/a:Value"/>
										</xsl:for-each>
									</td>
								</xsl:if>
							</xsl:otherwise>
						</xsl:choose>
					</tr>
				</xsl:when>
				<xsl:otherwise>
					<tr class="odd">
						<xsl:choose>
							<xsl:when test="a:Description/a:Text">
								<td>
									<xsl:value-of select="a:Description/a:Text"/>
								</td>
							</xsl:when>
							<xsl:otherwise>
								<td>
									<xsl:value-of select="a:Dose/a:Value"/><xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Dose/a:Units/a:Unit"/><xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Route/a:Text"/><xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Frequency/a:Value"/>
									<xsl:if test="a:Duration"><xsl:text xml:space="preserve"> </xsl:text>(for <xsl:value-of select="a:Duration/a:Value"/><xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Duration/a:Units/a:Unit"/>)
																								</xsl:if>
								</td>
								<xsl:if test="a:MultipleDirectionModifier/a:ObjectAttribute">
									<td>
										<xsl:for-each select="a:MultipleDirectionModifier/a:ObjectAttribute">
											<xsl:value-of select="a:Attribute"/>
											<br/>
											<xsl:value-of select="a:AttributeValue/a:Value"/>
										</xsl:for-each>
									</td>
								</xsl:if>
							</xsl:otherwise>
						</xsl:choose>
					</tr>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>
