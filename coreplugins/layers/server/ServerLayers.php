<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ServerLayers extends ServerPlugin
                   implements CoreProvider {
    private $log;

    private $requestedLayerNames;
    private $mapInfo;
    
    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    private function getMapInfo() {
        if(!$this->mapInfo) $this->mapInfo =& $this->serverContext->getMapInfo();
        return $this->mapInfo;
    }
   
    function getRequestedLayerNames() {
        if(!$this->requestedLayerNames) return array();
        return $this->requestedLayerNames;
    }

    function handleCorePlugin($requ) {

        $msMapObj = $this->serverContext->getMapObj();

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
            
            $msLayer = $this->getMapInfo()->getMsLayerById($msMapObj, $requLayerId);
            $msLayer->set('status', MS_ON);
        }
    }
}
?>
