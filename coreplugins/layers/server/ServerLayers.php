<?php

class ServerLayers extends ServerCoreplugin {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }
    
    function getRequestName() {
        return 'layerSelectionRequest';
    }

    function getResultName() {
        return false;
    }

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;

        if (!count($requ)) {
            $this->log->info("no layers request: doing nothing");
            return;
        }

        $this->log->debug("layers to draw: ");
        $this->log->debug($requ);
      
        // disable all layers
        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
            $msLayer = $msMapObj->getLayer($i);
            $msLayer->set('status', MS_OFF);
        }

        foreach ($requ as $requLayerId) {
            $this->log->debug("testing id $requLayerId");
 
            $initialMapInfo = $this->serverContext->mapInfo;
            $serverLayer = $initialMapInfo->getLayerById($requLayerId);

            if (!$serverLayer) {
                $this->log->warn("can't find serverLayer $requLayerId");
                continue;
            }

            $msLayer = @$msMapObj->getLayerByName($serverLayer->msLayer);
            if ($msLayer) {
                $msLayer->set('status', MS_ON);
            } else {
                $this->log->warn("can't find msLayer $msLayerId");
            }
        }
    }
}
?>