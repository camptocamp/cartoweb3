<?php

class MapInfoHandler {
    private $log;
    private $serverContext;

    public $configMapPath;

    public $mapInfo;

    function __construct($serverContext, $mapId) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->serverContext = $serverContext;
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
            if (empty($layer->msLayer))
                $layer->msLayer = $layer->id; 

        }
        
        return $mapInfo;
    }

    private function loadMapInfo($mapId) {

        $this->configMapPath = $this->serverContext->config->configPath . 
            $mapId . '/';

        $configStruct = StructHandler::loadFromIni($this->configMapPath .
                                            $mapId . '.ini');
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