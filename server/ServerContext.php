<?php

class ServerContext {
    private $log;

    public $msMapObj;
    //private $mapState;

    public $mapInfo;
    public $mapInfoHandler;
    
    public $mapRequest;
    public $mapResult;

    public $config;

    function __construct($mapId) {
        $this->log =& LoggerManager::getLogger(__CLASS__);

        $this->mapResult = new MapResult();

        $this->config = new ServerConfig();

        $this->mapInfoHandler = new MapInfoHandler($this, $mapId);
        //$this->mapInfoHandler->loadMapInfo($mapId);

        $this->initializeMapObj($mapId);

        // fills mapinfo with dynamic structures
        // PERF: maybe to not do it always

        //$this->mapInfoHandler->fillDynamic($this);

        $this->mapInfo = $this->mapInfoHandler->getMapInfo();

        $this->plugins = array();
    }

    private function initializeMapObj($mapId) {

        if (!extension_loaded('mapscript')) {
            $prefix = (PHP_SHLIB_SUFFIX == 'dll') ? '' : 'php_';
            if (!dl($prefix . 'mapscript.' . PHP_SHLIB_SUFFIX))
                throw new CartoserverException("can't load mapscript library");
        }

        $this->msMapObj = ms_newMapObj($this->mapInfoHandler->configMapPath . 
                                       $mapId . '.map');

        $this->checkMsErrors();

        if (!$this->msMapObj) { // could this happen ??
            throw new CartoserverException("cannot open mapfile $mapId for map $mapId");
        }
    }

    function checkMsErrors() {
        $error = ms_GetErrorObj();
        if (!$error || $error->code == MS_NOERR)
            return;
        
        while($error && $error->code != MS_NOERR) {
            $errorMsg = sprintf("Error in %s: %s<br>\n", $error->routine, $error->message);
            $this->log->fatal($errorMsg);
            
            $errorMessages .= $errorMsg;
            $error = $error->next();
        } 

        throw new CartoserverException("Mapserver error: " . $errorMessages);

    }

    function getMsMainmapImage() {
        if (@!$this->msMainmapImage)
            throw new CartoserverException("mainmap image not generated yet");
        return $this->msMainmapImage;
    }

    function setMapRequest($mapRequest) {
        $this->mapRequest = $mapRequest;
    }

    function getMapResult() {
        return $this->mapResult;
    }

    // maybe refoactorize with cartoclient
    private function getCorePluginNames() {
        //return array('images', 'location', 'layers', 'query');
        return array('images', 'location', 'layers');
    }

    function loadPlugins() {

        $this->pluginManager = new PluginManager();
        $corePluginNames = $this->getCorePluginNames();
        $this->pluginManager->loadPlugins($this->config->basePath . 'coreplugins/',
                                          PluginManager::SERVER_PLUGINS, $corePluginNames, 
                                          $this);

        // FIXME: maybe not in mapinfo
        $pluginNames = ConfigParser::parseArray($this->mapInfo->loadPlugins);

        $this->pluginManager->loadPlugins($this->config->basePath . 'plugins/',
                                          PluginManager::SERVER_PLUGINS, $pluginNames,
                                          $this);
    }
    
    function getPluginManager() {
        return $this->pluginManager;
    }
}

?>