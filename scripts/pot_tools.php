<?php
/**
 * pot_tools.php - useful functions for POT scripts
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

/**
 * Home dirs
 */ 
if (!defined('CARTOWEB_HOME'))
    define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');
if (!defined('CARTOCOMMON_PODIR'))
    define('CARTOCOMMON_PODIR', 'po/');

/**
 * Encoding class for charset
 */
require_once(CARTOWEB_HOME . 'common/Encoding.php');

/**
 * Project handler class for constants
 */
require_once(CARTOWEB_HOME . 'common/ProjectHandler.php');

/**
 * Dirs to exclude while looking for no PHP files
 */
global $excludedGenDirs;
$excludedGenDirs = array('.', '..', 'CVS', '.svn');

/**
 * Finds charset in client.ini
 * @param string
 * @return string
 */
function getCharset($type, $project) {
    
    $class = null;
    $iniFile = CARTOWEB_HOME;
    $projectIniFile = CARTOWEB_HOME;
    if (!is_null($project)) {
        $projectIniFile .= ProjectHandler::PROJECT_DIR . '/' . $project. '/';
    }
    $iniFile .= $type . "_conf/$type.ini";
    $projectIniFile .= $type . "_conf/$type.ini";
    if (file_exists($projectIniFile)) {
        $iniArray = parse_ini_file($projectIniFile);
        if (array_key_exists('EncoderClass.config', $iniArray)) {
            $class = $iniArray['EncoderClass.config'];
        }
    }
    if (is_null($class) && $iniFile != $projectIniFile
                          && file_exists($iniFile)) {
        $iniArray = parse_ini_file($iniFile);
        if (array_key_exists('EncoderClass.config', $iniArray)) {
            $class = $iniArray['EncoderClass.config'];
        }
    }
    if (is_null($class)) {
        $class = 'EncoderUTF';
    }
    $obj = new $class;
    return $obj->getCharset();
}

/**
 * Gets list of projects by reading projects directory
 * @return array
 */
function getProjects($projectname = false) {

    global $excludedGenDirs;
    $projects = array();
    $dir = CARTOWEB_HOME . ProjectHandler::PROJECT_DIR . '/';
    $d = dir($dir);
    while (false !== ($entry = $d->read())) {
        if (is_dir($dir . $entry) && !in_array($entry, $excludedGenDirs)) {
            $projects[] = $entry;
        }
    }
    if ($projectname) {
        if (in_array($projectname, $projects)) {
            $projects = array($projectname);
        } else {
            $projects = array();
            print "error: $projectname is not in the project list, ignored \n";
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

    global $excludedGenDirs;
    $mapIds = array();
    $dir = CARTOWEB_HOME;
    if (!is_null($project)) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }
    $dir .= 'server_conf/';
    if (is_dir($dir)) {
        $d = dir($dir);
        while (false !== ($entry = $d->read())) {
            if (is_dir($dir . $entry) && !in_array($entry, $excludedGenDirs)) {
                $mapIds[] = $entry;
            }
        }
    }    
    return $mapIds;    
}

/**
 * Dirs to exclude while looking for PHP files
 */
$excludedPhpDirs = array('pear_base', 'include', 'www-data',
                      'doc', 'client_conf', 'server_conf', 'locale',
                      'po', 'templates', 'templates_c', 'log',
                      'documentation');

$excludedPhpDirs += $excludedGenDirs;

/**
 * Finds recursively all strings in PHP code and add them to PO template 
 *
 * Will detect methods gt() from classes {@link I18n} and {@link I18nNoop}.
 * @param string
 * @param string
 */
function addPhpStrings($type, $path, $poTemplate, $project) {
    global $excludedPhpDirs;
    
    $dir = CARTOWEB_HOME;
    if (!is_null($project)) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }
    if (is_dir($path)) {
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            if (!is_dir($path . $entry) && 
                substr($entry, -4) == '.php') {

                exec("xgettext --from-code=" . getCharset($type, $project)
                     . "--language=PHP --keyword=gt --keyword=ngt:1,2 --output=$dir"
                     . CARTOCOMMON_PODIR . "_tmp_xgettext.po "
                     . $path . $entry);

                if (file_exists($dir . CARTOCOMMON_PODIR . "_tmp_xgettext.po")) {
                    $filecontents = file_get_contents($dir . CARTOCOMMON_PODIR
                                                      . "_tmp_xgettext.po");
                    $filecontents = str_replace(array('CHARSET', "#: $dir"),
                                                array(getCharset($type, $project), '#: '),
                                                $filecontents);
                    file_put_contents($dir . CARTOCOMMON_PODIR
                                      . "_tmp_xgettext.po", $filecontents);
                    exec("msgcat --to-code=" . getCharset($type, $project)
                         . " --use-first --output=$poTemplate $poTemplate $dir"
                         . CARTOCOMMON_PODIR . "_tmp_xgettext.po");
                    
                    unlink($dir . CARTOCOMMON_PODIR . "_tmp_xgettext.po");
                }
            } else if (is_dir($path . $entry)
                       && !in_array($entry, $excludedPhpDirs)
                       &&
                       (
                        (is_null($project)
                         && !strstr($path . $entry, ProjectHandler::PROJECT_DIR . '/'))
                       ||
                        (!is_null($project)
                         && (strstr($path . $entry, ProjectHandler::PROJECT_DIR . '/' . $project)
                             || $entry == ProjectHandler::PROJECT_DIR))
                       )
                       &&
                       (($type == 'server' && $entry != 'client')
                        ||
                        ($type == 'client' && $entry != 'server'))
                      ) {
                addPhpStrings($type, $path . $entry . '/', $poTemplate, $project);
            }
        }
    }
}

/**
 * Finds list of already translated PO files for a project
 * @param string
 * @return array
 */
function getTranslatedPo($type, $project) {
    
    $files = array();
    $dir = CARTOWEB_HOME;
    if (!is_null($project)) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }
    $d = dir($dir . CARTOCOMMON_PODIR);

    $pattern = "$type\\.(.*)\\.po";
 
    while (false !== ($entry = $d->read())) {
        if (!is_dir($dir . $entry)) {
            if (ereg($pattern, $entry, $regs)) {
                if (strlen($regs[1]) == 2) {
                    $files[] = $entry;
                }
            };
        }
    }    
 
    return $files;   
}

/**
 * Parses an INI file looking for variable ending with '.label' and '.labels'
 * @param string
 * @param array map text_to_translate => references
 * @param string (optional)
 * @return boolean
 */
function parseIni($project, &$texts, $mapId = null) {

    $iniPath = CARTOWEB_HOME;
    if (!is_null($project)) {
        $iniPath .= ProjectHandler::PROJECT_DIR . '/' . $project. '/';
    }

    if($mapId != null) {
        $iniPath .= 'server_conf/' . $mapId . '/';
    } else {
        $iniPath .= 'client_conf/';
    }

    if (!is_dir($iniPath)) {
        return true;
    }
    $d = dir($iniPath);
    while (false !== ($entry = $d->read())) {
        if (!is_dir($entry) && substr($entry, -4) == '.ini') {
            $iniFile = $iniPath . $entry;
            $iniArray = parse_ini_file($iniFile);
            foreach($iniArray as $key => $value) {
                if (substr($key, -6) == '.label') {
                    $info = $entry . ':' . $key;
                    if (array_key_exists($value, $texts)) {
                        $texts[$value] .= ',' . $info;
                    } else {
                        $texts[$value] = $info;
                    }
                }
                if (substr($key, -7) == '.labels') {
                    $info = $entry . ':' . $key;
                    $values = explode(',',$value);
                    $values = array_map('trim', $values);
                    foreach($values as $keys=>$vals) {
                        if (array_key_exists($vals, $texts)) {
                            $texts[$vals] .= ',' . $info;
                        } else {
                            $texts[$vals] = $info;
                        }
                    }
                }
            }
        }
    }
    return true;
}

?>
