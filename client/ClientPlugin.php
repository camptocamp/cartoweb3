<?php
/**
 * Classes and interfaces for client plugins
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
require_once(CARTOWEB_HOME . 'common/PluginBase.php');

/**
 * Description of a tool
 * @package Client
 */
class ToolDescription {

    /**
     * Bitmask for tools 
     */
    const MAINMAP = 2;
    const KEYMAP = 4;
    const APPLICATION = 8;

    /** 
     * @var string
     */
    public $id;
    
    /**
     * @var boolean
     */
    public $hasIcon;
    
    /**
     * @var int
     */
    public $weight;

    /**
     * @var int
     */
    public $group;
    
    /**
     * @var boolean
     */
    public $plugin;
    
    /**
     * @var int
     */
    public $appliesTo;
    
    /**
     * @var boolean
     */
    public $stateless;
    
    /**
     * Constructor
     * @param string
     * @param boolean
     * @param int
     * @param int
     * @param boolean
     * @param int
     * @param boolean
     */
    public function __construct($id, $hasIcon, $weight, 
        $appliesTo=self::MAINMAP, $stateless = false, $group=1, $plugin=false) {
        
        $this->id = $id;
        $this->hasIcon = $hasIcon;
        $this->weight = $weight;
        $this->appliesTo = $appliesTo;
        $this->stateless= $stateless;
        $this->group = $group;
        $this->plugin = $plugin;
    }
}

/**
 * Interface for plugins with tools capability
 * @package Client
 */
interface ToolProvider {

    /**
     * Handles tool when main map was clicked
     * @param ToolDescription description of tool
     * @param Shape selection on map
     */
    public function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape);
    
    /**
     * Handles tool when keymap was clicked
     * @param ToolDescription description of tool
     * @param Shape selection on map
     */
    public function handleKeymapTool(ToolDescription $tool, 
                              Shape $keymapShape);

    /**
     * Handles tool when stateless application was clicked
     * @param ToolDescription description of tool
     */
    public function handleApplicationTool(ToolDescription $tool);

    /** 
     * Returns the provided tools
     *
     * This method should always be called using 
     * {@link PluginManager::callPluginImplementing}.
     * @return array array of {@link ToolDescription}
     */
    public function getTools();
}

/**
 * Interface for plugins with session data
 * @package Client
 */
interface Sessionable {

    /**
     * Reloads data from session object
     * @param mixed plugin's section of session object
     */
    public function loadSession($sessionObject);

    /**
     * Initializes session data
     * @param MapInfo MapInfo
     * @param InitialMapState current state
     */
    public function createSession(MapInfo $mapInfo, InitialMapState $initialState);

    /**
     * Saves session data
     * @return object The object containing the session state to save.
     */
    public function saveSession();
}

/**
 * Interface for plugins that interact with HTML forms
 * @package Client
 */
interface GuiProvider {

    /**
     * Handles data coming from a post request 
     * @param array HTTP request
     */
    public function handleHttpPostRequest($request);

    /**
     * Handles data coming from a get request 
     * @param array HTTP request
     */
    public function handleHttpGetRequest($request);

    /**
     * Manages form output rendering
     * @param string Smarty template object
     */
    public function renderForm(Smarty $template);
}

/**
 * Interface for plugins that generate AJAX responses
 * @package Client
 */
interface Ajaxable {

    /**
     * Modifies plugins enable level for the given action
     * @param string Name Name of the action
     * @param $pluginEnabler PluginEnabler @see PluginEnabler
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler);

    /**
     * Populates a plugin XML response to AJAX request
     * @param AjaxPluginResponse
     * @see AjaxPluginResponse
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse);
}

/** 
 * Interface for plugins that may call server
 * @package Client
 */
interface ServerCaller {

    /**
     * Returns specific plugin request
     * @return mixed
     */
    public function buildRequest();

    /**
     * Initializes plugin state depending on server result
     * @param mixed plugin's section of map result
     */
    public function initializeResult($result); 
     
    /**
     * Handles server result
     * @param mixed plugin's section of map result 
     */
    public function handleResult($result);
}

/** 
 * Interface for plugins with MapInfo specific data
 * @package Client
 */
interface InitUser {

    /**
     * Handles initialization object taken from {@link MapInfo}
     * 
     * These values were generated by {@link InitProvider::getInit}.
     * @param mixed plugin's section of MapInfo
     */
    public function handleInit($initObject); 
}

/** 
 * Interface for plugins that may modify requests before an export
 * @package Client
 */
interface Exportable {

    /**
     * Adjust map request to get a ready for export result
     * @param ExportConfiguration configuration
     * @param MapRequest map request (will be modified)
     */
    public function adjustExportMapRequest(ExportConfiguration $configuration, 
                                    MapRequest $mapRequest);
}

/**
 * This class is used by plugins to modify HTTP Get requests
 * @package Client
 * @see FilterProvider
 */
class FilterRequestModifier {
    
    /**
     * @var array
     */
    private $request;
    
    /**
     * Constructor
     * @param array
     */
    public function __construct($request) {
        $this->request = $request;
    }
    
    /**
     * @return array
     */
    public function getRequest() {
        return $this->request;
    }
    
    /**
     * @param string
     * @param string
     */
    public function setValue($key, $value) {
        $this->request[$key] = $value;
    }
    
    /**
     * @param string
     * @return string
     */
    public function getValue($key) {
        if (array_key_exists($key, $this->request)) {
            return $this->request[$key];
        } else {
            return null; 
        }
    }
}

/** 
 * Interface for plugins that may modify HTTP GET requests
 * @package Client
 */
interface FilterProvider {

    /**
     * Modifies POST requests
     * @param ExportConfiguration configuration
     * @param MapRequest map request (will be modified)
     */
    public function filterPostRequest(FilterRequestModifier $request);

    /**
     * Modifies GET requests
     * @param ExportConfiguration configuration
     * @param MapRequest map request (will be modified)
     */
    public function filterGetRequest(FilterRequestModifier $request);
}

/**
 * Client plugin
 * @package Client
 */
abstract class ClientPlugin extends PluginBase {

    /**
     * Enable level of the plugin (used for AJAX calls).
     * @var int
     * @see PluginEnabler
     * @see Cartoclient::callEnabledPluginImplementing()
     * @see Cartoclient::callEnabledPluginsImplementing()
     */
     protected $enabledLevel = ClientPlugin::ENABLE_LEVEL_FULL;

    /* 
     * Enable level definitions
     * ENABLE_LEVEL_LOAD: Load/create plugin session
     * ENABLE_LEVEL_PROCESS: LOAD + filter+handle http request and save session
     * ENABLE_LEVEL_SERVERCALL: PROCESS + build server request and handle results
     * ENABLE_LEVEL_FULL: SERVERCALL + render GUI 
     */
    const ENABLE_LEVEL_LOAD = 0;
    const ENABLE_LEVEL_PROCESS = 1;
    const ENABLE_LEVEL_SERVERCALL = 2;
    const ENABLE_LEVEL_FULL = 3;

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var Cartoclient
     */
    protected $cartoclient;

    /** 
     * @var ClientConfig
     */
    private $config;
        
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Initializes plugin configuration
     * @param Cartoclient Cartoclient
     */
    public function initializeConfig($initArgs) {
        $this->cartoclient = $initArgs;

        $this->config = new ClientPluginConfig($this->getName(),
                                      $this->cartoclient->getProjectHandler());        
    }
    
    /**
     * @return ClientConfig
     */
    final public function getConfig() {
        return $this->config;
    }

    /**
     * @return Cartoclient
     */
    public function getCartoclient() {
        return $this->cartoclient;
    }

    /**
     * Checks if variable $variable has an integer positive or zero 
     * value $value. 
     * @param mixed variable value
     * @param string variable name
     * @return boolean
     */
    public function checkInt($value, $variable) {
        if (is_null($value) ||
            (is_numeric($value) && intval($value) == $value && 
             intval($value) >= 0)) {
            return true; 
        }
        $this->cartoclient->addMessage("Parameter $variable" .
                                       ' should be an int >= 0');
        return false;
    }

    /**
     * Checks if variable $variable has a boolean (0 or 1) value $value.
     * @param mixed variable value
     * @param string variable name
     * @return boolean
     */
    public function checkBool($value, $variable) {
        if (is_bool($value)) {
            return true;
        }

        if (is_null($value) ||
            (is_numeric($value) && (intval($value) == 0 || 
                                    intval($value) == 1))) {
            return true; 
        }
        $this->cartoclient->addMessage("Parameter $variable should be 0 or 1");
        return false;
    }

    /**
     * Checks if variable $variable has a numeric value $value.
     * @param mixed variable value
     * @param string variable name
     * @return boolean
     */
    public function checkNumeric($value, $variable) {
        if (is_null($value) || is_numeric($value)) {
            return true; 
        }
        $this->cartoclient->addMessage("Parameter $variable should be numeric");
        return false;
    }

    /**
     * Returns the user-submitted $key data if it is set.
     * @param array
     * @param string
     * @return string
     */
    public function getHttpValue($request, $key) {
        if (array_key_exists($key, $request) &&
            $request[$key] != '') {
            return $request[$key];
        }
        return NULL;
    }
    
    /**
     * Sets the enable level (to be used with the AJAX mode).
     * @param int
     */
    public function setEnableLevel($enableLevel) {
        if ($enableLevel < ClientPlugin::ENABLE_LEVEL_LOAD ||
            $enableLevel > ClientPlugin::ENABLE_LEVEL_FULL) {
            throw new AjaxException("The specified enable level ($enableLevel)" .
                                    'does not exist!');
        }
        $this->enabledLevel = $enableLevel;
    }

    /**
     * Returns the enable level.
     * @return int the current enable level.
     */
    public function getEnabledLevel() {
        return $this->enabledLevel;
    }
    
    /**
     * Sets the enable level to ClientPlugin::ENABLE_LEVEL_FULL.
     */
    public function enable() {
        $this->setEnableLevel(ClientPlugin::ENABLE_LEVEL_FULL);
    }

    /**
     * Sets the enable level to ClientPlugin::ENABLE_LEVEL_SERVERCALL.
     */
    public function disable() {
        $this->setEnableLevel(ClientPlugin::ENABLE_LEVEL_SERVERCALL);
    }
    
    /**
     * Sets the enable level to ClientPlugin::ENABLE_LEVEL_FULL.
     * @return bool true if the current enable level is equal or greater than
     *              the given enable level, false otherwise.
     */
    public function isEnabledAtLevel($enableLevel) {
        return $this->enabledLevel >= $enableLevel;
    }

}

?>
