#!/usr/local/bin/php -qn
<?php
/**
 * po2mo.php - compile all PO files for a mapId
 *
 * Usage:
 * ./po2mo.php [<project_name>] <map_id>
 *
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once(CARTOCLIENT_HOME . 'client/Internationalization.php');

function compileProject ($project = I18n::DEFAULT_PROJECT_DOMAIN) {
    $input = $project . '.po';
    $output = $project . '.mo';
    compile($input, $output);
}

function compileMapId($project, $mapId) {

    // Looks for server URL
    $iniFile = CARTOCLIENT_HOME;
    if ($project != I18n::DEFAULT_PROJECT_DOMAIN) {
        $iniFile .= 'projects/' . $project . '/';
    }
    $iniFile .= 'client_conf/client.ini';
    $iniArray = parse_ini_file($iniFile);
    if (array_key_exists('cartoserverUrl', $iniArray)) {
        $url = $iniArray['cartoserverUrl'];
    }
    $url = dirname($url) . '/';
    
    // Adds project if needed
    if ($project != I18n::DEFAULT_PROJECT_DOMAIN) {
        $url .= $project . '/';
    }
    $url .= 'locale/';

    // Finds which locales should be fetched
    $dir = CARTOCLIENT_HOME . 'locale/';
    $d = dir($dir);
    $locales = array();
    while (false !== ($entry = $d->read())) {
        if ($entry == '.' || $entry == '..') {
            continue;
        }
        $locales[] = $entry;
    }
    
    foreach ($locales as $locale) {
        $urlLocale = $url . $locale . '/LC_MESSAGES/' . $mapId . '.po';

        // CURL init
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlLocale);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        
        // Gets server PO file
        $input = $project . '.' . $mapId . '.po';
        $file = CARTOCLIENT_HOME . 'locale/' . $locale . '/LC_MESSAGES/' . $input ;
        $fh = fopen($file, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_exec($ch);
        curl_close($ch);  
        fclose($fh);
        
        // Compiles it
        $output = $project . '.' . $mapId . '.mo';
        compile($input, $output);      
    }
}

function compile($input, $output) {
    
    $dir = CARTOCLIENT_HOME . 'locale/';
    
    $d = dir($dir);
    
    while (false !== ($entry = $d->read())) {
        if ($entry == '.' || $entry == '..') {
            continue;
        }
        $file = $dir . $entry . '/LC_MESSAGES/' . $input;
        if (file_exists($file)) {
            exec ('msgfmt -o ' . $dir . $entry . '/LC_MESSAGES/' . $output . ' ' . $file);       
        }
    }
    
    $d->close();
}

if ($_SERVER['argc'] > 1) {
    
    $projectName = I18n::DEFAULT_PROJECT_DOMAIN;
    if ($_SERVER['argc'] == 2) {
        $mapId = $_SERVER['argv'][1];
    } else {
        $projectName = $_SERVER['argv'][1];
        $mapId = $_SERVER['argv'][2];
    }
    
    compileProject($projectName);
    compileMapId($projectName, $mapId);

} else {
    print "Usage: ./po2mo.php [<project_name>] <map_id>\n";    
}

?>
