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
   
    /**
     * Determines activated layers by recursively browsing LayerGroups.
     * Only keeps Layer objects.
     */
    private function fetchChildrenFromLayerGroup($layersList) {
        if (!$layersList || !is_array($layersList)) return false;

        $cleanList = array();
        foreach ($layersList as $key => $layerId) {
            $serverLayer = $this->getMapInfo()->getLayerById($layerId);
            if (!$serverLayer) continue;

            // removes non Layer objects
            if ($serverLayer instanceof Layer) $cleanList[] = $layerId;

            // no use to browse more if object is not a LayerGroup
            if (!$serverLayer instanceof LayerGroup) continue;
            
            // recursively gets sublayers from current layer children
            $newList = $this->fetchChildrenFromLayerGroup($serverLayer->children);
            if ($newList) {
                $cleanList = array_merge($cleanList, $newList);
                $cleanList = array_unique($cleanList);
            }
        }
        return $cleanList;
    }

    function getRequestedLayerNames() {
        $this->requestedLayerNames =& 
            $this->fetchChildrenFromLayerGroup($this->requestedLayerNames);

        if(!$this->requestedLayerNames) return array();

        $this->requestedLayerNames = array_unique($this->requestedLayerNames);
        return $this->requestedLayerNames;
    }

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;

        $this->requestedLayerNames = $requ;

        if (!is_array($requ)) {
            $this->log->info("invalid layers request: doing nothing");
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
