<?php

class QueryRequest {

    public $bbox;
    
    // if empty: server should select layers from layersRequest  
    public $layerIds;
    
    // retrieveType: fetch attributes ? fetch shapes ?
    // resultProperties: layer * maxResult * startIndex
}

class ResultElement {

    public $id;
    public $values;
}

class LayerResult {
    
    public $layerId;
    public $numResults;
    public $fields;
    public $resultElements;
}

class QueryResult {

    public $layerResults;
}

?>