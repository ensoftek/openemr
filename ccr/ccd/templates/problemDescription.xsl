<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:a="urn:astm-org:CCR" xmlns:fo="http://www.w3.org/1999/XSL/Format">
	<!-- Returns the description of the problem, if there is no name it returns the ObjectID that was passed in -->
	<xsl:template name="problemDescription">
		<xsl:param name="objID"/>
		<xsl:for-each select="/a:ContinuityOfCareRecord/a:Body/a:Problems/a:Problem">
			<xsl:variable name="thisObjID" select="a:CCRDataObjectID"/>
			<xsl:if test="$objID = $thisObjID">
				<xsl:choose>
					<xsl:when test="a:Description/a:Text">
						<xsl:value-of select="a:Description/a:Text"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$objID"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
		</xsl:for-each>
	</xsl:template>
	<!-- End problemDescription template -->
</xsl:stylesheet>
