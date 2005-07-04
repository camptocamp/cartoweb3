#!/bin/sh
find .. -type f -a \( -name '*dist' -o  -name '*php' -o -name '*tpl' -o -name '*wsdl' -o -name '*xml' -o -name "*map" -o -name "*sym" \) \
|xargs grep -l '	'|grep -v include/pear|grep -v '/include/'
