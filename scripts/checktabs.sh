#!/bin/sh
find .. -type f -a \( -name *dist -o  -name *php -o -name *tpl -o -name *wsdl \) |xargs grep -l '	'|grep -v include/pear|grep -v '/include/'
