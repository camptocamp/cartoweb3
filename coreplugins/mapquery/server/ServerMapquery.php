<?php
/**
 * @package CorePlugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */

/**
 * A service plugin to perform queries based on a set of selected id's
 * @package CorePlugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
class ServerMapquery extends ServerPlugin {

    /**
     * @var Logger
     */
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Returns Ids for generic query
     * @param string
     * @param string
     * @param array
     * @return array
     */
    private function genericQueryString($idAttribute, $idType, $selectedIds) {
    
        // FIXME: does queryByAttributes support multiple id's for dbf ?
        $queryString = array();
        foreach($selectedIds as $id) {
            if ($idType == 'string')
                $queryString[] = "'[$idAttribute]' = '$id'";
            else
                /* TODO */ 
                x('todo_int_query_string');
        } 
        return array('(' . implode(' OR ', $queryString) . ')');
    }
    
    /**
     * Returns Ids for database query
     * @param string
     * @param string
     * @param array
     * @return array
     */
    private function databaseQueryString($idAttribute, $idType, $selectedIds) {
        if (count($selectedIds) == 0)
            return array('false');
        if ($idType != 'string')
            x('todo_database_int_query_string');
        $queryString = implode("','", $selectedIds);
        return array("$idAttribute in ('$queryString')");
    }

    /**
     * Returns true if layer is linked to a database
     * @param msLayer
     * @return boolean
     */ 
    private function isDatabaseLayer($msLayer) {
        return $msLayer->connectiontype == MS_POSTGIS;
    }

    /**
     * Extracts all shapes in the given msLayer, and returns them in an array
     * @param msLayer the layer from which to retrieve shapes
     * @return array the array of result shapes in the given layer
     */
    private function extractResults($msLayer, $mayIgnore) {
        
        $msLayer->open();
        $results = array();

        $numResults = $msLayer->getNumResults();

        $ignoreQueryThreshold = $this->getConfig()->ignoreQueryThreshold;
        if ($mayIgnore && is_numeric($ignoreQueryThreshold) && 
                                    $numResults > $ignoreQueryThreshold) {
            $this->getServerContext()->addMessage($this, 'queryIgnored', 
                    'Query spanned too many objects, it was ignored');
            return array();
        }

        $maxResults = $this->getConfig()->maxResults;
        if (is_numeric($maxResults) && $numResults > $maxResults) {
            $this->getServerContext()->addMessage($this, 'maxResultsHit', 
                'This query hit the maximum number of results,' .
                ' truncating results');
            $numResults = $maxResults;
        }
        
        for ($i = 0; $i < $numResults; $i++) {
            $result = $msLayer->getResult($i);
            $shape = $msLayer->getShape($result->tileindex, $result->shapeindex);

            $results[] = $shape;
        }
        $msLayer->close();
        return $results;        
    }

    /**
     * Performs a query on a layer using attributes
     * @param ServerContext
     * @param msLayer
     * @param string
     * @param string
     * @return array an array of shapes
     */
    private function queryLayerByAttributes(ServerContext $serverContext, 
                                            $msLayer, $idAttribute, $query) { 
        $log =& LoggerManager::getLogger(__METHOD__);
        
        // save extent, and set it to max extent
        $msMapObj = $serverContext->getMapObj();
        $savedExtent = clone($msMapObj->extent); 
        $maxExtent = $serverContext->getMaxExtent();
        $msMapObj->setExtent($maxExtent->minx, $maxExtent->miny, 
                             $maxExtent->maxx, $maxExtent->maxy);
        
        $log->debug("queryLayerByAttributes layer: $msLayer->name " .
                "idAttribute: $idAttribute query: $query");
        // layer has to be activated for query
        $msLayer->set('status', MS_ON);
        $ret = @$msLayer->queryByAttributes($idAttribute, $query, MS_MULTIPLE);

        $this->log->debug("Query on layer " . $msLayer->name . ": queryByAttributes($idAttribute, $query)");
                
        if ($ret == MS_FAILURE) {
            throw new CartoserverException("Attribute query returned no " .
                    "results. Layer: $msLayer->name, idAttribute: $idAttribute," .
                    " query: $query"); 
        }

        $serverContext->checkMsErrors();
        // restore extent
        $msMapObj->setExtent($savedExtent->minx, $savedExtent->miny, 
                             $savedExtent->maxx, $savedExtent->maxy);
        
        return $this->extractResults($msLayer, false);
    }

    /**
     * Checks if layer's connection type is implemented
     * @param msLayer
     */
    private function checkImplementedConnectionTypes($msLayer) {
    
        $implementedConnectionTypes = array(MS_SHAPEFILE, MS_POSTGIS);
        
        if (in_array($msLayer->connectiontype, $implementedConnectionTypes))
            return;
            
        throw new CartoserverException("Layer to center on has an unsupported " .
                "connection type: $msLayer->connectiontype");
    }
    
    /**
     * Performs a query based on a set of selected id's on a given layer
     * @param IdSelection The selection to use for the query. It contains a
     *                    layer name and a set of id's
     * @return array an array of shapes
     */
    public function queryByIdSelection(IdSelection $idSelection) {

        $serverContext = $this->getServerContext();
        $mapInfo = $serverContext->getMapInfo();
        $msLayer = $mapInfo->getMsLayerById($serverContext->getMapObj(), 
                                            $idSelection->layerId);
        
        $idAttribute = $idSelection->idAttribute;
        if (is_null($idAttribute)) {
            $idAttribute = $serverContext->getIdAttribute($idSelection->layerId);
        }
        if (is_null($idAttribute)) {
            throw new CartoserverException("can't find idAttribute for layer " .
                    "$idSelection->layerId");
        }
        $idType = $idSelection->idType;
        if (is_null($idType)) {
            $idType = $serverContext->getIdAttributeType($idSelection->layerId);
        }

        self::checkImplementedConnectionTypes($msLayer);

        $queryStringFunction = (self::isDatabaseLayer($msLayer)) ?
            'databaseQueryString' : 'genericQueryString';

        $ids = Encoder::decode($idSelection->selectedIds, 'config');

        // FIXME: can shapefiles support queryString for multiple id's ?
        //  if yes, then improve this handling. 

        $queryString = self::$queryStringFunction($idAttribute, $idType, $ids); 
        $results = array();
        foreach($queryString as $query) {
            $new_results = self::queryLayerByAttributes($serverContext, $msLayer, 
                                             $idAttribute, $query);
            $results = array_merge($results, $new_results);
        }
        return $results;
    }      

    /**
     * Performs a query based on a bbox on a given layer
     * @param string layerId
     * @param Bbox
     * @return array an array of shapes
     */
    public function queryByBbox($layerId, Bbox $bbox) {

        $msMapObj = $this->serverContext->getMapObj();

        $rect = ms_newRectObj();
        $rect->setextent($bbox->minx, $bbox->miny, $bbox->maxx, $bbox->maxy);
        
        $mapInfo = $this->serverContext->getMapInfo();
        $msLayer = $mapInfo->getMsLayerById($msMapObj, $layerId);
        
        // layer has to be activated for query
        $msLayer->set('status', MS_ON);
        $ret = @$msLayer->queryByRect($rect);
        
        $this->log->debug("Query on layer $layerId: queryByRect($rect)");        
        
        $this->serverContext->resetMsErrors();

        if ($ret != MS_SUCCESS || 
            $msLayer->getNumResults() == 0) 
            return array();

        return $this->extractResults($msLayer, true);
    }
}

?>
