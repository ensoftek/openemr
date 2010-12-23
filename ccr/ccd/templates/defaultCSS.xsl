<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:a="urn:astm-org:CCR"  xmlns:fo="http://www.w3.org/1999/XSL/Format">
<xsl:template name="defaultCCS">
<style type="text/css">
*{
	font-size: x-small;
	font-family: Arial, sans-serif;
}
h1{
	font-weight: bold;
    font-size: medium;
}
strong.clinical {
	color: #3300FF;
}
p {
	margin-left: 20px
}
span.header{
	font-weight: bold;
    font-size: medium;
    line-height: 16pt;
	padding-top: 10px;
}
table.list {
	padding-bottom: 5px;
	border: thin solid #cccccc;
	border-style-internal: thin solid #cccccc;
	BORDER-COLLAPSE: collapse;
	background: white;
	background-image: none
}
table.list th {
	text-align: left;
	FONT-WEIGHT: bold;
	COLOR: white;
	background: #006699;
	background-image: none
}
table.list td {
	padding: 5px;
	border: thin solid #cccccc;
	vertical-align: top;
}
table.internal {
	border: none;	
}
table.internal td {
	vertical-align: top;
    padding: 1px;
    border: none;
}
table.internal tr.even{
	background: #CEFFFF;
	background-image: none
}
</style>
</xsl:template>
</xsl:stylesheet>
