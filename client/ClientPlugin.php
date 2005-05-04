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
require_once(CARTOCOMMON_HOME . 'common/PluginBase.php');

/**
 * Class used by {@link ToolDescription}, to specify javascript related
 * attributes for tools.
 * @package Client
 */
class JsToolAttributes {
    const SHAPE_RECTANGLE = 1;
    const SHAPE_POINT = 2;
    const SHAPE_LINE = 3;
    const SHAPE_PAN = 4;
    const SHAPE_POLYGON = 5;
    const SHAPE_RECTANGLE_OR_POINT = 6;

    const CURSOR_CROSSHAIR = 1;
    const CURSOR_HELP = 2;
    const CURSOR_MOVE = 3;
    const CURSOR_WAIT = 4;
    const CURSOR_NRESIZE = 5;
    
    const ACTION_SUBMIT = 1;
    const ACTION_MEASURE = 2;
    const ACTION_JAVASCRIPT = 3;
    
    /**
     * @var int
     */
    public $shapeType;
    
    /** 
     * @var int
     */
    public $cursorStyle;
    
    /** 
     * @var int
     */
    public $action;
    
    /**
     * Constructor
     * @param int
     * @param int
     * @param int
     */
    public function __construct($shapeType,  $cursorStyle = self::CURSOR_CROSSHAIR, 
                         $action = self::ACTION_SUBMIT, $jsFunction = '') { 
        $this->shapeType = $shapeType;
        $this->cursorStyle = $cursorStyle;
        $this->action = $action;
        $this->jsFunction = $jsFunction;
    }    

    /**
     * Returns shape string identification
     * @return string shape id
     */
    public function getShapeTypeString() {
        switch($this->shapeType) {
            case self::SHAPE_RECTANGLE: return 'rectangle';
            case self::SHAPE_POINT:     return 'point';
            case self::SHAPE_LINE:      return 'line';
            case self::SHAPE_PAN:       return 'pan';
            case self::SHAPE_POLYGON:   return 'polygon';
            case self::SHAPE_RECTANGLE_OR_POINT: return 'rectangle_or_point';
        }
        throw new CartoclientException("unknown shape type $this->shapeType");            
    }

    /**
     * Returns cursor string identification
     * @return string cursor id
     */
    public function getCursorStyleString() {
        switch($this->cursorStyle) {
            case self::CURSOR_CROSSHAIR: return 'crossHair';
            case self::CURSOR_HELP:      return 'help';
            case self::CURSOR_MOVE:      return 'move';
            case self::CURSOR_WAIT:      return 'wait';
            case self::CURSOR_NRESIZE:   return 'n-resize';
        }
        throw new CartoclientException("unknown cursor style $this->cursorStyle");            
    }

    /**
     * Returns action string identification
     * @return string action id
     */
    public function getActionString() {
        switch($this->action) {
            case self::ACTION_SUBMIT: return 'submit';
            case self::ACTION_MEASURE: return 'measure';
            case self::ACTION_JAVASCRIPT: return 'javascript:'.$this->jsFunction;
        }
        throw new CartoclientException("unknown action $this->action");            
    }
}

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

    /** 
     * @var string
     */
    public $id;
    
    /**
     * @var boolean
     */
    public $hasIcon;
    
    /**
     * @var JsToolAttributes
     */
    public $jsAttributes;
    
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
     * Constructor
     * @param string
     * @param boolean
     * @param JsToolAttributes
     * @param int
     * @param boolean
     * @param int
     */
    public function __construct($id, $hasIcon, $jsAttributes, 
                         $weight, $group = 1, $plugin = false, $appliesTo = self::MAINMAP) {
        $this->id = $id;
        $this->hasIcon = $hasIcon;
        $this->jsAttributes = $jsAttributes;
        $this->weight = $weight;
        $this->group = $group;
        $this->plugin = $plugin;
        $this->appliesTo = $appliesTo;
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
     * Handles tool when key map was clicked
     * @param ToolDescription description of tool
     * @param Shape selection on map
     */
    public function handleKeymapTool(ToolDescription $tool, 
                              Shape $keymapShape);

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
 * Interface for plugins that may call server
 * @package Client
 */
interface ServerCaller {

    /**
     * Adds specific plugin information to map request
     * @param MapRequest map request (will be modified)
     */
    public function buildMapRequest($mapRequest);

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
    final function getConfig() {
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
    
}

?>
