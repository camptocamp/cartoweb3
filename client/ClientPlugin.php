<?php
/**
 * @package Client
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/PluginBase.php');

/**
 * Class used by ToolDescription, to specify javascript related attributes
 *  for tools.
 * 
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
    
    public $shapeType;
    public $cursorStyle;
    public $action;
    
    function __construct($shapeType,  $cursorStyle = self::CURSOR_CROSSHAIR, 
                         $action = self::ACTION_SUBMIT) { 
        $this->shapeType = $shapeType;
        $this->cursorStyle = $cursorStyle;
        $this->action = $action;
    }    

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

    public function getActionString() {
        switch($this->action) {
            case self::ACTION_SUBMIT: return 'submit';
            case self::ACTION_MEASURE: return 'measure';
        }
        throw new CartoclientException("unknown action $this->action");            
    }
}

/**
 * @package Client
 */
class ToolDescription {

    /* bitmask */
    const MAINMAP = 2;
    const KEYMAP = 4;

    public $id;
    public $hasIcon;
    public $jsAttributes;
    public $weight;
    public $plugin;
    public $appliesTo;
    
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
 * @package Client
 */
interface ToolProvider {
    function handleMainmapTool(ToolDescription $tool, 
                            Shape $mainmapShape);
    
    function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape);

    /** 
     * Warning: this method should not be called directly to obtain the tools !!
     * Callers should use "doGetTools", which uses caching, and does some more
     * treatment on the tools.
     */
    function getTools();
}

/**
 * @package Client
 */
interface Sessionable {

    function loadSession($sessionObject);

    function createSession(MapInfo $mapInfo, InitialMapState $initialState);

    function saveSession();
}

/** 
 * @package Client
 */
interface ServerCaller {

    function buildMapRequest($mapRequest);

    function handleResult($result);
}

/** 
 * @package Client
 */
interface InitProvider {

    function handleInit($initObject); 
}

/** 
 * @package Client
 */
interface Exportable {

    function adjustExportMapRequest(ExportConfiguration $configuration, 
                                    MapRequest $mapRequest);
}


/**
 * @package Client
 */
abstract class ClientPlugin extends PluginBase {
    private $log;
    protected $cartoclient;

    private $config;
    private $tools;

    function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->tools = null;
    }

    function initialize($initArgs) {
        $this->cartoclient = $initArgs;

        $this->config = new ClientPluginConfig($this->getName(),
                                               $this->cartoclient->projectHandler);        
    }
    
    final function getConfig() {
        return $this->config;
    }

    function getCartoclient() {
        return $this->cartoclient;
    }

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

    final function dohandleInit($mapInfo) {

        assert($this instanceof InitProvider);

        $pluginInit = $this->unserializeInit($mapInfo);
        
        if (!empty($pluginInit)) {        
            $this->handleInit($pluginInit);
        }
    }

    /**
     * Converts a name one_toto_two ==> OneTotoTwo
     */
    private function convertName($name) {
        $n = explode('_', $name);
        $n = array_map('ucfirst', $n);
        return implode($n);
    }

    private function updateTool(ToolDescription $tool) {

        $tool->plugin = $this->getName();
    
        $weightConfigName = 'weight' . $this->convertName($tool->id);
        $weight = $this->getConfig()->$weightConfigName;
        if ($weight)
            $tool->weight = $weight;

        return $tool;
    }

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

    abstract function handleHttpRequest($request);

    final function internalHandleResult($mapResult) {

        assert($this instanceof ServerCaller);
        
        $pluginResult = $this->getRequest(false, $mapResult);
        
        $this->handleResult($pluginResult);
    }
    
    abstract function renderForm($template);
}

/**
 * @package Client
 */
abstract class ClientCorePlugin extends ClientPlugin {

}
?>
