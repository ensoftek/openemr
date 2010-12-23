<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:a="urn:astm-org:CCR"  xmlns:fo="http://www.w3.org/1999/XSL/Format">
<xsl:template match="a:Code">
		<xsl:value-of select="a:Value"/>
		<xsl:if test="a:CodingSystem">
			<xsl:text xml:space="preserve"> </xsl:text>(<xsl:value-of select="a:CodingSystem"/>)
		</xsl:if>
</xsl:template>
</xsl:stylesheet>
