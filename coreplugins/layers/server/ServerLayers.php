<?php

class ServerLayers extends ServerCoreplugin {
    private $log;

	private $requestedLayerNames;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

	function getRequestedLayerNames() {
		if (!$this->requestedLayerNames)
			return array();
		return $this->requestedLayerNames;
	}

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;

		$this->requestedLayerNames = $requ;

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

        foreach ($this->getRequestedLayerNames() as $requLayerId) {
            $this->log->debug("testing id $requLayerId");
 
            $mapInfo = $this->serverContext->mapInfo;
            $serverLayer = $mapInfo->getLayerById($requLayerId);

            if (!$serverLayer) {
                $this->log->warn("can't find serverLayer $requLayerId");
                continue;
            }

            $msLayer = @$msMapObj->getLayerByName($serverLayer->msLayer);
            if ($msLayer) {
                $msLayer->set('status', MS_ON);
            } else {
                $this->log->warn("can't find msLayer " . $serverLayer->msLayer);
            }
        }
    }
}
?>