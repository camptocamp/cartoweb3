<?php
/**
 * @package Client
 * @version $Id$
 */

/**
 * Root directory for common scripts
 */
if (!defined('CARTOCOMMON_HOME'))
    define('CARTOCOMMON_HOME', CARTOCLIENT_HOME);

define('LOG4PHP_CONFIGURATION', CARTOCLIENT_HOME . 
       'client_conf/cartoclientLogger.properties');

require_once('log4php/LoggerManager.php');

// NOTE: autoload mechanism can not be used there, because of classes needed while
//  session unserialization

require_once(CARTOCLIENT_HOME . 'client/CartoserverService.php');
require_once(CARTOCLIENT_HOME . 'client/HttpRequestHandler.php');
require_once(CARTOCLIENT_HOME . 'client/FormRenderer.php');
require_once(CARTOCLIENT_HOME . 'client/FormRenderer.php');

require_once(CARTOCOMMON_HOME . 'common/common.php');
require_once(CARTOCOMMON_HOME . 'common/Config.php');
require_once(CARTOCOMMON_HOME . 'common/PluginManager.php');
require_once(CARTOCOMMON_HOME . 'common/MapInfo.php');
require_once(CARTOCOMMON_HOME . 'common/StructHandler.php');
require_once(CARTOCLIENT_HOME . 'client/ClientPlugin.php');

require_once(CARTOCLIENT_HOME . 'coreplugins/project/client/ClientProjectHandler.php');

/**
 * @package Client
 */
class CartoclientException extends Exception {

}

/**
 * @package Client
 */
class CartoForm {
    const BUTTON_NONE = 1;
    const BUTTON_MAINMAP = 2;
    const BUTTON_KEYMAP = 3;

    // FIXME: is this needed ?, or rather test **shape if not null
    public $pushedButton;

    public $mainmapShape;
    public $keymapShape;

    public $plugins;
}

/**
 * @package Client
 */
class ClientConfig extends Config {

    function getKind() {
        return 'client';
    }

    function __construct() {
        $this->basePath = CARTOCLIENT_HOME;
        $this->projectHandler = new ClientProjectHandler();
        parent::__construct();
    }
}

/**
 * @package Client
 */
class ClientSession {
    public $pluginStorage;
    
    // ui specific
    public $selectedTool;
}

define('CLIENT_SESSION_KEY', 'client_session_key');

/**
 * @package Client
 */
class Cartoclient {
    private $log;

    private $mapInfo;
    private $clientSession;
    private $cartoForm;
       
    private $httpRequestHandler;
    private $pluginManager; 
       
    private $config;

    function getConfig() {
        return $this->config;
    }

    function getCartoForm() {
        return $this->cartoForm;
    }

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getClientSession() {
        return $this->clientSession;
    }

    function getHttpRequestHandler() {
        return $this->httpRequestHandler;
    }
    
    function getPluginManager() {
        return $this->pluginManager;
    }

    function setClientSession($clientSession) {
        $this->clientSession = $clientSession;
    }

    private function getCorePluginNames() {

        return array('location', 'layers', 'images', 'query', 'project');
    }

    private function initPlugins() {

        // Two sets of plugins : 
        // in INCLUDE/cartoclient/plugins
        // in $LOCAL_PLUGINS

        $this->pluginManager = new PluginManager();

        $corePluginNames = $this->getCorePluginNames();

        $this->pluginManager->loadPlugins($this->config->basePath . 'coreplugins/',
                                          PluginManager::CLIENT_PLUGINS, $corePluginNames,         
                                          $this);

        $pluginNames = ConfigParser::parseArray($this->config->loadPlugins);

        $this->pluginManager->loadPlugins($this->config->basePath . 'plugins/',
                                          PluginManager::CLIENT_PLUGINS, $pluginNames,
                                          $this);
    }

    function callPlugins($functionName) {

        $args = func_get_args();
        array_shift($args);
        $this->pluginManager->callPlugins($functionName, $args);
    }

    function getMapInfo() {
        if ($this->mapInfo) {
            return $this->mapInfo;
        }

        // TODO: have a mechanism to store mapinfo on hard storage
        $mapInfo = $this->cartoserverService->getMapInfo(
            $this->config->mapId);

        if (!$this->config->cartoserverDirectAccess) 
            $mapInfo = Serializable::unserializeObject($mapInfo, NULL, 'MapInfo');
        
        $this->mapInfo = $mapInfo; 
        return $mapInfo;
    }

    private function saveSession($clientSession) {
    
        $this->log->debug("saving session:");
        $this->log->debug($clientSession);

        $_SESSION[CLIENT_SESSION_KEY] = $this->clientSession;
        session_write_close();
    }

    private function createClientSession() {
        $clientSession = new ClientSession();

        // TODO: init default arguments

        return $clientSession;
    }

    private function createCartoForm() {

        $cartoForm = new CartoForm();

        // TODO: sets default cartoform arguments

        return $cartoForm;
    }

    //  case one : first time -> create Session
    //                               createClientSession, ...
    //  case two:  second time -> load Session
    //                               loadClientSession, ...
    private function initializeSession() {

        $clientSession = @$_SESSION[CLIENT_SESSION_KEY];
        $this->clientSession = $clientSession;

        if ($clientSession and !array_key_exists('reset_session', $_REQUEST)) {
            $this->log->debug("Loading existing session");

            $this->callPlugins('doLoadSession');

        } else {
            $this->log->debug("creating new  session");

            $_SESSION = array();
            $_REQUEST = array();
            session_destroy();
            session_start();
            
            $this->clientSession = $this->createClientSession();

            $mapInfo = $this->mapInfo;
            $mapStates = $this->mapInfo->initialMapStates;
        
            if (empty($mapStates))
                throw new CartoclientException('No initial map states defined' 
                                . ' in server configuration');
        
            
            $states = array_values($mapStates);
            $initialMapState = $states[0];

            if (@$this->config->initialMapStateId)
                $initialMapState = $mapInfo->getInitialMapStateById( 
                                $this->config->initialMapStateId);
            if ($initialMapState == NULL)
                throw new CartoclientException("cant find initial map state " .
                        $this->config->initialMapStateId);
            
            $this->callPlugins('createSession', $this->mapInfo, $initialMapState);
        }

        $this->cartoForm = $this->createCartoForm();
    }

    private function getMapRequest() {

        $mapRequest = new MapRequest();
        $mapRequest->mapId = $this->getConfig()->mapId;
        return $mapRequest;
    }
    
    private function doMain() {

        $this->mapInfo = $this->getMapInfo();
        $this->initializeSession();
        
        if (@$_REQUEST['posted']) {
            $this->cartoForm = 
                $this->httpRequestHandler->handleHttpRequest($this->clientSession,
                                                    $this->cartoForm);
            $this->callPlugins('handleHttpRequest', $_REQUEST);
        } 
        
        $mapRequest = $this->getMapRequest();
        $this->callPlugins('buildMapRequest', $mapRequest);

        $this->log->debug("maprequest:");
        $this->log->debug($mapRequest);

        $mapResult = $this->cartoserverService->getMap($mapRequest);

        // TODO: unserialize result object

        $this->log->debug("mapresult:");
        $this->log->debug($mapResult);

        $this->callPlugins('dohandleMapResult', $mapResult);

        $this->log->debug("client context to display");

        $this->formRenderer->showForm($this);

        $this->callPlugins('doSaveSession');

        $this->saveSession($this->clientSession);
        $this->log->debug("session saved\n");
    }

    private function initializeObjects() {

        $this->config = new ClientConfig();

        $this->log->debug("client context loaded (from session, or new)");

        // plugins
        $this->initPlugins();

        // initialize objects
        $this->cartoserverService = new CartoserverService($this);
        $this->httpRequestHandler = new HttpRequestHandler($this);
        $this->formRenderer = new FormRenderer($this);
    }
    
    /**
     * Main entry point. Session is started there, so that nothing
     * should be printed before calling this.
     */
    function main() {

        session_start();
        
        //echo "<pre>";
        
        $this->log->debug("request is : ");
        $this->log->debug($_REQUEST);

        $this->initializeObjects();
        
        try {
            $this->doMain();
        } catch (Exception $exception) {
            $this->formRenderer->showFailure($exception);
        }
    }
}
?>