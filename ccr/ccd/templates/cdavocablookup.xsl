<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template name="CDAVocabularyLookup">
        <xsl:param name="domain"/>
        <xsl:param name="ccrtext"/>

        <xsl:variable name="map" select="document('cdavocabmap.xml')"/>
        <xsl:variable name="ccrtext_uc" select="translate($ccrtext, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')"/>
        <xsl:variable name="cdaCodeMatch" select="$map/domains/domain[@name=$domain]/item[translate(cdacode,'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')=$ccrtext_uc]/cdacode"/>
        <xsl:choose>
            <xsl:when test="$cdaCodeMatch">
                <xsl:value-of select="$cdaCodeMatch"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$map/domains/domain[@name=$domain]/item[translate(ccrtext,'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')=$ccrtext_uc]/cdacode"/>    
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="CDADisplayNameLookup">
        <xsl:param name="domain"/>
        <xsl:param name="cdacode"/>
        <xsl:variable name="map" select="document('cdavocabmap.xml')"/>
        <xsl:choose>
            <xsl:when test="$map/domains/domain[@name=$domain]/item[cdacode=$cdacode]/cdadisplayname">
                <xsl:value-of select="$map/domains/domain[@name=$domain]/item[cdacode=$cdacode]/cdadisplayname"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$map/domains/domain[@name=$domain]/item[cdacode=$cdacode]/ccrtext"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="CDAVocabularyCodeSystemNameLookup">
        <xsl:param name="domain"/>
        
        <xsl:value-of select="document('cdavocabmap.xml')/domains/domain[@name=$domain]/@codeSystemName"/>
    </xsl:template>

    <xsl:template name="CDAVocabularyCodeSystemLookup">
        <xsl:param name="domain"/>

        <xsl:value-of select="document('cdavocabmap.xml')/domains/domain[@name=$domain]/@codeSystem"/>
    </xsl:template>
    
</xsl:stylesheet>
