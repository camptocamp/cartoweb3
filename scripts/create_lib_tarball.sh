#!/bin/sh

# This script creates the tarball with librairies for CartoWeb3.
# Usage: sh create_lib_tarball.sh
# $Id: $
# 
# Adjust the URLs of the libraries if you need newer versions.

set -e

PEAR_PACKAGES_STABLE="Benchmark PHPUnit2 PEAR DB Archive_Tar XML_RPC Console_Getopt Auth HTML_Crypt"
PEAR_PACKAGES_DEVEL="PhpDocumentor"

# Dependencies:
#
#Benchmark
#
#PHPUnit2
#    Console_Getopt
#    PEAR
#
#PhpDocumentor 
#    Archive_Tar
#PEAR
#    Archive_Tar
#    XML_RPC
#DB
#    PEAR
#Archive_Tar
#XML_RPC
#Console_Getopt
#Auth
#HTML_Crypt


LOG4PHP="http://www.vxr.it/log4php/log4php-0.9.tar.gz"
SMARTY="http://smarty.php.net/do_download.php?download_file=Smarty-2.6.14.tar.gz"
FPDF="http://www.fpdf.org/fr/dl.php?v=153&f=tgz"
THEMAMAP="http://cartoweb.org/downloads/themamap/themamap-0.4.tar.gz" 

# uncomment to upload with scp to this address
#UPLOAD_HOST="malmurainza.c2c:public_html/cartoweb3/"
TARBALL="cartoweb3_includes.tgz"

prepare()
{
    [ -d include ] && rm -rf include
    mkdir -p include
}

fetch_pear()
{
    PEAR_DIRECTORY=pear_base
    PEAR_PHP_DIR=pear

    # Warning: please remove preferred_state=devel when phpDocumentor is php 5 compatible

    pear -s -c $PEAR_DIRECTORY/.pearrc -d doc_dir=$PEAR_DIRECTORY/docs -d ext_dir=$PEAR_DIRECTORY/ext \
           -d php_dir=$PEAR_PHP_DIR -d data_dir=$PEAR_DIRECTORY/data -d test_dir=$PEAR_DIRECTORY/tests \
           -d cache_dir=$PEAR_DIRECTORY/cache -d bin_dir=$PEAR_DIRECTORY/bin 
          
    ## BUG: if executed all at once without the --nodeps flag, pear will get into an infinite recursion, and make php segfault !
    for i in $PEAR_PACKAGES_STABLE; do 
        pear -c $PEAR_DIRECTORY/.pearrc install --nodeps $i
    done

    # ... use this instead if pear is fixed
    #pear -c $PEAR_DIRECTORY/.pearrc install --nodeps $PEAR_PACKAGES

    pear -s -c $PEAR_DIRECTORY/.pearrc -d preferred_state=devel -d doc_dir=$PEAR_DIRECTORY/docs -d ext_dir=$PEAR_DIRECTORY/ext \
           -d php_dir=$PEAR_PHP_DIR -d data_dir=$PEAR_DIRECTORY/data -d test_dir=$PEAR_DIRECTORY/tests \
           -d cache_dir=$PEAR_DIRECTORY/cache -d bin_dir=$PEAR_DIRECTORY/bin 

    for i in $PEAR_PACKAGES_DEVEL; do 
        pear -c $PEAR_DIRECTORY/.pearrc install --nodeps $i
    done

    
}

fetch_contrib()
{
    ## log4php

    wget -O- "$LOG4PHP"|tar zxf -
    mv log4php*/src/log4php .
    mv log4php*/LICENSE log4php
    rm -r log4php?*

    ## smarty

    wget -O- "$SMARTY"|tar zxf -
    mv Smarty-*/libs smarty
    mv Smarty-*/COPYING.lib smarty
    rm -r Smarty-*

    ## fpdf

    wget -O- "$FPDF"|tar zxf -
    mv fpdf* fpdf

    ## themamap

    wget -O- "$THEMAMAP"|tar zxf -
    mv themamap-* themamap
}

create_tarball()
{
# How to rebuild the patch (patch_include):
# cp -r include include_orig
# > Modify include
# diff -dup8r include_orig include > patch_include

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
(cd include && fetch_pear)
(cd include && fetch_contrib)
create_tarball
#upload
