<?php
/**
 * @package Server
 * @version $Id$
 */

//require_once('log4php/LoggerManager.php');
require_once(CARTOSERVER_HOME . 'server/Cartoserver.php');
require_once(CARTOCOMMON_HOME . 'common/PluginBase.php');
require_once(CARTOCOMMON_HOME . 'common/Request.php');
require_once(CARTOCOMMON_HOME . 'common/Message.php');
require_once(CARTOCOMMON_HOME . 'common/ResourceHandler.php');
require_once(CARTOCOMMON_HOME . 'common/Encoding.php');

/**
 * Project handler
 */
require_once(CARTOSERVER_HOME . 'server/ServerProjectHandler.php');

/**
 * @package Server
 */
class ServerContext {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var string
     */
    private $mapId;
    
    /**
     * @var Mapscript MapObj
     */
    private $msMapObj;
    
    /**
     * @var Mapscript RectObj
     */
    private $maxExtent;

    /**
     * @var int
     */
    private $imageType;
    
    /**
     * @var Mapscript ImageObj 
     */
    private $msMainmapImage;

    /**
     * @var MapInfo
     */
    private $mapInfo;

    /**
     * @var MapInfoHandler
     */
    private $mapInfoHandler;
    
    /**
     * @var MapRequest
     */
    public $mapRequest;

    /**
     * @var MapResult
     */
    public $mapResult;

    /**
     * @var ServerConfig
     */
    public $config;
    
    /**
     * @var array
     */
    private $messages = array();
    
    /**
     * @var ProjectHandler
     */
    private $projectHandler;
    
    /**
     * @var ResourceHandler
     */
    private $resourceHandler;
    
    /**
     * @var array
     */
    private $plugins;
    
    /**
     * @var PluginManager
     */
    private $pluginManager;

    /**
     * Constructor
     * @param string map id
     */
    public function __construct($mapId) {
        $this->log =& LoggerManager::getLogger(__CLASS__);

        // Remembers mapId for future MapInfo creation
        $this->mapId = $mapId;

        $this->projectHandler = new ServerProjectHandler($mapId);
        $this->config = new ServerConfig($this->projectHandler);
        
        $this->pluginManager = null;
        $this->plugins = array();

        // Encoding
        Encoder::init($this->config);          

        $this->reset();        
    }
    
    /**
     * Resets map result object.
     */
    public function reset() {
        $this->mapResult = new MapResult();
    }

    /**
     * @param Mapscript ImageObj
     */
    public function setMsMainmapImage($msMainmapImage) {
        $this->msMainmapImage = $msMainmapImage;   
    }
    
    /**
     * @return Mapscript ImageObj
     */
    public function getMsMainmapImage() {
        if (empty($this->msMainmapImage))
            throw new CartoserverException('mainmap image not generated yet');
        return $this->msMainmapImage;
    }
   
    /**
     * Tells (from INI file) if developpers messages must be shown.
     * @return boolean
     */
    public function isDevelMessagesEnabled() {
        if (is_null($this->config))
            return false;
        return $this->config->showDevelMessages;   
    }
    
    /**
     * Adds a message in the messages-to-cartoclient list.
     * @param string message
     * @param int message type {@see Message}
     */
    public function addMessage($message, $channel = Message::CHANNEL_USER) {

        $this->messages[] = new Message($message, $channel);
    }
    
    /**
     * Returns messages list.
     * @return array
     */
    public function getMessages() {
        return $this->messages;
    }

    /**
     * Returns mapfile location.
     * @return string
     */
    public function getMapPath() {
        assert(!is_null($this->projectHandler));
        $mapName = $this->projectHandler->getMapName();
        $path = $this->projectHandler->getPath('server_conf/' . $mapName . '/',
                                               $mapName . '.map');
        return CARTOSERVER_HOME . $path . $mapName . '.map';
    } 

    /**
     * Returns mean (mapfile & INI file) modification time.
     * @return int
     */
    public function getTimestamp() {
        $mapPath = $this->getMapPath();
        $iniPath = $this->getMapInfoHandler()->getIniPath();
        
        $timestamp = (filemtime($mapPath) + filemtime($iniPath)) / 2;
        return (int)$timestamp;
    }

    /**
     * Instanciates a new Mapserver MapObj object.
     * @return Mapscript MapObj
     */
    public function getMapObj() {

        if (!$this->msMapObj) {
            if (!extension_loaded('mapscript')) {
                $prefix = (PHP_SHLIB_SUFFIX == 'dll') ? '' : 'php_';
                if (!dl($prefix . 'mapscript.' . PHP_SHLIB_SUFFIX))
                    throw new CartoserverException("can't load mapscript " .
                                                   'library');
            }
        
            $mapPath = $this->getMapPath();
            $this->msMapObj = ms_newMapObj($mapPath);
            $this->checkMsErrors();

            $this->maxExtent = clone($this->msMapObj->extent);
            $this->imageType = $this->msMapObj->imagetype;
            
            if (!$this->msMapObj) { // could this happen ??
                throw new CartoserverException("cannot open mapfile $mapId " .
                                               "for map $mapId");
            }
        }
        return $this->msMapObj;
    }

    /**
     * @return Mapscript RectObj 
     */
    public function getMaxExtent() {
        return $this->maxExtent;
    }

    /**
     * @return int
     */
    public function getImageType() {
        return $this->imageType;
    }

    /**
     * @return MapInfoHandler
     */
    public function getMapInfoHandler() {
        if (!$this->mapInfoHandler) {        
            $this->mapInfoHandler = new MapInfoHandler($this, $this->mapId,
                                                       $this->projectHandler);
        }
        return $this->mapInfoHandler;
    }

    /**
     * @return MapInfo
     */
    public function getMapInfo() {
        if (!$this->mapInfo) {
            $this->mapInfo = $this->getMapInfoHandler()->getMapInfo();
        }
        return $this->mapInfo;
    }

    /**
     * Clears lists of Mapserver errors.
     */
    public function resetMsErrors() {
        $error = ms_GetErrorObj();
        while($error && $error->code != MS_NOERR) {
            $errorMsg = sprintf("ignoring ms error in %s: %s<br>\n", 
                                $error->routine, $error->message);
            $this->log->debug($errorMsg);
            $error = $error->next();
        } 
        ms_ResetErrorList();
    }

    /**
     * Throws an exception if Mapserver errors are detected.
     */
    public function checkMsErrors() {
        $error = ms_GetErrorObj();
        if (!$error || $error->code == MS_NOERR)
            return;
        
        $errorMessages = '';
        while($error && $error->code != MS_NOERR) {
            $errorMsg = sprintf("Error in %s: %s<br>\n", 
                                $error->routine, $error->message);
            $this->log->fatal($errorMsg);
            
            $errorMessages .= $errorMsg;
            $error = $error->next();
        } 

        throw new CartoserverException("Mapserver error: " . $errorMessages);
    }

    /**
     * @param MapRequest
     */
    public function setMapRequest($mapRequest) {
        $this->mapRequest = $mapRequest;
    }

    /**
     * @return MapResult
     */
    public function getMapResult() {
        return $this->mapResult;
    }
    
    /**
     * Returns list of coreplugins names.
     * @return array
     */
    private function getCorePluginNames() {
        // TODO : factorize with cartoclient?
        return array('images', 'location', 'layers', 'query', 'mapquery', 
                     'tables');
    }

    /**
     * Loads the server plugins.
     */
    public function loadPlugins() {

        if (is_null($this->pluginManager)) {
            $this->pluginManager = new PluginManager(CARTOSERVER_HOME, 
                                 PluginManager::SERVER, $this->projectHandler);
            $corePluginNames = $this->getCorePluginNames();

            $this->pluginManager->loadPlugins($corePluginNames, $this);

            // FIXME: maybe not in mapinfo
            $pluginNames = $this->getMapInfo()->loadPlugins;
        
            $this->pluginManager->loadPlugins($pluginNames, $this);
        }
    }
    
    /**
     * Returns the plugin manager
     * @return PluginManager
     */
    public function getPluginManager() {
        return $this->pluginManager;
    }

    /**
     * Returns the project handler
     * @return ProjectHandler
     */
    public function getProjectHandler() {
        return $this->projectHandler;
    }
        
    /**
     * Returns the resource handler
     * @return ResourceHandler
     */
    public function getResourceHandler() {
        if (!$this->resourceHandler) {
            $this->resourceHandler = new ResourceHandler($this->config, 
                                                $this->getProjectHandler());
        }
        return $this->resourceHandler;
    }
    
    /*
     * Utility methods shared by several plugins
     */
    
    /**
     * Returns Mapserver id_attribute_string for given layer.
     * @param string layer id
     * @return string
     */
    private function getIdAttributeString($layerId) {
        $serverLayer = $this->getMapInfo()->getLayerById($layerId);
        if (!$serverLayer) 
            throw new CartoserverException("layerid $layerId not found");

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
     * Returns the default id attribute for given layer.
     * @param string layer id
     * @return string
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
     * @param string layer id
     * @return string
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
