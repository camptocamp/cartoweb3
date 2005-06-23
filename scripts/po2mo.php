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
define('CARTOCLIENT_PODIR', 'po/');
define('CARTOCLIENT_LOCALEDIR', CARTOCLIENT_HOME . 'locale/');

require_once('./pot_tools.php');

require_once(CARTOCLIENT_HOME . 'client/Internationalization.php');

/**
 * Project handler class for constants
 */
require_once(CARTOCOMMON_HOME . 'common/ProjectHandler.php');

/**
 * Finds locales by looking for my_project.lang.po files
 * @param string
 * @return array
 */
function getLocales($project) {

    $dir = CARTOCLIENT_HOME;
    if ($project != ProjectHandler::DEFAULT_PROJECT) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }
    $d = dir($dir . CARTOCLIENT_PODIR);
    $locales = array();
    $locale = '';
       
    while (false !== ($entry = $d->read())) {
        if (!is_dir($entry)) {
            $pattern = 'client-' . $project . '\.(.*)\.po';
            if (ereg($pattern, $entry, $locale)) {
                $locales[] = $locale[1];
            }
        }
    }
    return $locales;
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
function getMapPo($project, $mapId = null) {

    $direct = false;
    $iniFile = CARTOCLIENT_HOME . 'client_conf/client.ini';
    $iniFileProject = '';
    if ($project != ProjectHandler::DEFAULT_PROJECT) {
        $iniFileProject = CARTOCLIENT_HOME . ProjectHandler::PROJECT_DIR . 
                          '/' . $project .
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

    if (is_null($mapId)) {
        $fileName = 'server-' . $project;
    } else {
        $fileName = 'server-' . $project . '.' . $mapId;
    }
    
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
        $url .= 'po/' . $project . '/';

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
                continue;
            } else {
                $dir = CARTOCLIENT_HOME;
                if ($project != ProjectHandler::DEFAULT_PROJECT) {
                    $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
                }
            
                $file = $dir . CARTOCLIENT_PODIR . $fileName . '.' . $locale;
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
function merge($project, $file1, $file2, $file2Project, $output) {
    $dir = CARTOCLIENT_HOME;
    if ($project != ProjectHandler::DEFAULT_PROJECT) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }

    $locales = getLocales($project);

    foreach ($locales as $locale) {
        $file1Name = $file1 . '.' . $locale . '.po';
        $file2Name = $file2 . '-' . $file2Project . '.' . $locale . '.po';
        $fileOutputName = $output . '.' . $locale . '.po';
        $file1path = $dir . CARTOCLIENT_PODIR . $file1Name;
        if ($file2Project == ProjectHandler::DEFAULT_PROJECT) {
            $file2path = CARTOCLIENT_HOME . CARTOCLIENT_PODIR . $file2Name;
        } else {
            $file2path = $dir . CARTOCLIENT_PODIR . $file2Name;
        }
        $fileOutput = $dir . CARTOCLIENT_PODIR . $fileOutputName;
        
        if (!file_exists($file1path)) {
            print "Warning: Project " . $project . 
                  " merge - file $file1path not found.\n";
            continue;
        }
        if (!file_exists($file2path)) {
            print "Warning: Project " . $project . 
                  " merge - file $file2path not found.\n";
            continue;
        }
        
        print "Merging $file1Name + $file2Name = $fileOutputName ";
        
        exec("msgcat --to-code=" . getCharset('client', $project)
             . " --use-first --output=$fileOutput $file1path $file2path");
        
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
    
    $dir = CARTOCLIENT_HOME;
    if ($project != ProjectHandler::DEFAULT_PROJECT) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }

    $locales = getLocales($project);
    
    foreach ($locales as $locale) {
        $file = $dir . CARTOCLIENT_PODIR . $fileName . '.' . $locale . '.po';
        
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
            print "Warning: Project " . $project . 
                  " compile - file $file not found.\n";
            continue;
        }
    }
    return true;
}

/**
 * Gets list of map Ids by reading project directory
 * @param string
 * @return array
 */
function getMapIds($project) {
    
    $mapIds = array();
    $dir = CARTOCLIENT_HOME;
    if ($project != ProjectHandler::DEFAULT_PROJECT) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
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
$projects[] = ProjectHandler::DEFAULT_PROJECT;

foreach ($projects as $project) {
    
    // Gets server project-level file
    $projectFile = getMapPo($project);
    if (!$projectFile) {
        continue;
    }   
    
    $mapIds = getMapIds($project);
    foreach ($mapIds as $mapId) {
    
        // Gets server mapfile-level file
        $file = getMapPo($project, $mapId);
        if (!$file) {
            continue;
        }
        $finalFile = $project . '.' . $mapId;
        if (!merge($project, $file, 'server', $project, $finalFile)) {
            continue;
        }
        if (!merge($project, $finalFile, 'client', $project, $finalFile)) {
            continue;
        }
        if ($project != ProjectHandler::DEFAULT_PROJECT) {
            if (!merge($project, $finalFile,
                       'server', ProjectHandler::DEFAULT_PROJECT, $finalFile)) {
                continue;
            }
            if (!merge($project, $finalFile,
                       'client', ProjectHandler::DEFAULT_PROJECT, $finalFile)) {
                continue;
            }
        }
        if (!compile($project, $finalFile)) {
            continue;
        }
    }
}

?>
