<?php

class ServerQuery extends ServerCorePlugin {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getType() {
        return ServerPlugin::TYPE_POST_DRAWING;
    }

    private function getQueryLayerNames($requ) {
    
        if ($requ->layerIds && count($requ->layerIds) > 0)
            return $requ->layerIds;
        
        $plugins = $this->serverContext->pluginManager;
        return $plugins->layers->getRequestedLayerNames();
    } 

    function queryLayer($layerId, $shape, $queryArgs) {
    
        $msMapObj = $this->serverContext->msMapObj;

        if (!($shape instanceof Bbox)) {
            throw new CartoserverException("shapes other than bbox unsupported");
        }
        $rect = ms_newRectObj();

        $bbox = $shape;
        $rect->setextent($bbox->minx, $bbox->miny, $bbox->maxx, $bbox->maxy);
        
        $mapInfo = $this->serverContext->mapInfo;
        $serverLayer = $mapInfo->getLayerById($layerId);

        if (!$serverLayer) 
            throw new CartoserverException("layerid $layerId not found");

        $layerResult = new LayerResult();
        $layerResult->layerId = $layerId;
        $layerResult->numResults = 0;
        
        $layerResult->resultElements = array();

        $msLayer = @$msMapObj->getLayerByName($serverLayer->msLayer);
        $this->serverContext->checkMsErrors();
        
        if (empty($msLayer)) {
            $this->log->warn("Can't retrieve layer " . $serverLayer->msLayer);
            return $layerResult;
        }
        
        // layer has to be activated for query
        $msLayer->set('status', MS_ON);
        $ret = @$msLayer->queryByRect($rect);

        $this->serverContext->resetMsErrors();

        if ($ret != MS_SUCCESS || 
            $msLayer->getNumResults() == 0) 
            return $layerResult;

        if (!isset($queryArgs->startIndex))
            $queryArgs->startIndex = 0;
        // eventually put it in config
        define('MAX_RESULTS', 10000);
        if (!isset($queryArgs->maxResults))
            $queryArgs->maxResults = MAX_RESULTS;

        $msLayer->open();

        for ($i = $queryArgs->startIndex; 
            $i < $msLayer->getNumResults() && 
            $i - $queryArgs->startIndex < $queryArgs->maxResults; $i++) {

            $result = $msLayer->getResult($i);
            $shape = $msLayer->getShape($result->tileindex, $result->shapeindex);

            $this->log->debug("shape : " .  $i);
            $this->log->debug($shape);

            $resultElement = new ResultElement();            
            $resultElement->id = $i;
            
            if (empty($layerResult->fields))
                $layerResult->fields = array_keys($shape->values);
            $resultElement->values = array_values($shape->values);
            
            $resultElement->tileindex = $shape->tileindex;
            $resultElement->classindex = $shape->classindex;
            $layerResult->resultElements[] = $resultElement;
            $layerResult->numResults++;
        }  
        $msLayer->close();
        return $layerResult;
    }

    function getResultFromRequest($requ) {
    
        $this->log->debug("Get result from request: ");
        $this->log->debug($requ);
        // FIXME: will go away
        $requ->bbox = StructHandler::unserialize($requ->bbox, 'Bbox');

        $queryArgs = new stdclass();
        // TODO: from config or from request
        $queryArgs->maxResults = 10;  
        
        $queryResult = new QueryResult();
        $queryResult->layerResults = array();
        foreach ($this->getQueryLayerNames($requ) as $queryLayerName) {
            $layerResult = $this->queryLayer($queryLayerName, $requ->bbox, $queryArgs);
            $queryResult->layerResults[] = $layerResult;
        }
        
        return $queryResult;
    }    
}

?>