#!/bin/sh
find .. -type f -a \( -name '*dist' -o  -name '*php' -o -name '*tpl' -o -name '*wsdl' \
     -o -name '*xml' -o -name "*map" -o -name "*sym" -o -name "*.c" -o -name "*cpp" \
     -o -name "*README*" -o -name "*.sql.in" \) \
|xargs grep -l '	'|grep -v include/pear|grep -v '/include/'|grep -v 'vera/README.TXT'
