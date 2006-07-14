<?php
/**
 * ServerContext class
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
 * @package Server
 * @version $Id$
 */

require_once(CARTOWEB_HOME . 'server/Cartoserver.php');
require_once(CARTOWEB_HOME . 'common/PluginBase.php');
require_once(CARTOWEB_HOME . 'common/Request.php');
require_once(CARTOWEB_HOME . 'common/Message.php');
require_once(CARTOWEB_HOME . 'common/ResourceHandler.php');
require_once(CARTOWEB_HOME . 'common/Encoding.php');

/**
 * Project handler
 */
require_once(CARTOWEB_HOME . 'server/ServerProjectHandler.php');

/**
 * @package Server
 */
class ServerContext extends Cartocommon {

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
    private $mapRequest;

    /**
     * @var MapResult
     */
    private $mapResult;

    /**
     * @var ServerConfig
     */
    private $config;

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
     * @var boolean True when mapscript module has beed loaded
     */
    private $mapscriptLoaded;

    /**
     * If true, complete mapfile is used, if false, switch mapfile is used
     * @var boolean
     */
    public $globalMap = false;

    /**
     * Singleton
     * @var ServerContext
     */
    private static $instance;

    /**
     * Constructor
     * @param string map id
     */
    public function __construct($mapId) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        self::$instance = $this;

        // Remembers mapId for future MapInfo creation
        $this->mapId = $mapId;

        $this->projectHandler = new ServerProjectHandler($mapId);
        $this->config = new ServerConfig($this->projectHandler);

        $this->pluginManager = null;
        $this->plugins = array();

        // Encoding
        if (count(Encoder::$encoders) == 0) {
            // Was not initialized --> SOAP mode
            Encoder::init($this->config);
        }

        $this->reset();
    }

    /**
     * Returns the instance of this class. There is only one during the
     * ServerContext lifetime.
     */
    public static function getInstance() {
        if (is_null(self::$instance))
            throw new CartoserverException('ServerContext instance ' .
                                           'not yet initialized');
        return self::$instance;
    }

    /**
     * Returns the current mapId.
     * @return string
     */
    public function getMapId() {
        return $this->mapId;
    }

    /**
     * Resets map result object.
     */
    public function reset() {
        $this->mapResult = new MapResult();
        $this->msMapObj = null;
        $this->globalMap = false;
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
     * Adds a message to be returned to the client
     * @param PluginBase the plugin attached to this message
     * @param string message identifier, for machine message parsing
     * @param string the text of the message
     * @param int the channel identifier of the message
     */
    public function addMessage(PluginBase $plugin, $messageId, $message,
                                            $channel = Message::CHANNEL_USER) {

        $this->messages[] = new Message($message, $channel, $plugin->getName(),
                                                                    $messageId);
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
     *
     * If $global is set to false, will use reduced map file depending on
     * plugin layers switch.
     * @return string
     */
    public function getMapPath($global = false) {
        assert(!is_null($this->projectHandler));
        $mapName = $this->projectHandler->getMapName();
        if (file_exists(CARTOWEB_HOME
                        . $this->projectHandler->getPath('server_conf/'
                        . $mapName . '/', $mapName . '.map.php')
                        . $mapName . '.map.php')) {
            $mapFile = 'auto.' . $mapName;
            if (!$global) {
                $switchId = $this->getPluginManager()->getPlugin('layers')->getSwitchId();
                if (empty($switchId)) {
                    $mapFile .= '.all.map';
                } else {
                    $mapFile .= '.' . $switchId . '.map';
                }
            } else {
                $mapFile .= '.all.map';
            }
        } else {
            $mapFile = $mapName . '.map';
        }

        $path = $this->projectHandler->getPath('server_conf/' . $mapName . '/',
                                               $mapFile);
        return CARTOWEB_HOME . $path . $mapFile;
    }

    /**
     * Returns the file path of the main .ini file of the current mapfile. It
     * has the same location and name as the mapfile being used, but its
     * extension is .ini instead of .map
     * @return string the location of the .ini file related to the mapfile
     */
    public function getMapIniPath() {

        $mapName = $this->projectHandler->getMapName();
        $file = $mapName . '.ini';
        $path = $this->projectHandler->getPath('server_conf/' . $mapName . '/', $file);
        $iniPath = CARTOWEB_HOME . $path . $file;
        return $iniPath;
    }

    /**
     * Returns mean (mapfile & INI file) modification time.
     * @return int
     */
    public function getTimestamp() {
        $mapPath = $this->getMapPath(true);
        $iniPath = $this->getMapIniPath();

        $timestamp = (filemtime($mapPath) + filemtime($iniPath)) / 2;
        return (int)$timestamp;
    }

    /**
     * Update the ServerContext internal state, from the mapscript MapObj object.
     * This concerns the state that is updated once, when the MapObj has just
     * been created.
     */
    public function updateStateFromMapObj() {

        if (is_null($this->msMapObj))
            return;

        if (is_null($this->maxExtent))
            $this->maxExtent = clone($this->msMapObj->extent);

        $this->imageType = $this->msMapObj->imagetype;
    }

    /**
     * Instanciates a new Mapserver MapObj object.
     *
     * If $global is set to false, will use reduced map file depending on
     * plugin layers switch.
     * @param boolean
     * @return Mapscript MapObj
     */
    public function getMapObj() {

        $disablePHPModuleCheck = $this->getConfig()->disablePHPModuleCheck;

        if (!$this->msMapObj) {
            if (!extension_loaded('mapscript')) {
                if (!dl('php_mapscript.' . PHP_SHLIB_SUFFIX))
                    throw new CartoserverException("can't load mapscript " .
                                                   'library');
                $this->mapscriptLoaded = true;
            } else {
                // Safety check for Mapserver bug 1322:
                //   http://mapserver.gis.umn.edu/bugs/show_bug.cgi?id=1322
                // WARNING: this code should be in sync between:
                //  server/ServerContext.php, htdocs/info.php and scripts/info.php
                if (!$disablePHPModuleCheck && !$this->mapscriptLoaded
                    && !in_array(substr(php_sapi_name(), 0, 3), array('cgi', 'cli'))) {
                    throw new CartoserverException("You are not using PHP as " .
                        "a cgi and PHP Mapscript extension is loaded in your " .
                        "php.ini.\n As this will cause stability problems, " .
                        "CartoWeb stopped.\n You need to remove the " .
                        "php_mapscript extension loading of your php.ini " .
                        "file. \n If you want to remove this message, edit " .
                        "server_conf/server.ini and set the disablePHPModuleCheck " .
                        "parameter to true.");
                }
            }
            $mapPath = $this->getMapPath($this->globalMap);
            ms_ResetErrorList();
            $this->msMapObj = ms_newMapObj($mapPath);
            $this->checkMsErrors();

            if (!$this->msMapObj) { // could this happen?
                throw new CartoserverException("cannot open mapfile $mapId " .
                                               "for map $mapId");
            }

            $this->updateStateFromMapObj();
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
            $this->mapInfoHandler = new MapInfoHandler($this);
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
     * @return MapRequest
     */
    public function getMapRequest() {
        return $this->mapRequest;
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
     * @return ServerConfig
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Returns list of coreplugins names.
     * @return array
     */
    protected function getCorePluginNames() {
        return array_merge(parent::getCorePluginNames(), array('mapquery'));
    }

    /**
     * Loads the server plugins.
     */
    public function loadPlugins() {

        if (!is_null($this->pluginManager))
            return; /* already loaded */

        $this->pluginManager = new PluginManager(PluginManager::SERVER,
                                                 $this->projectHandler);
        $corePluginNames = $this->getCorePluginNames();

        $this->pluginManager->loadPlugins($corePluginNames, $this);

        $iniPath = $this->getMapIniPath();
        $iniArray = parse_ini_file($iniPath);
        if (isset($iniArray['mapInfo.loadPlugins']))
            $pluginNames = explode(',', $iniArray['mapInfo.loadPlugins']);
        else
            $pluginNames = array();
        $pluginNames = array_map('trim', $pluginNames);
        foreach ($pluginNames as $key => $val) {
            if (empty($val))
                unset($pluginNames[$key]);
        }
        $this->pluginManager->loadPlugins($pluginNames, $this);
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
        $serverLayer = $this->getMapInfo()->layersInit->getLayerById($layerId);
        if (!$serverLayer)
            throw new CartoserverException("layerid $layerId not found");

        // retrieve from metadata
        $msLayer = $this->msMapObj->getLayerByName($serverLayer->msLayer);
        $this->checkMsErrors();

        if (empty($msLayer)) {
            return NULL;
        }
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
     public function getIdAttribute($layerId) {
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
     public function getIdAttributeType($layerId) {
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
