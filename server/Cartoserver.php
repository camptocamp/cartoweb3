<?php
/**
 * @package Server
 * @version $Id$
 */

/**
 * Root directory for server scripts
 */
if (!defined('CARTOSERVER_HOME')) {
    define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');
}

/**
 * Root directory for common scripts
 */
if (!defined('CARTOCOMMON_HOME'))
    define('CARTOCOMMON_HOME', CARTOSERVER_HOME);

require_once(CARTOCOMMON_HOME . 'common/log4phpInit.php');
initializeLog4php(false);

function myErrorHandler($errno, $errstr, $errfile, $errline) {
    $log =& LoggerManager::getLogger(__METHOD__);
    $log->warn(sprintf("Error in php: errno: %i\n errstr: %s\n errfile: %s (line %i)", 
                       $errno, $errstr, $errfile, $errline));
}

// uncomment for special error handler
//set_error_handler("myErrorHandler");

require_once(CARTOSERVER_HOME . 'server/MapInfoHandler.php');

//require_once(CARTOSERVER_HOME . 'common/common.php');
require_once(CARTOCOMMON_HOME . 'common/Config.php');
require_once(CARTOCOMMON_HOME . 'common/MapInfo.php');
require_once(CARTOCOMMON_HOME . 'common/StructHandler.php');
require_once(CARTOCOMMON_HOME . 'common/PluginManager.php');

require_once(CARTOSERVER_HOME . 'server/ServerContext.php');
require_once(CARTOSERVER_HOME . 'server/ServerPlugin.php');

/**
 * @package Server
 */
class CartoserverException extends Exception {

    function __construct($message) {
        //$message .= var_export(debug_backtrace(), true);

        parent::__construct($message);
    }
}

/**
 * @package Server
 */
class ServerConfig extends Config {

    function getKind() {
        return 'server';
    }

    function __construct($projectHandler) {
        $this->basePath = CARTOSERVER_HOME;
        $this->projectHandler = $projectHandler;
        parent::__construct();
    }
}


/**
 * @package Server
 */
class Cartoserver {
    private $log;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    private function initializeServerContext($mapId) {

        if ($mapId == '')
            throw new CartoserverException("Invalid map id: $mapId");
        $serverContext = new ServerContext($mapId);
        return $serverContext;
    }

    private function doGetMapInfo($mapId) {

        $serverContext = $this->initializeServerContext($mapId);

        // Disabled for now
        // $serverContext->mapInfoHandler->fillDynamic($serverContext);

        return $serverContext->mapInfoHandler->getMapInfo();
    }

    private function generateImage($serverContext) {
        // TODO: generate keymap

        $serverContext->msMapObj->set('height', '500');
        $serverContext->msMapObj->set('width',  '500');
        
        //$corePlugins->imagesPlugin->drawMainmap($mapRequest->images);

        $msMainMapImage = $serverContext->msMapObj->draw();
        $mainMapImagePath = $msMainMapImage->saveWebImage();
        return $mainMapImagePath;
    }

    private function doGetMap($mapRequest) {
        $log =& LoggerManager::getLogger(__METHOD__);

        // serverContext init

        $serverContext = $this->initializeServerContext($mapRequest->mapId);
        $serverContext->setMapRequest($mapRequest);

        $mapResult = $serverContext->getMapResult();


        $serverContext->loadPlugins();
        $pluginManager = $serverContext->getPluginManager();

        // test new image generation
        //$mapResult->new_gen = $this->generateImage($serverContext);

        $pluginManager->callPlugins('getResult', ServerPlugin::TYPE_INIT);

        // images size
        // PRE_DRAW: 1) images
        $pluginManager->images->setupSizes($mapRequest->imagesRequest);

        // location
        $pluginManager->location->getResult();

        // layer selection
        $pluginManager->layers->getResult();

        $pluginManager->callPlugins('getResult', ServerPlugin::TYPE_PRE_DRAWING);

        // prepare output image
        $pluginManager->images->drawMainmap($mapRequest->imagesRequest);
        
        $pluginManager->callPlugins('getResult', ServerPlugin::TYPE_DRAWING);

        // images result
        $pluginManager->images->getResult();

        $pluginManager->callPlugins('getResult', ServerPlugin::TYPE_POST_DRAWING);

        $log->debug("result is:");
        $log->debug($mapResult);

        $log->debug("^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");
        return $mapResult;
    }

    private function callWithExceptionCheck($function, $argument) {

        try {
            return $this->$function($argument);
        } catch (CartoserverException $exception) {
            return new SoapFault('Cartoserver exception', $exception->getMessage());
        }

    }

    function getMap($mapRequest) {

        $this->log->debug("vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv");
        $this->log->debug("map request: ");
        $this->log->debug($mapRequest);

        return $this->callWithExceptionCheck('doGetMap', $mapRequest);
    }

    function getMapInfo($mapId) {
        return $this->callWithExceptionCheck('doGetMapInfo', $mapId);
    }
}

function setupSoapService($cartoserver) {
    $log =& LoggerManager::getLogger(__METHOD__);

    global $serverGlobal;
    $serverGlobal = $cartoserver;

    function getMapInfo($mapId) {
        $log =& LoggerManager::getLogger(__METHOD__);
        global $serverGlobal;
        $mapInfo = $serverGlobal->getMapInfo($mapId);
        $log->debug('mapinfo');
        $log->debug($mapInfo);
        return $mapInfo;
    }

    function getMap($mapRequest) {
        $log =& LoggerManager::getLogger(__METHOD__);
        global $serverGlobal;
        return $serverGlobal->getMap($mapRequest);
    }

    // disables WSDL cache:
    // TODO: option should be in config
    ini_set("soap.wsdl_cache_enabled", "0");

    if (array_key_exists('mapId', $_REQUEST))
        $mapId = $_REQUEST['mapId'];


    $url = (isset($_SERVER['HTTPS']) ? "https://" : "http://" ) . 
           $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
           '/cartoserver.wsdl.php';

    if (isset($mapId))
        $url .= '?mapId=' . $mapId;

    $server = new SoapServer($url);

    $server->addFunction('getMapInfo');
    $server->addFunction('getMap');

    return $server;
}

?>