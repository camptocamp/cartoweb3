#!/bin/sh

# Albert, Abel, Example-006 are artichow files. Remove the rules once moved to cartoweb_includes

for i in $(find .. -type f ! -path "../locale/*" ! -path "../include/*" ! -path "../www-data/*" ! -iname "*png" ! -iname "*gif" ! -iname "*jpg" \
    ! -iname "*ttf" ! -iname "*dbf" ! -iname "*shp" ! -iname "*jar" ! -iname "*class" ! -iname "*qix" \
    ! -iname "*gz" ! -iname "*bat" ! -iname "*shx" ! -iname "*rtf" ! -iname "*tif" ! -iname "*tiff" ! -iname "*ico" ! -iname "*db" \
    ! -name Albert ! -name Abel ! -name Example-006 ); \
    do od -c  $i |grep -q '\\r' && echo $i; done
