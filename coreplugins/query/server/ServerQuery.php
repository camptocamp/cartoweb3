<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
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
        if (!is_null($requ->layerIds) && count($requ->layerIds) > 0) {
            return $requ->layerIds;
        }
        $plugins = $this->serverContext->pluginManager;
        return $plugins->layers->getRequestedLayerNames();
    } 

    private function encodingConversion($str) {
        // FIXME: $str is asserted to be iso8851-1 
        return utf8_encode($str);
    }

    private function arrayEncodingConversion($array) {
        $ret = array();
        foreach($array as $str) {
            $ret[] = $this->encodingConversion($str);
        }
        return $ret;
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
            
            if (empty($layerResult->fields)) {
                $fields = array_keys($shape->values);
                $layerResult->fields = $this->arrayEncodingConversion($fields);
            }
            $values = array_values($shape->values);
            $resultElement->values = $this->arrayEncodingConversion($values);
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