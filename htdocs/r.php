<?php
/**
 * Mini-proxy for accessing static resources from main, project, plugins, and
 * resources inside projects.
 * 
 * @package Htdocs
 * @version $Id$
 */

define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOCOMMON_HOME', CARTOCLIENT_HOME);

require_once(CARTOCOMMON_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array('client' => true));

require_once(CARTOCOMMON_HOME . 'common/ProjectHandler.php');
require_once(CARTOCOMMON_HOME . 'common/PluginManager.php');
require_once('log4php/LoggerManager.php');

/**
 * Project handler for accessing resources.
 */
class ResourceProjectHandler extends ProjectHandler {
 
    /**
     * Constructor
     */
    public function __construct() {
    }
 
    /**
     * @see ProjectHandler::getRootPath()
     * @return string
     */
    public function getRootPath() {
        return CARTOCLIENT_HOME;
    }
    
    /**
     * @see ProjectHandler::getProjectName()
     * @return string
     */
    public function getProjectName() {
        if (isset($_REQUEST['pr']))
            return $_REQUEST['pr'];
        return ProjectHandler::DEFAULT_PROJECT;
    }
}

// set to true if you want debug
$debug = false;
$n = 0;

/**
 * Debug function putting its output in headers.
 * @param string message to output in headers
 */
function dbg($msg) {
    global $n, $debug;
    if (!$debug)
        return;
    $str = var_export($msg, true);
    header("X-debug$n: $msg");
    $n++;
}

/**
 * Shows an arror and aborts the script.
 * If debug is off, the error message is generic, to prevent malicious users
 * to try to interpret the messages to find holes.
 * @param string
 */
function fatal($msg) {
    global $debug;
    if ($debug)
        die($msg);
    else
        die('error');
}

/**
 * Class to translate from request parameters to a resource file accessible from
 * the filesystem.
 */
class MiniproxyFileProvider {
 
    /**
     * @param string resource name
     * @param array request data
     * @return string Path to a file in a plugin's htdocs directory
     */
    public function getHdocsFile($resource, $requ) {
        
        $projectHandler = new ResourceProjectHandler();
      
        $path = 'htdocs/';
        
        // plugin
        if (isset($requ['pl'])) {
            $plugin = $requ['pl'];
            
            $pluginManager = new PluginManager(CARTOCLIENT_HOME, 
                                    PluginManager::CLIENT, $projectHandler);
            
            $relativePath = $pluginManager->getRelativePath($plugin);
            $path = $relativePath . $plugin . '/' . $path;
            dbg($path);
        }
        
        $prjPath = $projectHandler->getPath($path, $resource);
        
        $filePath = CARTOCLIENT_HOME . $prjPath . $resource;
        dbg("file path $filePath\n");
        
        if (!file_exists($filePath))
            fatal('Resource not accessible'); 
            
        return $filePath;
    }
    
    /**
     * @param string resource name
     * @param array request data
     * @return string Path to a icon file. It looks in the 'icon' subdirectory
     *  of  the directory containing the mapfile.
     */
    public function getIconFile($resource, $requ) {
        $projectPath = '';
        if (isset($requ['pr']) && $requ['pr'] != ProjectHandler::DEFAULT_PROJECT) {
            $projectPath = ProjectHandler::PROJECT_DIR . '/' . $requ['pr'] . '/';
        }   
        if (!isset($requ['m']))
            fatal('no map id');
        $mapId = $requ['m'];
        dbg($projectPath);
        $filePath = implode('/', array(CARTOCLIENT_HOME . $projectPath, 
                        'server_conf', $mapId, 'icons', $resource));
        dbg($filePath);
        return $filePath;
    }
    
    /**
     * @param string resource name
     * @param array $_REQUEST
     * @return string Path to a file in the directory of generated images.
     */
    public function getGeneratedFile($resource, $requ) {
        return CARTOCLIENT_HOME . 'www-data/' . $resource;
    }
}

/**
 * Returns the full path to the resource being accessed.
 * @return string
 */
function getResourcePath() {
    
    // BIG FAT WARNING: analyze security risks: there is a potential filesystem
    //  access if checks are not done properly.
    
    $fileProvider = new MiniproxyFileProvider();
    
    $requ = $_REQUEST;
    
    // resource
    if (!isset($requ['r']))
        fatal('no resource to display');
    $resource = $requ['r'];

    if (strpos($resource, '..') !== false)
        fatal('unauthorized');
    if (!isset($requ['k']))
        fatal('no resource kind');
        
    switch ($requ['k']) {
        case 'h':
            return $fileProvider->getHdocsFile($resource, $requ);
        case 'i':
            return $fileProvider->getIconFile($resource, $requ);
        case 'g':
            return $fileProvider->getGeneratedFile($resource, $requ);
        default:
            fatal('unknown kind');
            break;
    }
}

/**
 * Displays a header with the content/type of the given file.
 * 
 * @param string Path of the file from which the extension is read
 */
function showMimeType($filePath) {
    // Update this list to support new mime types for resources.
    $extToMimes = array('gif' => 'image/gif',
                        'png' => 'image/png',
                        'jpg' => 'image/jpeg',
                        'css' => 'text/css',
                        'pdf' => 'application/pdf',
                        'js'  =>  'application/x-javascript');
    
    if (!preg_match('/\.([^\.]+$)/', $filePath, $match))
        fatal('Could not find extension');
    $ext = $match[1];
    if (!array_key_exists($ext, $extToMimes))
        fatal("Unknown extension $ext");
    $mime = $extToMimes[$ext];
    header('Content-Type: '. $extToMimes[$ext]);
}

/**
 * Outputs a header for the last-modified date of the given file
 *
 * @param string Path of the file to use for getting its mtime.
 */
function showLastModifiedHeader($filePath) {
    
    $last_modified = gmdate('D, d M Y H:i:s T', filemtime($filePath));
    header("Last-Modified: $last_modified GMT");
}

$filePath = getResourcePath();

if (!file_exists($filePath))
    fatal('file does not exists');
dbg("File path: $filePath");

if (substr($filePath, -3) == 'php') {
    // if this is a php script, is is included
    include $filePath;
} else {
    showMimeType($filePath);
    showLastModifiedHeader($filePath);

    echo file_get_contents($filePath);
}
?>
