<?php
/**
 * Main classes for Cartoclient
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

require_once(CARTOCOMMON_HOME . 'common/Config.php');
require_once(CARTOCOMMON_HOME . 'common/PluginManager.php');
require_once(CARTOCOMMON_HOME . 'common/MapInfo.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOCOMMON_HOME . 'common/StructHandler.php');
require_once(CARTOCOMMON_HOME . 'common/Message.php');

require_once(CARTOCLIENT_HOME . 'client/ClientPlugin.php');
require_once(CARTOCLIENT_HOME . 'client/ClientPluginHelper.php');

require_once(CARTOCLIENT_HOME . 'client/ClientProjectHandler.php');

require_once(CARTOCLIENT_HOME . 'client/Internationalization.php');

/**
 * Cartoclient exception 
 * @package Client
 */
class CartoclientException extends Exception {

}

/**
 * Stores if mainmap or keymap were clicked, and if yes where
 * @package Client
 */
class CartoForm {
    const BUTTON_NONE = 1;
    const BUTTON_MAINMAP = 2;
    const BUTTON_KEYMAP = 3;

    // FIXME: is this needed ?, or rather test **shape if not null
    /**
     * @var int
     */
    public $pushedButton;

    /**
     * @var Shape
     */
    public $mainmapShape;
    
    /**
     * @var Shape
     */
    public $keymapShape;
}

/**
 * Configuration for client side
 * @package Client
 */
class ClientConfig extends Config {

    function getKind() {
        return 'client';
    }

    /** 
     * Constructor
     *
     * If cartoserverBaseUrl is not set, tries to guess it using PHP_SELF.
     * @param ClientProjectHandler Cartoclient's project handler
     */
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
 * Configuration for client plugins
 * @package Client
 */
class ClientPluginConfig extends PluginConfig {

    function getKind() {
        return 'client';
    }

    /**
     * Returns directory where .ini are located
     *
     * Client's .ini are located directly in client_conf directory.
     * @return string path
     */
    function getPath() {
        return '';
    }

    /**
     * Constructor
     * @param string plugin name
     * @param ClientProjectHandler Cartoclient's project handler
     */
    function __construct($plugin, $projectHandler) {
        $this->basePath = CARTOCLIENT_HOME;
        parent::__construct($plugin, $projectHandler);
    }
}

/**
 * Data stored in session 
 * @package Client
 */
class ClientSession {

    /**
     * Plugins data
     */
    public $pluginStorage;
    
    /**
     * Tool currently selected
     */
    public $selectedTool;
    
    /**
     * Last request sent to server (useful for export, see
     * {@see ExportPlugin::getExport()})
     */
    public $lastMapRequest;
}

/**
 * Prefix for session key, mapId is appended before use
 */
define('CLIENT_SESSION_KEY', 'client_session_key');

/**
 * Main Cartoclient class
 * @package Client
 */
class Cartoclient {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var MapInfo
     */
    private $mapInfo;
    
    /**
     * @var ClientSession
     */
    private $clientSession;
    
    /**
     * @var CartoForm
     */
    private $cartoForm;
    
    /** 
     * @var MapResult
     */
    private $mapResult;
       
    /**
     * @var HttpRequestHandler
     */
    private $httpRequestHandler;
    
    /**
     * @var PluginManager
     */
    private $pluginManager; 
       
    /**
     * @var ClientConfig
     */
    private $config;
    
    /**
     * @var ClientProjectHandler
     */
    public $projectHandler;

    /**
     * Array of user/developer messages
     * @var array
     */
    private $messages = array();


    /**
     * Constructor
     *
     * Initializes:
     * - Project handler
     * - MapInfo cache
     * - Client objects
     * - Session
     */
    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->projectHandler = new ClientProjectHandler();
        $this->mapInfoCache = new MapInfoCache($this);
        
        $this->initializeObjects();

        session_start();
        $this->initializeSession();        
    }

    /**
     * @return ClientConfig
     */
    function getConfig() {
        return $this->config;
    }

    /**
     * @return CartoForm
     */
    function getCartoForm() {
        return $this->cartoForm;
    }

    /**
     * @return MapResult
     */
    function getMapResult() {
        return $this->mapResult;
    }

    /**
     * @return ClientSession
     */
    function getClientSession() {
        return $this->clientSession;
    }

    /**
     * @return HttpRequestHandler
     */
    function getHttpRequestHandler() {
        return $this->httpRequestHandler;
    }
    
    /**
     * @return PluginManager
     */
    function getPluginManager() {
        return $this->pluginManager;
    }

    /**
     * @param ClientSession
     */
    function setClientSession($clientSession) {
        $this->clientSession = $clientSession;
    }

    /**
     * Returns the names of core plugins
     * @return array names
     */
    private function getCorePluginNames() {

        return array('location', 'layers', 'images', 'query', 'statictools');
    }

    /**
     * Adds a message
     * @param string
     * @param int
     */
    function addMessage($message, $channel = Message::CHANNEL_USER) {

        $this->messages[] = new Message($message, $channel);
    }
    
    /**
     * @return array
     */
    function getMessages() {
        return $this->messages;
    }

    /**
     * Initializes core and normal client plugins
     */
    private function initPlugins() {

        $this->pluginManager = new PluginManager($this->projectHandler);

        $corePluginNames = $this->getCorePluginNames();

        $this->pluginManager->loadPlugins($this->config->basePath, 'coreplugins/',
                                          PluginManager::CLIENT_PLUGINS,
                                          $corePluginNames, $this);

        $pluginNames = ConfigParser::parseArray($this->config->loadPlugins);

        $this->pluginManager->loadPlugins($this->config->basePath, 'plugins/',
                                          PluginManager::CLIENT_PLUGINS,
                                          $pluginNames, $this);
    }

    /**
     * Calls plugins implementing an interface
     *
     * Interfaces are declared in {@link ClientPlugin}.
     * @param string interface name
     * @param string function name
     */
    function callPluginsImplementing($interface, $functionName) {

        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        $this->pluginManager->callPluginsImplementing($interface, $functionName, $args);
    }

    /**
     * Returns Map Info, get it from cache if not yet set
     * @see MapInfoCache
     * @return MapInfo MapInfo
     */
    function getMapInfo() {
        if (!$this->mapInfo) {
            $this->mapInfo = $this->mapInfoCache->getMapInfo($this->config->mapId);
        }
        
        return $this->mapInfo;
    }

    /**
     * Save session in a variable different for each mapId
     * @param ClientSession object to save in session 
     */
    private function saveSession($clientSession) {
    
        $this->log->debug("saving session:");
        $this->log->debug($clientSession);

        $_SESSION[CLIENT_SESSION_KEY . $this->config->mapId] = $this->clientSession;
        session_write_close();
    }

    /**
     * Creates new client session object
     * @return ClientSession object saved in session
     */
    private function createClientSession() {
        $clientSession = new ClientSession();

        // TODO: init default arguments

        return $clientSession;
    }

    /**
     * Creates new client map clicks information
     * @return CartoForm new object
     */
    private function createCartoForm() {

        $cartoForm = new CartoForm();

        // TODO: sets default cartoform arguments

        return $cartoForm;
    }

    /**
     * Initializes session
     *
     * If the mapId's session is not created yet, it is created and initialized.
     * For creation and reload, plugins are called to manage their session data.
     * @see Sessionable
     */
    private function initializeSession() {
        $clientSession = @$_SESSION[CLIENT_SESSION_KEY . $this->config->mapId];

        $this->clientSession = $clientSession;

        if ($clientSession and !array_key_exists('reset_session', $_REQUEST)) {
            $this->log->debug("Loading existing session");
            $this->callPluginsImplementing('Sessionable', 'loadSession');

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

    /**
     * Initializes map request with current mapId
     * @return MapRequest new map request
     */
    private function getMapRequest() {

        $mapRequest = new MapRequest();
        $mapRequest->mapId = $this->getConfig()->mapId;
        return $mapRequest;
    }
    
    /**
     * Calls Cartoserver service to get results
     *
     * Also checks that MapInfo is up-to-date. If not, MapInfo cache reloads it.
     * @see MapInfoCache
     * @param MapRequest the map request
     * @return MapResult result returned by server
     */
    private function getMapResultFromRequest($mapRequest) {

        $mapResult = $this->cartoserverService->getMap($mapRequest);
        $this->mapInfoCache->checkMapInfoTimestamp($mapResult->timeStamp, 
                                                    $mapRequest->mapId);
        return $mapResult;        
    }
    
    /**
     * Main method
     *
     * - Plugins initialization
     * - HTTP request handling
     * - Map request construction
     * - Server call
     * - Result handling
     * - Display
     * - Session save
     */
    private function doMain() {

        $this->callPluginsImplementing('InitUser', 'handleInit', $this->getMapInfo());
                        
        if (@$_REQUEST['posted']) {
        
            // Maps clicks cannot be modified by filters
            $this->cartoForm = 
                $this->httpRequestHandler->handleHttpRequest($this->clientSession,
                                                    $this->cartoForm);

            $request = new FilterRequestModifier($_REQUEST);
            $this->callPluginsImplementing('FilterProvider', 'filterPostRequest', $request);
            $this->callPluginsImplementing('GuiProvider', 'handleHttpPostRequest', $request->getRequest());
        } else {
            
            $request = new FilterRequestModifier($_REQUEST);
            $this->callPluginsImplementing('FilterProvider', 'filterGetRequest', $request);
            $this->callPluginsImplementing('GuiProvider', 'handleHttpGetRequest', $request->getRequest());
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

        $this->callPluginsImplementing('ServerCaller', 'initializeResult', $this->mapResult);

        $this->callPluginsImplementing('ServerCaller', 'handleResult', $this->mapResult);

        $this->log->debug("client context to display");

        $this->formRenderer->showForm($this);

        $this->callPluginsImplementing('Sessionable', 'saveSession');

        $this->saveSession($this->clientSession);
        $this->log->debug("session saved\n");
    }

    /**
     * Initializes client objects
     *
     * Initializes:
     * - Configuration
     * - I18n
     * - Plugins
     * - Cartoserver service
     * - HTTP request handler
     * - Form renderer
     */
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
     * Main entry point.
     *
     * Calls {@link Cartoclient::doMain()} with exception handling.
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
