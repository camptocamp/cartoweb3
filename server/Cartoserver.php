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
require_once(CARTOSERVER_HOME . 'server/ServerPluginHelper.php');
require_once(CARTOSERVER_HOME . 'server/MapResultCache.php');

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
        parent::__construct($projectHandler);
    }
}

/**
 * @package Server
 */
class ServerPluginConfig extends PluginConfig {

    function getKind() {
        return 'server';
    }

    function getPath() {
        return $this->projectHandler->getMapName() . '/';
    }

    function __construct($plugin, $projectHandler) {
        $this->basePath = CARTOSERVER_HOME;
        parent::__construct($plugin, $projectHandler);
    }
}

/**
 * @package Server
 */
class Cartoserver {
    private $log;
    
    private $serverContext;
    private $mapResultCache;
    private $timer;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->mapResultCache = new MapResultCache($this);
    }

    public function getServerContext($mapId) {
        if ($mapId == '')
            throw new CartoserverException("Invalid map id: $mapId");

        if (!$this->serverContext ||
            $this->serverContext->projectHandler->mapId != $mapId) {
            $this->serverContext = new ServerContext($mapId);
        }
        return $this->serverContext;
    }

    private function doGetMapInfo($mapId) {

        $serverContext = $this->getServerContext($mapId);
        $serverContext->getMapInfoHandler()->fillDynamic($serverContext);

        $serverContext->loadPlugins();
        $pluginManager = $serverContext->getPluginManager();

        $mapInfo = $serverContext->getMapInfoHandler()->getMapInfo();

        $pluginManager->callPluginsImplementing('InitProvider', 'getInit');
        
        return $mapInfo;
    }

    private function generateImage() {
        // TODO: generate keymap

        $serverContext = $this->getServerContext($mapId);
        $serverContext->getMapObj()->set('height', '500');
        $serverContext->getMapObj()->set('width',  '500');
        
        //$corePlugins->imagesPlugin->drawMainmap($mapRequest->images);

        $msMainMapImage = $serverContext->getMapObj()->draw();
        $mainMapImagePath = $msMainMapImage->saveWebImage();
        return $mainMapImagePath;
    }

    private function getDeveloperMessages() {

        $messages = array();
        if (isset($GLOBALS['saved_post_id']))
            $messages[] = 'saved post id is ' . $GLOBALS['saved_post_id'];
        
        if (!is_null($this->timer)) {
            $this->timer->stop();
            $messages[] = sprintf('getMap request time: %f (wrong if ' .
                    'mapResult cache hit)', $this->timer->timeElapsed());    
        }
        
        $serverMessages = array();
        foreach ($messages as $msg) {
            $serverMessages[] = new Message($msg, Message::CHANNEL_DEVELOPER);
        }
        return $serverMessages;
    }

    private function doGetMap($mapRequest) {
        $log =& LoggerManager::getLogger(__METHOD__);

        // serverContext init
        $serverContext = $this->getServerContext($mapRequest->mapId);
        // serverContext MapResult reset (in case of two calls)
        $serverContext->reset();
        
        if ($serverContext->isDevelMessagesEnabled()) {
            require_once('pear/Benchmark/Timer.php');
            $this->timer = new Benchmark_Timer();
            $this->timer->start();
        }
        $serverContext->loadPlugins();
        $pluginManager = $serverContext->getPluginManager();

        // Unserialize MapRequest
        $mapRequest = Serializable::unserializeObject($mapRequest, NULL, 'MapRequest');

        $serverContext->setMapRequest($mapRequest);

        $mapResult = $serverContext->getMapResult();
        $mapResult->timeStamp = $serverContext->getTimeStamp();

        // test new image generation
        //$mapResult->new_gen = $this->generateImage();

        $pluginManager->callPluginsImplementing('ClientResponder', 'initializeRequest');

        // images size
        // PRE_DRAW: 1) images
        $pluginManager->images->setupSizes($mapRequest->imagesRequest);

        // location
        $pluginManager->callPluginImplementing($pluginManager->location,
                                               'CoreProvider', 'handleCorePlugin');

        // layer selection
        $pluginManager->callPluginImplementing($pluginManager->layers,
                                               'CoreProvider', 'handleCorePlugin');

        $pluginManager->callPluginsImplementing('ClientResponder', 'handlePreDrawing');

        // prepare output image
        $pluginManager->images->drawMainmap($mapRequest->imagesRequest);
        
        $pluginManager->callPluginsImplementing('ClientResponder', 'handleDrawing');

        // images result
        $pluginManager->callPluginImplementing($pluginManager->images,
                                               'CoreProvider', 'handleCorePlugin');
                                               
        $pluginManager->callPluginsImplementing('ClientResponder', 'handlePostDrawing');

        $log->debug("result is:");
        $log->debug($mapResult);
        
        if ($serverContext->isDevelMessagesEnabled())
            $developerMessages = $this->getDeveloperMessages();
        else
            $developerMessages = array();
        $mapResult->serverMessages = array_merge($serverContext->getMessages(),
                    $developerMessages);
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

    function cacheGetMap($mapRequest) {

        $this->log->debug("vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv");
        $this->log->debug("map request: ");
        $this->log->debug($mapRequest);

        return $this->callWithExceptionCheck('doGetMap', $mapRequest);
    }

    function getMap($mapRequest) {

        return $this->mapResultCache->getMap($mapRequest);
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

    if (array_key_exists('mapId', $_REQUEST))
        $mapId = $_REQUEST['mapId'];

    $port = '';
    if (!isset($mapId)) {
        /* FIXME: Should this be fatal ? or find a right default */
        die('No mapId GET parameter given');
    }
    $projectHandler = new ServerProjectHandler($mapId);
    $config = new ServerConfig($projectHandler);
    if ($config->soapBrokenPortInfo) {
        $port = ':' . $config->soapBrokenPortInfo;
    }
    if (!$config->noWsdlCache) {
        // disables WSDL cache
        ini_set('soap.wsdl_cache_enabled', '0');
    }
    $wsdlCacheDir = $config->writablePath . 'wsdl_cache/';
    if (is_writable($wsdlCacheDir))
        ini_set("soap.wsdl_cache_dir", $wsdlCacheDir);

    initializeCartoweb($config);

    // This is useful for command line launching of the cartserver.
    //  just put a WSDL_URL environment variable containing the url of the wsdl
    //  file to use before launching the script.
    // TODO: there should be a way to produce the wsdl without a webserver
    if (array_key_exists('WSDL_URL', $_ENV)) {
        $url = $_ENV['WSDL_URL'];
    }
    
    if (!isset($url))
        $url = (isset($_SERVER['HTTPS']) ? "https://" : "http://" ) . 
               $_SERVER['HTTP_HOST'] . $port . dirname($_SERVER['PHP_SELF']) . 
               '/cartoserver.wsdl.php';

    if (isset($mapId))
        $url .= '?mapId=' . $mapId;

    $server = new SoapServer($url);

    $server->addFunction('getMapInfo');
    $server->addFunction('getMap');

    return $server;
}

?>