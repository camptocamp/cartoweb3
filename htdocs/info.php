<?php
/**
 * @package Htdocs
 * @version $Id$
 */

// WARNING: this code should be in sync with ServerContext.php::getMapObj()
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
