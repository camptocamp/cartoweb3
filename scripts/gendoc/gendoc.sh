#!/bin/bash

DOC_PATH=/home/sypasche/public_html/cartoweb_doc
CVSROOT=:pserver:sypasche@pserver.c2c:/var/cvs/mapserver
PHP_PATH=/usr/lib/cgi-bin/php5

cd $DOC_PATH
export CVSROOT
cvs co cartoweb3
rm -rf cartoweb3/documentatin/apidoc docbook/xhtml docbook/book.pdf docbook/doc/howto_docbook.pdf

# phpdocumentor
$PHP_PATH cartoweb3/scripts/makedoc.php > apidoc_log.txt 2>&1

# docbook
test -L docbook/source || ln -s ../cartoweb3/documentation/user_manual/source docbook/source
(cd docbook && make xhtml pdf howto > ../docbook_log.txt 2>&1)

sed -e "s,@DATE@,$(date),g" index.html.in  > index.html
