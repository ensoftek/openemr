XALAN=/usr/bin/xalan

INFILE=sample.xml

DIRNAME=$(shell ${XALAN} -in ${INFILE} -xsl xslt/safename.xslt -text)

# note: disabled preview.html after discovering openemr does not use this.
all: ${DIRNAME} ${DIRNAME}/info.txt ${DIRNAME}/new.php ${DIRNAME}/print.php ${DIRNAME}/report.php ${DIRNAME}/view.php ${DIRNAME}/save.php ${DIRNAME}/table.sql ${DIRNAME}/style.css ${DIRNAME}/show.php

formscript.pl: formscript.pl.orig
	cat formscript.pl.orig | tr -d '\r' > formscript.pl
	chmod 755 formscript.pl

${DIRNAME}:
	mkdir ${DIRNAME}

${DIRNAME}/info.txt: xslt/info.txt.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/info.txt.xslt -out ${DIRNAME}/info.txt -text
#
#${DIRNAME}/preview.html: xslt/preview.html.xslt ${INFILE}
#	${XALAN} -in ${INFILE} -xsl xslt/preview.html.xslt -out ${DIRNAME}/preview.html -text

${DIRNAME}/table.sql: xslt/table.sql.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/table.sql.xslt -out ${DIRNAME}/table.sql -text

${DIRNAME}/style.css: xslt/style.css.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/style.css.xslt -out ${DIRNAME}/style.css -text

${DIRNAME}/new.php: xslt/new.php.xslt xslt/field_objects.xslt xslt/common_objects.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/new.php.xslt -out ${DIRNAME}/new.php -text

${DIRNAME}/print.php: xslt/print.php.xslt xslt/field_objects.xslt xslt/common_objects.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/print.php.xslt -out ${DIRNAME}/print.php -text

${DIRNAME}/show.php: xslt/show.php.xslt xslt/field_objects.xslt xslt/common_objects.xslt xslt/report_objects.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/show.php.xslt -out ${DIRNAME}/show.php -text

${DIRNAME}/view.php: xslt/view.php.xslt xslt/field_objects.xslt xslt/common_objects.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/view.php.xslt -out ${DIRNAME}/view.php -text

${DIRNAME}/save.php: xslt/save.php.xslt xslt/common_objects.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/save.php.xslt -out ${DIRNAME}/save.php -text

${DIRNAME}/report.php: xslt/report.php.xslt xslt/common_objects.xslt ${INFILE}
	${XALAN} -in ${INFILE} -xsl xslt/report.php.xslt -out ${DIRNAME}/report.php -text

clean:
	rm -rf ${DIRNAME}