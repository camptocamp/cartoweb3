<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ServerQuery extends ClientResponderAdapter {
    private $log;
    private $drawQuery = false;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    private function getQueryLayerNames($requ) {
        if (!is_null($requ->layerIds) && count($requ->layerIds) > 0) {
            return $requ->layerIds;
        }
        $plugins = $this->serverContext->getPluginManager();
        return $plugins->layers->getRequestedLayerNames();
    } 

    private function encodingConversion($str) {
        // FIXME: $str is asserted to be iso8851-1 
        return utf8_encode($str);
    }

    private function arrayEncodingConversion($array) {
        $ret = array();
        foreach($array as $key => $str) {
            $ret[$this->encodingConversion($key)] = $this->encodingConversion($str);
        }
        return $ret;
    }

    private function filterReturnedAttributes($msLayer, $values) {

        $returnedAttributesMetadataName = 'query_returned_attributes';
        
        $retAttrString = $msLayer->getMetaData($returnedAttributesMetadataName);
        if (empty($retAttrString)) {
            // fallback to header property for compatibility

            $retAttrString = $msLayer->header;
            if (!empty($retAttrString))
                $this->log->warn("Using compatibility header property for layer instead of " .
                    "$returnedAttributesMetadataName metadata field, please update your " .
                    "Mapfile !!");
        }
        if (empty($retAttrString)) {
                $this->log->warn('no filter for returned attributes, returning everything');
                return $values;
        }

        $returnedAttributes = explode(' ', $retAttrString);
        
        $filteredValues = array();
        foreach($returnedAttributes as $key) {
            if (isset($values[$key]))
                $filteredValues[$key] = $values[$key];
        }

        if (empty($filteredValues))
            throw new CartoserverException('no attributes to return from query');
        return $filteredValues;
    }

    /**
     * TO BE DELETED
     */
    function queryLayer($layerId, $shape, $queryArgs) {
    
        $msMapObj = $this->serverContext->getMapObj();

        if (!($shape instanceof Bbox)) {
            throw new CartoserverException("shapes other than bbox unsupported");
        }
        $rect = ms_newRectObj();

        $bbox = $shape;
        $rect->setextent($bbox->minx, $bbox->miny, $bbox->maxx, $bbox->maxy);
        
        $mapInfo = $this->serverContext->getMapInfo();
        $msLayer = $mapInfo->getMsLayerById($msMapObj, $layerId);

        $layerResult = new LayerResult();
        $layerResult->layerId = $layerId;
        $layerResult->numResults = 0;
        
        $layerResult->resultElements = array();
        
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
        if (!defined('MAX_RESULTS'))
            define('MAX_RESULTS', 10000);
        
        if (!isset($queryArgs->maxResults))
            $queryArgs->maxResults = MAX_RESULTS;

        $msLayer->open();

        $idAttribute = $this->serverContext->getIdAttribute($layerId);

        for ($i = $queryArgs->startIndex; 
            $i < $msLayer->getNumResults() && 
            $i - $queryArgs->startIndex < $queryArgs->maxResults; $i++) {

            $result = $msLayer->getResult($i);
            $shape = $msLayer->getShape($result->tileindex, $result->shapeindex);

            $this->log->debug("shape : " .  $i);
            $this->log->debug($shape);

            $resultElement = new ResultElement();            
            if (!is_null($idAttribute))
                $resultElement->id = $this->encodingConversion(
                                                $shape->values[$idAttribute]);
            
            $filteredValues = $this->filterReturnedAttributes($msLayer, 
                                                              $shape->values);
            if (empty($layerResult->fields)) {
                $fields = array_keys($filteredValues);
                $layerResult->fields = $this->arrayEncodingConversion($fields);
            }
            $values = array_values($filteredValues);
            $resultElement->values = $this->arrayEncodingConversion($values);
            $layerResult->resultElements[] = $resultElement;
            $layerResult->numResults++;
        }  
        $msLayer->close();
        return $layerResult;
    }

    function getQueryTable($layerId, $shape, $queryArgs) {
    
        $msMapObj = $this->serverContext->getMapObj();

        if (!($shape instanceof Bbox)) {
            throw new CartoserverException("shapes other than bbox unsupported");
        }
        $rect = ms_newRectObj();

        $bbox = $shape;
        $rect->setextent($bbox->minx, $bbox->miny, $bbox->maxx, $bbox->maxy);
        
        $mapInfo = $this->serverContext->getMapInfo();
        $msLayer = $mapInfo->getMsLayerById($msMapObj, $layerId);

        $table = new Table();
        $table->tableId = $layerId;
        $table->tableTitle = $layerId;
        $table->numRows = 0;
        
        $table->rows = array();
        
        // layer has to be activated for query
        $msLayer->set('status', MS_ON);
        $ret = @$msLayer->queryByRect($rect);

        $this->serverContext->resetMsErrors();

        if ($ret != MS_SUCCESS || 
            $msLayer->getNumResults() == 0) 
            return $table;

        if (!isset($queryArgs->startIndex))
            $queryArgs->startIndex = 0;

        // eventually put it in config
        if (!defined('MAX_RESULTS'))
            define('MAX_RESULTS', 10000);
        
        if (!isset($queryArgs->maxResults))
            $queryArgs->maxResults = MAX_RESULTS;

        $msLayer->open();

        $idAttribute = $this->serverContext->getIdAttribute($layerId);

        for ($i = $queryArgs->startIndex; 
            $i < $msLayer->getNumResults() && 
            $i - $queryArgs->startIndex < $queryArgs->maxResults; $i++) {

            $result = $msLayer->getResult($i);
            $shape = $msLayer->getShape($result->tileindex, $result->shapeindex);

            $this->log->debug("shape : " .  $i);
            $this->log->debug($shape);

            $row = new TableRow();            
            if (is_null($idAttribute)) {
                $row->cells = array();
            } else {
                $row->cells['id'] = $this->encodingConversion(
                                                $shape->values[$idAttribute]);
            }
            
            $filteredValues = $this->filterReturnedAttributes($msLayer, 
                                                              $shape->values);
            if (empty($table->columnTitles)) {
                if (!is_null($idAttribute)) {
                    $columnTitles['id'] = 'Id';
                }
                foreach (array_keys($filteredValues) as $columnId) {
                    $columnTitles[$columnId] = $columnId;
                }
                $table->columnTitles = $this->arrayEncodingConversion($columnTitles);
            }

            $row->cells = array_merge($row->cells,
                                      $this->arrayEncodingConversion($filteredValues));
            $table->rows[] = $row;
            $table->numRows++;
        }  
        $msLayer->close();
        return $table;
    }

    /**
     * TO BE DELETED
     */
    function getIdsFromLayerResult(LayerResult $layerResult) {

        $resultElements = $layerResult->resultElements;
        
        $ids = array();
        foreach($resultElements as $resultElement) {
            $ids[] = $resultElement->id;
        }
        return $ids;
    }

    function getIdsFromTable(Table $table) {

        $rows = $table->rows;
        
        $ids = array();
        foreach($rows as $row) {
            $ids[] = $row->cells['id'];
        }
        return $ids;
    }

    private function getLayerSelectionRequest(Table $table) {
    
        $selectionRequest = new SelectionRequest();

        $selectionRequest->layerId = $table->tableId;
        $selectionRequest->selectedIds = $this->getIdsFromTable($table);
        
        return $selectionRequest;
    }

    private function hilightSelectionRequest(QueryResult $queryResult) {

        $plugins = $this->serverContext->getPluginManager();

        if (empty($plugins->hilight))
            throw new CartoserverException("hilight plugin not loaded, and needed " .
                    "for the query hilight drawing");
print_r($queryResult);
        foreach ($queryResult->tableGroup->tables as $table) {
            $selectionRequest = $this->getLayerSelectionRequest($table);
            $plugins->hilight->hilightLayer($selectionRequest);
        }
    }

    function drawQuery() {
        return $this->drawQuery;
    }

    // dependency: has to be called before drawMainMap(), so that drawQuery() may draw the query
    function handlePreDrawing($requ) {
        
        $this->log->debug("handle core plugin: ");
        $this->log->debug($requ);

        $queryArgs = new stdclass();
        // TODO: from config or from request
        $queryArgs->maxResults = 10;  
        
        $queryResult = new QueryResult();
                
        $queryResult->tableGroup = new TableGroup();
        $queryResult->tableGroup->groupId = "query";
        $queryResult->tableGroup->groupTitle = "Query";
        foreach ($this->getQueryLayerNames($requ) as $queryLayerName) {
            $table = $this->getQueryTable($queryLayerName, $requ->bbox, $queryArgs);
            $queryResult->tableGroup->tables[] = $table;
        }
        
        if ($this->getConfig()->drawQueryUsingHilight)
            $this->hilightSelectionRequest($queryResult);
        else
            $this->drawQuery = true;
        
        return $queryResult;
    }    
}

?>