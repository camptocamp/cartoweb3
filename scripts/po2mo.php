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

/**
 * Finds locales by looking for my_project.lang.po files
 * @param string
 * @return array
 */
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

/**
 * Returns project name for PO file name
 * @param string
 * @return string
 */
function getProjectName($project) {
    return ($project == '') ? 'default' : $project;
}

/**
 * Returns PO file name without extension
 * @param string
 * @param string
 */
function getProjectPo($project = '') {
    return 'client-' . getProjectName($project);
}

/**
 * Retrieves PO from server
 *
 * If not in direct mode, will retrieve file using CURL. This needs CURL 
 * to be installed in PHP environment.
 * @param string
 * @param string
 * @return string retrieved file name
 */
function getMapPo($project, $mapId) {

    $direct = false;
    $iniFile = CARTOCLIENT_HOME . 'client_conf/client.ini';
    $iniFileProject = '';
    if ($project != '') {
        $iniFileProject = CARTOCLIENT_HOME . 'projects/' . $project .
                          '/client_conf/client.ini';
    }        
    if (!file_exists($iniFileProject)) {
        $iniFileProject = $iniFile;
    }
    $iniArray = parse_ini_file($iniFile);
    $iniArrayProject = parse_ini_file($iniFileProject);
    if (array_key_exists('cartoserverDirectAccess', $iniArrayProject)) {
        $direct = $iniArrayProject['cartoserverDirectAccess'];
    } else if (array_key_exists('cartoserverDirectAccess', $iniArray)) {
        $direct = $iniArray['cartoserverDirectAccess'];
    }

    $fileName = 'server-';
    if ($project != '') {
        $fileName .= $project . '.';
    }
    $fileName .= $mapId;    
    
    if ($direct) {
 
        // nothing to do !
        
    } else {
        // Looks for server URL
        if (array_key_exists('cartoserverBaseUrl', $iniArrayProject)) {
            $url = $iniArrayProject['cartoserverBaseUrl'];
        } else if (array_key_exists('cartoserverBaseUrl', $iniArray)) {
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

/**
 * Merges two PO files
 * 
 * Writes out all messages of first file, then writes out messages from 
 * second file if not present in first one.
 * @param string
 * @param string
 * @param string
 * @param string
 * @return boolean true if OK
 */
function merge($project, $file1, $file2, $output) {

    $locales = getLocales($project);

    foreach ($locales as $locale) {
        $file1Name = $file1 . '.' . $locale . '.po';
        $file2Name = $file2 . '.' . $locale . '.po';
        $fileOutputName = $output . '.' . $locale . '.po';
        $file1path = CARTOCLIENT_PODIR . $file1Name;
        $file2path = CARTOCLIENT_PODIR . $file2Name;
        $fileOutput = CARTOCLIENT_PODIR . $fileOutputName;
        
        if (!file_exists($file1path)) {
            print "Warning: Project " . getProjectName($project) . 
                  " merge - file $file1path not found.\n";
            return false;
        }
        if (!file_exists($file2path)) {
            print "Warning: Project " . getProjectName($project) . 
                  " merge - file $file2path not found.\n";
            return false;
        }
        
        print "Merging $file1Name + $file2Name = $fileOutputName ";
        
        $file1Lines = file($file1path);
        $file2Lines = file($file2path);

        $fhOut = fopen($fileOutput, 'w');
        
        $msgIds = array();
        foreach ($file1Lines as $line) {
            fwrite($fhOut, $line);
            if (ereg('msgid \"(.*)\"', $line, $regs)) {
                $msgIds[] = $regs[1];
            }
        }
        
        $skip = false;
        foreach ($file2Lines as $line) {
            if (trim($line) == 'msgid ""') {
                $skip = true;
            } else if (ereg('msgid \"(.*)\"', $line, $regs)) {
                if (in_array($regs[1], $msgIds)) {
                    $skip = true;
                }
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

/**
 * Compiles PO files into MO
 * @param string
 * @param string
 * @return boolean true if OK
 */
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
            print "Warning: Project " . getProjectName($project) . 
                  " compile - file $file not found.\n";
            return false;
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

/**
 * Gets list of map Ids by reading project directory
 * @param string
 * @return array
 */
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
        if (!merge($project, $file, getProjectPo($project), $finalFile)) {
            continue;
        }
        if ($project != '') {
            if (!merge($project, $finalFile, getProjectPo(''), $finalFile)) {
                continue;
            }
        }
        if (!compile($project, $finalFile)) {
            continue;
        }
    }
}

?>
