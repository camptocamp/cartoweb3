<?php
/**
 * @package Client
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/PluginBase.php');

/**
 * @package Client
 */
class ToolDescription {

    const MAINMAP = 2;
    const KEYMAP = 4;

    public $id;
    public $icon;
    public $label;
    public $appliesTo;
    public $weight;
    public $plugin;
    public $jsId;
    
    function __construct($id, $icon, $label, $appliesTo, $weight, $plugin = false, 
                         $jsId = NULL) {
        $this->id = $id;
        $this->icon = $icon;
        $this->label = $label;
        $this->appliesTo = $appliesTo;
        $this->weight = $weight;
        $this->plugin = $plugin ? $plugin : $id;
        $this->jsId = $jsId;
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

    function getTools();
}

/**
 * @package Client
 */
abstract class ClientPlugin extends PluginBase {
    private $log;
    protected $cartoclient;

    private $config;

    function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
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

        $pluginInit = $this->unserializeInit($mapInfo);
        
        if (!empty($pluginInit)) {        
            $this->handleInit($pluginInit);
        }
    }

    abstract function loadSession($sessionObject);
    abstract function createSession(MapInfo $mapInfo, InitialMapState $initialState);
    abstract function saveSession();

    abstract function handleHttpRequest($request);

    abstract function buildMapRequest($mapRequest);


    final function internalHandleResult($mapResult) {

        $pluginResult = $this->getRequest(false, $mapResult);
        
        $this->handleResult($pluginResult);
    }

    abstract function handleResult($result);

    abstract function renderForm($template);
}

/**
 * @package Client
 */
abstract class ClientCorePlugin extends ClientPlugin {

}
?>
