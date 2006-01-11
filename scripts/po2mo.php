<?php
/**
 * po2mo.php - compile PO files
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * Usage:
 * php po2mo.php [projectname]
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
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
if (!defined('CW3_SETUP_INCLUDED')) {
    require_once(CARTOWEB_HOME . 'cw3setup.php');
    define('CALLED_BY_CW3_SETUP', false);
} else {
    define('CALLED_BY_CW3_SETUP', true);
}

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
    return array_unique($locales);
}

/**
 * Retrieves config from upstream and project client.ini.
 * @param string project name (optional)
 * @return array
 */
function getIniArray($project = NULL) {
    $iniArray = parse_ini_file(CARTOWEB_HOME . 'client_conf/client.ini');
    
    if (!is_null($project)) {
        $iniFileProject = CARTOWEB_HOME . ProjectHandler::PROJECT_DIR 
                          . "/$project/client_conf/client.ini";
    
        if (is_readable($iniFileProject)) {
            // overloads upstream parameters with project ones
            $iniArray = array_merge($iniArray, parse_ini_file($iniFileProject));
        }
    }        

    return $iniArray;
}

/**
 * Retrieves PO files from server.
 * @param string
 * @param string
 * @return string retrieved file name
 */
function getMapPo($project, $mapId = null) {

    if (is_null($project)) {
        return '';
    }

    $iniArray = getIniArray($project);

    if (array_key_exists('cartoserverDirectAccess', $iniArray)) {
        $direct = $iniArray['cartoserverDirectAccess'];
    } else {
        $direct = false;
    }

    $nullMapId = is_null($mapId);
    if ($nullMapId) {
        $mapId = getMapIdFromIni($project, $iniArray);
        // In case of a project using server part of another,
        // server.<locale>.po files are this way retrieved from the 
        // matching "server" project.

        $fileName = 'server';
    } else {
        $fileName = "server.$mapId";
    }
   
    // Looks for server URL
    if ($direct) {
        $url = CARTOWEB_HOME . 'htdocs/';
    } else {
        if (empty($iniArray['cartoserverBaseUrl'])) {
            warn("Warning: Project $project base URL not set in client.ini");
            return '';
        }
        $url = $iniArray['cartoserverBaseUrl'];
        if (substr($url, -1) != '/' && substr($url, -1) != '\\') {
            $url .= '/';
        }
    }
    $url .= 'po/';

    // Checks if we use a server part from another project
    $mapIdInfo = explode('.', $mapId);
    $separatedProject = !empty($mapIdInfo) && is_array($mapIdInfo) &&
                        count($mapIdInfo) == 2;
    if ($separatedProject) {
        $url .= $mapIdInfo[0];
        $remoteFileName = 'server';
        if (!$nullMapId) {
            $remoteFileName .= '.' . $mapIdInfo[1];
        }
    } else {
        $url .= $project;
        $remoteFileName = $fileName;
    }
    
    // This part is only necessary when in SOAP mode or when using 
    // server po files from an other project.
    if (!$direct || $separatedProject) {
        $url .= '/';
        foreach (getLocales($project) as $locale) {

            $urlLocale = "$url$remoteFileName.$locale.po";
            debug("Retrieving server PO file $urlLocale");
            
            // retrieves remote PO file:
            $contents = file_get_contents($urlLocale);
            
            if (empty($contents)) {
                warn("Warning: Couldn't retrieve server '$locale' file.");
                continue;
            } else {
                $dir = CARTOWEB_HOME;
                if (!is_null($project)) {
                    $dir .= ProjectHandler::PROJECT_DIR . "/$project/";
                }
            
                $file = $dir . CARTOCLIENT_PODIR . "$fileName.$locale.po";
                debug("Saving remote file as $file");
                file_put_contents($file, $contents);
            }
            debug("Done\n");
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
        $dir .= ProjectHandler::PROJECT_DIR . "/$project/";
    }

    $locales = getLocales($project);

    if (empty($locales)) {
        debug("No locale found for project '$project'. Aborted.");
        return false;
    }

    foreach ($locales as $locale) {
        $file1Name = "$file1.$locale.po";
        $file2Name = "$file2.$locale.po";
        $fileOutputName = "$output.$locale.po";
        $file1path = $dir . CARTOCLIENT_PODIR . $file1Name;
        if (is_null($file2Project)) {
            $file2path = CARTOWEB_HOME . CARTOCLIENT_PODIR . $file2Name;
        } else {
            $file2path = $dir . CARTOCLIENT_PODIR . $file2Name;
        }
        $fileOutput = $dir . CARTOCLIENT_PODIR . $fileOutputName;
        
        if (!file_exists($file1path)) {
            warn("Warning: Project $project" . 
                  " merge - file $file1path not found.");
            continue;
        }
        if (!file_exists($file2path)) {
            warn("Warning: Project $project" . 
                  " merge - file $file2path not found.");
            continue;
        }
        
        debug("Merging $file1Name + $file2Name = $fileOutputName");
        
        if (!hasCommand('msgcat')) {
            throw new InstallException("Can't find the msgcat command, " .
                    'be sure to have gettext installed correctly.');   
        }
        
        execWrapper('msgcat --to-code=' . getCharset('client', $project)
             . " --use-first --output=$fileOutput $file1path $file2path");
        
        debug("Done\n");    
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
        $dir .= ProjectHandler::PROJECT_DIR . "/$project/";
    }

    foreach (getLocales($project) as $locale) {
        $file = $dir . CARTOCLIENT_PODIR . "$fileName.$locale.po";
        
        if (file_exists($file)) {
            if (!is_dir(CARTOCLIENT_LOCALEDIR . $locale)) {
                mkdir(CARTOCLIENT_LOCALEDIR . $locale);
            }
            if (!is_dir(CARTOCLIENT_LOCALEDIR . "$locale/LC_MESSAGES")) {
                mkdir(CARTOCLIENT_LOCALEDIR . "$locale/LC_MESSAGES");
            }
            
            $fileName2 = strpos($fileName, '.') ? $fileName
                                                : "$project.$fileName";
            $outputFile = "$locale/LC_MESSAGES/$project.$fileName2.mo"; 
            
            debug("Compiling $fileName.$locale.po into /locale/$outputFile");
            
            if (!hasCommand('msgfmt --help')) {
                throw new InstallException("Can't find the msgfmt command, " .
                        'be sure to have gettext installed correctly.');   
            }   

            execWrapper('msgfmt -o ' . CARTOCLIENT_LOCALEDIR . "$outputFile $file");
            debug("Done\n");
        } else {
            warn("Warning: Project $project compile - file $file not found.");
            continue;
        }
    }
    return true;
}

/**
 * Retrieves the 'mapId' parameter in project client.ini config.
 * @param string project name
 * @param array array of ini parameters (optional)
 * @return string
 */
function getMapIdFromIni($project, $iniArray = NULL) {
    if (is_null($iniArray)) {
        $iniArray = getIniArray($project);
    }
    if (array_key_exists('mapId', $iniArray)) {
        return $iniArray['mapId'];
    }
    return '';
}

/**
 * Script starts here!
 */

// If project info is available, only runs the script for the given project.
if (empty($projects)) {
    if ($_SERVER['argc'] == 2 && !CALLED_BY_CW3_SETUP) {
        $projects = getProjects($_SERVER['argv'][1]);
    } else {
        $projects = getProjects();
    }
}

foreach ($projects as $project) {

    debug("\nProcessing project '$project'...\n");
    
    // Gets server project-level file 
    // (mainly used to retrieve remote server files)
    debug("Processing server project-level PO files for project '$project'...\n");
    $projectFile = getMapPo($project);
    if (!$projectFile) {
        debug("No project-level PO files found for project $project. Aborted.");
        continue;
    }   
    
    $mapIds = getMapIds($project);
    // Case of projects using mapfile from other projects:
    $mapIds[] = getMapIdFromIni($project);
    $mapIds = array_unique($mapIds);
    
    foreach ($mapIds as $mapId) {
    
        // Gets server mapfile-level file
        debug('Processing server mapfile-level PO files' .
              " for project '$project' and mapId '$mapId'...\n");
        $file = getMapPo($project, $mapId);
        if (!$file) {
            debug("No mapfile-level PO files found for $project/$mapId. Aborted.");
            continue;
        }
        $finalFile = $mapId;

        // Merges 'server.<mapId>.<lang>.po' and 'server.<lang>.po'
        if (!merge($project, $file, 'server', $project, $finalFile)) {
            continue;
        }

        // Merges previous resulting files and project 'client.<lang>.po'
        if (!merge($project, $finalFile, 'client', $project, $finalFile)) {
            continue;
        }
        
        // Not executed when retrieving upstream po files (already done above)
        if (!is_null($project)) {
            
            // Merges previous resulting files and upstream 'server.<lang>.po'
            if (!merge($project, $finalFile,
                       'server', null, $finalFile)) {
                continue;
            }

            // Merges previous resulting files and upstream 'client.<lang>.po'
            if (!merge($project, $finalFile,
                       'client', null, $finalFile)) {
                continue;
            }
        }

        // Compiling resulting PO files into MO files
        if (!compile($project, $finalFile)) {
            continue;
        }
    }
}

?>
