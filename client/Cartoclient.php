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

require_once(CARTOCOMMON_HOME . 'common/log4phpInit.php');
initializeLog4php(true);

require_once(CARTOCLIENT_HOME . 'client/MapInfoCache.php');
require_once(CARTOCLIENT_HOME . 'client/CartoserverService.php');
require_once(CARTOCLIENT_HOME . 'client/HttpRequestHandler.php');
require_once(CARTOCLIENT_HOME . 'client/FormRenderer.php');
require_once(CARTOCLIENT_HOME . 'client/FormRenderer.php');

require_once(CARTOCOMMON_HOME . 'common/Config.php');
require_once(CARTOCOMMON_HOME . 'common/PluginManager.php');
require_once(CARTOCOMMON_HOME . 'common/MapInfo.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOCOMMON_HOME . 'common/StructHandler.php');
require_once(CARTOCLIENT_HOME . 'client/ClientPlugin.php');

require_once(CARTOCLIENT_HOME . 'client/ClientProjectHandler.php');

require_once(CARTOCLIENT_HOME . 'client/Internationalization.php');

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

    function __construct($projectHandler) {
        $this->basePath = CARTOCLIENT_HOME;
        parent::__construct($projectHandler);
        
        if (is_null($this->cartoserverBaseUrl)) {
            if (empty($_SERVER['PHP_SELF']))
                throw new CartoclientException('You need to set cartoserverBaseUrl ' .
                        'in client.ini');
            $url = (isset($_SERVER['HTTPS']) ? "https://" : "http://" ) . 
                    $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/';
            $this->cartoserverBaseUrl = $url;
        } else if (substr($this->cartoserverBaseUrl, -1) != '/') {
            $this->cartoserverBaseUrl .= '/';
        }
    }
}

/**
 * @package Client
 */
class ClientPluginConfig extends PluginConfig {

    function getKind() {
        return 'client';
    }

    function getPath() {
        return '';
    }

    function __construct($plugin, $projectHandler) {
        $this->basePath = CARTOCLIENT_HOME;
        parent::__construct($plugin, $projectHandler);
    }
}

/**
 * @package Client
 */
class ClientSession {
    public $pluginStorage;
    
    // ui specific
    public $selectedTool;
    
    // last request
    public $lastMapRequest;
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
    private $mapResult;
       
    private $httpRequestHandler;
    private $pluginManager; 
       
    private $config;
    
    public $projectHandler;

    function getConfig() {
        return $this->config;
    }

    function getCartoForm() {
        return $this->cartoForm;
    }

    function getMapResult() {
        return $this->mapResult;
    }

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->projectHandler = new ClientProjectHandler();
        $this->mapInfoCache = new MapInfoCache($this);
        
        $this->initializeObjects();

        session_start();
        $this->initializeSession();        
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

        return array('location', 'layers', 'images', 'query', 'statictools');
    }

    private function initPlugins() {

        // Two sets of plugins : 
        // in INCLUDE/cartoclient/plugins
        // in $LOCAL_PLUGINS

        $this->pluginManager = new PluginManager($this->projectHandler);

        $corePluginNames = $this->getCorePluginNames();

        $this->pluginManager->loadPlugins($this->config->basePath, 'coreplugins/',
                                          PluginManager::CLIENT_PLUGINS, $corePluginNames, $this);

        $pluginNames = ConfigParser::parseArray($this->config->loadPlugins);

        $this->pluginManager->loadPlugins($this->config->basePath, 'plugins/',
                                          PluginManager::CLIENT_PLUGINS, $pluginNames, $this);
    }

    function callPluginsImplementing($interfaces, $functionName) {

        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        $this->pluginManager->callPluginsImplementing($interfaces, $functionName, $args);
    }

    function callPlugins($functionName) {

        $args = func_get_args();
        array_shift($args);
        $this->pluginManager->callPlugins($functionName, $args);
    }

    function getMapInfo() {
        if (!$this->mapInfo) {
            $this->mapInfo = $this->mapInfoCache->getMapInfo($this->config->mapId);
        }
        
        return $this->mapInfo;
    }

    private function saveSession($clientSession) {
    
        $this->log->debug("saving session:");
        $this->log->debug($clientSession);

        $_SESSION[CLIENT_SESSION_KEY . $this->config->mapId] = $this->clientSession;
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
        $clientSession = @$_SESSION[CLIENT_SESSION_KEY . $this->config->mapId];

        $this->clientSession = $clientSession;

        if ($clientSession and !array_key_exists('reset_session', $_REQUEST)) {
            $this->log->debug("Loading existing session");
            $this->callPluginsImplementing('Sessionable', 'doLoadSession');

        } else {
            $this->log->debug("creating new  session");

            //$_SESSION = array();
            $_REQUEST = array();
            //session_destroy();
            //session_start();
            
            $this->clientSession = $this->createClientSession();

            $mapStates = $this->getMapInfo()->initialMapStates;
        
            if (empty($mapStates))
                throw new CartoclientException('No initial map states defined' 
                                . ' in server configuration');
        
            
            $states = array_values($mapStates);
            $initialMapState = $states[0];

            if (@$this->config->initialMapStateId)
                $initialMapState = $this->getMapInfo()->getInitialMapStateById( 
                                $this->config->initialMapStateId);
            if ($initialMapState == NULL)
                throw new CartoclientException("cant find initial map state " .
                        $this->config->initialMapStateId);
            
            $this->callPluginsImplementing('Sessionable', 'createSession',
                                           $this->getMapInfo(), $initialMapState);
        }

        $this->cartoForm = $this->createCartoForm();
    }

    private function getMapRequest() {

        $mapRequest = new MapRequest();
        $mapRequest->mapId = $this->getConfig()->mapId;
        return $mapRequest;
    }
    
    private function getMapResultFromRequest($mapRequest) {

        $mapResult = $this->cartoserverService->getMap($mapRequest);
        $this->mapInfoCache->checkMapInfoTimestamp($mapResult->timeStamp, 
                                                    $mapRequest->mapId);
        return $mapResult;        
    }
    
    private function doMain() {

        $this->callPluginsImplementing('InitProvider', 'dohandleInit', $this->getMapInfo());
                        
        if (@$_REQUEST['posted']) {
            $this->cartoForm = 
                $this->httpRequestHandler->handleHttpRequest($this->clientSession,
                                                    $this->cartoForm);
            $this->callPlugins('handleHttpRequest', $_REQUEST);
        } 
        
        $mapRequest = $this->getMapRequest();
        $this->callPluginsImplementing('ServerCaller', 'buildMapRequest', $mapRequest);

        // Save mapRequest for future use
        $this->clientSession->lastMapRequest = $mapRequest;

        $this->log->debug("maprequest:");
        $this->log->debug($mapRequest);

        $this->mapResult = $this->getMapResultFromRequest($mapRequest);

        $this->log->debug("mapresult:");
        $this->log->debug($this->mapResult);

        $this->callPluginsImplementing('ServerCaller', 'internalHandleResult', $this->mapResult);

        $this->log->debug("client context to display");

        $this->formRenderer->showForm($this);

        $this->callPluginsImplementing('Sessionable', 'doSaveSession');

        $this->saveSession($this->clientSession);
        $this->log->debug("session saved\n");
    }

    private function initializeObjects() {

        $this->config = new ClientConfig($this->projectHandler);

        $this->log->debug("client context loaded (from session, or new)");

        // Internationalization
        I18n::init($this->config);

        // plugins
        $this->initPlugins();

        // initialize objects
        $this->cartoserverService = new CartoserverService($this->getConfig());
        $this->httpRequestHandler = new HttpRequestHandler($this);
        $this->formRenderer = new FormRenderer($this);
    }
        
    /**
     * Main entry point. Session is started there, so that nothing
     * should be printed before calling this.
     */
    function main() {
        
        //echo "<pre>";
        
        $this->log->debug("request is : ");
        $this->log->debug($_REQUEST);

        initializeCartoweb($this->config);
        
        try {
            $this->doMain();
        } catch (Exception $exception) {
            $this->formRenderer->showFailure($exception);
        }
    }
}
?>
