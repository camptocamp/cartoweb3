<?php
/**
 * Main cartoserver entry point
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
 * @copyright 2005 Camptocamp SA
 * @package Server
 * @version $Id$
 */

/**
 * Root directory for server scripts
 */
if (!defined('CARTOWEB_HOME')) {
    define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');
}

require_once(CARTOWEB_HOME . 'common/Log4phpInit.php');
initializeLog4php(false);
    
require_once(CARTOWEB_HOME . 'server/MapInfoHandler.php');

require_once(CARTOWEB_HOME . 'common/Common.php');
require_once(CARTOWEB_HOME . 'common/Utils.php');
require_once(CARTOWEB_HOME . 'common/Config.php');
require_once(CARTOWEB_HOME . 'common/MapInfo.php');
require_once(CARTOWEB_HOME . 'common/StructHandler.php');
require_once(CARTOWEB_HOME . 'common/PluginManager.php');

require_once(CARTOWEB_HOME . 'server/ServerContext.php');
require_once(CARTOWEB_HOME . 'server/ServerPlugin.php');
require_once(CARTOWEB_HOME . 'server/ServerPluginHelper.php');
require_once(CARTOWEB_HOME . 'server/MapResultCache.php');
require_once(CARTOWEB_HOME . 'server/ServerAccounting.php');

/**
 * Exception to be used by the server.
 * 
 * @package Server
 */
class CartoserverException extends CartowebException {

}

/**
 * Server configuration class
 * @package Server
 */
class ServerConfig extends Config {

    /**
     * @see Config::getKind()
     */
    public function getKind() {
        return 'server';
    }

    /**
     * @see Config::getBasePath()
     */
    public function getBasePath() {
        return CARTOWEB_HOME;
    }

    /**
     * {@see Config::getProfileParameters()})
     */
    protected function getProfileParameters() {
        return array_merge(parent::getProfileParameters(), 
                        array('noMapResultCache', 'noSoapXMLCache'));   
    }

    /**
     * Constructor
     * @param ProjectHandler
     */
    public function __construct(ProjectHandler $projectHandler) {
        parent::__construct($projectHandler);
    }
}

/**
 * Server plugin configuration
 * @package Server
 */
class ServerPluginConfig extends PluginConfig {

    /**
     * Constructor
     * @param PluginBase
     * @param ProjectHandler
     */
    public function __construct($plugin, ProjectHandler $projectHandler) {
        parent::__construct($plugin, $projectHandler);
    }
    
    /**
     * @see Config::getKind()
     */
    public function getKind() {
        return 'server';
    }

    /**
     * @see Config::getBasePath()
     */
    public function getBasePath() {
        return CARTOWEB_HOME;
    }

    /**
     * @see PluginConfig::getPath()
     */
    public function getPath() {
        return $this->projectHandler->getMapName() . '/';
    }
}

/**
 * Cartoserver main class. Contains method exported to the webservice.
 * @package Server
 */
class Cartoserver extends Cartocommon {
    
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var ServerContext
     */
    private $serverContext;
    
    /**
     * @var MapResultCache
     */
    private $mapResultCache;

    /**
     * @var Benchmark_Timer 
     */
    private $timer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log = LoggerManager::getLogger(__CLASS__);
        $this->mapResultCache = new MapResultCache($this);
    }

    /**
     * @param string map id
     * @return ServerContext
     */
    public function getServerContext($mapId) {
        if ($mapId == '')
            throw new CartoserverException("Invalid map id: $mapId");

        if (!$this->serverContext ||
            $this->serverContext->getProjectHandler()->mapId != $mapId) {
            $this->serverContext = new ServerContext($mapId);
        }
        return $this->serverContext;
    }

    /**
     * Performs getMapInfo service.
     * @param string map id
     * @return MapInfo
     */
    private function doGetMapInfo($mapId) {

        $serverContext = $this->getServerContext($mapId);
        $serverContext->loadPlugins();

        return $serverContext->getMapInfo();
    }

    /**
     * Returns developper messages.
     * @return Message
     */
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

    /**
     * Checks that all plugins needed by this mapRequest are loaded. Throws
     * a fatal exception otherwise.
     * @param MapRequest The request to check for loaded needed plugins.
     */
    private function checkRequest($mapRequest) {
        foreach (get_object_vars($mapRequest) as $attr => $value) {
            if (substr($attr, -7) != 'Request') {
                continue;
            }
            $class = null;
            if (!is_null($value)) {
                $class = $value->className;
            }
            if (is_null($class) || $class == '') {
                $class = ucfirst($attr);
            }
            // Is plugin loaded ?
            if (class_exists($class)) {
                continue;
            }
            throw new CartoserverException("Plugin server class $class " .
                                           'was not loaded');                
        }
    }

    /**
     * Performs getMap service.
     * @param MapRequest
     * @return MapResult
     */
    private function doGetMap($mapRequest) {

        // serverContext init
        $serverContext = $this->getServerContext($mapRequest->mapId);
        // serverContext MapResult reset (in case of two calls)
        $serverContext->reset();
        
        if ($serverContext->isDevelMessagesEnabled() ||
            $serverContext->getConfig()->accountingOn) {
            require_once('pear/Benchmark/Timer.php');
            $this->timer = new Benchmark_Timer();
            $this->timer->start();
        }
        $serverContext->loadPlugins();
        $pluginManager = $serverContext->getPluginManager();

        // Checks request-plugin match
        $this->checkRequest($mapRequest);

        // Unserialize MapRequest
        $mapRequest = CwSerializable::unserializeObject($mapRequest, NULL, 'MapRequest');

        $serverContext->setMapRequest($mapRequest);

        $mapResult = $serverContext->getMapResult();
        $mapResult->timestamp = $serverContext->getTimestamp();

        $pluginManager->callPlugins('initialize');

        $pluginManager->callPluginsImplementing('ClientResponder', 'initializeRequest');
        $pluginManager->callPluginsImplementing('ClientResponder', 'overrideRequest',
                                                $mapRequest);

        // This is called here to handle the case where a plugin changed the
        //  mapObj in its initializeRequest method.
        $serverContext->updateStateFromMapObj();

        if ($pluginManager->getPlugin('accounting')) {
            $pluginManager->getPlugin('accounting')->doAccounting();
        }

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

        // FIXME: Yves: isn't this done twice in the image plugin too ??
        $msMapObj = $serverContext->getMapObj();  
        $imageType = $pluginManager->layers->getImageType();
        if (!empty($imageType)) {      
            $msMapObj->selectOutputFormat($imageType);
        }

        // prepare output image
        $pluginManager->images->drawMainmap($mapRequest->imagesRequest);
        
        $pluginManager->callPluginsImplementing('ClientResponder', 'handleDrawing');

        // images result
        $pluginManager->callPluginImplementing($pluginManager->images,
                                               'CoreProvider', 'handleCorePlugin');
                                               
        $pluginManager->callPluginsImplementing('ClientResponder', 'handlePostDrawing');

        $this->log->debug("result is:");
        $this->log->debug($mapResult);

        if (!is_null($this->timer)) {
            $this->timer->stop();
            Accounting::getInstance()->account('general.elapsed_time', 
                                            $this->timer->timeElapsed());
        }
        
        if ($serverContext->isDevelMessagesEnabled())
            $developerMessages = $this->getDeveloperMessages();
        else
            $developerMessages = array();
        $mapResult->serverMessages = array_merge($serverContext->getMessages(),
                    $developerMessages);
        $this->log->debug("^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");
        return $mapResult;
    }

    /**
     * Runs given function with given argument. Returns a SoapFault object
     * if an exception occured.
     * @param string function name
     * @param mixed function argument
     */
    private function callWithExceptionCheck($function, $argument) {

        try {
            return $this->$function($argument);
        } catch (Exception $exception) {
            $this->mapResultCache->setSkipCaching(true);
            return new SoapFault('Cartoserver exception', $exception->getMessage());
        }

    }

    /**
     * Performs the getMap Webservice. This method is to be used by the 
     * mapResult cache object, and should not be called directly. Use getMap()
     * for this purpose.
     * @param MapRequest
     * @return MapResult
     */
    public function cacheGetMap($mapRequest) {

        $this->log->debug('vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv');
        $this->log->debug('map request: ');
        $this->log->debug($mapRequest);

        return $this->callWithExceptionCheck('doGetMap', $mapRequest);
    }

    /**
     * getMap webservice entry point
     * @param MapRequest
     * @return MapResult
     */
    public function getMap($mapRequest) {

        return $this->mapResultCache->getMap($mapRequest);
    }

    /**
     * getMapInfo webservice entry point
     * @param string map id
     * @return MapInfo
     */
    public function getMapInfo($mapId) {
        return $this->callWithExceptionCheck('doGetMapInfo', $mapId);
    }
}

/**
 * Returns the URL of the wsdl file to be used on the server, or null if
 * no wsdl is to be used (as set in the configuration).
 * @param string The mapId for the generated wsdl
 * @param ServerConfig Server configuration
 * @return string The url of the wsdl to be used on the server.
 */
function getWsdlUrl($mapId, ServerConfig $config) {
    
    if (!$config->useWsdl)
        return null;

    if ($config->noWsdlCache) {
        // disables WSDL cache
        ini_set('soap.wsdl_cache_enabled', '0');
    }
    $wsdlCacheDir = $config->writablePath . 'wsdl_cache/';
    if (is_writable($wsdlCacheDir))
        ini_set("soap.wsdl_cache_dir", $wsdlCacheDir);

    // This is useful for command line launching of the cartserver.
    //  just put a WSDL_URL environment variable containing the url of the wsdl
    //  file to use before launching the script.
    // TODO: there should be a way to produce the wsdl without a webserver
    if (array_key_exists('WSDL_URL', $_ENV)) {
        $url = $_ENV['WSDL_URL'];
    }
    
    if (!isset($url))
        $url = (isset($_SERVER['HTTPS']) ? "https://" : "http://" ) . 
               $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
               '/cartoserver.wsdl.php';

    if (isset($mapId))
        $url .= '?mapId=' . $mapId;    
    
    return $url;
}

/**
 * Setup the SOAP server, and registers the SOAP methods.
 * @param Cartoserver the cartoserver object used for calling web services
 * @return SoapServer
 */
function setupSoapService(Cartoserver $cartoserver) {
    $log = LoggerManager::getLogger(__METHOD__);

    global $serverGlobal;
    $serverGlobal = $cartoserver;

    /**
     * Nested method for getMapInfo webservice
     */
    function getMapInfo($mapId) {
        $log = LoggerManager::getLogger(__METHOD__);
        global $serverGlobal;
        $mapInfo = $serverGlobal->getMapInfo($mapId);
        $log->debug('mapinfo');
        $log->debug($mapInfo);
        return $mapInfo;
    }

    /**
     * Nested method for getMap webservice
     */
    function getMap($mapRequest) {
        $log = LoggerManager::getLogger(__METHOD__);
        global $serverGlobal;
        return $serverGlobal->getMap($mapRequest);
    }

    if (array_key_exists('mapId', $_REQUEST))
        $mapId = $_REQUEST['mapId'];

    if (!isset($mapId)) {
        /* FIXME: Should this be fatal ? or find a right default */
        die('No mapId GET parameter given');
    }
    $projectHandler = new ServerProjectHandler($mapId);
    $config = new ServerConfig($projectHandler);

    Common::initializeCartoweb($config);

    $url = getWsdlUrl($mapId, $config);
    $options = array();
    if (is_null($url))
        $options = array('uri' => 'foo');
    $server = new SoapServer($url, $options);

    $server->addFunction('getMapInfo');
    $server->addFunction('getMap');

    return $server;
}
