#!/bin/sh

#
# This scripts creates the tarball with librairies for cartoweb3
# 
# You need to adjust the url the libraries if you need a newer version

PEAR_PACKAGES="PHPUnit2 Benchmark Console_Getopt PhpDocumentor"
LOG4PHP="http://www.vxr.it/log4php/log4php-0.9.tar.gz"
SMARTY="http://smarty.php.net/do_download.php?download_file=Smarty-2.6.5.tar.gz"

# uncomment to upload with scp to this address
#UPLOAD_HOST="malmurainza.c2c:public_html/cartoweb3/"
TARBALL="cartoweb3_includes.tgz"

[ -d include ] && rm -rf include
mkdir -p include
cd include

## pear packages

mkdir -p pear
for i in $PEAR_PACKAGES; do 
    echo "fetching pear package: $i"
    pear download $i
    tar -C pear -zxf $i*gz
    mv pear/$i* pear/$i
    rm $i*gz
done

## log4php

wget -O- $LOG4PHP|tar zxf -
mv log4php*/src/log4php .
rm -r log4php?*

## smarty

wget -O- $SMARTY|tar zxf -
mv Smarty-*/libs smarty
rm -r Smarty-*

## create tarball

cd ..

tar zcf $TARBALL include
rm -r include

if [ -n "$UPLOAD_HOST" ] ; then
    scp $TARBALL $UPLOAD_HOST
    #rm $TARBALL
fi
