#!/usr/local/bin/php
<?php
/**
 * cleanimage.php - cleans old images and old map results cache files
 *
 * Cleans:
 * - Images not associated with map result
 * - Images associated with map result, including corresponding map result
 * - Empty map results
 *
 * Usage:
 * ./clean.php <cache_image_max_age> [<simple_image_max_age>]
 *
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

define('CARTOCOMMON_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once(CARTOCOMMON_HOME . 'common/Serializable.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOSERVER_HOME . 'coreplugins/images/common/Images.php');


if ($_SERVER['argc'] < 2 || $_SERVER['argc'] > 3) {
    usage();
} else {
    $ageCache = $_SERVER['argv'][1];
    $ageSimple = 10;
    if ($_SERVER['argc'] == 3) {
        $ageSimple = $_SERVER['argv'][2];
    }
}
    
// Deletes old and empty map results
$resultCachedir = CARTOSERVER_HOME . 'www-data/mapresult_cache';
$resultFiles = scandir($resultCachedir);

foreach ($resultFiles as $filename) {
    $file = $resultCachedir . '/' . $filename;
    if (!is_dir($file)
        && filesize($file) == 0
        && (mktime() - fileatime($file)) > ($ageCache * 60)) {
        unlink($file);
    }
}

$results = loadMapResults($resultCachedir);

$imageCachedir = CARTOSERVER_HOME . 'www-data/images';
$imageFiles = scandir($imageCachedir);

foreach ($imageFiles as $filename) {
    $file = $imageCachedir . '/' . $filename;
    if (!is_dir($file)) {
        $isCached = array_key_exists($filename, $results);
    
        if (($isCached && (mktime() - fileatime($file)) > ($ageCache * 60))
            || (!$isCached && (mktime() - fileatime($file)) > ($ageSimple * 60))) {
        
            // image is old, delete it
            unlink($file);
            if ($isCached
                && file_exists($resultCachedir . '/' . $results[$filename])) {
            
                // a result was associated, delete it
                unlink($resultCachedir . '/' . $results[$filename]);
            }
        }
    }
}


function loadMapResults($resultCachedir) {

    $resultFiles = scandir($resultCachedir);
    $results = array();

    foreach ($resultFiles as $filename) {
        $file = $resultCachedir . '/' . $filename;
        if (!is_dir($file) 
            && filesize($file) > 0) {
            
            // let's find out about referenced images
            $mapResultSerialized = file_get_contents($file);
            if ($mapResultSerialized) {
                $mapResult = unserialize($mapResultSerialized);

                foreach ($mapResult->imagesResult as $image) {
                    if ($image instanceof Image) {
                        $pos = strrpos($image->path, '/');
                        if ($pos === FALSE) {
                            $name = $image->path;
                        } else {
                            $name = substr($image->path, $pos + 1, strlen($image->path) - $pos - 1);                              
                        }
                        
                        // remembers for each image in which result it was found
                        if ($name) {
                            $results[$name] = $filename;
                        }
                    }
                }
            }  
        }
    }    
    return $results;
}

function usage() {
    print "Usage: ./clean.php <cache_image_max_age> [<simple_image_max_age>]\n";
    print "       (ages in minutes)\n";
    exit(1);    
}

?>
