<?php
/**
 * @package Server
 * @version $Id$
 */

/**
 * @package Server
 */
class MapInfoHandler {
    private $log;
    private $serverContext;

    public $configMapPath;

    public $mapInfo;
    
    private $projectHandler;

    function __construct($serverContext, $mapId, $projectHandler) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->serverContext = $serverContext;
        $this->projectHandler = $projectHandler;
        $this->loadMapInfo($mapId);
    }

    /**
     * Process mapInfo after being loaded from the configuration.
     * Does some basic consistency checks, fills some information.
     */
    private function fixupMapInfo(MapInfo $mapInfo) {
    
        foreach ($mapInfo->layers as $layer) {
            if (empty($layer->label))
                $layer->label = $layer->id; 
            if ($layer instanceof Layer && empty($layer->msLayer))
                $layer->msLayer = $layer->id; 
        }
        
        return $mapInfo;
    }

    private function loadMapInfo($mapId) {

        // Now server.ini can be in a different directory than mapfile !
        // $this->configMapPath = $this->serverContext->config->configPath . 
        //    $mapId . '/';
        $mapName = $this->projectHandler->getMapName();
        $iniPath = $this->projectHandler->getPath(CARTOSERVER_HOME,
                                'server_conf/' . $mapName . '/', $mapName . '.ini');
        $configStruct = StructHandler::loadFromIni(CARTOSERVER_HOME .
                                $iniPath . $mapName . '.ini');
        $this->mapInfo = new MapInfo();
        $this->mapInfo->unserialize($configStruct->mapInfo);
        $this->mapInfo = $this->fixupMapInfo($this->mapInfo);
        return $this->mapInfo;
    }

    function getMapInfo() {
         return $this->mapInfo;
    }

    function fillDynamic($serverContext) {

        $initialMapInfo = $this->mapInfo;
        $layers = $initialMapInfo->getLayers();
        $msMapObj = $serverContext->msMapObj;

        foreach ($layers as $layer) {

            $msLayer = $msMapObj->getLayerByName($layer->msLayer);
            if (!$msLayer)
                throw new CartoserverException("Could not find msLayer " . $layer->msLayer);
            
            for ($i = 0; $i < $msLayer->numclasses; $i++) {
                $msClass = $msLayer->GetClass($i);
                $layerClass = new LayerClass();

                copy_vars($msClass, $layerClass);
                $layerClass->id = $layer->id . '_class_' . $i;
                
                $initialMapInfo->addChildLayerBase($layer, $layerClass);
            }
        }
    }
}
?>
