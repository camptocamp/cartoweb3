#!/usr/local/bin/php
<?php
/**
 * cleanresult.php - cleans empty and old MapResult cache files
 *
 * Usage:
 * ./cleanresult.php <age_in_hours>
 *
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');


if ($_SERVER['argc'] == 2) {
    
    $age = $_SERVER['argv'][1];

    $cachedir = CARTOSERVER_HOME . 'www-data/mapresult_cache';
    $files = scandir($cachedir);
    
    foreach ($files as $filename) {
        $file = $cachedir . '/' . $filename;
        if (!is_dir($file)
            && filesize($file) == 0
            && (mktime() - fileatime($file)) > ($age * 3600)) {
            unlink($file);
        }
    }

} else {
    print "Usage: ./cleanresult.php <age_in_hours>\n";    
}

?>
