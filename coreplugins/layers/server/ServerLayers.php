<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ServerLayers extends ServerCoreplugin {
    private $log;

    private $requestedLayerNames;
    private $mapInfo;
    
    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    private function getMapInfo() {
        if(!$this->mapInfo) $this->mapInfo =& $this->serverContext->mapInfo;
        return $this->mapInfo;
    }
   
    function getRequestedLayerNames() {
        if(!$this->requestedLayerNames) return array();
        return $this->requestedLayerNames;
    }

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;

        $layerIds = $requ->layerIds;
        $this->requestedLayerNames = $layerIds;
        
        if (!is_array($layerIds)) {
            throw new CartoclientException('Invalid layer request: ' .
            'layerIds not array');
            return;
        }

        $this->log->debug("layers to draw: ");
        $this->log->debug($layerIds);
      
        // disable all layers
        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
            $msLayer = $msMapObj->getLayer($i);
            $msLayer->set('status', MS_OFF);
        }

        foreach ($this->getRequestedLayerNames() as $requLayerId) {
            $this->log->debug("testing id $requLayerId");
            
            $serverLayer = $this->getMapInfo()->getLayerById($requLayerId);

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
