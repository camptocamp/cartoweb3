#!/usr/local/bin/php
<?php
/**
 * cleanimage.php - cleans empty and old image cache files
 *
 * Also removes MapResult cache files linked to the removed image
 *
 * Usage:
 * ./cleanimage.php <age_in_hours>
 *
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

define('CARTOCOMMON_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once(CARTOCOMMON_HOME . 'common/Serializable.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOSERVER_HOME . 'coreplugins/images/common/Images.php');


if ($_SERVER['argc'] == 2) {
    
    $age = $_SERVER['argv'][1];  

    $imageCachedir = CARTOSERVER_HOME . 'www-data/images';
    $imageFiles = scandir($imageCachedir);
    $imageToDelete = array();
    
    foreach ($imageFiles as $filename) {
        $file = $imageCachedir . '/' . $filename;
        if (!is_dir($file)
            && (mktime() - fileatime($file)) > ($age * 3600)) {
            
            // image is old
            $imageToDelete[] = $filename;
        }
    }

    if (count($imageToDelete) > 0) {
        $resultCachedir =  CARTOSERVER_HOME . 'www-data/mapresult_cache';
        $resultFiles = scandir($resultCachedir);
        $resultToDelete = array();
    
        foreach ($resultFiles as $filename) {
            $file = $resultCachedir . '/' . $filename;
            if (!is_dir($file) 
                && filesize($file) > 0) {
                
                // let's find out if result references old images
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
                            if (in_array($name, $imageToDelete)) {
                                
                                // the image is one of the images we want to delete
                                $resultToDelete[] = $filename;
                                break;
                            }
                        }
                    }
                }  
            }
        }    
        
        // OK, now let's delete
        foreach ($resultToDelete as $filename) {
            print "MapResult: $filename\n";
            unlink($resultCachedir . '/' . $filename);
        }
        foreach ($imageToDelete as $filename) {
            print "Image: $filename\n";
            unlink($imageCachedir . '/' . $filename);
        }
    }
} else {
    print "Usage: ./cleanimage.php <age_in_hours>\n";    
}

?>
