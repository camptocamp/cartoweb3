<?php
/**
 * @package Server
 * @version $Id$
 */
//require_once('log4php/LoggerManager.php');
require_once(CARTOSERVER_HOME . 'server/Cartoserver.php');
require_once(CARTOCOMMON_HOME . 'common/PluginBase.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');

/**
 * Project handler
 */
require_once(CARTOSERVER_HOME . 'server/ServerProjectHandler.php');

/**
 * @package Server
 */
class ServerContext {
    private $log;

    private $mapId;
    
    private $msMapObj;
    private $maxExtent;
    private $msMainmapImage;

    private $mapInfo;
    private $mapInfoHandler;
    
    public $mapRequest;
    public $mapResult;

    public $config;
    private $messages = array();

    public $projectHandler;

    function __construct($mapId) {
        $this->log =& LoggerManager::getLogger(__CLASS__);

        // Remembers mapId for future MapInfo creation
        $this->mapId = $mapId;

        $this->projectHandler = new ServerProjectHandler($mapId);

        $this->config = new ServerConfig($this->projectHandler);

        $this->plugins = array();
        
        $this->reset();          
    }
    
    function reset() {
        $this->mapResult = new MapResult();
    }

    function setMsMainmapImage($msMainmapImage) {
        $this->msMainmapImage = $msMainmapImage;   
    }
    
    function getMsMainmapImage() {
        if (empty($this->msMainmapImage))
            throw new CartoserverException("mainmap image not generated yet");
        return $this->msMainmapImage;
    }
    
    function isDevelMessagesEnabled() {
        if (is_null($this->config))
            return false;
        return $this->config->showDevelMessages;   
    }
    
    function addMessage($message, $channel = ServerMessage::CHANNEL_USER) {

        $this->messages[] = new ServerMessage($message, $channel);
    }
    
    function getMessages() {
        return $this->messages;
    }

    function getMapPath() {
        assert(!is_null($this->projectHandler));
        $mapName = $this->projectHandler->getMapName();
        $path = $this->projectHandler->getPath(CARTOSERVER_HOME, 
                            'server_conf/' . $mapName . '/', $mapName . '.map');
        return CARTOSERVER_HOME . $path . $mapName . '.map';
    } 

    function getTimestamp() {
        $mapPath = $this->getMapPath();
        $iniPath = $this->getMapInfoHandler()->getIniPath();
        
        $timeStamp = (filemtime($mapPath) + filemtime($iniPath)) / 2;
        return (int)$timeStamp;
    }

    public function getMapObj() {

        if (!$this->msMapObj) {
            if (!extension_loaded('mapscript')) {
                $prefix = (PHP_SHLIB_SUFFIX == 'dll') ? '' : 'php_';
                if (!dl($prefix . 'mapscript.' . PHP_SHLIB_SUFFIX))
                    throw new CartoserverException("can't load mapscript library");
            }
        
            $mapPath = $this->getMapPath();
            $this->msMapObj = ms_newMapObj($mapPath);

            $this->maxExtent = clone($this->getMapObj()->extent);

            $this->checkMsErrors();

            if (!$this->msMapObj) { // could this happen ??
                throw new CartoserverException("cannot open mapfile $mapId for map $mapId");
            }
        }
        return $this->msMapObj;
    }

    public function getMaxExtent() {
            
        return $this->maxExtent;
    }

    function getMapInfoHandler() {
        if (!$this->mapInfoHandler) {        
            $this->mapInfoHandler = new MapInfoHandler($this, $this->mapId, $this->projectHandler);
        }
        return $this->mapInfoHandler;
    }

    function getMapInfo() {
        if (!$this->mapInfo) {
            $this->mapInfo = $this->getMapInfoHandler()->getMapInfo();
        }
        return $this->mapInfo;
    }

    function resetMsErrors() {
        $error = ms_GetErrorObj();
        while($error && $error->code != MS_NOERR) {
            $errorMsg = sprintf("ignoring ms error in %s: %s<br>\n", $error->routine, $error->message);
            $this->log->debug($errorMsg);
            $error = $error->next();
        } 
        ms_ResetErrorList();
    }

    function checkMsErrors() {
        $error = ms_GetErrorObj();
        if (!$error || $error->code == MS_NOERR)
            return;
        
        $errorMessages = '';
        while($error && $error->code != MS_NOERR) {
            $errorMsg = sprintf("Error in %s: %s<br>\n", $error->routine, $error->message);
            $this->log->fatal($errorMsg);
            
            $errorMessages .= $errorMsg;
            $error = $error->next();
        } 

        throw new CartoserverException("Mapserver error: " . $errorMessages);
    }

    function setMapRequest($mapRequest) {
        $this->mapRequest = $mapRequest;
    }

    function getMapResult() {
        return $this->mapResult;
    }
    
    // maybe refactorize with cartoclient
    private function getCorePluginNames() {
        return array('images', 'location', 'layers', 'query');
    }

    function loadPlugins() {

        $this->pluginManager = new PluginManager($this->projectHandler);
        $corePluginNames = $this->getCorePluginNames();
        $this->pluginManager->loadPlugins($this->config->basePath, 'coreplugins/',
                                          PluginManager::SERVER_PLUGINS, $corePluginNames, $this);

        // FIXME: maybe not in mapinfo
        $pluginNames = $this->getMapInfo()->loadPlugins;
        
        $this->pluginManager->loadPlugins($this->config->basePath, 'plugins/',
                                          PluginManager::SERVER_PLUGINS, $pluginNames, $this);
    }
    
    function getPluginManager() {
        return $this->pluginManager;
    }
    
    /*
     * Utility methods shared by several plugins
     */
     
    private function getIdAttributeString($layerId) {
        $serverLayer = $this->getMapInfo()->getLayerById($layerId);
        if (!$serverLayer) 
            throw new CartoserverException("layerid $layerId not found");
        
        // extract from ini config file
        if (!empty($serverLayer->idAttributeString)) {
            return $serverLayer->idAttributeString;   
        }
        
        // retrieve from metadata
        $msLayer = $this->msMapObj->getLayerByName($serverLayer->msLayer);
        $this->checkMsErrors();

        $idAttribute = $msLayer->getMetaData('id_attribute_string');
        if (!empty($idAttribute)) {
            return $idAttribute;   
        }
        
        return NULL;
    } 
    
    /**
     * Returns the default attribute for matching identifier for a given
     * layer
     *
     * @param $layerId the layer on which to retrieve the default id attribute
     */
     function getIdAttribute($layerId) {
        $idAttributeString = $this->getIdAttributeString($layerId);
        if (is_null($idAttributeString))
            return NULL;
        $explodedAttr = explode('|', $idAttributeString);
        assert(count($explodedAttr) >= 1);
        return $explodedAttr[0]; 
     }

    /**
     * Returns the type of the default attribute.
     * It may be "string" or "integer"
     *
     * @param $layerId the layer on which to retrieve the default id type
     */
     function getIdAttributeType($layerId) {
        $idAttributeString = $this->getIdAttributeString($layerId);
        $explodedAttr = explode('|', $idAttributeString);
        // by default, type is string
        if (count($explodedAttr) == 1)
            return 'string';
        assert(count($explodedAttr) == 2);
        $type = $explodedAttr[1];
        if (!in_array($type, array('int', 'string')))
            throw new CartoserverException("bad id attribute type: $type");
        return $type; 
     }
}

?>