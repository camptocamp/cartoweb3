<?php
/**
 * Construction of the MapInfo structure.
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

require_once(CARTOWEB_HOME . 'server/ServerMapInfoCache.php');

/**
 * This class constructs the MapInfo structure. It will use the MapInfo cache
 * when available.
 * 
 * @package Server
 */
class MapInfoHandler {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var ServerContext
     */
    private $serverContext;

    /**
     * @var MapInfo
     */
    public $mapInfo;
    
    /**
     * @var ProjectHandler
     */
    private $projectHandler;

    /**
     * @var string
     */
    private $iniPath;
    
    /**
     * @var string
     */
    private $mapId;

    /**
     * Constructor
     * @param ServerContext
     */
    public function __construct(ServerContext $serverContext) {

        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->serverContext = $serverContext;
        $this->projectHandler = $serverContext->getProjectHandler();
        $this->mapId = $serverContext->getMapId();
    }

    /**
     * Returns the server context
     * @return ServerContext
     */
    public function getServerContext() {
        return $this->serverContext;
    }

    /**
     * Fills dynamic general map information, like map name.
     */
    private function fillDynamicMap() {

        $mapInfo = $this->mapInfo;
        $msMapObj = $this->serverContext->getMapObj();
        $mapInfo->mapLabel = $msMapObj->name;

        $bbox = new Bbox();
        $bbox->setFromMsExtent($msMapObj->extent);
        $mapInfo->extent = $bbox;
    }
    
    /**
     * Fills dynamic general keymap information.
     */
    private function fillDynamicKeymap() {
        
        $msMapObj = $this->serverContext->getMapObj();
        $referenceMapObj = $msMapObj->reference;

        $this->serverContext->checkMsErrors();

        $dim = new Dimension($referenceMapObj->width, $referenceMapObj->height);
        $bbox = new Bbox();
        $bbox->setFromMsExtent($referenceMapObj->extent);
        
        $this->mapInfo->keymapGeoDimension = new GeoDimension();
        $this->mapInfo->keymapGeoDimension->dimension = $dim;
        $this->mapInfo->keymapGeoDimension->bbox = $bbox;
    }
    
    /**
     * Constructs a MapInfo object, by reading ini files and calling plugins.
     * This method should not be called directly. Use getMapInfo() instead, 
     * which manages the cache.
     * @return MapInfo
     */
    public function loadMapInfo() {

        // For now, complete mapfile should be used
        $this->serverContext->globalMap = true;
    
        $mapName = $this->projectHandler->getMapName();
        $iniPath = $this->serverContext->getMapIniPath();
        $configStruct = StructHandler::loadFromIni($iniPath);

        if (isset($configStruct->mapInfo->layers))
            throw new CartoserverException('Your layers configuration is obsolete. ' .
                    'See http://dev.camptocamp.com/c2cwiki/IncompatibleUpdates');

        $this->mapInfo = new MapInfo();
        $this->mapInfo->unserialize($configStruct->mapInfo);

        // if no initialMapState defined, create a default one.
        if (empty($this->mapInfo->initialMapStates)) {
            $initialMapState = new InitialMapState();
            $initialMapState->id = 'default';
            $initialMapState->layers = array();
            
            $this->mapInfo->initialMapStates[] = $initialMapState;
        }
        
        // sets default location of initial map states if not set.
        if (isset($this->mapInfo->initialMapStates)) {
            foreach ($this->mapInfo->initialMapStates as $state) {
                if (!isset($state->location)) {
                    $this->serverContext->getMapObj();
                    $state->location = new InitialLocation();
                    $state->location->bbox = new Bbox();
                    $state->location->bbox->setFromMsExtent(
                                    $this->serverContext->getMaxExtent());
                }
            }
        }        

        $this->mapInfo->timestamp = $this->serverContext->getTimestamp();
        $this->fillDynamicMap();
        $this->fillDynamicKeymap();
        
        $pluginManager = $this->serverContext->getPluginManager();
        $pluginManager->callPluginsImplementing('InitProvider', 'getInit');
        
        return $this->mapInfo;
    }

    /**
     * @return MapInfo
     */
    public function getMapInfo() {
        if (!$this->mapInfo) {
            $serverMapInfoCache = new ServerMapInfoCache($this);
            $this->mapInfo = $serverMapInfoCache->getMapInfo();
        }
        return $this->mapInfo;
    }
}
?>
