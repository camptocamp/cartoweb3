#!/usr/local/bin/php
<?php
/**
 * server2pot.php - generates PO templates, merge with old PO files
 *
 * Uses .ini (for layer labels) and .map (for class names) 
 *
 * Usage:
 * ./server2pot.php
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

/**
 * Home dirs
 */
define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOCOMMON_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOSERVER_PODIR', CARTOSERVER_HOME . 'po/');

/**
 * Encoding class for charset
 */
require_once(CARTOCOMMON_HOME . 'common/Encoding.php');

/**
 * Finds charset in client.ini
 * @param string
 * @return string
 */
function getCharset($project) {
    
    $class = null;
    $iniFile = CARTOSERVER_HOME;
    $projectIniFile = CARTOSERVER_HOME;
    if ($project != '') {
        $projectIniFile .= 'projects/' . $project. '/';
    }
    $iniFile .= 'server_conf/server.ini';
    $projectIniFile .= 'server_conf/server.ini';
    if (file_exists($projectIniFile)) {
        $iniArray = parse_ini_file($projectIniFile);
        if (array_key_exists('EncoderClass.config', $iniArray)) {
            $class = $iniArray['EncoderClass.config'];
        }
    }
    if (is_null($class) && $iniFile != $projectIniFile
                          && file_exists($iniFile)) {
        $iniArray = parse_ini_file($iniFile);
        if (array_key_exists('EncoderClass.config', $iniArray)) {
            $class = $iniArray['EncoderClass.config'];
        }
    }
    if (is_null($class)) {
        $class = 'EncoderUTF';
    }
    $obj = new $class;
    return $obj->getCharset();
}

/**
 * Parses an INI file looking for variable ending with '.label'
 * @param string
 * @param string
 * @param array map text_to_translate => references
 * @return boolean
 */
function parseIni($project, $mapId, &$texts) {

    $iniPath = CARTOSERVER_HOME;
    if ($project != '') {
        $iniPath .= 'projects/' . $project. '/';
    }
    $iniPath .= 'server_conf/' . $mapId . '/';
    
    if (!is_dir($iniPath)) {
        return true;
    }
    $d = dir($iniPath);
    while (false !== ($entry = $d->read())) {
        if (!is_dir($entry) && substr($entry, -4) == '.ini') {
            $iniFile = $iniPath . $entry;
            $iniArray = parse_ini_file($iniFile);
            foreach($iniArray as $key => $value) {
                if (substr($key, -6) == '.label') {
                    $info = $entry . ':' . $key;
                    if (array_key_exists($value, $texts)) {
                        $texts[$value] .= ',' . $info;
                    } else {
                        $texts[$value] = $info;
                    }
                }
            }
        }
    }
    return true;
}

/**
 * Adds a reference
 * @param string
 * @param string
 * @param array map text_to_translate => references
 */
function addMapText($text, $mapId, &$texts) {
    $info = $mapId . '.map';
    if (array_key_exists($text, $texts)) {
        $texts[$text] .= ',' . $info;
    } else {
        $texts[$text] = $info;
    }
}

/**
 * Parses a MAP file looking for class names and 'query_returned_attributes'
 * metadata
 * @param string
 * @param string
 * @param array map text_to_translate => references
 * @return boolean
 */
function parseMap($project, $mapId, &$texts) {

    $mapFile = CARTOSERVER_HOME;
    if ($project != '') {
        $mapFile .= 'projects/' . $project. '/';
    }
    $mapFile .= 'server_conf/' . $mapId . '/' . $mapId . '.map';
    
    if (!extension_loaded('mapscript')) {
        $prefix = (PHP_SHLIB_SUFFIX == 'dll') ? '' : 'php_';
        if (!dl($prefix . 'mapscript.' . PHP_SHLIB_SUFFIX))
            print 'Error: Cannot load Mapscript library.';
            return false;
    }
    
    if (!file_exists($mapFile)) {
        print "\nWarning: Map file $mapFile not found.\n";
        return false;
    }
    $map = ms_newMapObj($mapFile);
    for ($i = 0; $i < $map->numlayers; $i++) {
        $layer = $map->getLayer($i);
        
        for ($j = 0; $j < $layer->numclasses; $j++) {
            $class = $layer->getClass($j);
            
            if ($class->name != '') {
                addMapText($class->name, $mapId, $texts);
            }
        }     
        
        $returnedAttr = $layer->getMetaData('query_returned_attributes');
        if (!empty($returnedAttr)) {
            $attrArray = explode(' ', $returnedAttr);
            foreach ($attrArray as $attr) {
                addMapText($attr, $mapId, $texts);
            }
        }
    }
    return true;
}

/**
 * Gets list of projects by reading projects directory
 * @return array
 */
function getProjects() {

    $projects = array();
    $dir = CARTOSERVER_HOME . 'projects/';
    $d = dir($dir);
    while (false !== ($entry = $d->read())) {
        if (is_dir($dir . $entry) && $entry != '.'
            && $entry != '..' && $entry != 'CVS') {
            $projects[] = $entry;
        }
    }    
    return $projects;
}

/**
 * Gets list of map Ids by reading project directory
 * @param string
 * @return array
 */
function getMapIds($project) {
    
    $mapIds = array();
    $dir = CARTOSERVER_HOME;
    if ($project != '') {
        $dir .= 'projects/' . $project . '/';
    }
    $dir .= 'server_conf/';
    if (is_dir($dir)) {
        $d = dir($dir);
        while (false !== ($entry = $d->read())) {
            if (is_dir($dir . $entry) && $entry != '.'
                && $entry != '..' && $entry != 'CVS') {
                $mapIds[] = $entry;
            }
        }
    }    
    return $mapIds;    
}

/**
 * Finds list of already translated PO files for a project
 * @param string
 * @return array
 */
function getTranslatedPo($project, $mapId) {
    
    $files = array();
    $d = dir(CARTOSERVER_PODIR);

    $pattern = "server\\-";
    if ($project != '') {
        $pattern .= "$project\\.";
    }
    $pattern .= "$mapId\\.(.*)\\.po";          
 
    while (false !== ($entry = $d->read())) {
        if (!is_dir(CARTOSERVER_PODIR . $entry)) {
            if (ereg($pattern, $entry)) {
            
                $files[] = $entry;
            };
        }
    }    
 
    return $files;   
}

$projects = getProjects();
// Adds default project
$projects[] = '';

foreach ($projects as $project) {

    $mapIds = getMapIds($project);
    
    foreach ($mapIds as $mapId) {
    
        $texts = array();
        $fileName = 'server-';
        if ($project != '') {
            $fileName .= $project . '.';
        }
        $fileName .= $mapId . '.po';
        $file = CARTOSERVER_PODIR . $fileName;

        print "Creating new template $fileName ";
        
        parseIni($project, $mapId, $texts);
        if (!parseMap($project, $mapId, $texts)) continue;

        $fh = fopen($file, 'w');
    
        // POT header
        fwrite($fh, '# CartoWeb 3 translation template ' . "\n");
        fwrite($fh, '#' . "\n");
        fwrite($fh, '#, fuzzy' . "\n");
        fwrite($fh, 'msgid ""' . "\n");
        fwrite($fh, 'msgstr ""' . "\n");
        fwrite($fh, '"POT-Creation-Date: ' . date('Y-m-d H:iO') . '\n"' . "\n");
        fwrite($fh, '"MIME-Version: 1.0\n"' . "\n");
        fwrite($fh, '"Content-Type: text/plain; charset=' . 
                                        getCharset($project) . '\n"' . "\n");
        fwrite($fh, '"Content-Transfer-Encoding: 8bit\n"' . "\n");

        foreach ($texts as $text => $files) {
            fwrite($fh, "\n");
            foreach (explode(',', $files) as $file) {
                fwrite($fh, "#: $file\n");
            }
            fwrite($fh, 'msgid "' . $text . '"' . "\n");
            fwrite($fh, 'msgstr ""' . "\n");
        }
    
        fclose($fh);    
        
        print ".. done.\n";
        
        $poFiles = getTranslatedPo($project, $mapId);

        foreach ($poFiles as $poFile) {
        
            print "Merging new template into $poFile ";
            exec("msgmerge -o " . CARTOSERVER_PODIR . "$poFile "
                 . CARTOSERVER_PODIR . "$poFile " . CARTOSERVER_PODIR . "$fileName");
        }
    }
}

?>
