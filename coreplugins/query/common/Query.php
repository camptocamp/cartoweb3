<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class QueryRequest {

    public $bbox;
    
    // if empty: server should select layers from layersRequest  
    public $layerIds;
    
    // retrieveType: fetch attributes ? fetch shapes ?
    // resultProperties: layer * maxResult * startIndex
}

/**
 * @package CorePlugins
 */
class ResultElement {

    public $id;
    public $values;
}

/**
 * @package CorePlugins
 */
class LayerResult {
    
    public $layerId;
    public $numResults;
    public $fields;
    public $resultElements;
}

/**
 * @package CorePlugins
 */
class QueryResult {

    public $layerResults;
}

?>