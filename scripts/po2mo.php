#!/usr/local/bin/php
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

function getProjectPo ($project = I18n::DEFAULT_PROJECT_DOMAIN) {
    return $project;
}

function getMapPo ($project, $mapId) {

    $direct = false;
    $iniFile = CARTOCLIENT_HOME;
    if ($project != I18n::DEFAULT_PROJECT_DOMAIN) {
        $iniFile .= 'projects/' . $project . '/';
    }
    $iniFile .= 'client_conf/client.ini';
    $iniArray = parse_ini_file($iniFile);
    if (array_key_exists('cartoserverDirectAccess', $iniArray)) {
        $direct = $iniArray['cartoserverDirectAccess'];
    }

    $locales = I18n::getLocales();

    $input = $project . '.' . $mapId;
    if ($direct) {
 
        foreach ($locales as $locale) {
            $file = CARTOCLIENT_HOME . 'locale/' . $locale .
                                            '/LC_MESSAGES/' . $input . '.po';
            $serverFile = CARTOCLIENT_HOME;
            if ($project != I18n::DEFAULT_PROJECT_DOMAIN) {
                $serverFile .= 'projects/' . $project . '/';
            }
            $serverFile .= 'htdocs/locale/' . $locale .
                                     '/LC_MESSAGES/' . $mapId . '.po' ;
            copy ($serverFile, $file);
        }
    } else {
        // Looks for server URL
        if (array_key_exists('cartoserverUrl', $iniArray)) {
            $url = $iniArray['cartoserverUrl'];
        }
        $url = dirname($url) . '/';
 
        // Adds project if needed
        if ($project != I18n::DEFAULT_PROJECT_DOMAIN) {
            $url .= $project . '/';
        }
        $url .= 'locale/';
   
        foreach ($locales as $locale) {
            $urlLocale = $url . $locale . '/LC_MESSAGES/' . $mapId . '.po';

            // CURL init
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlLocale);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            
            // Gets server PO file
            $file = CARTOCLIENT_HOME . 'locale/' . $locale .
                                        '/LC_MESSAGES/' . $input . '.po';
            $fh = fopen($file, 'w');
            curl_setopt($ch, CURLOPT_FILE, $fh);
            curl_exec($ch);
            curl_close($ch);  
            fclose($fh);
        }
    }
    
    return $input;
}

function merge($source, $dest) {

    $dir = CARTOCLIENT_HOME . 'locale/';
    
    $locales = I18n::getLocales();

    foreach ($locales as $locale) {
        $fileSource = $dir . $locale . '/LC_MESSAGES/' . $source . '.po';
        $fileDest = $dir . $locale . '/LC_MESSAGES/' . $dest . '.po';
        
        $sourceLines = file($fileSource);

        $fhDest = fopen($fileDest, 'a');
        
        $skip = false;
        foreach ($sourceLines as $line) {
            if (trim($line) == 'msgid ""') {
                $skip = true;
            }
            if (!$skip) {
                fwrite($fhDest, $line);
            } else if (trim($line) == '') {
                $skip = false;
            }
        }
        
        fclose($fhDest);
    }
}

function compile($fileName) {
    
    $dir = CARTOCLIENT_HOME . 'locale/';
    
    $locales = I18n::getLocales();
    
    foreach ($locales as $locale) {
        $file = $dir . $locale . '/LC_MESSAGES/' . $fileName . '.po';
        if (file_exists($file)) {
            exec('msgfmt -o ' . $dir . $locale . '/LC_MESSAGES/' . $fileName . '.mo ' . $file);
            unlink($file);

            // Rename file if default project
            if (substr($fileName, 0, strlen(I18n::DEFAULT_PROJECT_DOMAIN)) ==
                                            I18n::DEFAULT_PROJECT_DOMAIN) {
                rename($dir . $locale . '/LC_MESSAGES/' . $fileName . '.mo',
                       $dir . $locale . '/LC_MESSAGES/' .
                            substr($fileName,
                                strlen(I18n::DEFAULT_PROJECT_DOMAIN) + 1) . '.mo');
            }        
        }
    }
}

if ($_SERVER['argc'] > 1) {
    
    $projectName = I18n::DEFAULT_PROJECT_DOMAIN;
    if ($_SERVER['argc'] == 2) {
        $mapId = $_SERVER['argv'][1];
    } else {
        $projectName = $_SERVER['argv'][1];
        $mapId = $_SERVER['argv'][2];
    }
    
    $file = getMapPo($projectName, $mapId);
    merge(getProjectPo($projectName), $file);
    compile($file);

} else {
    print "Usage: ./po2mo.php [<project_name>] <map_id>\n";    
}

?>
