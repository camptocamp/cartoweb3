#!/bin/sh

#
# This scripts creates the tarball with librairies for cartoweb3
# 
# You need to adjust the url the libraries if you need a newer version

PEAR_PACKAGES="PHPUnit2 Benchmark PhpDocumentor PEAR Archive_Tar XML_RPC Console_Getopt"

LOG4PHP="http://www.vxr.it/log4php/log4php-0.9.tar.gz"
SMARTY="http://smarty.php.net/do_download.php?download_file=Smarty-2.6.6.tar.gz"

# uncomment to upload with scp to this address
#UPLOAD_HOST="malmurainza.c2c:public_html/cartoweb3/"
TARBALL="cartoweb3_includes.tgz"

prepare()
{
    [ -d include ] && rm -rf include
    mkdir -p include
    cd include
}

fetch_pear()
{
    PEAR_DIRECTORY=pear_base
    PEAR_PHP_DIR=pear

    # Warning: please remove preferred_state=devel when phpDocumentor is php 5 compatible

    pear -s -c $PEAR_DIRECTORY/.pearrc -d preferred_state=devel -d doc_dir=$PEAR_DIRECTORY/docs -d ext_dir=$PEAR_DIRECTORY/ext \
           -d php_dir=$PEAR_PHP_DIR -d data_dir=$PEAR_DIRECTORY/data -d test_dir=$PEAR_DIRECTORY/tests \
           -d cache_dir=$PEAR_DIRECTORY/cache -d bin_dir=$PEAR_DIRECTORY/bin 
          
    pear -c $PEAR_DIRECTORY/.pearrc install $PEAR_PACKAGES
}

fetch_contrib()
{
    ## log4php

    wget -O- $LOG4PHP|tar zxf -
    mv log4php*/src/log4php .
    rm -r log4php?*

    ## smarty

    wget -O- $SMARTY|tar zxf -
    mv Smarty-*/libs smarty
    rm -r Smarty-*
}

create_tarball()
{
    cd ..

    [ -f patch_include ] && (cd include; patch -p1 < ../patch_include)

    cp -rl include_addons include_addons_tmp
    find include_addons_tmp -name CVS | xargs --no-run-if-empty rm -r
    (cd include_addons_tmp; \cp -rf --parents include/ .. )
    rm -rf include_addons_tmp

    tar zcf $TARBALL include
    rm -r include
}

upload()
{
   if [ -n "$UPLOAD_HOST" ] ; then
       scp $TARBALL $UPLOAD_HOST
   fi 
}

# main
prepare
fetch_pear
fetch_contrib
create_tarball
upload
