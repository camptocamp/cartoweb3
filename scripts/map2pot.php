#!/usr/local/bin/php
<?php
/**
 * map2pot.php - generates PO templates, merge with old PO files
 *
 * Uses .ini (for layer labels) and .map (for class names) 
 *
 * Usage:
 * ./map2pot.php
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

/**
 * Cartoclient home dir
 */
define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOSERVER_PODIR', CARTOSERVER_HOME . 'po/');

function parseIni($project, $mapId, $file, &$texts) {

    $iniFile = CARTOSERVER_HOME;
    if ($project != '') {
        $iniFile .= 'projects/' . $project. '/';
    }
    $iniFile .= 'server_conf/' . $mapId . '/' . $file . '.ini';
    
    if (file_exists($iniFile)) {
        $iniArray = parse_ini_file($iniFile);
        foreach($iniArray as $key => $value) {
            if (substr($key, -6) == '.label') {
                $info = $file . '.ini:' . $key;
                if (array_key_exists($value, $texts)) {
                    $texts[$value] .= ',' . $info;
                } else {
                    $texts[$value] = $info;
                }
            }
        }
    }
    return true;
}

function parseMapIni($project, $mapId, &$texts) {
    return parseIni($project, $mapId, $mapId, $texts);
}

function parseLocationIni($project, $mapId, &$texts) {
    return parseIni($project, $mapId, 'location', $texts);
}

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
                $info = $mapId . '.map';
                if (array_key_exists($class->name, $texts)) {
                    $texts[$class->name] .= ',' . $info;
                } else {
                    $texts[$class->name] = $info;
                }
            }
        }       
    }
    return true;
}

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

function getTranslatedPo($project, $mapId) {
    
    $files = array();
    $d = dir(CARTOSERVER_PODIR);

    $pattern = "map\\-";
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
        $fileName = 'map-';
        if ($project != '') {
            $fileName .= $project . '.';
        }
        $fileName .= $mapId . '.po';
        $file = CARTOSERVER_PODIR . $fileName;

        print "Creating new template $fileName ";
        
        if (!parseMapIni($project, $mapId, $texts)) continue;
        if (!parseLocationIni($project, $mapId, $texts)) continue;
        if (!parseMap($project, $mapId, $texts)) continue;

        $fh = fopen($file, 'w');
    
        // POT header
        fwrite($fh, '# CartoWeb 3 Map ' . "\n");
        fwrite($fh, '#' . "\n");
        fwrite($fh, '#, fuzzy' . "\n");
        fwrite($fh, 'msgid ""' . "\n");
        fwrite($fh, 'msgstr ""' . "\n");
        fwrite($fh, '"POT-Creation-Date: ' . date('Y-m-d H:iO') . '\n"' . "\n");
        fwrite($fh, '"MIME-Version: 1.0\n"' . "\n");
        fwrite($fh, '"Content-Type: text/plain; charset=ISO-8859-1\n"' . "\n");
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
