<?php
/**
 * pot_tools.php - useful functions for POT scripts
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

/**
 * Home dirs
 */ 
define('CARTOCOMMON_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOCOMMON_PODIR', CARTOCOMMON_HOME . 'po/');

/**
 * Encoding class for charset
 */
require_once(CARTOCOMMON_HOME . 'common/Encoding.php');

/**
 * Project handler class for constants
 */
require_once(CARTOCOMMON_HOME . 'common/ProjectHandler.php');

/**
 * Finds charset in client.ini
 * @param string
 * @return string
 */
function getCharset($type, $project) {
    
    $class = null;
    $iniFile = CARTOCOMMON_HOME;
    $projectIniFile = CARTOCOMMON_HOME;
    if ($project != ProjectHandler::DEFAULT_PROJECT) {
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
function getProjects() {

    $projects = array();
    $dir = CARTOCOMMON_HOME . ProjectHandler::PROJECT_DIR . '/';
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
 * Dirs to exclude while looking for PHP files
 */
$exclude_dirs = array('pear_base', 'include', 'www-data',
                      'doc', 'client_conf', 'server_conf', 'locale',
                      'po', 'templates', 'templates_c', 'log',
                      'documentation', '.', '..', 'CVS');

/**
 * Finds recursively all strings in PHP code and add them to PO template 
 *
 * Will detect methods gt() from classes {@link I18n} and {@link I18nNoop}.
 * @param string
 * @param string
 */
function addPhpStrings($type, $path, $poTemplate, $project) {
    global $exclude_dirs;
    
    if (is_dir($path)) {
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            if (!is_dir($path . $entry) &&
                substr($entry, -4) == '.php') {
                exec("xgettext --from-code=" . getCharset($type, $project)
                     . "--language=PHP --keyword=gt --output="
                     . CARTOCOMMON_PODIR . "_tmp_xgettext.po "
                     . $path . $entry);
                if (file_exists(CARTOCOMMON_PODIR . "_tmp_xgettext.po")) {
                    $filecontents = file_get_contents(CARTOCOMMON_PODIR
                                                      . "_tmp_xgettext.po");
                    $filecontents = str_replace('CHARSET', getCharset($type, $project),
                                                $filecontents);
                    file_put_contents(CARTOCOMMON_PODIR
                                      . "_tmp_xgettext.po", $filecontents);
                    exec("msgcat --to-code=" . getCharset($type, $project)
                         . " --use-first --output=$poTemplate $poTemplate "
                         . CARTOCOMMON_PODIR . "_tmp_xgettext.po");
                    
                    unlink(CARTOCOMMON_PODIR . "_tmp_xgettext.po");
                }
            } else if (is_dir($path . $entry)
                       && !in_array($entry, $exclude_dirs)
                       &&
                       (
                        ($project == ProjectHandler::DEFAULT_PROJECT
                         && !strstr($path . $entry, ProjectHandler::PROJECT_DIR . '/'))
                       ||
                        ($project != ProjectHandler::DEFAULT_PROJECT
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
    $dir = CARTOCOMMON_HOME . 'po/';
    $d = dir($dir);

    $pattern = "$type\\-$project\\.(.*)\\.po";
 
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

?>
