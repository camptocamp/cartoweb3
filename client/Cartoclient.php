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

/**
 * Root directory for common scripts
 */
if (!defined('CARTOCOMMON_HOME'))
    define('CARTOCOMMON_HOME', CARTOCLIENT_HOME);

require_once(CARTOCOMMON_HOME . 'common/Log4phpInit.php');
initializeLog4php(true);

require_once(CARTOCLIENT_HOME . 'client/ClientMapInfoCache.php');
require_once(CARTOCLIENT_HOME . 'client/CartoserverService.php');
require_once(CARTOCLIENT_HOME . 'client/HttpRequestHandler.php');
require_once(CARTOCLIENT_HOME . 'client/FormRenderer.php');
require_once(CARTOCLIENT_HOME . 'client/ClientPlugin.php');
require_once(CARTOCLIENT_HOME . 'client/ClientPluginHelper.php');
require_once(CARTOCLIENT_HOME . 'client/ClientProjectHandler.php');
require_once(CARTOCLIENT_HOME . 'client/Internationalization.php');

require_once(CARTOCOMMON_HOME . 'common/Common.php');
require_once(CARTOCOMMON_HOME . 'common/Utils.php');
require_once(CARTOCOMMON_HOME . 'common/Config.php');
require_once(CARTOCOMMON_HOME . 'common/PluginManager.php');
require_once(CARTOCOMMON_HOME . 'common/ResourceHandler.php');
require_once(CARTOCOMMON_HOME . 'common/SecurityManager.php');
require_once(CARTOCOMMON_HOME . 'common/MapInfo.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOCOMMON_HOME . 'common/StructHandler.php');
require_once(CARTOCOMMON_HOME . 'common/Message.php');
require_once(CARTOCOMMON_HOME . 'common/Encoding.php');

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
        return CARTOCLIENT_HOME;
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
            throw new CartoclientException('You need to set cartoserverBaseUrl ' .
                    'in client.ini');            

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
        return CARTOCLIENT_HOME;
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
     * @var MapInfoCache
     */
    private $mapInfoCache;
    
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
     * Constructor
     *
     * Initializes:
     * - Project handler
     * - MapInfo cache
     * - Client objects
     * - Session
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->projectHandler = new ClientProjectHandler();
        
        try {
            $this->initializeObjects();
            $this->initializePlugins();

            $this->callPluginsImplementing('InitUser', 'handleInit',
                                       $this->getMapInfo());
            
            if (!isset($GLOBALS['headless']))
                session_start();
            $this->initializeSession();
            
            // Plugin initialization is called before the main() call, 
            //  to ensure security is applied when constructing Cartoclient object.
            $this->pluginManager->callPlugins('initialize');
                    
        } catch (Exception $exception) {
            if (isset($this->formRenderer)) {
                $this->formRenderer->showFailure($exception);
            } else {
                // form renderer not yet initialized: show a raw error message
                print 'An exception in early stage occured: <pre>';
                var_dump($exception);
                print '</pre>';
            }
            // Cartoclient was not initialized, exit
            exit;
        }
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
     * @param ClientSession
     */
    public function setClientSession($clientSession) {
        $this->clientSession = $clientSession;
    }

    /**
     * Returns the names of core plugins
     * @return array names
     */
    private function getCorePluginNames() {

        return array('location', 'layers', 'images', 'query', 'statictools',
                     'tables');
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
     * Initializes core and normal client plugins
     */
    private function initializePlugins() {

        $this->pluginManager = new PluginManager(CARTOCLIENT_HOME, 
                                PluginManager::CLIENT, $this->projectHandler);

        $corePluginNames = $this->getCorePluginNames();

        $this->pluginManager->loadPlugins($corePluginNames, $this);

        $pluginNames = ConfigParser::parseArray($this->config->loadPlugins);

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
            $this->mapInfo = $this->getMapInfoCache()->
                                            getMapInfo($this->config->mapId);
        }
        
        return $this->mapInfo;
    }

    /**
     * Save session in a variable different for each mapId
     * @param ClientSession object to save in session 
     */
    private function saveSession($clientSession) {
    
        $this->log->debug('saving session:');
        $this->log->debug($clientSession);

        $_SESSION[CLIENT_SESSION_KEY . $this->config->mapId] = $this->clientSession;
        session_write_close();
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
            $this->log->debug('Loading existing session');
            $this->callPluginsImplementing('Sessionable', 'loadSession');

        } else {
            $this->log->debug('creating new  session');

            $this->clientSession = new ClientSession();

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
                throw new CartoclientException('cant find initial map state ' .
                        $this->config->initialMapStateId);
            
            $this->callPluginsImplementing('Sessionable', 'createSession',
                                           $this->getMapInfo(), $initialMapState);
        }

        $this->cartoForm = new CartoForm();
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
     * Returns whether the current user has privileges to access cartoweb. It
     * reads the securityAllowedRoles variable in client.ini
     */
    public function clientAllowed() {
        // If lots of client security checks are to done there, make a new
        /// ClientSecurityChecker object 
        if (!$this->config->securityAllowedRoles)
            return true;
        $allowedRoles = ConfigParser::parseArray($this->config->securityAllowedRoles);
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
     */
    private function doMain() {

        if (isset($_REQUEST['posted'])) {        
            if ($_REQUEST['posted'] != '0') {
        
                // Maps clicks cannot be modified by filters
                $this->cartoForm = 
                    $this->httpRequestHandler->handleHttpRequest(
                                                        $this->clientSession,
                                                        $this->cartoForm);

                $request = new FilterRequestModifier($_REQUEST);
                $this->callPluginsImplementing('FilterProvider',
                                               'filterPostRequest', $request);
                $this->callPluginsImplementing('GuiProvider', 
                                               'handleHttpPostRequest',
                                               $request->getRequest());
            }
        } else {
            
            $request = new FilterRequestModifier($_REQUEST);
            $this->callPluginsImplementing('FilterProvider',
                                           'filterGetRequest', $request);
            $this->callPluginsImplementing('GuiProvider',
                                           'handleHttpGetRequest',
                                           $request->getRequest());
        }
        
        // If flow is interrupted and client not allowed, display unauthorized
        //  page.
        if (!$this->isInterruptFlow() && !$this->clientAllowed()) {
            $this->setInterruptFlow(true);
            $this->formRenderer->setCustomForm('unauthorized.tpl');
        }
        
        // If the flow has to be interrupted (no cartoserver call), 
        //  then this method stops here
        if ($this->isInterruptFlow())
            return $this->formRenderer->showForm();
        
        $mapRequest = $this->getMapRequest();
        $this->callPluginsImplementing('ServerCaller', 'buildMapRequest',
                                       $mapRequest);

        // Save mapRequest for future use
        $this->clientSession->lastMapRequest = $mapRequest;

        $this->log->debug('maprequest:');
        $this->log->debug($mapRequest);

        $this->mapResult = $this->getMapResultFromRequest($mapRequest);

        // Save mapResult for future use
        $this->clientSession->lastMapResult = $this->mapResult;

        $this->log->debug('mapresult:');
        $this->log->debug($this->mapResult);

        $this->callPluginsImplementing('ServerCaller', 'initializeResult',
                                       $this->mapResult);

        $this->callPluginsImplementing('ServerCaller', 'handleResult',
                                       $this->mapResult);

        $this->log->debug('client context to display');

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
     * - UTF/ISO Encoding
     * - Plugins
     * - Cartoserver service
     * - HTTP request handler
     * - Form renderer
     */
    private function initializeObjects() {

        $this->log->debug('client context loaded (from session, or new)');

        // Internationalization
        I18n::init($this->getConfig());

        // Encoding
        Encoder::init($this->getConfig());

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
    public function main() {
        
        //echo "<pre>";
        
        $this->log->debug("request is : ");
        $this->log->debug($_REQUEST);

        Common::initializeCartoweb($this->config);
        
        try {
            $this->doMain();
        } catch (Exception $exception) {
            $this->formRenderer->showFailure($exception);
        }
    }
}
?>
