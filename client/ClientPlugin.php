<?php

class ToolDescription {

    const MAINMAP = 2;
    const KEYMAP = 4;

    public $id;
    public $icon;
    public $label;
    public $appliesTo;
    
    function __construct($id, $icon, $label, $appliesTo) {
        $this->id = $id;
        $this->icon = $icon;
        $this->label = $label;
        $this->appliesTo = $appliesTo;
    }
}


interface ToolProvider {
    function handleMainmapTool(ToolDescription $tool, 
                            Shape $mainmapShape);
    
    function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape);

    function getTools();
}


abstract class ClientPlugin extends PluginBase {
    private $log;
    protected $cartoclient;

    function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function initialize($initArgs) {
        $this->cartoclient = $initArgs;
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
        

    abstract function loadSession($sessionObject);
    abstract function createSession(MapInfo $mapInfo, InitialMapState $initialState);
    abstract function saveSession();

    abstract function handleHttpRequest($request);

    abstract function buildMapRequest($mapRequest);

    final function dohandleMapResult($mapResult) {
        $className = get_class($this);
        
        $this->handleMapResult($mapResult);
    }

    abstract function handleMapResult($mapResult);

    abstract function renderForm($template);
}

abstract class ClientCorePlugin extends ClientPlugin {

}
?>