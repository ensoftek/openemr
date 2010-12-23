<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">
    <xsl:template name="HL7OIDLookup">
        <xsl:param name="name"/>

        <xsl:value-of select="document('hl7oid.xml')/entities/entity[name=$name]/oid"/>        
    </xsl:template>
    <xsl:template name="HL7CodeSystemNameLookup">
        <xsl:param name="oid"/>

        <xsl:value-of select="document('hl7oid.xml')/entities/entity[oid=$oid]/name"/>        
    </xsl:template>
</xsl:stylesheet>

