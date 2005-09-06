#!/bin/sh
# TODO: add ../tests once modified
find ../coreplugins ../plugins ../client ../common ../server ../projects ../htdocs -type f -a -iname "*php" |xargs egrep  '^[[:space:]]+function'\
|grep -v 'Cartoserver.php:    function getMapInfo\|Cartoserver.php:    function getMap\|Common.php:    function utf8_'
