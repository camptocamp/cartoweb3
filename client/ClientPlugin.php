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
     * Warning: this method should not be called directly to obtain the tools !!
     * Callers should use {@link ClientPlugin::doGetTools()}, which uses caching,
     * and does some more treatment on the tools.
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
     * Handles server result
     * @param mixed plugin's section of map request 
     */
    function handleResult($result);
}

/** 
 * Interface for plugins with MapInfo specific data
 * @package Client
 */
interface InitProvider {

    /**
     * Handles initialization object taken from {@link MapInfo}
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
     * @var array
     */
    private $tools;

    function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->tools = null;
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

    /**
     * Loads client session and calls child object's
     * {@link Sessionable::loadSession()}
     *
     * Assumes that plugin implements {@link Sessionable}.
     */
    final function doLoadSession() {
    
        assert($this instanceof Sessionable);
        
        $clientSession = $this->cartoclient->getClientSession();

        $className = get_class($this);

        $this->log->debug(isset($clientSession->pluginStorage->$className));
        if (empty($clientSession->pluginStorage->$className)) {
            $this->log->warn("no session to load for plugin $className");
            return;
        }

        $this->loadSession(unserialize($clientSession->pluginStorage->$className));

        $this->log->debug("plugin $className loads:");
        $this->log->debug(var_export(unserialize($clientSession->pluginStorage->$className), true));
    }

    /**
     * Gets child object's session data and save it
     *
     * Assumes that plugin implements {@link Sessionable}.
     */
    final function doSaveSession() {

        assert($this instanceof Sessionable);
        
        $className = get_class($this);

        $toSave = $this->saveSession();
        $this->log->debug("plugin $className wants to save:");
        $this->log->debug(var_export(serialize($toSave), true));
        if (!$toSave) {
            $this->log->debug("Plugin $className did not return a session to save");
            return;
        }

        $clientSession = $this->cartoclient->getClientSession();
        $clientSession->pluginStorage->$className = serialize($toSave);
        $this->cartoclient->setClientSession($clientSession);
    }
       
    /**
     * Unserializes init object specific to plugin
     * @param MapInfo MapInfo
     */
    private function unserializeInit($mapInfo) {
        
        $name = $this->getName();
        $field = $name . 'Init';
        $class = ucfirst($field);
        
        if (empty($mapInfo->$field))
            return NULL;
            
        $result = Serializable::unserializeObject($mapInfo, $field, $class);
        
        if (!is_null($result))                
            $mapInfo->$field = $result;
        
        return $result;
    }

    /**
     * Gets init object and calls child object's {@link InitProvider::handleInit()}
     *
     * Assumes that plugin implements {@link InitProvider}.
     * @param MapInfo MapInfo
     */
    final function dohandleInit($mapInfo) {

        assert($this instanceof InitProvider);

        $pluginInit = $this->unserializeInit($mapInfo);
        
        if (!empty($pluginInit)) {        
            $this->handleInit($pluginInit);
        }
    }

    /**
     * Converts a name one_toto_two ==> OneTotoTwo
     * @param string input name
     * @return string converted name
     */
    private function convertName($name) {
        $n = explode('_', $name);
        $n = array_map('ucfirst', $n);
        return implode($n);
    }

    /**
     * Updates tools info plugin name and weight
     *
     * Weight is read in plugin configuration file.
     * Example: id = my_tool, variable in configuration file = weightMyTool.
     * @param ToolDescription tool to update
     * @return ToolDescription updated tool
     */
    private function updateTool(ToolDescription $tool) {

        $tool->plugin = $this->getName();
    
        $weightConfigName = 'weight' . $this->convertName($tool->id);
        $weight = $this->getConfig()->$weightConfigName;
        if ($weight)
            $tool->weight = $weight;

        return $tool;
    }

    /** 
     * Calls child object's {@link ToolProvider::getTools()}, updates tools
     * and returns them
     * 
     * Assumes that plugin implements {@link ToolProvider}.
     * @return array array of {@link ToolDescription}
     */
    final function doGetTools() {

        assert($this instanceof ToolProvider); 

        if (is_null($this->tools)) {
            $tools = $this->getTools();

            unset($this->tools);
            $this->tools = array();
            
            // update tools
            foreach ($tools as $tool) {
                $tool = $this->updateTool($tool);
                if ($tool->weight >= 0) {
                    $this->tools[] = $tool;
                }
            }
        }   
        return $this->tools;
    }

    /**
     * Handles data coming from a post request 
     * @param array HTTP request
     */
    abstract function handleHttpRequest($request);

    /**
     * Gets plugin specific result out of {@link MapResult} and calls child
     * object's {@link ServerCaller::handleResult()}
     *
     * Assumes that plugin implements {@link ServerCaller}.
     * @param MapResult complete MapResult
     */
    final function internalHandleResult($mapResult) {

        assert($this instanceof ServerCaller);
        
        $pluginResult = $this->getRequest(false, $mapResult);
        
        $this->handleResult($pluginResult);
    }
    
    /**
     * Manages form output rendering
     * @param string template name
     */
    abstract function renderForm($template);
}

/**
 * Core plugin
 * @package Client
 */
abstract class ClientCorePlugin extends ClientPlugin {

}
?>
