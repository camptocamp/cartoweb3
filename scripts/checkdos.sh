#!/bin/sh
for i in $(find .. -type f ! -path "../locale/*" ! -path "../include/*" ! -path "../www-data/*" ! -name "*png" ! -name "*gif" ! -name "*jpg" \
    ! -name "*ttf" ! -name "*dbf" ! -name "*shp" ! -name "*jar" ! -name "*class" \
    ! -name "*gz" ! -name "*bat" ! -name "*shx" ! -name "*tif" ! -name "*ico" ); do od -c  $i |grep -q '\\r' && echo $i; done
