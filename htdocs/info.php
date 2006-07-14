<?php
/**
 * @package Htdocs
 * @version $Id$
 */

// Safety check for Mapserver bug 1322:
//   http://mapserver.gis.umn.edu/bugs/show_bug.cgi?id=1322
// WARNING: this code should be in sync between:
//  server/ServerContext.php, htdocs/info.php and scripts/info.php
if (!extension_loaded('mapscript')) {
    if (!dl('php_mapscript.' . PHP_SHLIB_SUFFIX))
        print("WARNING: can't load mapscript library");
} else if (!in_array(substr(php_sapi_name(), 0, 3), array('cgi', 'cli'))) {
    print("WARNING: You are not using PHP as " .
        "a cgi and PHP Mapscript extension is loaded in your " .
        "php.ini.\n This will cause stability problems.\n" .
        "You need to remove the " .
        "php_mapscript extension loading of your php.ini " .
        "file.");
}
phpinfo();

?>
