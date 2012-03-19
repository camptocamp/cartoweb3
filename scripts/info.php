<?php
/**
 * @package Htdocs
 * @version $Id$
 */

if (!extension_loaded('mapscript') && 
    !dl('php_mapscript.' . PHP_SHLIB_SUFFIX)) {
    print("WARNING: can't load mapscript library");
}

phpinfo();

