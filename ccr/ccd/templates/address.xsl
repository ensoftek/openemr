<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:a="urn:astm-org:CCR" xmlns:fo="http://www.w3.org/1999/XSL/Format">
    <xsl:template name="address">
        <xsl:param name="actorID"/>
        <xsl:variable name="actor" select="/a:ContinuityOfCareRecord/a:Actors/a:Actor[a:ActorObjectID=$actorID]"/>
        <td>
            <table class="internal" cellpadding="0" cellspacing="0">
                <tr>
                    <td valign="top">
                        <xsl:for-each select="$actor/a:Address">
                            <xsl:if test="a:Type">
                                <b>
                                    <xsl:value-of select="a:Type/a:Text"/>:
                                </b>
                                <br/>
                            </xsl:if>
                            <xsl:if test="a:Line1">
                                <xsl:value-of select="a:Line1"/>
                                <br/>
                            </xsl:if>
                            <xsl:if test="a:Line2">
                                <xsl:value-of select="a:Line2"/>
                                <br/>
                            </xsl:if>
                            <xsl:if test="a:City">
                                <xsl:value-of select="a:City"/>,<xsl:text xml:space="preserve"> </xsl:text>
                            </xsl:if>
                            <xsl:value-of select="a:State"/>
                            <xsl:text xml:space="preserve"> </xsl:text>
                            <xsl:value-of select="a:PostalCode"/>
                            <br/>
                        </xsl:for-each>
                    </td>
                    <td valign="top">
                        <xsl:for-each select="a:Telephone">
                            <xsl:if test="a:Type/a:Text">
                                <xsl:value-of select="a:Type/a:Text"/> Phone:<xsl:text xml:space="preserve"> </xsl:text>
                            </xsl:if>
                            <xsl:value-of select="a:Value"/>
                        </xsl:for-each>
                        <xsl:for-each select="a:EMail">
                            Email:<xsl:text xml:space="preserve"> </xsl:text><xsl:value-of select="a:Value"/>
                        </xsl:for-each>
                    </td>
                </tr>
            </table>
        </td>
    </xsl:template>
</xsl:stylesheet>