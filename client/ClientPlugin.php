<?php
/**
 * Classes and interfaces for client plugins
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

    const CURSOR_CROSSHAIR = 1;
    const CURSOR_HELP = 2;
    const CURSOR_MOVE = 3;
    const CURSOR_WAIT = 4;
    const CURSOR_NRESIZE = 5;
    
    const ACTION_SUBMIT = 1;
    const ACTION_MEASURE = 2;
    
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
     * @param int
     * @param int
     @ @param int
     */
    function __construct($shapeType,  $cursorStyle = self::CURSOR_CROSSHAIR, 
                         $action = self::ACTION_SUBMIT) { 
        $this->shapeType = $shapeType;
        $this->cursorStyle = $cursorStyle;
        $this->action = $action;
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
        }
        throw new CartoclientException("unknown action $this->action");            
    }
}

/**
 * Description of a tool
 * @package Client
 */
class ToolDescription {

    /* bitmask */
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
     * @var boolean
     */
    public $plugin;
    
    /**
     * @var int
     */
    public $appliesTo;
    
    /**
     * @param string
     * @param boolean
     * @param JsToolAttributes
     * @param int
     * @param boolean
     * @param int
     */
    function __construct($id, $hasIcon, $jsAttributes, 
                         $weight, $plugin = false, $appliesTo = self::MAINMAP) {
        $this->id = $id;
        $this->hasIcon = $hasIcon;
        $this->jsAttributes = $jsAttributes;
        $this->weight = $weight;
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
    function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape);
    
    /**
     * Handles tool when key map was clicked
     * @param ToolDescription description of tool
     * @param Shape selection on map
     */
    function handleKeymapTool(ToolDescription $tool, 
                              Shape $keymapShape);

    /** 
     * Returns the provided tools
     *
     * This method should always be called using 
     * {@link PluginManager::callPluginImplementing}.
     * @return array array of {@link ToolDescription}
     */
    function getTools();
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
    function loadSession($sessionObject);

    /**
     * Initializes session data
     * @param MapInfo MapInfo
     * @param InitialMapState current state
     */
    function createSession(MapInfo $mapInfo, InitialMapState $initialState);

    /**
     * Saves session data
     */
    function saveSession();
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
    function handleHttpPostRequest($request);

    /**
     * Handles data coming from a get request 
     * @param array HTTP request
     */
    function handleHttpGetRequest($request);

    /**
     * Manages form output rendering
     * @param string template name
     */
    function renderForm($template);
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
    function buildMapRequest($mapRequest);

    /**
     * Initializes plugin state depending on server result
     * @param mixed plugin's section of map result
     */
    function initializeResult($result); 
     
    /**
     * Handles server result
     * @param mixed plugin's section of map result 
     */
    function handleResult($result);
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
    function handleInit($initObject); 
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
    function adjustExportMapRequest(ExportConfiguration $configuration, 
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
     * @param array
     */
    function __construct($request) {
        $this->request = $request;
    }
    
    /**
     * @return array
     */
    function getRequest() {
        return $this->request;
    }
    
    /**
     * @param string
     * @param string
     */
    function setValue($key, $value) {
        $this->request[$key] = $value;
    }
    
    /**
     * @param string
     * @return string
     */
    function getValue($key) {
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
     * Modifies GET requests
     * @param ExportConfiguration configuration
     * @param MapRequest map request (will be modified)
     */
    function filterGetRequest(FilterRequestModifier $request);
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
    
    function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Initializes plugin configuration
     * @param Cartoclient Cartoclient
     */
    function initialize($initArgs) {
        $this->cartoclient = $initArgs;

        $this->config = new ClientPluginConfig($this->getName(),
                                               $this->cartoclient->projectHandler);        
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
    function getCartoclient() {
        return $this->cartoclient;
    }
    
    
    function checkInt($value, $variable) {
        if (is_null($value) ||
            (is_numeric($value) && intval($value) == $value && intval($value) >= 0)) {
            return true; 
        }
        $this->cartoclient->addMessage("Parameter $variable should be an int >= 0");
        return false;
    }

    function checkNumeric($value, $variable) {
        if (is_null($value) || is_numeric($value)) {
            return true; 
        }
        $this->cartoclient->addMessage("Parameter $variable should be numeric");
        return false;
    }
        
    function getHttpValue($request, $key) {
        if (array_key_exists($key, $request) &&
            $request[$key] != '') {
            return $request[$key];
        }
        return NULL;
    }
    
}

?>
