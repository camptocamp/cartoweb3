<?php

class ServerSelection extends ServerPlugin {

    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getType() {
        // has to be called before hilight plugin
        return ServerPlugin::TYPE_INIT;
    }

    private function getClassItem($layerId) {

        //TODO: error check     
        $mapInfo = $this->serverContext->mapInfo;
        $serverLayer = $mapInfo->getLayerById($layerId);
        $msMapObj = $this->serverContext->msMapObj;
        $msLayer = $msMapObj->getLayerByName($serverLayer->msLayer);
        $classitem = $msLayer->classitem;
        if (empty($classitem))
            throw new CartoserverException("no classitem for layer $layerId");
        return $classitem;
    }

    private function getIdsFromResult($layerId, LayerResult $layerResult) {
     
        $classitem = $this->getClassItem($layerId);     
     
        $resultElements = $layerResult->resultElements;

        $ids = array();
        foreach($resultElements as $resultElement) {
            if (!array_key_exists($classitem, $resultElement->fields))
                throw new CartoserverException("an item has no $classitem field");
            $ids[] = $resultElement->fields[$classitem];
        }
        return $ids;
    }
  
    private function queryLayer($layerId, $shape) {
        
        $plugins = $this->serverContext->pluginManager;
        
        // FIXME: PERFORMANCE add a queryArgs which does not fetch metadata 
        // (but needs an id, retrieved from classItem !!)
        $queryArgs = new stdclass();
        
        return $plugins->query->queryLayer($layerId, $shape, $queryArgs);
    }

    private function array_union($a, $b) {
        return array_unique(array_merge($a, $b));   
    }

    private function array_xor($a, $b) {
        return array_diff($this->array_union($a, $b), array_intersect($a, $b));   
    }  
  
    private function mergeIds($previousIds, $newIds, $policy) {

        $this->log->debug("previous Ids: " . var_export($previousIds, true));
        $this->log->debug("new Ids: " . var_export($newIds, true));

        switch($policy) {
        case SelectionRequest::POLICY_XOR:
            return $this->array_xor($previousIds, $newIds);
            break;
        case SelectionRequest::POLICY_UNION:
            return $this->array_union($previousIds, $newIds);
            break;
        case SelectionRequest::POLICY_INTERSECTION:
            return array_intersect($previousIds, $newIds);
            break;
        default:
            throw new CartoserverException("invalid selection request policy $policy");
        }
    }
  
    function getResultFromRequest($requ) {

        // FIXME: will go away
        $requ->bbox = StructHandler::unserialize($requ->bbox, 'Bbox');
        
        // TODO: mechanism to fetch request from other plugins
        $hilightRequest = @$this->serverContext->mapRequest->hilightRequest;
        
        //array_push($hilightRequest->selectedIds, "45");
        
        if (empty($hilightRequest))
            throw new CartoserverException("serverRequest needs a hilightRequest");
        
        $layerId = $hilightRequest->layerId;
        
        $layerResult = $this->queryLayer($layerId, $requ->bbox);
        $newIds = $this->getIdsFromResult($layerId, $layerResult);
        
        $mergedIds = $hilightRequest->selectedIds = $this->mergeIds($hilightRequest->selectedIds,
                                        $newIds, $requ->policy);
        $this->log->debug("merged ids are: " . var_export($mergedIds, true));
        $hilightRequest->selectedIds = $mergedIds; 
        
        $selectionResult = new SelectionResult();
        
        $selectionResult->layerId = $hilightRequest->layerId;
        $selectionResult->selectedIds = $mergedIds; 

        return $selectionResult;
    }
}
?>