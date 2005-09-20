#!/usr/local/bin/php
<?php
/**
 * clean.php - cleans old files: images, map results, PDF and SOAP XML cache
 *
 * Cleans:
 * - Images not associated with map result
 * - Images associated with map result / SOAP XML, including corresponding
 *   map result / SOAP XML
 * - Exported PDF files as well as temporary images used to generate them
 * - Empty map results /SOAP XML
 *
 * Usage:
 * ./clean.php <cache_image_max_age> [<simple_image_max_age>]
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * Common home dir
 */
define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once(CARTOWEB_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array());

require_once(CARTOWEB_HOME . 'common/CwSerializable.php');
require_once(CARTOWEB_HOME . 'common/Request.php');
require_once(CARTOWEB_HOME . 'coreplugins/images/common/Images.php');


if ($_SERVER['argc'] < 2 || $_SERVER['argc'] > 3) {
    usage();
} else {
    $ageCache = $_SERVER['argv'][1];
    $ageSimple = 10;
    if ($_SERVER['argc'] == 3) {
        $ageSimple = $_SERVER['argv'][2];
    }
}
    
// Deletes old generated PDF files
$pdfCachedir = CARTOWEB_HOME . 'htdocs/generated/pdf';
deleteOldFiles($pdfCachedir, true);

// Deletes old cache PDF-related images
$pdfImagesdir = CARTOWEB_HOME . 'www-data/pdf_cache';
deleteOldFiles($pdfImagesdir, true);

// Deletes old and empty map results
$resultCachedir = CARTOWEB_HOME . 'www-data/mapresult_cache';
deleteOldFiles($resultCachedir);

// Deletes old and empty SOAP XMLs
$soapCachedir = CARTOWEB_HOME . 'www-data/soapxml_cache';
deleteOldFiles($soapCachedir);

$results = loadMapResults($resultCachedir);
$soapXMLs = loadSoapXMLs($soapCachedir);

$imageCachedir = CARTOWEB_HOME . 'htdocs/generated/images';
$imageFiles = scandir($imageCachedir);

foreach ($imageFiles as $filename) {
    $file = $imageCachedir . '/' . $filename;
    if (!is_dir($file)) {
        $isResultCached = array_key_exists($filename, $results);
        $isSoapCached = array_key_exists($filename, $soapXMLs);
        $isCached = $isResultCached || $isSoapCached;
    
        if (($isCached && (mktime() - fileatime($file)) > ($ageCache * 60))
            || (!$isCached && (mktime() - fileatime($file)) > ($ageSimple * 60))) {
        
            // image is old, delete it
            unlink($file);
            if ($isResultCached
                && file_exists($resultCachedir . '/' . $results[$filename])) {
            
                // a result was associated, delete it
                unlink($resultCachedir . '/' . $results[$filename]);
            }
            if ($isSoapCached
                && file_exists($soapCachedir . '/' . $soapXMLs[$filename])) {
            
                // a SOAP XML was associated, delete it
                unlink($soapCachedir . '/' . $soapXMLs[$filename]);
            }
        }
    }
}

/**
 * Gets all links between images and MapResults
 * @param string
 * @return array
 */
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
                            $name = substr($image->path, $pos + 1, 
                                           strlen($image->path) - $pos - 1);                           
                        }
                        
                        // Remembers for each image in which result it was 
                        // found.
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

/**
 * Gets all links between images and SOAP XMLs
 * @param string
 * @return array
 */
function loadSoapXMLs($soapCachedir) {

    $soapFiles = scandir($soapCachedir);
    $soapXMLs = array();

    foreach ($soapFiles as $filename) {
        $file = $soapCachedir . '/' . $filename;
        if (!is_dir($file) 
            && filesize($file) > 0) {
            
            // let's find out about referenced images
            $soapXML = file_get_contents($file);
            if ($soapXML) {
                preg_match_all('|images/([0-9]+\.\w+)</path>|', $soapXML, $matches);
                
                foreach($matches[1] as $match) {
                    if ($match) {
                        $soapXMLs[$match] = $filename;
                    }
                }
            }  
        }
    }    
    return $soapXMLs;
}

/**
 * Deletes files older than cache age from given directory.
 * @param string path of directory to clean
 * @param bool if true, all old files are deleted, else (default) only empty ones.
 */
function deleteOldFiles($cachedir, $deleteAll = false) {
    global $ageCache;
    $files = scandir($cachedir);

    foreach ($files as $filename) {
        $file = $cachedir . '/' . $filename;
        $emptyCondition = $deleteAll || (filesize($file) == 0);
        if (!is_dir($file) && $emptyCondition &&
            (mktime() - fileatime($file)) > ($ageCache * 60)) {
            unlink($file);
        }
    }
}

/**
 * Prints usage
 */
function usage() {
    print "Usage: ./clean.php <cache_file_max_age> [<simple_file_max_age>]\n";
    print "       (ages in minutes)\n";
    exit(1);    
}

?>
