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

    private function loadMapInfo($mapId) {

        $this->configMapPath = $this->serverContext->config->configPath . 
            $mapId . '/';

        $configStruct = StructHandler::loadFromIni($this->configMapPath .
                                            $mapId . '.ini');
        
        $this->mapInfo = StructHandler::unserialize($configStruct->mapInfo, 'MapInfo');
        
        return $this->mapInfo;
    }

    function getMapInfo() {

        return $this->mapInfo;
    }

    function fillDynamic($serverContext) {

        $initialMapInfo = $this->mapInfo;
        $layers = $initialMapInfo->getLayersByType(LayerBase::TYPE_LAYER);
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