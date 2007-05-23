<?php
/**
 * Main classes for Cartoclient
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
 * @package Client
 * @version $Id$
 */

require_once(CARTOWEB_HOME . 'common/Log4phpInit.php');
initializeLog4php(true);

require_once(CARTOWEB_HOME . 'client/AjaxHelper.php');
require_once(CARTOWEB_HOME . 'client/ClientMapInfoCache.php');
require_once(CARTOWEB_HOME . 'client/CartoserverService.php');
require_once(CARTOWEB_HOME . 'client/HttpRequestHandler.php');
require_once(CARTOWEB_HOME . 'client/FormRenderer.php');
require_once(CARTOWEB_HOME . 'client/ClientPlugin.php');
require_once(CARTOWEB_HOME . 'client/ClientPluginHelper.php');
require_once(CARTOWEB_HOME . 'client/ClientProjectHandler.php');
require_once(CARTOWEB_HOME . 'client/Internationalization.php');
require_once(CARTOWEB_HOME . 'client/Views.php');
require_once(CARTOWEB_HOME . 'client/ClientAccounting.php');

require_once(CARTOWEB_HOME . 'common/Common.php');
require_once(CARTOWEB_HOME . 'common/Utils.php');
require_once(CARTOWEB_HOME . 'common/Config.php');
require_once(CARTOWEB_HOME . 'common/PluginManager.php');
require_once(CARTOWEB_HOME . 'common/ResourceHandler.php');
require_once(CARTOWEB_HOME . 'common/SecurityManager.php');
require_once(CARTOWEB_HOME . 'common/MapInfo.php');
require_once(CARTOWEB_HOME . 'common/Request.php');
require_once(CARTOWEB_HOME . 'common/StructHandler.php');
require_once(CARTOWEB_HOME . 'common/Message.php');
require_once(CARTOWEB_HOME . 'common/Encoding.php');

/**
 * Cartoclient exception 
 * @package Client
 */
class CartoclientException extends CartowebException {

}

/**
 * Stores if mainmap or keymap were clicked, and if yes where
 * @package Client
 */
class CartoForm {
    
    const BUTTON_NONE = 1;
    const BUTTON_MAINMAP = 2;
    const BUTTON_KEYMAP = 3;
    const BUTTON_APPLICATION = 4;
    
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

    /**
     * Returns config type
     * @return string
     */
    public function getKind() {
        return 'client';
    }

    /**
     * Returns base path
     * @return string
     */
    public function getBasePath() {
        return CARTOWEB_HOME;
    }

    /**
     * {@see Config::getProfileParameters()})
     */
    protected function getProfileParameters() {
        return array_merge(parent::getProfileParameters(), array('smartyCompileCheck'));   
    }

    /** 
     * Constructor
     *
     * @param ClientProjectHandler Cartoclient's project handler
     */
    public function __construct($projectHandler) {
        parent::__construct($projectHandler);
        
        if (!$this->cartoclientBaseUrl)
            throw new CartoclientException('You need to set cartoclientBaseUrl ' .
                    'in client.ini');            
        if (!$this->cartoserverBaseUrl)
            $this->cartoserverBaseUrl = $this->cartoclientBaseUrl;            

        if (substr($this->cartoclientBaseUrl, -1) != '/') {
            $this->cartoclientBaseUrl .= '/';
        }
        if (substr($this->cartoserverBaseUrl, -1) != '/') {
            $this->cartoserverBaseUrl .= '/';
        }
    }
}

/**
 * Configuration for client plugins
 * @package Client
 */
class ClientPluginConfig extends PluginConfig {

    /**
     * Returns plugin config type
     * @return string
     */
    public function getKind() {
        return 'client';
    }

    /**
     * Returns base path
     * @return string
     */
    public function getBasePath() {
        return CARTOWEB_HOME;
    }
    
    /**
     * Returns directory where .ini are located
     *
     * Client's .ini are located directly in client_conf directory.
     * @return string path
     */
    public function getPath() {
        return '';
    }

    /**
     * Constructor
     * @param string plugin name
     * @param ClientProjectHandler Cartoclient's project handler
     */
    public function __construct($plugin, $projectHandler) {
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

    /**
     * Last result received from server (useful for export, see
     * {@see ExportPlugin::getExport()})
     */
    public $lastMapResult;
}

/**
 * Main Cartoclient class
 * @package Client
 */
class Cartoclient extends Cartocommon {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var MapInfo
     */
    private $mapInfo;

    /**
     * @var MapInfoCache
     */
    private $mapInfoCache;
    
    /**
     * @var ClientSession
     */
    private $clientSession;

    /**
     * @var string
     */
    private $sessionName;
    
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
     * @var FormRenderer
     */
    private $formRenderer;    

    /**
     * @var CartoserverService
     */
    private $cartoserverService;

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
    private $projectHandler;

    /**
     * @var ResourceHandler
     */
    private $resourceHandler;

    /**
     * Array of user/developer messages
     * @var array
     */
    private $messages = array();

    /**
     * When true, the cartoweb flow of operation will be interrupted and the 
     * html will be displayed.
     * @var boolean
     */
    private $interruptFlow = false;

    /**
     * @var ViewManager
     */
    private $viewManager;

    /**
     * Indicates if a view is to be processed.
     * @var boolean
     */
    private $viewOn = false;

    /**
     * Indicates if views device is activated.
     * @var boolean
     */
    private $viewsEnable;

    /**
     * @var string
     */
    private $outputType;

    /**
     * @var bool
     */
    private $isNewSession;

    /**
     * Boolean value allowing to bypass the saveSession() call, hence the 
     * map state remain unmodified on next call
     * @var bool
     */
    private $preventSaveSession;

    /**
     * @var InitialMapState
     */
    private $initialMapState;
    
    /**
     * Singleton
     * @var Cartoclient
     */
    private static $instance;

    /**
     * Current AJAX action, null if no AJAX action requested
     * @var string
     */
    private $ajaxAction = null;

    /**
     * HTTP parameter name for AJAX actions
     */
    const AJAXACTION_PARAM_NAME = 'ajaxActionRequest';
    
    /**
     * Output formats constants.
     */
    const OUTPUT_HTML_VIEWER = 'viewer';
    const OUTPUT_IMAGE       = 'image';

    /**
     * Prefix for session key.
     */
    const CLIENT_SESSION_KEY = 'CW3_client_session_key';

    /**
     * Constructor
     *
     * Initializes:
     * - Project handler
     * - MapInfo cache
     * - Client objects
     * - Session
     *
     * Plugins cannot call internationalization functions in constructor
     * and in preInitialize().
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        self::$instance = $this;
        
        $this->projectHandler = new ClientProjectHandler();
        
        try {
            Accounting::getInstance()->reset();
            
            if (array_key_exists('reset_session', $_POST)) {
                // POST reset is made consistent with GET behavior.
                $_REQUEST = array('reset_session' => '') + $_COOKIE;
            }

            $this->preventSaveSession = array_key_exists('prevent_save_session',
                                                         $_REQUEST);

            if (array_key_exists('mode', $_REQUEST)) {
                $this->outputType = $_REQUEST['mode'];
            } else {
                $this->outputType = self::OUTPUT_HTML_VIEWER;
            }
            
            $this->initializePlugins();

            if (!isset($GLOBALS['headless']))
                session_start();

            $this->pluginManager->callPlugins('preInitialize');

            $this->initializeObjects();

            $this->pluginManager->callPlugins('initialize');

            $this->callPluginsImplementing('InitUser', 'handleInit',
                                       $this->getMapInfo());
            
            $this->initializeSession();
            
            // Plugin initialization is called before the main() call, 
            //  to ensure security is applied when constructing Cartoclient object.
            
                    
        } catch (Exception $exception) {
            if (isset($this->formRenderer)) {
                print $this->formRenderer->showFailure($exception);
            } else {
                // form renderer not yet initialized: show a raw error message
                if (!isset($GLOBALS['headless']))
                    header('HTTP/1.1 500 Internal Server Error');
                print 'An exception in early stage occured: <pre>';
                var_export($exception);
                print '</pre>';
            }
            // Cartoclient was not initialized, exit
            exit;
        }
    }

    /**
     * Returns the instance of this class. There is only one during the
     * cartoclient lifetime.
     * @return Cartoclient
     */
    public static function getInstance() {
        if (is_null(self::$instance))
            throw new CartoclientException('Cartoclient instance ' .
                                           'not yet initialized');
        return self::$instance;
    }
        
    /**
     * @return ClientConfig
     */
    public function getConfig() {
        if (!$this->config) {
            $this->config = new ClientConfig($this->projectHandler);
        }
        return $this->config;
    }

    /**
     * @return ProjectHandler the current cartoclient project handler
     */
    public function getProjectHandler() {
        return $this->projectHandler;
    }
    
    /**
     * @return ResourceHandler the cartoclient resource handler
     */
    public function getResourceHandler() {
        if (!$this->resourceHandler) {
            $this->resourceHandler = new ResourceHandler($this->config, 
                                                $this->getProjectHandler());
        }
        return $this->resourceHandler;
    }
        
    /**
     * @return CartoForm
     */
    public function getCartoForm() {
        return $this->cartoForm;
    }

    /**
     * @return MapResult
     */
    public function getMapResult() {
        return $this->mapResult;
    }

    /**
     * @return ClientSession
     */
    public function getClientSession() {
        return $this->clientSession;
    }

    /**
     * @return HttpRequestHandler
     */
    public function getHttpRequestHandler() {
        return $this->httpRequestHandler;
    }
    
    /**
     * @return PluginManager
     */
    public function getPluginManager() {
        return $this->pluginManager;
    }

    /**
     * @return FormRenderer the current form renderer 
     */
    public function getFormRenderer() {
        return $this->formRenderer;
    }

    /**
     * @return CartoserverService
     */
    public function getCartoserverService() {
        return $this->cartoserverService;
    }

    /**
     * Tells if the Cartoclient runs in AJAX mode
     * @return bool true if the Cartoclient runs in AJAX mode,
     *              false otherwise
     */
    public function isAjaxMode() {
        return !empty($this->ajaxAction);
    }
    
    /**
     * Sets the current AJAX action
     * @return string Requested AJAX action, or null if no AJAX action requested
     */
    public function setAjaxAction($ajaxAction) {
        if (!ereg("^.+\..+$", $ajaxAction)) {
            throw new AjaxException(
                "ajaxActionRequest parameter\'s value is not correctly " .
                'formatted. It should look like: PluginName.ActionName ' .
                "(current value: '$ajaxAction')");
        }
                
        list($requestedPluginName, $requestedActionName) = 
                explode('.', $ajaxAction, 2);
        
        $requestedPluginName = strtolower($requestedPluginName{0}) 
                               . substr($requestedPluginName, 1);

        if ($this->getPluginManager()->getPlugin($requestedPluginName) == NULL) {
            throw new AjaxException(
                "Requested plugin $requestedPluginName is not loaded. " .
                'Check your AJAX call parameters ' .
                '(currently ajaxActionRequest=' . $ajaxAction . ')');      
        }

        $this->ajaxAction = $ajaxAction;
    }

    /**
     * Returns the requested AJAX action
     * @return string Requested AJAX action, or null if no AJAX action requested
     */
    public function getAjaxAction() {
        return $this->ajaxAction;
    }

    /**
     * @param ClientSession
     */
    public function setClientSession($clientSession) {
        $this->clientSession = $clientSession;
    }

    /**
     * Returns the names of core plugins
     * @return array names
     */
    public function getCorePluginNames() {
        return array_merge(parent::getCorePluginNames(), array('statictools'));   
    }

    /**
     * Returns CartoWeb version and revision strings of the Cartoclient
     * @return array
     */
    public function getClientVersion() {
        $versionFile = CARTOWEB_HOME . 'VERSION';
        if (is_readable($versionFile)) {
            $cw = simplexml_load_file($versionFile);
            return array('version'  => (string)$cw->version,
                         'revision' => (string)$cw->revision);
        }
        return array('version'  => '(unknown version)',
                     'revision' => '(unknown revision)');
    }

    /**
     * Adds a message to the list of message to display to the user
     * @param string the text of the message
     * @param int the channel identifier of the message
     */
    public function addMessage($message, $channel = Message::CHANNEL_USER) {

        $this->messages[] = new Message($message, $channel);
    }
    
    /**
     * @return array
     */
    public function getMessages() {
        return $this->messages;
    }

    /**
     * @return ViewManager
     */
    public function getViewManager() {
        if (!isset($this->viewManager)) {
            $this->viewManager = new ViewManager($this);
        }
        return $this->viewManager;
    }

    /**
     * Tells if views are enabled in configuration.
     * @return boolean
     */
    public function areViewsEnable() {
        if (!isset($this->viewsEnable)) {
            $this->viewsEnable = $this->getConfig()->viewOn;
        }
        return $this->viewsEnable;
    }

    /**
     * Returns the current output type (pdf, csv, html, etc.).
     * @return string
     */
    public function getOutputType() {
        return $this->outputType;
    }

    /**
     * Set the current output type (pdf, csv, html, etc.).
     * @param string one of the predefined output type
     */
    public function setOutputType($outputType) {
        $this->outputType = $outputType;
    }

    /**
     * Activate session bypass
     * @param bool preventSaveSession session bypassed if true
     */
    public function setPreventSaveSession($preventSaveSession) {
        $this->preventSaveSession = $preventSaveSession;
    }

    /**
     * Initializes core and normal client plugins
     */
    private function initializePlugins() {

        $this->pluginManager = new PluginManager(PluginManager::CLIENT, 
                                                 $this->projectHandler);

        $corePluginNames = $this->getCorePluginNames();

        $this->pluginManager->loadPlugins($corePluginNames, $this);

        $pluginNames = Utils::parseArray($this->getConfig()->loadPlugins);

        $this->pluginManager->loadPlugins($pluginNames, $this);
    }

    /**
     * Calls plugins implementing an interface
     *
     * Interfaces are declared in {@link ClientPlugin}.
     * @param string interface name
     * @param string function name
     */
    public function callPluginsImplementing($interface, $functionName) {

        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        $this->pluginManager->callPluginsImplementing($interface, 
                                                      $functionName, $args);
    }

    /**
     * Calls enabled plugins implementing an interface
     * A plugin is enabled if its enable level is equal or higher than
     * the given $enableLevel
     * Interfaces are declared in {@link ClientPlugin}.
     * @param int minimum enable level for a plugin to be called
     * @param string interface name
     * @param string function name
     */
    public function callEnabledPluginsImplementing($enableLevel, $interface,
                                                   $functionName) {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);
        $this->pluginManager->callEnabledPluginsImplementing($enableLevel,
                                              $interface, $functionName, $args);
    }

    /**
     * Calls a given $pluginName implementing an interface if its enable level
     * is equal or higher than the given $enableLevel
     * Interfaces are declared in {@link ClientPlugin}.
     * @param int minimum enable level for the plugin to be called
     * @param string plugin name
     * @param string interface name
     * @param string function name
     */
    public function callEnabledPluginImplementing($enableLevel, $pluginName,
                                                  $interface, $functionName) {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);
        array_shift($args);
        $this->pluginManager->callEnabledPluginImplementing($enableLevel,
                                 $pluginName, $interface, $functionName, $args);
    }
    
    /**
     * Returns the MapInfoCache
     * @return MapInfoCache
     */
    private function getMapInfoCache() {
        if (!$this->mapInfoCache) {
            $this->mapInfoCache = new ClientMapInfoCache($this);
        }
        return $this->mapInfoCache;
    }

    /**
     * Returns Map Info, get it from cache if not yet set
     * @see MapInfoCache
     * @return MapInfo MapInfo
     */
    public function getMapInfo() {
        if (!$this->mapInfo) {
            // Mapinfo requests should not be accounted
            Accounting::getInstance()->setActive(false);

            $this->mapInfo = $this->getMapInfoCache()->
                                            getMapInfo($this->config->mapId);
            Accounting::getInstance()->setActive(true);
        }
        
        return $this->mapInfo;
    }

    /**
     * Builds and returns session name.
     * @return string
     */
    public function getSessionName() {
        if (!isset($this->sessionName)) {
            
            $this->sessionName = sprintf('%s.%s.%s',
                                         self::CLIENT_SESSION_KEY,
                                         $this->projectHandler->getProjectName(),
                                         $this->config->mapId);
            
            if ($this->config->sessionNameSuffix) {
                $suffixes = Utils::parseArray($this->config->sessionNameSuffix);
                foreach ($suffixes as $suffix) {
                    $data = explode(':', $suffix);
                    if (count($data) != 2) continue;
                    switch ($data[0]) {
                        case 'str':
                            $this->sessionName .= $data[1];
                            break;

                        case 'conf':
                            $this->sessionName .= $this->config->$data[1];
                            break;

                        case 'env':
                            if (array_key_exists($data[1], $_ENV)) {
                                $this->sessionName .= $_ENV[$data[1]];
                            }
                            break;
                    }
                }
            }
        }
        return $this->sessionName;
    }

    /**
     * Saves session data in a different container for each mapId
     */
    public function saveSession() {

        if ($this->preventSaveSession) {
            $this->log->debug('session save bypassed');
            return;
        }
        
        $this->callEnabledPluginsImplementing(ClientPlugin::ENABLE_LEVEL_PROCESS,
                                              'Sessionable',
                                              'saveSession');

        $this->log->debug('saving session:');
        $this->log->debug($this->clientSession);

        $_SESSION[$this->getSessionName()] = $this->clientSession;
        session_write_close();

        if ($this->areViewsEnable() && $this->isNewSession &&
            !file_exists($this->getViewManager()->getSessionCacheLocation())) {
            // Caches some initial session data for views use.
            $this->getViewManager()
                 ->makeSessionCache($_SESSION[$this->getSessionName()]); 
        }
        
        $this->log->debug("session saved\n");
    }

    /**
     * Initializes session
     *
     * If the mapId's session is not created yet, it is created and initialized.
     * For creation and reload, plugins are called to manage their session data.
     * @see Sessionable
     */
    private function initializeSession() {
        $clientSession = @$_SESSION[$this->getSessionName()];
        
        $this->clientSession = $clientSession;

        if ($this->viewOn) {
            $this->log->debug('Handling views');
            $this->getViewManager()->handleView($this->clientSession);
        }
        
        $this->isNewSession = !$this->clientSession || 
                              array_key_exists('reset_session', $_REQUEST);
        if (!$this->isNewSession) {
            $this->log->debug('Loading existing session');
            $this->callPluginsImplementing('Sessionable', 'loadSession');

        } else {
            $this->log->debug('creating new session');
            $this->clientSession = new ClientSession();
            $this->callPluginsImplementing('Sessionable', 'createSession',
                                           $this->getMapInfo(), 
                                           $this->getInitialMapState());
        }

        $this->cartoForm = new CartoForm();
    }

    /**
     * Retrieves initialMapState data depending on detected initialMapStateId.
     *
     * initialMapStateId is determined using (by order of priority):
     * $_REQUEST, $_ENV, client.ini, auto (first initialMapState found).
     * @return InitialMapState
     */
    public function getInitialMapState() {
        if (!isset($this->initialMapState)) {

            $mapStates = $this->getMapInfo()->initialMapStates;
        
            if (empty($mapStates))
                throw new CartoclientException('No initial map states defined' 
                                . ' in server configuration');
            
            // detects initialMapState to use:
            if (!empty($_REQUEST['initialState'])) {
                // tries REQUEST (GET => OK, POST => ??)
                // TODO: what if REQUEST'ed initialMapState does not exist ?
                // currently it generates a failure...
                $stateId = $_REQUEST['initialState'];
                $stateSource = 'REQUEST variable';
                $this->initialMapState = $this->getMapInfo()
                                        ->getInitialMapStateById($stateId);
            
            } elseif (!empty($_ENV['CW3_INITIAL_MAP_STATE_ID'])) { 
                // tries ENV
                $stateId = $_ENV['CW3_INITIAL_MAP_STATE_ID'];
                $stateSource = 'ENV variable';
                $this->initialMapState = $this->getMapInfo()
                                        ->getInitialMapStateById($stateId);
            
            } elseif (@$this->config->initialMapStateId) {
                // tries client.ini
                $stateId = $this->config->initialMapStateId;
                $stateSource = 'INI file';

                $this->initialMapState = $this->getMapInfo()
                                        ->getInitialMapStateById($stateId);
            
            } else {
                // uses first initialMapState available
                $statesIds = array_keys($mapStates);
                $stateId = $statesIds[0];
                $states = array_values($mapStates);
                $this->initialMapState = $states[0];
                $stateSource = 'auto';
            }
            
            if ($this->initialMapState == NULL) {
                throw 
                   new CartoclientException("cant find initial map state $stateId");
            }

            $this->log->debug("Using '$stateId' initialMapState,"
                              . " detected from $stateSource.");
        }
        return $this->initialMapState;
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
        $this->getMapInfoCache()->checkMapInfoTimestamp($mapResult->timestamp);
        return $mapResult;        
    }
        
    /**
     * Tells the Cartoclient that the normal control flow has to be interrupted.
     * When true, the server will not be called, and the final template drawing
     * step is invoked.
     * 
     * @param boolean true if the control flow has to be interrupted.
     */    
    public function setInterruptFlow($interruptFlow) {
        $this->interruptFlow = $interruptFlow;
    }

    /**
     * Returns true if the flow of operation has to be interrupted, and the
     * template displayed without calling server. 
     * @see #setInterruptFlow()
     */
    public function isInterruptFlow() {
        return $this->interruptFlow;
    }

    /**
     * Returns current base URL.
     * @return string
     */
    public function getSelfUrl() {
        return './' . basename($_SERVER['PHP_SELF']);
    }
    
    /**
     * Returns whether the current user has privileges to access cartoweb. It
     * reads the securityAllowedRoles variable in client.ini
     */
    public function clientAllowed() {
        // If lots of client security checks are to done there, make a new
        /// ClientSecurityChecker object 
        if (!$this->config->securityAllowedRoles)
            return true;
        $allowedRoles = Utils::parseArray($this->config->securityAllowedRoles);
        return SecurityManager::getInstance()->hasRole($allowedRoles);
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
     * @return string
     */
    private function doMain() {

        if (isset($_REQUEST['posted'])) {        
            if ($_REQUEST['posted'] != '0') {
        
                // Checks cookies
                if (!isset($_COOKIE['TestCookie'])) {
                    $this->setInterruptFlow(true);
                    $this->formRenderer->setCustomForm('nocookies.tpl');
                }

                // Maps clicks cannot be modified by filters
                $this->cartoForm = 
                    $this->httpRequestHandler->handleHttpRequest(
                                                        $this->clientSession,
                                                        $this->cartoForm);

                $request = new FilterRequestModifier($_REQUEST);
                $this->callEnabledPluginsImplementing(
                                             ClientPlugin::ENABLE_LEVEL_PROCESS,
                                             'FilterProvider',
                                             'filterPostRequest',
                                             $request);
                $this->callEnabledPluginsImplementing(
                                             ClientPlugin::ENABLE_LEVEL_PROCESS,
                                             'GuiProvider', 
                                             'handleHttpPostRequest',
                                             $request->getRequest());
            }
        } else {
    
            // Tries to set a cookie
            setcookie('TestCookie', 'OK');            
                        
            $request = new FilterRequestModifier($_REQUEST);
            $this->callEnabledPluginsImplementing(
                                             ClientPlugin::ENABLE_LEVEL_PROCESS,
                                             'FilterProvider',
                                             'filterGetRequest',
                                             $request);
            $this->callEnabledPluginsImplementing(
                                             ClientPlugin::ENABLE_LEVEL_PROCESS,
                                             'GuiProvider',
                                             'handleHttpGetRequest',
                                             $request->getRequest());
        }
        
        // If flow is not interrupted and client not allowed, 
        //   then display unauthorized message
        //  page.
        if (!$this->isInterruptFlow() && !$this->clientAllowed()) {
            $this->setInterruptFlow(true);
            $this->formRenderer->setCustomForm('unauthorized.tpl');
        }
        
        // If the flow has to be interrupted (no cartoserver call), 
        //  then this method stops here
        if ($this->isInterruptFlow()) {
            $output = $this->formRenderer->render();
            $this->saveSession();
            return $output;
        }

        $mapRequest = $this->getMapRequest();
        $this->callEnabledPluginsImplementing(
                                          ClientPlugin::ENABLE_LEVEL_SERVERCALL,
                                          'ServerCaller', 'buildRequest',
                                          $mapRequest);
        $this->callEnabledPluginsImplementing(
                                          ClientPlugin::ENABLE_LEVEL_SERVERCALL,
                                          'ServerCaller', 'overrideRequest',
                                          $mapRequest);

        // Saves mapRequest for future use
        $this->clientSession->lastMapRequest = 
            StructHandler::deepClone($mapRequest);

        $this->log->debug('maprequest:');
        $this->log->debug($mapRequest);

        $this->mapResult = $this->getMapResultFromRequest($mapRequest);

        // Saves mapResult for future use
        $this->clientSession->lastMapResult = 
            StructHandler::deepClone($this->mapResult);

        $this->log->debug('mapresult:');
        $this->log->debug($this->mapResult);

        $this->callEnabledPluginsImplementing(
                                          ClientPlugin::ENABLE_LEVEL_SERVERCALL,
                                          'ServerCaller', 'initializeResult',
                                          $this->mapResult);
        $this->callEnabledPluginsImplementing(
                                          ClientPlugin::ENABLE_LEVEL_SERVERCALL,
                                          'ServerCaller', 'handleResult',
                                          $this->mapResult);

        $this->log->debug('client context to display');

        if (!$this->isInterruptFlow() && 
            $this->outputType == self::OUTPUT_IMAGE) {
            // Returns raw mainmap image
            $this->getPluginManager()->getPlugin('images')->outputMap();
            $output = '';
        } else {
            $output = $this->formRenderer->render();
        }

        $this->saveSession();
        
        return $output;
    }

    /**
     * Prepares the AJAX mode by setting plugins enable level
     * according the given ajaxAction.
     * @param string AJAX action
     */    
    private function prepareAjax($ajaxAction) {

        $this->setAjaxAction($ajaxAction);

        // Determines what plugin triggered what action 
        list($requestedPluginName, $requestedActionName) = 
                explode('.', $this->getAjaxAction(), 2);        
        // Lowercases the first letter of $requestedPluginName
        $requestedPluginName = strtolower($requestedPluginName{0}) 
                               . substr($requestedPluginName, 1);

        
        $pluginEnabler = new PluginEnabler($this);
        
        // Plugins are disabled by default in AJAX mode
        $pluginEnabler->disablePlugins();

        // Asks plugins to give their plugins directives for the given $actionId
        $this->callPluginsImplementing('Ajaxable', 'ajaxHandleAction',
                                       $this->ajaxAction, $pluginEnabler);

        // Gives the $requestedPlugin the last word
        $this->pluginManager->callPluginImplementing($requestedPluginName,
                                                     'Ajaxable',
                                                     'ajaxHandleAction',
                                                     $this->ajaxAction,
                                                     $pluginEnabler);
    }

    /**
     * Alternative processing of doMain() when exporting data.
     * @param ExportPlugin
     * @return string
     */
    private function doExport(ExportPlugin $plugin) {
        if (!$this->clientAllowed()) {
            throw new CartoclientException(
                'You do no have permission to perform this export action.');
        }

        if (!empty($_REQUEST['posted'])) {
            $plugin->handleHttpPostRequest($_REQUEST);
        } else {
            $plugin->handleHttpGetRequest($_REQUEST);
        }
        Accounting::getInstance()->account('general.export_plugin', 
                                           $plugin->getName());
        return $plugin->output();
    }

    /**
     * Detects if views controller must be launched.
     */
    private function manageViewsSystem() {
       
        if (isset($_GET['view'])) {
            $_REQUEST['handleView'] = 1;
            $_REQUEST['viewLoad'] = 1;
            $_REQUEST['viewLoadId'] = $_GET['view'];
        }
       
        $this->viewOn = $this->areViewsEnable() && 
                        !empty($_REQUEST['handleView']);
        if ($this->viewOn) {
            $this->getViewManager();
        }
    }

    /**
     * Initializes client objects
     *
     * Initializes:
     * - Configuration
     * - I18n
     * - UTF/ISO Encoding
     * - Plugins
     * - Cartoserver service
     * - HTTP request handler
     * - Form renderer
     */
    private function initializeObjects() {

        $this->log->debug('client context loaded (from session, or new)');

        // Internationalization
        I18n::init($this->getConfig(), $this->projectHandler);

        // Encoding
        Encoder::init($this->getConfig());

        // initialize objects
        $this->cartoserverService = new CartoserverService($this->getConfig());
        $this->httpRequestHandler = new HttpRequestHandler($this);
        $this->formRenderer = new FormRenderer($this);

        // views
        $this->manageViewsSystem();
    }

    /**
     * Tells if an export plugin is available for current output and returns it.
     * @return mixed
     */
    private function getValidExportType() {
        $exportPluginName = 'export' . ucfirst($this->outputType);
        $exportPlugin = $this->getPluginManager()
                             ->getPlugin($exportPluginName);
        if (!is_null($exportPlugin) && $exportPlugin instanceof ExportPlugin) {
            return $exportPlugin;
        }

        return false;
    }
        
    /**
     * Main entry point.
     *
     * Calls {@link Cartoclient::doMain()} or {@link Cartoclient::doExport()}
     * with exception handling.
     * @return string CartoWeb page string
     */
    public function main() {
        
        $this->log->debug('request is: ');
        $this->log->debug($_REQUEST);

        Common::initializeCartoweb($this->config);
        
        try {
            if ($this->outputType == self::OUTPUT_HTML_VIEWER ||
                $this->outputType == self::OUTPUT_IMAGE ||
                !$exportPlugin = $this->getValidExportType()) {
                // Triggers the AJAX mode if an ajaxActionRequest was received               
                if (isset($_REQUEST[self::AJAXACTION_PARAM_NAME])) {
                    $this->prepareAjax($_REQUEST[self::AJAXACTION_PARAM_NAME]);
                }
                $output = $this->doMain();
            } else {
                $output = $this->doExport($exportPlugin);
            }
            Accounting::getInstance()->save();
        } catch (Exception $exception) {
            $output = $this->formRenderer->showFailure($exception);
        }
        return $output;
    }
}
?>
