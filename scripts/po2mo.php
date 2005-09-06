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
if (!defined('CARTOWEB_HOME'))
    define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOCLIENT_PODIR', 'po/');
define('CARTOCLIENT_LOCALEDIR', CARTOWEB_HOME . 'locale/');

require_once(CARTOWEB_HOME . 'scripts/pot_tools.php');
require_once(CARTOWEB_HOME . 'client/Internationalization.php');
if (!defined('CW3_SETUP_INCLUDED'))
    require_once(CARTOWEB_HOME . 'cw3setup.php');

/**
 * Project handler class for constants
 */
require_once(CARTOWEB_HOME . 'common/ProjectHandler.php');

/**
 * Finds locales by looking for my_project.lang.po files
 * @param string
 * @return array
 */
function getLocales($project) {

    $dir = CARTOWEB_HOME;
    if (!is_null($project)) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }
    if (!is_dir($dir . CARTOCLIENT_PODIR)) {
        return array();
    }
    $d = dir($dir . CARTOCLIENT_PODIR);
    $locales = array();
    $locale = '';
       
    while (false !== ($entry = $d->read())) {
        if (!is_dir($entry)) {
            $pattern = 'client\.(.*)\.po';
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
    $iniFile = CARTOWEB_HOME . 'client_conf/client.ini';
    $iniFileProject = '';
    if (!is_null($project)) {
        $iniFileProject = CARTOWEB_HOME . ProjectHandler::PROJECT_DIR . 
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
        $fileName = 'server';
    } else {
        $fileName = 'server.' . $mapId;
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
            warn("Warning: Project $project base URL not set in client.ini");
            return false;
        }
        $url .= 'po/' . $project . '/';

        $locales = getLocales($project);

        foreach ($locales as $locale) {
            $urlLocale = $url . $fileName . '.' . $locale . '.po';

            debug("Retrieving server PO file $urlLocale ");
            
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
                warn("Warning: Couldn't retrieve server file.");
                continue;
            } else {
                $dir = CARTOWEB_HOME;
                if (!is_null($project)) {
                    $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
                }
            
                $file = $dir . CARTOCLIENT_PODIR . $fileName . '.' . $locale;
                $fh = fopen($file . '.po', 'w');
                fwrite($fh, $contents);
                fclose($fh);                                
            }
            debug(" .. done.");
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
    $dir = CARTOWEB_HOME;
    if (!is_null($project)) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }

    $locales = getLocales($project);

    foreach ($locales as $locale) {
        $file1Name = $file1 . '.' . $locale . '.po';
        $file2Name = $file2 . '.' . $locale . '.po';
        $fileOutputName = $output . '.' . $locale . '.po';
        $file1path = $dir . CARTOCLIENT_PODIR . $file1Name;
        if (is_null($file2Project)) {
            $file2path = CARTOWEB_HOME . CARTOCLIENT_PODIR . $file2Name;
        } else {
            $file2path = $dir . CARTOCLIENT_PODIR . $file2Name;
        }
        $fileOutput = $dir . CARTOCLIENT_PODIR . $fileOutputName;
        
        if (!file_exists($file1path)) {
            warn("Warning: Project " . $project . 
                  " merge - file $file1path not found.");
            continue;
        }
        if (!file_exists($file2path)) {
            warn("Warning: Project " . $project . 
                  " merge - file $file2path not found.");
            continue;
        }
        
        debug("Merging $file1Name + $file2Name = $fileOutputName ");
        
        if (!hasCommand('msgcat')) {
            throw new InstallException("Can't find the msgcat command, " .
                    "be sure to have gettext installed correctly");   
        }
        
        execWrapper("msgcat --to-code=" . getCharset('client', $project)
             . " --use-first --output=$fileOutput $file1path $file2path");
        
        debug(" .. done.");    
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
    
    $dir = CARTOWEB_HOME;
    if (!is_null($project)) {
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
            $outputFile = $locale . '/LC_MESSAGES/' . $project . '.' . $fileName . '.mo'; 
            debug("Compiling $fileName.$locale.po into /locale/$outputFile ");
            if (!hasCommand('msgfmt --help')) {
                throw new InstallException("Can't find the msgfmt command, " .
                        "be sure to have gettext installed correctly");   
            }   

            execWrapper("msgfmt -o " . CARTOCLIENT_LOCALEDIR . "$outputFile $file");
            debug(".. done.");
        } else {
            warn("Warning: Project " . $project . 
                  " compile - file $file not found.");
            continue;
        }
    }
    return true;
}

$projects = getProjects();
// Adds a null value for extracting the po file from upstream
$projects[] = null;

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
        $finalFile = $mapId;
        if (!merge($project, $file, 'server', $project, $finalFile)) {
            continue;
        }
        if (!merge($project, $finalFile, 'client', $project, $finalFile)) {
            continue;
        }
        if (!is_null($project)) {
            if (!merge($project, $finalFile,
                       'server', null, $finalFile)) {
                continue;
            }
            if (!merge($project, $finalFile,
                       'client', null, $finalFile)) {
                continue;
            }
        }
        if (!compile($project, $finalFile)) {
            continue;
        }
    }
}

?>
