#!/usr/local/bin/php -qn
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

function parseIni($project, $mapId) {
    global $texts;

    $iniFile = CARTOSERVER_HOME;
    if ($project != '') {
        $iniFile .= 'projects/' . $project. '/';
    }
    $iniFile .= 'server_conf/' . $mapId . '/' . $mapId . '.ini';
    
    $iniArray = parse_ini_file($iniFile);
    foreach($iniArray as $key => $value) {
        if (substr($key, -6) == '.label') {
            $info = $mapId . '.ini:' . $key;
            if (array_key_exists($value, $texts)) {
                $texts[$value] .= ',' . $info;
            } else {
                $texts[$value] = $info;
            }
        }
    }
}

function parseMap($project, $mapId) {
    global $texts;

    // TODO
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

    parseIni($projectName, $mapId);
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
