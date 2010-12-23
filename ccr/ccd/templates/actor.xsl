<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:a="urn:astm-org:CCR"  xmlns:fo="http://www.w3.org/1999/XSL/Format">
    <!-- Returns the name of the actor, if there is no name it returns the ActorObjectID that was passed in -->
    <xsl:template name="actorName">
        <xsl:param name="objID"/>
        <xsl:variable name="actor" select="//a:ContinuityOfCareRecord/a:Actors/a:Actor[a:ActorObjectID=$objID]"/>
        <xsl:choose>
            <xsl:when test="$actor/a:Person">
                <xsl:choose>
                    <xsl:when test="$actor/a:Person/a:Name/a:DisplayName">
                        <xsl:value-of select="$actor/a:Person/a:Name/a:DisplayName"/>
                    </xsl:when>
                    <xsl:when test="$actor/a:Person/a:Name/a:CurrentName">
                        <xsl:value-of select="$actor/a:Person/a:Name/a:CurrentName/a:Given"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                        <xsl:value-of select="$actor/a:Person/a:Name/a:CurrentName/a:Middle"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                        <xsl:value-of select="$actor/a:Person/a:Name/a:CurrentName/a:Family"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                        <xsl:value-of select="$actor/a:Person/a:Name/a:CurrentName/a:Suffix"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                        <xsl:value-of select="$actor/a:Person/a:Name/a:CurrentName/a:Title"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                    </xsl:when>
                    <xsl:when test="$actor/a:Person/a:Name/a:BirthName">
                        <xsl:value-of select="$actor/a:Person/a:Name/a:BirthName/a:Given"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                        <xsl:value-of select="$actor/a:Person/a:Name/a:BirthName/a:Middle"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                        <xsl:value-of select="$actor/a:Person/a:Name/a:BirthName/a:Family"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                        <xsl:value-of select="$actor/a:Person/a:Name/a:BirthName/a:Suffix"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                        <xsl:value-of select="$actor/a:Person/a:Name/a:BirthName/a:Title"/>
                        <xsl:text xml:space="preserve"> </xsl:text>
                    </xsl:when>
                    <xsl:when test="$actor/a:Person/a:Name/a:AdditionalName">
                        <xsl:for-each select="$actor/a:Person/a:Name/a:AdditionalName">
                            <xsl:value-of select="a:Given"/>
                            <xsl:text xml:space="preserve"> </xsl:text>
                            <xsl:value-of select="a:Middle"/>
                            <xsl:text xml:space="preserve"> </xsl:text>
                            <xsl:value-of select="a:Family"/>
                            <xsl:text xml:space="preserve"> </xsl:text>
                            <xsl:value-of select="a:Suffix"/>
                            <xsl:text xml:space="preserve"> </xsl:text>
                            <xsl:value-of select="a:Title"/>
                            <xsl:text xml:space="preserve"> </xsl:text>
                            <xsl:if test="position() != last()">
                                <br/>
                            </xsl:if>
                        </xsl:for-each>
                    </xsl:when>
                </xsl:choose>
            </xsl:when>
            <xsl:when test="$actor/a:Organization">
                <xsl:value-of select="$actor/a:Organization/a:Name"/>
            </xsl:when>
            <xsl:when test="$actor/a:InformationSystem">
                <xsl:value-of select="$actor/a:InformationSystem/a:Name"/>
                <xsl:text xml:space="preserve"> </xsl:text>
                <xsl:if test="$actor/a:InformationSystem/a:Version">
                    <xsl:value-of select="$actor/a:InformationSystem/a:Version"/>
                    <xsl:text xml:space="preserve"> </xsl:text>
                </xsl:if>
                <xsl:if test="$actor/a:InformationSystem/a:Type">
                    (<xsl:value-of select="$actor/a:InformationSystem/a:Type"/>)
                </xsl:if>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$objID"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- End actorname template -->
</xsl:stylesheet>
