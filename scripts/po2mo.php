#!/usr/local/bin/php
<?php
/**
 * po2mo.php - compile all PO files
 *
 * Usage:
 * ./po2mo.php
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

/**
 * Cartoclient home dir
 */
define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOCLIENT_PODIR', CARTOCLIENT_HOME . 'po/');
define('CARTOCLIENT_LOCALEDIR', CARTOCLIENT_HOME . 'locale/');

require_once(CARTOCLIENT_HOME . 'client/Internationalization.php');

function getLocales($project) {
    $d = dir(CARTOCLIENT_PODIR);
    $locales = array();
    $locale = '';
       
    while (false !== ($entry = $d->read())) {
        if (!is_dir($entry)) {
            $pattern = getProjectPo($project) . '\.(.*)\.po';
            if (ereg($pattern, $entry, $locale)) {
                $locales[] = $locale[1];
            }
        }
    }
    return $locales;
}

function getProjectName($project) {
    return ($project == '') ? 'default' : $project;
}

function getProjectPo($project = '') {
    return 'smarty-' . getProjectName($project);
}

function getMapPo($project, $mapId) {

    $direct = false;
    $iniFile = '';
    if ($project != '') {
        $iniFile = CARTOCLIENT_HOME . 'projects/' . $project . '/client_conf/client.ini';
    }        
    if (!file_exists($iniFile)) {
        $iniFile = CARTOCLIENT_HOME . 'client_conf/client.ini';
    }
    $iniArray = parse_ini_file($iniFile);
    if (array_key_exists('cartoserverDirectAccess', $iniArray)) {
        $direct = $iniArray['cartoserverDirectAccess'];
    }

    $fileName = 'map-';
    if ($project != '') {
        $fileName .= $project . '.';
    }
    $fileName .= $mapId;    
    
    if ($direct) {
 
        // nothing to do !
        
    } else {
        // Looks for server URL
        if (array_key_exists('cartoserverBaseUrl', $iniArray)) {
            $url = $iniArray['cartoserverBaseUrl'];
        } else {
            print "Warning: Project $project base URL not set in client.ini\n";
            return false;
        }
        $url .= 'po/';

        $locales = getLocales($project);

        foreach ($locales as $locale) {
            $urlLocale = $url . $fileName . '.' . $locale . '.po';

            print "Retrieving server PO file $urlLocale ";
            
            // CURL init
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlLocale);
            curl_setopt($ch, CURLOPT_HEADER, 0);
                        
            ob_start();
            curl_exec($ch);
            curl_close($ch);  
            $contents = ob_get_contents();
            ob_end_clean();
            
            if (strpos($contents, 'DOCTYPE HTML')) {
                print "\nWarning: Couldn't retrieve server file.\n";
                return false;
            } else {
                $file = CARTOCLIENT_PODIR . $fileName . '.' . $locale;
                $fh = fopen($file . '.po', 'w');
                fwrite($fh, $contents);
                fclose($fh);                                
            }
            print " .. done.\n";
        }
    }
    
    return $fileName;
}

function merge($project, $file1, $file2, $output) {

    $locales = getLocales($project);

    foreach ($locales as $locale) {
        $file1Name = $file1 . '.' . $locale . '.po';
        $file2Name = $file2 . '.' . $locale . '.po';
        $fileOutputName = $output . '.' . $locale . '.po';
        $file1 = CARTOCLIENT_PODIR . $file1Name;
        $file2 = CARTOCLIENT_PODIR . $file2Name;
        $fileOutput = CARTOCLIENT_PODIR . $fileOutputName;
        
        if (!file_exists($file1)) {
            print "Warning: Project " . getProjectName($project) . " merge - file $file1 not found.\n";
            return false;
        }
        if (!file_exists($file2)) {
            print "Warning: Project " . getProjectName($project) . " merge - file $file2 not found.\n";
            return false;
        }
        
        print "Merging $file1Name + $file2Name = $fileOutputName ";
        
        $file1Lines = file($file1);
        $file2Lines = file($file2);

        $fhOut = fopen($fileOutput, 'w');
        
        foreach ($file1Lines as $line) {
            fwrite($fhOut, $line);
        }
        
        $skip = false;
        foreach ($file2Lines as $line) {
            if (trim($line) == 'msgid ""') {
                $skip = true;
            }
            if (trim($line) == '') {
                $skip = false;
            }
            if (!$skip) {
                fwrite($fhOut, $line);
            }
        }
        
        fclose($fhOut);    
        print " .. done.\n";    
    }
    return true;
}

function compile($project, $fileName) {
    
    $locales = getLocales($project);
    
    foreach ($locales as $locale) {
        $file = CARTOCLIENT_PODIR . $fileName . '.' . $locale . '.po';
        
        if (file_exists($file)) {
            if (!is_dir(CARTOCLIENT_LOCALEDIR . $locale)) {
                mkdir(CARTOCLIENT_LOCALEDIR . $locale);
            }
            if (!is_dir(CARTOCLIENT_LOCALEDIR . $locale . '/LC_MESSAGES')) {
                mkdir(CARTOCLIENT_LOCALEDIR . $locale . '/LC_MESSAGES');
            }
            $outputFile = $locale . '/LC_MESSAGES/' . $fileName . '.mo'; 
            print "Compiling $fileName.$locale.po into /locale/$outputFile ";
            exec("msgfmt -o " . CARTOCLIENT_LOCALEDIR . "$outputFile $file");
            print ".. done.\n";
        } else {
            print "Warning: Project " . getProjectName($project) . " compile - file $file not found.\n";
            return false;
        }
    }
    return true;
}

function getProjects() {

    $projects = array();
    $dir = CARTOCLIENT_HOME . 'projects/';
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
    $dir = CARTOCLIENT_HOME;
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

$projects = getProjects();
// Adds default project
$projects[] = '';

foreach ($projects as $project) {
    
    $mapIds = getMapIds($project);
    foreach ($mapIds as $mapId) {
    
        $file = getMapPo($project, $mapId);
        if (!$file) {
            continue;
        }
        $finalFile = '';
        if ($project != '') {
            $finalFile .= $project . '.';
        }
        $finalFile .= $mapId;
        if (!merge($project, getProjectPo($project), $file, $finalFile)) {
            continue;
        }
        if ($project != '') {
            if (!merge($project, getProjectPo(''), $finalFile, $finalFile)) {
                continue;
            }
        }
        if (!compile($project, $finalFile)) {
            continue;
        }
    }
}

?>
