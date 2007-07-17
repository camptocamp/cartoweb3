#!/bin/sh
for i in $(find .. -type f ! -path "../locale/*" ! -path "../include/*" ! -path "../www-data/*" ! -iname "*png" ! -iname "*gif" ! -iname "*jpg" \
    ! -iname "*ttf" ! -iname "*dbf" ! -iname "*shp" ! -iname "*jar" ! -iname "*class" ! -iname "*qix" \
    ! -iname "*gz" ! -iname "*bat" ! -iname "*shx" ! -iname "*rtf" ! -iname "*tif" ! -iname "*tiff" ! -iname "*ico" ! -iname "*db"); do od -c  $i |grep -q '\\r' && echo $i; done
