#!/usr/local/bin/php
<?php
/**
 * map2pot.php - generates PO template for a map
 *
 * Uses .ini (for layer labels) and .map (for class names) 
 *
 * Usage:
 * ./map2pot.php [<project_name>] <map_id>
 *
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');

// arrays with all translations found
$texts = array();

function parseIni($project, $mapId, $file) {
    global $texts;

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
}

function parseMapIni($project, $mapId) {
    parseIni($project, $mapId, $mapId);
}

function parseLocationIni($project, $mapId) {
    parseIni($project, $mapId, 'location');
}

function parseMap($project, $mapId) {
    global $texts;

    $mapFile = CARTOSERVER_HOME;
    if ($project != '') {
        $mapFile .= 'projects/' . $project. '/';
    }
    $mapFile .= 'server_conf/' . $mapId . '/' . $mapId . '.map';
    
    if (!extension_loaded('mapscript')) {
        $prefix = (PHP_SHLIB_SUFFIX == 'dll') ? '' : 'php_';
        if (!dl($prefix . 'mapscript.' . PHP_SHLIB_SUFFIX))
            print 'Cannot load Mapscript library.';
            return;
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
}

if ($_SERVER['argc'] > 1) {
    
    $projectName = '';
    if ($_SERVER['argc'] == 2) {
        $mapId = $_SERVER['argv'][1];
    } else {
        $projectName = $_SERVER['argv'][1];
        $mapId = $_SERVER['argv'][2];
    } 
        
    $fileName = $mapId . '.po';

    $fh = fopen($fileName, 'w');
    
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

    parseMapIni($projectName, $mapId);
    parseLocationIni($projectName, $mapId);
    parseMap($projectName, $mapId);

    foreach ($texts as $text => $files) {
        fwrite($fh, "\n");
        foreach (explode(',', $files) as $file) {
            fwrite($fh, "#: $file\n");
        }
        fwrite($fh, 'msgid "' . $text . '"' . "\n");
        fwrite($fh, 'msgstr ""' . "\n");
    }
    
    fclose($fh);
    
} else {
    print "Usage: ./map2pot.php [<project_name>] <map_id>\n";    
}

?>
